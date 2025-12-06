<?php
/**
 * Get Sessions Endpoint
 * 
 * Returns class sessions based on user role:
 * - Faculty: Returns sessions for their courses with attendance count
 * - Students: Returns sessions for enrolled courses with their attendance status
 * 
 * Can filter by course_id or return all sessions.
 * 
 * Database Tables Used:
 * - sessions: Session information
 * - courses: Course information
 * - course_student_list: To verify enrollment (students)
 * - attendance: To count attendance (faculty) or check status (students)
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

// STEP 2: Determine user role and get optional course_id filter
$user_id = $_SESSION['user_id'];
$is_faculty_user = isFaculty($con, $user_id);

if ($is_faculty_user) {
    // FACULTY QUERY: Get sessions for courses they teach
    $faculty_id = $user_id;
    $course_id = isset($_GET['course_id']) ? intval($_GET['course_id']) : null;
    
    if ($course_id) {
        // Get sessions for a specific course with attendance count
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
        
        // Get all sessions for all courses taught by this faculty
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
    // STUDENT QUERY: Get sessions for enrolled courses
    $student_id = $user_id;
    $course_id = isset($_GET['course_id']) ? intval($_GET['course_id']) : null;
    
    if ($course_id) {
        // Get sessions for specific course with student's attendance status
        // Verify student is enrolled in this course
        $check_stmt = $con->prepare("SELECT course_id FROM course_student_list WHERE course_id = ? AND student_id = ?");
        $check_stmt->bind_param("ii", $course_id, $student_id);
        $check_stmt->execute();
        if ($check_stmt->get_result()->num_rows === 0) {
            echo json_encode(["success" => false, "message" => "Not enrolled in this course."]);
            $check_stmt->close();
            exit();
        }
        $check_stmt->close();
        
        // Get sessions with attendance status (marked/not_marked)
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
        // Get all sessions for all enrolled courses
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
// STEP 3: Execute query and return results
$stmt->execute();
$result = $stmt->get_result();
$sessions = [];

while ($row = $result->fetch_assoc()) {
    $sessions[] = $row;
}

echo json_encode(["success" => true, "sessions" => $sessions]);
$stmt->close();
?>

