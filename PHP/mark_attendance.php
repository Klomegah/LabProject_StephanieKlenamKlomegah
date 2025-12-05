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

if ($input === null || !isset($input['session_id']) || !isset($input['student_id'])) {
    echo json_encode(["success" => false, "message" => "Invalid input."]);
    exit();
}

$faculty_id = $_SESSION['user_id'];
$session_id = intval($input['session_id']);
$student_id = intval($input['student_id']);
$status = isset($input['status']) ? $input['status'] : 'present';

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

// Verify student is enrolled in the course
$check_stmt = $con->prepare("SELECT csl.student_id FROM course_student_list csl
                             INNER JOIN sessions s ON csl.course_id = s.course_id
                             WHERE s.session_id = ? AND csl.student_id = ?");
$check_stmt->bind_param("ii", $session_id, $student_id);
$check_stmt->execute();
$check_result = $check_stmt->get_result();

if ($check_result->num_rows === 0) {
    echo json_encode(["success" => false, "message" => "Student not enrolled in this course."]);
    $check_stmt->close();
    exit();
}
$check_stmt->close();

// Check if attendance already exists
$check_stmt = $con->prepare("SELECT attendance_id FROM attendance WHERE session_id = ? AND student_id = ?");
$check_stmt->bind_param("ii", $session_id, $student_id);
$check_stmt->execute();
$check_result = $check_stmt->get_result();

$current_time = date('H:i:s');

if ($check_result->num_rows > 0) {
    // Update existing attendance
    $stmt = $con->prepare("UPDATE attendance SET status = ?, check_in_time = ? WHERE session_id = ? AND student_id = ?");
    $stmt->bind_param("ssii", $status, $current_time, $session_id, $student_id);
} else {
    // Insert new attendance
    $stmt = $con->prepare("INSERT INTO attendance (session_id, student_id, status, check_in_time) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("iiss", $session_id, $student_id, $status, $current_time);
}
$check_stmt->close();

if ($stmt->execute()) {
    echo json_encode(["success" => true, "message" => "Attendance marked successfully."]);
} else {
    echo json_encode(["success" => false, "message" => "Failed to mark attendance: " . $stmt->error]);
}

$stmt->close();
?>

