<?php
// hostel/api_get_my_history.php

// This API fetches the movement history (check-ins and check-outs)
// for *only* the currently logged-in student.

session_start();
include 'db_connect.php';

// 1. Check if user is logged in and is a student
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// 2. We only need to GET data, so no POST check is needed.

$student_id = $_SESSION['user_id'];
$history = [];

// 3. Select all movement records for this specific student
// We order by time_out DESC to get the most recent movements first.
$stmt = $conn->prepare("SELECT status, time_out, time_in, reason, description, proof 
                        FROM student_movements 
                        WHERE student_id = ? 
                        ORDER BY time_out DESC");
$stmt->bind_param("i", $student_id);

if ($stmt->execute()) {
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $history[] = $row; // Add each row to our $history array
    }
    
    // 4. Send the data back as JSON
    echo json_encode(['success' => true, 'history' => $history]);

} else {
    // 5. Handle database error
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Database error. Could not fetch history.']);
}

$stmt->close();
$conn->close();

?>
