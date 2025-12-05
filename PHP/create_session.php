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

if ($input === null) {
    echo json_encode(["success" => false, "message" => "Invalid input."]);
    exit();
}

// Validate required fields
if (!isset($input['course_id'], $input['session_date'], $input['session_time'])) {
    echo json_encode(["success" => false, "message" => "Missing required fields."]);
    exit();
}

$faculty_id = $_SESSION['faculty_id'] ?? $_SESSION['user_id'];
$course_id = intval($input['course_id']);
$date = $input['date'];
$start_time = $input['start_time'];
$end_time = isset($input['end_time']) ? $input['end_time'] : $start_time;
$topic = isset($input['topic']) ? $input['topic'] : '';
$location = isset($input['location']) ? $input['location'] : '';

// Verify that the course belongs to this faculty
$check_stmt = $con->prepare("SELECT course_id FROM courses WHERE course_id = ? AND faculty_id = ?");
$check_stmt->bind_param("ii", $course_id, $faculty_id);
$check_stmt->execute();
$check_result = $check_stmt->get_result();

if ($check_result->num_rows === 0) {
    echo json_encode(["success" => false, "message" => "Course not found or access denied."]);
    $check_stmt->close();
    exit();
}
$check_stmt->close();

// Insert the session (no attendance_code - not in database schema)
$stmt = $con->prepare("INSERT INTO sessions (course_id, date, start_time, end_time, topic, location) VALUES (?, ?, ?, ?, ?, ?)");
$stmt->bind_param("isssss", $course_id, $date, $start_time, $end_time, $topic, $location);

if ($stmt->execute()) {
    $session_id = $stmt->insert_id;
    echo json_encode([
        "success" => true,
        "message" => "Session created successfully.",
        "session_id" => $session_id
    ]);
} else {
    echo json_encode(["success" => false, "message" => "Failed to create session: " . $stmt->error]);
}

$stmt->close();
?>

