<?php
// logout.php
session_start();

// Unset all session variables
$_SESSION = array();

// If you want to kill the session cookie as well
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy the session
session_destroy();

// Optional: Add a logout message to display on accounts page
session_start();
$_SESSION['logout_message'] = "You have been successfully logged out.";

// Redirect to accounts page with login form active
header("Location: account.php?form=login&logout=success");
exit();
?>