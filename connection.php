<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "codelab";

try {
    $pdo = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    // echo "Database connection successful!<br>";
} catch(PDOException $e) {
    error_log("Database connection failed: " . $e->getMessage());
    die("Database connection failed. Please try again later.");
}
// Helper functions
function sanitizeInput($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

function generateToken($length = 32) {
    return bin2hex(random_bytes($length));
}

function sendEmail($to, $subject, $message) {
    error_log("Email to $to: $subject - $message");
    return true;
}

?>