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
    $task_title = $_POST['taskTitle'] ?? '';
    $course_code = $_POST['courseCode'] ?? '';
    $topic_code = $_POST['topicCode'] ?? '';
    $task_type = $_POST['taskType'] ?? '';
    $description = $_POST['taskDescription'] ?? '';
    $due_date = $_POST['dueDate'] ?? '';
    $max_marks = (int)($_POST['maxMarks'] ?? 0);
    $allow_late = $_POST['allowLateSubmission'] ?? 'no';
    $students = $_POST['students'] ?? [];

    // Validate required fields
    if (empty($task_title) || empty($course_code) || empty($topic_code) || 
        empty($task_type) || empty($description) || empty($due_date) || 
        $max_marks <= 0 || empty($students)) {
        throw new Exception('All required fields must be filled');
    }

    $pdo->beginTransaction();

    // Insert task
    $stmt = $pdo->prepare("
        INSERT INTO tasks (
            task_title, course_code, topic_code, task_type, 
            description, due_date, max_marks, allow_late_submission, instructor_id
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $task_title, $course_code, $topic_code, $task_type, 
        $description, $due_date, $max_marks, $allow_late, $instructor_id
    ]);

    $task_id = $pdo->lastInsertId();

    // Assign task to selected students
    $stmt = $pdo->prepare("INSERT INTO task_assignments (task_id, student_id) VALUES (?, ?)");
    foreach ($students as $student_id) {
        if (!empty($student_id)) {
            $stmt->execute([$task_id, $student_id]);
        }
    }

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Task created and assigned successfully',
        'task_id' => $task_id
    ]);

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    error_log("Database error in create_task: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Database error occurred'
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