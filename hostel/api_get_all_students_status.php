<?php
// api_get_all_students_status.php
// Fetches a list of all students and their most recent status for the warden.

session_start();

// Suppress warnings to ensure clean JSON output
ini_set('display_errors', 0);
error_reporting(0);

require_once 'db_connect.php'; // Provides getDB()

header('Content-Type: application/json');
$response = ['success' => false, 'message' => 'An unknown error occurred.', 'students' => []];

// 1. Security Check: Only allow 'warden'
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'warden') {
    $response['message'] = 'Error: Unauthorized access.';
    echo json_encode($response);
    exit;
}

$conn = getDB();
if (!$conn) {
    $response['message'] = 'Error: Database connection failed.';
    echo json_encode($response);
    exit;
}

try {
    // 2. Prepare the SQL query
    // This query gets all students and LEFT JOINs their *most recent* movement log.
    // It finds the most recent log by using a subquery for MAX(log_id).
    // It also includes students who have no logs yet (ml.log_id IS NULL).
    
    // We alias user_id AS id to match what the warden_dashboard.html's JavaScript expects
    $sql = "
        SELECT 
            u.user_id AS id, 
            u.name, 
            u.username,
            ml.status AS last_status,
            ml.timestamp AS last_timestamp
        FROM 
            users u
        LEFT JOIN 
            movement_log ml ON u.user_id = ml.user_id
        WHERE 
            u.role = 'student'
        AND 
            (ml.log_id = (
                SELECT MAX(m2.log_id) 
                FROM movement_log m2 
                WHERE m2.user_id = u.user_id
            ) OR ml.log_id IS NULL)
        ORDER BY 
            u.name ASC;
    ";
    
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        throw new Exception("Database prepare failed: " . $conn->error);
    }
    
    if (!$stmt->execute()) {
        throw new Exception("Database execute failed: " . $stmt->error);
    }

    // 3. Fetch all results
    $result = $stmt->get_result();
    $students_list = [];
    
    while ($row = $result->fetch_assoc()) {
        // Handle students with no history yet
        if ($row['last_status'] === null) {
            $row['last_status'] = 'N/A'; // Or 'Checked-In' as a default
            $row['last_timestamp'] = null;
        }
        $students_list[] = $row;
    }

    $stmt->close();
    $conn->close();

    // 4. Send success response
    $response['success'] = true;
    $response['students'] = $students_list;
    $response['message'] = 'Students fetched successfully.';

} catch (Exception $e) {
    if ($conn) $conn->close();
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
?>