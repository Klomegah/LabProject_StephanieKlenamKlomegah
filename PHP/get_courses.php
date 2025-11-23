<?php
session_start();
require_once 'db.php';
require_once 'auth_check.php';

// Set JSON response header
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(["success" => false, "message" => "Unauthorized."]);
    exit();
}

$user_role = $_SESSION['role'] ?? 'student';

if ($user_role === 'faculty') {
    // Faculty sees only their courses
    $faculty_id = $_SESSION['faculty_id'] ?? $_SESSION['user_id'];
    $stmt = $con->prepare("SELECT c.course_id, c.course_code, c.course_name, c.description, 
                           COUNT(DISTINCT csl.student_id) as enrolled_students,
                           COUNT(DISTINCT CASE WHEN cr.status = 'pending' THEN cr.request_id END) as pending_requests
                           FROM courses c
                           LEFT JOIN course_student_list csl ON c.course_id = csl.course_id
                           LEFT JOIN course_requests cr ON c.course_id = cr.course_id
                           WHERE c.faculty_id = ?
                           GROUP BY c.course_id
                           ORDER BY c.course_id DESC");
    $stmt->bind_param("i", $faculty_id);
} else {
    // Students see all available courses with their enrollment status
    $student_id = $_SESSION['student_id'] ?? $_SESSION['user_id'];
    $stmt = $con->prepare("SELECT c.course_id, c.course_code, c.course_name, c.description,
                           CONCAT(u.first_name, ' ', u.last_name) as faculty_name,
                           CASE WHEN csl.student_id IS NOT NULL THEN 'enrolled'
                                WHEN cr.status = 'pending' THEN 'pending'
                                WHEN cr.status = 'approved' THEN 'enrolled'
                                WHEN cr.status = 'rejected' THEN 'rejected'
                                ELSE 'available' END as enrollment_status
                           FROM courses c
                           INNER JOIN faculty f ON c.faculty_id = f.faculty_id
                           INNER JOIN users u ON f.faculty_id = u.user_id
                           LEFT JOIN course_student_list csl ON c.course_id = csl.course_id AND csl.student_id = ?
                           LEFT JOIN course_requests cr ON c.course_id = cr.course_id AND cr.student_id = ?
                           ORDER BY c.course_id DESC");
    $stmt->bind_param("ii", $student_id, $student_id);
}

$stmt->execute();
$result = $stmt->get_result();
$courses = [];

while ($row = $result->fetch_assoc()) {
    $courses[] = $row;
}

echo json_encode(["success" => true, "courses" => $courses]);
$stmt->close();
?>


