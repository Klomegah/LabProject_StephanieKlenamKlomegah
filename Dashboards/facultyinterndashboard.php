<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../Login and Signup/login.html");
    exit();
}

// Check if user is faculty or faculty intern (exists in faculty table)
require_once '../PHP/faculty_check.php';
require_once '../PHP/db.php';

if (!isset($_SESSION['user_id']) || !isFaculty($con, $_SESSION['user_id'])) {
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
    <title>Faculty Intern Dashboard</title>
    <link rel="stylesheet" href="dashboard.css">
</head>

<body>
    <header>
        <h1>Faculty Intern Dashboard</h1>
        <nav>
            <ul>
                <li><a href="#course-list">Course List</a></li>
                <li><a href="#sessions">Sessions</a></li>
                <li><a href="#reports">Attendance Reports</a></li>
                <li><a href="#auditors">Auditors</a></li>
                <li><a href="#" onclick="logout(); return false;">Logout</a></li>
            </ul>
        </nav>
    </header>

    <main>
        <div id="message" class="message"></div>

        <!-- Course List Section -->
        <section id="course-list">
            <h2>Course List</h2>
            <p style="margin-bottom: 1rem; color: #666;">View all courses in the system.</p>
            
            <div class="form-group" style="margin-bottom: 1.5rem;">
                <label for="course-filter">Filter by Faculty:</label>
                <select id="course-filter" onchange="loadCourses()">
                    <option value="">All Faculty</option>
                </select>
            </div>

            <table id="courses-table">
                <thead>
                    <tr>
                        <th>Course Code</th>
                        <th>Course Name</th>
                        <th>Assigned Faculty</th>
                        <th>Enrolled Students</th>
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

        <!-- Sessions Section -->
        <section id="sessions">
            <h2>Sessions</h2>
            <p style="margin-bottom: 1rem; color: #666;">View all class sessions across all courses.</p>

            <div class="form-group" style="margin-bottom: 1.5rem;">
                <label for="session-course-filter">Filter by Course:</label>
                <select id="session-course-filter" onchange="loadSessions()">
                    <option value="">All Courses</option>
                </select>
            </div>

            <table id="sessions-table">
                <thead>
                    <tr>
                        <th>Course</th>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td colspan="5" class="loading">Loading sessions...</td>
                    </tr>
                </tbody>
            </table>
        </section>

        <!-- Attendance Reports Section -->
        <section id="reports">
            <h2>Attendance Reports</h2>
            <p style="margin-bottom: 1rem; color: #666;">View and manage attendance reports for all courses.</p>

            <div class="form-group" style="margin-bottom: 1.5rem;">
                <label for="report-course-filter">Filter by Course:</label>
                <select id="report-course-filter" onchange="loadReports()">
                    <option value="">All Courses</option>
                </select>
            </div>

            <table id="reports-table">
                <thead>
                    <tr>
                        <th>Course</th>
                        <th>Attendance Rate</th>
                        <th>Total Sessions</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td colspan="4" class="loading">Loading reports...</td>
                    </tr>
                </tbody>
            </table>
        </section>

        <!-- Auditors Section -->
        <section id="auditors">
            <h2>Auditors</h2>
            <p style="margin-bottom: 1rem; color: #666;">View students who are auditing courses.</p>

            <div class="form-group" style="margin-bottom: 1.5rem;">
                <label for="auditor-course-filter">Filter by Course:</label>
                <select id="auditor-course-filter" onchange="loadAuditors()">
                    <option value="">All Courses</option>
                </select>
            </div>

            <table id="auditors-table">
                <thead>
                    <tr>
                        <th>Student Name</th>
                        <th>Email</th>
                        <th>Course</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td colspan="4" class="loading">Loading auditors...</td>
                    </tr>
                </tbody>
            </table>
        </section>
    </main>

    <footer>
        <p>© 2025 Attendance Management System</p>
    </footer>

    <script src="faculty-intern-dashboard.js"></script>
</body>
</html>

