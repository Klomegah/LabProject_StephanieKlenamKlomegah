<?php
session_start();

// Clear session data
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
session_destroy();

// Check if this is an AJAX request (from JavaScript fetch)
$isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
$isAjax = $isAjax || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);

if ($isAjax) {
    // Set the response header to JSON for AJAX requests
    header('Content-Type: application/json');
    echo json_encode(["success" => true, "message" => "Logout successful"]);
} else {
    // Redirect to login page for direct browser requests
    header("Location: ../Login and Signup/login.html");
    exit();
}
?>