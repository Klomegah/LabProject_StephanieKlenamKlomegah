<?php
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
