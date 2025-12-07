<?php
// Create connection
$env = parse_ini_file('../env/connect.env');

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
 