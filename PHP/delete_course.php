<?php
/**
 * Delete Course Endpoint
 * 
 * Allows faculty to delete their courses.
 * This will also delete related records (sessions, enrollments, attendance).
 * 
 * Process:
 * 1. Verify user is faculty and owns the course
 * 2. Delete the course (cascade will handle related records)
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

if ($input === null || !isset($input['course_id'])) {
    echo json_encode(["success" => false, "message" => "Invalid input. Course ID is required."]);
    exit();
}

$faculty_id = $_SESSION['user_id'];
$course_id = intval($input['course_id']);

// STEP 3: Verify course belongs to this faculty member
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

// STEP 4: Delete the course
// Note: Due to foreign key constraints, we may need to delete related records first
// or set up CASCADE DELETE in the database

// First, delete related attendance records (through sessions)
$delete_attendance = $con->prepare("DELETE a FROM attendance a 
                                    INNER JOIN sessions s ON a.session_id = s.session_id 
                                    WHERE s.course_id = ?");
$delete_attendance->bind_param("i", $course_id);
$delete_attendance->execute();
$delete_attendance->close();

// Delete sessions
$delete_sessions = $con->prepare("DELETE FROM sessions WHERE course_id = ?");
$delete_sessions->bind_param("i", $course_id);
$delete_sessions->execute();
$delete_sessions->close();

// Delete enrollments
$delete_enrollments = $con->prepare("DELETE FROM course_student_list WHERE course_id = ?");
$delete_enrollments->bind_param("i", $course_id);
$delete_enrollments->execute();
$delete_enrollments->close();

// Finally, delete the course
$stmt = $con->prepare("DELETE FROM courses WHERE course_id = ? AND faculty_id = ?");
$stmt->bind_param("ii", $course_id, $faculty_id);

if ($stmt->execute()) {
    echo json_encode(["success" => true, "message" => "Course deleted successfully."]);
} else {
    echo json_encode(["success" => false, "message" => "Failed to delete course: " . $stmt->error]);
}

$stmt->close();
?>

