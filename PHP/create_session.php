<?php
/**
 * Create Session Endpoint
 * 
 * Allows faculty to create class sessions (individual class meetings).
 * A session represents one class meeting with date, time, topic, and location.
 * 
 * Process:
 * 1. Verify user is faculty and owns the course
 * 2. Validate required fields (course_id, date, start_time)
 * 3. Verify course belongs to this faculty
 * 4. Insert new session into sessions table
 * 
 * Database Tables Used:
 * - courses: To verify course ownership
 * - sessions: To store the new session
 * 
 * Note: The session_id returned is used as the "attendance code" for students
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

if ($input === null) {
    echo json_encode(["success" => false, "message" => "Invalid input."]);
    exit();
}

// Required fields: course_id, date, start_time
// Optional fields: end_time (defaults to start_time), topic, location
if (!isset($input['course_id'], $input['date'], $input['start_time'])) {
    echo json_encode(["success" => false, "message" => "Missing required fields: course_id, date, and start_time are required."]);
    exit();
}

$faculty_id = $_SESSION['user_id'];
$course_id = intval($input['course_id']);
$date = $input['date'];
$start_time = $input['start_time'];
$end_time = isset($input['end_time']) ? $input['end_time'] : $start_time;
$topic = isset($input['topic']) ? $input['topic'] : '';
$location = isset($input['location']) ? $input['location'] : '';

// STEP 3: Verify the course belongs to this faculty member
// This ensures faculty can only create sessions for their own courses
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

// STEP 4: Insert new session into sessions table
// Columns: course_id, date, start_time, end_time, topic, location
// The session_id (auto-generated) will be used as the attendance code
$stmt = $con->prepare("INSERT INTO sessions (course_id, date, start_time, end_time, topic, location) VALUES (?, ?, ?, ?, ?, ?)");
$stmt->bind_param("isssss", $course_id, $date, $start_time, $end_time, $topic, $location);

if ($stmt->execute()) {
    $session_id = $stmt->insert_id;
    echo json_encode([
        "success" => true,
        "message" => "Session created successfully. Share Session ID: " . $session_id . " with students.",
        "session_id" => $session_id
    ]);
} else {
    echo json_encode(["success" => false, "message" => "Failed to create session: " . $stmt->error]);
}

$stmt->close();
?>

