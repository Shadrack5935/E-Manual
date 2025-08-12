<?php
session_start();
require_once 'connection.php';

// Security validation
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: account.php");
    exit();
}

// CSRF protection
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Get admin data
$user_id = $_SESSION['user_id'];
$user = null;

try {
    $stmt = $pdo->prepare("SELECT accounts_id, sur_name, other_name, email, phone, fullname FROM accounts WHERE accounts_id = ? AND role = 'admin'");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        session_destroy();
        header("Location: account.php");
        exit();
    }
} catch(PDOException $e) {
    error_log("Database error in dashboard: " . $e->getMessage());
    die("Something went wrong. Please try again later.");
}

// Dashboard stats with error handling
function getCount($pdo, $query) {
    try {
        return $pdo->query($query)->fetchColumn();
    } catch(PDOException $e) {
        error_log("Count query failed: " . $e->getMessage());
        return 0;
    }
}

$totalStudents = getCount($pdo, "SELECT COUNT(*) FROM accounts WHERE role = 'student'");
$totalInstructors = getCount($pdo, "SELECT COUNT(*) FROM accounts WHERE role = 'instructor'");
$totalCourses = getCount($pdo, "SELECT COUNT(*) FROM courses");

// User display data
$full_name = ($user['sur_name'] ?? '') . ' ' . ($user['other_name'] ?? '');
if (trim($full_name) === '') {
    $full_name = $user['fullname'] ?? 'Admin';
}

function getUserInitials($name) {
    $initials = '';
    $words = preg_split('/\s+/', trim($name));
    foreach ($words as $word) {
        if (mb_strlen($word) > 0) {
            $initials .= mb_substr($word, 0, 1);
        }
        if (mb_strlen($initials) >= 2) break;
    }
    return mb_strtoupper($initials ?: 'AD');
}

