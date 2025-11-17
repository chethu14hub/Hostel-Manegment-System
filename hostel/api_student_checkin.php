<?php
// hostel/api_student_checkin.php

// This API handles the student "Check-In" process.
// It finds the student's open "OUT" record and updates it.

session_start();
include 'db_connect.php';

// 1. Check if user is logged in and is a student
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// 2. Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

$student_id = $_SESSION['user_id'];

// 3. Find the student's *last* record to see if they are 'OUT'
$stmt_check = $conn->prepare("SELECT id, status FROM student_movements WHERE student_id = ? ORDER BY time_out DESC LIMIT 1");
$stmt_check->bind_param("i", $student_id);
$stmt_check->execute();
$result_check = $stmt_check->get_result();

if ($result_check->num_rows == 0) {
    // No records exist, they can't check in.
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Cannot check in. No check-out record found.']);
    $stmt_check->close();
    $conn->close();
    exit();
}

$last_record = $result_check->fetch_assoc();

// 4. Check if they are actually OUT
if ($last_record['status'] == 'IN') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'You are already checked in.']);
    $stmt_check->close();
    $conn->close();
    exit();
}

$stmt_check->close();

// 5. Update the open record
// We set time_in to NOW() and status to 'IN'
// We identify the record by its unique 'id'
$record_id_to_update = $last_record['id'];

$stmt_update = $conn->prepare("UPDATE student_movements SET time_in = NOW(), status = 'IN' WHERE id = ?");
$stmt_update->bind_param("i", $record_id_to_update);

if ($stmt_update->execute()) {
    // Success
    echo json_encode(['success' => true, 'message' => 'Checked in successfully']);
} else {
    // Failure
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Database error. Could not check in.']);
}

$stmt_update->close();
$conn->close();

?>

