<?php
session_start();

// For non-AJAX requests, check if user is logged in
if ($_SERVER['REQUEST_METHOD'] != 'POST' || !isset($_SERVER['HTTP_ACCEPT']) || strpos($_SERVER['HTTP_ACCEPT'], 'application/json') === false) {
    // Authorization check: look for user_id in session
    if (!isset($_SESSION['user_id'])) {
        // Define the login page path
        $login_path = '../Login and Signup/login.html';
        
        // Redirect to the login page
        header("Location: $login_path");
        exit();
    }
}

// If the session variable is set, the script continues to the protected content.
// If it's an AJAX request (POST + application/json), we skip redirecting to prevent breaking the API call.
?>