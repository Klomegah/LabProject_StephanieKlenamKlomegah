// Student Dashboard JavaScript

const API_BASE = '../PHP/';

// Show message function
function showMessage(message, type = 'success') {
    const messageDiv = document.getElementById('message');
    if (messageDiv) {
        messageDiv.textContent = message;
        messageDiv.className = `message ${type} show`;
        setTimeout(() => {
            messageDiv.classList.remove('show');
        }, 5000);
    }
}

// Load enrolled courses
async function loadEnrolledCourses() {
    try {
        const response = await fetch(`${API_BASE}get_enrolled_courses.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            }
        });

        const result = await response.json();

        if (result.success) {
            displayEnrolledCourses(result.courses);
        } else {
            showMessage(result.message || 'Failed to load courses', 'error');
        }
    } catch (error) {
        console.error('Error loading enrolled courses:', error);
        showMessage('Error loading courses', 'error');
    }
}

// Display enrolled courses
function displayEnrolledCourses(courses) {
    const tbody = document.querySelector('#enrolled-courses-table tbody');
    if (!tbody) return;

    tbody.innerHTML = '';

    if (courses.length === 0) {
        tbody.innerHTML = '<tr><td colspan="4" style="text-align: center;">You are not enrolled in any courses yet. Search and join courses below!</td></tr>';
        return;
    }

    courses.forEach(course => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${course.course_code}</td>
            <td>${course.course_name}</td>
            <td>${course.faculty_name || 'N/A'}</td>
            <td>${course.enrolled_at ? new Date(course.enrolled_at).toLocaleDateString() : 'N/A'}</td>
        `;
        tbody.appendChild(row);
    });
}

// Load all available courses
async function loadAvailableCourses() {
    try {
        const response = await fetch(`${API_BASE}get_courses.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            }
        });

        const result = await response.json();

        if (result.success) {
            allCourses = result.courses;
            filterAndDisplayCourses();
        } else {
            showMessage(result.message || 'Failed to load courses', 'error');
        }
    } catch (error) {
        console.error('Error loading courses:', error);
        showMessage('Error loading courses', 'error');
    }
}

let allCourses = [];

// Filter and display courses based on search
function filterAndDisplayCourses() {
    const searchTerm = document.getElementById('course-search').value.toLowerCase();
    const filteredCourses = allCourses.filter(course => 
        course.course_code.toLowerCase().includes(searchTerm) ||
        course.course_name.toLowerCase().includes(searchTerm) ||
        (course.faculty_name && course.faculty_name.toLowerCase().includes(searchTerm))
    );

    displayAvailableCourses(filteredCourses);
}

// Display available courses
function displayAvailableCourses(courses) {
    const tbody = document.querySelector('#available-courses-table tbody');
    if (!tbody) return;

    tbody.innerHTML = '';

    if (courses.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" style="text-align: center;">No courses found</td></tr>';
        return;
    }

    courses.forEach(course => {
        const row = document.createElement('tr');
        const statusClass = `status-${course.enrollment_status}`;
        const statusText = course.enrollment_status.charAt(0).toUpperCase() + course.enrollment_status.slice(1);
        
        let actionButton = '';
        if (course.enrollment_status === 'available' || course.enrollment_status === 'rejected') {
            actionButton = `<button class="request-btn" onclick="requestEnrollment(${course.course_id})">Request to Join</button>`;
        } else if (course.enrollment_status === 'pending') {
            actionButton = `<button class="request-btn" disabled>Request Pending</button>`;
        } else if (course.enrollment_status === 'enrolled') {
            actionButton = `<span class="status-badge status-enrolled">Enrolled</span>`;
        }

        row.innerHTML = `
            <td>${course.course_code}</td>
            <td>${course.course_name}</td>
            <td>${course.faculty_name || 'N/A'}</td>
            <td><span class="status-badge ${statusClass}">${statusText}</span></td>
            <td>${actionButton}</td>
        `;
        tbody.appendChild(row);
    });
}

// Request enrollment
async function requestEnrollment(courseId) {
    if (!confirm('Are you sure you want to request enrollment in this course?')) {
        return;
    }

    try {
        const response = await fetch(`${API_BASE}request_enrollment.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                course_id: courseId
            })
        });

        const result = await response.json();

        if (result.success) {
            showMessage(result.message, 'success');
            loadAvailableCourses(); // Refresh the list
            loadEnrolledCourses(); // Also refresh enrolled courses in case it was auto-approved
        } else {
            showMessage(result.message || 'Failed to submit request', 'error');
        }
    } catch (error) {
        console.error('Error requesting enrollment:', error);
        showMessage('Error submitting request', 'error');
    }
}

