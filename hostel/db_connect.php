<?php

function getDB() {
    // Database configuration
    $servername = "localhost";
    $username = "root";
    $password = ""; // Default XAMPP password is blank
    $dbName = "hostel_db"; // <-- We will connect directly to this

    // Create connection to the MySQL server *and* select the database
    $conn = new mysqli($servername, $username, $password, $dbName);

    // Check connection
    // If the database connection fails (e.g., DB doesn't exist, wrong password, etc.)
    if ($conn->connect_error) {
        // Stop script and show error
        // This is what api_register.php will send back as an error
        die("Connection failed: " . $conn->connect_error);
    }

    // If we are here, the connection is successful and the database is selected.
    return $conn;
}
?>

