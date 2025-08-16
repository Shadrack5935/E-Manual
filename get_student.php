<?php
session_start();
require_once 'connection.php';

header('Content-Type: application/json');

try {
    // Verify user is logged in
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('Authentication required');
    }

    $course_code = $_GET['course_code'] ?? '';
    
    if (empty($course_code)) {
        throw new Exception('Course code is required');
    }

    // Get enrolled students for the course with their program information
    $stmt = $pdo->prepare("
        SELECT 
            a.accounts_id AS student_id,
            a.sur_name AS last_name,
            a.other_name AS first_name,
            a.email,
            s.program,
            s.level
        FROM accounts a
        JOIN students s ON a.accounts_id = s.accounts_id
        JOIN course_enrollments ce ON a.accounts_id = ce.accounts_id
        WHERE ce.course_code = ? 
        AND a.role = 'student'
        ORDER BY a.sur_name, a.other_name
    ");
    $stmt->execute([$course_code]);
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Format the response data
    $response = [
        'success' => true,
        'students' => array_map(function($student) {
            return [
                'student_id' => $student['student_id'],
                'first_name' => $student['first_name'],
                'last_name' => $student['last_name'],
                'full_name' => $student['last_name'] . ' ' . $student['first_name'],
                'email' => $student['email'],
                'program' => $student['program'],
                'level' => $student['level']
            ];
        }, $students)
    ];

    echo json_encode($response);

} catch (PDOException $e) {
    error_log("Database error in get_student: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Database error occurred'
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>