// Logout function
async function logout() {
    try {
        const response = await fetch(`${API_BASE}logout.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            }
        });

        const result = await response.json();
        if (result.success) {
            window.location.href = '../Login and Signup/login.html';
        } else {
            // Even if JSON fails, try direct redirect
            window.location.href = '../PHP/logout.php';
        }
    } catch (error) {
        // Fallback to direct redirect
        window.location.href = '../PHP/logout.php';
    }
}

// Note: Attendance code submission removed - attendance is marked by faculty only

// Load attendance reports
async function loadAttendanceReports() {
    const reportType = document.getElementById('report-type').value;
    const courseFilter = document.getElementById('report-course-filter').value || '';
    const dateFilter = document.getElementById('report-date').value || '';

    // Show/hide date filter based on report type
    const dateFilterGroup = document.getElementById('date-filter-group');
    if (dateFilterGroup) {
        dateFilterGroup.style.display = reportType === 'daily' ? 'block' : 'none';
        if (reportType === 'daily' && !dateFilter) {
            document.getElementById('report-date').value = new Date().toISOString().split('T')[0];
        }
    }

    try {
        let url = `${API_BASE}get_attendance_reports.php?type=${reportType}`;
        if (courseFilter) {
            url += `&course_id=${courseFilter}`;
        }
        if (reportType === 'daily' && dateFilter) {
            url += `&date=${dateFilter}`;
        }

        const response = await fetch(url, {
            method: 'GET',
            headers: {
                'Accept': 'application/json'
            }
        });

        const result = await response.json();

        if (result.success) {
            if (reportType === 'daily') {
                displayDailyReport(result.reports);
            } else {
                displayOverallReport(result.reports);
            }
        } else {
            showMessage(result.message || 'Failed to load reports', 'error');
        }
    } catch (error) {
        console.error('Error loading attendance reports:', error);
        showMessage('Error loading attendance reports', 'error');
    }
}

// Display overall attendance report
function displayOverallReport(reports) {
    const tbody = document.querySelector('#overall-attendance-table tbody');
    const overallDiv = document.getElementById('overall-report');
    const dailyDiv = document.getElementById('daily-report');

    if (overallDiv) overallDiv.style.display = 'block';
    if (dailyDiv) dailyDiv.style.display = 'none';

    if (!tbody) return;

    tbody.innerHTML = '';

    if (reports.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" style="text-align: center;">No attendance data available</td></tr>';
        return;
    }

    reports.forEach(report => {
        const row = document.createElement('tr');
        const attendanceRate = parseFloat(report.attendance_rate) || 0;
        const rateColor = attendanceRate >= 75 ? 'var(--success-color)' : attendanceRate >= 50 ? '#ffc107' : 'var(--error-color)';
        
        row.innerHTML = `
            <td>${report.course_code}</td>
            <td>${report.course_name}</td>
            <td>${report.total_sessions || 0}</td>
            <td>${report.attended_sessions || 0}</td>
            <td><strong style="color: ${rateColor};">${attendanceRate.toFixed(1)}%</strong></td>
        `;
        tbody.appendChild(row);
    });
}

// Display daily attendance report
function displayDailyReport(reports) {
    const tbody = document.querySelector('#daily-attendance-table tbody');
    const overallDiv = document.getElementById('overall-report');
    const dailyDiv = document.getElementById('daily-report');

    if (overallDiv) overallDiv.style.display = 'none';
    if (dailyDiv) dailyDiv.style.display = 'block';

    if (!tbody) return;

    tbody.innerHTML = '';

    if (reports.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" style="text-align: center;">No sessions found for this date</td></tr>';
        return;
    }

    reports.forEach(report => {
        const row = document.createElement('tr');
        const statusClass = `status-${report.status}`;
        const sessionDate = new Date(report.session_date + 'T' + report.start_time);
        const checkInTime = report.check_in_time ? report.check_in_time : 'Not marked';
        
        row.innerHTML = `
            <td>${report.course_code} - ${report.course_name}</td>
            <td>${sessionDate.toLocaleDateString()}</td>
            <td>${sessionDate.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}</td>
            <td><span class="status-badge ${statusClass}">${report.status}</span></td>
            <td>${checkInTime}</td>
        `;
        tbody.appendChild(row);
    });
}

// Populate course filter dropdown
function populateCourseFilter() {
    const filterSelect = document.getElementById('report-course-filter');
    if (!filterSelect) return;

    // Get enrolled courses
    fetch(`${API_BASE}get_enrolled_courses.php`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        }
    })
    .then(res => res.json())
    .then(result => {
        if (result.success) {
            filterSelect.innerHTML = '<option value="">All Courses</option>';
            result.courses.forEach(course => {
                const option = document.createElement('option');
                option.value = course.course_id;
                option.textContent = `${course.course_code} - ${course.course_name}`;
                filterSelect.appendChild(option);
            });
        }
    })
    .catch(error => {
        console.error('Error loading courses for filter:', error);
    });
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', () => {
    loadEnrolledCourses();
    loadAvailableCourses();
    populateCourseFilter();
    loadAttendanceReports();

    // Set up search functionality
    const searchInput = document.getElementById('course-search');
    if (searchInput) {
        searchInput.addEventListener('input', filterAndDisplayCourses);
    }

    // Attendance is marked by faculty, students can only view reports
});

