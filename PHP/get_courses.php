<?php
session_start();
require_once 'db.php';
require_once 'auth_check.php';
require_once 'faculty_check.php';

// Set JSON response header
header('Content-Type: application/json');

// Error handling
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors, return JSON instead

try {
    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(["success" => false, "message" => "Unauthorized."]);
        exit();
    }

    $user_id = $_SESSION['user_id'];
    $is_faculty_user = isFaculty($con, $user_id);

    if ($is_faculty_user) {
        // Faculty/Faculty Intern sees only their courses
        $faculty_id = $user_id;
        
        // Check if course_requests table exists, if not, use simpler query
        $has_requests_table = false;
        try {
            $table_check = $con->query("SHOW TABLES LIKE 'course_requests'");
            $has_requests_table = $table_check && $table_check->num_rows > 0;
        } catch (Exception $e) {
            // Table doesn't exist or error checking, use simplified query
            $has_requests_table = false;
        }
        
        if ($has_requests_table) {
            $stmt = $con->prepare("SELECT c.course_id, c.course_code, c.course_name, c.description, 
                                   COUNT(DISTINCT csl.student_id) as enrolled_students,
                                   COUNT(DISTINCT CASE WHEN cr.status = 'pending' THEN cr.request_id END) as pending_requests
                                   FROM courses c
                                   LEFT JOIN course_student_list csl ON c.course_id = csl.course_id
                                   LEFT JOIN course_requests cr ON c.course_id = cr.course_id
                                   WHERE c.faculty_id = ?
                                   GROUP BY c.course_id
                                   ORDER BY c.course_id DESC");
        } else {
            // Simplified query without course_requests table
            $stmt = $con->prepare("SELECT c.course_id, c.course_code, c.course_name, c.description, 
                                   COUNT(DISTINCT csl.student_id) as enrolled_students,
                                   0 as pending_requests
                                   FROM courses c
                                   LEFT JOIN course_student_list csl ON c.course_id = csl.course_id
                                   WHERE c.faculty_id = ?
                                   GROUP BY c.course_id
                                   ORDER BY c.course_id DESC");
        }
        
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $con->error);
        }
        
        $stmt->bind_param("i", $faculty_id);
    } else {
        // Students see all available courses with their enrollment status
        $student_id = $user_id;
        
        // Check if course_requests table exists
        $has_requests_table = false;
        try {
            $table_check = $con->query("SHOW TABLES LIKE 'course_requests'");
            $has_requests_table = $table_check && $table_check->num_rows > 0;
        } catch (Exception $e) {
            // Table doesn't exist or error checking, use simplified query
            $has_requests_table = false;
        }
        
        if ($has_requests_table) {
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
        } else {
            // Simplified query without course_requests table
            $stmt = $con->prepare("SELECT c.course_id, c.course_code, c.course_name, c.description,
                                   CONCAT(u.first_name, ' ', u.last_name) as faculty_name,
                                   CASE WHEN csl.student_id IS NOT NULL THEN 'enrolled'
                                        ELSE 'available' END as enrollment_status
                                   FROM courses c
                                   INNER JOIN faculty f ON c.faculty_id = f.faculty_id
                                   INNER JOIN users u ON f.faculty_id = u.user_id
                                   LEFT JOIN course_student_list csl ON c.course_id = csl.course_id AND csl.student_id = ?
                                   ORDER BY c.course_id DESC");
            $stmt->bind_param("i", $student_id);
        }
        
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $con->error);
        }
    }

    if (!$stmt->execute()) {
        throw new Exception("Execute failed: " . $stmt->error);
    }
    
    $result = $stmt->get_result();
    $courses = [];

    while ($row = $result->fetch_assoc()) {
        $courses[] = $row;
    }

    echo json_encode(["success" => true, "courses" => $courses]);
    $stmt->close();
    
} catch (Exception $e) {
    // Return error as JSON instead of causing 500 error
    http_response_code(200); // Return 200 with error in JSON
    echo json_encode([
        "success" => false, 
        "message" => "Error loading courses: " . $e->getMessage(),
        "error" => $e->getMessage()
    ]);
}
?>


