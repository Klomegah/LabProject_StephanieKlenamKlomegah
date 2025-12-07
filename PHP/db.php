<?php
// Create connection
$env = parse_ini_file('../env/connect.env');

$con= new mysqli(
    $env['host'],
    $env['user'],
    $env['password'],
    $env['database']
);

   
   // Check connection
   if ($con->connect_error) {
    // Throw exception 
    throw new Exception("Connection failed: " . $con->connect_error);
}
 