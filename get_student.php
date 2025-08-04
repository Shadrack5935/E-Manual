<?php
session_start();
require_once 'connection.php';

header('Content-Type: application/json');

try {
    $course_code = $_GET['course_code'] ?? '';
    
    if (!$course_code) {
        throw new Exception('Course code is required');
    }

    // Get enrolled students for the course
    $stmt = $pdo->prepare("
        SELECT a.student_id, a.first_name, a.last_name, a.email, a.program
        FROM accounts a
        INNER JOIN course_enrollments ce ON a.student_id = ce.student_id
        WHERE ce.course_code = ? AND a.role = 'student'
        ORDER BY a.last_name, a.first_name
    ");
    $stmt->execute([$course_code]);
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'students' => $students
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>