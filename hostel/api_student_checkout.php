<?php
// hostel/api_student_checkout.php

// This API handles the student "Check-Out" process.
// It receives JSON data (reason, description, proof) and creates a new
// "OUT" record in the student_movements table.

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

// 3. Get the data from the JavaScript fetch()
// The data is sent as raw JSON, so we need to read it this way.
$data = json_decode(file_get_contents('php://input'), true);

// 4. Validate input
if (empty($data['reason'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Reason is required']);
    exit();
}

$reason = $data['reason'];
$description = $data['description'] ?? null; // Optional
$proof = $data['proof'] ?? null;         // Optional

// 5. CRITICAL: Check if the student is already 'OUT'
// We don't want them to be able to check out twice.
$stmt_check = $conn->prepare("SELECT status FROM student_movements WHERE student_id = ? ORDER BY time_out DESC LIMIT 1");
$stmt_check->bind_param("i", $student_id);
$stmt_check->execute();
$result_check = $stmt_check->get_result();

if ($result_check->num_rows > 0) {
    $last_status = $result_check->fetch_assoc()['status'];
    if ($last_status == 'OUT') {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'You are already checked out. Please check-in first.']);
        $stmt_check->close();
        $conn->close();
        exit();
    }
}
$stmt_check->close();

// 6. Insert the new "OUT" record
// time_out is set to NOW() by the database (or we can do it here)
// time_in is left NULL by default
// status is set to 'OUT'
$stmt = $conn->prepare("INSERT INTO student_movements (student_id, time_out, reason, description, proof, status) VALUES (?, NOW(), ?, ?, ?, 'OUT')");
$stmt->bind_param("isss", $student_id, $reason, $description, $proof);

if ($stmt->execute()) {
    // Success
    echo json_encode(['success' => true, 'message' => 'Checked out successfully']);
} else {
    // Failure
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Database error. Could not check out.']);
}

$stmt->close();
$conn->close();

?>
