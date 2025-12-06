// Faculty Dashboard JavaScript

const API_BASE = '../PHP/';

// Modal functions - make them globally accessible
window.openCreateCourseModal = function() {
    const modal = document.getElementById('create-course-modal');
    if (modal) {
        modal.style.display = 'block';
    }
};

window.closeModal = function(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'none';
    }
}

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

// Load courses
async function loadCourses() {
    try {
        const response = await fetch(`${API_BASE}get_courses.php`, {
            method: 'GET',
            headers: {
                'Accept': 'application/json'
            }
        });

        // Check if response is OK
        if (!response.ok) {
            const errorText = await response.text();
            console.error('Response error:', errorText);
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        // Try to parse JSON response
        let result;
        try {
            const responseText = await response.text();
            console.log('Courses response:', responseText);
            result = JSON.parse(responseText);
        } catch (jsonError) {
            console.error('JSON parse error:', jsonError);
            throw new Error('Invalid response from server. Please try again.');
        }

        console.log('Courses result:', result);

        if (result.success) {
            displayCourses(result.courses);
        } else {
            showMessage(result.message || 'Failed to load courses', 'error');
        }
    } catch (error) {
        console.error('Error loading courses:', error);
        showMessage('Error loading courses: ' + error.message, 'error');
    }
}

// Display courses in table
function displayCourses(courses) {
    const tbody = document.querySelector('#courses-table tbody');
    if (!tbody) {
        console.error('Courses table tbody not found');
        return;
    }

    tbody.innerHTML = '';

    if (!courses || courses.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" style="text-align: center;">No courses found. Create your first course!</td></tr>';
        return;
    }

    console.log('Displaying courses:', courses);

    courses.forEach(course => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${course.course_code || 'N/A'}</td>
            <td>${course.course_name || 'N/A'}</td>
            <td>${course.enrolled_students || 0}</td>
            <td>
                <span class="status-badge ${course.pending_requests > 0 ? 'status-pending' : ''}">
                    ${course.pending_requests || 0} pending
                </span>
            </td>
            <td>
                <button class="edit-btn" onclick="editCourse(${course.course_id})">Edit</button>
                <button class="delete-btn" onclick="deleteCourse(${course.course_id})">Delete</button>
                <button class="primary-btn" onclick="viewCourseStudents(${course.course_id})" style="margin-left: 0.5rem;">Manage Students</button>
            </td>
        `;
        tbody.appendChild(row);
    });
}

// View and manage students in a course
async function viewCourseStudents(courseId) {
    // This would open a modal showing enrolled students with option to remove them
    // For now, we'll show a message - you can enhance this later
    showMessage('Student management feature - coming soon. Use enrollment requests to manage students.', 'error');
}

// Create course
async function createCourse(event) {
    event.preventDefault();

    const courseCode = document.getElementById('course-code').value.trim();
    const courseName = document.getElementById('course-name').value.trim();
    const courseDesc = document.getElementById('course-desc').value.trim();

    if (!courseCode || !courseName) {
        showMessage('Course code and name are required', 'error');
        return;
    }

    try {
        const payload = {
            course_code: courseCode,
            course_name: courseName,
            course_description: courseDesc
        };
        
        console.log('Creating course with payload:', payload);
        
        const response = await fetch(`${API_BASE}create_course.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify(payload)
        });

        console.log('Response status:', response.status);

        // Check if response is OK
        if (!response.ok) {
            const errorText = await response.text();
            console.error('Response error:', errorText);
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        // Try to parse JSON response
        let result;
        try {
            const responseText = await response.text();
            console.log('Response text:', responseText);
            result = JSON.parse(responseText);
        } catch (jsonError) {
            console.error('JSON parse error:', jsonError);
            throw new Error('Invalid response from server. Please try again.');
        }

        console.log('Result:', result);

        if (result.success) {
            showMessage('Course created successfully!', 'success');
            document.getElementById('create-course-form').reset();
            // Close modal if open
            const modal = document.getElementById('create-course-modal');
            if (modal) modal.style.display = 'none';
            // Reload courses after a short delay to ensure the course is saved
            setTimeout(() => {
                loadCourses();
            }, 500);
        } else {
            showMessage(result.message || 'Failed to create course', 'error');
        }
    } catch (error) {
        console.error('Error creating course:', error);
        showMessage(error.message || 'Error creating course. Please check your connection and try again.', 'error');
    }
}

