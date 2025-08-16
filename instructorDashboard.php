<?php
session_start();
require_once 'connection.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: account.php");
    exit();
}

// Get user data
$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT * FROM accounts WHERE accounts_id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    session_destroy();
    header("Location: account.php");
    exit();
}

function getUserInitials($fullName) {
    if (empty($fullName)) return 'U';
    $words = explode(' ', trim($fullName));
    if (count($words) >= 2) {
        return strtoupper(substr($words[0], 0, 1) . substr($words[1], 0, 1));
    }
    return strtoupper(substr($fullName, 0, 2));
}

// Get user data
$full_name = ($user['sur_name'] ?? '') . ' ' . ($user['other_name'] ?? '');
$initials = getUserInitials($full_name);
$username = $user['email'] ?? '';
$email = $user['email'] ?? '';
$phone = $user['phone'] ?? '';

// Get stats for dashboard
$students_count = $pdo->query("SELECT COUNT(*) FROM accounts WHERE role = 'student'")->fetchColumn();
$courses_count = $pdo->query("SELECT COUNT(*) FROM courses WHERE instructor_id = '$user_id'")->fetchColumn();
$tasks_count = $pdo->query("SELECT COUNT(*) FROM tasks WHERE instructor_id = '$user_id'")->fetchColumn();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E-MANUAL - Dashboard</title>
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

        /* Page sections */
        .page-section {
            display: none;
        }
        .page-section.active {
            display: block;
        }

        /* Profile styles */
        .profile-section {
            padding: 2rem;
        }
        .profile-header {
            display: flex;
            align-items: center;
            margin-bottom: 2rem;
            gap: 1.5rem;
        }
        .profile-avatar-large {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            font-weight: bold;
        }
        .profile-info h1 {
            margin: 0;
            font-size: 1.8rem;
            color: #333;
        }
        .profile-info p {
            margin: 0.5rem 0 0;
            color: #666;
        }
        .edit-profile-btn {
            margin-left: auto;
            background: #667eea;
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            cursor: pointer;
            transition: background 0.3s ease;
        }
        .edit-profile-btn:hover {
            background: #5a6ec7;
        }
        .profile-details {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }
        .detail-group {
            margin-bottom: 2rem;
        }
        .detail-group h3 {
            color: #333;
            margin-bottom: 1.5rem;
            font-size: 1.3rem;
            border-bottom: 2px solid #f0f0f0;
            padding-bottom: 0.75rem;
        }
        .detail-item {
            display: flex;
            margin-bottom: 1rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #f5f5f5;
        }
        .detail-label {
            font-weight: 500;
            color: #555;
            width: 150px;
        }
        .detail-value {
            color: #333;
            flex: 1;
        }

        /* Dashboard stats */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-top: 2rem;
        }
        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            text-align: center;
        }
        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 0.5rem;
        }
        .stat-label {
            color: #666;
            font-size: 1rem;
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
                <span>Welcome back,<?= htmlspecialchars($full_name) ?></span>
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
                    <a href="#profile" class="sidebar-link" onclick="showPage('profile', event)">
                        <span class="sidebar-icon">👤</span>
                        My Profile
                    </a>
                </li>
                <li class="sidebar-item">
                    <a href="#dashboard" class="sidebar-link" onclick="showPage('dashboard', event)">
                        <span class="sidebar-icon">📊</span>
                        Dashboard
                    </a>
                </li>
                <li class="sidebar-item">
                    <a href="addTopic.php" class="sidebar-link">
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
            <!-- Profile Page -->
            <div id="profile" class="page-section active">
                <div class="section-card">
                    <div class="profile-section">
                        <div class="profile-header">
                            <div class="profile-avatar-large"><?= htmlspecialchars($initials) ?></div>
                            <div class="profile-info">
                                <h1><?= htmlspecialchars($full_name) ?></h1>
                                <p><?= htmlspecialchars($user['role']) ?></p>
                            </div>
                            <button class="edit-profile-btn" onclick="editProfile()">Edit Profile</button>
                        </div>

                        <div class="profile-details">
                            <div class="detail-group">
                                <h3>Personal Information</h3>
                                <div class="detail-item">
                                    <span class="detail-label">Email</span>
                                    <span class="detail-value"><?= htmlspecialchars($email) ?></span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Phone</span>
                                    <span class="detail-value"><?= htmlspecialchars($phone) ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Dashboard Page -->
            <div id="dashboard" class="page-section">
                <div class="section-card">
                    <h2 class="section-title">📊 Instructor Dashboard</h2>
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-number"><?= htmlspecialchars($students_count) ?></div>
                            <div class="stat-label">Total Students</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number"><?= htmlspecialchars($courses_count) ?></div>
                            <div class="stat-label">Your Courses</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number"><?= htmlspecialchars($tasks_count) ?></div>
                            <div class="stat-label">Tasks Assigned</div>
                        </div>
                    </div>
                </div>
            </div>
        </main>       
    </div>

<script>
    // Make functions available immediately
    window.showPage = function(pageId, event = null) {
        if (event) {
            event.preventDefault();
            event.stopPropagation();
        }

        // Validate page exists
        if (!document.getElementById(pageId)) {
            console.error('Page not found:', pageId);
            return;
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
            if (link.getAttribute('href').includes(pageId)) {
                link.classList.add('active');
            }
        });

        // Close sidebar on mobile
        if (window.innerWidth <= 768) {
            closeSidebar();
        }
    };

    window.toggleSidebar = function() {
        document.getElementById('sidebar').classList.toggle('open');
        document.querySelector('.overlay').classList.toggle('active');
        document.body.classList.toggle('no-scroll');
    };

    window.closeSidebar = function() {
        document.getElementById('sidebar').classList.remove('open');
        document.querySelector('.overlay').classList.remove('active');
        document.body.classList.remove('no-scroll');
    };

    window.logout = function() {
        if (confirm('Are you sure you want to logout?')) {
            window.location.href = 'logout.php';
        }
    };

    // Initialize the dashboard
    document.addEventListener('DOMContentLoaded', function() {
        // Set active page based on URL hash
        const hash = window.location.hash.substring(1);
        if (hash && document.getElementById(hash)) {
            showPage(hash);
        }

        // Welcome notification
        setTimeout(() => {
            showNotification('Welcome back, <?= htmlspecialchars($full_name) ?>!', 'success');
        }, 500);
    });

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
</script>
</body>
</html>