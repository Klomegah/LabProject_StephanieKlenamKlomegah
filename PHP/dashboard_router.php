<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../Login and Signup/login.html");
    exit();
}

// Redirect based on role - check if user is in faculty table
require_once 'db.php';
require_once 'faculty_check.php';

$user_id = $_SESSION['user_id'] ?? null;

if ($user_id && isFaculty($con, $user_id)) {
    header("Location: ../Dashboards/facultydashboard.php");
} else {
    header("Location: ../Dashboards/studentdashboard.php");
}

exit();
?>


