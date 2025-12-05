<?php
session_start();
require_once 'db.php';
require_once 'auth_check.php';
require_once 'faculty_check.php';

// Set JSON response header
header('Content-Type: application/json');

// Check if user is logged in and is faculty (or faculty intern)
if (!isset($_SESSION['user_id']) || !isFaculty($con, $_SESSION['user_id'])) {
    echo json_encode(["success" => false, "message" => "Unauthorized. Faculty access required."]);
    exit();
}

$faculty_id = $_SESSION['user_id'];

// Get requests for courses owned by this faculty
$stmt = $con->prepare("SELECT cr.request_id, cr.course_id, cr.student_id, cr.status, cr.requested_at, cr.reviewed_at,
                       c.course_code, c.course_name,
                       u.first_name, u.last_name, u.email
                       FROM course_requests cr
                       INNER JOIN courses c ON cr.course_id = c.course_id
                       INNER JOIN students s ON cr.student_id = s.student_id
                       INNER JOIN users u ON s.student_id = u.user_id
                       WHERE c.faculty_id = ?
                       ORDER BY cr.requested_at DESC");
$stmt->bind_param("i", $faculty_id);
$stmt->execute();
$result = $stmt->get_result();
$requests = [];

while ($row = $result->fetch_assoc()) {
    $requests[] = $row;
}

echo json_encode(["success" => true, "requests" => $requests]);
$stmt->close();
?>