// Load enrollment requests
async function loadRequests() {
    try {
        const response = await fetch(`${API_BASE}get_enrollment_requests.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            }
        });

        const result = await response.json();

        if (result.success) {
            displayRequests(result.requests);
        } else {
            showMessage(result.message || 'Failed to load requests', 'error');
        }
    } catch (error) {
        console.error('Error loading requests:', error);
        showMessage('Error loading requests', 'error');
    }
}

// Display enrollment list (since there's no course_requests table, show enrolled students)
function displayRequests(requests) {
    const tbody = document.querySelector('#requests-table tbody');
    if (!tbody) return;

    tbody.innerHTML = '';

    if (requests.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" style="text-align: center;">No enrolled students</td></tr>';
        return;
    }

    requests.forEach(request => {
        const row = document.createElement('tr');
        const statusClass = `status-${request.status}`;
        row.innerHTML = `
            <td>${request.course_code}</td>
            <td>${request.course_name}</td>
            <td>${request.first_name} ${request.last_name}</td>
            <td>${request.email}</td>
            <td><span class="status-badge ${statusClass}">${request.status}</span></td>
            <td>
                <button class="reject-btn" onclick="removeStudent(${request.course_id}, ${request.student_id}, '${request.first_name} ${request.last_name}')">Remove</button>
            </td>
        `;
        tbody.appendChild(row);
    });
}

// Remove student from course (since there's no course_requests table)
async function removeStudent(courseId, studentId, studentName) {
    if (!confirm(`Are you sure you want to remove ${studentName} from this course?`)) {
        return;
    }

    try {
        const response = await fetch(`${API_BASE}manage_request.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                course_id: courseId,
                student_id: studentId,
                action: 'remove'
            })
        });

        const result = await response.json();

        if (result.success) {
            showMessage(result.message, 'success');
            loadRequests();
            loadCourses(); // Refresh courses to update enrolled count
        } else {
            showMessage(result.message || 'Failed to remove student', 'error');
        }
    } catch (error) {
        console.error('Error removing student:', error);
        showMessage('Error removing student', 'error');
    }
}


// Close modal when clicking outside
window.onclick = function(event) {
    const modals = document.querySelectorAll('.modal');
    modals.forEach(modal => {
        if (event.target === modal) {
            modal.style.display = 'none';
        }
    });
}

// Placeholder functions for edit/delete
function editCourse(courseId) {
    showMessage('Edit functionality coming soon', 'error');
}

function deleteCourse(courseId) {
    if (confirm('Are you sure you want to delete this course?')) {
        showMessage('Delete functionality coming soon', 'error');
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

// Session Management Functions
let allCourses = [];

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
        tbody.innerHTML = '<tr><td colspan="5" style="text-align: center;">No sessions found. Create your first session!</td></tr>';
        return;
    }

    sessions.forEach(session => {
        const row = document.createElement('tr');
        const sessionDate = new Date(session.session_date + 'T' + session.start_time);
        const timeRange = session.end_time ? 
            `${new Date(session.session_date + 'T' + session.start_time).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})} - ${new Date(session.session_date + 'T' + session.end_time).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}` :
            new Date(session.session_date + 'T' + session.start_time).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
        row.innerHTML = `
            <td>${session.course_code} - ${session.course_name}</td>
            <td>${sessionDate.toLocaleDateString()}</td>
            <td>${timeRange}</td>
            <td><strong style="color: var(--accent-color);">${session.session_id}</strong><br><small>Share this ID with students</small></td>
            <td>
                <button class="primary-btn" onclick="viewSessionAttendance(${session.session_id})" style="margin-top: 0.5rem;">View Attendance (${session.attendance_count || 0})</button>
            </td>
        `;
        tbody.appendChild(row);
    });
}

