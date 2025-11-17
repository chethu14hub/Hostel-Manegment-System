<?php
// logout.php

// 1. Start the session so we can access it
session_start();

// 2. Unset all session variables
$_SESSION = array();

// 3. Destroy the session
session_destroy();

// 4. Redirect to the login page
header("Location: login.html");
exit();
?>

