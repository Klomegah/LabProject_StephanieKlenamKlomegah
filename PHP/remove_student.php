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

if ($input === null || !isset($input['course_id']) || !isset($input['student_id'])) {
    echo json_encode(["success" => false, "message" => "Invalid input. Course ID and Student ID are required."]);
    exit();
}

$faculty_id = $_SESSION['user_id'];
$course_id = intval($input['course_id']);
$student_id = intval($input['student_id']);

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

// Verify student is enrolled in the course
$check_stmt = $con->prepare("SELECT student_id FROM course_student_list WHERE course_id = ? AND student_id = ?");
$check_stmt->bind_param("ii", $course_id, $student_id);
$check_stmt->execute();
$check_result = $check_stmt->get_result();

if ($check_result->num_rows === 0) {
    echo json_encode(["success" => false, "message" => "Student is not enrolled in this course."]);
    $check_stmt->close();
    exit();
}
$check_stmt->close();

// Remove student from course
$stmt = $con->prepare("DELETE FROM course_student_list WHERE course_id = ? AND student_id = ?");
$stmt->bind_param("ii", $course_id, $student_id);

if ($stmt->execute()) {
    echo json_encode(["success" => true, "message" => "Student removed from course successfully."]);
} else {
    echo json_encode(["success" => false, "message" => "Failed to remove student: " . $stmt->error]);
}

$stmt->close();
?>

