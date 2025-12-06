<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../Login and Signup/login.html");
    exit();
}

// Check if user is student
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header("Location: facultydashboard.php");
    exit();
}

// If authorized, output the HTML file
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1.0">
    <title>Student Dashboard</title>
    <link rel="stylesheet" href="dashboard.css">
</head>

<body>
    <header>
        <h1>Student Dashboard</h1>
        <nav>
            <ul>
                <li><a href="#my-courses">My Courses</a></li>
                <li><a href="#mark-attendance">Mark Attendance</a></li>
                <li><a href="#attendance-reports">Attendance Reports</a></li>
                <li><a href="#available-courses">Browse Courses</a></li>
                <li><a href="#" onclick="logout(); return false;">Logout</a></li>
            </ul>
        </nav>
    </header>

    <main>
        <div id="message" class="message"></div>

        <!-- My Enrolled Courses Section -->
        <section id="my-courses">
            <h2>My Enrolled Courses</h2>
            <table id="enrolled-courses-table">
                <thead>
                    <tr>
                        <th>Course Code</th>
                        <th>Course Name</th>
                        <th>Faculty</th>
                        <th>Enrolled Date</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td colspan="4" class="loading">Loading courses...</td>
                    </tr>
                </tbody>
            </table>
        </section>

        <!-- Mark Attendance Section -->
        <section id="mark-attendance">
            <h2>Mark Attendance</h2>
            <p style="margin-bottom: 1.5rem; color: #666;">Enter the Session ID provided by your instructor to mark your attendance for a class session.</p>
            
            <form id="attendance-code-form" style="max-width: 500px;">
                <div class="form-group">
                    <label for="attendance-code">Session ID *</label>
                    <input type="text" id="attendance-code" name="attendance-code" placeholder="Enter Session ID (number)" required style="font-size: 1.2rem; text-align: center;">
                </div>
                <button type="submit" class="primary-btn">Submit Attendance</button>
            </form>
        </section>

        <!-- Attendance Reports Section -->
        <section id="attendance-reports">
            <h2>Attendance Reports</h2>

            <div style="margin-bottom: 1.5rem; display: flex; gap: 1rem; flex-wrap: wrap; align-items: center;">
                <div class="form-group" style="margin-bottom: 0;">
                    <label for="report-course-filter">Filter by Course:</label>
                    <select id="report-course-filter" onchange="loadAttendanceReports()">
                        <option value="">All Courses</option>
                    </select>
                </div>
                <div class="form-group" style="margin-bottom: 0;">
                    <label for="report-type">Report Type:</label>
                    <select id="report-type" onchange="loadAttendanceReports()">
                        <option value="overall">Overall Report</option>
                        <option value="daily">Daily Report</option>
                    </select>
                </div>
                <div class="form-group" id="date-filter-group" style="margin-bottom: 0; display: none;">
                    <label for="report-date">Date:</label>
                    <input type="date" id="report-date" onchange="loadAttendanceReports()" value="">
                </div>
            </div>

            <div id="overall-report">
                <h3>Overall Attendance</h3>
                <table id="overall-attendance-table">
                    <thead>
                        <tr>
                            <th>Course Code</th>
                            <th>Course Name</th>
                            <th>Total Sessions</th>
                            <th>Attended</th>
                            <th>Attendance Rate</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td colspan="5" class="loading">Loading reports...</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div id="daily-report" style="display: none;">
                <h3>Daily Attendance</h3>
                <table id="daily-attendance-table">
                    <thead>
                        <tr>
                            <th>Course</th>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Status</th>
                            <th>Marked At</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td colspan="5" class="loading">Loading reports...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <!-- Available Courses Section -->
        <section id="available-courses">
            <h2>Browse and Join Courses</h2>
            
            <div class="search-bar">
                <input type="text" id="course-search" placeholder="Search courses by code, name, or faculty...">
            </div>

            <table id="available-courses-table">
                <thead>
                    <tr>
                        <th>Course Code</th>
                        <th>Course Name</th>
                        <th>Faculty</th>
                        <th>Status</th>
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
    </main>

    <footer>
        <p>© 2025 Attendance Management System</p>
    </footer>

    <script src="student-dashboard.js"></script>
</body>

</html>

