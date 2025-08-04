<?php
session_start();

require_once 'connection.php';

// if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['role'] !== 'admin') {
//     header("Location: account.php");
//     exit();
// }

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

    //     if (!$user || !isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['role'] !== 'admin') {
    //         session_destroy();
    //         header("Location: account.php");
    //         exit();
    //     }
    // } catch(PDOException $e) {
    //     error_log("Database error in dashboard: " . $e->getMessage());
    //     die("Something went wrong. Please try again later.");
    // }
}

// Dashboard stats
$totalStudents = $pdo->query("SELECT COUNT(*) FROM accounts where role = 'student'")->fetchColumn();
$totalInstructors = $pdo->query("SELECT COUNT(*) FROM accounts where role = 'instructor'")->fetchColumn();
$totalCourses = $pdo->query("SELECT COUNT(*) FROM courses")->fetchColumn();

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
    $full_name = $user['fullname'] ?? 'Admin';
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
    <title>CodeLab - Dashboard</title>
    <link rel="stylesheet" href="dasboard.css">
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
                <span>Welcome back, <?= htmlspecialchars($full_name) ?></span>
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
                    <a href="#" class="sidebar-link active" onclick="showPage('profile')">
                        <span class="sidebar-icon">👤</span>
                        My Profile
                    </a>
                </li>
                <li class="sidebar-item">
                    <a href="#" class="sidebar-link" onclick="showPage('dashboard')">
                        <span class="sidebar-icon">📊</span>
                        Dashboard
                    </a>
                </li>
                <li class="sidebar-item">
                    <a href="addusers.php" class="sidebar-link" onclick="showPage('add-users')">
                        <span class="sidebar-icon">➕</span>
                        Add Users
                    </a>
                </li>
                <li class="sidebar-item">
                    <a href="addCourse.php" class="sidebar-link" onclick="showPage('add-course')">
                        <span class="sidebar-icon">📚</span>
                        Add Courses
                    </a>
                </li>
            </ul>
        </nav>

        <main class="content-area">
            <!-- Profile Page (Default) -->
            <div id="profile" class="page-section active">
                <div class="section-card">
                    <div class="profile-section">
                        <div class="profile-header">
                            <div class="profile-avatar-large"><?= htmlspecialchars($initials) ?></div>
                            <div class="profile-info">
                                <h1><?= htmlspecialchars($full_name) ?></h1>
                            </div>
                            <button class="edit-profile-btn" onclick="editProfile()">Edit Profile</button>
                        </div>

                        <div class="profile-details">
                            <div class="detail-group">
                                <h3>Personal Information</h3>
                                <div class="detail-item">
                                    <span class="detail-label">Staff Id</span>
                                    <span class="detail-value"><?= htmlspecialchars($staff_id) ?></span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">First Name</span>
                                    <span class="detail-value"><?= htmlspecialchars($first_name) ?></span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Last Name</span>
                                    <span class="detail-value"><?= htmlspecialchars($last_name) ?></span>
                                </div>
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
                    <h2 class="section-title">📊 Admin Dashboard</h2>
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-number"><?= $totalStudents ?></div>
                            <div class="stat-label">Total Students</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number"><?= $totalInstructors ?></div>
                            <div class="stat-label">Total Instructors</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number"><?= $totalCourses ?></div>
                            <div class="stat-label">Total Courses</div>
                        </div>
                    </div>
                </div>
            </div>
        </main>       
    </div>

    <script>
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

        function editProfile() {
            showNotification('Opening profile editor...', 'info');
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

        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(() => {
                showNotification('<?= htmlspecialchars($full_name) ?>, Welcome to your profile! 👋', 'success');
            }, 500);
        });
    </script>
</body>
</html>