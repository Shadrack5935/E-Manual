<?php
session_start();
require_once 'connection.php';

header('Content-Type: application/json');

$student_id = $_SESSION['user_id'] ?? null;

if (!$student_id) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

try {
    // Get all courses the student is enrolled in
    $stmt = $pdo->prepare("
        SELECT 
            c.id,
            c.course_code,
            c.course_name,
            c.credits,
            c.schedule,
            c.description,
            a.fullname as instructor,
            ce.enrolled_at,
            (SELECT COUNT(*) FROM course_enrollments WHERE course_code = c.course_code) as enrolled_count
        FROM course_enrollments ce
        JOIN courses c ON ce.course_code = c.course_code
        LEFT JOIN accounts a ON c.instructor_id = a.accounts_id
        WHERE ce.accounts_id = ?
        ORDER BY c.course_name
    ");
    
    $stmt->execute([$student_id]);
    $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true, 
        'courses' => $courses
    ]);
    
} catch(PDOException $e) {
    echo json_encode([
        'success' => false, 
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
?>