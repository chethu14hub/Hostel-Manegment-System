<?php
// hostel/api_food_feedback.php

// This API handles saving the student's daily food feedback.

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
$data = json_decode(file_get_contents('php://input'), true);

// 4. Validate input
if (empty($data['meal']) || empty($data['rating'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Meal and rating are required']);
    exit();
}

$meal = $data['meal'];
$rating = (int)$data['rating']; // Ensure it's an integer
$comment = $data['comment'] ?? null; // Optional
$date_today = date('Y-m-d'); // Get today's date

// 5. Check for duplicate feedback
// We don't want the same student to rate the same meal twice on the same day.
$stmt_check = $conn->prepare("SELECT id FROM food_feedback WHERE student_id = ? AND date = ? AND meal = ?");
$stmt_check->bind_param("iss", $student_id, $date_today, $meal);
$stmt_check->execute();
$result_check = $stmt_check->get_result();

if ($result_check->num_rows > 0) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'You have already submitted feedback for this meal today.']);
    $stmt_check->close();
    $conn->close();
    exit();
}
$stmt_check->close();

// 6. Insert the new feedback record
$stmt = $conn->prepare("INSERT INTO food_feedback (student_id, date, meal, rating, comment) VALUES (?, ?, ?, ?, ?)");
$stmt->bind_param("issis", $student_id, $date_today, $meal, $rating, $comment);

if ($stmt->execute()) {
    // Success
    echo json_encode(['success' => true, 'message' => 'Feedback submitted successfully!']);
} else {
    // Failure
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Database error. Could not submit feedback.']);
}

$stmt->close();
$conn->close();

?>
