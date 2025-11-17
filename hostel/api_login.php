<?php
// api_login.php
// This version has enhanced error reporting to find the database problem.

require_once 'db_connect.php'; // Provides getDB()

header('Content-Type: application/json');
$response = ['success' => false, 'message' => 'An unknown error occurred.'];

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (json_last_error() !== JSON_ERROR_NONE || !isset($data['username']) || !isset($data['password'])) {
        throw new Exception("Invalid JSON or missing fields.");
    }

    $username = $data['username'];
    $password = $data['password'];

    $conn = getDB();

    // Prepare the SQL statement
    // FIXED: Added 'username' to the SELECT list to fix the 'Undefined index' notice
    $stmt = $conn->prepare("SELECT user_id, username, password_hash, role, name FROM users WHERE username = ?");

    // --- THIS IS THE PART THAT IS FAILING ---
    if ($stmt === false) {
        // Send back the REAL database error message
        $response['message'] = "Database error: " . $conn->error;
        echo json_encode($response);
        $conn->close();
        exit;
    }
    // --- END OF DEBUG SECTION ---

    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        
        if (password_verify($password, $user['password_hash'])) {
            // Password is correct! Start the session.
            
            // --- THE FIX IS HERE ---
            // We trim any hidden whitespace from the role
            $user_role = trim($user['role']);
            // --- END OF FIX ---

            session_start();
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['name'] = $user['name'];
            $_SESSION['role'] = $user_role; // Use the trimmed role

            $response['success'] = true;
            $response['message'] = "Login successful!";
            $response['role'] = $user_role; // Send the trimmed role
        } else {
            // Invalid password
            $response['message'] = "Invalid username or password.";
        }
    } else {
        // No user found
        $response['message'] = "Invalid username or password.";
    }

    $stmt->close();
    $conn->close();

} catch (Exception $e) {
    $response['message'] = "Server Exception: " . $e->getMessage();
}

echo json_encode($response);
?>