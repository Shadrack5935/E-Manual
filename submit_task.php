<?php
session_start();
require_once 'connection.php';

header('Content-Type: application/json');

$student_id = $_SESSION['user_id'] ?? null;

if (!$student_id || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Not authenticated or invalid method']);
    exit;
}

// Get form data
$task_id = $_POST['task_id'] ?? null;
$course_code = $_POST['course_code'] ?? null;
$submission_text = $_POST['submission_text'] ?? '';
$files = $_FILES['submission_files'] ?? null;

if (!$task_id || !$course_code) {
    echo json_encode(['success' => false, 'error' => 'Missing required fields']);
    exit;
}

try {
    // Check if task exists and is assigned to student's course
    $stmt = $pdo->prepare("
        SELECT 1 FROM tasks t
        JOIN course_enrollments ce ON t.course_code = ce.course_code
        WHERE t.id = ? AND ce.accounts_id = ?
    ");
    $stmt->execute([$task_id, $student_id]);
    
    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'error' => 'Task not found or not assigned to you']);
        exit;
    }

    // Handle file uploads
    $file_paths = [];
    if (!empty($files['name'][0])) {
        $uploadDir = 'uploads/submissions/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        for ($i = 0; $i < count($files['name']); $i++) {
            $fileName = uniqid() . '_' . basename($files['name'][$i]);
            $targetPath = $uploadDir . $fileName;
            
            if (move_uploaded_file($files['tmp_name'][$i], $targetPath)) {
                $file_paths[] = $targetPath;
            }
        }
    }
    
    $file_paths_str = implode(',', $file_paths);
    $current_time = date('Y-m-d H:i:s');
    
    // Check if submission already exists
    $stmt = $pdo->prepare("
        SELECT id FROM submissions 
        WHERE task_id = ? AND student_id = ?
    ");
    $stmt->execute([$task_id, $student_id]);
    
    if ($existing = $stmt->fetch()) {
        // Update existing submission
        $stmt = $pdo->prepare("
            UPDATE submissions SET
                submission_text = ?,
                file_path = ?,
                submitted_at = ?,
                status = 'pending'
            WHERE id = ?
        ");
        $stmt->execute([
            $submission_text,
            $file_paths_str,
            $current_time,
            $existing['id']
        ]);
    } else {
        // Create new submission
        $stmt = $pdo->prepare("
            INSERT INTO submissions (
                task_id, 
                student_id, 
                submission_text, 
                file_path, 
                submitted_at, 
                status
            ) VALUES (?, ?, ?, ?, ?, 'pending')
        ");
        $stmt->execute([
            $task_id,
            $student_id,
            $submission_text,
            $file_paths_str,
            $current_time
        ]);
    }
    
    echo json_encode([
        'success' => true, 
        'message' => 'Task submitted successfully'
    ]);
    
} catch(PDOException $e) {
    echo json_encode([
        'success' => false, 
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
?>