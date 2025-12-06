<?php
/**
 * Get Enrolled Students for Faculty
 * 
 * This endpoint returns all students enrolled in courses taught by the faculty member.
 * Since there's no course_requests table, this shows the current enrollment list
 * from the course_student_list table.
 * 
 * Returns: List of all enrolled students with their course and student information
 */

session_start();
require_once 'db.php';
require_once 'auth_check.php';
require_once 'faculty_check.php';

header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 0);

try {
    // ============================================================================
    // STEP 1: Verify user is logged in and is faculty/faculty intern
    // ============================================================================
    if (!isset($_SESSION['user_id']) || !isFaculty($con, $_SESSION['user_id'])) {
        echo json_encode(["success" => false, "message" => "Unauthorized. Faculty access required."]);
        exit();
    }

    $faculty_id = $_SESSION['user_id'];

    // ============================================================================
    // STEP 2: Get all enrolled students for courses taught by this faculty
    // ============================================================================
    // Query joins: courses → course_student_list → students → users
    // This gets all students enrolled in any course taught by this faculty
    $stmt = $con->prepare("SELECT 
                           c.course_id,
                           c.course_code,
                           c.course_name,
                           csl.student_id,
                           u.first_name,
                           u.last_name,
                           u.email,
                           'enrolled' as status
                           FROM courses c
                           INNER JOIN course_student_list csl ON c.course_id = csl.course_id
                           INNER JOIN students s ON csl.student_id = s.student_id
                           INNER JOIN users u ON s.student_id = u.user_id
                           WHERE c.faculty_id = ?
                           ORDER BY c.course_code, u.last_name, u.first_name");
    
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $con->error);
    }
    
    $stmt->bind_param("i", $faculty_id);
    
    if (!$stmt->execute()) {
        throw new Exception("Execute failed: " . $stmt->error);
    }
    
    // ============================================================================
    // STEP 3: Build array of enrolled students
    // ============================================================================
    $result = $stmt->get_result();
    $requests = [];

    while ($row = $result->fetch_assoc()) {
        $requests[] = [
            'course_id' => $row['course_id'],
            'course_code' => $row['course_code'],
            'course_name' => $row['course_name'],
            'student_id' => $row['student_id'],
            'first_name' => $row['first_name'],
            'last_name' => $row['last_name'],
            'email' => $row['email'],
            'status' => 'enrolled'
        ];
    }

    echo json_encode(["success" => true, "requests" => $requests]);
    $stmt->close();
    
} catch (Exception $e) {
    http_response_code(200);
    echo json_encode([
        "success" => false, 
        "message" => "Error loading enrollment list: " . $e->getMessage(),
        "error" => $e->getMessage()
    ]);
}
?>


