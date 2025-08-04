<?php
session_start();
require_once 'connection.php';

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$message = '';
$messageType = '';

// Handle Add/Edit User form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Sanitize and validate input
    $staff_Id = trim($_POST['staffId']);
    $firstName = htmlspecialchars(trim($_POST['firstName']));
    $lastName = htmlspecialchars(trim($_POST['lastName']));
    $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
    $phone = htmlspecialchars(trim($_POST['phone']));
    $role = htmlspecialchars(trim($_POST['role']));
    $password = $_POST['password'] ?? '';
    $edit_user_id = $_POST['edit_user_id'] ?? '';

    // Validation
    if (empty($staff_Id) || empty($firstName) || empty($lastName) || empty($email) || empty($phone) || empty($role) || (empty($password) && !$edit_user_id)) {
        $message = 'All fields are required';
        $messageType = "error";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = 'Invalid email format';
        $messageType = 'error';
    } else {
        try {
            if ($edit_user_id) {
                // Update existing user
                $params = [$firstName, $lastName, $email, $phone, $role, $staff_Id];
                $sql = "UPDATE accounts SET first_name=?, last_name=?, email=?, phone=?, role=?";
                
                if (!empty($password)) {
                    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                    $sql .= ", pass=?";
                    $params[] = $hashedPassword;
                }
                
                $sql .= " WHERE staff_id=?";
                $params[] = $staff_Id;
                
                $stmt = $pdo->prepare($sql);
                if ($stmt->execute($params)) {
                    $message = 'User updated successfully!';
                    $messageType = 'success';
                } else {
                    $message = 'Error updating user.';
                    $messageType = 'error';
                }
            } else {
                // Create new user
                // Check for duplicate email
                $stmt = $pdo->prepare("SELECT id FROM accounts WHERE email = ?");
                $stmt->execute([$email]);
                if ($stmt->fetch()) {
                    $message = 'Email already exists';
                    $messageType = "error";
                } else {
                    // Check for duplicate staff ID
                    $stmt = $pdo->prepare("SELECT id FROM accounts WHERE staff_id = ?");
                    $stmt->execute([$staff_Id]);
                    if ($stmt->fetch()) {
                        $message = 'Staff ID already exists';
                        $messageType = "error";
                    } else {
                        // Insert new user
                        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                        $verificationToken = bin2hex(random_bytes(16));
                        
                        $stmt = $pdo->prepare("INSERT INTO accounts 
                            (staff_id, first_name, last_name, email, pass, verification_token, phone, role, created_at, is_active, is_verified) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), 1, 1)");
                            
                        if ($stmt->execute([$staff_Id, $firstName, $lastName, $email, $hashedPassword, $verificationToken, $phone, $role])) {
                            $message = 'Account created successfully!';
                            $messageType = 'success';
                            // Clear form after successful submission
                            $_POST = array();
                        } else {
                            $errorInfo = $stmt->errorInfo();
                            $message = 'Error creating account: ' . $errorInfo[2];
                            $messageType = 'error';
                        }
                    }
                }
            }
        } catch (PDOException $e) {
            $message = 'Database error: ' . $e->getMessage();
            $messageType = 'error';
        }
    }
}

// Handle edit request (populate form)
$edit_user = null;
if (isset($_GET['edit_user_id'])) {
    try {
        $edit_id = $_GET['edit_user_id'];
        $stmt = $pdo->prepare("SELECT * FROM accounts WHERE staff_id = ? OR student_id = ?");
        $stmt->execute([$edit_id, $edit_id]);
        $edit_user = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $message = 'Error fetching user data: ' . $e->getMessage();
        $messageType = 'error';
    }
}

// Handle delete request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user_id'])) {
    try {
        $delete_id = $_POST['delete_user_id'];
        $stmt = $pdo->prepare("DELETE FROM accounts WHERE id = ?");
        if ($stmt->execute([$delete_id])) {
            $message = 'User deleted successfully';
            $messageType = 'success';
        } else {
            $message = 'Error deleting user';
            $messageType = 'error';
        }
        header("Location: addusers.php");
        exit;
    } catch (PDOException $e) {
        $message = 'Error deleting user: ' . $e->getMessage();
        $messageType = 'error';
    }
}

// Fetch current admin info
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

$full_name = ($user['first_name'] ?? 'Admin') . ' ' . ($user['last_name'] ?? '');
$initials = getUserInitials($full_name);

