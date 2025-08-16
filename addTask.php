<?php
session_start();
require_once 'connection.php';

// Check if user is logged in and is an instructor
// if (!isset($_SESSION['user_id']) || $_SESSION['user']['role'] !== 'instructor') {
//     header("Location: account.php");
//     exit();
// }

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Get user data
$stmt = $pdo->prepare("SELECT * FROM accounts WHERE accounts_id = ? and role = 'instructor'");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    session_destroy();
    header("Location: account.php");
    exit();
}

// Get courses taught by this instructor
$courses = [];
try {
    $stmt = $pdo->prepare("SELECT course_code, course_name FROM courses WHERE instructor_id = ?");
    $stmt->execute([$user_id]);
    $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = 'Error loading courses: ' . $e->getMessage();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $taskTitle = trim($_POST['taskTitle']);
    $courseCode = trim($_POST['courseCode']);
    $topicCode = trim($_POST['topicCode']);
    $taskType = trim($_POST['taskType']);
    $taskDescription = trim($_POST['taskDescription']);
    $dueDate = trim($_POST['dueDate']);
    $maxMarks = (int)$_POST['maxMarks'];
    $allowLateSubmission = trim($_POST['allowLateSubmission']);
    $studentIds = $_POST['students'] ?? [];

    // Validate inputs
    if (empty($taskTitle) || empty($courseCode) || empty($topicCode) || empty($taskType) || 
        empty($taskDescription) || empty($dueDate) || $maxMarks <= 0 || count($studentIds) === 0) {
        $error = 'All fields are required and must be valid';
    } else {
        try {
            $pdo->beginTransaction();

            // Insert the task
            $stmt = $pdo->prepare("INSERT INTO tasks (task_title, course_code, topic_code, task_type, 
                                  description, due_date, max_marks, allow_late_submission, instructor_id)
                                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $taskTitle, $courseCode, $topicCode, $taskType, $taskDescription, 
                $dueDate, $maxMarks, $allowLateSubmission, $user_id
            ]);
            
            $taskId = $pdo->lastInsertId();

            // Assign task to selected students
            $assignStmt = $pdo->prepare("INSERT INTO task_assignments (task_id, student_id) VALUES (?, ?)");
            foreach ($studentIds as $studentId) {
                $assignStmt->execute([$taskId, $studentId]);
            }

            $pdo->commit();
            $success = 'Task created and assigned successfully!';
            $_POST = array(); // Clear form
        } catch (PDOException $e) {
            $pdo->rollBack();
            $error = 'Database error: ' . $e->getMessage();
        }
    }
}

function getUserInitials($fullName) {
    if (empty($fullName)) return 'U';
    $words = explode(' ', trim($fullName));
    if (count($words) >= 2) {
        return strtoupper(substr($words[0], 0, 1) . substr($words[1], 0, 1));
    }
    return strtoupper(substr($fullName, 0, 2));
}

