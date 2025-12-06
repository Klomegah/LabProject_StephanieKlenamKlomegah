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

// Verify user is a student
$user_id = $_SESSION['user_id'];
$check_stmt = $con->prepare("SELECT student_id FROM students WHERE student_id = ?");
$check_stmt->bind_param("i", $user_id);
$check_stmt->execute();
$check_result = $check_stmt->get_result();

if ($check_result->num_rows === 0) {
    echo json_encode(["success" => false, "message" => "Unauthorized. Student access required."]);
    $check_stmt->close();
    exit();
}
$check_stmt->close();

$input = json_decode(file_get_contents("php://input"), true);

if ($input === null || !isset($input['attendance_code'])) {
    echo json_encode(["success" => false, "message" => "Attendance code is required."]);
    exit();
}

$student_id = $user_id;
$attendance_code = strtoupper(trim($input['attendance_code']));

// NOTE: Since sessions table doesn't have attendance_code column in your schema,
// we'll use session_id as the code (faculty can share the session_id)
// OR you can add attendance_code column to sessions table

// Try to find session by ID (if code is numeric, treat as session_id)
if (is_numeric($attendance_code)) {
    $session_id = intval($attendance_code);
    
    // Verify session exists and student is enrolled
    $stmt = $con->prepare("SELECT s.session_id, s.course_id, s.date
                           FROM sessions s
                           INNER JOIN course_student_list csl ON s.course_id = csl.course_id
                           WHERE s.session_id = ? AND csl.student_id = ? AND s.date >= CURDATE()");
    $stmt->bind_param("ii", $session_id, $student_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(["success" => false, "message" => "Invalid session code or you are not enrolled in this course."]);
        $stmt->close();
        exit();
    }
    
    $session = $result->fetch_assoc();
    $stmt->close();
    
    // Check if already marked
    $check_stmt = $con->prepare("SELECT attendance_id FROM attendance WHERE session_id = ? AND student_id = ?");
    $check_stmt->bind_param("ii", $session_id, $student_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows > 0) {
        echo json_encode(["success" => false, "message" => "Attendance already marked for this session."]);
        $check_stmt->close();
        exit();
    }
    $check_stmt->close();
    
    // Mark attendance
    $current_time = date('H:i:s');
    $stmt = $con->prepare("INSERT INTO attendance (session_id, student_id, status, check_in_time) VALUES (?, ?, 'present', ?)");
    $stmt->bind_param("iis", $session_id, $student_id, $current_time);
    
    if ($stmt->execute()) {
        echo json_encode([
            "success" => true,
            "message" => "Attendance marked successfully!",
            "session_date" => $session['date']
        ]);
    } else {
        echo json_encode(["success" => false, "message" => "Failed to mark attendance: " . $stmt->error]);
    }
    
    $stmt->close();
} else {
    echo json_encode(["success" => false, "message" => "Invalid attendance code format. Please enter the session ID number provided by your instructor."]);
}
?>
