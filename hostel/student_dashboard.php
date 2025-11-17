<?php
// student_dashboard.php
session_start();

// This is the security check.
// 1. Is a user_id set in the session? (Are they logged in?)
// 2. Is their role 'student'?
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student') {
    // If not, they don't belong here. Kick them back to the login page.
    header("Location: login.html");
    exit(); // Stop the script
}

// If the script continues, we know they are a logged-in student.
// We can safely get their data from the session.
$username = $_SESSION['username'];
$full_name = $_SESSION['full_name'] ?? $username; // Use full_name, or fallback to username

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-gray-100 min-h-screen">
    <nav class="bg-white shadow-md p-4 flex justify-between items-center">
        <h1 class="text-2xl font-bold text-blue-600">Student Dashboard</h1>
        <div class="flex items-center">
            <!-- Greet the user by name -->
            <span class="text-gray-700 mr-4">Welcome, <strong><?php echo htmlspecialchars($full_name); ?></strong>!</span>
            <!-- Link to our logout script -->
            <a href="logout.php" class="bg-red-500 hover:bg-red-600 text-white font-semibold py-2 px-4 rounded-lg transition duration-200">
                Logout
            </a>
        </div>
    </nav>

    <div class="container mx-auto p-8">
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-2xl font-semibold mb-4">Your Dashboard</h2>
            <p>Welcome to your personal dashboard. We will add the check-in/check-out forms and food feedback forms here in the next step.</p>
        </div>
    </div>

</body>
</html>

