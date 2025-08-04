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
    <title>CodeLab - Grade Tasks</title>
    <link rel="stylesheet" href="dasboard.css">
    <style>
        .form-container {
            max-width: 1200px;
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

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 1rem 2rem;
            border-radius: 8px;
            font-size: 1rem;
            cursor: pointer;
            transition: transform 0.3s ease;
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

        .btn-success {
            background: #28a745;
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            font-size: 0.9rem;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .btn-success:hover {
            background: #218838;
        }

        .btn-danger {
            background: #dc3545;
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            font-size: 0.9rem;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .btn-danger:hover {
            background: #c82333;
        }

        .form-actions {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
            margin-top: 2rem;
        }

        .submissions-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }

        .submissions-table th,
        .submissions-table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #e1e5e9;
        }

        .submissions-table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #333;
        }

        .submissions-table tr:hover {
            background: #f8f9fa;
        }

        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
            text-transform: uppercase;
        }

        .status-pending {
            background: #fff3cd;
            color: #856404;
        }

        .status-graded {
            background: #d4edda;
            color: #155724;
        }

        .status-late {
            background: #f8d7da;
            color: #721c24;
        }

        .search-filter {
            display: flex;
            gap: 1rem;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
            align-items: center;
        }

        .search-filter input,
        .search-filter select {
            padding: 0.5rem;
            border: 2px solid #e1e5e9;
            border-radius: 6px;
            font-size: 0.9rem;
        }

        .search-filter input {
            flex: 1;
            min-width: 200px;
        }

        .grade-modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }

        .grade-modal-content {
            background-color: white;
            margin: 5% auto;
            padding: 2rem;
            border-radius: 12px;
            width: 90%;
            max-width: 800px;
            max-height: 80vh;
            overflow-y: auto;
        }

        .grade-modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #e1e5e9;
        }

        .close-modal {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: #666;
        }

        .close-modal:hover {
            color: #000;
        }

        .submission-details {
            margin-bottom: 1.5rem;
        }

        .submission-details h4 {
            margin-bottom: 0.5rem;
            color: #333;
        }

        .submission-details p {
            margin-bottom: 0.5rem;
            color: #666;
        }

        .code-preview {
            background: #f8f9fa;
            border: 1px solid #e1e5e9;
            border-radius: 8px;
            padding: 1rem;
            margin: 1rem 0;
            font-family: 'Courier New', monospace;
            white-space: pre-wrap;
            max-height: 300px;
            overflow-y: auto;
        }

        .grade-form {
            display: grid;
            gap: 1rem;
        }

        .grade-input {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            text-align: center;
        }

        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 0.5rem;
        }

        .stat-label {
            color: #666;
            font-size: 0.9rem;
        }

        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .search-filter {
                flex-direction: column;
                align-items: stretch;
            }
            
            .submissions-table {
                font-size: 0.9rem;
            }
            
            .grade-modal-content {
                margin: 2% auto;
                width: 95%;
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
                <button onclick="logout()" style="background: black; border: 1px solid white; color: white; padding: 0.5rem 1rem; border-radius: 4px; cursor: pointer;">Logout</button>
            </div>
        </div>
    </div>

    <div class="overlay" onclick="closeSidebar()"></div>

    <div class="main-container">
        <nav class="sidebar" id="sidebar">
            <ul class="sidebar-menu">
                <li class="sidebar-item">
                    <a href="instructorDashboard.php" class="sidebar-link" onclick="showPage('profile')">
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
                    <a href="addTask.php" class="sidebar-link" onclick="showPage('add-task')">
                        <span class="sidebar-icon">📚</span>
                        Add Task
                    </a>
                </li>
                <li class="sidebar-item">
                    <a href="#" class="sidebar-link active" onclick="showPage('grade')">
                        <span class="sidebar-icon">📈</span>
                        Grade
                    </a>
                </li>
            </ul>
        </nav>

        <main class="content-area">
            <!-- Grade Page -->
            <div id="grade" class="page-section active">
                <div class="form-container">
                    <div class="section-card">
                        <h2 class="section-title">✔ Grade Student Submissions</h2>
                        
                        <!-- Statistics Overview -->
                        <div class="stats-grid">
                            <div class="stat-card">
                                <div class="stat-number" id="totalSubmissions">12</div>
                                <div class="stat-label">Total Submissions</div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-number" id="pendingGrades">8</div>
                                <div class="stat-label">Pending Grades</div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-number" id="gradedSubmissions">4</div>
                                <div class="stat-label">Graded</div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-number" id="averageGrade">85%</div>
                                <div class="stat-label">Average Grade</div>
                            </div>
                        </div>

                        <!-- Filter and Search -->
                        <div class="form-section">
                            <h3>🔍 Filter Submissions</h3>
                            <div class="search-filter">
                                <input type="text" id="searchStudent" placeholder="Search by student name or ID..." onkeyup="filterSubmissions()">
                                <select id="filterTopic" onchange="filterSubmissions()">
                                    <option value="">All Topics</option>
                                    <option value="HTML01">HTML Basics (HTML01)</option>
                                    <option value="CSS02">CSS Styling (CSS02)</option>
                                    <option value="JS03">JavaScript Fundamentals (JS03)</option>
                                    <option value="PHP04">PHP Backend (PHP04)</option>
                                </select>
                                <select id="filterStatus" onchange="filterSubmissions()">
                                    <option value="">All Status</option>
                                    <option value="pending">Pending</option>
                                    <option value="graded">Graded</option>
                                    <option value="late">Late Submission</option>
                                </select>
                                <select id="filterProgram" onchange="filterSubmissions()">
                                    <option value="">All Programs</option>
                                    <option value="Computer Science">Computer Science</option>
                                    <option value="Information Technology">Information Technology</option>
                                    <option value="Software Engineering">Software Engineering</option>
                                </select>
                            </div>
                        </div>

                        <!-- Submissions Table -->
                        <div class="form-section">
                            <h3>📋 Student Submissions</h3>
                            <div style="overflow-x: auto;">
                                <table class="submissions-table" id="submissionsTable">
                                    <thead>
                                        <tr>
                                            <th>Student</th>
                                            <th>Student ID</th>
                                            <th>Topic</th>
                                            <th>Program</th>
                                            <th>Submitted</th>
                                            <th>Status</th>
                                            <th>Grade</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody id="submissionsBody">
                                        <!-- Sample data will be populated here -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Grade Modal -->
    <div id="gradeModal" class="grade-modal">
        <div class="grade-modal-content">
            <div class="grade-modal-header">
                <h3>Grade Submission</h3>
                <button class="close-modal" onclick="closeGradeModal()">&times;</button>
            </div>
            
            <div class="submission-details" id="submissionDetails">
                <!-- Submission details will be populated here -->
            </div>
            
            <div class="code-preview" id="codePreview">
                <!-- Code preview will be shown here -->
            </div>
            
            <form class="grade-form" id="gradeForm">
                <div class="grade-input">
                    <div class="form-group">
                        <label for="gradeScore">Grade (0-100)</label>
                        <input type="number" id="gradeScore" min="0" max="100" required>
                    </div>
                    <div class="form-group">
                        <label for="letterGrade">Letter Grade</label>
                        <select id="letterGrade" required>
                            <option value="">Select Grade</option>
                            <option value="A+">A+ (90-100)</option>
                            <option value="A">A (80-89)</option>
                            <option value="B+">B+ (75-79)</option>
                            <option value="B">B (70-74)</option>
                            <option value="C+">C+ (65-69)</option>
                            <option value="C">C (60-64)</option>
                            <option value="D">D (50-59)</option>
                            <option value="F">F (0-49)</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="feedback">Feedback</label>
                    <textarea id="feedback" placeholder="Provide detailed feedback on the submission..."></textarea>
                </div>
                
                <div class="form-actions">
                    <button type="button" class="btn-secondary" onclick="closeGradeModal()">Cancel</button>
                    <button type="submit" class="btn-primary">Save Grade</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Sample data for demonstrations
        let submissionsData = [];
        let statsData = {};

        // Initialize page
        document.addEventListener('DOMContentLoaded', function() {
            loadSubmissions();
        });
        
        function loadSubmissions() {
            fetch('get_submissions.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        submissionsData = data.submissions.map(submission => ({
                            id: submission.id,
                            studentName: `${submission.first_name} ${submission.last_name}`,
                            studentId: submission.student_id,
                            topic: submission.topic_code,
                            topicTitle: submission.topic_title,
                            program: submission.program,
                            submittedDate: submission.submitted_at,
                            status: submission.status,
                            grade: submission.grade,
                            letterGrade: submission.letter_grade,
                            code: submission.submission_text || 'No code submitted',
                            feedback: submission.feedback || '',
                            taskTitle: submission.task_title,
                            maxMarks: submission.max_marks
                        }));
                        
                        statsData = data.stats;
                        populateSubmissionsTable();
                        updateStats();
                    } else {
                        showNotification('Error loading submissions: ' + data.error, 'error');
                    }
                })
                .catch(error => {
                    showNotification('Error loading submissions: ' + error.message, 'error');
                });
        }

        function populateSubmissionsTable() {
            const tbody = document.getElementById('submissionsBody');
            tbody.innerHTML = '';

            submissionsData.forEach(submission => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${submission.studentName}</td>
                    <td>${submission.studentId}</td>
                    <td>${submission.topicTitle} (${submission.topic})</td>
                    <td>${submission.program}</td>
                    <td>${formatDate(submission.submittedDate)}</td>
                    <td><span class="status-badge status-${submission.status}">${submission.status}</span></td>
                    <td>${submission.grade ? submission.grade + '%' : '-'}</td>
                    <td>
                        <button class="btn-success" onclick="openGradeModal(${submission.id})">
                            ${submission.status === 'graded' ? 'View/Edit' : 'Grade'}
                        </button>
                    </td>
                `;
                tbody.appendChild(row);
            });
        }

        function openGradeModal(submissionId) {
            const submission = submissionsData.find(s => s.id === submissionId);
            if (!submission) return;

            const modal = document.getElementById('gradeModal');
            const details = document.getElementById('submissionDetails');
            const codePreview = document.getElementById('codePreview');
            
            // Populate submission details
            details.innerHTML = `
                <h4>Student: ${submission.studentName} (${submission.studentId})</h4>
                <p><strong>Topic:</strong> ${submission.topicTitle} (${submission.topic})</p>
                <p><strong>Program:</strong> ${submission.program}</p>
                <p><strong>Submitted:</strong> ${formatDate(submission.submittedDate)}</p>
                <p><strong>Status:</strong> <span class="status-badge status-${submission.status}">${submission.status}</span></p>
            `;
            
            // Show code preview
            codePreview.textContent = submission.code;
            
            // Populate form if already graded
            if (submission.grade) {
                document.getElementById('gradeScore').value = submission.grade;
                document.getElementById('letterGrade').value = submission.letterGrade || '';
                document.getElementById('feedback').value = submission.feedback || '';
            } else {
                // Clear form for new grading
                document.getElementById('gradeScore').value = '';
                document.getElementById('letterGrade').value = '';
                document.getElementById('feedback').value = '';
            }
            
            // Store current submission ID for form submission
            document.getElementById('gradeForm').dataset.submissionId = submissionId;
            
            modal.style.display = 'block';
        }

        function closeGradeModal() {
            document.getElementById('gradeModal').style.display = 'none';
        }

        // Auto-calculate letter grade based on score
        document.getElementById('gradeScore').addEventListener('input', function() {
            const score = parseInt(this.value);
            const letterGradeSelect = document.getElementById('letterGrade');
            
            if (score >= 90) letterGradeSelect.value = 'A+';
            else if (score >= 80) letterGradeSelect.value = 'A';
            else if (score >= 75) letterGradeSelect.value = 'B+';
            else if (score >= 70) letterGradeSelect.value = 'B';
            else if (score >= 65) letterGradeSelect.value = 'C+';
            else if (score >= 60) letterGradeSelect.value = 'C';
            else if (score >= 50) letterGradeSelect.value = 'D';
            else letterGradeSelect.value = 'F';
        });

        // Handle grade form submission
        document.getElementById('gradeForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const submissionId = parseInt(this.dataset.submissionId);
            const score = parseInt(document.getElementById('gradeScore').value);
            const letterGrade = document.getElementById('letterGrade').value;
            const feedback = document.getElementById('feedback').value;
            
            const formData = new FormData();
            formData.append('submission_id', submissionId);
            formData.append('grade', score);
            formData.append('letter_grade', letterGrade);
            formData.append('feedback', feedback);
            
            fetch('save_grade.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update local data
                    const submission = submissionsData.find(s => s.id === submissionId);
                    if (submission) {
                        submission.grade = score;
                        submission.letterGrade = letterGrade;
                        submission.feedback = feedback;
                        submission.status = 'graded';
                    }
                    
                    // Refresh table and stats
                    populateSubmissionsTable();
                    updateStats();
                    
                    closeGradeModal();
                    showNotification('Grade saved successfully!', 'success');
                } else {
                    showNotification('Error saving grade: ' + data.error, 'error');
                }
            })
            .catch(error => {
                showNotification('Error saving grade: ' + error.message, 'error');
            });
        });

        function filterSubmissions() {
            const searchTerm = document.getElementById('searchStudent').value.toLowerCase();
            const topicFilter = document.getElementById('filterTopic').value;
            const statusFilter = document.getElementById('filterStatus').value;
            const programFilter = document.getElementById('filterProgram').value;
            
            const rows = document.querySelectorAll('#submissionsTable tbody tr');
            
            rows.forEach(row => {
                const studentName = row.cells[0].textContent.toLowerCase();
                const studentId = row.cells[1].textContent.toLowerCase();
                const topic = row.cells[2].textContent;
                const program = row.cells[3].textContent;
                const status = row.cells[5].textContent.toLowerCase();
                
                const matchesSearch = studentName.includes(searchTerm) || studentId.includes(searchTerm);
                const matchesTopic = !topicFilter || topic.includes(topicFilter);
                const matchesStatus = !statusFilter || status.includes(statusFilter);
                const matchesProgram = !programFilter || program.includes(programFilter);
                
                row.style.display = matchesSearch && matchesTopic && matchesStatus && matchesProgram ? '' : 'none';
            });
        }

        function updateStats() {
            if (statsData) {
                document.getElementById('totalSubmissions').textContent = statsData.total || 0;
                document.getElementById('pendingGrades').textContent = statsData.pending || 0;
                document.getElementById('gradedSubmissions').textContent = statsData.graded || 0;
                document.getElementById('averageGrade').textContent = (statsData.average_grade || 0) + '%';
            }
        }

        function formatDate(dateString) {
            return new Date(dateString).toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'short',
                day: 'numeric'
            });
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

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('gradeModal');
            if (event.target === modal) {
                closeGradeModal();
            }
        }

        // Welcome message
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(() => {
                showNotification('Grade management system loaded! 📝', 'success');
            }, 500);
        });
    </script>
</body>
</html>