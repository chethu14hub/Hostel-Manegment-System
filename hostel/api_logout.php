<?php
// api_logout.php
session_start();

// Unset all of the session variables
$_SESSION = [];

// Destroy the session
session_destroy();

// Redirect to login page
// We use a relative path to go up one directory and then to index.html
header('Location: ../index.html');
exit;
?>