// Get user data for display
$full_name = ($user['sur_name'] ?? '') . ' ' . ($user['other_name'] ?? '');
$initials = getUserInitials($full_name);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CodeLab - Add Task</title>
    <link rel="stylesheet" href="dasboard.css">
    <style>
        .form-container {
            max-width: 900px;
            margin: 0 auto;
        }

        .form-section {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }

        .form-section h3 {
            color: #333;
            margin-bottom: 1.5rem;
            font-size: 1.3rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #555;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
            box-sizing: border-box;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #667eea;
        }

        .form-group textarea {
            resize: vertical;
            min-height: 120px;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
        }

        .form-row-three {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 1.5rem;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 1rem 2rem;
            border-radius: 8px;
            font-size: 1rem;
            cursor: pointer;
            transition: transform 0.3s ease;
            width: 100%;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
        }

        .form-actions {
            margin-top: 2rem;
        }

        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
        }

        .alert-error {
            background-color: #ffebee;
            color: #c62828;
            border: 1px solid #ef9a9a;
        }

        .alert-success {
            background-color: #e8f5e9;
            color: #2e7d32;
            border: 1px solid #a5d6a7;
        }

        .student-selection {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 1rem;
            margin-top: 1rem;
        }

        .student-list {
            max-height: 300px;
            overflow-y: auto;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            padding: 1rem;
            background: white;
        }

        .student-item {
            display: flex;
            align-items: center;
            padding: 0.75rem;
            border-bottom: 1px solid #eee;
            transition: background-color 0.2s ease;
        }

        .student-item:hover {
            background-color: #f8f9fa;
        }

        .student-checkbox {
            margin-right: 1rem;
            transform: scale(1.2);
        }

        .student-info {
            flex: 1;
        }

        .student-name {
            font-weight: 500;
            color: #333;
        }

        .student-details {
            font-size: 0.9rem;
            color: #666;
            margin-top: 0.25rem;
        }

        .select-all-container {
            padding: 1rem;
            border-bottom: 2px solid #e1e5e9;
            background: #f8f9fa;
            border-radius: 8px 8px 0 0;
        }

        .select-all-checkbox {
            margin-right: 0.5rem;
            transform: scale(1.2);
        }

        .selected-count {
            background: #667eea;
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.85rem;
            margin-left: 1rem;
        }

        .course-info {
            background: #e8f4f8;
            border-left: 4px solid #667eea;
            padding: 1rem;
            margin-top: 1rem;
            border-radius: 0 8px 8px 0;
        }

        @media (max-width: 768px) {
            .form-row,
            .form-row-three {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <div style="display: flex; align-items: center; gap: 1rem;">
                <div class="hamburger" onclick="toggleSidebar()">
                    <span></span>
                    <span></span>
                    <span></span>
                </div>
                <div class="logo">E-MANUAL</div>
            </div>
            <div class="user-menu">
                <span>Welcome back, <?= htmlspecialchars($user['other_name'] ?? 'Instructor') ?></span>
                <div class="user-avatar"><?= htmlspecialchars($initials) ?></div>
                <button onclick="logout()" class="logout-btn">Logout</button>
            </div>
        </div>
    </div>

    <div class="overlay" onclick="closeSidebar()"></div>

    <div class="main-container">
        <nav class="sidebar" id="sidebar">
            <ul class="sidebar-menu">
                <li class="sidebar-item">
                    <a href="instructorDashboard.php" class="sidebar-link"  onclick="showPage('profile', event)">
                        <span class="sidebar-icon">👤</span>
                        My Profile
                    </a>
                </li>
                <li class="sidebar-item">
                    <a href="instructorDashboard.php?page=dashboard" class="sidebar-link"  onclick="showPage('dashboard', event)">
                        <span class="sidebar-icon">📊</span>
                        Dashboard
                    </a>
                </li>
                <li class="sidebar-item">
                    <a href="addtopic.php" class="sidebar-link">
                        <span class="sidebar-icon">➕</span>
                        Add Topic
                    </a>
                </li>
                <li class="sidebar-item">
                    <a href="addTask.php" class="sidebar-link active">
                        <span class="sidebar-icon">📚</span>
                        Add Task
                    </a>
                </li>
                <li class="sidebar-item">
                    <a href="grade.php" class="sidebar-link">
                        <span class="sidebar-icon">📈</span>
                        Grade
                    </a>
                </li>
            </ul>
        </nav>

        <main class="content-area">
            <div id="add-task" class="page-section active">
                <div class="form-container">
                    <div class="section-card">
                        <h2 class="section-title">📚 Assign New Task</h2>
                        
                        <?php if ($error): ?>
                            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
                        <?php endif; ?>
                        
                        <?php if ($success): ?>
                            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
                        <?php endif; ?>
                        
                        <form method="POST" id="addTaskForm">
                            <!-- Task Details -->
                            <div class="form-section">
                                <h3>📝 Task Details</h3>
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="taskTitle">Task Title *</label>
                                        <input type="text" id="taskTitle" name="taskTitle" required
                                               value="<?= htmlspecialchars($_POST['taskTitle'] ?? '') ?>"
                                               placeholder="Enter task title">
                                    </div>
                                    <div class="form-group">
                                        <label for="courseCode">Course *</label>
                                        <select id="courseCode" name="courseCode" required onchange="loadTopics()">
                                            <option value="">Select Course</option>
                                            <?php foreach ($courses as $course): ?>
                                                <option value="<?= htmlspecialchars($course['course_code']) ?>"
                                                    <?= isset($_POST['courseCode']) && $_POST['courseCode'] === $course['course_code'] ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($course['course_code'] . ' - ' . $course['course_name']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>

                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="topicCode">Topic *</label>
                                        <select id="topicCode" name="topicCode" required>
                                            <option value="">Select Topic</option>
                                            <?php 
                                            if (isset($_POST['courseCode']) && !empty($_POST['courseCode'])) {
                                                try {
                                                    $stmt = $pdo->prepare("SELECT topic_code, topic_title FROM topics WHERE course_code = ?");
                                                    $stmt->execute([$_POST['courseCode']]);
                                                    $topics = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                                    
                                                    foreach ($topics as $topic) {
                                                        echo '<option value="' . htmlspecialchars($topic['topic_code']) . '"';
                                                        if (isset($_POST['topicCode']) && $_POST['topicCode'] === $topic['topic_code']) {
                                                            echo ' selected';
                                                        }
                                                        echo '>' . htmlspecialchars($topic['topic_code'] . ' - ' . $topic['topic_title']) . '</option>';
                                                    }
                                                } catch (PDOException $e) {
                                                    $error = 'Error loading topics: ' . $e->getMessage();
                                                }
                                            }
                                            ?>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label for="taskType">Task Type *</label>
                                        <select id="taskType" name="taskType" required>
                                            <option value="">Select Type</option>
                                            <option value="assignment" <?= isset($_POST['taskType']) && $_POST['taskType'] === 'assignment' ? 'selected' : '' ?>>Assignment</option>
                                            <option value="project" <?= isset($_POST['taskType']) && $_POST['taskType'] === 'project' ? 'selected' : '' ?>>Project</option>
                                            <option value="quiz" <?= isset($_POST['taskType']) && $_POST['taskType'] === 'quiz' ? 'selected' : '' ?>>Quiz</option>
                                            <option value="practical" <?= isset($_POST['taskType']) && $_POST['taskType'] === 'practical' ? 'selected' : '' ?>>Practical</option>
                                            <option value="research" <?= isset($_POST['taskType']) && $_POST['taskType'] === 'research' ? 'selected' : '' ?>>Research</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label for="taskDescription">Task Description *</label>
                                    <textarea id="taskDescription" name="taskDescription" required
                                              placeholder="Provide detailed instructions for the task..."><?= 
                                              htmlspecialchars($_POST['taskDescription'] ?? '') ?></textarea>
                                </div>
                            </div>

                            <!-- Task Settings -->
                            <div class="form-section">
                                <h3>⚙️ Task Settings</h3>
                                
                                <div class="form-row-three">
                                    <div class="form-group">
                                        <label for="dueDate">Due Date *</label>
                                        <input type="datetime-local" id="dueDate" name="dueDate" required
                                               value="<?= htmlspecialchars($_POST['dueDate'] ?? '') ?>">
                                    </div>
                                    <div class="form-group">
                                        <label for="maxMarks">Maximum Marks *</label>
                                        <input type="number" id="maxMarks" name="maxMarks" required min="1" max="100"
                                               value="<?= htmlspecialchars($_POST['maxMarks'] ?? '') ?>"
                                               placeholder="e.g., 50">
                                    </div>
                                    <div class="form-group">
                                        <label for="allowLateSubmission">Allow Late Submission</label>
                                        <select id="allowLateSubmission" name="allowLateSubmission">
                                            <option value="no" <?= (!isset($_POST['allowLateSubmission']) || $_POST['allowLateSubmission'] === 'no') ? 'selected' : '' ?>>No</option>
                                            <option value="yes" <?= isset($_POST['allowLateSubmission']) && $_POST['allowLateSubmission'] === 'yes' ? 'selected' : '' ?>>Yes</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <!-- Student Selection -->
                            <div class="form-section">
                                <h3>👥 Assign to Students</h3>
                                
                                <div id="courseInfo" class="course-info" style="<?= isset($_POST['courseCode']) ? '' : 'display: none;' ?>">
                                    <strong>Course:</strong> <span id="selectedCourse">
                                        <?php if (isset($_POST['courseCode'])) {
                                            foreach ($courses as $course) {
                                                if ($course['course_code'] === $_POST['courseCode']) {
                                                    echo htmlspecialchars($course['course_code'] . ' - ' . $course['course_name']);
                                                    break;
                                                }
                                            }
                                        } ?>
                                    </span><br>
                                    <strong>Total Students:</strong> <span id="totalStudents">0</span>
                                </div>
                                
                                <div class="student-selection">
                                    <div class="select-all-container">
                                        <label>
                                            <input type="checkbox" id="selectAll" class="select-all-checkbox" onchange="toggleAllStudents()">
                                            Select All Students
                                        </label>
                                        <span id="selectedCount" class="selected-count">0 selected</span>
                                    </div>
                                    
                                    <div id="studentList" class="student-list">
                                        <?php
                                        if (isset($_POST['courseCode']) && !empty($_POST['courseCode'])) {
                                            try {
                                                $stmt = $pdo->prepare("
                                                    SELECT a.accounts_id, a.sur_name, a.other_name, a.email 
                                                    FROM course_enrollments ce
                                                    JOIN accounts a ON ce.accounts_id = a.accounts_id
                                                    WHERE ce.course_code = ? AND a.role = 'student'
                                                ");
                                                $stmt->execute([$_POST['courseCode']]);
                                                $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                                
                                                if (count($students) > 0) {
                                                    echo '<div id="studentListContent">';
                                                    foreach ($students as $student) {
                                                        $checked = isset($_POST['students']) && in_array($student['accounts_id'], $_POST['students']) ? 'checked' : '';
                                                        echo '
                                                        <div class="student-item">
                                                            <input type="checkbox" class="student-checkbox" name="students[]" 
                                                                   value="' . htmlspecialchars($student['accounts_id']) . '" ' . $checked . '>
                                                            <div class="student-info">
                                                                <div class="student-name">' . htmlspecialchars($student['sur_name'] . ' ' . $student['other_name']) . '</div>
                                                                <div class="student-details">Email: ' . htmlspecialchars($student['email']) . '</div>
                                                            </div>
                                                        </div>';
                                                    }
                                                    echo '</div>';
                                                    echo '<script>updateSelectedCount();</script>';
                                                } else {
                                                    echo '<p style="text-align: center; color: #666; padding: 2rem;">No students enrolled in this course</p>';
                                                }
                                            } catch (PDOException $e) {
                                                echo '<p style="text-align: center; color: #666; padding: 2rem;">Error loading students</p>';
                                            }
                                        } else {
                                            echo '<p style="text-align: center; color: #666; padding: 2rem;">Please select a course to view enrolled students</p>';
                                        }
                                        ?>
                                    </div>
                                </div>
                            </div>

                            <!-- Form Actions -->
                            <div class="form-actions">
                                <button type="submit" class="btn-primary">Assign Task</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Load topics when course changes
        function loadTopics() {
            const courseCode = document.getElementById('courseCode').value;
            const topicSelect = document.getElementById('topicCode');
            
            if (!courseCode) {
                topicSelect.innerHTML = '<option value="">Select Topic</option>';
                document.getElementById('courseInfo').style.display = 'none';
                document.getElementById('studentList').innerHTML = '<p style="text-align: center; color: #666; padding: 2rem;">Please select a course to view enrolled students</p>';
                return;
            }
            
            // Update course info
            const selectedOption = document.querySelector(`#courseCode option[value="${courseCode}"]`);
            document.getElementById('selectedCourse').textContent = selectedOption.textContent;
            document.getElementById('courseInfo').style.display = 'block';
            
            // Load topics via AJAX
            fetch(`get_topics.php?course_code=${courseCode}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        topicSelect.innerHTML = '<option value="">Select Topic</option>';
                        data.topics.forEach(topic => {
                            const option = document.createElement('option');
                            option.value = topic.topic_code;
                            option.textContent = `${topic.topic_code} - ${topic.topic_title}`;
                            topicSelect.appendChild(option);
                        });
                    } else {
                        showNotification('Error loading topics: ' + data.error, 'error');
                    }
                })
                .catch(error => {
                    showNotification('Error loading topics: ' + error.message, 'error');
                });
            
            // Load students
            fetch(`get_students.php?course_code=${courseCode}`)
                .then(response => response.json())
                .then(data => {
                    const studentList = document.getElementById('studentList');
                    
                    if (data.success) {
                        document.getElementById('totalStudents').textContent = data.students.length;
                        
                        if (data.students.length === 0) {
                            studentList.innerHTML = '<p style="text-align: center; color: #666; padding: 2rem;">No students enrolled in this course</p>';
                            return;
                        }

                        studentList.innerHTML = `
                            <div class="select-all-container">
                                <label>
                                    <input type="checkbox" id="selectAll" class="select-all-checkbox" onchange="toggleAllStudents()">
                                    Select All Students
                                </label>
                                <span id="selectedCount" class="selected-count">0 selected</span>
                            </div>
                            <div id="studentListContent">
                                ${data.students.map(student => `
                                    <div class="student-item">
                                        <input type="checkbox" class="student-checkbox" name="students[]" 
                                               value="${student.accounts_id}" onchange="updateSelectedStudents()">
                                        <div class="student-info">
                                            <div class="student-name">${student.sur_name} ${student.other_name}</div>
                                            <div class="student-details">Email: ${student.email}</div>
                                        </div>
                                    </div>
                                `).join('')}
                            </div>
                        `;
                        
                        // Reset selections
                        selectedStudents = [];
                        document.getElementById('selectAll').checked = false;
                        updateSelectedCount();
                    } else {
                        showNotification('Error loading students: ' + data.error, 'error');
                    }
                })
                .catch(error => {
                    showNotification('Error loading students: ' + error.message, 'error');
                });
        }

        // Student selection functions
        let selectedStudents = [];

        function toggleAllStudents() {
            const selectAll = document.getElementById('selectAll');
            const checkboxes = document.querySelectorAll('.student-checkbox');
            
            checkboxes.forEach(checkbox => {
                checkbox.checked = selectAll.checked;
            });
            
            updateSelectedStudents();
        }

        function updateSelectedStudents() {
            const checkboxes = document.querySelectorAll('.student-checkbox:checked');
            selectedStudents = Array.from(checkboxes).map(cb => cb.value);
            
            const selectAll = document.getElementById('selectAll');
            const allCheckboxes = document.querySelectorAll('.student-checkbox');
            
            if (selectedStudents.length === allCheckboxes.length) {
                selectAll.checked = true;
                selectAll.indeterminate = false;
            } else if (selectedStudents.length === 0) {
                selectAll.checked = false;
                selectAll.indeterminate = false;
            } else {
                selectAll.checked = false;
                selectAll.indeterminate = true;
            }
            
            updateSelectedCount();
        }

        function updateSelectedCount() {
            const selectedCount = document.getElementById('selectedCount');
            const count = document.querySelectorAll('.student-checkbox:checked').length;
            selectedCount.textContent = `${count} selected`;
        }

        // Set minimum date to today
        function setMinDate() {
            const now = new Date();
            const year = now.getFullYear();
            const month = String(now.getMonth() + 1).padStart(2, '0');
            const day = String(now.getDate()).padStart(2, '0');
            const hours = String(now.getHours()).padStart(2, '0');
            const minutes = String(now.getMinutes()).padStart(2, '0');
            
            const minDateTime = `${year}-${month}-${day}T${hours}:${minutes}`;
            document.getElementById('dueDate').min = minDateTime;
            
            // Set default due date to tomorrow at same time
            if (!document.getElementById('dueDate').value) {
                const tomorrow = new Date(now);
                tomorrow.setDate(tomorrow.getDate() + 1);
                const tomorrowYear = tomorrow.getFullYear();
                const tomorrowMonth = String(tomorrow.getMonth() + 1).padStart(2, '0');
                const tomorrowDay = String(tomorrow.getDate()).padStart(2, '0');
                
                document.getElementById('dueDate').value = `${tomorrowYear}-${tomorrowMonth}-${tomorrowDay}T${hours}:${minutes}`;
            }
        }

        // Form validation
        function validateForm() {
            let isValid = true;
            
            // Check required fields
            const requiredFields = [
                'taskTitle', 'courseCode', 'topicCode', 'taskType',
                'taskDescription', 'dueDate', 'maxMarks'
            ];
            
            requiredFields.forEach(fieldId => {
                const field = document.getElementById(fieldId);
                if (!field.value.trim()) {
                    field.style.borderColor = '#f44336';
                    isValid = false;
                } else {
                    field.style.borderColor = '#e1e5e9';
                }
            });
            
            // Check at least one student is selected
            if (selectedStudents.length === 0) {
                showNotification('Please select at least one student', 'error');
                isValid = false;
            }
            
            return isValid;
        }

        // Navigation functions
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
                window.location.href = 'logout.php';
            }
        }

        // Notification system
        function showNotification(message, type = 'info') {
            const existingNotification = document.querySelector('.notification');
            if (existingNotification) {
                existingNotification.remove();
            }

            const notification = document.createElement('div');
            notification.className = `notification notification-${type}`;
            notification.innerHTML = `
                <div style="
                    position: fixed;
                    top: 100px;
                    right: 20px;
                    background: ${type === 'success' ? '#4CAF50' : type === 'error' ? '#f44336' : '#2196F3'};
                    color: white;
                    padding: 1rem 1.5rem;
                    border-radius: 8px;
                    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                    z-index: 1000;
                    animation: slideIn 0.3s ease;
                ">
                    ${message}
                </div>
                <style>
                    @keyframes slideIn {
                        from { transform: translateX(100%); opacity: 0; }
                        to { transform: translateX(0); opacity: 1; }
                    }
                </style>
            `;
            
            document.body.appendChild(notification);
            
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.style.animation = 'slideIn 0.3s ease reverse';
                    setTimeout(() => notification.remove(), 300);
                }
            }, 3000);
        }

        // Initialize page
        document.addEventListener('DOMContentLoaded', function() {
            setMinDate();
            
            // If course is already selected (from form submission), load its topics and students
            const courseCode = document.getElementById('courseCode').value;
            if (courseCode) {
                loadTopics();
            }
        });

        // Form submission
        document.getElementById('addTaskForm').addEventListener('submit', function(e) {
            if (!validateForm()) {
                e.preventDefault();
            }
        });
    </script>
</body>
</html>