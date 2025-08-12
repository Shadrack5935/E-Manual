<?php
session_start();
require_once 'connection.php';
$message = '';
$messageType = '';

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
    $stmt = $pdo->prepare("SELECT accounts_id, sur_name, other_name, email, phone, role FROM accounts WHERE accounts_id = ? AND role = 'admin'");
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

// Handle Add/Edit Student form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $message = 'Invalid CSRF token';
        $messageType = "error";
    } else {
        // Sanitize and validate input
        $studentId = trim($_POST['studentId']);
        $surName = htmlspecialchars(trim($_POST['sur_name']));
        $otherName = htmlspecialchars(trim($_POST['other_name']));
        $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
        $phone = htmlspecialchars(trim($_POST['phone']));
        $program = htmlspecialchars(trim($_POST['program']));
        $level = htmlspecialchars(trim($_POST['level']));
        $password = $_POST['password'] ?? '';
        $edit_student_id = $_POST['edit_student_id'] ?? '';

        // Validation
        if (empty($studentId) || empty($surName) || empty($otherName) || empty($email) || 
            empty($phone) || empty($program) || empty($level) || (empty($password) && !$edit_student_id)) {
            $message = 'All fields are required';
            $messageType = "error";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $message = 'Invalid email format';
            $messageType = 'error';
        } else {
            try {
                if ($edit_student_id) {
                    // Update existing student
                    $pdo->beginTransaction();
                    
                    // Update accounts table
                    $params = [$surName, $otherName, $email, $phone];
                    $sql = "UPDATE accounts SET sur_name=?, other_name=?, email=?, phone=?";
                    
                    if (!empty($password)) {
                        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                        $sql .= ", pass=?";
                        $params[] = $hashedPassword;
                    }
                    
                    $sql .= " WHERE accounts_id=?";
                    $params[] = $studentId;
                    
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute($params);

                    // Update students table
                    $stmt = $pdo->prepare("UPDATE students SET program=?, level=? WHERE accounts_id=?");
                    $stmt->execute([$program, $level, $studentId]);

                    $pdo->commit();
                    
                    $message = 'Student updated successfully!';
                    $messageType = 'success';
                } else {
                    // Create new student
                    // Check for duplicate email
                    $stmt = $pdo->prepare("SELECT accounts_id FROM accounts WHERE email = ?");
                    $stmt->execute([$email]);
                    if ($stmt->fetch()) {
                        $message = 'Email already exists';
                        $messageType = "error";
                    } else {
                        // Check for duplicate student ID
                        $stmt = $pdo->prepare("SELECT accounts_id FROM accounts WHERE accounts_id = ?");
                        $stmt->execute([$studentId]);
                        if ($stmt->fetch()) {
                            $message = 'Student ID already exists';
                            $messageType = "error";
                        } else {
                            // Insert new student
                            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                            $verificationToken = bin2hex(random_bytes(16));
                            $academicYear = date('n') >= 9 ? date('Y') . '-' . (date('Y') + 1) : (date('Y') - 1) . '-' . date('Y');
                            
                            $pdo->beginTransaction();
                            
                            // Insert into accounts table
                            $stmt = $pdo->prepare("INSERT INTO accounts 
                                (accounts_id, sur_name, other_name, email, pass, verification_token, phone, role, created_at, is_active, is_verified) 
                                VALUES (?, ?, ?, ?, ?, ?, ?, 'student', NOW(), 1, 1)");
                            $stmt->execute([$studentId, $surName, $otherName, $email, $hashedPassword, $verificationToken, $phone]);

                            // Insert into students table
                            $stmt = $pdo->prepare("INSERT INTO students 
                                (accounts_id, program, level, semester, academic_year)
                                VALUES (?, ?, ?, 'Semester 1', ?)");
                            $stmt->execute([$studentId, $program, $level, $academicYear]);

                            $pdo->commit();
                            
                            $message = 'Student account created successfully!';
                            $messageType = 'success';
                            // Clear form after successful submission
                            $_POST = array();
                        }
                    }
                }
            } catch (PDOException $e) {
                $pdo->rollBack();
                $message = 'Database error: ' . $e->getMessage();
                $messageType = 'error';
            }
        }
    }
}

// Handle edit request (populate form)
$edit_student = null;
if (isset($_GET['edit_student_id'])) {
    try {
        $edit_id = $_GET['edit_student_id'];
        // Get student data from both accounts and students tables
        $stmt = $pdo->prepare("SELECT a.*, s.program, s.level 
                              FROM accounts a 
                              JOIN students s ON a.accounts_id = s.accounts_id 
                              WHERE a.accounts_id = ?");
        $stmt->execute([$edit_id]);
        $edit_student = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $message = 'Error fetching student data: ' . $e->getMessage();
        $messageType = 'error';
    }
}

// Handle delete request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_student_id'])) {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $message = 'Invalid CSRF token';
        $messageType = 'error';
    } else {
        try {
            $delete_id = $_POST['delete_student_id'];
            $pdo->beginTransaction();
            
            // Delete from students table first
            $stmt = $pdo->prepare("DELETE FROM students WHERE accounts_id = ?");
            $stmt->execute([$delete_id]);
            
            // Then delete from accounts table
            $stmt = $pdo->prepare("DELETE FROM accounts WHERE accounts_id = ?");
            if ($stmt->execute([$delete_id])) {
                $pdo->commit();
                $message = 'Student deleted successfully';
                $messageType = 'success';
                header("Location: addStudents.php");
                exit;
            } else {
                $pdo->rollBack();
                $message = 'Error deleting student';
                $messageType = 'error';
            }
        } catch (PDOException $e) {
            $pdo->rollBack();
            $message = 'Error deleting student: ' . $e->getMessage();
            $messageType = 'error';
        }
    }
}

