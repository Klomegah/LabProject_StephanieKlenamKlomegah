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

// Get JSON input
$input = json_decode(file_get_contents("php://input"), true);

if (!isset($input['course_id'])) {
    echo json_encode(["success" => false, "message" => "Invalid input. Course ID is required."]);
    exit();
}

$course_id = intval($input['course_id']);
$student_id = $_SESSION['student_id'] ?? $_SESSION['user_id'];

// Check if course exists
$stmt = $con->prepare("SELECT course_id FROM courses WHERE course_id = ?");
$stmt->bind_param("i", $course_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(["success" => false, "message" => "Course not found."]);
    exit();
}

// Check if already enrolled (using course_student_list)
$stmt = $con->prepare("SELECT student_id FROM course_student_list WHERE course_id = ? AND student_id = ?");
$stmt->bind_param("ii", $course_id, $student_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    echo json_encode(["success" => false, "message" => "You are already enrolled in this course."]);
    exit();
}

// Check if request already exists
$stmt = $con->prepare("SELECT request_id, status FROM course_requests WHERE course_id = ? AND student_id = ?");
$stmt->bind_param("ii", $course_id, $student_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $request = $result->fetch_assoc();
    if ($request['status'] === 'pending') {
        echo json_encode(["success" => false, "message" => "You already have a pending request for this course."]);
        exit();
    } elseif ($request['status'] === 'approved') {
        echo json_encode(["success" => false, "message" => "Your request was already approved. You should be enrolled."]);
        exit();
    }
    // If rejected, allow new request by updating the existing one
    $stmt = $con->prepare("UPDATE course_requests SET status = 'pending', requested_at = CURRENT_TIMESTAMP, reviewed_at = NULL WHERE course_id = ? AND student_id = ?");
    $stmt->bind_param("ii", $course_id, $student_id);
    if ($stmt->execute()) {
        echo json_encode(["success" => true, "message" => "Enrollment request submitted successfully."]);
    } else {
        echo json_encode(["success" => false, "message" => "Failed to submit request."]);
    }
    exit();
}

// Create new request
$stmt = $con->prepare("INSERT INTO course_requests (course_id, student_id, status) VALUES (?, ?, 'pending')");
$stmt->bind_param("ii", $course_id, $student_id);

if ($stmt->execute()) {
    echo json_encode(["success" => true, "message" => "Enrollment request submitted successfully."]);
} else {
    echo json_encode(["success" => false, "message" => "Failed to submit request."]);
}

$stmt->close();
?>


