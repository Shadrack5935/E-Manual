<?php
session_start();
require_once 'connection.php';

header('Content-Type: application/json');

try {
    $course_code = $_GET['course_code'] ?? '';
    $instructor_id = $_SESSION['user_id'] ?? null;
    
    if (!$instructor_id || !$course_code) {
        throw new Exception('Missing required parameters');
    }

    // Get instructor's staff_id
    $stmt = $pdo->prepare("SELECT staff_id FROM accounts WHERE id = ?");
    $stmt->execute([$instructor_id]);
    $instructor = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$instructor) {
        throw new Exception('Instructor not found');
    }

    // Get topics for the specified course
    $stmt = $pdo->prepare("
        SELECT topic_code, topic_title, description 
        FROM topics 
        WHERE course_code = ? AND instructor_id = ?
        ORDER BY topic_code
    ");
    $stmt->execute([$course_code, $instructor['staff_id']]);
    $topics = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'topics' => $topics
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>