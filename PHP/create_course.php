<?php
/**
 * Create Course Endpoint
 * 
 * Allows faculty members to create new courses in the system.
 * 
 * Process:
 * 1. Verify user is faculty/faculty intern
 * 2. Ensure user exists in faculty table (auto-add if needed)
 * 3. Validate course code and name
 * 4. Check if course code already exists
 * 5. Insert new course into courses table
 * 
 * Database Tables Used:
 * - faculty: To verify/ensure faculty status
 * - courses: To store the new course
 */

session_start();
require_once 'db.php';
require_once 'auth_check.php';
require_once 'faculty_check.php';

header('Content-Type: application/json');

// STEP 1: Verify user is logged in and has faculty access
if (!isset($_SESSION['user_id']) || !isFaculty($con, $_SESSION['user_id'])) {
    echo json_encode(["success" => false, "message" => "Unauthorized. Faculty access required."]);
    exit();
}

// STEP 2: Parse and validate input data from request
$input = json_decode(file_get_contents("php://input"), true);

if (!isset($input['course_code'], $input['course_name'])) {
    echo json_encode(["success" => false, "message" => "Invalid input. Course code and name are required."]);
    exit();
}

$course_code = trim($input['course_code']);
$course_name = trim($input['course_name']);
$course_description = isset($input['course_description']) ? trim($input['course_description']) : '';

// STEP 3: Get faculty ID and ensure it exists in faculty table
// If user is not in faculty table yet, add them automatically
// This allows faculty interns to create courses
$faculty_id = $_SESSION['user_id'];

$check_stmt = $con->prepare("SELECT faculty_id FROM faculty WHERE faculty_id = ?");
$check_stmt->bind_param("i", $faculty_id);
$check_stmt->execute();
$check_result = $check_stmt->get_result();

if ($check_result->num_rows === 0) {
    // Auto-add user to faculty table if they're not there yet
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

// STEP 4: Validate that course code and name are not empty
if (empty($course_code) || empty($course_name)) {
    echo json_encode(["success" => false, "message" => "Course code and name cannot be empty."]);
    exit();
}

// STEP 5: Check if course code already exists (course_code must be unique)
$stmt = $con->prepare("SELECT course_id FROM courses WHERE course_code = ?");
$stmt->bind_param("s", $course_code);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    echo json_encode(["success" => false, "message" => "Course code already exists."]);
    $stmt->close();
    exit();
}
$stmt->close();

// STEP 6: Insert new course into courses table
// Columns: course_code, course_name, description, faculty_id
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


