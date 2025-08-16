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
$stmt = $pdo->prepare("SELECT * FROM accounts WHERE accounts_id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    session_destroy();
    header("Location: account.php");
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $topicTitle = trim($_POST['topicTitle']);
    $topicCode = trim($_POST['topicCode']);
    $courseCode = trim($_POST['courseCode']);
    $description = trim($_POST['topicDescription']);
    
    // Validate inputs
    if (empty($topicTitle) || empty($topicCode) || empty($courseCode) || empty($description)) {
        $error = 'All fields are required';
    } else {
        try {
            // Check if topic code already exists
            $stmt = $pdo->prepare("SELECT * FROM topics WHERE topic_code = ?");
            $stmt->execute([$topicCode]);
            
            if ($stmt->rowCount() > 0) {
                $error = 'Topic code already exists';
            } else {
                // Insert new topic
                $stmt = $pdo->prepare("INSERT INTO topics (topic_code, topic_title, course_code, description, instructor_id) 
                                      VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$topicCode, $topicTitle, $courseCode, $description, $user_id]);
                
                $success = 'Topic created successfully!';
                // Clear form
                $_POST = array();
            }
        } catch (PDOException $e) {
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

// Get courses taught by this instructor
$courses = [];
try {
    $stmt = $pdo->prepare("SELECT course_code, course_name FROM courses WHERE instructor_id = ?");
    $stmt->execute([$user_id]);
    $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = 'Error loading courses: ' . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CodeLab - Add Topic</title>
    <link rel="stylesheet" href="dasboard.css">
    <style>
        .form-container {
            max-width: 800px;
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
                    <a href="addtopic.php" class="sidebar-link active">
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
                    <a href="grade.php" class="sidebar-link">
                        <span class="sidebar-icon">📈</span>
                        Grade
                    </a>
                </li>
            </ul>
        </nav>

        <main class="content-area">
            <div id="add-topic" class="page-section active">
                <div class="form-container">
                    <div class="section-card">
                        <h2 class="section-title">➕ Add New Topic</h2>
                        
                        <?php if ($error): ?>
                            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
                        <?php endif; ?>
                        
                        <?php if ($success): ?>
                            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
                        <?php endif; ?>
                        
                        <form method="POST" id="addTopicForm">
                            <div class="form-section">
                                <h3>📝 Basic Information</h3>
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="topicTitle">Topic Title *</label>
                                        <input type="text" id="topicTitle" name="topicTitle" required 
                                               value="<?= htmlspecialchars($_POST['topicTitle'] ?? '') ?>" 
                                               placeholder="Enter topic title">
                                    </div>
                                    <div class="form-group">
                                        <label for="topicCode">Topic Code *</label>
                                        <input type="text" id="topicCode" name="topicCode" required 
                                               value="<?= htmlspecialchars($_POST['topicCode'] ?? '') ?>" 
                                               placeholder="e.g., HTML01, CSS02">
                                    </div>
                                </div>
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="courseCode">Course *</label>
                                        <select id="courseCode" name="courseCode" required>
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
                                
                                <div class="form-group">
                                    <label for="topicDescription">Topic Description *</label>
                                    <textarea id="topicDescription" name="topicDescription" required 
                                              placeholder="Provide a detailed description of the topic..."><?= 
                                              htmlspecialchars($_POST['topicDescription'] ?? '') ?></textarea>
                                </div>
                            </div>
                            
                            <div class="form-actions">
                                <button type="submit" class="btn-primary">Create Topic</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </main>       
    </div>

    <script>
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

        // Form validation
        document.getElementById('addTopicForm').addEventListener('submit', function(e) {
            const topicCode = document.getElementById('topicCode').value;
            const courseCode = document.getElementById('courseCode').value;
            
            if (!/^[A-Za-z0-9]+$/.test(topicCode)) {
                alert('Topic code should only contain letters and numbers');
                e.preventDefault();
                return;
            }
            
            if (courseCode === '') {
                alert('Please select a course');
                e.preventDefault();
                return;
            }
        });

        // Auto-generate topic code based on title
        document.getElementById('topicTitle').addEventListener('input', function() {
            const title = this.value;
            const codeInput = document.getElementById('topicCode');
            
            if (codeInput.value === '') {
                // Generate a simple code from the title (first 3 letters + random 2 digits)
                const prefix = title.substring(0, 3).toUpperCase().replace(/[^A-Z]/g, '');
                if (prefix.length > 0) {
                    const randomDigits = Math.floor(Math.random() * 90) + 10;
                    codeInput.value = prefix + randomDigits;
                }
            }
        });
    </script>
</body>
</html>