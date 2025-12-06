// Faculty Intern Dashboard JavaScript

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

// Load all courses (faculty interns can see all courses)
async function loadCourses() {
    try {
        const courseFilter = document.getElementById('course-filter')?.value || '';
        const url = `${API_BASE}get_courses.php`;
        
        const response = await fetch(url, {
            method: 'GET',
            headers: {
                'Accept': 'application/json'
            }
        });

        const result = await response.json();

        if (result.success) {
            displayCourses(result.courses, courseFilter);
            populateCourseFilters(result.courses);
        } else {
            showMessage(result.message || 'Failed to load courses', 'error');
        }
    } catch (error) {
        console.error('Error loading courses:', error);
        showMessage('Error loading courses', 'error');
    }
}

// Display courses in table
function displayCourses(courses, facultyFilter = '') {
    const tbody = document.querySelector('#courses-table tbody');
    if (!tbody) return;

    tbody.innerHTML = '';

    // Filter by faculty if needed
    let filteredCourses = courses;
    if (facultyFilter) {
        // This would need faculty name - for now show all
        filteredCourses = courses;
    }

    if (!filteredCourses || filteredCourses.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" style="text-align: center;">No courses found.</td></tr>';
        return;
    }

    filteredCourses.forEach(course => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${course.course_code || 'N/A'}</td>
            <td>${course.course_name || 'N/A'}</td>
            <td>${course.faculty_name || 'N/A'}</td>
            <td>${course.enrolled_students || 0}</td>
            <td>
                <button class="primary-btn" onclick="viewCourseDetails(${course.course_id})">View Details</button>
            </td>
        `;
        tbody.appendChild(row);
    });
}

// Populate course filter dropdowns
function populateCourseFilters(courses) {
    const filters = ['session-course-filter', 'report-course-filter', 'auditor-course-filter'];
    
    filters.forEach(filterId => {
        const select = document.getElementById(filterId);
        if (select) {
            // Keep "All Courses" option
            const currentValue = select.value;
            select.innerHTML = '<option value="">All Courses</option>';
            
            courses.forEach(course => {
                const option = document.createElement('option');
                option.value = course.course_id;
                option.textContent = `${course.course_code} - ${course.course_name}`;
                select.appendChild(option);
            });
            
            // Restore previous selection if it still exists
            if (currentValue) {
                select.value = currentValue;
            }
        }
    });
}

// View course details
function viewCourseDetails(courseId) {
    showMessage('Course details feature - coming soon', 'error');
    // Could open a modal with course details, enrolled students, etc.
}

// Load sessions
async function loadSessions() {
    try {
        const courseFilter = document.getElementById('session-course-filter')?.value || '';
        const url = courseFilter ? `${API_BASE}get_sessions.php?course_id=${courseFilter}` : `${API_BASE}get_sessions.php`;
        
        const response = await fetch(url, {
            method: 'GET',
            headers: {
                'Accept': 'application/json'
            }
        });

        const result = await response.json();

        if (result.success) {
            displaySessions(result.sessions);
        } else {
            showMessage(result.message || 'Failed to load sessions', 'error');
        }
    } catch (error) {
        console.error('Error loading sessions:', error);
        showMessage('Error loading sessions', 'error');
    }
}

// Display sessions in table
function displaySessions(sessions) {
    const tbody = document.querySelector('#sessions-table tbody');
    if (!tbody) return;

    tbody.innerHTML = '';

    if (sessions.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" style="text-align: center;">No sessions found.</td></tr>';
        return;
    }

    sessions.forEach(session => {
        const row = document.createElement('tr');
        const sessionDate = new Date(session.session_date + 'T' + session.start_time);
        const timeRange = session.end_time ? 
            `${new Date(session.session_date + 'T' + session.start_time).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})} - ${new Date(session.session_date + 'T' + session.end_time).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}` :
            new Date(session.session_date + 'T' + session.start_time).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
        
        // Determine status based on date
        const today = new Date();
        today.setHours(0, 0, 0, 0);
        const sessionDateOnly = new Date(sessionDate);
        sessionDateOnly.setHours(0, 0, 0, 0);
        
        let status = 'Completed';
        let statusClass = 'status-success';
        if (sessionDateOnly.getTime() === today.getTime()) {
            status = 'Ongoing';
            statusClass = 'status-pending';
        } else if (sessionDateOnly.getTime() > today.getTime()) {
            status = 'Upcoming';
            statusClass = 'status-info';
        }
        
        row.innerHTML = `
            <td>${session.course_code} - ${session.course_name}</td>
            <td>${sessionDate.toLocaleDateString()}</td>
            <td>${timeRange}</td>
            <td><span class="status-badge ${statusClass}">${status}</span></td>
            <td>
                <button class="primary-btn" onclick="viewSessionSummary(${session.session_id})">View Session Summary</button>
            </td>
        `;
        tbody.appendChild(row);
    });
}

// View session summary
async function viewSessionSummary(sessionId) {
    try {
        const response = await fetch(`${API_BASE}get_attendance.php?session_id=${sessionId}`, {
            method: 'GET',
            headers: {
                'Accept': 'application/json'
            }
        });

        const result = await response.json();

        if (result.success) {
            // Show session summary in a modal or alert
            const totalStudents = result.attendance.length;
            const present = result.attendance.filter(a => a.status === 'present').length;
            const absent = result.attendance.filter(a => a.status === 'absent').length;
            const late = result.attendance.filter(a => a.status === 'late').length;
            
            const summary = `Session Summary:\n\nTotal Students: ${totalStudents}\nPresent: ${present}\nAbsent: ${absent}\nLate: ${late}`;
            alert(summary);
        } else {
            showMessage(result.message || 'Failed to load session summary', 'error');
        }
    } catch (error) {
        console.error('Error loading session summary:', error);
        showMessage('Error loading session summary', 'error');
    }
}

// Load attendance reports
async function loadReports() {
    try {
        // For now, we'll create a simple report from sessions and attendance
        // In a real system, you might have a dedicated reports endpoint
        const courseFilter = document.getElementById('report-course-filter')?.value || '';
        const url = courseFilter ? `${API_BASE}get_sessions.php?course_id=${courseFilter}` : `${API_BASE}get_sessions.php`;
        
        const response = await fetch(url, {
            method: 'GET',
            headers: {
                'Accept': 'application/json'
            }
        });

        const result = await response.json();

        if (result.success) {
            // Calculate attendance rates for each course
            const courseReports = {};
            
            for (const session of result.sessions) {
                const courseId = session.course_id;
                if (!courseReports[courseId]) {
                    courseReports[courseId] = {
                        course_code: session.course_code,
                        course_name: session.course_name,
                        total_sessions: 0,
                        total_attendance: 0
                    };
                }
                courseReports[courseId].total_sessions++;
                courseReports[courseId].total_attendance += session.attendance_count || 0;
            }
            
            displayReports(Object.values(courseReports));
        } else {
            showMessage(result.message || 'Failed to load reports', 'error');
        }
    } catch (error) {
        console.error('Error loading reports:', error);
        showMessage('Error loading reports', 'error');
    }
}

// Display reports in table
function displayReports(reports) {
    const tbody = document.querySelector('#reports-table tbody');
    if (!tbody) return;

    tbody.innerHTML = '';

    if (reports.length === 0) {
        tbody.innerHTML = '<tr><td colspan="4" style="text-align: center;">No reports available.</td></tr>';
        return;
    }

    reports.forEach(report => {
        const row = document.createElement('tr');
        // Calculate average attendance rate (simplified)
        const avgAttendance = report.total_sessions > 0 ? 
            Math.round((report.total_attendance / (report.total_sessions * 10)) * 100) : 0; // Assuming ~10 students per session
        
        row.innerHTML = `
            <td>${report.course_code} - ${report.course_name}</td>
            <td>${avgAttendance}%</td>
            <td>${report.total_sessions}</td>
            <td>
                <button class="primary-btn" onclick="publishReport(${report.course_id})" style="margin-right: 0.5rem;">Publish</button>
                <button class="edit-btn" onclick="editReport(${report.course_id})" style="margin-right: 0.5rem;">Edit</button>
                <button class="delete-btn" onclick="deleteReport(${report.course_id})">Delete</button>
            </td>
        `;
        tbody.appendChild(row);
    });
}

// Report actions
function publishReport(courseId) {
    if (confirm('Are you sure you want to publish this attendance report?')) {
        showMessage('Report published successfully', 'success');
        // In a real system, this would make an API call to publish the report
    }
}

function editReport(courseId) {
    showMessage('Edit report feature - coming soon', 'error');
    // Could open a modal to edit report details
}

function deleteReport(courseId) {
    if (confirm('Are you sure you want to delete this attendance report?')) {
        showMessage('Report deleted successfully', 'success');
        loadReports();
        // In a real system, this would make an API call to delete the report
    }
}

// Load auditors (students auditing courses)
async function loadAuditors() {
    try {
        // Get all enrolled students across all courses
        const courseFilter = document.getElementById('auditor-course-filter')?.value || '';
        
        // For now, we'll get students from enrollment requests
        // In a real system, you might have a separate "auditors" table
        const response = await fetch(`${API_BASE}get_enrollment_requests.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            }
        });

        const result = await response.json();

        if (result.success) {
            displayAuditors(result.requests, courseFilter);
        } else {
            showMessage(result.message || 'Failed to load auditors', 'error');
        }
    } catch (error) {
        console.error('Error loading auditors:', error);
        showMessage('Error loading auditors', 'error');
    }
}

// Display auditors in table
function displayAuditors(auditors, courseFilter = '') {
    const tbody = document.querySelector('#auditors-table tbody');
    if (!tbody) return;

    tbody.innerHTML = '';

    // Filter by course if needed
    let filteredAuditors = auditors;
    if (courseFilter) {
        filteredAuditors = auditors.filter(a => a.course_id == courseFilter);
    }

    if (filteredAuditors.length === 0) {
        tbody.innerHTML = '<tr><td colspan="4" style="text-align: center;">No auditors found.</td></tr>';
        return;
    }

    filteredAuditors.forEach(auditor => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${auditor.first_name} ${auditor.last_name}</td>
            <td>${auditor.email}</td>
            <td>${auditor.course_code} - ${auditor.course_name}</td>
            <td><span class="status-badge status-success">${auditor.status || 'Active'}</span></td>
        `;
        tbody.appendChild(row);
    });
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
            window.location.href = '../PHP/logout.php';
        }
    } catch (error) {
        window.location.href = '../PHP/logout.php';
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', () => {
    console.log('Faculty intern dashboard initialized');
    loadCourses();
    loadSessions();
    loadReports();
    loadAuditors();
});


