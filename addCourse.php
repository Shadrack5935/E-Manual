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

// Handle Add/Edit Course form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_course'])) {
    $code = trim($_POST['courseCode']);
    $name = trim($_POST['courseName']);
    $credits = intval($_POST['credits']);
    $program = $_POST['program'];
    $semester = $_POST['semester'];
    $instructor_id = $_POST['instructor'];
    $description = isset($_POST['description']) ? trim($_POST['description']) : '';
    $course_id = isset($_POST['course_id']) ? intval($_POST['course_id']) : null;

    if ($course_id) {
        // Edit existing course
        $stmt = $pdo->prepare("UPDATE courses SET course_code=?, course_name=?, credits=?, program=?, semester=?, instructor_id=?, description=? WHERE id=?");
        $stmt->execute([$code, $name, $credits, $program, $semester, $instructor_id, $description, $course_id]);
        $success_message = "Course updated successfully!";
    } else {
        // Add new course
        $stmt = $pdo->prepare("INSERT INTO courses (course_code, course_name, credits, program, semester, instructor_id, description) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$code, $name, $credits, $program, $semester, $instructor_id, $description]);
        $success_message = "Course added successfully!";
    }
}

// Handle course deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_course_id'])) {
    $delete_id = intval($_POST['delete_course_id']);
    $pdo->prepare("DELETE FROM courses WHERE id = ?")->execute([$delete_id]);
    header("Location: addCourse.php");
    exit;
}

// Fetch all instructors
$instructors = $pdo->query("SELECT staff_id, fullname FROM accounts WHERE role IN ('instructor','admin')")->fetchAll(PDO::FETCH_ASSOC);

// Fetch all courses
$courses = $pdo->query("SELECT c.*, a.fullname AS instructor_name FROM courses c LEFT JOIN accounts a ON c.instructor_id = a.staff_id")->fetchAll(PDO::FETCH_ASSOC);

// Fetch admin info for avatar
$user_id = $_SESSION['user_id'] ?? null;
$user = null;
$initials = 'AD';
if ($user_id) {
    $stmt = $pdo->prepare("SELECT first_name, last_name, fullname FROM accounts WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($user) {
        if (!empty($user['first_name']) && !empty($user['last_name'])) {
            $initials = strtoupper(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1));
        } elseif (!empty($user['fullname'])) {
            $names = explode(' ', $user['fullname']);
            $initials = strtoupper(substr($names[0], 0, 1) . (isset($names[1]) ? substr($names[1], 0, 1) : ''));
        } elseif (!empty($user['username'])) {
            $initials = strtoupper(substr($user['username'], 0, 2));
        }
    }
}

