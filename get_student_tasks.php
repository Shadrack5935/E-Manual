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
    // Get tasks assigned to the student
    $stmt = $pdo->prepare("
        SELECT 
            t.id,
            t.task_title as title,
            t.task_description as description,
            t.task_type as type,
            t.due_date,
            t.max_marks,
            t.course_code,
            c.course_name,
            s.submission_date,
            s.submission_text,
            s.grade,
            s.feedback,
            CASE 
                WHEN s.grade IS NOT NULL THEN 'graded'
                WHEN s.submission_date IS NOT NULL THEN 'submitted'
                ELSE 'pending'
            END as status
        FROM tasks t
        JOIN courses c ON t.course_code = c.course_code
        JOIN course_enrollments ce ON c.id = ce.course_id
        LEFT JOIN task_submissions s ON t.id = s.task_id AND s.student_id = ?
        WHERE ce.student_id = ?
        ORDER BY t.due_date ASC
    ");
    
    $stmt->execute([$student_id, $student_id]);
    $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format the data
    $formatted_tasks = array_map(function($task) {
        return [
            'id' => $task['id'],
            'title' => $task['title'],
            'description' => $task['description'],
            'type' => ucfirst($task['type']),
            'dueDate' => date('M j, Y g:i A', strtotime($task['due_date'])),
            'maxMarks' => $task['max_marks'],
            'courseCode' => $task['course_code'],
            'courseName' => $task['course_name'],
            'status' => $task['status'],
            'submissionDate' => $task['submission_date'] ? date('M j, Y g:i A', strtotime($task['submission_date'])) : null,
            'submissionText' => $task['submission_text'],
            'grade' => $task['grade'],
            'feedback' => $task['feedback'],
            'isOverdue' => strtotime($task['due_date']) < time() && $task['status'] === 'pending'
        ];
    }, $tasks);
    
    echo json_encode($formatted_tasks);
    
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
?>