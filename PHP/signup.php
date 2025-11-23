<?php

session_start();
require_once 'db.php';

// Set JSON response header
header('Content-Type: application/json');

// echo post object for debugging

// INFO: the fix to the issue; Instead of using POST when passing the json, usue the line below

// $input =json_decode(file_get_contents("php://input"),true); /// if using JSON

$input = json_decode(file_get_contents("php://input"), true);
if ($input === null) {
    echo json_encode([
        "success" => false,
        "message" => "JSON decode failed. Please check your input.",
        "raw" => file_get_contents("php://input")
    ]);
    exit();
}



//validate input here
if(!isset($input['firstname'], $input['lastname'], $input['email'], $input['password'], $input['confirm_password'])){
    $state=["success"=>false,"message"=>"Invalid Input"];
    echo json_encode($state);
    exit();
}

//$input = $_POST;

$fname=$input['firstname'];
$lname=$input['lastname'];
$email=$input['email'];
$password=password_hash($input['password'],PASSWORD_DEFAULT);
$role = isset($input['role']) && in_array($input['role'], ['student', 'faculty']) ? $input['role'] : 'student';

//// Variables $varriable name; or $variablename=value;
//$fname=$_POST['firstname'];
//$lname=$_POST['lastname'];
//$email=$_POST['email'];
//$password=password_hash($_POST['password'],PASSWORD_DEFAULT);

// storing values from the html page $_POST['field id'];
// hasing password for dsafety pasword_hash(password,hash_algorithm[PASSWORD_DEFAULT, PASSWORD_BCRYPT, etc]);

//connection new mysqli(all the above variables)


// check connection use c->connect_error
    // ending the program if that is the case
    //OR
    //die("connection failed: ".$c->connect_error);


// Making SQL COMMANDS [getting th users table(SELECT * FROM users) / adding to the user table(INSERT INTO users (first_name, last_name, email, password_hash) VALUES ('$fname', '$lname', '$email', '$hashedpassword'))]
// $INS_COM="INSERT INTO users (first_name, last_name, email, password_hash) VALUES ('$fname', '$lname', '$email', '$password')";
// $con->prepare($INS_COM);

// Correct sql inhjection prevention

// SQL COMMAND

//$c=$con->query($INS_COM);

$INS_COM="INSERT INTO users (first_name, last_name, email, password_hash, role) VALUES (?, ?, ?, ?, ?)"; // ?-> where the cvalues will go

//preparing the statement
$stmt = $con->prepare($INS_COM);

//checking if prepare was successful
if($stmt===false){
    $state=["success"=>false,"message"=>"Prepare Failed"];
    echo json_encode($state);
    exit();
}

//binding the parameters
$stmt->bind_param("sssss", $fname, $lname, $email, $password, $role);// Parameters: type(s), values

//excute
$excecute_success = $stmt->execute(); //execute

//preventing SQL INJECTION: using c->prepare(command)
//getting the result -Query call $c->query($command);
// if you use select to see if there are results use $result->num_rows to see number of rows returned


// Redirect if insert is successful query retruns true or false 
if($excecute_success){
    $user_id = $stmt->insert_id;
    
    // Also insert into faculty or students table based on role
    $faculty_student_insert_success = true;
    if ($role === 'faculty') {
        $faculty_stmt = $con->prepare("INSERT INTO faculty (faculty_id) VALUES (?)");
        if ($faculty_stmt) {
            $faculty_stmt->bind_param("i", $user_id);
            if (!$faculty_stmt->execute()) {
                // If insert fails, try to continue anyway (might already exist)
                error_log("Failed to insert into faculty table: " . $faculty_stmt->error);
                // Don't fail the signup if faculty entry already exists
                if (strpos($faculty_stmt->error, 'Duplicate') === false) {
                    $faculty_student_insert_success = false;
                }
            }
            $faculty_stmt->close();
        }
    } else {
        $student_stmt = $con->prepare("INSERT INTO students (student_id) VALUES (?)");
        if ($student_stmt) {
            $student_stmt->bind_param("i", $user_id);
            if (!$student_stmt->execute()) {
                // If insert fails, try to continue anyway (might already exist)
                error_log("Failed to insert into students table: " . $student_stmt->error);
                // Don't fail the signup if student entry already exists
                if (strpos($student_stmt->error, 'Duplicate') === false) {
                    $faculty_student_insert_success = false;
                }
            }
            $student_stmt->close();
        }
    }
    
    // Set session variables to log the user in automatically
    $_SESSION['user_id'] = $user_id;
    $_SESSION['first_name'] = $fname;
    $_SESSION['last_name'] = $lname;
    $_SESSION['email'] = $email;
    $_SESSION['role'] = $role;
    
    // Set faculty_id or student_id in session
    if ($role === 'faculty') {
        $_SESSION['faculty_id'] = $user_id;
    } else {
        $_SESSION['student_id'] = $user_id;
    }
    
    // Return success with role for redirect
    $state=["success"=>true, "role"=>$role, "message"=>"Signup successful"];
    echo json_encode($state);
    exit();
}else{
    // Get the actual error from MySQL
    $error_message = $stmt->error ? $stmt->error : "Insert Failed";
    // Check for duplicate email error
    if (strpos($error_message, 'Duplicate entry') !== false && strpos($error_message, 'email') !== false) {
        $error_message = "Email already exists. Please use a different email.";
    } elseif (strpos($error_message, 'Duplicate entry') !== false) {
        $error_message = "This information already exists in the system.";
    }
    $state=["success"=>false, "message"=>$error_message];
    echo json_encode($state);
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