<?php
session_start(); 
 require_once 'connection.php';

 $user_id = $_SESSION['user_id'];
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

$full_name = ($user['first_name'] ?? 'Instructor') . ' ' . ($user['last_name'] ?? '');
$initials = getUserInitials($full_name);
 ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CodeLab - Dashboard</title>
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
                <button onclick="logout()" style="background: black; border: 1px solid white; color: white; padding: 0.5rem 1rem; border-radius: 4px; cursor: pointer; color: white">Logout</button>
            </div>
        </div>
    </div>

    <div class="overlay" onclick="closeSidebar()"></div>

    <div class="main-container">
        <nav class="sidebar" id="sidebar">
            <ul class="sidebar-menu">
                <li class="sidebar-item">
                    <a href="instructorDashboard.php" class="sidebar-link " onclick="showPage('profile')">
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
                    <a href="addtopic.php" class="sidebar-link active" onclick="showPage('add-topic')">
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
                    <a href="grade.php" class="sidebar-link" onclick="showPage('add-task')">
                        <span class="sidebar-icon">📈</span>
                        Grade
                    </a>
                </li>
            </ul>
        </nav>

        <main class="content-area">
            <div id="add-topic" class="page-section active ">
                <div class="form-container">
                    <div class="section-card">
                        <h2 class="section-title">➕ Add New Task Topic</h2>
                        
                        <form id="addTopicForm">
                            <div class="form-section">
                                <h3>📝 Basic Information</h3>
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="topicTitle">Topic Title *</label>
                                        <input type="text" id="topicTitle" name="topicTitle" required placeholder="Enter topic title">
                                    </div>
                                    <div class="form-group">
                                        <label for="topicCode">Topic Code *</label>
                                        <input type="text" id="topicCode" name="topicCode" required placeholder="e.g., HTML01, CSS02">
                                    </div>
                                </div>
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="courseCode">Course Code *</label>
                                        <select id="courseCode" name="courseCode" required>
                                            <option value="">Select Course</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label for="category">Program *</label>
                                        <select id="category" name="category" required>
                                           <option value="">Select Program</option>
                                            <option value="Computer Science">Computer Science</option>
                                            <option value="Information Technology">Information Technology</option>
                                            <option value="Software Engineering">Software Engineering</option>
                                            <option value="Data Science">Data Science</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label for="topicDescription">Topic Description *</label>
                                    <textarea id="topicDescription" name="topicDescription" required placeholder="Provide a detailed description of the topic..."></textarea>
                                </div>
                            </div>
                            <div class="form-actions">
                                <button type="submit" class="btn-primary">Create Task Topic</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </main>       
    </div>
        <script>
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

        // Handle form submission
        document.getElementById('addTopicForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            showNotification('Creating topic...', 'info');
            
            fetch('create_topic.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification('Topic created successfully!', 'success');
                    this.reset();
                } else {
                    showNotification('Error creating topic: ' + data.error, 'error');
                }
            })
            .catch(error => {
                showNotification('Error creating topic: ' + error.message, 'error');
            });
        });

        // Navigation functions
        function showPage(pageId) {
            // Hide all pages
            document.querySelectorAll('.page-section').forEach(page => {
                page.classList.remove('active');
            });
            
            // Show selected page
            document.getElementById(pageId).classList.add('active');
            
            // Update active sidebar link
            document.querySelectorAll('.sidebar-link').forEach(link => {
                link.classList.remove('active');
            });
            event.target.closest('.sidebar-link').classList.add('active');
            
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

        function editProfile() {
            showNotification('Opening profile editor...', 'info');
            // This would open a profile editing modal or navigate to edit page
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

        // Welcome animation
        document.addEventListener('DOMContentLoaded', function() {
            loadCourses();
            
            setTimeout(() => {
                showNotification('Ready to create new topics! ➕', 'success');
            }, 500);
        });
    </script>
</body>
</html>