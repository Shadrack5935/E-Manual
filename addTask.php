<?php
session_start();
require_once 'connection.php';
$user_id = $_SESSION['user_id'] ?? null;
$user = null;
if ($user_id) {
        $stmt = $pdo->prepare("SELECT * FROM accounts WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
    // try {
    //     $stmt = $pdo->prepare("SELECT * FROM accounts WHERE id = ?");
    //     $stmt->execute([$user_id]);
    //     $user = $stmt->fetch(PDO::FETCH_ASSOC);

    //     if (!$user || !isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    //         session_destroy();
    //         header("Location: account.php");
    //         exit();
    //     }
    // } catch(PDOException $e) {
    //     error_log("Database error in dashboard: " . $e->getMessage());
    //     die("Something went wrong. Please try again later.");
    // }
}
function getUserInitials($fullName) {
    if (empty($fullName)) return 'U';
    $words = explode(' ', trim($fullName));
    if (count($words) >= 2) {
        return strtoupper(substr($words[0], 0, 1) . substr($words[1], 0, 1));
    }
    return strtoupper(substr($fullName, 0, 2));
}

// Get user data properly from the fetched $user array
$full_name = ($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '');
if (trim($full_name) === '') {
    $full_name = $user['fullname'] ?? 'Instructor';
}
$initials = getUserInitials($full_name);
$username = $user['username'] ?? '';
$staff_id = $user['staff_id'] ?? '00000';
$first_name = $user['first_name'] ?? '';
$last_name = $user['last_name'] ?? '';
$email = $user['email'] ?? '';
$phone = $user['phone'] ?? '';
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

        .btn-secondary {
            background: #6c757d;
            color: white;
            border: none;
            padding: 1rem 2rem;
            border-radius: 8px;
            font-size: 1rem;
            cursor: pointer;
            margin-right: 1rem;
        }

        .btn-secondary:hover {
            background: #5a6268;
        }

        .form-actions {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
            margin-top: 2rem;
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

        .student-item:last-child {
            border-bottom: none;
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

        .attachment-area {
            border: 2px dashed #ccc;
            border-radius: 8px;
            padding: 2rem;
            text-align: center;
            background: #fafafa;
            transition: all 0.3s ease;
        }

        .attachment-area:hover {
            border-color: #667eea;
            background: #f0f4ff;
        }

        .attachment-area.drag-over {
            border-color: #667eea;
            background: #e8f4f8;
        }

        .file-input {
            display: none;
        }

        .file-list {
            margin-top: 1rem;
        }

        .file-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.75rem;
            background: white;
            border-radius: 8px;
            margin-bottom: 0.5rem;
            border: 1px solid #e1e5e9;
        }

        .file-info {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .file-remove {
            background: #dc3545;
            color: white;
            border: none;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.8rem;
        }

        @media (max-width: 768px) {
            .form-row,
            .form-row-three {
                grid-template-columns: 1fr;
            }
            
            .form-actions {
                flex-direction: column;
            }
            
            .btn-secondary {
                margin-right: 0;
                margin-bottom: 1rem;
            }
        }

        /* Navigation Styles */
       .header {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            height: 70px;
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 2rem;
            height: 100%;
        }

        .logo {
            font-size: 1.8rem;
            font-weight: bold;
            color: black;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
        }

        .hamburger {
            display: none;
            flex-direction: column;
            cursor: pointer;
            gap: 4px;
        }

        .hamburger span {
            width: 25px;
            height: 3px;
            background: white;
            border-radius: 2px;
            transition: all 0.3s ease;
        }

        .user-menu {
            display: flex;
            align-items: center;
            gap: 1rem;
            color: black;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            background: linear-gradient(45deg, #ff6b6b, #4ecdc4);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            color: white;
            font-size: 0.9rem;
        }

        /* Main Container */
        .main-container {
            display: flex;
            margin-top: 70px;
            min-height: calc(100vh - 70px);
        }
              .sidebar {
            width: 280px;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            box-shadow: 2px 0 20px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
            position: fixed;
            height: calc(100vh - 70px);
            overflow-y: auto;
        }

        .sidebar-menu {
            list-style: none;
            padding: 2rem 0;
        }

        .sidebar-item {
            margin-bottom: 0.5rem;
        }

        .sidebar-link {
            display: flex;
            align-items: center;
            padding: 1rem 1.5rem;
            text-decoration: none;
            color: #555;
            transition: all 0.3s ease;
            border-left: 4px solid transparent;
        }

        .sidebar-link:hover {
            background: linear-gradient(90deg, rgba(102, 126, 234, 0.1), transparent);
            color: #667eea;
            border-left-color: #667eea;
        }

        .sidebar-link.active {
            background: linear-gradient(90deg, rgba(102, 126, 234, 0.15), transparent);
            color: #667eea;
            border-left-color: #667eea;
            font-weight: 600;
        }

        .sidebar-icon {
            margin-right: 1rem;
            font-size: 1.2rem;
            width: 24px;
            text-align: center;
        }


        .content-area {
            flex: 1;
            margin-left: 250px;
            padding: 2rem;
            background: #f8f9fa;
        }

        .page-section {
            display: none;
        }

        .page-section.active {
            display: block;
        }

        .section-card {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }

        .section-title {
            color: #333;
            margin-bottom: 2rem;
            font-size: 1.8rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            z-index: 999;
        }

        .overlay.active {
            display: block;
        }

        @media (max-width: 768px) {
            .hamburger {
                display: flex;
            }
            
            .sidebar {
                transform: translateX(-100%);
                z-index: 1001;
            }
            
            .sidebar.open {
                transform: translateX(0);
            }
            
            .content-area {
                margin-left: 0;
                padding: 1rem;
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
                <div class="logo">CodeLab</div>
            </div>
            <div class="user-menu">
                <span>Welcome back,<?= htmlspecialchars($full_name) ?></span>
                <div class="user-avatar"><?= htmlspecialchars($initials) ?></div>
                <button onclick="logout()" style="background: rgba(255,255,255,0.2); border: 1px solid rgba(255,255,255,0.3); color: white; padding: 0.5rem 1rem; border-radius: 4px; cursor: pointer;">Logout</button>
            </div>
        </div>
    </div>

    <div class="overlay" onclick="closeSidebar()"></div>

    <div class="main-container">
        <nav class="sidebar" id="sidebar">
            <ul class="sidebar-menu">
                <li class="sidebar-item">
                    <a href="InstructorDashboard.php" class="sidebar-link" onclick="showPage('profile')">
                        <span class="sidebar-icon">👤</span>
                        My Profile
                    </a>
                </li>
                <li class="sidebar-item">
                    <a href="instructorDashboard.php" class="sidebar-link" onclick="showPage('dashboard')">
                        <span class="sidebar-icon">📊</span>
                        Dashboard
                    </a>
                </li>
                <li class="sidebar-item">
                    <a href="addtopic.php" class="sidebar-link" onclick="showPage('add-topic')">
                        <span class="sidebar-icon">➕</span>
                        Add Topic Task
                    </a>
                </li>
                <li class="sidebar-item">
                    <a href="addTask.php" class="sidebar-link active" onclick="showPage('add-task')">
                        <span class="sidebar-icon">📚</span>
                        Add Task
                    </a>
                </li>
                <li class="sidebar-item">
                    <a href="grade.php" class="sidebar-link" onclick="showPage('add-task')">
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
                        
                        <form id="addTaskForm">
                            <!-- Basic Task Information -->
                            <div class="form-section">
                                <h3>📝 Task Details</h3>
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="taskTitle">Task Title *</label>
                                        <input type="text" id="taskTitle" name="taskTitle" required placeholder="Enter task title">
                                    </div>
                                    <div class="form-group">
                                        <label for="courseCode">Course Code *</label>
                                        <select id="courseCode" name="courseCode" required onchange="loadStudents(); loadTopics();">
                                            <option value="">Select Course</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="topicCode">Topic Code *</label>
                                        <select id="topicCode" name="topicCode" required>
                                            <option value="">Select Topic</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label for="taskType">Task Type *</label>
                                        <select id="taskType" name="taskType" required>
                                            <option value="">Select Type</option>
                                            <option value="assignment">Assignment</option>
                                            <option value="project">Project</option>
                                            <option value="quiz">Quiz</option>
                                            <option value="practical">Practical</option>
                                            <option value="research">Research</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label for="taskDescription">Task Description *</label>
                                    <textarea id="taskDescription" name="taskDescription" required placeholder="Provide detailed instructions for the task..."></textarea>
                                </div>
                            </div>

                            <!-- Task Settings -->
                            <div class="form-section">
                                <h3>⚙️ Task Settings</h3>
                                
                                <div class="form-row-three">
                                    <div class="form-group">
                                        <label for="dueDate">Due Date *</label>
                                        <input type="datetime-local" id="dueDate" name="dueDate" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="maxMarks">Maximum Marks *</label>
                                        <input type="number" id="maxMarks" name="maxMarks" required min="1" max="100" placeholder="e.g., 50">
                                    </div>
                                    <div class="form-row">
                                    <div class="form-group">
                                        <label for="allowLateSubmission">Allow Late Submission</label>
                                        <select id="allowLateSubmission" name="allowLateSubmission">
                                            <option value="no">No</option>
                                            <option value="yes">Yes</option>
                                        </select>
                                    </div>
                                </div>
                                </div>
                            </div>

                            <!-- File Attachments -->
                            <div class="form-section">
                                <h3>📎 Task Attachments</h3>
                                
                                <div class="attachment-area" onclick="document.getElementById('fileInput').click()">
                                    <p>📁 Click to upload files or drag and drop</p>
                                    <p style="color: #666; font-size: 0.9rem;">Supported formats: PDF, DOC, DOCX, XLS, XLSX, PPT, PPTX, ZIP (Max 10MB each)</p>
                                </div>
                                
                                <input type="file" id="fileInput" class="file-input" multiple accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.zip">
                                
                                <div id="fileList" class="file-list"></div>
                            </div>

                            <!-- Student Selection -->
                            <div class="form-section">
                                <h3>👥 Assign to Students</h3>
                                
                                <div id="courseInfo" class="course-info" style="display: none;">
                                    <strong>Course:</strong> <span id="selectedCourse"></span><br>
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
                                        <p style="text-align: center; color: #666; padding: 2rem;">
                                            Please select a course to view enrolled students
                                        </p>
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
        let selectedFiles = [];
        let selectedStudents = [];

        // Load courses on page load
        function loadCourses() {
            fetch('get_courses.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const courseSelect = document.getElementById('courseCode');
                        courseSelect.innerHTML = '<option value="">Select Course</option>';
                        
                        data.courses.forEach(course => {
                            const option = document.createElement('option');
                            option.value = course.course_code;
                            option.textContent = `${course.course_code} - ${course.course_name}`;
                            courseSelect.appendChild(option);
                        });
                    } else {
                        showNotification('Error loading courses: ' + data.error, 'error');
                    }
                })
                .catch(error => {
                    showNotification('Error loading courses: ' + error.message, 'error');
                });
        }

        // Load topics based on selected course
        function loadTopics() {
            const courseCode = document.getElementById('courseCode').value;
            const topicSelect = document.getElementById('topicCode');
            
            topicSelect.innerHTML = '<option value="">Select Topic</option>';
            
            if (!courseCode) return;
            
            fetch(`get_topics.php?course_code=${courseCode}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
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
        }

        // Load students based on selected course
        function loadStudents() {
            const courseCode = document.getElementById('courseCode').value;
            const studentList = document.getElementById('studentList');
            const courseInfo = document.getElementById('courseInfo');
            const selectedCourse = document.getElementById('selectedCourse');
            const totalStudents = document.getElementById('totalStudents');
            
            if (!courseCode) {
                studentList.innerHTML = '<p style="text-align: center; color: #666; padding: 2rem;">Please select a course to view enrolled students</p>';
                courseInfo.style.display = 'none';
                return;
            }

            // Fetch students from database
            fetch(`get_students.php?course_code=${courseCode}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const students = data.students;
                        const courseName = document.querySelector(`option[value="${courseCode}"]`).textContent;
                        
                        selectedCourse.textContent = courseName;
                        totalStudents.textContent = students.length;
                        courseInfo.style.display = 'block';
                        
                        if (students.length === 0) {
                            studentList.innerHTML = '<p style="text-align: center; color: #666; padding: 2rem;">No students enrolled in this course</p>';
                            return;
                        }

                        studentList.innerHTML = students.map(student => `
                            <div class="student-item">
                                <input type="checkbox" class="student-checkbox" value="${student.student_id}" onchange="updateSelectedStudents()">
                                <div class="student-info">
                                    <div class="student-name">${student.first_name} ${student.last_name}</div>
                                    <div class="student-details">ID: ${student.student_id} | Email: ${student.email}</div>
                                </div>
                            </div>
                        `).join('');
                        
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

        // Toggle all students selection
        function toggleAllStudents() {
            const selectAll = document.getElementById('selectAll');
            const checkboxes = document.querySelectorAll('.student-checkbox');
            
            checkboxes.forEach(checkbox => {
                checkbox.checked = selectAll.checked;
            });
            
            updateSelectedStudents();
        }

        // Update selected students array
        function updateSelectedStudents() {
            const checkboxes = document.querySelectorAll('.student-checkbox:checked');
            selectedStudents = Array.from(checkboxes).map(cb => parseInt(cb.value));
            
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

        // Update selected count display
        function updateSelectedCount() {
            const selectedCount = document.getElementById('selectedCount');
            selectedCount.textContent = `${selectedStudents.length} selected`;
        }

        // File handling
        document.getElementById('fileInput').addEventListener('change', function(e) {
            handleFiles(e.target.files);
        });

        // Drag and drop functionality
        const attachmentArea = document.querySelector('.attachment-area');
        
        attachmentArea.addEventListener('dragover', function(e) {
            e.preventDefault();
            attachmentArea.classList.add('drag-over');
        });
        
        attachmentArea.addEventListener('dragleave', function(e) {
            e.preventDefault();
            attachmentArea.classList.remove('drag-over');
        });
        
        attachmentArea.addEventListener('drop', function(e) {
            e.preventDefault();
            attachmentArea.classList.remove('drag-over');
            handleFiles(e.dataTransfer.files);
        });

        function handleFiles(files) {
            const fileList = document.getElementById('fileList');
            
            Array.from(files).forEach(file => {
                if (file.size > 10 * 1024 * 1024) { // 10MB limit
                    showNotification(`File "${file.name}" is too large. Maximum size is 10MB.`, 'error');
                    return;
                }
                
                const fileId = Date.now() + Math.random();
                selectedFiles.push({ id: fileId, file: file });
                
                const fileItem = document.createElement('div');
                fileItem.className = 'file-item';
                fileItem.innerHTML = `
                    <div class="file-info">
                        <span>📄</span>
                        <span>${file.name}</span>
                        <span style="color: #666; font-size: 0.8rem;">(${(file.size / 1024).toFixed(1)} KB)</span>
                    </div>
                    <button type="button" class="file-remove" onclick="removeFile(${fileId})">Remove</button>
                `;
                
                fileList.appendChild(fileItem);
            });
        }

        function removeFile(fileId) {
            selectedFiles = selectedFiles.filter(f => f.id !== fileId);
            document.querySelector(`button[onclick="removeFile(${fileId})"]`).closest('.file-item').remove();
        }

        // Form submission
        document.getElementById('addTaskForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            if (selectedStudents.length === 0) {
                showNotification('Please select at least one student to assign the task.', 'error');
                return;
            }
            
            const formData = new FormData(this);
            
            // Add selected students to form data
            selectedStudents.forEach(studentId => {
                formData.append('students[]', studentId);
            });
            
            showNotification('Creating task assignment...', 'info');
            
            // Send data to server
            fetch('create_task.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification('Task assigned successfully to ' + selectedStudents.length + ' students!', 'success');
                    resetForm();
                } else {
                    showNotification('Error creating task: ' + data.error, 'error');
                }
            })
            .catch(error => {
                showNotification('Error creating task: ' + error.message, 'error');
            });
        });

        // Reset form
        function resetForm() {
            document.getElementById('addTaskForm').reset();
            selectedFiles = [];
            selectedStudents = [];
            document.getElementById('fileList').innerHTML = '';
            document.getElementById('studentList').innerHTML = '<p style="text-align: center; color: #666; padding: 2rem;">Please select a course to view enrolled students</p>';
            document.getElementById('courseInfo').style.display = 'none';
            document.getElementById('selectAll').checked = false;
            updateSelectedCount();
        }

        // Navigation functions
        function showPage(pageId) {
            document.querySelectorAll('.page-section').forEach(page => {
                page.classList.remove('active');
            });
            
            document.getElementById(pageId).classList.add('active');
            
            document.querySelectorAll('.sidebar-link').forEach(link => {
                link.classList.remove('active');
            });
            event.target.closest('.sidebar-link').classList.add('active');
            
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
        }

        // Initialize page
        document.addEventListener('DOMContentLoaded', function() {
            setMinDate();
            loadCourses();
            
            setTimeout(() => {
                showNotification('Ready to assign tasks to your students! 📚', 'success');
            }, 500);
        });

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

        // Auto-save form data to prevent data loss
        let autoSaveInterval;
        
        function startAutoSave() {
            autoSaveInterval = setInterval(() => {
                const formData = {
                    taskTitle: document.getElementById('taskTitle').value,
                    courseCode: document.getElementById('courseCode').value,
                    topicCode: document.getElementById('topicCode').value,
                    taskType: document.getElementById('taskType').value,
                    taskDescription: document.getElementById('taskDescription').value,
                    dueDate: document.getElementById('dueDate').value,
                    maxMarks: document.getElementById('maxMarks').value,
                    priority: document.getElementById('priority').value,
                    submissionFormat: document.getElementById('submissionFormat').value,
                    allowLateSubmission: document.getElementById('allowLateSubmission').value,
                    selectedStudents: selectedStudents
                };
                
                // In a real application, you might want to save this to localStorage
                // or send it to the server for auto-save functionality
                console.log('Auto-saved form data:', formData);
            }, 30000); // Auto-save every 30 seconds
        }

        // Start auto-save when user starts typing
        document.getElementById('addTaskForm').addEventListener('input', function() {
            if (!autoSaveInterval) {
                startAutoSave();
            }
        });

        // Clear auto-save interval when form is submitted or reset
        function clearAutoSave() {
            if (autoSaveInterval) {
                clearInterval(autoSaveInterval);
                autoSaveInterval = null;
            }
        }

        // Enhanced form validation
        function validateForm() {
            const requiredFields = [
                'taskTitle', 'courseCode', 'topicCode', 'taskType', 
                'taskDescription', 'dueDate', 'maxMarks', 'submissionFormat'
            ];
            
            let isValid = true;
            let firstErrorField = null;
            
            requiredFields.forEach(fieldId => {
                const field = document.getElementById(fieldId);
                if (!field.value.trim()) {
                    field.style.borderColor = '#f44336';
                    isValid = false;
                    if (!firstErrorField) {
                        firstErrorField = field;
                    }
                } else {
                    field.style.borderColor = '#e1e5e9';
                }
            });
            
            if (selectedStudents.length === 0) {
                showNotification('Please select at least one student to assign the task.', 'error');
                isValid = false;
            }
            
            const dueDate = new Date(document.getElementById('dueDate').value);
            const now = new Date();
            if (dueDate <= now) {
                document.getElementById('dueDate').style.borderColor = '#f44336';
                showNotification('Due date must be in the future.', 'error');
                isValid = false;
            }
            
            if (!isValid && firstErrorField) {
                firstErrorField.focus();
                firstErrorField.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
            
            return isValid;
        }

        // Update form submission to include validation
        document.getElementById('addTaskForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            if (!validateForm()) {
                return;
            }
            
            clearAutoSave();
            
            const formData = new FormData(this);
            
            // Add selected students to form data
            selectedStudents.forEach(studentId => {
                formData.append('students[]', studentId);
            });
            
            // Add files to form data
            selectedFiles.forEach(fileObj => {
                formData.append('attachments[]', fileObj.file);
            });
            
            showNotification('Creating task assignment...', 'info');
            
            // Simulate API call
            setTimeout(() => {
                showNotification('Task assigned successfully to ' + selectedStudents.length + ' students!', 'success');
                resetForm();
            }, 2000);
        });
    </script>
</body>
</html>