<?php
session_start();
require_once 'connection.php';

// Verify user is logged in and is an instructor
// if (!isset($_SESSION['user_id']) || $_SESSION['user']['role'] !== 'instructor') {
//     header("Location: account.php");
//     exit();
// }

$user_id = $_SESSION['user_id'];
$user = null;

try {
    // Get user data
    $stmt = $pdo->prepare("SELECT * FROM accounts WHERE accounts_id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        session_destroy();
        header("Location: account.php");
        exit();
    }

    // Get submissions to grade
    $stmt = $pdo->prepare("
        SELECT 
            s.id,
            t.task_title,
            tp.topic_code,
            tp.topic_title,
            a.accounts_id AS student_id,
            a.sur_name,
            a.other_name,
            st.program,
            s.submitted_at,
            s.status,
            s.grade,
            s.letter_grade,
            s.feedback,
            s.submission_text,
            t.max_marks
        FROM submissions s
        JOIN tasks t ON s.task_id = t.id
        JOIN topics tp ON t.topic_code = tp.topic_code
        JOIN accounts a ON s.student_id = a.accounts_id
        JOIN students st ON a.accounts_id = st.accounts_id
        WHERE t.instructor_id = ?
        ORDER BY s.submitted_at DESC
    ");
    $stmt->execute([$user_id]);
    $submissions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get statistics
    $stats = $pdo->prepare("
        SELECT 
            COUNT(*) AS total,
            SUM(CASE WHEN s.status = 'pending' THEN 1 ELSE 0 END) AS pending,
            SUM(CASE WHEN s.status = 'graded' THEN 1 ELSE 0 END) AS graded,
            AVG(s.grade) AS average_grade
        FROM submissions s
        JOIN tasks t ON s.task_id = t.id
        WHERE t.instructor_id = ?
    ");
    $stats->execute([$user_id]);
    $statsData = $stats->fetch(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Database error in grade.php: " . $e->getMessage());
    $submissions = [];
    $statsData = [];
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
    <title>CodeLab - Grade Tasks</title>
    <link rel="stylesheet" href="dasboard.css">
    <style>
        /* (Keep all your existing CSS styles) */
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
                    <a href="instructorDashboard.php" class="sidebar-link">
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
                    <a href="addTask.php" class="sidebar-link">
                        <span class="sidebar-icon">📚</span>
                        Add Task
                    </a>
                </li>
                <li class="sidebar-item">
                    <a href="grade.php" class="sidebar-link active">
                        <span class="sidebar-icon">📈</span>
                        Grade
                    </a>
                </li>
            </ul>
        </nav>

        <main class="content-area">
            <div id="grade" class="page-section active">
                <div class="form-container">
                    <div class="section-card">
                        <h2 class="section-title">✔ Grade Student Submissions</h2>
                        
                        <!-- Statistics Overview -->
                        <div class="stats-grid">
                            <div class="stat-card">
                                <div class="stat-number" id="totalSubmissions"><?= $statsData['total'] ?? 0 ?></div>
                                <div class="stat-label">Total Submissions</div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-number" id="pendingGrades"><?= $statsData['pending'] ?? 0 ?></div>
                                <div class="stat-label">Pending Grades</div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-number" id="gradedSubmissions"><?= $statsData['graded'] ?? 0 ?></div>
                                <div class="stat-label">Graded</div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-number" id="averageGrade"><?= round($statsData['average_grade'] ?? 0) ?>%</div>
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
                                    <?php
                                    // Get unique topics
                                    $topics = [];
                                    foreach ($submissions as $sub) {
                                        $key = $sub['topic_code'];
                                        if (!isset($topics[$key])) {
                                            $topics[$key] = $sub['topic_title'];
                                        }
                                    }
                                    foreach ($topics as $code => $title) {
                                        echo '<option value="'.htmlspecialchars($code).'">'.htmlspecialchars("$title ($code)").'</option>';
                                    }
                                    ?>
                                </select>
                                <select id="filterStatus" onchange="filterSubmissions()">
                                    <option value="">All Status</option>
                                    <option value="pending">Pending</option>
                                    <option value="graded">Graded</option>
                                    <option value="late">Late Submission</option>
                                </select>
                                <select id="filterProgram" onchange="filterSubmissions()">
                                    <option value="">All Programs</option>
                                    <?php
                                    // Get unique programs
                                    $programs = [];
                                    foreach ($submissions as $sub) {
                                        if (!in_array($sub['program'], $programs)) {
                                            $programs[] = $sub['program'];
                                            echo '<option value="'.htmlspecialchars($sub['program']).'">'.htmlspecialchars($sub['program']).'</option>';
                                        }
                                    }
                                    ?>
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
                                            <th>Task</th>
                                            <th>Topic</th>
                                            <th>Program</th>
                                            <th>Submitted</th>
                                            <th>Status</th>
                                            <th>Grade</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody id="submissionsBody">
                                        <?php foreach ($submissions as $sub): ?>
                                        <tr data-id="<?= $sub['id'] ?>">
                                            <td><?= htmlspecialchars($sub['sur_name'] . ' ' . $sub['other_name']) ?></td>
                                            <td><?= htmlspecialchars($sub['student_id']) ?></td>
                                            <td><?= htmlspecialchars($sub['task_title']) ?></td>
                                            <td><?= htmlspecialchars($sub['topic_title'] . ' (' . $sub['topic_code'] . ')') ?></td>
                                            <td><?= htmlspecialchars($sub['program']) ?></td>
                                            <td><?= date('M j, Y g:i a', strtotime($sub['submitted_at'])) ?></td>
                                            <td><span class="status-badge status-<?= $sub['status'] ?>"><?= $sub['status'] ?></span></td>
                                            <td><?= $sub['grade'] ? $sub['grade'] . '%' : '-' ?></td>
                                            <td>
                                                <button class="btn-success" onclick="openGradeModal(<?= $sub['id'] ?>)">
                                                    <?= $sub['status'] === 'graded' ? 'View/Edit' : 'Grade' ?>
                                                </button>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
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
                <!-- Will be populated by JavaScript -->
            </div>
            
            <div class="code-preview" id="codePreview">
                <!-- Will be populated by JavaScript -->
            </div>
            
            <form class="grade-form" id="gradeForm">
                <input type="hidden" id="submissionId">
                <div class="grade-input">
                    <div class="form-group">
                        <label for="gradeScore">Grade (0-<?= $sub['max_marks'] ?? 100 ?>)</label>
                        <input type="number" id="gradeScore" min="0" max="<?= $sub['max_marks'] ?? 100 ?>" required>
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
        // Store submissions data for JavaScript
        const submissionsData = <?= json_encode($submissions) ?>;
        const statsData = <?= json_encode($statsData) ?>;

        function openGradeModal(submissionId) {
            const submission = submissionsData.find(s => s.id == submissionId);
            if (!submission) return;

            const modal = document.getElementById('gradeModal');
            const details = document.getElementById('submissionDetails');
            const codePreview = document.getElementById('codePreview');
            
            // Populate submission details
            details.innerHTML = `
                <h4>Student: ${submission.sur_name} ${submission.other_name} (${submission.student_id})</h4>
                <p><strong>Task:</strong> ${submission.task_title}</p>
                <p><strong>Topic:</strong> ${submission.topic_title} (${submission.topic_code})</p>
                <p><strong>Program:</strong> ${submission.program}</p>
                <p><strong>Submitted:</strong> ${new Date(submission.submitted_at).toLocaleString()}</p>
                <p><strong>Status:</strong> <span class="status-badge status-${submission.status}">${submission.status}</span></p>
                <p><strong>Max Marks:</strong> ${submission.max_marks}</p>
            `;
            
            // Show code preview
            codePreview.textContent = submission.submission_text || 'No submission content available';
            
            // Populate form
            document.getElementById('submissionId').value = submission.id;
            document.getElementById('gradeScore').value = submission.grade || '';
            document.getElementById('gradeScore').max = submission.max_marks;
            document.getElementById('letterGrade').value = submission.letter_grade || '';
            document.getElementById('feedback').value = submission.feedback || '';
            
            modal.style.display = 'block';
        }

        function closeGradeModal() {
            document.getElementById('gradeModal').style.display = 'none';
        }

        // Auto-calculate letter grade based on score
        document.getElementById('gradeScore').addEventListener('input', function() {
            const score = parseInt(this.value);
            const maxMarks = parseInt(this.max);
            const percentage = (score / maxMarks) * 100;
            const letterGradeSelect = document.getElementById('letterGrade');
            
            if (percentage >= 90) letterGradeSelect.value = 'A+';
            else if (percentage >= 80) letterGradeSelect.value = 'A';
            else if (percentage >= 75) letterGradeSelect.value = 'B+';
            else if (percentage >= 70) letterGradeSelect.value = 'B';
            else if (percentage >= 65) letterGradeSelect.value = 'C+';
            else if (percentage >= 60) letterGradeSelect.value = 'C';
            else if (percentage >= 50) letterGradeSelect.value = 'D';
            else if (!isNaN(percentage)) letterGradeSelect.value = 'F';
        });

        // Handle grade form submission
        document.getElementById('gradeForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const submissionId = document.getElementById('submissionId').value;
            const grade = document.getElementById('gradeScore').value;
            const letterGrade = document.getElementById('letterGrade').value;
            const feedback = document.getElementById('feedback').value;
            
            const formData = new FormData();
            formData.append('submission_id', submissionId);
            formData.append('grade', grade);
            formData.append('letter_grade', letterGrade);
            formData.append('feedback', feedback);
            
            fetch('save_grade.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update the table row
                    const row = document.querySelector(`tr[data-id="${submissionId}"]`);
                    if (row) {
                        row.cells[6].innerHTML = `<span class="status-badge status-graded">graded</span>`;
                        row.cells[7].textContent = `${grade}%`;
                    }
                    
                    closeGradeModal();
                    showNotification('Grade saved successfully!', 'success');
                    // Refresh the page to update stats
                    setTimeout(() => location.reload(), 1000);
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
                const topic = row.cells[3].textContent;
                const program = row.cells[4].textContent;
                const status = row.cells[6].textContent.toLowerCase();
                
                const matchesSearch = studentName.includes(searchTerm) || studentId.includes(searchTerm);
                const matchesTopic = !topicFilter || topic.includes(topicFilter);
                const matchesStatus = !statusFilter || status.includes(statusFilter);
                const matchesProgram = !programFilter || program.includes(programFilter);
                
                row.style.display = matchesSearch && matchesTopic && matchesStatus && matchesProgram ? '' : 'none';
            });
        }

        // Navigation functions (keep your existing ones)
    </script>
</body>
</html>