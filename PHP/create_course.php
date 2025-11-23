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

if (!isset($input['course_code'], $input['course_name'])) {
    echo json_encode(["success" => false, "message" => "Invalid input. Course code and name are required."]);
    exit();
}

$course_code = trim($input['course_code']);
$course_name = trim($input['course_name']);
$course_description = isset($input['course_description']) ? trim($input['course_description']) : '';

// Get faculty_id - check if it exists in faculty table
$faculty_id = $_SESSION['faculty_id'] ?? $_SESSION['user_id'];

// Verify that the faculty_id exists in the faculty table, if not, try to create it
$check_stmt = $con->prepare("SELECT faculty_id FROM faculty WHERE faculty_id = ?");
$check_stmt->bind_param("i", $faculty_id);
$check_stmt->execute();
$check_result = $check_stmt->get_result();

if ($check_result->num_rows === 0) {
    // Try to insert the faculty_id into the faculty table
    $insert_stmt = $con->prepare("INSERT INTO faculty (faculty_id) VALUES (?)");
    if ($insert_stmt) {
        $insert_stmt->bind_param("i", $faculty_id);
        if (!$insert_stmt->execute()) {
            $check_stmt->close();
            echo json_encode(["success" => false, "message" => "Faculty ID not found and could not be created. Please contact administrator."]);
            exit();
        }
        $insert_stmt->close();
    } else {
        $check_stmt->close();
        echo json_encode(["success" => false, "message" => "Faculty ID not found. Please ensure you are registered as faculty."]);
        exit();
    }
}
$check_stmt->close();

// Validate input
if (empty($course_code) || empty($course_name)) {
    echo json_encode(["success" => false, "message" => "Course code and name cannot be empty."]);
    exit();
}

// Check if course code already exists
$stmt = $con->prepare("SELECT course_id FROM courses WHERE course_code = ?");
$stmt->bind_param("s", $course_code);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    echo json_encode(["success" => false, "message" => "Course code already exists."]);
    exit();
}

// Insert new course - using 'description' column name to match existing schema
$stmt = $con->prepare("INSERT INTO courses (course_code, course_name, description, faculty_id) VALUES (?, ?, ?, ?)");

if ($stmt === false) {
    echo json_encode(["success" => false, "message" => "Prepare failed: " . $con->error]);
    exit();
}

$stmt->bind_param("sssi", $course_code, $course_name, $course_description, $faculty_id);

if ($stmt->execute()) {
    echo json_encode(["success" => true, "message" => "Course created successfully.", "course_id" => $stmt->insert_id]);
} else {
    $error_msg = $stmt->error ? $stmt->error : "Unknown error";
    echo json_encode(["success" => false, "message" => "Failed to create course: " . $error_msg]);
}

$stmt->close();
?>


