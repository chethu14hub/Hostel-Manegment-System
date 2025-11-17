<?php
// api_log_movement.php
// Logs a "Check-In" or "Check-Out" for the student.
// NOW HANDLES a "reason" for check-out and an optional "checkoutTime".

session_start();

// FIX: Set the timezone to ensure date() function works correctly.
// This will be based on the server's location or app's target audience.
// Using 'Asia/Kolkata' based on your location.
date_default_timezone_set('Asia/Kolkata');

ini_set('display_errors', 0);
error_reporting(0);
require_once 'db_connect.php'; // Provides getDB()

header('Content-Type: application/json');
$response = ['success' => false, 'message' => 'An unknown error occurred.'];

// 1. Get User ID from session
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    $response['message'] = 'Error: Not authenticated or not a student.';
    echo json_encode($response);
    exit;
}
$user_id = $_SESSION['user_id'];

// 2. Get data from JSON payload
$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['status'])) {
    $response['message'] = 'Error: Invalid request data.';
    echo json_encode($response);
    exit;
}

$status = $data['status'];
$reason = null;
$timestamp_sql = "NOW()"; // Default to current server time

// 3. Handle different statuses
$conn = getDB();
if (!$conn) {
    $response['message'] = 'Error: Database connection failed.';
    echo json_encode($response);
    exit;
}

try {
    $conn->begin_transaction();

    // --- Check-Out Logic ---
    if ($status === 'Check-Out') {
        // Reason is now required for check-out
        if (empty($data['reason'])) {
            $response['message'] = 'Error: A reason is required for check-out.';
            echo json_encode($response);
            $conn->rollback(); // Rollback transaction
            $conn->close();
            exit;
        }
        $reason = $data['reason'];

        // NEW: Handle Manual Timestamp
        // Check if a manual checkoutTime was provided
        if (!empty($data['checkoutTime'])) {
            // The HTML input 'datetime-local' sends a format like: "YYYY-MM-DDTHH:MM"
            // We can use this directly, as MySQL understands it.
            $timestamp_sql = "?"; // We will bind this as a parameter
        }

    }
    // --- Check-In Logic ---
    // (No special logic needed for Check-In)

    // 4. Prepare and execute the SQL insert
    // We use a dynamic SQL part for the timestamp
    $sql = "INSERT INTO movement_log (user_id, status, reason, timestamp) VALUES (?, ?, ?, $timestamp_sql)";
    $stmt = $conn->prepare($sql);

    if ($stmt === false) {
        throw new Exception("Database prepare failed: " . $conn->error);
    }

    // Bind parameters
    if (!empty($data['checkoutTime']) && $status === 'Check-Out') {
        // Bind 4 params: user_id, status, reason, manual_time
        $stmt->bind_param("isss", $user_id, $status, $reason, $data['checkoutTime']);
    } else {
        // Bind 3 params: user_id, status, reason (timestamp is NOW())
        $stmt->bind_param("iss", $user_id, $status, $reason);
    }
    
    if (!$stmt->execute()) {
        // FIX: Changed 'V' to '.' for string concatenation
        throw new Exception("Database execute failed: " . $stmt->error);
    }

    $stmt->close();
    $conn->commit(); // Commit transaction
    $conn->close();

    // 5. Send success response
    // Get the current server time for the success message (even if they entered a manual time)
    // This provides a consistent "Logged at..." message.
    
    // FIX: Changed 'g' (1-12) to 'h' (01-12) for a clearer, more robust time format.
    $logged_time_str = date('h:i A \o\n j M Y');
    
    $response['success'] = true;
    $response['message'] = "Successfully $status at $logged_time_str.";

} catch (Exception $e) {
    $conn->rollback(); // Rollback on error
    if ($conn) $conn->close();
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
?>