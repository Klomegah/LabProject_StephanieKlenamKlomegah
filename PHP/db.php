<?php
// connecting to the database
$host="localhost";
$user="root";
$pass="";
$db="attendancemanagement";

$con=new mysqli($host,$user,$pass,$db);

if($con->connect_error){
    // Return JSON error instead of dying immediately
    // This allows the calling script to handle the error properly
    error_log("Database connection failed: " . $con->connect_error);
    // Don't die here - let the calling script handle it
    // The connection will be null/false which we can check
}
?>
