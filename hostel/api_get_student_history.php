<?php
// api_get_student_history.php
// Fetches movement logs.
// THIS FILE IS NOW USED BY BOTH STUDENT AND WARDEN.
// - If logged-in user is a 'student', it fetches their *own* history.
// - If logged-in user is a 'warden', it fetches the history for the 'student_id' passed in the URL.

session_start();

// FIX: Suppress PHP warnings
ini_set('display_errors', 0);
error_reporting(0);

require_once 'db_connect.php'; // Provides getDB()

header('Content-Type: application/json');
$response = ['success' => false, 'message' => 'An unknown error occurred.', 'history' => []];

// 1. Check if user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    $response['message'] = 'Error: Not authenticated.';
    echo json_encode($response);
    exit;
}

$role = $_SESSION['role'];
$user_id_to_fetch = null;

// 2. Determine which user's history to fetch
if ($role === 'student') {
    // Students can ONLY fetch their own history
    $user_id_to_fetch = $_SESSION['user_id'];
    
} elseif ($role === 'warden') {
    // Wardens can fetch a specific student's history
    if (!isset($_GET['student_id'])) {
        $response['message'] = 'Error: No student ID provided.';
        echo json_encode($response);
        exit;
    }
    $user_id_to_fetch = (int)$_GET['student_id'];
    
} else {
    // Unknown role
    $response['message'] = 'Error: Unauthorized role.';
    echo json_encode($response);
    exit;
}

// 3. Database Connection
$conn = getDB();
if (!$conn) {
    $response['message'] = 'Error: Database connection failed.';
    echo json_encode($response);
    exit;
}

try {
    // 4. Prepare the SQL query
    // This query selects all the necessary columns
    $stmt = $conn->prepare(
        "SELECT 
            COALESCE(status, 'N/A') AS status, 
            reason, 
            DATE_FORMAT(timestamp, '%h:%i %p on %e %b %Y') AS formatted_timestamp 
         FROM movement_log 
         WHERE user_id = ? 
         ORDER BY timestamp DESC"
    );
    
    if ($stmt === false) {
        throw new Exception("Database prepare failed: " . $conn->error);
    }

    $stmt->bind_param("i", $user_id_to_fetch);
    
    if (!$stmt->execute()) {
        throw new Exception("Database execute failed: " . $stmt->error);
    }

    // 5. Fetch all results
    $result = $stmt->get_result();
    $history_logs = [];
    
    // This loop formats the data for the JavaScript
    while ($row = $result->fetch_assoc()) {
        $history_logs[] = [
            'status' => $row['status'],
            'reason' => $row['reason'],
            'timestamp' => $row['formatted_timestamp']
        ];
    }

    $stmt->close();
    $conn->close();

    // 6. Send success response
    $response['success'] = true;
    $response['history'] = $history_logs;
    $response['message'] = 'History fetched successfully.';

} catch (Exception $e) {
    if ($conn) $conn->close();
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
?>