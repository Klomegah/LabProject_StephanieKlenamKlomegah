<?php
session_start();
require_once 'db.php';
require_once 'auth_check.php';

// Set JSON response header
header('Content-Type: application/json');

// Check if user is logged in and is faculty
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'faculty') {
    echo json_encode(["success" => false, "message" => "Unauthorized. Faculty access required."]);
    exit();
}

// Get JSON input
$input = json_decode(file_get_contents("php://input"), true);

if (!isset($input['request_id'], $input['action'])) {
    echo json_encode(["success" => false, "message" => "Invalid input. Request ID and action are required."]);
    exit();
}

$request_id = intval($input['request_id']);
$action = $input['action']; // 'approve' or 'reject'
$faculty_id = $_SESSION['faculty_id'] ?? $_SESSION['user_id'];

if (!in_array($action, ['approve', 'reject'])) {
    echo json_encode(["success" => false, "message" => "Invalid action. Must be 'approve' or 'reject'."]);
    exit();
}

// Verify that the request belongs to a course owned by this faculty
$stmt = $con->prepare("SELECT cr.course_id, cr.student_id, cr.status
                       FROM course_requests cr
                       INNER JOIN courses c ON cr.course_id = c.course_id
                       WHERE cr.request_id = ? AND c.faculty_id = ?");
$stmt->bind_param("ii", $request_id, $faculty_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(["success" => false, "message" => "Request not found or you don't have permission to manage it."]);
    exit();
}

$request = $result->fetch_assoc();

if ($request['status'] !== 'pending') {
    echo json_encode(["success" => false, "message" => "This request has already been processed."]);
    exit();
}

$course_id = $request['course_id'];
$student_id = $request['student_id'];
$new_status = $action === 'approve' ? 'approved' : 'rejected';

// Start transaction
$con->begin_transaction();

try {
    // Update request status
    $stmt = $con->prepare("UPDATE course_requests SET status = ?, reviewed_at = CURRENT_TIMESTAMP WHERE request_id = ?");
    $stmt->bind_param("si", $new_status, $request_id);
    $stmt->execute();

    if ($action === 'approve') {
        // Add to course_student_list if approved
        $stmt = $con->prepare("INSERT INTO course_student_list (course_id, student_id) VALUES (?, ?)
                               ON DUPLICATE KEY UPDATE course_id = course_id");
        $stmt->bind_param("ii", $course_id, $student_id);
        $stmt->execute();
    }

    $con->commit();
    echo json_encode(["success" => true, "message" => "Request " . $action . "d successfully."]);
} catch (Exception $e) {
    $con->rollback();
    echo json_encode(["success" => false, "message" => "Failed to process request."]);
}

$stmt->close();
?>


