<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../Login and Signup/login.html");
    exit();
}

// Redirect based on role
$role = $_SESSION['role'] ?? 'student';

if ($role === 'faculty') {
    header("Location: ../Dashboards/facultydashboard.php");
} else {
    header("Location: ../Dashboards/studentdashboard.php");
}

exit();
?>


