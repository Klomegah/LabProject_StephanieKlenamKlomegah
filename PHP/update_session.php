<?php
/**
 * Update Session Endpoint
 * 
 * Allows faculty to update session details (date, time, topic, location).
 * Only faculty who own the course can update its sessions.
 * 
 * Process:
 * 1. Verify faculty owns the session's course
 * 2. Build dynamic UPDATE query based on provided fields
 * 3. Update session in database
 * 
 * Database Tables Used:
 * - sessions: To update session information
 * - courses: To verify course ownership
 */

session_start();
require_once 'db.php';
require_once 'auth_check.php';
require_once 'faculty_check.php';

header('Content-Type: application/json');

// STEP 1: Verify user is logged in and has faculty access
if (!isset($_SESSION['user_id']) || !isFaculty($con, $_SESSION['user_id'])) {
    echo json_encode(["success" => false, "message" => "Unauthorized. Faculty access required."]);
    exit();
}

// STEP 2: Parse and validate input data
$input = json_decode(file_get_contents("php://input"), true);

if ($input === null || !isset($input['session_id'])) {
    echo json_encode(["success" => false, "message" => "Invalid input. Session ID is required."]);
    exit();
}

$faculty_id = $_SESSION['user_id'];
$session_id = intval($input['session_id']);

// STEP 3: Verify session belongs to a course owned by this faculty
// This ensures faculty can only update sessions for their own courses
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

// STEP 4: Build dynamic UPDATE query based on provided fields
// Only update fields that are provided in the request
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

// STEP 5: Execute UPDATE query
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

