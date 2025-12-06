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
$env_path = __DIR__ . '/../env/connect.env';

if (!file_exists($env_path)) {
    error_log("Database config file not found at: " . $env_path);
    die(json_encode(["success" => false, "message" => "Database configuration file not found"]));
}

$env = parse_ini_file($env_path);

if ($env === false) {
    error_log("Failed to parse environment file: " . $env_path);
    die(json_encode(["success" => false, "message" => "Failed to parse database configuration"]));
}

// Enable error reporting for mysqli (must be before connection)
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// Use the values from the environment file to connect
try {
    $con = new mysqli(
        $env['host'],
        $env['user'],
        $env['password'],
        $env['database']
    );
    
    // Ensure auto-commit is enabled
    $con->query("SET AUTOCOMMIT = 1");
    
    // Check connection
    if ($con->connect_error) {
        error_log("Database Connection failed: " . $con->connect_error . " (Database: " . ($env['database'] ?? 'N/A') . ")");
        die(json_encode(["success" => false, "message" => "Database connection failed: " . $con->connect_error]));
    }
} catch (Exception $e) {
    error_log("Database connection exception: " . $e->getMessage());
    die(json_encode(["success" => false, "message" => "Database connection error: " . $e->getMessage()]));
}
?>