// Create session
async function createSession(event) {
    event.preventDefault();

    const courseId = document.getElementById('session-course').value;
    const date = document.getElementById('session-date').value;
    const startTime = document.getElementById('session-start-time').value;
    const endTime = document.getElementById('session-end-time').value || startTime;

    if (!courseId || !date || !startTime) {
        showMessage('Course, date, and start time are required', 'error');
        return;
    }

    try {
        const payload = {
            course_id: parseInt(courseId),
            date: date,
            start_time: startTime,
            end_time: endTime
        };
        
        const response = await fetch(`${API_BASE}create_session.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify(payload)
        });

        const result = await response.json();

        if (result.success) {
            showMessage('Session created successfully!', 'success');
            document.getElementById('create-session-form').reset();
            loadSessions();
            loadAttendanceSessions(); // Refresh session dropdown
            closeModal('create-session-modal');
        } else {
            showMessage(result.message || 'Failed to create session', 'error');
        }
    } catch (error) {
        console.error('Error creating session:', error);
        showMessage('Error creating session', 'error');
    }
}


// Open create session modal - make it globally accessible
window.openCreateSessionModal = function() {
    try {
        populateCourseDropdowns();
        const modal = document.getElementById('create-session-modal');
        if (modal) {
            modal.style.display = 'block';
        } else {
            console.error('Modal not found: create-session-modal');
        }
    } catch (error) {
        console.error('Error opening session modal:', error);
    }
};

// Populate course dropdowns
function populateCourseDropdowns() {
    const sessionCourseSelect = document.getElementById('session-course');
    const sessionFilterSelect = document.getElementById('session-course-filter');
    const attendanceSessionSelect = document.getElementById('attendance-session-select');

    if (sessionCourseSelect) {
        sessionCourseSelect.innerHTML = '<option value="">-- Select a course --</option>';
    }
    if (sessionFilterSelect) {
        sessionFilterSelect.innerHTML = '<option value="">All Courses</option>';
    }

    allCourses.forEach(course => {
        if (sessionCourseSelect) {
            const option = document.createElement('option');
            option.value = course.course_id;
            option.textContent = `${course.course_code} - ${course.course_name}`;
            sessionCourseSelect.appendChild(option);
        }
        if (sessionFilterSelect) {
            const option = document.createElement('option');
            option.value = course.course_id;
            option.textContent = `${course.course_code} - ${course.course_name}`;
            sessionFilterSelect.appendChild(option);
        }
    });
}

// Attendance Marking Functions
async function loadAttendanceSessions() {
    try {
        const response = await fetch(`${API_BASE}get_sessions.php`, {
            method: 'GET',
            headers: {
                'Accept': 'application/json'
            }
        });

        const result = await response.json();

        if (result.success) {
            const select = document.getElementById('attendance-session-select');
            if (select) {
                select.innerHTML = '<option value="">-- Select a session --</option>';
                result.sessions.forEach(session => {
                    const option = document.createElement('option');
                    option.value = session.session_id;
                    const sessionDate = new Date(session.session_date + 'T' + session.start_time);
                    const timeRange = session.end_time ? 
                        `${new Date(session.session_date + 'T' + session.start_time).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})} - ${new Date(session.session_date + 'T' + session.end_time).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}` :
                        new Date(session.session_date + 'T' + session.start_time).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
                    option.textContent = `${session.course_code} - ${sessionDate.toLocaleDateString()} ${timeRange}`;
                    select.appendChild(option);
                });
            }
        }
    } catch (error) {
        console.error('Error loading attendance sessions:', error);
    }
}

// View session attendance
async function viewSessionAttendance(sessionId) {
    // Navigate to attendance marking section
    document.getElementById('attendance-marking').scrollIntoView({ behavior: 'smooth' });
    
    // Set the session in the dropdown and load attendance
    const select = document.getElementById('attendance-session-select');
    if (select) {
        // First, make sure the dropdown is populated
        await loadAttendanceSessions();
        
        // Then set the value
        select.value = sessionId;
        
        // Load attendance for this session
        await loadAttendanceForSession();
    }
}

// Load attendance for selected session
async function loadAttendanceForSession() {
    const sessionId = document.getElementById('attendance-session-select').value;

    if (!sessionId) {
        document.getElementById('attendance-display').style.display = 'none';
        return;
    }

    try {
        const response = await fetch(`${API_BASE}get_attendance.php?session_id=${sessionId}`, {
            method: 'GET',
            headers: {
                'Accept': 'application/json'
            }
        });

        const result = await response.json();

        if (result.success) {
            document.getElementById('attendance-display').style.display = 'block';
            displayAttendance(result.attendance, sessionId);
        } else {
            showMessage(result.message || 'Failed to load attendance', 'error');
        }
    } catch (error) {
        console.error('Error loading attendance:', error);
        showMessage('Error loading attendance', 'error');
    }
}

// Display attendance in table
function displayAttendance(attendance, sessionId) {
    const tbody = document.querySelector('#attendance-table tbody');
    if (!tbody) return;

    tbody.innerHTML = '';

    if (attendance.length === 0) {
        tbody.innerHTML = '<tr><td colspan="4" style="text-align: center;">No students enrolled</td></tr>';
        return;
    }

        attendance.forEach(record => {
        const row = document.createElement('tr');
        const statusClass = `status-${record.status}`;
        const checkInTime = record.check_in_time ? record.check_in_time : 'Not marked';
        row.innerHTML = `
            <td>${record.first_name} ${record.last_name}</td>
            <td>${record.email}</td>
            <td><span class="status-badge ${statusClass}">${record.status}</span></td>
            <td>
                ${record.attendance_id ? 
                    `<span style="color: #666;">Checked in: ${checkInTime}</span>
                     <button class="edit-btn" onclick="markStudentAttendance(${sessionId}, ${record.student_id}, 'present')" style="margin-left: 0.5rem;">Update</button>` :
                    `<button class="primary-btn" onclick="markStudentAttendance(${sessionId}, ${record.student_id}, 'present')">Mark Present</button>`
                }
            </td>
        `;
        tbody.appendChild(row);
    });
}

// Mark student attendance
async function markStudentAttendance(sessionId, studentId, status = 'present') {
    try {
        const response = await fetch(`${API_BASE}mark_attendance.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                session_id: sessionId,
                student_id: studentId,
                status: status
            })
        });

        const result = await response.json();

        if (result.success) {
            showMessage('Attendance marked successfully', 'success');
            loadAttendanceForSession();
            loadSessions(); // Refresh session list to update count
        } else {
            showMessage(result.message || 'Failed to mark attendance', 'error');
        }
    } catch (error) {
        console.error('Error marking attendance:', error);
        showMessage('Error marking attendance', 'error');
    }
}

