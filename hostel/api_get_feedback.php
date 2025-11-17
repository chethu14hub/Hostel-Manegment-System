<?php
// api_get_feedback.php
// --- THIS IS THE "COMPLEX" VERSION ---
// Fetches all food feedback, calculates average ratings,
// AND groups individual feedback entries for the "notepad".

ini_set('display_errors', 0);
error_reporting(0);
require_once 'db_connect.php'; // Provides getDB()

header('Content-Type: application/json');
$response = ['success' => false, 'message' => 'An unknown error occurred.', 'feedback' => []];

try {
    $conn = getDB();
    
    // --- THIS QUERY IS UPDATED ---
    // We now get the average/count AND a concatenated list of individual feedback.
    $sql = "
        SELECT 
            day_of_week, 
            meal_type, 
            AVG(rating) AS avg_rating,
            COUNT(feedback_id) AS rating_count,
            GROUP_CONCAT(
                CONCAT(rating, '::', IFNULL(comment, '')) 
                SEPARATOR '||'
            ) AS feedback_list
        FROM 
            food_feedback f1
        GROUP BY 
            day_of_week, meal_type
    ";
    
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        // --- THIS IS THE FIX ---
        // The extra "!" has been removed.
        throw new Exception("Database prepare failed: " . $conn->error);
        // --- END OF FIX ---
    }
    
    if (!$stmt->execute()) {
        throw new Exception("Database execute failed: " . $stmt->error);
    }

    $result = $stmt->get_result();
    $feedback_data = [];
    
    while ($row = $result->fetch_assoc()) {
        // Create a unique key (e.g., "Monday-Breakfast")
        $key = $row['day_of_week'] . '-' . $row['meal_type'];
        
        // Split the new feedback_list string into a proper array
        $feedback_entries = [];
        if ($row['feedback_list']) {
            $list = explode('||', $row['feedback_list']);
            foreach ($list as $entry) {
                $parts = explode('::', $entry, 2); // Split into 2 parts
                if (count($parts) === 2) {
                    $feedback_entries[] = [
                        'rating' => (int)$parts[0],
                        'comment' => $parts[1] ?: 'N/A' // Use N/A if comment is empty
                    ];
                }
            }
        }
        
        $feedback_data[$key] = [
            'avg_rating' => (float)$row['avg_rating'],
            'count' => (int)$row['rating_count'],
            'entries' => $feedback_entries // Send the array of objects
        ];
    }

    $stmt->close();
    $conn->close();

    $response['success'] = true;
    $response['feedback'] = $feedback_data;
    $response['message'] = 'Feedback fetched successfully.';

} catch (Exception $e) {
    if ($conn) $conn->close();
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
?>