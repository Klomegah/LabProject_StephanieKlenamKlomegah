<?php

// Enable error reporting for debugging (remove in production)
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

session_start();
require_once 'db.php';

header('Content-Type: application/json');

// Check if database connection was successful
if (!isset($con)) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Database connection variable not set."
    ]);
    exit();
}

if ($con->connect_error) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Database connection failed: " . $con->connect_error
    ]);
    exit();
}

try {
    $input = json_decode(file_get_contents("php://input"), true);
    if ($input === null) {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "message" => "JSON decode failed. Please check your input.",
            "raw" => file_get_contents("php://input")
        ]);
        exit();
    }

    //validate input here
    if(!isset($input['firstname'], $input['lastname'], $input['email'], $input['password'], $input['confirm_password'])){
        http_response_code(400);
        $state=["success"=>false,"message"=>"Invalid Input"];
        echo json_encode($state);
        exit();
    }

    $fname=$input['firstname'];
    $lname=$input['lastname'];
    $email=$input['email'];
    $password=password_hash($input['password'],PASSWORD_DEFAULT);

    // Get role from input, default to 'student'
    $role_input = isset($input['role']) ? $input['role'] : 'student';
    $is_faculty_intern = ($role_input === 'facultyintern');

    // Store as 'faculty' in database if it's facultyintern (since ENUM only allows 'student' or 'faculty')
    $role_for_db = ($role_input === 'facultyintern') ? 'faculty' : $role_input;

    // SQL COMMAND
    $INS_COM="INSERT INTO users (first_name, last_name, email, password_hash, role) VALUES (?, ?, ?, ?, ?)";

    $stmt = $con->prepare($INS_COM);

    if($stmt===false){
        http_response_code(500);
        $state=["success"=>false,"message"=>"Prepare Failed: " . $con->error];
        echo json_encode($state);
        exit();
    }

    $stmt->bind_param("sssss", $fname, $lname, $email, $password, $role_for_db);

    $excecute_success = $stmt->execute();

    if (!$excecute_success || $stmt->error) {
        $error_message = $stmt->error ? $stmt->error : "Insert Failed";
        if (strpos($error_message, 'Duplicate entry') !== false && strpos($error_message, 'email') !== false) {
            $error_message = "Email already exists. Please use a different email.";
        } elseif (strpos($error_message, 'Duplicate entry') !== false) {
            $error_message = "This information already exists in the system.";
        }
        http_response_code(400);
        $state=["success"=>false, "message"=>$error_message];
        echo json_encode($state);
        exit();
    }

    $user_id = $con->insert_id;
    $stmt->close();

    // Insert into students or faculty table based on role
    if ($role_for_db === 'student') {
        $student_stmt = $con->prepare("INSERT INTO students (student_id, first_name, last_name, email) VALUES (?, ?, ?, ?)");
        $student_stmt->bind_param("isss", $user_id, $fname, $lname, $email);
        $student_insert = $student_stmt->execute();
        if (!$student_insert) {
            http_response_code(500);
            $state=["success"=>false, "message"=>"Failed to create student record: " . $student_stmt->error];
            echo json_encode($state);
            exit();
        }
        $student_stmt->close();
    } elseif ($role_for_db === 'faculty') {
        $faculty_stmt = $con->prepare("INSERT INTO faculty (faculty_id, first_name, last_name, email) VALUES (?, ?, ?, ?)");
        $faculty_stmt->bind_param("isss", $user_id, $fname, $lname, $email);
        $faculty_insert = $faculty_stmt->execute();
        if (!$faculty_insert) {
            http_response_code(500);
            $state=["success"=>false, "message"=>"Failed to create faculty record: " . $faculty_stmt->error];
            echo json_encode($state);
            exit();
        }
        $faculty_stmt->close();
    }

    // Set session variables
    $_SESSION['user_id'] = $user_id;
    $_SESSION['first_name'] = $fname;
    $_SESSION['last_name'] = $lname;
    $_SESSION['email'] = $email;
    $_SESSION['role'] = $role_for_db;
    if ($is_faculty_intern) {
        $_SESSION['is_faculty_intern'] = true;
    }

    // Return success with role information
    $state = [
        "success" => true,
        "role" => $role_for_db,
        "is_faculty_intern" => $is_faculty_intern
    ];
    echo json_encode($state);
    exit();
    
} catch (Exception $e) {
    error_log("Signup error: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine());
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "An error occurred during signup: " . $e->getMessage(),
        "error" => $e->getMessage(),
        "file" => $e->getFile(),
        "line" => $e->getLine()
    ]);
    exit();
} catch (Error $e) {
    error_log("Signup fatal error: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine());
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "A fatal error occurred: " . $e->getMessage(),
        "error" => $e->getMessage(),
        "file" => $e->getFile(),
        "line" => $e->getLine()
    ]);
    exit();
}

// if html is rendered in php to check for a submit 
// $_SERVER['REQUEST_METHOD'] === 'POST'

// When using js and expecting a return value you echo a json instead of redirecting
//$state=["state"=>true];

//echo using json_encode(object to echo);

// ending the program
?>

<!-- require -->