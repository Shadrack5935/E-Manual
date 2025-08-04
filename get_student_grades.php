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
    // Get grades for all courses
    $stmt = $pdo->prepare("
        SELECT 
            c.course_code,
            c.course_name,
            t.task_title,
            t.max_marks,
            s.grade,
            s.feedback,
            s.submission_date
        FROM courses c
        JOIN course_enrollments ce ON c.id = ce.course_id
        JOIN tasks t ON c.course_code = t.course_code
        LEFT JOIN task_submissions s ON t.id = s.task_id AND s.student_id = ?
        WHERE ce.student_id = ? AND s.grade IS NOT NULL
        ORDER BY c.course_code, t.task_title
    ");
    
    $stmt->execute([$student_id, $student_id]);
    $grades = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Group by course
    $grouped_grades = [];
    foreach ($grades as $grade) {
        $course_code = $grade['course_code'];
        if (!isset($grouped_grades[$course_code])) {
            $grouped_grades[$course_code] = [
                'courseCode' => $course_code,
                'courseName' => $grade['course_name'],
                'tasks' => [],
                'totalPoints' => 0,
                'maxPoints' => 0
            ];
        }
        
        $task_grade = [
            'name' => $grade['task_title'],
            'grade' => $grade['grade'],
            'maxGrade' => $grade['max_marks'],
            'percentage' => round(($grade['grade'] / $grade['max_marks']) * 100, 1),
            'feedback' => $grade['feedback'],
            'submissionDate' => $grade['submission_date']
        ];
        
        $grouped_grades[$course_code]['tasks'][] = $task_grade;
        $grouped_grades[$course_code]['totalPoints'] += $grade['grade'];
        $grouped_grades[$course_code]['maxPoints'] += $grade['max_marks'];
    }
    
    // Calculate overall grades
    $formatted_grades = array_map(function($course) {
        $overall_percentage = $course['maxPoints'] > 0 ? round(($course['totalPoints'] / $course['maxPoints']) * 100, 1) : 0;
        $course['overall'] = [
            'grade' => getLetterGrade($overall_percentage),
            'percentage' => $overall_percentage,
            'points' => $course['totalPoints'] . '/' . $course['maxPoints']
        ];
        return $course;
    }, array_values($grouped_grades));
    
    echo json_encode($formatted_grades);
    
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