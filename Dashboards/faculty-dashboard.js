// Faculty Dashboard JavaScript

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

// Load courses
async function loadCourses() {
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
            displayCourses(result.courses);
        } else {
            showMessage(result.message || 'Failed to load courses', 'error');
        }
    } catch (error) {
        console.error('Error loading courses:', error);
        showMessage('Error loading courses', 'error');
    }
}

// Display courses in table
function displayCourses(courses) {
    const tbody = document.querySelector('#courses-table tbody');
    if (!tbody) return;

    tbody.innerHTML = '';

    if (courses.length === 0) {
        tbody.innerHTML = '<tr><td colspan="4" style="text-align: center;">No courses found. Create your first course!</td></tr>';
        return;
    }

    courses.forEach(course => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${course.course_code}</td>
            <td>${course.course_name}</td>
            <td>${course.enrolled_students || 0}</td>
            <td>
                <span class="status-badge ${course.pending_requests > 0 ? 'status-pending' : ''}">
                    ${course.pending_requests || 0} pending
                </span>
            </td>
            <td>
                <button class="edit-btn" onclick="editCourse(${course.course_id})">Edit</button>
                <button class="delete-btn" onclick="deleteCourse(${course.course_id})">Delete</button>
            </td>
        `;
        tbody.appendChild(row);
    });
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
            loadCourses();
            // Close modal if open
            const modal = document.getElementById('create-course-modal');
            if (modal) modal.style.display = 'none';
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

// Display requests in table
function displayRequests(requests) {
    const tbody = document.querySelector('#requests-table tbody');
    if (!tbody) return;

    tbody.innerHTML = '';

    if (requests.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" style="text-align: center;">No enrollment requests</td></tr>';
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
                ${request.status === 'pending' ? `
                    <button class="approve-btn" onclick="manageRequest(${request.request_id}, 'approve')">Approve</button>
                    <button class="reject-btn" onclick="manageRequest(${request.request_id}, 'reject')">Reject</button>
                ` : `
                    <span>${new Date(request.reviewed_at).toLocaleDateString()}</span>
                `}
            </td>
        `;
        tbody.appendChild(row);
    });
}

// Manage request (approve/reject)
async function manageRequest(requestId, action) {
    if (!confirm(`Are you sure you want to ${action} this request?`)) {
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
                request_id: requestId,
                action: action
            })
        });

        const result = await response.json();

        if (result.success) {
            showMessage(result.message, 'success');
            loadRequests();
            loadCourses(); // Refresh courses to update pending count
        } else {
            showMessage(result.message || 'Failed to process request', 'error');
        }
    } catch (error) {
        console.error('Error managing request:', error);
        showMessage('Error processing request', 'error');
    }
}

// Modal functions
function openCreateCourseModal() {
    const modal = document.getElementById('create-course-modal');
    if (modal) modal.style.display = 'block';
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) modal.style.display = 'none';
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

// Initialize on page load
document.addEventListener('DOMContentLoaded', () => {
    loadCourses();
    loadRequests();

    // Set up form submission
    const createForm = document.getElementById('create-course-form');
    if (createForm) {
        createForm.addEventListener('submit', createCourse);
    }
});

