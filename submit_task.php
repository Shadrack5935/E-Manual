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

$task_id = $_POST['task_id'] ?? null;
$submission_text = $_POST['submission_text'] ?? '';

if (!$task_id) {
    echo json_encode(['success' => false, 'message' => 'Task ID is required']);
    exit;
}

try {
    // Check if task exists and student is enrolled in the course
    $stmt = $pdo->prepare("
        SELECT t.id, t.due_date, c.id as course_id
        FROM tasks t
        JOIN courses c ON t.course_code = c.course_code
        JOIN course_enrollments ce ON c.id = ce.course_id
        WHERE t.id = ? AND ce.student_id = ?
    ");
    $stmt->execute([$task_id, $student_id]);
    $task = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$task) {
        echo json_encode(['success' => false, 'message' => 'Task not found or you are not enrolled in this course']);
        exit;
    }
    
    // Check if already submitted
    $stmt = $pdo->prepare("SELECT id FROM task_submissions WHERE task_id = ? AND student_id = ?");
    $stmt->execute([$task_id, $student_id]);
    
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Task already submitted']);
        exit;
    }
    
    // Handle file uploads
    $uploaded_files = [];
    if (isset($_FILES['submission_files'])) {
        $upload_dir = '../uploads/submissions/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        foreach ($_FILES['submission_files']['tmp_name'] as $key => $tmp_name) {
            if ($_FILES['submission_files']['error'][$key] === UPLOAD_ERR_OK) {
                $file_name = $_FILES['submission_files']['name'][$key];
                $file_extension = pathinfo($file_name, PATHINFO_EXTENSION);
                $new_file_name = $student_id . '_' . $task_id . '_' . time() . '_' . $key . '.' . $file_extension;
                $upload_path = $upload_dir . $new_file_name;
                
                if (move_uploaded_file($tmp_name, $upload_path)) {
                    $uploaded_files[] = $new_file_name;
                }
            }
        }
    }
    
    // Insert submission
    $stmt = $pdo->prepare("
        INSERT INTO task_submissions (task_id, student_id, submission_text, submission_files, submission_date) 
        VALUES (?, ?, ?, ?, NOW())
    ");
    $stmt->execute([
        $task_id, 
        $student_id, 
        $submission_text, 
        json_encode($uploaded_files)
    ]);
    
    echo json_encode(['success' => true, 'message' => 'Task submitted successfully']);
    
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>