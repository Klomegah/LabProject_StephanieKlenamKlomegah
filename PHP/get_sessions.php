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

if ($is_faculty_user) {
    // Faculty/Faculty Intern sees sessions for their courses
    $faculty_id = $user_id;
    $course_id = isset($_GET['course_id']) ? intval($_GET['course_id']) : null;
    
    if ($course_id) {
        // Get sessions for a specific course
        $stmt = $con->prepare("SELECT s.session_id, s.course_id, s.date as session_date, s.start_time, s.end_time, 
                               s.topic, s.location,
                               c.course_code, c.course_name,
                               COUNT(DISTINCT a.student_id) as attendance_count
                               FROM sessions s
                               INNER JOIN courses c ON s.course_id = c.course_id
                               LEFT JOIN attendance a ON s.session_id = a.session_id
                               WHERE s.course_id = ? AND c.faculty_id = ?
                               GROUP BY s.session_id
                               ORDER BY s.date DESC, s.start_time DESC");
        $stmt->bind_param("ii", $course_id, $faculty_id);
    } else {
        // Get all sessions for faculty's courses
        $stmt = $con->prepare("SELECT s.session_id, s.course_id, s.date as session_date, s.start_time, s.end_time,
                               s.topic, s.location,
                               c.course_code, c.course_name,
                               COUNT(DISTINCT a.student_id) as attendance_count
                               FROM sessions s
                               INNER JOIN courses c ON s.course_id = c.course_id
                               LEFT JOIN attendance a ON s.session_id = a.session_id
                               WHERE c.faculty_id = ?
                               GROUP BY s.session_id
                               ORDER BY s.date DESC, s.start_time DESC");
        $stmt->bind_param("i", $faculty_id);
    }
} else {
    // Students see sessions for their enrolled courses
    $student_id = $user_id;
    $course_id = isset($_GET['course_id']) ? intval($_GET['course_id']) : null;
    
    if ($course_id) {
        // Verify student is enrolled
        $check_stmt = $con->prepare("SELECT course_id FROM course_student_list WHERE course_id = ? AND student_id = ?");
        $check_stmt->bind_param("ii", $course_id, $student_id);
        $check_stmt->execute();
        if ($check_stmt->get_result()->num_rows === 0) {
            echo json_encode(["success" => false, "message" => "Not enrolled in this course."]);
            $check_stmt->close();
            exit();
        }
        $check_stmt->close();
        
        $stmt = $con->prepare("SELECT s.session_id, s.course_id, s.date as session_date, s.start_time, s.end_time,
                               s.topic, s.location,
                               c.course_code, c.course_name,
                               CASE WHEN a.attendance_id IS NOT NULL THEN 'marked' ELSE 'not_marked' END as attendance_status
                               FROM sessions s
                               INNER JOIN courses c ON s.course_id = c.course_id
                               LEFT JOIN attendance a ON s.session_id = a.session_id AND a.student_id = ?
                               WHERE s.course_id = ?
                               ORDER BY s.date DESC, s.start_time DESC");
        $stmt->bind_param("ii", $student_id, $course_id);
    } else {
        $stmt = $con->prepare("SELECT s.session_id, s.course_id, s.date as session_date, s.start_time, s.end_time,
                               s.topic, s.location,
                               c.course_code, c.course_name,
                               CASE WHEN a.attendance_id IS NOT NULL THEN 'marked' ELSE 'not_marked' END as attendance_status
                               FROM sessions s
                               INNER JOIN courses c ON s.course_id = c.course_id
                               INNER JOIN course_student_list csl ON c.course_id = csl.course_id
                               LEFT JOIN attendance a ON s.session_id = a.session_id AND a.student_id = ?
                               WHERE csl.student_id = ?
                               ORDER BY s.date DESC, s.start_time DESC");
        $stmt->bind_param("ii", $student_id, $student_id);
    }
}

$stmt->execute();
$result = $stmt->get_result();
$sessions = [];

while ($row = $result->fetch_assoc()) {
    $sessions[] = $row;
}

echo json_encode(["success" => true, "sessions" => $sessions]);
$stmt->close();
?>

