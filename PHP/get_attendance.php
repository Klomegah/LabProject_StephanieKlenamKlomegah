<?php
/**
 * Get Attendance Endpoint
 * 
 * Returns attendance records based on user role:
 * - Faculty: Returns all students' attendance for a specific session
 * - Students: Returns their own attendance records (all or for specific session)
 * 
 * Database Tables Used:
 * - sessions: Session information
 * - courses: Course information
 * - course_student_list: To get enrolled students
 * - attendance: Attendance records
 * - users: Student names
 */

session_start();
require_once 'db.php';
require_once 'auth_check.php';
require_once 'faculty_check.php';

header('Content-Type: application/json');

// STEP 1: Verify user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(["success" => false, "message" => "Unauthorized."]);
    exit();
}

// STEP 2: Determine user role and get session_id from query parameter
$user_id = $_SESSION['user_id'];
$is_faculty_user = isFaculty($con, $user_id);
$session_id = isset($_GET['session_id']) ? intval($_GET['session_id']) : null;

if ($is_faculty_user) {
    // FACULTY QUERY: Get attendance for all students in a session
    $faculty_id = $user_id;
    
    if (!$session_id) {
        echo json_encode(["success" => false, "message" => "Session ID required."]);
        exit();
    }
    
    // STEP 3A: Verify session belongs to this faculty's course
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
    
    // STEP 4A: Get all enrolled students with their attendance status
    // Returns: student info + attendance status (present/absent/late) or 'absent' if not marked
    $stmt = $con->prepare("SELECT 
                           csl.student_id,
                           u.first_name,
                           u.last_name,
                           u.email,
                           a.attendance_id,
                           a.check_in_time,
                           a.status as attendance_status,
                           CASE WHEN a.attendance_id IS NOT NULL THEN a.status ELSE 'absent' END as status
                           FROM course_student_list csl
                           INNER JOIN students st ON csl.student_id = st.student_id
                           INNER JOIN users u ON st.student_id = u.user_id
                           INNER JOIN sessions s ON csl.course_id = s.course_id
                           LEFT JOIN attendance a ON a.session_id = s.session_id AND a.student_id = csl.student_id
                           WHERE s.session_id = ?
                           ORDER BY u.last_name, u.first_name");
    $stmt->bind_param("i", $session_id);
    
} else {
    // STUDENT QUERY: Get their own attendance records
    $student_id = $user_id;
    
    if ($session_id) {
        // Get attendance for specific session
        $stmt = $con->prepare("SELECT a.attendance_id, a.session_id, a.check_in_time, a.status,
                               s.date as session_date, s.start_time,
                               c.course_code, c.course_name
                               FROM attendance a
                               INNER JOIN sessions s ON a.session_id = s.session_id
                               INNER JOIN courses c ON s.course_id = c.course_id
                               WHERE a.student_id = ? AND a.session_id = ?");
        $stmt->bind_param("ii", $student_id, $session_id);
    } else {
        // Get all attendance records for this student
        $stmt = $con->prepare("SELECT a.attendance_id, a.session_id, a.check_in_time, a.status,
                               s.date as session_date, s.start_time,
                               c.course_code, c.course_name
                               FROM attendance a
                               INNER JOIN sessions s ON a.session_id = s.session_id
                               INNER JOIN courses c ON s.course_id = c.course_id
                               WHERE a.student_id = ?
                               ORDER BY s.date DESC, s.start_time DESC");
        $stmt->bind_param("i", $student_id);
    }
}

// STEP 5: Execute query and return results
$stmt->execute();
$result = $stmt->get_result();
$attendance = [];

while ($row = $result->fetch_assoc()) {
    $attendance[] = $row;
}

echo json_encode(["success" => true, "attendance" => $attendance]);
$stmt->close();
?>

