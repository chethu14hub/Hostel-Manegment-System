<?php
// api_check_session.php
// Starts the session and returns the logged-in user's data.

session_start(); // This is the most important line!

header('Content-Type: application/json');

// Check if the user is logged in by looking for the session variable
if (isset($_SESSION['user_id']) && isset($_SESSION['role'])) {
    
    // User is logged in. Send back their data.
    echo json_encode([
        'success' => true,
        'user_id' => $_SESSION['user_id'],
        'username' => $_SESSION['username'],
        'name' => $_SESSION['name'],
        'role' => $_SESSION['role']
    ]);

} else {
    // User is not logged in.
    echo json_encode([
        'success' => false,
        'message' => 'User not authenticated.'
    ]);
}
?>

