<?php
session_start();
require_once 'connection.php';

header('Content-Type: application/json');

try {
    $instructor_id = $_SESSION['user_id'] ?? null;
    
    if (!$instructor_id) {
        throw new Exception('User not authenticated');
    }

    // Get form data
    $topic_title = trim($_POST['topicTitle'] ?? '');
    $topic_code = trim($_POST['topicCode'] ?? '');
    $course_code = trim($_POST['courseCode'] ?? '');
    $description = trim($_POST['topicDescription'] ?? '');

    // Validate required fields
    if (empty($topic_title)) {
        throw new Exception('Topic title is required');
    }
    if (empty($topic_code)) {
        throw new Exception('Topic code is required');
    }
    if (empty($course_code)) {
        throw new Exception('Course code is required');
    }
    if (empty($description)) {
        throw new Exception('Description is required');
    }

    // Check if topic code already exists
    $stmt = $pdo->prepare("SELECT id FROM topics WHERE topic_code = ?");
    $stmt->execute([$topic_code]);
    if ($stmt->fetch()) {
        throw new Exception('Topic code already exists');
    }

    // Verify course exists and is taught by this instructor
    $stmt = $pdo->prepare("SELECT course_code FROM courses WHERE course_code = ? AND instructor_id = ?");
    $stmt->execute([$course_code, $instructor_id]);
    if (!$stmt->fetch()) {
        throw new Exception('Course not found or you are not the instructor');
    }

    // Insert topic
    $stmt = $pdo->prepare("
        INSERT INTO topics (
            topic_code, topic_title, course_code, 
            description, instructor_id
        ) VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $topic_code, $topic_title, $course_code, 
        $description, $instructor_id
    ]);

    echo json_encode([
        'success' => true,
        'message' => 'Topic created successfully',
        'topic_code' => $topic_code
    ]);

} catch (PDOException $e) {
    error_log("Database error in create_topic: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Database error occurred'
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>