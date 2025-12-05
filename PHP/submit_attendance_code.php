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

$input = json_decode(file_get_contents("php://input"), true);

if ($input === null || !isset($input['attendance_code'])) {
    echo json_encode(["success" => false, "message" => "Attendance code is required."]);
    exit();
}

$student_id = $_SESSION['student_id'] ?? $_SESSION['user_id'];
$attendance_code = strtoupper(trim($input['attendance_code']));

// Since attendance_code is not in the database, we'll use a different approach
// For now, students can't mark attendance via code - they need faculty to mark it
// Or we can implement a session-based approach where faculty shares a session ID
// For this implementation, we'll disable code-based attendance and require faculty marking

echo json_encode(["success" => false, "message" => "Attendance code system not available. Please contact your instructor to mark attendance."]);
exit();

// Note: If you want to implement code-based attendance, you would need to:
// 1. Add attendance_code column to sessions table, OR
// 2. Use a temporary code system stored separately, OR  
// 3. Have faculty generate and share session-specific codes manually

// Verify student is enrolled in the course
$check_stmt = $con->prepare("SELECT student_id FROM course_student_list 
                             WHERE course_id = ? AND student_id = ?");
$check_stmt->bind_param("ii", $course_id, $student_id);
$check_stmt->execute();
$check_result = $check_stmt->get_result();

if ($check_result->num_rows === 0) {
    echo json_encode(["success" => false, "message" => "You are not enrolled in this course."]);
    $check_stmt->close();
    $stmt->close();
    exit();
}
$check_stmt->close();
$stmt->close();

// Check if already marked
$check_stmt = $con->prepare("SELECT attendance_id FROM attendance 
                             WHERE session_id = ? AND student_id = ?");
$check_stmt->bind_param("ii", $session_id, $student_id);
$check_stmt->execute();
$check_result = $check_stmt->get_result();

if ($check_result->num_rows > 0) {
    echo json_encode(["success" => false, "message" => "Attendance already marked for this session."]);
    $check_stmt->close();
    exit();
}
$check_stmt->close();

// Mark attendance (using existing attendance table structure)
$current_time = date('H:i:s');
$stmt = $con->prepare("INSERT INTO attendance (session_id, student_id, status, check_in_time) 
                       VALUES (?, ?, 'present', ?)");
$stmt->bind_param("iis", $session_id, $student_id, $current_time);

if ($stmt->execute()) {
    echo json_encode([
        "success" => true,
        "message" => "Attendance marked successfully!",
        "session_date" => $session['session_date']
    ]);
} else {
    echo json_encode(["success" => false, "message" => "Failed to mark attendance: " . $stmt->error]);
}

$stmt->close();
?>

