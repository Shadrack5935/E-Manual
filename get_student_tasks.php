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
    // Get all tasks for the student's enrolled courses
    $stmt = $pdo->prepare("
        SELECT 
            t.id,
            t.task_title as title,
            t.course_code,
            c.course_name,
            t.topic_code,
            tp.topic_title,
            t.task_type,
            t.description,
            t.due_date,
            t.max_marks,
            t.allow_late_submission,
            s.status,
            s.grade,
            s.letter_grade,
            s.feedback,
            s.submitted_at,
            s.graded_at,
            a.fullname as instructor
        FROM tasks t
        JOIN course_enrollments ce ON t.course_code = ce.course_code
        JOIN courses c ON t.course_code = c.course_code
        LEFT JOIN topics tp ON t.topic_code = tp.topic_code
        LEFT JOIN submissions s ON t.id = s.task_id AND s.student_id = ?
        LEFT JOIN accounts a ON c.instructor_id = a.accounts_id
        WHERE ce.accounts_id = ?
        ORDER BY t.due_date ASC
    ");
    
    $stmt->execute([$student_id, $student_id]);
    $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format dates and set default status if not submitted
    $formatted_tasks = array_map(function($task) {
        return [
            'id' => $task['id'],
            'title' => $task['title'],
            'courseCode' => $task['course_code'],
            'courseName' => $task['course_name'],
            'topicCode' => $task['topic_code'],
            'topicTitle' => $task['topic_title'],
            'type' => $task['task_type'],
            'description' => $task['description'],
            'dueDate' => $task['due_date'],
            'maxMarks' => $task['max_marks'],
            'allowLateSubmission' => $task['allow_late_submission'] === 'yes',
            'status' => $task['status'] ?? 'pending',
            'grade' => $task['grade'],
            'letterGrade' => $task['letter_grade'],
            'feedback' => $task['feedback'],
            'submittedAt' => $task['submitted_at'],
            'gradedAt' => $task['graded_at'],
            'instructor' => $task['instructor']
        ];
    }, $tasks);
    
    echo json_encode([
        'success' => true, 
        'tasks' => $formatted_tasks
    ]);
    
} catch(PDOException $e) {
    echo json_encode([
        'success' => false, 
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
?>