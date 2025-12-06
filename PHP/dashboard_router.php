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
    // Check if user is a faculty intern
    if (isset($_SESSION['is_faculty_intern']) && $_SESSION['is_faculty_intern'] === true) {
        header("Location: ../Dashboards/facultyinterndashboard.php");
    } else {
        header("Location: ../Dashboards/facultydashboard.php");
    }
} else {
    header("Location: ../Dashboards/studentdashboard.php");
}

exit();
?>


