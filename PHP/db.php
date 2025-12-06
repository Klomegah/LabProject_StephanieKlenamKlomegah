<?php
/*
// connecting to the database
$host="localhost";
$user="root";
$pass="";
$db="attendancemanagement";

$con=new mysqli($host,$user,$pass,$db);
//needed valraiables (hostname,host_user,hostpassword,databasename)

if($con->connect_error){
    // error logic
    // die("Connection falied");
    $errorjson=["state"=>false];
    echo json_encode($errorjson);

    die();
}else{
    // success logic
    //echo "Connected successfully";
    
}
?>
*/


// Alternative approach using environment file for better security and flexibility
$env = parse_ini_file('../env/connect.env');

if ($env === false) {
    http_response_code(500);
    header('Content-Type: application/json');
    die(json_encode(["success" => false, "message" => "Failed to load database configuration"]));
}

// Use the values from the environment file to connect
$con = new mysqli(
    $env['host'],
    $env['user'],
    $env['password'],
    $env['database']
);

// Check connection
if ($con->connect_error) {
    http_response_code(500);
    header('Content-Type: application/json');
    die(json_encode(["success" => false, "message" => "Database connection failed: " . $con->connect_error]));
}
?>
