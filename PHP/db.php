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

$env = parse_ini_file('./../env/connect.env');

$conn= new mysqli(
    $env['host'],
    $env['user'],
    $env['password'],
    $env['database']
);

   
   // Check connection
   if ($conn->connect_error) {
    // Throw exception 
    throw new Exception("Connection failed: " . $conn->connect_error);
}

