<?php
session_start();
require_once 'connection.php';

header('Content-Type: application/json');

try {
    $instructor_id = $_SESSION['user_id'] ?? null;
    
    if (!$instructor_id) {
        throw new Exception('User not authenticated');
    }

    // Get instructor's staff_id
    $stmt = $pdo->prepare("SELECT staff_id FROM accounts WHERE id = ?");
    $stmt->execute([$instructor_id]);
    $instructor = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$instructor) {
        throw new Exception('Instructor not found');
    }

    // Get courses taught by this instructor
    $stmt = $pdo->prepare("
        SELECT course_code, course_name, credits, program, semester, description 
        FROM courses 
        WHERE instructor_id = ? 
        ORDER BY course_code
    ");
    $stmt->execute([$instructor['staff_id']]);
    $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'courses' => $courses
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>