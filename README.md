Hostel Management & Feedback System

A full-stack web application built with PHP, MySQL, and JavaScript that provides a complete management solution for a student hostel. This project features distinct user roles (Student and Warden), real-time movement logging, and a public-facing, time-locked food feedback system.

Features

1. Student Management

Secure Registration & Login: Students can create their own accounts. All passwords are encrypted using password_hash().

Role-Based Dashboards: The system provides two separate dashboards:

Student Dashboard: For students to manage their own status.

Warden Dashboard: For the warden to manage all students.

Session Management: Secure PHP sessions ($_SESSION) are used to manage user authentication and role access.

2. Student Movement Tracking

Check-In / Check-Out: Students can log their movements.

Reason for Check-Out: (A key feature) When checking out, students are required to submit a reason (e.g., "Going to library," "Weekend leave").

Manual Time Entry: Students can (optionally) back-date their check-out time, which is then saved to the database.

Live History: The student dashboard updates in real-time to show their complete movement history (status, reason, and time).

3. Warden Administration

Full Student Roster: The warden's dashboard displays a complete list of all registered students and their last known status (Check-In or Check-Out).

Detailed History Modal: The warden can click "View History" on any student to open a modal and view that student's complete, detailed movement log (status, reason, and timestamp).

4. Public Food Feedback System

Anonymous Feedback: A public-facing page (feedback.html) that does not require a login, allowing anyone (students, guests) to leave feedback.

Dynamic Day Selection: The UI features a clean "Select Day" dropdown, which defaults to the current day and loads the 3-meal menu (Breakfast, Lunch, Dinner).

Time-Locked Submissions: Feedback is time-locked based on the server's time (IST). The "Give Feedback" button for a meal (e.g., Lunch) only unlocks after that meal's service time (e.g., 1:30 PM), preventing spam.

"Notepad" Review Feature: Users can click the "(X reviews)" link on any meal to open a "notepad" area. This fetches and displays a list of all individual anonymous comments and star ratings for that specific meal.

No Spam: Users can submit feedback multiple times (as requested).

Technologies Used

Backend: PHP

Database: MySQL

Frontend: HTML5, Tailwind CSS, JavaScript (ES6+)

Server Environment: XAMPP (Apache)

Core Features:

Full-stack CRUD (Create, Read, Update, Delete) operations

RESTful API principles (using PHP to serve JSON to the frontend)

Asynchronous JavaScript (Fetch API)

Complex SQL queries (JOINs, Subqueries, GROUP_CONCAT)

Secure password hashing (password_hash and password_verify)

Dynamic, single-page-like UI (JavaScript for modals, table updates, and error handling)
