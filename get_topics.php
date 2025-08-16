<?php
session_start();
require_once 'connection.php';

header('Content-Type: application/json');

try {
    // Verify user is logged in
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('Authentication required');
    }

    $course_code = $_GET['course_code'] ?? '';
    
    if (empty($course_code)) {
        throw new Exception('Course code is required');
    }

    // Get topics for the specified course taught by this instructor
    $stmt = $pdo->prepare("
        SELECT 
            topic_code, 
            topic_title, 
            description,
            created_at
        FROM topics 
        WHERE course_code = ? AND instructor_id = ?
        ORDER BY topic_code
    ");
    $stmt->execute([$course_code, $_SESSION['user_id']]);
    $topics = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'topics' => $topics
    ]);

} catch (PDOException $e) {
    error_log("Database error in get_topics: " . $e->getMessage());
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