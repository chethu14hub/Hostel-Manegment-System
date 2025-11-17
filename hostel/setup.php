<?php
// Final, correct setup script.
// This file creates the database AND all tables with the correct columns.
// It does NOT use db_connect.php.

$servername = "localhost";
$username = "root";
$password = ""; // Your XAMPP password (usually blank)

// --- 1. Connect to MySQL Server (without selecting a database) ---
$conn = new mysqli($servername, $username, $password);

// Check connection
if ($conn->connect_error) {
    die("Connection to MySQL server failed: " . $conn->connect_error);
}
echo "Connected to MySQL server successfully.<br>";

// --- 2. Create the Database ---
$dbName = "hostel_db";
$sql_createdb = "CREATE DATABASE IF NOT EXISTS $dbName";

if ($conn->query($sql_createdb) === TRUE) {
    echo "Database '$dbName' created successfully or already exists.<br>";
} else {
    die("Error creating database: " . $conn->error);
}

// --- 3. Select the new Database ---
$conn->select_db($dbName);

// --- 4. Create 'users' table (with ALL correct columns) ---
$sql_users = "
CREATE TABLE IF NOT EXISTS users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role VARCHAR(10) NOT NULL
);";

if ($conn->query($sql_users) === TRUE) {
    echo "Table 'users' created successfully or already exists.<br>";
} else {
    echo "Error creating 'users' table: " . $conn->error . "<br>";
}

// --- 5. Create 'movement_log' table (with ALL correct columns) ---
$sql_movement = "
CREATE TABLE IF NOT EXISTS movement_log (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    timestamp DATETIME NOT NULL,
    status ENUM('Checked-Out', 'Checked-In') NOT NULL,
    reason VARCHAR(255) DEFAULT NULL,
    FOREIGN KEY (user_id) REFERENCES users(user_id)
);";

if ($conn->query($sql_movement) === TRUE) {
    echo "Table 'movement_log' created successfully or already exists.<br>";
} else {
    echo "Error creating 'movement_log' table: " . $conn->error . "<br>";
}


// --- 6. NEW: Create 'food_feedback' table (MODIFIED FOR ANONYMOUS FEEDBACK) ---
// We make user_id NULLABLE and remove the FOREIGN KEY constraint
// This allows entries to be saved without a logged-in user.
$sql_food_feedback = "
CREATE TABLE IF NOT EXISTS food_feedback (
    feedback_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL DEFAULT NULL, 
    date_submitted DATETIME NOT NULL,
    day_of_week VARCHAR(10) NOT NULL, 
    meal_type VARCHAR(10) NOT NULL, 
    rating INT NOT NULL, 
    comment TEXT DEFAULT NULL
);";

if ($conn->query($sql_food_feedback) === TRUE) {
    echo "Table 'food_feedback' created successfully or already exists.<br>";
} else {
    echo "Error creating 'food_feedback' table: " . $conn->error . "<br>";
}


// --- 7. Add Default Warden User (if not exists) ---
$warden_user = "warden";
$warden_pass = password_hash("wardenpass", PASSWORD_DEFAULT);
$warden_name = "Warden Name";
$warden_role = "warden";

$stmt_check = $conn->prepare("SELECT user_id FROM users WHERE username = ?");

// This check handles the case where the table creation failed
if ($stmt_check === false) {
    die("Error preparing statement: " . $conn->error);
}

$stmt_check->bind_param("s", $warden_user);
$stmt_check->execute();
$result = $stmt_check->get_result();

if ($result->num_rows == 0) {
    $stmt_insert = $conn->prepare("INSERT INTO users (name, username, password_hash, role) VALUES (?, ?, ?, ?)");
    $stmt_insert->bind_param("ssss", $warden_name, $warden_user, $warden_pass, $warden_role);
    if ($stmt_insert->execute()) {
        echo "Default warden user created.<br>";
    }
    $stmt_insert->close();
} else {
    echo "Default warden user already exists.<br>";
}
$stmt_check->close();

$conn->close();

echo "<hr><strong>Setup complete!</strong><br>";
echo "<a href='login.html'>Go to the login page</a>";

?>