// Remove student from course
async function removeStudentFromCourse(courseId, studentId, studentName) {
    if (!confirm(`Are you sure you want to remove ${studentName} from this course?`)) {
        return;
    }

    try {
        const response = await fetch(`${API_BASE}remove_student.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                course_id: courseId,
                student_id: studentId
            })
        });

        const result = await response.json();

        if (result.success) {
            showMessage(result.message, 'success');
            loadCourses(); // Refresh courses
        } else {
            showMessage(result.message || 'Failed to remove student', 'error');
        }
    } catch (error) {
        console.error('Error removing student:', error);
        showMessage('Error removing student', 'error');
    }
}

// Make sure functions are available immediately (not just after DOMContentLoaded)
// This ensures onclick handlers work even if DOMContentLoaded hasn't fired yet

// Initialize on page load
document.addEventListener('DOMContentLoaded', () => {
    console.log('Faculty dashboard initialized');
    loadCourses().then(() => {
        // Store courses for dropdowns
        fetch(`${API_BASE}get_courses.php`, {
            method: 'GET',
            headers: {
                'Accept': 'application/json'
            }
        })
        .then(res => res.json())
        .then(result => {
            if (result.success) {
                allCourses = result.courses;
                populateCourseDropdowns();
            }
        });
    });
    loadRequests();
    loadSessions();
    loadAttendanceSessions();

    // Set up form submissions
    const createForm = document.getElementById('create-course-form');
    if (createForm) {
        createForm.addEventListener('submit', createCourse);
    }

    const createSessionForm = document.getElementById('create-session-form');
    if (createSessionForm) {
        createSessionForm.addEventListener('submit', createSession);
    }
});

