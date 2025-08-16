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
    // Get student's program name and semester
    $stmt = $pdo->prepare("
        SELECT s.program, s.semester 
        FROM students s 
        WHERE s.accounts_id = ?
    ");
    $stmt->execute([$student_id]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$student) {
        echo json_encode(['success' => false, 'error' => 'Student not found']);
        exit;
    }

    // Get all available courses for student's program/semester
    $stmt = $pdo->prepare("
        SELECT 
            c.id,
            c.course_code,
            c.course_name,
            c.credits,
            c.description,
            p.program_name as program, 
            c.semester,
            a.fullname as instructor,
            (SELECT COUNT(*) FROM course_enrollments WHERE course_code = c.course_code) as enrolled_count,
            EXISTS (
                SELECT 1 FROM course_enrollments 
                WHERE course_code = c.course_code AND accounts_id = ?
            ) as is_enrolled
        FROM courses c
        JOIN programs p ON c.program = p.program_id 
        LEFT JOIN accounts a ON c.instructor_id = a.accounts_id
        WHERE p.program_name = ?  
        AND c.semester = ?
        AND c.course_code NOT IN (
            SELECT course_code FROM course_enrollments 
            WHERE accounts_id = ?
        )
        ORDER BY c.course_code
    ");
    
    $stmt->execute([
        $student_id,
        $student['program'],  // This is the program NAME from students table
        $student['semester'],
        $student_id
    ]);
    
    $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true, 
        'courses' => $courses,
        'filters' => [
            'program' => $student['program'],
            'semester' => $student['semester']
        ]
    ]);
    
} catch(PDOException $e) {
    echo json_encode([
        'success' => false, 
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
?>