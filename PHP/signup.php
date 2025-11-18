<?php

require_once 'db.php';

// echo post object for debugging

// INFO: the fix to the issue; Instead of using POST when passing the json, usue the line below

// $input =json_decode(file_get_contents("php://input"),true); /// if using JSON

$input = json_decode(file_get_contents("php://input"), true);
if ($input === null) {
    echo json_encode([
        "success" => false,
        "message" => "JSON decode failed", // Debugging message, i keep getting invalid input and i dont know why, i think it my json validation
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

$INS_COM="INSERT INTO users (first_name, last_name, email, password_hash) VALUES (?, ?, ?, ?)"; // ?-> where the cvalues will go

//preparing the statement
$stmt = $con->prepare($INS_COM);

//checking if prepare was successful
if($stmt===false){
    $state=["success"=>false,"message"=>"Prepare Failed"];
    echo json_encode($state);
    exit();
}

//binding the parameters
$stmt->bind_param("ssss", $fname, $lname, $email, $password);// Parameters: type(s), values

//excute
$excecute_success = $stmt->execute(); //execute

//preventing SQL INJECTION: using c->prepare(command)
//getting the result -Query call $c->query($command);
// if you use select to see if there are results use $result->num_rows to see number of rows returned


// Redirect if insert is successful query retruns true or false 
if($excecute_success){
    //header('Location: ../view/login.html');
    $state=["success"=>true];
    echo json_encode($state);
    exit();
}else{
    $state=["success"=>false, "message"=>"Insert Failed"];
    echo json_encode($state);
    // echo "Failed Retry";
}

// if html is rendered in php to check for a submit 
// $_SERVER['REQUEST_METHOD'] === 'POST'

// When using js and expecting a return value you echo a json instead of redirecting
//$state=["state"=>true];

//echo using json_encode(object to echo);

// ending the program
?>

<!-- require -->