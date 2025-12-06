<?php
/**
 * Mark Attendance Endpoint (Faculty)
 * 
 * Allows faculty to mark student attendance for a session.
 * Can mark students as present, absent, or late.
 * 
 * Process:
 * 1. Verify faculty owns the session's course
 * 2. Verify student is enrolled in the course
 * 3. Insert or update attendance record
 * 
 * Database Tables Used:
 * - sessions: To verify session exists
 * - courses: To verify course ownership
 * - course_student_list: To verify student enrollment
 * - attendance: To store attendance record
 */

session_start();
require_once 'db.php';
require_once 'auth_check.php';
require_once 'faculty_check.php';

header('Content-Type: application/json');

// ============================================================================
// STEP 1: Verify user is logged in and has faculty access
// ============================================================================
if (!isset($_SESSION['user_id']) || !isFaculty($con, $_SESSION['user_id'])) {
    echo json_encode(["success" => false, "message" => "Unauthorized. Faculty access required."]);
    exit();
}

// ============================================================================
// STEP 2: Parse and validate input data
// ============================================================================
$input = json_decode(file_get_contents("php://input"), true);

if ($input === null || !isset($input['session_id']) || !isset($input['student_id'])) {
    echo json_encode(["success" => false, "message" => "Invalid input. Session ID and Student ID are required."]);
    exit();
}

$faculty_id = $_SESSION['user_id'];
$session_id = intval($input['session_id']);
$student_id = intval($input['student_id']);
$status = isset($input['status']) ? $input['status'] : 'present'; // present, absent, or late

// ============================================================================
// STEP 3: Verify session belongs to a course owned by this faculty
// ============================================================================
// This ensures faculty can only mark attendance for their own sessions
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

// ============================================================================
// STEP 4: Verify student is enrolled in the course
// ============================================================================
// Only enrolled students can have attendance marked
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

// ============================================================================
// STEP 5: Check if attendance record already exists
// ============================================================================
// If exists, update it. If not, create new record.
$check_stmt = $con->prepare("SELECT attendance_id FROM attendance WHERE session_id = ? AND student_id = ?");
$check_stmt->bind_param("ii", $session_id, $student_id);
$check_stmt->execute();
$check_result = $check_stmt->get_result();

$current_time = date('H:i:s');

if ($check_result->num_rows > 0) {
    // ========================================================================
    // UPDATE: Attendance already exists, update the status and time
    // ========================================================================
    $stmt = $con->prepare("UPDATE attendance SET status = ?, check_in_time = ? WHERE session_id = ? AND student_id = ?");
    $stmt->bind_param("ssii", $status, $current_time, $session_id, $student_id);
} else {
    // ========================================================================
    // INSERT: Create new attendance record
    // ========================================================================
    $stmt = $con->prepare("INSERT INTO attendance (session_id, student_id, status, check_in_time) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("iiss", $session_id, $student_id, $status, $current_time);
}
$check_stmt->close();

// ============================================================================
// STEP 6: Execute the insert or update and return result
// ============================================================================
if ($stmt->execute()) {
    echo json_encode(["success" => true, "message" => "Attendance marked successfully."]);
} else {
    echo json_encode(["success" => false, "message" => "Failed to mark attendance: " . $stmt->error]);
}

$stmt->close();
?>

