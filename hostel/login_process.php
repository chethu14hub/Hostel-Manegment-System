<?php
// login_process.php
// This file handles the login logic.

// 1. Start the session
// A session is a way to store user data (like login status)
// across multiple pages. This line MUST be at the very top.
session_start();

// 2. Include our database connection "key"
include 'db_connect.php';

// 3. Check if the form was submitted (it should be POST)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // 4. Get username and password from the form
    $username = $_POST['username'];
    $password = $_POST['password'];

    // 5. Prepare a SQL statement to prevent SQL injection (very important!)
    // We are looking for a user with the matching username.
    $stmt = $conn->prepare("SELECT id, username, full_name, password, role FROM users WHERE username = ?");
    $stmt->bind_param("s", $username); // "s" means string
    $stmt->execute();
    $result = $stmt->get_result();

    // 6. Check if we found a user
    if ($result->num_rows == 1) {
        // User found! Fetch their data.
        $user = $result->fetch_assoc();

        // 7. Verify the password
        // We compare the typed password with the *hashed* password in the database.
        // This is why we stored the hash in Step 1!
        if (password_verify($password, $user['password'])) {
            // Password is correct!
            
            // 8. Store user data in the session
            // This is the "login" part.
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['role'] = $user['role'];

            // 9. Redirect based on their role
            if ($user['role'] == 'warden') {
                header("Location: warden_dashboard.php");
                exit();
            } else {
                header("Location: student_dashboard.php");
                exit();
            }

        } else {
            // Invalid password
            // Redirect back to the login page with an error message
            header("Location: login.html?error=1");
            exit();
        }
    } else {
        // No user found with that username
        header("Location: login.html?error=1");
        exit();
    }

    $stmt->close();
}

$conn->close();
?>