$initials = getUserInitials($full_name);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CodeLab - Admin Dashboard</title>
    <link rel="stylesheet" href="dasboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="header">
        <div class="header-content">
            <div class="header-left">
                <div class="hamburger" onclick="toggleSidebar()">
                    <span></span>
                    <span></span>
                    <span></span>
                </div>
                <div class="logo">CodeLab Admin</div>
            </div>
            <div class="user-menu">
                <span>Welcome, <?= htmlspecialchars($full_name, ENT_QUOTES) ?></span>
                <div class="user-avatar"><?= htmlspecialchars($initials) ?></div>
                <button class="logout-btn" onclick="logout()">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </button>
            </div>
        </div>
    </div>

    <div class="overlay" onclick="closeSidebar()"></div>

    <div class="main-container">
        <nav class="sidebar" id="sidebar">
            <ul class="sidebar-menu">
                <li class="sidebar-item">
                    <a href="#profile" class="sidebar-link active" onclick="showPage('profile', event)">
                        <i class="fas fa-user sidebar-icon"></i>
                        <span>My Profile</span>
                    </a>
                </li>
                <li class="sidebar-item">
                    <a href="#dashboard" class="sidebar-link" onclick="showPage('dashboard', event)">
                        <i class="fas fa-chart-bar sidebar-icon"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li class="sidebar-item">
                    <a href="addusers.php" class="sidebar-link">
                        <i class="fas fa-user-plus sidebar-icon"></i>
                        <span>Add Instructors</span>
                    </a>
                </li>
                <li class="sidebar-item">
                    <a href="addStudents.php" class="sidebar-link">
                        <i class="fas fa-user-graduate sidebar-icon"></i>
                        <span>Add Students</span>
                    </a>
                </li>
                <li class="sidebar-item">
                    <a href="addPrograms.php" class="sidebar-link">
                        <i class="fas fa-graduation-cap sidebar-icon"></i>
                        <span>Add Programs</span>
                    </a>
                </li>
                <li class="sidebar-item">
                    <a href="addCourse.php" class="sidebar-link">
                        <i class="fas fa-book sidebar-icon"></i>
                        <span>Add Courses</span>
                    </a>
                </li>
                <li class="sidebar-item">
                    <a href="Academic_Calender.php" class="sidebar-link">
                        <i class="fas fa-calendar-alt sidebar-icon"></i>
                        <span>Academic Calendar</span>
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
                                <p>Administrator</p>
                            </div>
                            <button class="edit-profile-btn" onclick="editProfile()">
                                <i class="fas fa-edit"></i> Edit Profile
                            </button>
                        </div>

                        <div class="profile-details">
                            <div class="detail-group">
                                <h3><i class="fas fa-id-card"></i> Account Information</h3>
                                <div class="detail-item">
                                    <span class="detail-label">Admin ID:</span>
                                    <span class="detail-value"><?= htmlspecialchars($user['accounts_id']) ?></span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Email:</span>
                                    <span class="detail-value"><?= htmlspecialchars($user['email']) ?></span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Phone:</span>
                                    <span class="detail-value"><?= htmlspecialchars($user['phone']) ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Dashboard Page -->
            <div id="dashboard" class="page-section">
                <div class="section-card">
                    <h2 class="section-title"><i class="fas fa-chart-bar"></i> Admin Dashboard</h2>
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-icon"><i class="fas fa-users"></i></div>
                            <div class="stat-number"><?= htmlspecialchars($totalStudents) ?></div>
                            <div class="stat-label">Total Students</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon"><i class="fas fa-chalkboard-teacher"></i></div>
                            <div class="stat-number"><?= htmlspecialchars($totalInstructors) ?></div>
                            <div class="stat-label">Total Instructors</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon"><i class="fas fa-book-open"></i></div>
                            <div class="stat-number"><?= htmlspecialchars($totalCourses) ?></div>
                            <div class="stat-label">Total Courses</div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Loading Overlay -->
    <div id="loading-overlay" class="loading-overlay">
        <div class="loading-spinner">
            <div class="spinner"></div>
            <p>Loading...</p>
        </div>
    </div>

    <script>
    // Global variables
    const csrfToken = '<?= $_SESSION['csrf_token'] ?>';
    let currentPage = 'profile';

    // DOM Elements
    const sidebar = document.getElementById('sidebar');
    const overlay = document.querySelector('.overlay');
    const hamburger = document.querySelector('.hamburger');
    const pageSections = document.querySelectorAll('.page-section');
    const sidebarLinks = document.querySelectorAll('.sidebar-link');
    const loadingOverlay = document.getElementById('loading-overlay');

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

    // Navigation functions
    function showPage(pageId, event = null) {
        if (event) {
            event.preventDefault();
            event.stopPropagation();
        }

        // Validate page exists
        if (!document.getElementById(pageId)) {
            console.error('Page not found:', pageId);
            return;
        }

        // Update current page
        currentPage = pageId;
        window.location.hash = pageId;

        // Hide all pages
        pageSections.forEach(page => {
            page.classList.remove('active');
        });

        // Show selected page
        document.getElementById(pageId).classList.add('active');

        // Update active link
        sidebarLinks.forEach(link => {
            link.classList.remove('active');
            if (link.getAttribute('href') === `#${pageId}`) {
                link.classList.add('active');
            }
        });

        // Close sidebar on mobile
        if (window.innerWidth <= 768) {
            closeSidebar();
        }
    }

    // Sidebar functions
    function toggleSidebar() {
        sidebar.classList.toggle('open');
        overlay.classList.toggle('active');
        document.body.classList.toggle('no-scroll');
    }

    function closeSidebar() {
        sidebar.classList.remove('open');
        overlay.classList.remove('active');
        document.body.classList.remove('no-scroll');
    }

    // Profile functions
    function editProfile() {
        showLoading(true);
        // Simulate loading
        setTimeout(() => {
            showNotification('Profile editor will be available in the next update', 'info');
            showLoading(false);
        }, 1000);
    }

    // Logout function
    async function logout() {
        if (!confirm('Are you sure you want to logout?')) return;
        
        showLoading(true);
        try {
            const response = await fetch('logout.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': csrfToken
                },
                body: JSON.stringify({ csrf_token: csrfToken })
            });

            if (response.ok) {
                window.location.href = 'account.php';
            } else {
                throw new Error('Logout failed');
            }
        } catch (error) {
            console.error('Logout error:', error);
            showNotification(error.message, 'error');
        } finally {
            showLoading(false);
        }
    }

    // UI Utility functions
    function showLoading(show) {
        loadingOverlay.style.display = show ? 'flex' : 'none';
    }

    function showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.innerHTML = `
            <div class="notification-content">
                <i class="fas ${type === 'success' ? 'fa-check-circle' : 
                                 type === 'error' ? 'fa-exclamation-circle' : 
                                 'fa-info-circle'}"></i>
                <span>${message}</span>
            </div>
        `;
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.classList.add('fade-out');
            setTimeout(() => notification.remove(), 300);
        }, 3000);
    }

    // Event listeners
    hamburger.addEventListener('click', toggleSidebar);
    overlay.addEventListener('click', closeSidebar);
    window.addEventListener('resize', function() {
        if (window.innerWidth > 768) {
            closeSidebar();
        }
    });

    // Make functions available globally
    window.showPage = showPage;
    window.toggleSidebar = toggleSidebar;
    window.closeSidebar = closeSidebar;
    window.logout = logout;
    window.editProfile = editProfile;
    </script>
</body>
</html>