<?php
require  'db.php';

session_start();
if(ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

//Clear all session variables
session_unset();

// Destroy the session
$success = session_destroy();

// Set the response header to JSON
header('Content-Type: application/json');

if ($success) {
    echo json_encode(["success" => true, "message" => "Logout successful"]);
} else {
    echo json_encode(["success" => false, "message" => "Logout failed"]);}


?>