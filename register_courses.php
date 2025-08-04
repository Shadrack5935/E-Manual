<?php
session_start();
require_once 'connection.php';

header('Content-Type: application/json');

$student_id = $_SESSION['user_id'] ?? null;

if (!$student_id) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$course_ids = $input['courses'] ?? [];

if (empty($course_ids)) {
    echo json_encode(['success' => false, 'message' => 'No courses selected']);
    exit;
}

try {
    $pdo->beginTransaction();
    
    $registered_count = 0;
    $errors = [];
    
    foreach ($course_ids as $course_id) {
        // Check if already enrolled
        $stmt = $pdo->prepare("SELECT id FROM course_enrollments WHERE student_id = ? AND course_id = ?");
        $stmt->execute([$student_id, $course_id]);
        
        if ($stmt->fetch()) {
            $errors[] = "Already enrolled in course ID: $course_id";
            continue;
        }
        
        // Check course capacity (assuming max 50 students per course)
        $stmt = $pdo->prepare("SELECT COUNT(*) as enrolled FROM course_enrollments WHERE course_id = ?");
        $stmt->execute([$course_id]);
        $enrollment = $stmt->fetch();
        
        if ($enrollment['enrolled'] >= 50) {
            $errors[] = "Course ID $course_id is full";
            continue;
        }
        
        // Register for the course
        $stmt = $pdo->prepare("INSERT INTO course_enrollments (student_id, course_id, enrollment_date) VALUES (?, ?, NOW())");
        $stmt->execute([$student_id, $course_id]);
        $registered_count++;
    }
    
    $pdo->commit();
    
    if ($registered_count > 0) {
        $message = "Successfully registered for $registered_count course(s)";
        if (!empty($errors)) {
            $message .= ". Some registrations failed: " . implode(', ', $errors);
        }
        echo json_encode(['success' => true, 'message' => $message]);
    } else {
        echo json_encode(['success' => false, 'message' => 'No courses were registered: ' . implode(', ', $errors)]);
    }
    
} catch(PDOException $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>