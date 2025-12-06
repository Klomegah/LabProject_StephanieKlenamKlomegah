<?php
/**
 * Helper function to check if a user is faculty or faculty intern
 * Checks if user exists in faculty table (not just role)
 */
function isFaculty($con, $user_id) {
    $stmt = $con->prepare("SELECT faculty_id FROM faculty WHERE faculty_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $is_faculty = $result->num_rows > 0;
    $stmt->close();
    return $is_faculty;
}
?>


