<?php
// api_submit_feedback.php
// Saves new anonymous food feedback to the database.

ini_set('display_errors', 0);
error_reporting(0);
require_once 'db_connect.php'; // Provides getDB()

header('Content-Type: application/json');
$response = ['success' => false, 'message' => 'An unknown error occurred.'];

// 1. Get data from JSON payload
$data = json_decode(file_get_contents('php://input'), true);

// --- THIS IS THE FIX ---
// The keys in the check now match what feedback.html sends
if (!$data || !isset($data['day_of_week']) || !isset($data['meal_type']) || !isset($data['rating'])) {
    $response['message'] = 'Error: Invalid request data. Missing fields.';
    echo json_encode($response);
    exit;
}
// --- END OF FIX ---

// 2. Sanitize and validate
// Variables are now assigned from the correct keys
$day_of_week = $data['day_of_week'];
$meal_type = $data['meal_type'];
$rating = (int)$data['rating'];
// Comment is optional, default to null if empty
$comment = isset($data['comment']) && !empty($data['comment']) ? $data['comment'] : null;

// Simple validation
if ($rating < 1 || $rating > 5) {
    $response['message'] = 'Error: Rating must be between 1 and 5.';
    echo json_encode($response);
    exit;
}

// We trust the day and meal from the JS, but a real app would validate them
// against an approved list.

try {
    $conn = getDB();
    
    // We use NOW() for the submission time
    $sql = "INSERT INTO food_feedback (user_id, date_submitted, day_of_week, meal_type, rating, comment) 
            VALUES (NULL, NOW(), ?, ?, ?, ?)";
            
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        throw new Exception("Database prepare failed: " . $conn->error);
    }

    // Bind parameters: s-string, i-integer
    $stmt->bind_param("ssis", $day_of_week, $meal_type, $rating, $comment);
    
    if (!$stmt->execute()) {
        throw new Exception("Database execute failed: " . $stmt->error);
    }

    $stmt->close();
    $conn->close();
    
    $response['success'] = true;
    $response['message'] = 'Feedback submitted successfully.';

} catch (Exception $e) {
    if ($conn) $conn->close();
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
?>