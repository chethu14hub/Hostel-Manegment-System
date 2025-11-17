<?php
require_once 'db_connect.php';
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

// --- Input Validation ---
if (!$data || !isset($data['name']) || !isset($data['username']) || !isset($data['password'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid input.']);
    exit();
}

$name = $data['name'];
$username = $data['username'];
$password = $data['password'];

if (empty($name) || empty($username) || empty($password)) {
    echo json_encode(['success' => false, 'message' => 'All fields are required.']);
    exit();
}

try {
    $conn = getDB();

    // --- 1. Check if username already exists ---
    // MODIFICATION: Changed 'SELECT user_id' to 'SELECT username' to fix "Unknown column 'user_id'" error
    $stmt_check = $conn->prepare("SELECT username FROM users WHERE username = ?");
    if ($stmt_check === false) {
        throw new Exception('Prepare check failed: ' . $conn->error);
    }
    
    $stmt_check->bind_param("s", $username);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();

    if ($result_check->num_rows > 0) {
        // Username already exists, send a *clean* error
        echo json_encode(['success' => false, 'message' => 'Username is already taken.']);
        $stmt_check->close();
        $conn->close();
        exit();
    }
    $stmt_check->close();

    // --- 2. Hash password ---
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    if ($hashed_password === false) {
        throw new Exception('Password hashing failed.');
    }

    // --- 3. Insert new user ---
    $role = 'student'; // Default role
    $stmt_insert = $conn->prepare("INSERT INTO users (name, username, password_hash, role) VALUES (?, ?, ?, ?)");
    if ($stmt_insert === false) {
        throw new Exception('Prepare insert failed: ' . $conn->error);
    }
    
    $stmt_insert->bind_param("ssss", $name, $username, $hashed_password, $role);

    // --- THIS IS THE CRITICAL CHECK ---
    // We check if execute() *actually* worked
    if ($stmt_insert->execute()) {
        // It worked!
        echo json_encode(['success' => true, 'message' => 'Registration successful!']);
    } else {
        // It failed!
        throw new Exception('Execute failed: ' . $stmt_insert->error);
    }

    $stmt_insert->close();
    
} catch (Exception $e) {
    // Catch any error and send it as a clean JSON message
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}

$conn->close();
?>