// Handle edit request (populate form)
$edit_course = null;
if (isset($_GET['edit']) && $_GET['edit']) {
    $edit_id = intval($_GET['edit']);
    $stmt = $pdo->prepare("SELECT * FROM courses WHERE id = ?");
    $stmt->execute([$edit_id]);
    $edit_course = $stmt->fetch(PDO::FETCH_ASSOC);
}
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
                <span>Welcome back, <?= htmlspecialchars($user['fullname'] ?? $user['first_name'] ?? $user['username'] ?? 'Admin') ?></span>
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
                    <a href="adminDashboard.php" class="sidebar-link " onclick="showPage('profile')">
                        <span class="sidebar-icon">👤</span>
                        My Profile
                    </a>
                </li>
                <li class="sidebar-item">
                    <a href="adminDashboard.php" class="sidebar-link" onclick="showPage('dashboard')">
                        <span class="sidebar-icon">📊</span>
                        Dashboard
                    </a>
                </li>
                <li class="sidebar-item">
                    <a href="addusers.php" class="sidebar-link " onclick="showPage('add-users')">
                        <span class="sidebar-icon">➕</span>
                        Add Users
                    </a>
                </li>
                <li class="sidebar-item">
                    <a href="addCourse.php" class="sidebar-link active" onclick="showPage('add-course')">
                        <span class="sidebar-icon">📚</span>
                        Add Courses
                    </a>
                </li>
            </ul>
        </nav>

        <main class="content-area">
            <!-- Add/Edit Course Form -->
            <div id="add-course" class="page-section active">
                <div class="section-card">
                    <h2 class="section-title"><?= $edit_course ? '✏️ Edit Course' : '📚 Add New Course' ?></h2>
                    <?php if (!empty($success_message)): ?>
                        <div class="message success"><?= htmlspecialchars($success_message) ?></div>
                    <?php endif; ?>
                    <form id="addCourseForm" class="add-user-form" method="POST">
                        <input type="hidden" name="save_course" value="1">
                        <?php if ($edit_course): ?>
                            <input type="hidden" name="course_id" value="<?= $edit_course['id'] ?>">
                        <?php endif; ?>
                        <div class="form-group">
                            <label for="courseCode" class="form-label">Course Code</label>
                            <input type="text" id="courseCode" name="courseCode" class="form-input" placeholder="e.g., CS101" required value="<?= htmlspecialchars($edit_course['course_code'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label for="courseName" class="form-label">Course Name</label>
                            <input type="text" id="courseName" name="courseName" class="form-input" placeholder="e.g., Introduction to Programming" required value="<?= htmlspecialchars($edit_course['course_name'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label for="credits" class="form-label">Credits</label>
                            <input type="number" id="credits" name="credits" class="form-input" min="1" max="6" placeholder="3" required value="<?= htmlspecialchars($edit_course['credits'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label for="program" class="form-label">Program</label>
                            <select id="program" name="program" class="form-select" required>
                                <option value="">Select Program</option>
                                <option value="Computer Science" <?= (isset($edit_course['program']) && $edit_course['program'] == 'Computer Science') ? 'selected' : '' ?>>Computer Science</option>
                                <option value="Information Technology" <?= (isset($edit_course['program']) && $edit_course['program'] == 'Information Technology') ? 'selected' : '' ?>>Information Technology</option>
                                <option value="Software Engineering" <?= (isset($edit_course['program']) && $edit_course['program'] == 'Software Engineering') ? 'selected' : '' ?>>Software Engineering</option>
                                <option value="Data Science" <?= (isset($edit_course['program']) && $edit_course['program'] == 'Data Science') ? 'selected' : '' ?>>Data Science</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="semester" class="form-label">Semester</label>
                            <select id="semester" name="semester" class="form-select" required>
                                <option value="">Select Semester</option>
                                <option value="First Semester" <?= (isset($edit_course['semester']) && $edit_course['semester'] == 'First Semester') ? 'selected' : '' ?>>First Semester</option>
                                <option value="Second Semester" <?= (isset($edit_course['semester']) && $edit_course['semester'] == 'Second Semester') ? 'selected' : '' ?>>Second Semester</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="instructor" class="form-label">Instructor</label>
                            <select id="instructor" name="instructor" class="form-select" required>
                                <option value="">Select an instructor</option>
                                <?php foreach ($instructors as $inst): ?>
                                    <option value="<?= htmlspecialchars($inst['staff_id']) ?>" <?= (isset($edit_course['instructor_id']) && $edit_course['instructor_id'] == $inst['staff_id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($inst['fullname']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="description" class="form-label">Description</label>
                            <textarea id="description" name="description" class="form-input" placeholder="Course description..."><?= htmlspecialchars($edit_course['description'] ?? '') ?></textarea>
                        </div>
                        <div class="form-group full-width">
                            <div class="form-buttons">
                                <button type="reset" class="btn btn-secondary" onclick="window.location='addCourse.php'">Reset</button>
                                <button type="submit" class="btn btn-primary"><?= $edit_course ? 'Update Course' : 'Add Course' ?></button>
                            </div>
                        </div>
                    </form>
                </div>
                
                <!-- Course Management -->
                <div class="section-card" style="margin-top: 2rem;">
                    <h2 class="section-title">📋 Course Management</h2>
                    <div class="search-filter">
                        <input type="text" id="searchCourses" class="search-input" placeholder="Search courses..." onkeyup="filterCourses()">
                        <select id="programFilter" class="filter-select" onchange="filterCourses()">
                            <option value="">All Programs</option>
                            <option value="Computer Science">Computer Science</option>
                            <option value="Information Technology">Information Technology</option>
                            <option value="Software Engineering">Software Engineering</option>
                            <option value="Data Science">Data Science</option>
                        </select>
                    </div>
                    <table class="users-table" id="coursesTable">
                        <thead>
                            <tr>
                                <th>Course Code</th>
                                <th>Course Name</th>
                                <th>Credits</th>
                                <th>Program</th>
                                <th>Semester</th>
                                <th>Instructor</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="coursesTableBody">
                            <?php foreach ($courses as $course): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($course['course_code']) ?></strong></td>
                                    <td><?= htmlspecialchars($course['course_name']) ?></td>
                                    <td><?= htmlspecialchars($course['credits']) ?></td>
                                    <td><?= htmlspecialchars($course['program']) ?></td>
                                    <td><?= htmlspecialchars($course['semester']) ?></td>
                                    <td><?= htmlspecialchars($course['instructor_name'] ?? 'Unassigned') ?></td>
                                    <td>
                                        <form method="GET" style="display:inline;">
                                            <input type="hidden" name="edit" value="<?= $course['id'] ?>">
                                            <button type="submit" class="btn btn-small btn-edit">Edit</button>
                                        </form>
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="delete_course_id" value="<?= $course['id'] ?>">
                                            <button type="submit" class="btn btn-small btn-delete" onclick="return confirm('Delete this course?')">Delete</button>
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