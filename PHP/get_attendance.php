<?php
session_start();
require_once 'db.php';
require_once 'auth_check.php';
require_once 'faculty_check.php';

// Set JSON response header
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(["success" => false, "message" => "Unauthorized."]);
    exit();
}

$user_id = $_SESSION['user_id'];
$is_faculty_user = isFaculty($con, $user_id);
$session_id = isset($_GET['session_id']) ? intval($_GET['session_id']) : null;

if ($is_faculty_user) {
    // Faculty/Faculty Intern sees attendance for their sessions
    $faculty_id = $user_id;
    
    if (!$session_id) {
        echo json_encode(["success" => false, "message" => "Session ID required."]);
        exit();
    }
    
    // Verify session belongs to faculty
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
    
    // Get all students enrolled in the course and their attendance status
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
    // Students see their own attendance
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
        // Get all attendance records for student
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

$stmt->execute();
$result = $stmt->get_result();
$attendance = [];

while ($row = $result->fetch_assoc()) {
    $attendance[] = $row;
}

echo json_encode(["success" => true, "attendance" => $attendance]);
$stmt->close();
?>

