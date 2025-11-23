<?php
session_start();

// Landing page - always redirect to login page
$login_page = "./Login and Signup/login.html";

// Check if user is logged in
if(isset($_SESSION['user_id'])){
    // User is logged in, redirect to appropriate dashboard
    $dashboard_page = "./PHP/dashboard_router.php";
    header("Location: " . $dashboard_page);
    exit();
} else {
    // User is not logged in, redirect to login page (landing page)
    header("Location: " . $login_page);
    exit();
}
?>