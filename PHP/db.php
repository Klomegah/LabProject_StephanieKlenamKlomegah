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

// Use $con (not $conn) to match all other PHP files
$con = new mysqli(
    $env['host'],
    $env['user'],
    $env['password'],
    $env['database']
);

// Check connection
if ($con->connect_error) {
    die("Connection failed: " . $con->connect_error);
}
?>

