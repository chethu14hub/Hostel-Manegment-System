<?php
// hostel/api_get_my_status.php

// This API checks the database to see if the student is currently IN or OUT.
// It finds the *last* movement record for the logged-in student.

session_start();
include 'db_connect.php';

// 1. Check if user is logged in and is a student
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student') {
    // Not a student or not logged in
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$student_id = $_SESSION['user_id'];

// 2. Find the *most recent* movement record for this student
// We order by time_out DESC (most recent first) and take just 1.
$stmt = $conn->prepare("SELECT status, reason, time_out FROM student_movements WHERE student_id = ? ORDER BY time_out DESC LIMIT 1");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$result = $stmt->get_result();

$response = [];

if ($result->num_rows > 0) {
    // We found a record
    $last_record = $result->fetch_assoc();
    
    if ($last_record['status'] == 'OUT') {
        // The student is currently OUT
        $response = [
            'success' => true,
            'status' => 'OUT',
            'last_record' => [
                'reason' => $last_record['reason'],
                'time_out' => $last_record['time_out']
            ]
        ];
    } else {
        // The student's last record is 'IN'
        $response = [
            'success' => true,
            'status' => 'IN'
        ];
    }
} else {
    // No records found for this student. This is their first time.
    // We assume they are IN the hostel by default.
    $response = [
        'success' => true,
        'status' => 'IN'
    ];
}

$stmt->close();
$conn->close();

// 3. Send the response back to the JavaScript
header('Content-Type: application/json');
echo json_encode($response);
exit();

?>
