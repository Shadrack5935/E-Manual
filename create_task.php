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
    $task_title = $_POST['taskTitle'] ?? '';
    $course_code = $_POST['courseCode'] ?? '';
    $topic_code = $_POST['topicCode'] ?? '';
    $task_type = $_POST['taskType'] ?? '';
    $description = $_POST['taskDescription'] ?? '';
    $due_date = $_POST['dueDate'] ?? '';
    $max_marks = $_POST['maxMarks'] ?? '';
    $allow_late = $_POST['allowLateSubmission'] ?? 'no';
    $students = $_POST['students'] ?? [];

    // Validate required fields
    if (empty($task_title) || empty($course_code) || empty($topic_code) || 
        empty($task_type) || empty($description) || empty($due_date) || 
        empty($max_marks) || empty($students)) {
        throw new Exception('All required fields must be filled');
    }

    $pdo->beginTransaction();

    // Insert task
    $stmt = $pdo->prepare("
        INSERT INTO tasks (task_title, course_code, topic_code, task_type, description, due_date, max_marks, allow_late_submission, instructor_id)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $task_title, $course_code, $topic_code, $task_type, 
        $description, $due_date, $max_marks, $allow_late, $instructor['staff_id']
    ]);

    $task_id = $pdo->lastInsertId();

    // Assign task to selected students
    $stmt = $pdo->prepare("INSERT INTO task_assignments (task_id, student_id) VALUES (?, ?)");
    foreach ($students as $student_id) {
        $stmt->execute([$task_id, $student_id]);
    }

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Task created and assigned successfully',
        'task_id' => $task_id
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>