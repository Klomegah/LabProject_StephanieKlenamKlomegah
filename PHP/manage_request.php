<?php
/**
 * Remove Student from Course
 * 
 * This endpoint allows faculty to remove a student from one of their courses.
 * Since there's no course_requests table, this directly removes the student
 * from the course_student_list table.
 * 
 * Flow:
 * 1. Verify faculty is logged in and owns the course
 * 2. Verify the course exists and belongs to this faculty
 * 3. Remove student from course_student_list table
 */

session_start();
require_once 'db.php';
require_once 'auth_check.php';
require_once 'faculty_check.php';

header('Content-Type: application/json');

// ============================================================================
// STEP 1: Verify user is logged in and is faculty/faculty intern
// ============================================================================
if (!isset($_SESSION['user_id']) || !isFaculty($con, $_SESSION['user_id'])) {
    echo json_encode(["success" => false, "message" => "Unauthorized. Faculty access required."]);
    exit();
}

// ============================================================================
// STEP 2: Get and validate input data
// ============================================================================
$input = json_decode(file_get_contents("php://input"), true);

if ($input === null || !isset($input['course_id']) || !isset($input['student_id']) || !isset($input['action'])) {
    echo json_encode(["success" => false, "message" => "Invalid input. Course ID, Student ID, and action are required."]);
    exit();
}

$faculty_id = $_SESSION['user_id'];
$course_id = intval($input['course_id']);
$student_id = intval($input['student_id']);
$action = $input['action'];

if ($action !== 'remove') {
    echo json_encode(["success" => false, "message" => "Invalid action. Use 'remove' to remove a student."]);
    exit();
}

// ============================================================================
// STEP 3: Verify course belongs to this faculty member
// ============================================================================
$stmt = $con->prepare("SELECT course_id FROM courses WHERE course_id = ? AND faculty_id = ?");
$stmt->bind_param("ii", $course_id, $faculty_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(["success" => false, "message" => "Course not found or access denied."]);
    $stmt->close();
    exit();
}
$stmt->close();

// ============================================================================
// STEP 4: Remove student from course_student_list table
// ============================================================================
// This deletes the enrollment record, effectively removing the student from the course
$stmt = $con->prepare("DELETE FROM course_student_list WHERE course_id = ? AND student_id = ?");
$stmt->bind_param("ii", $course_id, $student_id);

if ($stmt->execute()) {
    echo json_encode(["success" => true, "message" => "Student removed from course successfully."]);
} else {
    echo json_encode(["success" => false, "message" => "Failed to remove student: " . $stmt->error]);
}

$stmt->close();
?>
