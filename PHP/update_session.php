<?php
session_start();
require_once 'db.php';
require_once 'auth_check.php';
require_once 'faculty_check.php';

// Set JSON response header
header('Content-Type: application/json');

// Check if user is logged in and is faculty (or faculty intern)
if (!isset($_SESSION['user_id']) || !isFaculty($con, $_SESSION['user_id'])) {
    echo json_encode(["success" => false, "message" => "Unauthorized. Faculty access required."]);
    exit();
}

$input = json_decode(file_get_contents("php://input"), true);

if ($input === null || !isset($input['session_id'])) {
    echo json_encode(["success" => false, "message" => "Invalid input."]);
    exit();
}

$faculty_id = $_SESSION['faculty_id'] ?? $_SESSION['user_id'];
$session_id = intval($input['session_id']);

// Verify that the session belongs to a course owned by this faculty
$check_stmt = $con->prepare("SELECT s.session_id FROM sessions s
                             INNER JOIN courses c ON s.course_id = c.course_id
                             WHERE s.session_id = ? AND c.faculty_id = ?");
$check_stmt->bind_param("ii", $session_id, $faculty_id);
$check_stmt->execute();
$check_result = $check_stmt->get_result();

if ($check_result->num_rows === 0) {
    echo json_encode(["success" => false, "message" => "Session not found or access denied."]);
    $check_stmt->close();
    exit();
}
$check_stmt->close();

// Build update query dynamically based on provided fields
$update_fields = [];
$params = [];
$types = "";

if (isset($input['date'])) {
    $update_fields[] = "date = ?";
    $params[] = $input['date'];
    $types .= "s";
}

if (isset($input['start_time'])) {
    $update_fields[] = "start_time = ?";
    $params[] = $input['start_time'];
    $types .= "s";
}

if (isset($input['end_time'])) {
    $update_fields[] = "end_time = ?";
    $params[] = $input['end_time'];
    $types .= "s";
}

if (isset($input['topic'])) {
    $update_fields[] = "topic = ?";
    $params[] = $input['topic'];
    $types .= "s";
}

if (isset($input['location'])) {
    $update_fields[] = "location = ?";
    $params[] = $input['location'];
    $types .= "s";
}

if (empty($update_fields)) {
    echo json_encode(["success" => false, "message" => "No fields to update."]);
    exit();
}

$params[] = $session_id;
$types .= "i";

$sql = "UPDATE sessions SET " . implode(", ", $update_fields) . " WHERE session_id = ?";
$stmt = $con->prepare($sql);
$stmt->bind_param($types, ...$params);

if ($stmt->execute()) {
    echo json_encode(["success" => true, "message" => "Session updated successfully."]);
} else {
    echo json_encode(["success" => false, "message" => "Failed to update session: " . $stmt->error]);
}

$stmt->close();
?>

