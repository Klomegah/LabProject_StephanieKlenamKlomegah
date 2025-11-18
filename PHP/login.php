<?php 

session_start();
require 'db.php';

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
$stmt = $con->prepare("SELECT user_id, first_name, last_name, email, password_hash FROM users WHERE email = ?");
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

    echo json_encode(["success"=>true,"message"=>"Login successful"]);

} else {
    // Password is incorrect
    $state=["success"=>false,"message"=>"Incorrect password"];
    echo json_encode($state);
    exit();
}






















?>