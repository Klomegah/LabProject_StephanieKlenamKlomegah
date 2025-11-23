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

// Initialize on page load
document.addEventListener('DOMContentLoaded', () => {
    loadEnrolledCourses();
    loadAvailableCourses();

    // Set up search functionality
    const searchInput = document.getElementById('course-search');
    if (searchInput) {
        searchInput.addEventListener('input', filterAndDisplayCourses);
    }
});

