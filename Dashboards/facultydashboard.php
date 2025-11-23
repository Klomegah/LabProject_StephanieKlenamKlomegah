<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../Login and Signup/login.html");
    exit();
}

// Check if user is faculty
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'faculty') {
    header("Location: studentdashboard.php");
    exit();
}

// If authorized, output the HTML file
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1.0">
    <title>Faculty Dashboard</title>
    <link rel="stylesheet" href="dashboard.css">
</head>

<body>
    <header>
        <h1>Faculty Dashboard</h1>
        <nav>
            <ul>
                <li><a href="#course-management">Course Management</a></li>
                <li><a href="#enrollment-requests">Enrollment Requests</a></li>
                <li><a href="#" onclick="logout(); return false;">Logout</a></li>
            </ul>
        </nav>
    </header>

    <main>
        <div id="message" class="message"></div>

        <!-- Course Management Section -->
        <section id="course-management">
            <h2>Course Management</h2>

            <button class="primary-btn" onclick="openCreateCourseModal()" style="margin-bottom: 1.5rem;">Create New Course</button>

            <h3>My Courses</h3>
            <table id="courses-table">
                <thead>
                    <tr>
                        <th>Course Code</th>
                        <th>Course Name</th>
                        <th>Enrolled Students</th>
                        <th>Pending Requests</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td colspan="5" class="loading">Loading courses...</td>
                    </tr>
                </tbody>
            </table>
        </section>

        <!-- Enrollment Requests Section -->
        <section id="enrollment-requests">
            <h2>Enrollment Requests</h2>
            <table id="requests-table">
                <thead>
                    <tr>
                        <th>Course Code</th>
                        <th>Course Name</th>
                        <th>Student Name</th>
                        <th>Email</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td colspan="6" class="loading">Loading requests...</td>
                    </tr>
                </tbody>
            </table>
        </section>
    </main>

    <footer>
        <p>© 2025 Attendance Management System</p>
    </footer>

    <!-- Create Course Modal -->
    <div id="create-course-modal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('create-course-modal')">&times;</span>
            <h2>Create New Course</h2>
            <form id="create-course-form">
                <div class="form-group">
                    <label for="course-code">Course Code *</label>
                    <input type="text" id="course-code" name="course-code" placeholder="e.g., CS201" required>
                </div>

                <div class="form-group">
                    <label for="course-name">Course Name *</label>
                    <input type="text" id="course-name" name="course-name" placeholder="e.g., Data Structures" required>
                </div>

                <div class="form-group">
                    <label for="course-desc">Course Description</label>
                    <textarea id="course-desc" name="course-desc" placeholder="Enter course description (optional)"></textarea>
                </div>

                <button type="submit" class="primary-btn">Create Course</button>
                <button type="button" onclick="closeModal('create-course-modal')" style="background-color: #6c757d; margin-left: 1rem;">Cancel</button>
            </form>
        </div>
    </div>

    <script src="faculty-dashboard.js"></script>
</body>

</html>

