<?php
require 'db.php';
session_start();

$login_page="../Login and Signup/login.html";
$dashboard_page="dashboard.php";

// Define a function to check if user is logged in
if(isset($_SESSION['user_id'])){
    // User is logged in, redirect to dashboard
   header("Location: ".$dashboard_page);
}else{
    // User is not logged in, redirect to login page
    header("Location: ".$login_page);
}

exit();



?>