// Fetch all users
try {
    $users = $pdo->query("SELECT id, staff_id, student_id, first_name, last_name, email, phone, role FROM accounts ORDER BY staff_id")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $users = [];
    $message = 'Error fetching users: ' . $e->getMessage();
    $messageType = 'error';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CodeLab - User Management</title>
    <link rel="stylesheet" href="dasboard.css">
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
                    <a href="adminDashboard.php" class="sidebar-link">
                        <span class="sidebar-icon">👤</span>
                        My Profile
                    </a>
                </li>
                <li class="sidebar-item">
                    <a href="adminDashboard.php" class="sidebar-link">
                        <span class="sidebar-icon">📊</span>
                        Dashboard
                    </a>
                </li>
                <li class="sidebar-item">
                    <a href="addusers.php" class="sidebar-link active">
                        <span class="sidebar-icon">➕</span>
                        Add Users
                    </a>
                </li>
                <li class="sidebar-item">
                    <a href="addCourse.php" class="sidebar-link">
                        <span class="sidebar-icon">📚</span>
                        Add Courses
                    </a>
                </li>
            </ul>
        </nav>

        <main class="content-area">
            <div id="add-users" class="page-section active">
                <div class="section-card">
                    <h2 class="section-title"><?= $edit_user ? '✏️ Edit User' : '➕ Add New User' ?></h2>
                    <?php if ($message): ?>
                        <div class="message <?= htmlspecialchars($messageType) ?>"><?= htmlspecialchars($message) ?></div>
                    <?php endif; ?>
                    <form id="addUserForm" class="add-user-form" method="POST" action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>">
                        <?php if ($edit_user): ?>
                            <input type="hidden" name="edit_user_id" value="<?= htmlspecialchars($edit_user['staff_id'] ?: $edit_user['student_id']) ?>">
                        <?php endif; ?>
 <div class="form-group">
    <label for="staffId" class="form-label">Staff ID</label>
    <input type="text" id="staffId" name="staffId" class="form-input" required 
           value="<?= isset($edit_user['staff_id']) ? htmlspecialchars($edit_user['staff_id']) : (isset($edit_user['student_id']) ? htmlspecialchars($edit_user['student_id']) : '') ?>" 
           <?= isset($edit_user) ? 'readonly' : '' ?>>
</div>
                        <div class="form-group">
                            <label for="firstName" class="form-label">First Name</label>
                            <input type="text" id="firstName" name="firstName" class="form-input" required 
                                   value="<?= htmlspecialchars($edit_user['first_name'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label for="lastName" class="form-label">Last Name</label>
                            <input type="text" id="lastName" name="lastName" class="form-input" required 
                                   value="<?= htmlspecialchars($edit_user['last_name'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" id="email" name="email" class="form-input" required 
                                   value="<?= htmlspecialchars($edit_user['email'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label for="phone" class="form-label">Phone Number</label>
                            <input type="tel" id="phone" name="phone" class="form-input" required 
                                   value="<?= htmlspecialchars($edit_user['phone'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label for="role" class="form-label">Role</label>
                            <select id="role" name="role" class="form-select" required>
                                <option value="">Select Role</option>
                                <option value="student" <?= isset($edit_user['role']) && $edit_user['role'] == 'student' ? 'selected' : '' ?>>Student</option>
                                <option value="instructor" <?= (isset($edit_user['role']) && $edit_user['role'] == 'instructor' ? 'selected' : '') ?>>Instructor</option>
                                <option value="admin" <?= (isset($edit_user['role']) && $edit_user['role'] == 'admin' ? 'selected' : '') ?>>Admin</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="password" class="form-label"><?= $edit_user ? 'New Password (leave blank to keep current)' : 'Password' ?></label>
                            <input type="password" id="password" name="password" class="form-input" 
                                   <?= $edit_user ? '' : 'required' ?>>
                        </div>
                        <div class="form-group full-width">
                            <div class="form-buttons">
                                <button type="button" class="btn btn-secondary" onclick="window.location='addusers.php'">Cancel</button>
                                <button type="submit" class="btn btn-primary"><?= $edit_user ? 'Update User' : 'Add User' ?></button>
                            </div>
                        </div>
                    </form>
                </div>
                
                <div class="section-card" style="margin-top: 2rem;">
                    <h2 class="section-title">👥 User Management</h2>
                    <div class="search-filter">
                        <input type="text" id="searchInput" class="search-input" placeholder="Search users..." onkeyup="searchUsers()">
                        <select id="roleFilter" class="filter-select" onchange="filterUsers()">
                            <option value="">All Roles</option>
                            <option value="student">Student</option>
                            <option value="instructor">Instructor</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                    <table class="users-table" id="usersTable">
                        <thead>
                            <tr>
                                <th>Staff/Student ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Role</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="usersTableBody">
                            <?php foreach ($users as $u): ?>
                                <tr>
                                    <td><?= htmlspecialchars($u['student_id'] ?: $u['staff_id']) ?></td>
                                    <td><?= htmlspecialchars($u['first_name'] . ' ' . $u['last_name']) ?></td>
                                    <td><?= htmlspecialchars($u['email']) ?></td>
                                    <td><?= htmlspecialchars($u['phone']) ?></td>
                                    <td>
                                        <span class="user-role role-<?= htmlspecialchars(strtolower($u['role'])) ?>">
                                            <?= ucfirst(htmlspecialchars($u['role'])) ?>
                                        </span>
                                    </td>
                                    <td class="action-buttons">
                                        <form method="GET" style="display:inline;">
                                            <input type="hidden" name="edit_user_id" value="<?= htmlspecialchars($u['staff_id'] ?: $u['student_id']) ?>">
                                            <button type="submit" class="btn btn-small btn-edit">Edit</button>
                                        </form>
                                        <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this user?');">
                                            <input type="hidden" name="delete_user_id" value="<?= htmlspecialchars($u['id']) ?>">
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
        function searchUsers() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            const rows = document.querySelectorAll('#usersTableBody tr');
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchTerm) ? '' : 'none';
            });
        }

        function filterUsers() {
    const roleFilter = document.getElementById('roleFilter').value;
    const rows = document.querySelectorAll('#usersTableBody tr');
    
    rows.forEach(row => {
        const roleCell = row.querySelector('.user-role');
        if (!roleCell) {
            row.style.display = 'none';
            return;
        }
        
        const roleText = roleCell.textContent.trim().toLowerCase();
        const filterValue = roleFilter.toLowerCase();
        
        if (!filterValue || roleText === filterValue) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
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