// Fetch all students with their details
try {
    $stmt = $pdo->query("SELECT a.accounts_id, a.sur_name, a.other_name, a.email, a.phone, a.role, 
                         s.program, s.level, s.semester 
                         FROM accounts a 
                         JOIN students s ON a.accounts_id = s.accounts_id 
                         WHERE a.role = 'student' 
                         ORDER BY a.accounts_id");
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $students = [];
    $message = 'Error fetching students: ' . $e->getMessage();
    $messageType = 'error';
}

function getUserInitials($fullName) {
    if (empty($fullName)) return 'U';
    $words = explode(' ', trim($fullName));
    if (count($words) >= 2) {
        return strtoupper(substr($words[0], 0, 1) . substr($words[1], 0, 1));
    }
    return strtoupper(substr($fullName, 0, 2));
}

$full_name = ($user['sur_name'] ?? 'Admin') . ' ' . ($user['other_name'] ?? '');
$initials = getUserInitials($full_name);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CodeLab - Student Management</title>
    <link rel="stylesheet" href="dasboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .message {
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 4px;
        }
        .message.error {
            background-color: #ffdddd;
            color: #d8000c;
            border: 1px solid #d8000c;
        }
        .message.success {
            background-color: #ddffdd;
            color: #4F8A10;
            border: 1px solid #4F8A10;
        }
        .user-role {
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 0.8em;
            font-weight: bold;
        }
        .role-admin {
            background-color: #4CAF50;
            color: white;
        }
        .role-instructor {
            background-color: #2196F3;
            color: white;
        }
        .role-student {
            background-color: #ff9800;
            color: white;
        }
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
            <div style="">
                <div class="hamburger" onclick="toggleSidebar()">
                    <span></span>
                    <span></span>
                    <span></span>
                </div>
                <div class="logo">E-MANUAL</div>
            </div>
            <div class="user-menu">
                <span>Welcome back, <?= htmlspecialchars($full_name) ?></span>
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
                    <a href="addStudents.php" class="sidebar-link active">
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
            <div id="add-users" class="page-section active">
                <div class="section-card">
                    <h2 class="section-title"><?= $edit_student ? '✏️ Edit Student' : '➕ Add New Student' ?></h2>
                    <?php if ($message): ?>
                        <div class="message <?= htmlspecialchars($messageType) ?>"><?= htmlspecialchars($message) ?></div>
                    <?php endif; ?>
                    <form id="addStudentForm" class="add-user-form" method="POST" action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                        <?php if ($edit_student): ?>
                            <input type="hidden" name="edit_student_id" value="<?= htmlspecialchars($edit_student['accounts_id']) ?>">
                        <?php endif; ?>
                        <div class="form-group">
                            <label for="studentId" class="form-label">Student ID</label>
                            <input type="text" id="studentId" name="studentId" class="form-input" required 
                                   value="<?= isset($edit_student['accounts_id']) ? htmlspecialchars($edit_student['accounts_id']) : '' ?>" 
                                   <?= isset($edit_student) ? 'readonly' : '' ?>>
                        </div>
                        <div class="form-group">
                            <label for="sur_name" class="form-label">Surname</label>
                            <input type="text" id="sur_name" name="sur_name" class="form-input" required 
                                   value="<?= htmlspecialchars($edit_student['sur_name'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label for="other_name" class="form-label">Other Name</label>
                            <input type="text" id="other_name" name="other_name" class="form-input" required 
                                   value="<?= htmlspecialchars($edit_student['other_name'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" id="email" name="email" class="form-input" required 
                                   value="<?= htmlspecialchars($edit_student['email'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label for="phone" class="form-label">Phone Number</label>
                            <input type="tel" id="phone" name="phone" class="form-input" required 
                                   value="<?= htmlspecialchars($edit_student['phone'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label for="program" class="form-label">Program</label>
                            <select id="program" name="program" class="form-select" required>
                                <option value="">Select Program</option>
                                <option value="Computer Science" <?= isset($edit_student['program']) && $edit_student['program'] == 'Computer Science' ? 'selected' : '' ?>>Computer Science</option>
                                <option value="Information Technology" <?= isset($edit_student['program']) && $edit_student['program'] == 'Information Technology' ? 'selected' : '' ?>>Information Technology</option>
                                <option value="Software Engineering" <?= isset($edit_student['program']) && $edit_student['program'] == 'Software Engineering' ? 'selected' : '' ?>>Software Engineering</option>
                                <option value="Data Science" <?= isset($edit_student['program']) && $edit_student['program'] == 'Data Science' ? 'selected' : '' ?>>Data Science</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="level" class="form-label">Level</label>
                            <select id="level" name="level" class="form-select" required>
                                <option value="">Select Level</option>
                                <option value="100" <?= isset($edit_student['level']) && $edit_student['level'] == '100' ? 'selected' : '' ?>>100</option>
                                <option value="200" <?= isset($edit_student['level']) && $edit_student['level'] == '200' ? 'selected' : '' ?>>200</option>
                                <option value="300" <?= isset($edit_student['level']) && $edit_student['level'] == '300' ? 'selected' : '' ?>>300</option>
                                <option value="400" <?= isset($edit_student['level']) && $edit_student['level'] == '400' ? 'selected' : '' ?>>400</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="password" class="form-label"><?= $edit_student ? 'New Password (leave blank to keep current)' : 'Password' ?></label>
                            <input type="password" id="password" name="password" class="form-input" 
                                   <?= $edit_student ? '' : 'required' ?>>
                        </div>
                        <div class="form-group full-width">
                            <div class="form-buttons">
                                <button type="button" class="btn btn-secondary" onclick="window.location='addStudents.php'">Cancel</button>
                                <button type="submit" class="btn btn-primary"><?= $edit_student ? 'Update Student' : 'Add Student' ?></button>
                            </div>
                        </div>
                    </form>
                </div>
                
                <div class="section-card" style="margin-top: 2rem;">
                    <h2 class="section-title">👥 Student Management</h2>
                    <div class="search-filter">
                        <input type="text" id="searchInput" class="search-input" placeholder="Search students..." onkeyup="searchStudents()">
                        <select id="programFilter" class="filter-select" onchange="filterStudents()">
                            <option value="">All Programs</option>
                            <option value="Computer Science">Computer Science</option>
                            <option value="Information Technology">Information Technology</option>
                            <option value="Software Engineering">Software Engineering</option>
                            <option value="Data Science">Data Science</option>
                        </select>
                        <select id="levelFilter" class="filter-select" onchange="filterStudents()">
                            <option value="">All Levels</option>
                            <option value="100">100</option>
                            <option value="200">200</option>
                            <option value="300">300</option>
                            <option value="400">400</option>
                        </select>
                    </div>
                    <table class="users-table" id="studentsTable">
                        <thead>
                            <tr>
                                <th>Student ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Program</th>
                                <th>Level</th>
                                <th>Semester</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="studentsTableBody">
                            <?php foreach ($students as $student): ?>
                                <tr>
                                    <td><?= htmlspecialchars($student['accounts_id']) ?></td>
                                    <td><?= htmlspecialchars($student['sur_name'] . ' ' . $student['other_name']) ?></td>
                                    <td><?= htmlspecialchars($student['email']) ?></td>
                                    <td><?= htmlspecialchars($student['phone']) ?></td>
                                    <td><?= htmlspecialchars($student['program']) ?></td>
                                    <td><?= htmlspecialchars($student['level']) ?></td>
                                    <td><?= htmlspecialchars($student['semester']) ?></td>
                                    <td class="action-buttons">
                                        <form method="GET" style="display:inline;">
                                            <input type="hidden" name="edit_student_id" value="<?= htmlspecialchars($student['accounts_id']) ?>">
                                            <button type="submit" class="btn btn-small btn-edit">Edit</button>
                                        </form>
                                        <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this student?');">
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                                            <input type="hidden" name="delete_student_id" value="<?= htmlspecialchars($student['accounts_id']) ?>">
                                            <button type="submit" class="btn btn-small btn-delete">Delete</button>
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
        function searchStudents() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            const rows = document.querySelectorAll('#studentsTableBody tr');
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchTerm) ? '' : 'none';
            });
        }

        function filterStudents() {
            const programFilter = document.getElementById('programFilter').value;
            const levelFilter = document.getElementById('levelFilter').value;
            const rows = document.querySelectorAll('#studentsTableBody tr');
            
            rows.forEach(row => {
                const programCell = row.cells[4].textContent.trim();
                const levelCell = row.cells[5].textContent.trim();
                
                const programMatch = !programFilter || programCell === programFilter;
                const levelMatch = !levelFilter || levelCell === levelFilter;
                
                row.style.display = programMatch && levelMatch ? '' : 'none';
            });
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
                window.location.href = 'logout.php';
            }
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
    </script>
</body>
</html>