<?php
session_start();
require_once 'db.php';
require_once 'auth_check.php';

// Set JSON response header
header('Content-Type: application/json');

// Check if user is logged in and is student
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    echo json_encode(["success" => false, "message" => "Unauthorized. Student access required."]);
    exit();
}

$student_id = $_SESSION['student_id'] ?? $_SESSION['user_id'];

// Get enrolled courses using course_student_list
$stmt = $con->prepare("SELECT c.course_id, c.course_code, c.course_name, c.description,
                       CONCAT(u.first_name, ' ', u.last_name) as faculty_name
                       FROM course_student_list csl
                       INNER JOIN courses c ON csl.course_id = c.course_id
                       INNER JOIN faculty f ON c.faculty_id = f.faculty_id
                       INNER JOIN users u ON f.faculty_id = u.user_id
                       WHERE csl.student_id = ?
                       ORDER BY c.course_id DESC");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$result = $stmt->get_result();
$courses = [];

while ($row = $result->fetch_assoc()) {
    $courses[] = $row;
}

echo json_encode(["success" => true, "courses" => $courses]);
$stmt->close();
?>


