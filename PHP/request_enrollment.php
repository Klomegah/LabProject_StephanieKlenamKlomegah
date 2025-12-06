<?php
/**
 * Student Enrollment Request Handler
 * 
 * This endpoint handles student requests to join courses.
 * Since the database schema doesn't include a course_requests table,
 * students are immediately enrolled when they request to join a course.
 * 
 * Flow:
 * 1. Verify student is logged in and exists in students table
 * 2. Validate course exists
 * 3. Check if student is already enrolled
 * 4. If not enrolled, add student to course_student_list (immediate enrollment)
 */

session_start();
require_once 'db.php';
require_once 'auth_check.php';

header('Content-Type: application/json');

// ============================================================================
// STEP 1: Verify user is logged in
// ============================================================================
if (!isset($_SESSION['user_id'])) {
    echo json_encode(["success" => false, "message" => "Unauthorized."]);
    exit();
}

// ============================================================================
// STEP 2: Verify user is a student (must exist in students table)
// ============================================================================
$user_id = $_SESSION['user_id'];
$check_stmt = $con->prepare("SELECT student_id FROM students WHERE student_id = ?");
$check_stmt->bind_param("i", $user_id);
$check_stmt->execute();
$check_result = $check_stmt->get_result();

if ($check_result->num_rows === 0) {
    echo json_encode(["success" => false, "message" => "Unauthorized. Student access required."]);
    $check_stmt->close();
    exit();
}
$check_stmt->close();

// ============================================================================
// STEP 3: Get and validate input data
// ============================================================================
$input = json_decode(file_get_contents("php://input"), true);

if (!isset($input['course_id'])) {
    echo json_encode(["success" => false, "message" => "Invalid input. Course ID is required."]);
    exit();
}

$course_id = intval($input['course_id']);
$student_id = $user_id;

// ============================================================================
// STEP 4: Verify the course exists in the database
// ============================================================================
$stmt = $con->prepare("SELECT course_id FROM courses WHERE course_id = ?");
$stmt->bind_param("i", $course_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(["success" => false, "message" => "Course not found."]);
    $stmt->close();
    exit();
}
$stmt->close();

// ============================================================================
// STEP 5: Check if student is already enrolled in this course
// ============================================================================
$stmt = $con->prepare("SELECT student_id FROM course_student_list WHERE course_id = ? AND student_id = ?");
$stmt->bind_param("ii", $course_id, $student_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    echo json_encode(["success" => false, "message" => "You are already enrolled in this course."]);
    $stmt->close();
    exit();
}
$stmt->close();

// ============================================================================
// STEP 6: Enroll student in course (immediate enrollment - no approval needed)
// ============================================================================
// Note: Since there's no course_requests table, students are auto-enrolled
// when they request to join. The enrollment is added directly to course_student_list.
$stmt = $con->prepare("INSERT INTO course_student_list (course_id, student_id) VALUES (?, ?)");

if (!$stmt) {
    echo json_encode(["success" => false, "message" => "Prepare failed: " . $con->error]);
    exit();
}

$stmt->bind_param("ii", $course_id, $student_id);

if ($stmt->execute()) {
    echo json_encode(["success" => true, "message" => "Successfully enrolled in course!"]);
} else {
    echo json_encode(["success" => false, "message" => "Failed to enroll: " . $stmt->error]);
}

$stmt->close();
?>
