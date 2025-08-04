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

    // Get submissions for tasks created by this instructor
    $stmt = $pdo->prepare("
        SELECT 
            s.id,
            s.task_id,
            s.student_id,
            s.submission_text,
            s.submitted_at,
            s.status,
            s.grade,
            s.letter_grade,
            s.feedback,
            t.task_title,
            t.topic_code,
            tp.topic_title,
            t.course_code,
            t.max_marks,
            a.first_name,
            a.last_name,
            a.program
        FROM submissions s
        INNER JOIN tasks t ON s.task_id = t.id
        INNER JOIN topics tp ON t.topic_code = tp.topic_code
        INNER JOIN accounts a ON s.student_id = a.student_id
        WHERE t.instructor_id = ?
        ORDER BY s.submitted_at DESC
    ");
    $stmt->execute([$instructor['staff_id']]);
    $submissions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get statistics
    $total = count($submissions);
    $pending = count(array_filter($submissions, fn($s) => $s['status'] === 'pending'));
    $graded = count(array_filter($submissions, fn($s) => $s['status'] === 'graded'));
    $late = count(array_filter($submissions, fn($s) => $s['status'] === 'late'));
    
    $graded_submissions = array_filter($submissions, fn($s) => $s['grade'] !== null);
    $average_grade = count($graded_submissions) > 0 ? 
        round(array_sum(array_column($graded_submissions, 'grade')) / count($graded_submissions)) : 0;

    echo json_encode([
        'success' => true,
        'submissions' => $submissions,
        'stats' => [
            'total' => $total,
            'pending' => $pending,
            'graded' => $graded,
            'late' => $late,
            'average_grade' => $average_grade
        ]
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>