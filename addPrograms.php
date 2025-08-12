<?php
session_start();
require_once 'connection.php';
$user_id = $_SESSION['user_id'] ?? null;
$user = null;
if ($user_id) {
    $stmt = $pdo->prepare("SELECT * FROM accounts WHERE accounts_id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Handle Add/Edit Program form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_course'])) {
    $code = trim($_POST['courseCode']);
    $name = trim($_POST['courseName']);
    $type = $_POST['program'];
    $program_id = isset($_POST['course_id']) ? intval($_POST['course_id']) : null;

    try {
        if ($program_id) {
            // Edit existing program
            $stmt = $pdo->prepare("UPDATE programs SET program_id=?, program_name=?, program_type=? WHERE program_id=?");
            $stmt->execute([$code, $name, $type, $program_id]);
            $success_message = "Program updated successfully!";
        } else {
            // Add new program
            $stmt = $pdo->prepare("INSERT INTO programs (program_id, program_name, program_type) VALUES (?, ?, ?)");
            $stmt->execute([$code, $name, $type]);
            $success_message = "Program added successfully!";
        }
    } catch(PDOException $e) {
        $error_message = "Error: " . $e->getMessage();
    }
}

// Handle program deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_course_id'])) {
    $delete_id = $_POST['delete_course_id'];
    try {
        $pdo->prepare("DELETE FROM programs WHERE program_id = ?")->execute([$delete_id]);
        header("Location: addPrograms.php");
        exit;
    } catch(PDOException $e) {
        $error_message = "Error deleting program: " . $e->getMessage();
    }
}

// Fetch all programs
$programs = $pdo->query("SELECT * FROM programs")->fetchAll(PDO::FETCH_ASSOC);

// Fetch admin info for avatar
$user_id = $_SESSION['user_id'] ?? null;
$user = null;
$initials = 'AD';
if ($user_id) {
    $stmt = $pdo->prepare("SELECT sur_name, other_name, fullname FROM accounts WHERE accounts_id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($user) {
        if (!empty($user['sur_name']) && !empty($user['other_name'])) {
            $initials = strtoupper(substr($user['sur_name'], 0, 1) . substr($user['other_name'], 0, 1));
        } elseif (!empty($user['fullname'])) {
            $names = explode(' ', $user['fullname']);
            $initials = strtoupper(substr($names[0], 0, 1) . (isset($names[1]) ? substr($names[1], 0, 1) : ''));
        } elseif (!empty($user['username'])) {
            $initials = strtoupper(substr($user['username'], 0, 2));
        }
    }
}

