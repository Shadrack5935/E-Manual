<?php
session_start();
require_once 'connection.php';

$student_id = $_SESSION['user_id'] ?? null;
$student = null;
$initials = 'ST';

if ($student_id) {
    $stmt = $pdo->prepare("SELECT student_id, first_name, last_name, email, phone, program, academic_year FROM accounts WHERE id = ? AND role = 'student'");
    $stmt->execute([$student_id]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($student) {
        $initials = strtoupper(substr($student['first_name'], 0, 1) . substr($student['last_name'], 0, 1));
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CodeLab - Student Dashboard</title>
    <link rel="stylesheet" href="students.css">
</head>
<body>
    <!-- Header -->
    <div class="header">
        <div class="header-content">
            <div style="display: flex; align-items: center; gap: 1rem;">
                <div class="hamburger" onclick="toggleSidebar()">
                    <span></span>
                    <span></span>
                    <span></span>
                </div>
                <div class="logo">CodeLab Student</div>
            </div>
            <div class="user-menu">
                <span>Welcome back, <?= htmlspecialchars($student['first_name'] ?? 'Student') ?></span>
                <div class="user-avatar"><?= htmlspecialchars($initials) ?></div>
                <button onclick="logout()" class="logout-btn">Logout</button>
            </div>
        </div>
    </div>

    <!-- Sidebar Overlay -->
    <div class="overlay" onclick="closeSidebar()"></div>

    <!-- Main Container -->
    <div class="main-container">
        <!-- Sidebar -->
        <nav class="sidebar" id="sidebar">
            <ul class="sidebar-menu">
                <li class="sidebar-item">
                    <a href="#" class="sidebar-link active" onclick="showPage(event, 'dashboard')">
                        <span class="sidebar-icon">📊</span>
                        Dashboard
                    </a>
                </li>
                <li class="sidebar-item">
                    <a href="#" class="sidebar-link" onclick="showPage(event, 'registration')">
                        <span class="sidebar-icon">➕</span>
                        Course Registration
                    </a>
                </li>
                <li class="sidebar-item">
                    <a href="#" class="sidebar-link" onclick="showPage(event, 'courses')">
                        <span class="sidebar-icon">📚</span>
                        My Courses
                    </a>
                </li>
                <li class="sidebar-item">
                    <a href="#" class="sidebar-link" onclick="showPage(event, 'tasks')">
                        <span class="sidebar-icon">📝</span>
                        Tasks & Assignments
                    </a>
                </li>
                <li class="sidebar-item">
                    <a href="#" class="sidebar-link" onclick="showPage(event, 'grades')">
                        <span class="sidebar-icon">🏆</span>
                        Grades
                    </a>
                </li>
                <li class="sidebar-item">
                    <a href="#" class="sidebar-link" onclick="showPage(event, 'profile')">
                        <span class="sidebar-icon">👤</span>
                        Profile
                    </a>
                </li>
            </ul>
        </nav>

        <!-- Main Content -->
        <main class="content-area">
            <!-- Dashboard Page -->
            <div id="dashboard" class="page-section active">
                <div class="section-card">
                    <h2 class="section-title">📊 Dashboard Overview</h2>
                    
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-number" id="enrolledCount">3</div>
                            <div class="stat-label">Enrolled Courses</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number" id="pendingTasks">5</div>
                            <div class="stat-label">Pending Tasks</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number" id="avgGrade">B+</div>
                            <div class="stat-label">Average Grade</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number" id="completedTasks">12</div>
                            <div class="stat-label">Completed Tasks</div>
                        </div>
                    </div>

                    <div class="dashboard-content">
                        <div class="dashboard-left">
                            <h3>📚 My Courses</h3>
                            <div class="course-cards" id="dashboardCourses">
                                <!-- Dynamic course cards will be inserted here -->
                            </div>
                        </div>
                        
                        <div class="dashboard-right">
                            <h3>📋 Upcoming Deadlines</h3>
                            <div class="deadline-list" id="upcomingDeadlines">
                                <!-- Dynamic deadline items will be inserted here -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Course Registration Page -->
            <div id="registration" class="page-section">
                <div class="section-card">
                    <h2 class="section-title">➕ Course Registration</h2>
                    
                    <div class="registration-controls">
                        <div class="search-filter">
                            <input type="text" id="courseSearch" class="search-input" placeholder="Search courses..." onkeyup="filterCourses()">
                            <select id="semesterFilter" class="filter-select" onchange="filterCourses()">
                                <option value="">All Semesters</option>
                                <option value="First Semester">First Semester</option>
                                <option value="Second Semester">Second Semester</option>
                            </select>
                            <select id="programFilter" class="filter-select" onchange="filterCourses()">
                                <option value="">All Programs</option>
                                <option value="Computer Science">Computer Science</option>
                                <option value="Information Technology">Information Technology</option>
                                <option value="Software Engineering">Software Engineering</option>
                                <option value="Data Science">Data Science</option>
                            </select>
                        </div>
                        
                        <div class="bulk-actions">
                            <button class="btn-primary" onclick="registerSelectedCourses()">
                                Register Selected Courses (<span id="selectedCount">0</span>)
                            </button>
                            <button class="btn-secondary" onclick="clearSelection()">Clear Selection</button>
                        </div>
                    </div>

                    <div class="table-container">
                        <table class="courses-table" id="coursesTable">
                            <thead>
                                <tr>
                                    <th>
                                        <input type="checkbox" id="selectAll" onchange="toggleSelectAll()">
                                    </th>
                                    <th>Course Code</th>
                                    <th>Course Name</th>
                                    <th>Credits</th>
                                    <th>Instructor</th>
                                    <th>Schedule</th>
                                    <th>Enrolled/Capacity</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="coursesTableBody">
                                <!-- Dynamic course rows will be inserted here -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- My Courses Page -->
            <div id="courses" class="page-section">
                <div class="section-card">
                    <h2 class="section-title">📚 My Courses</h2>
                    
                    <div class="courses-grid" id="myCoursesGrid">
                        <!-- Dynamic enrolled course cards will be inserted here -->
                    </div>
                </div>
            </div>

            <!-- Tasks & Assignments Page -->
            <div id="tasks" class="page-section">
                <div class="section-card">
                    <h2 class="section-title">📝 Tasks & Assignments</h2>
                    
                    <div class="task-filters">
                        <button class="filter-btn active" onclick="filterTasks('all')">All Tasks</button>
                        <button class="filter-btn" onclick="filterTasks('pending')">Pending</button>
                        <button class="filter-btn" onclick="filterTasks('submitted')">Submitted</button>
                        <button class="filter-btn" onclick="filterTasks('graded')">Graded</button>
                    </div>

                    <div class="tasks-grid" id="tasksGrid">
                        <!-- Dynamic task cards will be inserted here -->
                    </div>
                </div>
            </div>

            <!-- Grades Page -->
            <div id="grades" class="page-section">
                <div class="section-card">
                    <h2 class="section-title">🏆 My Grades</h2>
                    
                    <div class="grades-overview">
                        <div class="grade-summary">
                            <h3>Overall Performance</h3>
                            <div class="overall-grade">B+</div>
                            <div class="grade-breakdown">
                                <div class="grade-item">
                                    <span>Total Courses:</span>
                                    <span>3</span>
                                </div>
                                <div class="grade-item">
                                    <span>Completed Tasks:</span>
                                    <span>12/17</span>
                                </div>
                                <div class="grade-item">
                                    <span>Average Score:</span>
                                    <span>82%</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="grades-detailed" id="gradesDetailed">
                        <!-- Dynamic grade cards will be inserted here -->
                    </div>
                </div>
            </div>

            <!-- Profile Page -->
            <div id="profile" class="page-section">
                <div class="section-card">
                    <h2 class="section-title">👤 My Profile</h2>
                    
                    <div class="profile-content">
                        <div class="profile-header">
                            <div class="profile-avatar-large"><?= htmlspecialchars($initials) ?></div>
                            <div class="profile-info">
                                <h3><?= htmlspecialchars(($student['first_name'] ?? '') . ' ' . ($student['last_name'] ?? '')) ?></h3>
                                <p><?= htmlspecialchars($student['program'] ?? '') ?> Student</p>
                                <p>Student ID: <?= htmlspecialchars($student['student_id'] ?? '') ?></p>
                            </div>
                            <button class="edit-profile-btn" onclick="editProfile()">Edit Profile</button>
                        </div>

                        <div class="profile-details">
                            <div class="detail-group">
                                <h4>Personal Information</h4>
                                <div class="detail-item">
                                    <span class="detail-label">Email:</span>
                                    <span class="detail-value"><?= htmlspecialchars($student['email'] ?? '') ?></span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Phone:</span>
                                    <span class="detail-value"><?= htmlspecialchars($student['phone'] ?? '') ?></span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Program:</span>
                                    <span class="detail-value"><?= htmlspecialchars($student['program'] ?? '') ?></span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Year:</span>
                                    <span class="detail-value"><?= htmlspecialchars($student['academic_year'] ?? '') ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Task Submission Modal -->
    <div id="taskModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Submit Task</h3>
                <span class="close" onclick="closeTaskModal()">&times;</span>
            </div>
            <div class="modal-body">
                <form id="taskSubmissionForm" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="submit_task" value="1">
                    <input type="hidden" name="task_id" id="modalTaskId">
                    <input type="hidden" name="course_code" id="modalCourseCode">
                    <div class="form-group">
                        <label>Task Title</label>
                        <input type="text" id="modalTaskTitle" readonly>
                    </div>
                    <div class="form-group">
                        <label>Course</label>
                        <input type="text" id="modalCourse" readonly>
                    </div>
                    <div class="form-group">
                        <label>Due Date</label>
                        <input type="text" id="modalDueDate" readonly>
                    </div>
                    <div class="form-group">
                        <label>Submission Text</label>
                        <textarea id="submissionText" name="submission_text" placeholder="Enter your submission text or notes..."></textarea>
                    </div>
                    <div class="form-group">
                        <label>Upload Files</label>
                        <input type="file" id="submissionFiles" name="submission_files[]" multiple>
                        <div class="file-list" id="fileList"></div>
                    </div>
                    <div class="form-actions">
                        <button type="button" class="btn-secondary" onclick="closeTaskModal()">Cancel</button>
                        <button type="submit" class="btn-primary">Submit Task</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
    // Global variables
    let availableCourses = [];
    let studentCourses = [];
    let tasks = [];
    let grades = [];
    let selectedCourses = new Set();
    let enrolledCourses = new Set();
    let currentTaskFilter = 'all';

    // Fetch data from the backend
    async function fetchAvailableCourses() {
        try {
            const res = await fetch('get_available_courses.php');
            const data = await res.json();
            if (data.success) {
                availableCourses = data.courses;
            } else {
                showNotification('Error loading courses: ' + data.error, 'error');
            }
        } catch (error) {
            showNotification('Error loading courses: ' + error.message, 'error');
        }
        renderCourseRegistration();
    }

    async function fetchStudentCourses() {
        try {
            const res = await fetch('get_student_courses.php');
            studentCourses = await res.json();
            enrolledCourses = new Set(studentCourses.map(c => c.id));
        } catch (error) {
            showNotification('Error loading student courses: ' + error.message, 'error');
        }
        renderMyCourses();
        renderDashboard();
    }

    async function fetchTasks() {
        try {
            const res = await fetch('get_student_tasks.php');
            tasks = await res.json();
        } catch (error) {
            showNotification('Error loading tasks: ' + error.message, 'error');
        }
        renderTasks();
        renderDashboard();
    }

    async function fetchGrades() {
        try {
            const res = await fetch('get_student_grades.php');
            grades = await res.json();
        } catch (error) {
            showNotification('Error loading grades: ' + error.message, 'error');
        }
        renderGrades();
    }

    // Initialize the dashboard
    document.addEventListener('DOMContentLoaded', async function() {
        await fetchAvailableCourses();
        await fetchStudentCourses();
        await fetchTasks();
        await fetchGrades();

        // Set up file input handler
        const fileInput = document.getElementById('submissionFiles');
        if (fileInput) {
            fileInput.addEventListener('change', handleFileSelection);
        }

        // Set up task form handler
        const taskForm = document.getElementById('taskSubmissionForm');
        if (taskForm) {
            taskForm.addEventListener('submit', handleTaskSubmission);
        }

        // Welcome message
        setTimeout(() => {
            showNotification('Welcome to your student dashboard! 🎓', 'success');
        }, 1000);
    });

    // Navigation functions
    function showPage(event, pageId) {
        if (event) {
            event.preventDefault();
        }
        
        // Hide all pages
        document.querySelectorAll('.page-section').forEach(page => {
            page.classList.remove('active');
        });
        
        // Show selected page
        document.getElementById(pageId).classList.add('active');
        
        // Update active link
        document.querySelectorAll('.sidebar-link').forEach(link => {
            link.classList.remove('active');
        });
        
        // Add active class to clicked link
        if (event && event.currentTarget) {
            event.currentTarget.classList.add('active');
        }
        
        // Close sidebar on mobile
        if (window.innerWidth <= 768) {
            closeSidebar();
        }
    }

    function toggleSidebar() {
        const sidebar = document.getElementById('sidebar');
        const overlay = document.querySelector('.overlay');
        sidebar.classList.toggle('open');
        overlay.classList.toggle('active');
    }

    function closeSidebar() {
        const sidebar = document.getElementById('sidebar');
        const overlay = document.querySelector('.overlay');
        sidebar.classList.remove('open');
        overlay.classList.remove('active');
    }

    function logout() {
        if (confirm('Are you sure you want to logout?')) {
            showNotification('Logging out...', 'info');
            setTimeout(() => {
                window.location.href = 'logout.php';
            }, 1000);
        }
    }

    // Dashboard functions
    function renderDashboard() {
        document.getElementById('enrolledCount').textContent = enrolledCourses.size;
        document.getElementById('pendingTasks').textContent = tasks.filter(t => t.status === 'pending').length;
        document.getElementById('completedTasks').textContent = tasks.filter(t => t.status === 'graded').length;

        const dashboardCourses = document.getElementById('dashboardCourses');
        dashboardCourses.innerHTML = studentCourses.map(course => `
            <div class="course-card">
                <h4>${course.name}</h4>
                <p><strong>Code:</strong> ${course.code}</p>
                <p><strong>Instructor:</strong> ${course.instructor}</p>
                <p><strong>Progress:</strong> ${course.progress}%</p>
                <div class="progress-bar">
                    <div class="progress-fill" style="width: ${course.progress}%"></div>
                </div>
                <p><strong>Grade:</strong> ${course.grade}</p>
            </div>
        `).join('');

        const upcomingDeadlines = document.getElementById('upcomingDeadlines');
        const pendingTasks = tasks.filter(t => t.status === 'pending').slice(0, 3);
        upcomingDeadlines.innerHTML = pendingTasks.map(task => `
            <div class="deadline-item">
                <h4>${task.title}</h4>
                <p><strong>Course:</strong> ${task.courseName}</p>
                <p><strong>Due:</strong> ${task.dueDate}</p>
                <p><strong>Type:</strong> ${task.type}</p>
            </div>
        `).join('');
    }

    // Course registration functions
    function renderCourseRegistration() {
        const tbody = document.getElementById('coursesTableBody');
        tbody.innerHTML = availableCourses.map(course => {
            const isEnrolled = course.is_enrolled;
            const isFull = course.enrolled >= course.capacity;
            const status = isEnrolled ? 'enrolled' : (isFull ? 'full' : 'available');
            return `
                <tr>
                    <td>
                        <input type="checkbox" 
                               value="${course.id}" 
                               onchange="handleCourseSelection('${course.id}')"
                               ${isEnrolled ? 'disabled' : ''}
                               ${isFull ? 'disabled' : ''}>
                    </td>
                    <td><strong>${course.code}</strong></td>
                    <td>${course.name}</td>
                    <td>${course.credits}</td>
                    <td>${course.instructor}</td>
                    <td>${course.schedule}</td>
                    <td>${course.enrolled}/${course.capacity}</td>
                    <td>
                        <span class="status-badge status-${status}">
                            ${status.charAt(0).toUpperCase() + status.slice(1)}
                        </span>
                    </td>
                    <td>
                        <button class="btn-secondary" onclick="viewCourseDetails('${course.id}')">
                            View Details
                        </button>
                    </td>
                </tr>
            `;
        }).join('');
    }

    function handleCourseSelection(courseId) {
        const checkbox = document.querySelector(`input[value="${courseId}"]`);
        if (checkbox.checked) {
            selectedCourses.add(courseId);
        } else {
            selectedCourses.delete(courseId);
        }
        updateSelectedCount();
    }

    function toggleSelectAll() {
        const selectAllCheckbox = document.getElementById('selectAll');
        const courseCheckboxes = document.querySelectorAll('#coursesTableBody input[type="checkbox"]:not(:disabled)');
        courseCheckboxes.forEach(checkbox => {
            checkbox.checked = selectAllCheckbox.checked;
            handleCourseSelection(checkbox.value);
        });
    }

    function updateSelectedCount() {
        document.getElementById('selectedCount').textContent = selectedCourses.size;
    }

    function registerSelectedCourses() {
        if (selectedCourses.size === 0) {
            showNotification('Please select at least one course to register.', 'error');
            return;
        }
        // Send selected courses to backend for registration
        fetch('register_courses.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ courses: Array.from(selectedCourses) })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                showNotification(`Successfully registered for ${selectedCourses.size} courses!`, 'success');
                selectedCourses.clear();
                document.getElementById('selectAll').checked = false;
                updateSelectedCount();
                fetchStudentCourses();
                renderCourseRegistration();
                renderDashboard();
            } else {
                showNotification(data.message || 'Registration failed.', 'error');
            }
        })
        .catch(error => {
            showNotification('Error submitting task: ' + error.message, 'error');
        });
    }

    function clearSelection() {
        selectedCourses.clear();
        document.getElementById('selectAll').checked = false;
        document.querySelectorAll('#coursesTableBody input[type="checkbox"]').forEach(checkbox => {
            checkbox.checked = false;
        });
        updateSelectedCount();
    }

    function filterCourses() {
        const searchTerm = document.getElementById('courseSearch').value.toLowerCase();
        const semesterFilter = document.getElementById('semesterFilter').value;
        const programFilter = document.getElementById('programFilter').value;
        const rows = document.querySelectorAll('#coursesTableBody tr');
        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            const matchesSearch = searchTerm === '' || text.includes(searchTerm);
            const matchesSemester = semesterFilter === '' || text.includes(semesterFilter);
            const matchesProgram = programFilter === '' || text.includes(programFilter);
            row.style.display = matchesSearch && matchesSemester && matchesProgram ? '' : 'none';
        });
    }

    function viewCourseDetails(courseId) {
        const course = availableCourses.find(c => c.id === courseId);
        if (course) {
            const details = `Course Details:

Code: ${course.code}
Name: ${course.name}
Instructor: ${course.instructor}
Credits: ${course.credits}
Program: ${course.program}
Semester: ${course.semester}
Schedule: ${course.schedule}
Description: ${course.description}
Prerequisites: ${course.prerequisites}
Enrolled: ${course.enrolled}/${course.capacity}`;
            alert(details);
        }
    }

    // My courses functions
    function renderMyCourses() {
        const coursesGrid = document.getElementById('myCoursesGrid');
        coursesGrid.innerHTML = studentCourses.map(course => `
            <div class="enrolled-course-card">
                <div class="course-header">
                    <div>
                        <h3>${course.name}</h3>
                        <p><strong>Instructor:</strong> ${course.instructor}</p>
                    </div>
                    <span class="course-code">${course.code}</span>
                </div>
                <div class="course-details">
                    <p><strong>Progress:</strong> ${course.progress}%</p>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: ${course.progress}%"></div>
                    </div>
                    <p><strong>Current Grade:</strong> ${course.grade}</p>
                    <p><strong>Tasks Completed:</strong> ${course.tasksCompleted}/${course.totalTasks}</p>
                    <p><strong>Next Deadline:</strong> ${course.nextDeadline}</p>
                </div>
                <div class="course-actions">
                    <button class="btn-primary" onclick="viewCourseTasks('${course.id}')">
                        View Tasks
                    </button>
                    <button class="btn-secondary" onclick="viewCourseGrades('${course.id}')">
                        View Grades
                    </button>
                </div>
            </div>
        `).join('');
    }

    function viewCourseTasks(courseId) {
        showPage(event, 'tasks');
        const course = studentCourses.find(c => c.id === courseId);
        const courseTasks = course ? tasks.filter(task => task.courseCode === course.code) : [];
        renderFilteredTasks(courseTasks);
    }

    function viewCourseGrades(courseId) {
        showPage(event, 'grades');
        const course = studentCourses.find(c => c.id === courseId);
        if (course) {
            setTimeout(() => {
                const gradeCard = document.querySelector(`[data-course-code="${course.code}"]`);
                if (gradeCard) {
                    gradeCard.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            }, 100);
        }
    }

    // Tasks functions
    function renderTasks() {
        renderFilteredTasks(tasks);
    }

    function renderFilteredTasks(taskList) {
        const tasksGrid = document.getElementById('tasksGrid');
        tasksGrid.innerHTML = taskList.map(task => `
            <div class="task-card ${task.status}">
                <div class="task-header">
                    <h3>${task.title}</h3>
                    <span class="task-status ${task.status}">
                        ${getStatusIcon(task.status)}
                        ${task.status.charAt(0).toUpperCase() + task.status.slice(1)}
                    </span>
                </div>
                <div class="task-details">
                    <p><strong>Course:</strong> ${task.courseName}</p>
                    <p><strong>Type:</strong> ${task.type}</p>
                    <p><strong>Due Date:</strong> ${task.dueDate}</p>
                    <p><strong>Max Marks:</strong> ${task.maxMarks}</p>
                    ${task.grade ? `<p><strong>Grade:</strong> ${task.grade}/${task.maxMarks}</p>` : ''}
                    <p><strong>Description:</strong> ${task.description}</p>
                </div>
                <div class="task-actions">
                    ${getTaskActions(task)}
                </div>
            </div>
        `).join('');
    }

    function getStatusIcon(status) {
        const icons = {
            'pending': '⏰',
            'submitted': '✅',
            'graded': '🏆'
        };
        return icons[status] || '📄';
    }

    function getTaskActions(task) {
        switch (task.status) {
            case 'pending':
                return `<button class="btn-primary" onclick="submitTask('${task.id}')">📤 Submit Task</button>`;
            case 'submitted':
                return `<button class="btn-secondary" onclick="viewSubmission('${task.id}')">👁️ View Submission</button>`;
            case 'graded':
                return `
                    <button class="btn-secondary" onclick="viewFeedback('${task.id}')">📋 View Feedback</button>
                    <button class="btn-secondary" onclick="downloadGrade('${task.id}')">📥 Download Grade</button>
                `;
            default:
                return '';
        }
    }

    function filterTasks(status) {
        currentTaskFilter = status;
        document.querySelectorAll('.filter-btn').forEach(btn => {
            btn.classList.remove('active');
        });
        event.target.classList.add('active');
        const filteredTasks = status === 'all' ? tasks : tasks.filter(task => task.status === status);
        renderFilteredTasks(filteredTasks);
    }

    function submitTask(taskId) {
        const task = tasks.find(t => t.id === taskId);
        if (task) {
            openTaskModal(task);
        }
    }

    function viewSubmission(taskId) {
        const task = tasks.find(t => t.id === taskId);
        if (task) {
            showNotification(`Viewing submission for "${task.title}"`, 'info');
        }
    }

    function viewFeedback(taskId) {
        const task = tasks.find(t => t.id === taskId);
        if (task && task.feedback) {
            alert(`Feedback for "${task.title}":\n\n${task.feedback}`);
        } else {
            showNotification('No feedback available yet.', 'info');
        }
    }

    function downloadGrade(taskId) {
        const task = tasks.find(t => t.id === taskId);
        if (task) {
            showNotification(`Downloading grade report for "${task.title}"`, 'success');
        }
    }

    // Task modal functions
    function openTaskModal(task) {
        document.getElementById('modalTaskId').value = task.id;
        document.getElementById('modalCourseCode').value = task.courseCode;
        document.getElementById('modalTaskTitle').value = task.title;
        document.getElementById('modalCourse').value = task.courseName;
        document.getElementById('modalDueDate').value = task.dueDate;
        document.getElementById('taskModal').style.display = 'block';
    }

    function closeTaskModal() {
        document.getElementById('taskModal').style.display = 'none';
        document.getElementById('taskSubmissionForm').reset();
        document.getElementById('fileList').innerHTML = '';
    }

    function handleFileSelection(event) {
        const files = event.target.files;
        const fileList = document.getElementById('fileList');
        fileList.innerHTML = '';
        Array.from(files).forEach(file => {
            const fileItem = document.createElement('div');
            fileItem.className = 'file-item';
            fileItem.innerHTML = `
                <div class="file-info">
                    <span>📄</span>
                    <span class="file-name">${file.name}</span>
                    <span class="file-size">(${(file.size / 1024).toFixed(1)} KB)</span>
                </div>
                <button type="button" class="file-remove" onclick="removeFile(this)">Remove</button>
            `;
            fileList.appendChild(fileItem);
        });
    }

    function removeFile(button) {
        const fileItem = button.closest('.file-item');
        if (fileItem) {
            fileItem.remove();
        }
    }

    function handleTaskSubmission(event) {
        event.preventDefault();
        const formData = new FormData(event.target);
        
        fetch('submit_task.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('Task submitted successfully!', 'success');
                closeTaskModal();
                fetchTasks();
            } else {
                showNotification('Error submitting task: ' + data.message, 'error');
            }
        })
        .catch(error => {
            showNotification('Error submitting task: ' + error.message, 'error');
        });
    }

    function showNotification(message, type) {
        const notification = document.createElement('div');
        notification.className = `notification ${type}`;
        notification.style.position = 'fixed';
        notification.style.bottom = '20px';
        notification.style.right = '20px';
        notification.style.padding = '1rem 1.5rem';
        notification.style.borderRadius = '8px';
        notification.style.boxShadow = '0 4px 12px rgba(0,0,0,0.15)';
        notification.style.zIndex = '1000';
        notification.style.animation = 'slideIn 0.3s ease';
        
        notification.textContent = message;
        document.body.appendChild(notification);
        setTimeout(() => {
            if (notification.parentNode) {
                notification.style.animation = 'slideIn 0.3s ease reverse';
                setTimeout(() => notification.remove(), 300);
            }
        }, 3000);
    }

    // Close modal when clicking outside
    window.onclick = function(event) {
        const modal = document.getElementById('taskModal');
        if (event.target === modal || event.target.classList.contains('modal')) {
            closeTaskModal();
        }
    }

    // Close sidebar when clicking outside on mobile
    document.addEventListener('click', function(e) {
        const sidebar = document.getElementById('sidebar');
        const hamburger = document.querySelector('.hamburger');
        if (window.innerWidth <= 768 && 
            !sidebar.contains(e.target) && 
            !hamburger.contains(e.target) &&
            sidebar.classList.contains('open')) {
            closeSidebar();
        }
    });

    // Handle responsive sidebar
    window.addEventListener('resize', function() {
        if (window.innerWidth > 768) {
            closeSidebar();
        }
    });
    </script>
</body>
</html>