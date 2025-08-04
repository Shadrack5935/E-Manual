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
    // Get student's enrolled courses with progress and grades
    $stmt = $pdo->prepare("
        SELECT 
            c.id,
            c.course_code as code,
            c.course_name as name,
            c.credits,
            c.semester,
            c.program,
            a.fullname as instructor,
            COALESCE(AVG(CASE WHEN s.grade IS NOT NULL THEN (s.grade / t.max_marks) * 100 END), 0) as progress,
            COALESCE(AVG(CASE WHEN s.grade IS NOT NULL THEN s.grade END), 0) as avg_grade,
            COUNT(t.id) as total_tasks,
            COUNT(CASE WHEN s.submission_date IS NOT NULL THEN 1 END) as tasks_completed,
            MIN(CASE WHEN s.submission_date IS NULL AND t.due_date > NOW() THEN t.due_date END) as next_deadline
        FROM courses c
        LEFT JOIN accounts a ON c.instructor_id = a.staff_id
        LEFT JOIN course_enrollments ce ON c.id = ce.course_id
        LEFT JOIN tasks t ON c.course_code = t.course_code
        LEFT JOIN task_submissions s ON t.id = s.task_id AND s.student_id = ?
        WHERE ce.student_id = ?
        GROUP BY c.id, c.course_code, c.course_name, c.credits, c.semester, c.program, a.fullname
    ");
    
    $stmt->execute([$student_id, $student_id]);
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
            'instructor' => $course['instructor'] ?? 'TBA',
            'progress' => round($course['progress'], 1),
            'grade' => $course['avg_grade'] > 0 ? getLetterGrade($course['avg_grade']) : 'N/A',
            'totalTasks' => $course['total_tasks'],
            'tasksCompleted' => $course['tasks_completed'],
            'nextDeadline' => $course['next_deadline'] ? date('M j, Y', strtotime($course['next_deadline'])) : 'No upcoming deadlines'
        ];
    }, $courses);
    
    echo json_encode($formatted_courses);
    
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}

function getLetterGrade($score) {
    if ($score >= 90) return 'A+';
    if ($score >= 85) return 'A';
    if ($score >= 80) return 'A-';
    if ($score >= 75) return 'B+';
    if ($score >= 70) return 'B';
    if ($score >= 65) return 'B-';
    if ($score >= 60) return 'C+';
    if ($score >= 55) return 'C';
    if ($score >= 50) return 'C-';
    return 'F';
}
?>