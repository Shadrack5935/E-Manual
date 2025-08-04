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
    // Get student's program to filter relevant courses
    $stmt = $pdo->prepare("SELECT program FROM accounts WHERE id = ?");
    $stmt->execute([$student_id]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);
    $student_program = $student['program'] ?? '';
    
    // Get all available courses with enrollment info
    $stmt = $pdo->prepare("
        SELECT 
            c.id,
            c.course_code as code,
            c.course_name as name,
            c.credits,
            c.semester,
            c.program,
            c.description,
            a.fullname as instructor,
            COUNT(ce.student_id) as enrolled,
            50 as capacity,
            CASE WHEN ce_student.student_id IS NOT NULL THEN 1 ELSE 0 END as is_enrolled,
            'Mon/Wed 10:00-11:30' as schedule,
            'None' as prerequisites
        FROM courses c
        LEFT JOIN accounts a ON c.instructor_id = a.staff_id
        LEFT JOIN course_enrollments ce ON c.id = ce.course_id
        LEFT JOIN course_enrollments ce_student ON c.id = ce_student.course_id AND ce_student.student_id = ?
        WHERE c.program = ? OR c.program = 'General'
        GROUP BY c.id, c.course_code, c.course_name, c.credits, c.semester, c.program, c.description, a.fullname, ce_student.student_id
        ORDER BY c.course_code
    ");
    
    $stmt->execute([$student_id, $student_program]);
    $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format the data
    $formatted_courses = array_map(function($course) {
        return [
            'id' => $course['id'],
            'code' => $course['code'],
            'name' => $course['name'],
            'credits' => $course['credits'],
            'semester' => $course['semester'],
            'program' => $course['program'],
            'description' => $course['description'] ?? 'No description available',
            'instructor' => $course['instructor'] ?? 'TBA',
            'enrolled' => $course['enrolled'],
            'capacity' => $course['capacity'],
            'schedule' => $course['schedule'],
            'prerequisites' => $course['prerequisites'],
            'is_enrolled' => $course['is_enrolled'] == 1
        ];
    }, $courses);
    
    echo json_encode(['success' => true, 'courses' => $formatted_courses]);
    
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
?>