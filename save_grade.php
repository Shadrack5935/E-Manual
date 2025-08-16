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
    $submission_id = $_POST['submission_id'] ?? '';
    $grade = $_POST['grade'] ?? '';
    $letter_grade = $_POST['letter_grade'] ?? '';
    $feedback = $_POST['feedback'] ?? '';

    // Validate required fields
    if (empty($submission_id) || empty($grade) || empty($letter_grade)) {
        throw new Exception('Grade and letter grade are required');
    }

    // Verify the submission belongs to a task created by this instructor
    $stmt = $pdo->prepare("
        SELECT s.id 
        FROM submissions s
        INNER JOIN tasks t ON s.task_id = t.id
        WHERE s.id = ? AND t.instructor_id = ?
    ");
    $stmt->execute([$submission_id, $instructor_id]);
    
    if (!$stmt->fetch()) {
        throw new Exception('Submission not found or access denied');
    }

    // Update the submission with grade
    $stmt = $pdo->prepare("
        UPDATE submissions 
        SET grade = ?, letter_grade = ?, feedback = ?, status = 'graded', 
            graded_at = NOW(), graded_by = ?
        WHERE id = ?
    ");
    $stmt->execute([$grade, $letter_grade, $feedback, $instructor_id, $submission_id]);

    echo json_encode([
        'success' => true,
        'message' => 'Grade saved successfully'
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>