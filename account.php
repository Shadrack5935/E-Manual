<?php
session_start();
require_once 'connection.php';

$message = '';
$messageType = '';
$logout_message = '';

// Handle logout messages
if (isset($_SESSION['logout_message'])) {
    $logout_message = $_SESSION['logout_message'];
    unset($_SESSION['logout_message']);
}

// Redirect logged-in users to their dashboard
if (isset($_SESSION['user_id'])) {
    switch ($_SESSION['user_role']) {
        case 'admin':
            header("Location: adminDashboard.php");
            break;
        case 'instructor':
            header("Location: instructorDashboard.php");
            break;
        case 'student':
            header("Location: Studentdashboard.php");
            break;
    }
    exit();
}

// Helper functions
// function sanitizeInput($input) {
//     return htmlspecialchars(trim($input));
// }
// function generateToken($length = 32) {
//     return bin2hex(random_bytes($length));
// }
// function sendPasswordResetEmail($email, $token) {
//     // Implement your mail sending logic here
//     // mail($email, "Password Reset", "Reset link: ...?token=$token");
// }
function getCurrentAcademicYear() {
    $currentMonth = date('n'); // Get current month (1-12)
    $currentYear = date('Y');
    
    // Academic year runs from September to August
    if ($currentMonth >= 9) { // September or later
        return $currentYear . '-' . ($currentYear + 1);
    } else { // January-August
        return ($currentYear - 1) . '-' . $currentYear;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'login':
            $loginId = sanitizeInput($_POST['login_id']);
            $password = $_POST['password'];

            if (empty($loginId) || empty($password)) {
                $message = 'ID and password are required';
                $messageType = 'error';
            } else {
                // Try student login
                $stmt = $pdo->prepare("SELECT id, fullname, email, phone, pass, is_verified, role FROM accounts WHERE student_id = ? AND role = 'student' AND is_active = 1");
                $stmt->execute([$loginId]);
                $user = $stmt->fetch();

                // If not found, try staff/instructor/admin login
                if (!$user) {
                    $stmt = $pdo->prepare("SELECT id, fullname, email, phone, pass, is_verified, role FROM accounts WHERE staff_id = ? AND role IN ('admin','instructor') AND is_active = 1");
                    $stmt->execute([$loginId]);
                    $user = $stmt->fetch();
                }

                if ($user && password_verify($password, $user['pass'])) {
                    if (!$user['is_verified']) {
                        $message = 'Please verify your account before logging in';
                        $messageType = 'error';
                    } else {
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['user_name'] = $user['fullname'];
                        $_SESSION['user_email'] = $user['email'];
                        $_SESSION['user_role'] = $user['role'];
                        $_SESSION['login_time'] = time();

                        $updateStmt = $pdo->prepare("UPDATE accounts SET last_login = NOW() WHERE id = ?");
                        $updateStmt->execute([$user['id']]);

                        // Redirect based on role
                        switch ($user['role']) {
                            case 'admin':
                                header("Location: adminDashboard.php");
                                break;
                            case 'instructor':
                                header("Location: instructorDashboard.php");
                                break;
                            case 'student':
                                header("Location: Studentdashboard.php");
                                break;
                        }
                        exit();
                    }
                } else {
                    $message = 'Invalid ID or password';
                    $messageType = 'error';
                }
            }
            break;

        case 'signup':
            $studentId = sanitizeInput($_POST['student_id']);
            $firstName = sanitizeInput($_POST['first_name']);
            $lastName = sanitizeInput($_POST['last_name']);
            $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
            $program = $_POST['program'];
            $phone = $_POST['phone'];
            $password = $_POST['password'];
            $confirmPassword = $_POST['confirm_password'];

            // Validation
            if (empty($studentId) || empty($firstName) || empty($lastName) || empty($email) || empty($program) ||
                empty($phone) || empty($password) || empty($confirmPassword)) {
                $message = 'All fields are required';
                $messageType = 'error';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $message = 'Invalid email format';
                $messageType = 'error';
            } elseif ($password !== $confirmPassword) {
                $message = 'Passwords do not match';
                $messageType = 'error';
            } else {
                // Check if email exists
                $stmt = $pdo->prepare("SELECT id FROM accounts WHERE email = ?");
                $stmt->execute([$email]);
                if ($stmt->fetch()) {
                    $message = 'Email already registered';
                    $messageType = 'error';
                } else {
                    // Check if student ID exists
                    $stmt = $pdo->prepare("SELECT id FROM accounts WHERE student_id = ?");
                    $stmt->execute([$studentId]);
                    if ($stmt->fetch()) {
                        $message = 'Student ID already registered';
                        $messageType = 'error';
                    } else {
                        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                        $verificationToken = generateToken();
                        $academicYear = getCurrentAcademicYear();

                        $stmt = $pdo->prepare("INSERT INTO accounts 
                            (student_id, first_name, last_name, email, pass, verification_token, phone, program, role, created_at, is_active, is_verified, academic_year) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'student', NOW(), 1, 1, ?)");

                        if ($stmt->execute([$studentId, $firstName, $lastName, $email, $hashedPassword, $verificationToken, $phone, $program, $academicYear])) {
                            $message = 'Account created successfully! Please check your email to verify your account.';
                            $messageType = 'success';
                            echo "<script>setTimeout(() => showForm('login'), 2000);</script>";
                        } else {
                            $message = 'Error creating account. Please try again.';
                            $messageType = 'error';
                        }
                    }
                }
            }
            break;

        case 'reset':
            $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);

            if (empty($email)) {
                $message = 'Email is required';
                $messageType = 'error';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $message = 'Invalid email format';
                $messageType = 'error';
            } else {
                $stmt = $pdo->prepare("SELECT id FROM accounts WHERE email = ?");
                $stmt->execute([$email]);
                $user = $stmt->fetch();

                if ($user) {
                    // Send reset link to the email on file
                    $resetToken = generateToken();
                    $pdo->prepare("UPDATE accounts SET reset_token = ?, reset_token_expiry = DATE_ADD(NOW(), INTERVAL 1 HOUR) WHERE email = ?")
                       ->execute([$resetToken, $email]);

                    // Send email (implementation depends on your mail system)
                    sendPasswordResetEmail($email, $resetToken);

                    $message = 'Password reset link sent to your registered email';
                    $messageType = 'success';
                } else {
                    $message = 'If this email exists, a reset link was sent';
                    $messageType = 'success';
                }
            }
            break;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CodeLab - Authentication</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .auth-container {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 450px;
        }

        .auth-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .auth-header h1 {
            color: #333;
            margin-bottom: 0.5rem;
        }

        .auth-header p {
            color: #666;
            font-size: 0.9rem;
        }

        .message {
            padding: 0.75rem;
            border-radius: 5px;
            margin-bottom: 1.5rem;
            border-left: 4px solid;
        }

        .message.error {
            background: #fee;
            color: #c33;
            border-color: #c33;
        }

        .message.success {
            background: #efe;
            color: #363;
            border-color: #363;
        }

        .form-nav {
            display: flex;
            margin-bottom: 1.5rem;
            border-bottom: 1px solid #e1e5e9;
        }

        .nav-btn {
            flex: 1;
            padding: 0.75rem;
            background: none;
            border: none;
            border-bottom: 3px solid transparent;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .nav-btn.active {
            border-bottom-color: #667eea;
            color: #667eea;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: #333;
            font-weight: 500;
        }

        .form-group input {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e1e5e9;
            border-radius: 5px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }

        .form-group input:focus {
            outline: none;
            border-color: #667eea;
        }

        .password-container {
            position: relative;
        }

        .toggle-password {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
        }

        .btn {
            width: 100%;
            padding: 0.75rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 1rem;
            cursor: pointer;
            transition: transform 0.2s ease;
        }

        .btn:hover {
            transform: translateY(-2px);
        }

        .form-footer {
            text-align: center;
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 1px solid #e1e5e9;
        }
        .form-select {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e1e5e9;
            border-radius: 5px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }

        .form-select:focus{
            outline: none;
            border-color: #667eea;
        }

        .form-link {
            color: #667eea;
            text-decoration: none;
        }

        .form-link:hover {
            text-decoration: underline;
        }

        /* Hide all forms by default */
        .form {
            display: none;
        }

        /* Show active form */
        .form.active {
            display: block;
        }
    </style>
</head>
<body>
    <div class="auth-container">
        <div class="auth-header">
            <h1><i class="fas fa-code"></i> CodeLab</h1>
            <p>Master the art of programming</p>
        </div>

        <?php if ($message): ?>
            <div class="message <?php echo $messageType; ?>"><?php echo $message; ?></div>
        <?php endif; ?>

        <div class="form-nav" id="formNav">
            <button class="nav-btn active" onclick="showForm('login')">Login</button>
            <button class="nav-btn" onclick="showForm('signup')">Sign Up</button>
        </div>

        <!-- Login Form -->
        <form class="form active" id="loginForm" method="POST">
            <input type="hidden" name="action" value="login">
            <div class="form-group">
                <label>ID Number</label>
                <input type="text" name="login_id" placeholder="Enter your Student ID or Staff ID" required>
            </div>
            <div class="form-group">
                <label>Password</label>
                <div class="password-container">
                    <input type="password" name="password" placeholder="Enter your password" required>
                    <button type="button" class="toggle-password" onclick="togglePassword(this)">👁️</button>
                </div>
            </div>
            <button type="submit" class="btn">
                <i class="fas fa-sign-in-alt"></i> Sign In
            </button>
            <div class="form-footer">
                <a href="#" class="form-link" onclick="showForm('reset')">Forgot Password?</a>
            </div>
        </form>

        <!-- Signup Form -->
        <form class="form" id="signupForm" method="POST">
            <input type="hidden" name="action" value="signup">
            <div class="form-row">
                <div class="form-group">
                    <label>Student ID</label>
                    <input type="text" name="student_id" placeholder="e.g., CS20230001" required>
                </div>
                <div class="form-group">
                    <label>First Name</label>
                    <input type="text" name="first_name" placeholder="First name" required>
                </div>
                <div class="form-group">
                    <label>Last Name</label>
                    <input type="text" name="last_name" placeholder="Last name" required>
                </div>
            </div>
            <div class="form-group">
                <label>Email Address</label>
                <input type="email" name="email" placeholder="Enter your email" required>
            </div>
            <div class="form-group">
                <label>Program</label>
                <select name="program" class="form-select" required>
                    <option value="">Select Program</option>
                    <option value="Computer Science">Computer Science</option>
                    <option value="Information Technology">Information Technology</option>
                    <option value="Software Engineering">Software Engineering</option>
                    <option value="Data Science">Data Science</option>
                </select>
            </div>
            <div class="form-group">
                <label>Phone Number</label>
                <input type="text" name="phone" placeholder="Enter your phone number" required>
            </div>
            <div class="form-group">
                <label>Password</label>
                <div class="password-container">
                    <input type="password" name="password" id="signupPassword" placeholder="Create a strong password" required>
                    <button type="button" class="toggle-password" onclick="togglePassword(this)">👁️</button>
                </div>
            </div>
            <div class="form-group">
                <label>Confirm Password</label>
                <div class="password-container">
                    <input type="password" name="confirm_password" placeholder="Confirm your password" required>
                    <button type="button" class="toggle-password" onclick="togglePassword(this)">👁️</button>
                </div>
            </div>
            <button type="submit" class="btn">
                <i class="fas fa-user-plus"></i> Create Account
            </button>
        </form>

        <!-- Password Reset Form -->
        <form class="form" id="resetForm" method="POST">
            <input type="hidden" name="action" value="reset">
            <div class="form-group">
                <label>Email Address</label>
                <input type="email" name="email" placeholder="Enter your email address" required>
            </div>
            <p style="color: #666; font-size: 0.9rem; margin-bottom: 1.5rem;">
                We'll send you a secure link to reset your password.
            </p>
            <div style="display: flex; gap: 1rem;">
                <button type="button" class="btn" style="background: #6c757d;" onclick="showForm('login')">Back</button>
                <button type="submit" class="btn">Send Reset Link</button>
            </div>
            <div class="form-footer">
                <p style="color: #666; font-size: 0.85rem;">
                    Remember your password? <a href="#" class="form-link" onclick="showForm('login')">Sign in</a>
                </p>
            </div>
        </form>
    </div>

    <script>
        function showForm(formType) {
            const forms = document.querySelectorAll('.form');
            const navBtns = document.querySelectorAll('.nav-btn');
            const formNav = document.getElementById('formNav');
            // Hide all forms
            forms.forEach(form => form.classList.remove('active'));
            // Show selected form
            document.getElementById(formType + 'Form').classList.add('active');
            // Handle navigation buttons
            if (formType === 'reset') {
                formNav.style.display = 'none';
            } else {
                formNav.style.display = 'flex';
                navBtns.forEach(btn => btn.classList.remove('active'));
                if (formType === 'login') {
                    navBtns[0].classList.add('active');
                } else if (formType === 'signup') {
                    navBtns[1].classList.add('active');
                }
            }
        }
        function togglePassword(button) {
            const input = button.previousElementSibling;
            if (input.type === 'password') {
                input.type = 'text';
                button.textContent = '🙈';
            } else {
                input.type = 'password';
                button.textContent = '👁️';
            }
        }
        <?php if (isset($_POST['action']) && $_POST['action'] === 'signup'): ?>
            showForm('signup');
        <?php elseif (isset($_POST['action']) && $_POST['action'] === 'reset'): ?>
            showForm('reset');
        <?php endif; ?>
    </script>
</body>
</html>