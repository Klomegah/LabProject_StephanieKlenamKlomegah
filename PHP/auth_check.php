<?php
session_start();


//ensure the request method is not a fettch/AJAX request that should return JSON
if($_SERVER['REQUEST_METHOD'] != 'POST' || !isset($_SERVER['HTTP_ACCEPT']) || strpos($_SERVER['HTTP_ACCEPT'], 'application/json') === false){
    //authorisation check : look for user_id in session
    if(isset($_SESSION['user_id'])){
    
        // Define the login page path relative to your protected pages (e.g., dashboard.php)
        // If protected pages are in 'Login and Signup' and login.html is also there, the path is simple.
    
        $login_path = 'login.html'; 

        // Redirect to the login page
        header("Location: $login_path");
        exit();
    }
}

// If the session variable is set, the script continues to the protected content.
// If it's an AJAX request (POST + application/json), we skip redirecting to prevent breaking the API call.
?>