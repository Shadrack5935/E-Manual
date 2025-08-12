<?php
session_start();
require_once 'connection.php';

header('Content-Type: application/json');

$student_id = $_SESSION['user_id'] ?? null;

if (!$student_id || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Not authenticated or invalid method']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$course_ids = $data['courses'] ?? [];

if (empty($course_ids)) {
    echo json_encode(['success' => false, 'error' => 'No courses selected']);
    exit;
}

try {
    $pdo->beginTransaction();
    
    // First get course codes for the selected IDs
    $placeholders = implode(',', array_fill(0, count($course_ids), '?'));
    $stmt = $pdo->prepare("
        SELECT id, course_code FROM courses 
        WHERE id IN ($placeholders)
    ");
    $stmt->execute($course_ids);
    $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $enrolled_courses = [];
    foreach ($courses as $course) {
        // Check if already enrolled
        $check = $pdo->prepare("
            SELECT 1 FROM course_enrollments 
            WHERE course_code = ? AND accounts_id = ?
        ");
        $check->execute([$course['course_code'], $student_id]);
        
        if (!$check->fetch()) {
            // Enroll student
            $stmt = $pdo->prepare("
                INSERT INTO course_enrollments (course_code, accounts_id)
                VALUES (?, ?)
            ");
            $stmt->execute([$course['course_code'], $student_id]);
            $enrolled_courses[] = $course['course_code'];
        }
    }
    
    $pdo->commit();
    
    echo json_encode([
        'success' => true, 
        'message' => 'Successfully registered for ' . count($enrolled_courses) . ' courses',
        'enrolled_courses' => $enrolled_courses
    ]);
    
} catch(PDOException $e) {
    $pdo->rollBack();
    echo json_encode([
        'success' => false, 
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
?>