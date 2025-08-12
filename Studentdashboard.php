<?php
session_start();
require_once 'connection.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'student') {
    header("Location: account.php");
    exit();
}

$student_id = $_SESSION['user_id'];
$student = null;
$initials = 'ST';

// Get student data from both accounts and students tables
$stmt = $pdo->prepare("
    SELECT a.accounts_id, a.sur_name, a.other_name, a.email, a.phone, 
           s.program, s.level, s.academic_year,s.semester
    FROM accounts a
    JOIN students s ON a.accounts_id = s.accounts_id
    WHERE a.accounts_id = ? AND a.role = 'student'
");
$stmt->execute([$student_id]);
$student = $stmt->fetch(PDO::FETCH_ASSOC);

if ($student) {
    $initials = strtoupper(substr($student['sur_name'], 0, 1) . substr($student['other_name'], 0, 1));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CodeLab - Student Dashboard</title>
    <link rel="stylesheet" href="students.css">
    <style>
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        #loadingOverlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }
        
        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px;
            border-radius: 5px;
            color: white;
            z-index: 1001;
            transition: opacity 0.3s ease;
        }
        
        .notification.success {
            background-color: #4CAF50;
        }
        
        .notification.error {
            background-color: #f44336;
        }
        
        .notification.info {
            background-color: #2196F3;
        }
    </style>
</head>
<body>
    <!-- Loading Overlay -->
    <div id="loadingOverlay">
        <div class="spinner"></div>
    </div>

    <!-- Header -->
    <div class="header">
        <div class="header-content">
            <div class="header-left">
                <div class="hamburger">
                    <span></span>
                    <span></span>
                    <span></span>
                </div>
                <div class="logo">CodeLab Student</div>
            </div>
            <div class="user-menu">
                <span class="welcome-message">Welcome back, <?= htmlspecialchars($student['other_name'] ?? 'Student') ?></span>
                <div class="user-avatar"><?= htmlspecialchars($initials) ?></div>
                <button class="logout-btn">Logout</button>
            </div>
        </div>
    </div>

    <!-- Sidebar Overlay -->
    <div class="overlay"></div>

    <!-- Main Container -->
    <div class="main-container">
        <!-- Sidebar -->
        <nav class="sidebar" id="sidebar">
            <ul class="sidebar-menu">
                <li class="sidebar-item">
                    <a href="#" class="sidebar-link active" data-page="dashboard">
                        <span class="sidebar-icon">📊</span>
                        Dashboard
                    </a>
                </li>
                <li class="sidebar-item">
                    <a href="#" class="sidebar-link" data-page="registration">
                        <span class="sidebar-icon">➕</span>
                        Course Registration
                    </a>
                </li>
                <li class="sidebar-item">
                    <a href="#" class="sidebar-link" data-page="courses">
                        <span class="sidebar-icon">📚</span>
                        My Courses
                    </a>
                </li>
                <li class="sidebar-item">
                    <a href="#" class="sidebar-link" data-page="tasks">
                        <span class="sidebar-icon">📝</span>
                        Tasks & Assignments
                    </a>
                </li>
                <li class="sidebar-item">
                    <a href="#" class="sidebar-link" data-page="grades">
                        <span class="sidebar-icon">🏆</span>
                        Grades
                    </a>
                </li>
                <li class="sidebar-item">
                    <a href="#" class="sidebar-link" data-page="profile">
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
                            <div class="stat-number" id="enrolledCount">0</div>
                            <div class="stat-label">Enrolled Courses</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number" id="pendingTasks">0</div>
                            <div class="stat-label">Pending Tasks</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number" id="avgGrade">N/A</div>
                            <div class="stat-label">Average Grade</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number" id="completedTasks">0</div>
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
                    
                    <div class="registration-info">
                        <div class="info-item">
                            <strong>Your Program:</strong> 
                            <span id="studentProgram"><?= htmlspecialchars($student['program'] ?? 'N/A') ?></span>
                        </div>
                        <div class="info-item">
                            <strong>Current Semester:</strong> 
                            <span id="studentSemester"><?= htmlspecialchars($student['semester'] ?? 'N/A') ?></span>
                        </div>
                        <div class="info-item">
                            <strong>Current Level:</strong> 
                            <span id="studentSemester"><?= htmlspecialchars($student['level'] ?? 'N/A') ?></span>
                        </div>
                    </div>
                    
                    <div class="search-filter">
                        <input type="text" id="courseSearch" class="search-input" 
                               placeholder="Search courses by name or code...">
                    </div>

                    <div class="table-container">
                        <table class="courses-table" id="coursesTable">
                            <thead>
                                <tr>
                                    <th>
                                        <input type="checkbox" id="selectAll">
                                    </th>
                                    <th>Course Code</th>
                                    <th>Course Name</th>
                                    <th>Credits</th>
                                    <th>Instructor</th>
                                    <th>Schedule</th>
                                    <th>Enrolled</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="coursesTableBody">
                                <!-- Dynamic course rows will be inserted here -->
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="bulk-actions">
                        <button class="btn-primary" id="registerCoursesBtn">
                            Register Selected Courses (<span id="selectedCount">0</span>)
                        </button>
                        <button class="btn-secondary" id="clearSelectionBtn">Clear Selection</button>
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
                        <button class="filter-btn active" data-filter="all">All Tasks</button>
                        <button class="filter-btn" data-filter="pending">Pending</button>
                        <button class="filter-btn" data-filter="submitted">Submitted</button>
                        <button class="filter-btn" data-filter="graded">Graded</button>
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
                            <div class="overall-grade" id="overallGrade">N/A</div>
                            <div class="grade-breakdown">
                                <div class="grade-item">
                                    <span>Total Courses:</span>
                                    <span id="totalCourses">0</span>
                                </div>
                                <div class="grade-item">
                                    <span>Completed Tasks:</span>
                                    <span id="completedTasksCount">0/0</span>
                                </div>
                                <div class="grade-item">
                                    <span>Average Score:</span>
                                    <span id="averageScore">0%</span>
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
                                <h3><?= htmlspecialchars(($student['sur_name'] ?? '') . ' ' . ($student['other_name'] ?? '')) ?></h3>
                                <p><?= htmlspecialchars($student['program'] ?? '') ?> Student</p>
                                <p>Student ID: <?= htmlspecialchars($student['accounts_id'] ?? '') ?></p>
                            </div>
                            <button class="edit-profile-btn" id="editProfileBtn">Edit Profile</button>
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
                                    <span class="detail-label">Level:</span>
                                    <span class="detail-value"><?= htmlspecialchars($student['level'] ?? '') ?></span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Academic Year:</span>
                                    <span class="detail-value"><?= htmlspecialchars($student['academic_year'] ?? '') ?></span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Semester:</span>
                                    <span class="detail-value"><?= htmlspecialchars($student['semester'] ?? '') ?></span>
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
                <span class="close">&times;</span>
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
                        <button type="button" class="btn-secondary" id="cancelTaskBtn">Cancel</button>
                        <button type="submit" class="btn-primary">Submit Task</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <script>
        // Student Dashboard - Complete JavaScript Implementation
    document.addEventListener('DOMContentLoaded', function() {
            // Global variables
            let availableCourses = [];
            let studentCourses = [];
            let tasks = [];
            let grades = [];
            let selectedCourses = new Set();
            let enrolledCourses = new Set();
            let currentTaskFilter = 'all';
            let currentStudentId = '<?= $student_id ?>';

            // DOM Elements
            const sidebar = document.getElementById('sidebar');
            const overlay = document.querySelector('.overlay');
            const hamburger = document.querySelector('.hamburger');
            const courseSearch = document.getElementById('courseSearch');
            const selectAllCheckbox = document.getElementById('selectAll');
            const selectedCountSpan = document.getElementById('selectedCount');
            const coursesTableBody = document.getElementById('coursesTableBody');
            const myCoursesGrid = document.getElementById('myCoursesGrid');
            const tasksGrid = document.getElementById('tasksGrid');
            const dashboardCourses = document.getElementById('dashboardCourses');
            const upcomingDeadlines = document.getElementById('upcomingDeadlines');
            const enrolledCount = document.getElementById('enrolledCount');
            const pendingTasks = document.getElementById('pendingTasks');
            const completedTasks = document.getElementById('completedTasks');
            const avgGrade = document.getElementById('avgGrade');
            const taskModal = document.getElementById('taskModal');
            const taskSubmissionForm = document.getElementById('taskSubmissionForm');
            const logoutBtn = document.querySelector('.logout-btn');
            const registerCoursesBtn = document.getElementById('registerCoursesBtn');
            const clearSelectionBtn = document.getElementById('clearSelectionBtn');

            // Initialize the dashboard
            initDashboard();

            // Event Listeners
            if (hamburger) hamburger.addEventListener('click', toggleSidebar);
            if (overlay) overlay.addEventListener('click', closeSidebar);
            if (courseSearch) courseSearch.addEventListener('input', filterCourses);
            if (selectAllCheckbox) selectAllCheckbox.addEventListener('change', toggleSelectAll);
            if (logoutBtn) logoutBtn.addEventListener('click', logout);
            if (registerCoursesBtn) registerCoursesBtn.addEventListener('click', registerSelectedCourses);
            if (clearSelectionBtn) clearSelectionBtn.addEventListener('click', clearSelection);
            
            window.addEventListener('resize', handleResponsiveLayout);

            // Sidebar navigation
            document.querySelectorAll('.sidebar-link').forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    const pageId = this.getAttribute('data-page');
                    showPage(this, pageId);
                });
            });

            // Task filter buttons
            document.querySelectorAll('.filter-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
                    this.classList.add('active');
                    currentTaskFilter = this.dataset.filter;
                    renderTasks();
                });
            });

            // Initialize the dashboard
            async function initDashboard() {
                showLoading(true);
                try {
                    await Promise.all([
                        fetchAvailableCourses(),
                        fetchStudentCourses(),
                        fetchTasks(),
                        fetchGrades()
                    ]);
                    renderDashboard();
                    showNotification('Dashboard loaded successfully!', 'success');
                } catch (error) {
                    console.error('Initialization error:', error);
                    showNotification('Error loading dashboard data: ' + error.message, 'error');
                } finally {
                    showLoading(false);
                }
            }

            // Data Fetching Functions
            async function fetchAvailableCourses() {
                try {
                    const response = await fetch(`get_available_courses.php?accounts_id=${currentStudentId}`);
                    if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
                    
                    const data = await response.json();
                    
                    if (data.success) {
                        availableCourses = data.courses;
                        renderCourseRegistration();
                        return true;
                    } else {
                        throw new Error(data.message || 'Failed to load courses');
                    }
                } catch (error) {
                    console.error('Error fetching courses:', error);
                    showNotification('Error loading available courses: ' + error.message, 'error');
                    return false;
                }
            }

            async function fetchStudentCourses() {
                try {
                    const response = await fetch(`get_student_courses.php?accounts_id=${currentStudentId}`);
                    if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
                    
                    const data = await response.json();
                    
                    if (data.success) {
                        studentCourses = data.courses;
                        enrolledCourses = new Set(studentCourses.map(c => c.id));
                        renderMyCourses();
                        return true;
                    } else {
                        throw new Error(data.message || 'Failed to load student courses');
                    }
                } catch (error) {
                    console.error('Error fetching student courses:', error);
                    showNotification('Error loading your courses: ' + error.message, 'error');
                    return false;
                }
            }

            async function fetchTasks() {
                try {
                    const response = await fetch(`get_student_tasks.php?accounts_id=${currentStudentId}`);
                    if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
                    
                    const data = await response.json();
                    
                    if (data.success) {
                        tasks = data.tasks;
                        renderTasks();
                        return true;
                    } else {
                        throw new Error(data.message || 'Failed to load tasks');
                    }
                } catch (error) {
                    console.error('Error fetching tasks:', error);
                    showNotification('Error loading tasks: ' + error.message, 'error');
                    return false;
                }
            }

            async function fetchGrades() {
                try {
                    const response = await fetch(`get_student_grades.php?accounts_id=${currentStudentId}`);
                    if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
                    
                    const data = await response.json();
                    
                    if (data.success) {
                        grades = data.grades;
                        renderGrades();
                        updateGradeStats();
                        return true;
                    } else {
                        throw new Error(data.message || 'Failed to load grades');
                    }
                } catch (error) {
                    console.error('Error fetching grades:', error);
                    showNotification('Error loading grades: ' + error.message, 'error');
                    return false;
                }
            }

            // Page Navigation
            function showPage(clickedElement, pageId) {
                // Hide all pages
                document.querySelectorAll('.page-section').forEach(page => {
                    page.classList.remove('active');
                });
                
                // Show selected page
                const targetPage = document.getElementById(pageId);
                if (targetPage) {
                    targetPage.classList.add('active');
                }
                
                // Update active link
                document.querySelectorAll('.sidebar-link').forEach(link => {
                    link.classList.remove('active');
                });
                
                // Add active class to clicked link
                if (clickedElement) {
                    clickedElement.classList.add('active');
                } else {
                    const activeLink = document.querySelector(`.sidebar-link[data-page="${pageId}"]`);
                    if (activeLink) activeLink.classList.add('active');
                }
                
                // Close sidebar on mobile
                if (window.innerWidth <= 768) {
                    closeSidebar();
                }
            }

            // UI Functions
            function toggleSidebar() {
                if (sidebar) sidebar.classList.toggle('open');
                if (overlay) overlay.classList.toggle('active');
            }

            function closeSidebar() {
                if (sidebar) sidebar.classList.remove('open');
                if (overlay) overlay.classList.remove('active');
            }

            function handleResponsiveLayout() {
                if (window.innerWidth > 768) {
                    closeSidebar();
                }
            }

            function showLoading(show) {
                const loader = document.getElementById('loadingOverlay');
                if (loader) loader.style.display = show ? 'flex' : 'none';
            }

            function showNotification(message, type) {
                const notification = document.createElement('div');
                notification.className = `notification ${type}`;
                notification.textContent = message;
                document.body.appendChild(notification);

                setTimeout(() => {
                    notification.style.opacity = '0';
                    setTimeout(() => notification.remove(), 300);
                }, 3000);
            }

            function logout() {
                if (confirm('Are you sure you want to logout?')) {
                    showNotification('Logging out...', 'info');
                    setTimeout(() => {
                        window.location.href = 'logout.php';
                    }, 1000);
                }
            }

            // Course Registration Functions
            function filterCourses() {
                const searchTerm = courseSearch?.value.toLowerCase() || '';
                
                if (!coursesTableBody) return;
                
                const rows = coursesTableBody.querySelectorAll('tr');
                rows.forEach(row => {
                    const text = row.textContent.toLowerCase();
                    row.style.display = text.includes(searchTerm) ? '' : 'none';
                });
            }

            function toggleSelectAll() {
                if (!selectAllCheckbox || !coursesTableBody) return;
                
                const checkboxes = coursesTableBody.querySelectorAll('input[type="checkbox"]:not(:disabled)');
                checkboxes.forEach(checkbox => {
                    checkbox.checked = selectAllCheckbox.checked;
                    const courseId = checkbox.value;
                    if (checkbox.checked) {
                        selectedCourses.add(courseId);
                    } else {
                        selectedCourses.delete(courseId);
                    }
                });
                updateSelectedCount();
            }

            function handleCourseSelection(courseId) {
                if (selectedCourses.has(courseId)) {
                    selectedCourses.delete(courseId);
                } else {
                    selectedCourses.add(courseId);
                }
                updateSelectedCount();
            }

            function updateSelectedCount() {
                if (selectedCountSpan) {
                    selectedCountSpan.textContent = selectedCourses.size;
                }
            }

            async function registerSelectedCourses() {
                if (selectedCourses.size === 0) {
                    showNotification('Please select at least one course to register.', 'error');
                    return;
                }

                showLoading(true);
                try {
                    const response = await fetch('register_courses.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            student_id: currentStudentId,
                            courses: Array.from(selectedCourses)
                        })
                    });

                    if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
                    
                    const data = await response.json();

                    if (data.success) {
                        showNotification(`Successfully registered for ${selectedCourses.size} courses!`, 'success');
                        selectedCourses.clear();
                        if (selectAllCheckbox) selectAllCheckbox.checked = false;
                        updateSelectedCount();
                        await Promise.all([fetchAvailableCourses(), fetchStudentCourses()]);
                        renderDashboard();
                    } else {
                        throw new Error(data.message || 'Registration failed');
                    }
                } catch (error) {
                    console.error('Registration error:', error);
                    showNotification(error.message, 'error');
                } finally {
                    showLoading(false);
                }
            }

            function clearSelection() {
                selectedCourses.clear();
                if (selectAllCheckbox) selectAllCheckbox.checked = false;
                if (coursesTableBody) {
                    coursesTableBody.querySelectorAll('input[type="checkbox"]').forEach(checkbox => {
                        checkbox.checked = false;
                    });
                }
                updateSelectedCount();
            }

            // Render Functions
            function renderDashboard() {
                // Update stats
                if (enrolledCount) enrolledCount.textContent = studentCourses.length;
                
                const pendingTasksCount = tasks.filter(t => t.status === 'pending').length;
                if (pendingTasks) pendingTasks.textContent = pendingTasksCount;
                
                const completedTasksCount = tasks.filter(t => t.status === 'completed' || t.status === 'graded').length;
                if (completedTasks) completedTasks.textContent = completedTasksCount;
                
                // Calculate average grade if grades exist
                if (grades.length > 0) {
                    const total = grades.reduce((sum, grade) => sum + parseFloat(grade.score), 0);
                    const average = total / grades.length;
                    if (avgGrade) avgGrade.textContent = average.toFixed(1);
                }

                // Render course cards for dashboard
                if (dashboardCourses) {
                    dashboardCourses.innerHTML = studentCourses.slice(0, 3).map(course => `
                        <div class="course-card">
                            <h4>${course.name}</h4>
                            <p><strong>Code:</strong> ${course.code}</p>
                            <p><strong>Instructor:</strong> ${course.instructor}</p>
                        </div>
                    `).join('');
                }

                // Render upcoming deadlines
                if (upcomingDeadlines) {
                    const upcoming = tasks
                        .filter(t => t.status === 'pending')
                        .slice(0, 3);

                    upcomingDeadlines.innerHTML = upcoming.map(task => `
                        <div class="deadline-item">
                            <h4>${task.title}</h4>
                            <p><strong>Due:</strong> ${new Date(task.due_date).toLocaleDateString()}</p>
                        </div>
                    `).join('');
                }
            }

            function renderCourseRegistration() {
                if (!coursesTableBody) return;
                
                coursesTableBody.innerHTML = availableCourses.map(course => `
                    <tr>
                        <td><input type="checkbox" value="${course.id}" 
                            ${enrolledCourses.has(course.id) ? 'disabled' : ''} 
                            onchange="handleCourseSelection('${course.id}')"></td>
                        <td>${course.code}</td>
                        <td>${course.name}</td>
                        <td>${course.credits}</td>
                        <td>${course.instructor}</td>
                        <td>${course.schedule}</td>
                        <td>${course.enrolled || 0}/${course.capacity || 'N/A'}</td>
                        <td>${enrolledCourses.has(course.id) ? 'Enrolled' : 'Available'}</td>
                        <td>
                            <button class="btn-small" onclick="viewCourseDetails('${course.id}')">
                                Details
                            </button>
                        </td>
                    </tr>
                `).join('');
            }

            function renderMyCourses() {
                if (!myCoursesGrid) return;
                
                myCoursesGrid.innerHTML = studentCourses.map(course => `
                    <div class="course-card">
                        <h3>${course.name}</h3>
                        <p><strong>Code:</strong> ${course.code}</p>
                        <p><strong>Instructor:</strong> ${course.instructor}</p>
                        <p><strong>Schedule:</strong> ${course.schedule}</p>
                        <div class="course-actions">
                            <button class="btn-small" onclick="viewCourseDetails('${course.id}')">
                                View Details
                            </button>
                        </div>
                    </div>
                `).join('');
            }

            function renderTasks() {
                if (!tasksGrid) return;
                
                let filteredTasks = tasks;
                if (currentTaskFilter === 'pending') {
                    filteredTasks = tasks.filter(t => t.status === 'pending');
                } else if (currentTaskFilter === 'submitted') {
                    filteredTasks = tasks.filter(t => t.status === 'submitted');
                } else if (currentTaskFilter === 'graded') {
                    filteredTasks = tasks.filter(t => t.status === 'graded');
                }

                tasksGrid.innerHTML = filteredTasks.map(task => `
                    <div class="task-card ${task.status}">
                        <h3>${task.title}</h3>
                        <p><strong>Course:</strong> ${task.course_name || task.course_code}</p>
                        <p><strong>Due:</strong> ${new Date(task.due_date).toLocaleDateString()}</p>
                        <p><strong>Status:</strong> ${task.status}</p>
                        ${task.status === 'pending' ? `
                            <button class="btn-small" onclick="submitTask('${task.id}', '${task.course_code}')">
                                Submit
                            </button>
                        ` : ''}
                        ${task.status === 'graded' ? `
                            <p><strong>Grade:</strong> ${task.grade || 'N/A'}</p>
                        ` : ''}
                    </div>
                `).join('');
            }

            function renderGrades() {
                const gradesDetailed = document.getElementById('gradesDetailed');
                if (!gradesDetailed) return;
                
                gradesDetailed.innerHTML = grades.map(grade => `
                    <div class="grade-card">
                        <h3>${grade.course_name}</h3>
                        <p><strong>Task:</strong> ${grade.task_title}</p>
                        <p><strong>Score:</strong> ${grade.score}</p>
                        <p><strong>Feedback:</strong> ${grade.feedback || 'No feedback provided'}</p>
                    </div>
                `).join('');
            }

            function updateGradeStats() {
                const overallGrade = document.getElementById('overallGrade');
                const totalCourses = document.getElementById('totalCourses');
                const completedTasksCount = document.getElementById('completedTasksCount');
                const averageScore = document.getElementById('averageScore');
                
                if (grades.length > 0) {
                    const total = grades.reduce((sum, grade) => sum + parseFloat(grade.score), 0);
                    const average = total / grades.length;
                    
                    if (overallGrade) overallGrade.textContent = average.toFixed(1);
                    if (totalCourses) totalCourses.textContent = new Set(grades.map(g => g.course_id)).size;
                    if (completedTasksCount) completedTasksCount.textContent = `${grades.length}/${tasks.length}`;
                    if (averageScore) averageScore.textContent = `${average.toFixed(1)}%`;
                }
            }

            // Task submission functions
            function submitTask(taskId, courseCode) {
                // Implement task submission modal opening
                console.log(`Submitting task ${taskId} for course ${courseCode}`);
                // You would typically open a modal here for submission
            }

            function viewCourseDetails(courseId) {
                // Implement course details viewing
                console.log(`Viewing details for course ${courseId}`);
            }

            // Make functions available globally
            window.showPage = showPage;
            window.toggleSidebar = toggleSidebar;
            window.closeSidebar = closeSidebar;
            window.logout = logout;
            window.filterCourses = filterCourses;
            window.toggleSelectAll = toggleSelectAll;
            window.handleCourseSelection = handleCourseSelection;
            window.registerSelectedCourses = registerSelectedCourses;
            window.clearSelection = clearSelection;
            window.viewCourseDetails = viewCourseDetails;
            window.submitTask = submitTask;
        });
    </script>
</body>
</html>