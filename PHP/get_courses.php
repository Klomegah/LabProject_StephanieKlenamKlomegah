<?php
/**
 * Get Courses Endpoint
 * 
 * Returns list of courses based on user role:
 * - Faculty: Returns only courses they teach
 * - Students: Returns all courses with enrollment status
 * 
 * Database Tables Used:
 * - courses: Main course data
 * - course_student_list: To count enrolled students
 * - faculty: To verify faculty status
 * - users: To get faculty names for students
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
    // STEP 1: Verify user is logged in
    // ============================================================================
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(["success" => false, "message" => "Unauthorized."]);
        exit();
    }

    // ============================================================================
    // STEP 2: Determine if user is faculty or student
    // ============================================================================
    $user_id = $_SESSION['user_id'];
    $is_faculty_user = isFaculty($con, $user_id);

    // ============================================================================
    // STEP 3: Build query based on user role
    // ============================================================================
    if ($is_faculty_user) {
        // ========================================================================
        // FACULTY QUERY: Get courses taught by this faculty
        // ========================================================================
        // Returns: course info + count of enrolled students
        $faculty_id = $user_id;
        
        $stmt = $con->prepare("SELECT c.course_id, c.course_code, c.course_name, c.description, 
                               COUNT(DISTINCT csl.student_id) as enrolled_students,
                               0 as pending_requests
                               FROM courses c
                               LEFT JOIN course_student_list csl ON c.course_id = csl.course_id
                               WHERE c.faculty_id = ?
                               GROUP BY c.course_id
                               ORDER BY c.course_id DESC");
        
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $con->error);
        }
        
        $stmt->bind_param("i", $faculty_id);
    } else {
        // Students see all available courses with their enrollment status
        // Using ONLY the provided tables (no course_requests)
        $student_id = $user_id;
        
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


