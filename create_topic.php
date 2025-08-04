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

    // Get form data
    $topic_title = $_POST['topicTitle'] ?? '';
    $topic_code = $_POST['topicCode'] ?? '';
    $course_code = $_POST['courseCode'] ?? '';
    $description = $_POST['topicDescription'] ?? '';
    $program = $_POST['category'] ?? '';

    // Validate required fields
    if (empty($topic_title) || empty($topic_code) || empty($course_code) || 
        empty($description) || empty($program)) {
        throw new Exception('All required fields must be filled');
    }

    // Check if topic code already exists
    $stmt = $pdo->prepare("SELECT id FROM topics WHERE topic_code = ?");
    $stmt->execute([$topic_code]);
    if ($stmt->fetch()) {
        throw new Exception('Topic code already exists');
    }

    // Insert topic
    $stmt = $pdo->prepare("
        INSERT INTO topics (topic_code, topic_title, course_code, description, program, instructor_id)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $topic_code, $topic_title, $course_code, 
        $description, $program, $instructor['staff_id']
    ]);

    echo json_encode([
        'success' => true,
        'message' => 'Topic created successfully'
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>