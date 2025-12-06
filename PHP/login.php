<?php 

session_start();
require 'db.php';

// Set JSON response header
header('Content-Type: application/json');

//Get JSON input
$input =json_decode(file_get_contents("php://input"),true); /// if using JSON

if(!isset($input['email'], $input['password'])){
    $state=["success"=>false,"message"=>"Invalid Input"]; // I keep getting "Invalid Input" 
    echo json_encode($state);
    exit();
}

// this part of my code doesn't seem to be working

// email and password from input
$email=$input['email'];
$password=$input['password'];

//Find user by email
$stmt = $con->prepare("SELECT user_id, first_name, last_name, email, password_hash, role FROM users WHERE email = ?");
$stmt->bind_param("s", $email); // "s" indicates the type is string
$stmt->execute();
$result = $stmt->get_result();

if($result->num_rows === 0){
    // No user found with that email
    $state=["success"=>false,"message"=>"No user found with that email"];
    echo json_encode($state);
    exit();
}

$user = $result->fetch_assoc();

// Verify password
if(password_verify($password, $user['password_hash'])){
    // Password is correct, set session variables
    $_SESSION['user_id'] = $user['user_id'];
    $_SESSION['first_name'] = $user['first_name'];
    $_SESSION['last_name'] = $user['last_name'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['role'] = $user['role'] ?? 'student'; // Default to student if role not set
    
    // Get faculty_id or student_id based on role
    if ($_SESSION['role'] === 'faculty') {
        $faculty_stmt = $con->prepare("SELECT faculty_id FROM faculty WHERE faculty_id = ?");
        $faculty_stmt->bind_param("i", $user['user_id']);
        $faculty_stmt->execute();
        $faculty_result = $faculty_stmt->get_result();
        if ($faculty_result->num_rows > 0) {
            $_SESSION['faculty_id'] = $user['user_id'];
        }
        $faculty_stmt->close();
    } else {
        $student_stmt = $con->prepare("SELECT student_id FROM students WHERE student_id = ?");
        $student_stmt->bind_param("i", $user['user_id']);
        $student_stmt->execute();
        $student_result = $student_stmt->get_result();
        if ($student_result->num_rows > 0) {
            $_SESSION['student_id'] = $user['user_id'];
        }
        $student_stmt->close();
    }

    // Note: Faculty intern detection happens in dashboard_router.php
    // For now, return the role as stored in database
    echo json_encode([
        "success" => true,
        "message" => "Login successful", 
        "role" => $_SESSION['role']
    ]);

} else {
    // Password is incorrect
    $state=["success"=>false,"message"=>"Incorrect password"];
    echo json_encode($state);
    exit();
}






















?>