// Handle edit request (populate form)
$edit_program = null;
if (isset($_GET['edit'])) {
    $edit_id = $_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM programs WHERE program_id = ?");
    $stmt->execute([$edit_id]);
    $edit_program = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CodeLab - Dashboard</title>
    <link rel="stylesheet" href="dasboard.css">
     <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: #333;
        }
        .content-area {
            flex: 1;
            margin-left: 280px;
            padding: 2rem;
            transition: margin-left 0.3s ease;
            background: #f8f9fa;
        }
        /* Header Styles */
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
            color: white;
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
            color: white;
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
        .logout-btn {
            background: rgba(255, 255, 255, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.3);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .logout-btn:hover {
            background: rgba(255, 255, 255, 0.3);
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
                <span>Welcome back, <?= htmlspecialchars($user['fullname'] ?? $user['first_name'] ?? $user['username'] ?? 'Admin') ?></span>
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
                    <a href="adminDashboard.php?view=profile" class="sidebar-link" onclick="showPage('profile', event)">
                        <i class="fas fa-user sidebar-icon"></i>
                        <span>My Profile</span>
                    </a>
                </li>
                <li class="sidebar-item">
                    <a href="adminDashboard.php?view=dashboard" class="sidebar-link" onclick="showPage('dashboard', event)">
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
                    <a href="addPrograms.php" class="sidebar-link active">
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
            <!-- Add/Edit Program Form -->
            <div id="add-course" class="page-section active">
                <div class="section-card">
                    <h2 class="section-title"><?= $edit_program ? '✏️ Edit Program' : '📚 Add Program' ?></h2>
                    <?php if (!empty($success_message)): ?>
                        <div class="message success"><?= htmlspecialchars($success_message) ?></div>
                    <?php endif; ?>
                    <?php if (!empty($error_message)): ?>
                        <div class="message error"><?= htmlspecialchars($error_message) ?></div>
                    <?php endif; ?>
                    <form id="addCourseForm" class="add-user-form" method="POST">
                        <input type="hidden" name="save_course" value="1">
                        <?php if ($edit_program): ?>
                            <input type="hidden" name="course_id" value="<?= htmlspecialchars($edit_program['program_id']) ?>">
                        <?php endif; ?>
                        <div class="form-group">
                            <label for="courseCode" class="form-label">Program ID</label>
                            <input type="text" id="courseCode" name="courseCode" class="form-input" placeholder="e.g., CS101" required value="<?= htmlspecialchars($edit_program['program_id'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label for="courseName" class="form-label">Program Name</label>
                            <input type="text" id="courseName" name="courseName" class="form-input" placeholder="e.g., Computer Science" required value="<?= htmlspecialchars($edit_program['program_name'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label for="program" class="form-label">Program Type</label>
                            <select id="program" name="program" class="form-select" required>
                                <option value="">Select Program Type</option>
                                <option value="BTech" <?= (isset($edit_program['program_type']) && $edit_program['program_type'] == 'BTech') ? 'selected' : '' ?>>BTech</option>
                                <option value="HND" <?= (isset($edit_program['program_type']) && $edit_program['program_type'] == 'HND') ? 'selected' : '' ?>>HND</option>
                                <option value="MTech" <?= (isset($edit_program['program_type']) && $edit_program['program_type'] == 'MTech') ? 'selected' : '' ?>>MTech</option>
                                <option value="Diploma" <?= (isset($edit_program['program_type']) && $edit_program['program_type'] == 'Diploma') ? 'selected' : '' ?>>Diploma</option>
                            </select>
                        </div>
                        <div class="form-group full-width">
                            <div class="form-buttons">
                                <button type="button" class="btn btn-secondary" onclick="window.location='addPrograms.php'">Cancel</button>
                                <button type="submit" class="btn btn-primary"><?= $edit_program ? 'Update Program' : 'Add Program' ?></button>
                            </div>
                        </div>
                    </form>
                </div>
                
                <!-- Program Management -->
                <div class="section-card" style="margin-top: 2rem;">
                    <h2 class="section-title">📋 Program Management</h2>
                    <div class="search-filter">
                        <input type="text" id="searchCourses" class="search-input" placeholder="Search Programs..." onkeyup="filterCourses()">
                        <select id="programFilter" class="filter-select" onchange="filterCourses()">
                            <option value="">All Programs</option>
                            <option value="BTech">BTech</option>
                            <option value="HND">HND</option>
                            <option value="MTech">MTech</option>
                            <option value="Diploma">Diploma</option>
                        </select>
                    </div>
                    <table class="users-table" id="coursesTable">
                        <thead>
                            <tr>
                                <th>Program ID</th>
                                <th>Program Name</th>
                                <th>Program Type</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="coursesTableBody">
                            <?php foreach ($programs as $program): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($program['program_id']) ?></strong></td>
                                    <td><?= htmlspecialchars($program['program_name']) ?></td>
                                    <td><?= htmlspecialchars($program['program_type']) ?></td>
                                    <td>
                                        <form method="GET" style="display:inline;">
                                            <input type="hidden" name="edit" value="<?= htmlspecialchars($program['program_id']) ?>">
                                            <button type="submit" class="btn btn-small btn-edit">Edit</button>
                                        </form>
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="delete_course_id" value="<?= htmlspecialchars($program['program_id']) ?>">
                                            <button type="submit" class="btn btn-small btn-delete" onclick="return confirm('Delete this program?')">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>       
    </div>

    <script>
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
                setTimeout(() => {
                    window.location.href = 'logout.php';
                }, 500);
            }
        }
        function filterCourses() {
            const searchInput = document.getElementById('searchCourses');
            const programFilter = document.getElementById('programFilter');
            const searchTerm = searchInput.value.toLowerCase();
            const programValue = programFilter.value;
            const rows = document.querySelectorAll('#coursesTableBody tr');
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                const showSearch = searchTerm === '' || text.includes(searchTerm);
                const showProgram = programValue === '' || text.includes(programValue);
                row.style.display = showSearch && showProgram ? '' : 'none';
            });
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
    </script>
</body>
</html>