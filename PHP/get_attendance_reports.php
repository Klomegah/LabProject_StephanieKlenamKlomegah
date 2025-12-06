<?php
/**
 * Get Attendance Reports Endpoint (Student)
 * 
 * Generates attendance reports for students showing:
 * - Daily report: All sessions for a specific date with attendance status
 * - Overall report: Attendance statistics (total sessions, attended, percentage)
 * 
 * Can filter by course_id or show all enrolled courses.
 * 
 * Database Tables Used:
 * - course_student_list: To verify enrollment
 * - sessions: Session information
 * - courses: Course information
 * - attendance: Attendance records
 */

session_start();
require_once 'db.php';
require_once 'auth_check.php';

header('Content-Type: application/json');

// ============================================================================
// STEP 1: Verify user is logged in and is a student
// ============================================================================
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    echo json_encode(["success" => false, "message" => "Unauthorized. Student access required."]);
    exit();
}

// ============================================================================
// STEP 2: Get parameters from query string
// ============================================================================
$student_id = $_SESSION['student_id'] ?? $_SESSION['user_id'];
$course_id = isset($_GET['course_id']) ? intval($_GET['course_id']) : null;
$report_type = isset($_GET['type']) ? $_GET['type'] : 'overall'; // 'daily' or 'overall'

if ($report_type === 'daily') {
    // ========================================================================
    // DAILY REPORT: Show all sessions for a specific date
    // ========================================================================
    $date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
    
    if ($course_id) {
        // ====================================================================
        // Daily report for specific course
        // ====================================================================
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
        
        // Get all sessions for this course on this date with attendance status
        $stmt = $con->prepare("SELECT 
                               s.session_id,
                               s.date as session_date,
                               s.start_time,
                               c.course_code,
                               c.course_name,
                               CASE WHEN a.attendance_id IS NOT NULL THEN a.status ELSE 'absent' END as status,
                               a.check_in_time
                               FROM sessions s
                               INNER JOIN courses c ON s.course_id = c.course_id
                               LEFT JOIN attendance a ON s.session_id = a.session_id AND a.student_id = ?
                               WHERE s.course_id = ? AND s.date = ?
                               ORDER BY s.start_time");
        $stmt->bind_param("iis", $student_id, $course_id, $date);
    } else {
        // ====================================================================
        // Daily report for all enrolled courses
        // ====================================================================
        $stmt = $con->prepare("SELECT 
                               s.session_id,
                               s.date as session_date,
                               s.start_time,
                               c.course_code,
                               c.course_name,
                               CASE WHEN a.attendance_id IS NOT NULL THEN a.status ELSE 'absent' END as status,
                               a.check_in_time
                               FROM sessions s
                               INNER JOIN courses c ON s.course_id = c.course_id
                               INNER JOIN course_student_list csl ON c.course_id = csl.course_id
                               LEFT JOIN attendance a ON s.session_id = a.session_id AND a.student_id = ?
                               WHERE csl.student_id = ? AND s.date = ?
                               ORDER BY c.course_code, s.start_time");
        $stmt->bind_param("iis", $student_id, $student_id, $date);
    }
} else {
    // ========================================================================
    // OVERALL REPORT: Show attendance statistics
    // ========================================================================
    if ($course_id) {
        // ====================================================================
        // Overall report for specific course
        // ====================================================================
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
        
        // Calculate attendance statistics: total sessions, attended sessions, percentage
        $stmt = $con->prepare("SELECT 
                               c.course_id,
                               c.course_code,
                               c.course_name,
                               COUNT(DISTINCT s.session_id) as total_sessions,
                               COUNT(DISTINCT a.attendance_id) as attended_sessions,
                               ROUND(COUNT(DISTINCT a.attendance_id) * 100.0 / NULLIF(COUNT(DISTINCT s.session_id), 0), 2) as attendance_rate
                               FROM courses c
                               INNER JOIN sessions s ON c.course_id = s.course_id
                               LEFT JOIN attendance a ON s.session_id = a.session_id AND a.student_id = ?
                               WHERE c.course_id = ?
                               GROUP BY c.course_id");
        $stmt->bind_param("ii", $student_id, $course_id);
    } else {
        // ====================================================================
        // Overall report for all enrolled courses
        // ====================================================================
        $stmt = $con->prepare("SELECT 
                               c.course_id,
                               c.course_code,
                               c.course_name,
                               COUNT(DISTINCT s.session_id) as total_sessions,
                               COUNT(DISTINCT a.attendance_id) as attended_sessions,
                               ROUND(COUNT(DISTINCT a.attendance_id) * 100.0 / NULLIF(COUNT(DISTINCT s.session_id), 0), 2) as attendance_rate
                               FROM courses c
                               INNER JOIN course_student_list csl ON c.course_id = csl.course_id
                               INNER JOIN sessions s ON c.course_id = s.course_id
                               LEFT JOIN attendance a ON s.session_id = a.session_id AND a.student_id = ?
                               WHERE csl.student_id = ?
                               GROUP BY c.course_id
                               ORDER BY c.course_code");
        $stmt->bind_param("ii", $student_id, $student_id);
    }
}

// ============================================================================
// STEP 3: Execute query and return results
// ============================================================================
$stmt->execute();
$result = $stmt->get_result();
$reports = [];

while ($row = $result->fetch_assoc()) {
    $reports[] = $row;
}

echo json_encode(["success" => true, "reports" => $reports, "type" => $report_type]);
$stmt->close();
?>

