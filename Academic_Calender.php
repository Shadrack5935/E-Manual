<?php
session_start();
require_once 'connection.php';

// Check if user is admin
// if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
//     header("Location: account.php");
//     exit();
// }

$message = '';
$messageType = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $pdo->beginTransaction();

       if (isset($_POST['update_academic_year'])) {
    $newYear = $_POST['new_academic_year'];
    $oldYear = $_POST['old_academic_year'];
    
    $stmt = $pdo->prepare("UPDATE academic_calendar SET academic_year = ? WHERE academic_year = ?");
    $stmt->execute([$newYear, $oldYear]);
    
    // Update ALL students' academic year, not just those matching old year
    $stmt = $pdo->prepare("UPDATE students SET academic_year = ?");
    $stmt->execute([$newYear]);
    
    $message = "Academic year updated successfully to $newYear";
    $messageType = "success";
} elseif (isset($_POST['add_semester'])) {
    // Add new semester
    $academicYear = $_POST['academic_year'];
    $semester = $_POST['semester'];
    $startDate = $_POST['start_date'];
            $endDate = $_POST['end_date'];
            
            // Validate dates
            if ($startDate >= $endDate) {
                throw new Exception("End date must be after start date");
            }
            
            // Check for overlapping semesters
            $overlapCheck = $pdo->prepare("
                SELECT COUNT(*) FROM academic_calendar 
                WHERE academic_year = ? 
                AND ((start_date BETWEEN ? AND ?) OR (end_date BETWEEN ? AND ?))
            ");
            $overlapCheck->execute([$academicYear, $startDate, $endDate, $startDate, $endDate]);
            if ($overlapCheck->fetchColumn() > 0) {
                throw new Exception("This semester overlaps with an existing semester");
            }
            
            $stmt = $pdo->prepare("INSERT INTO academic_calendar (academic_year, semester, start_date, end_date) VALUES (?, ?, ?, ?)");
            $stmt->execute([$academicYear, $semester, $startDate, $endDate]);
            
            // Update all students' semester and handle progression
            handleSemesterTransition($semester, $academicYear);
            
            $message = "New semester added successfully. All students updated to $semester" . 
                      ($semester == 'Second Semester' ? " and processed for level advancement/graduation" : "");
            $messageType = "success";
        }

        $pdo->commit();
    } catch (Exception $e) {
        $pdo->rollBack();
        $message = "Error: " . $e->getMessage();
        $messageType = "error";
    }
}

// Function to handle semester transitions, progression and graduation
function handleSemesterTransition($semester, $academicYear) {
    global $pdo;
    
    try {
        // 1. Update all students' current semester
        $stmt = $pdo->prepare("UPDATE students SET semester = ?");
        $stmt->execute([$semester]);
        
        // 2. If this is Second Semester, handle level advancement and graduation
        if ($semester == 'Second Semester') {
            // Calculate next academic year
            $years = explode('-', $academicYear);
            $nextAcademicYear = ($years[1] + 1) . '-' . ($years[1] + 2);
            
            // 3. Handle graduation for 400-level students
            $gradStmt = $pdo->prepare("
                INSERT INTO graduations (student_id, graduation_year, program, level)
                SELECT s.accounts_id, ?, s.program, s.level 
                FROM students s 
                WHERE s.level = '400' AND s.is_graduated = 0
            ");
            $gradStmt->execute([$academicYear]);
            
            // 4. Mark students as graduated and change role to alumni
            $pdo->prepare("
                UPDATE accounts a
                JOIN students s ON a.accounts_id = s.accounts_id
                SET a.role = 'alumni', s.is_graduated = 1, s.graduation_year = ?
                WHERE s.level = '400' AND s.is_graduated = 0
            ")->execute([$academicYear]);
            
            // 5. Promote other students to next level
            $promoteStmt = $pdo->prepare("
                UPDATE students 
                SET level = CASE 
                    WHEN level = '100' THEN '200'
                    WHEN level = '200' THEN '300' 
                    WHEN level = '300' THEN '400'
                    ELSE level
                END,
                academic_year = ?
                WHERE level != '400' OR is_graduated = 0
            ");
            $promoteStmt->execute([$nextAcademicYear]);
        }
        
        return true;
    } catch (PDOException $e) {
        error_log("Error handling semester transition: " . $e->getMessage());
        throw $e;
    }
}

// Get current academic calendar data
try {
    $currentYear = $pdo->query("SELECT DISTINCT academic_year FROM academic_calendar ORDER BY academic_year DESC LIMIT 1")->fetchColumn();
    $semesters = $pdo->query("
        SELECT *, 
            CASE 
                WHEN CURDATE() < start_date THEN 'upcoming'
                WHEN CURDATE() > end_date THEN 'completed'
                ELSE 'current'
            END AS status
        FROM academic_calendar 
        ORDER BY academic_year DESC, start_date DESC
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    // Get current semester
    $currentSemester = $pdo->query("
        SELECT semester FROM academic_calendar 
        WHERE CURDATE() BETWEEN start_date AND end_date 
        LIMIT 1
    ")->fetchColumn();
    
    // Get graduation stats
    $graduationStats = $pdo->query("
        SELECT 
            COUNT(CASE WHEN level = '400' AND is_graduated = 0 THEN 1 END) AS pending_graduates,
            COUNT(CASE WHEN is_graduated = 1 THEN 1 END) AS total_graduates
        FROM students
    ")->fetch(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $message = "Error fetching academic calendar: " . $e->getMessage();
    $messageType = "error";
    $semesters = [];
    $currentYear = date('Y') . '-' . (date('Y') + 1);
    $currentSemester = '';
    $graduationStats = ['pending_graduates' => 0, 'total_graduates' => 0];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Academic Calendar Management</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
            line-height: 1.6;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        h1, h2, h3 {
            color: #2c3e50;
            margin-top: 0;
        }
        .message {
            padding: 10px 15px;
            margin-bottom: 20px;
            border-radius: 4px;
            border-left: 4px solid;
        }
        .message.success {
            background-color: #dff0d8;
            color: #3c763d;
            border-color: #d6e9c6;
        }
        .message.error {
            background-color: #f2dede;
            color: #a94442;
            border-color: #ebccd1;
        }
        .message.info {
            background-color: #d9edf7;
            color: #31708f;
            border-color: #bce8f1;
        }
        form {
            margin-bottom: 30px;
            padding: 20px;
            background: #f9f9f9;
            border-radius: 5px;
            border: 1px solid #eee;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #34495e;
        }
        input, select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
            font-size: 16px;
        }
        button {
            background-color: #3498db;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin-right: 10px;
            font-size: 16px;
            transition: background-color 0.3s;
        }
        button:hover {
            background-color: #2980b9;
        }
        button.danger {
            background-color: #e74c3c;
        }
        button.danger:hover {
            background-color: #c0392b;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            font-size: 15px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 12px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
            font-weight: bold;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .current-info {
            font-weight: bold;
            margin-bottom: 20px;
            padding: 15px;
            background-color: #e7f3fe;
            border-left: 5px solid #3498db;
            border-radius: 4px;
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
        }
        .info-item {
            margin-bottom: 5px;
            flex: 1;
            min-width: 200px;
        }
        .info-item strong {
            display: inline-block;
            width: 150px;
            color: #2c3e50;
        }
        .status-upcoming {
            color: #f39c12;
            font-weight: bold;
        }
        .status-current {
            color: #27ae60;
            font-weight: bold;
        }
        .status-completed {
            color: #7f8c8d;
            font-weight: bold;
        }
        .stats-container {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        .stat-card {
            flex: 1;
            min-width: 200px;
            background: white;
            padding: 15px;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            border-top: 4px solid;
        }
        .stat-card h3 {
            margin-top: 0;
            font-size: 16px;
            color: #7f8c8d;
        }
        .stat-card .value {
            font-size: 24px;
            font-weight: bold;
            margin: 10px 0;
        }
        .stat-card.graduation {
            border-color: #27ae60;
        }
        .stat-card.students {
            border-color: #3498db;
        }
        .stat-card.semesters {
            border-color: #9b59b6;
        }
        @media (max-width: 768px) {
            .current-info, .stats-container {
                flex-direction: column;
            }
            .info-item, .stat-card {
                min-width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Academic Calendar Management</h1>
        
        <?php if ($message): ?>
            <div class="message <?= $messageType ?>"><?= $message ?></div>
        <?php endif; ?>
        
        <div class="current-info">
            <div class="info-item">
                <strong>Current Academic Year:</strong> <?= htmlspecialchars($currentYear) ?>
            </div>
            <div class="info-item">
                <strong>Current Semester:</strong> 
                <?= $currentSemester ? htmlspecialchars($currentSemester) : 'Not in active semester' ?>
            </div>
            <div class="info-item">
                <strong>Current Date:</strong> <?= date('F j, Y') ?>
            </div>
        </div>
        
        <div class="stats-container">
            <div class="stat-card graduation">
                <h3>Pending Graduations</h3>
                <div class="value"><?= $graduationStats['pending_graduates'] ?></div>
                <div>400-level students ready to graduate</div>
            </div>
            <div class="stat-card students">
                <h3>Total Graduates</h3>
                <div class="value"><?= $graduationStats['total_graduates'] ?></div>
                <div>Students who have graduated</div>
            </div>
            <div class="stat-card semesters">
                <h3>Active Semesters</h3>
                <div class="value"><?= count($semesters) ?></div>
                <div>Semesters in system</div>
            </div>
        </div>
        
        <h2>Update Academic Year</h2>
        <form method="POST">
            <input type="hidden" name="old_academic_year" value="<?= htmlspecialchars($currentYear) ?>">
            
            <div class="form-group">
                <label for="new_academic_year">New Academic Year:</label>
                <input type="text" id="new_academic_year" name="new_academic_year" 
                       placeholder="e.g., 2025-2026" required
                       pattern="\d{4}-\d{4}" title="Format should be YYYY-YYYY"
                       value="<?= htmlspecialchars($currentYear) ?>">
            </div>
            
            <button type="submit" name="update_academic_year">Update Academic Year</button>
            <p><small>This will update the academic year for all records in the system.</small></p>
        </form>
        
        <h2>Add New Semester</h2>
        <form method="POST">
            <div class="form-group">
                <label for="academic_year">Academic Year:</label>
                <input type="text" id="academic_year" name="academic_year" 
                       value="<?= htmlspecialchars($currentYear) ?>" required
                       pattern="\d{4}-\d{4}" title="Format should be YYYY-YYYY">
            </div>
            
            <div class="form-group">
                <label for="semester">Semester:</label>
                <select id="semester" name="semester" required>
                    <option value="">Select Semester</option>
                    <option value="First Semester">First Semester</option>
                    <option value="Second Semester">Second Semester</option>
                    <option value="Summer Semester">Summer Semester</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="start_date">Start Date:</label>
                <input type="date" id="start_date" name="start_date" required
                       min="<?= date('Y-m-d') ?>">
            </div>
            
            <div class="form-group">
                <label for="end_date">End Date:</label>
                <input type="date" id="end_date" name="end_date" required
                       min="<?= date('Y-m-d', strtotime('+1 day')) ?>">
            </div>
            
            <button type="submit" name="add_semester">Add Semester</button>
            <button type="reset" class="danger">Reset Form</button>
            <p><em>Note: Adding a Second Semester will automatically:</em></p>
            <ul>
                <li>Update all students' current semester</li>
                <li>Promote students to the next level (100→200→300→400)</li>
                <li>Graduate 400-level students and mark them as alumni</li>
                <li>Update academic year for continuing students</li>
            </ul>
        </form>
        
        <h2>Academic Calendar</h2>
        <?php if (empty($semesters)): ?>
            <div class="message info">No semesters found in the system. Please add your first semester.</div>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Academic Year</th>
                        <th>Semester</th>
                        <th>Start Date</th>
                        <th>End Date</th>
                        <th>Duration (Days)</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($semesters as $semester): 
                        $start = new DateTime($semester['start_date']);
                        $end = new DateTime($semester['end_date']);
                        $duration = $start->diff($end)->days;
                    ?>
                        <tr>
                            <td><?= htmlspecialchars($semester['academic_year']) ?></td>
                            <td><?= htmlspecialchars($semester['semester']) ?></td>
                            <td><?= $start->format('M j, Y') ?></td>
                            <td><?= $end->format('M j, Y') ?></td>
                            <td><?= $duration ?></td>
                            <td class="status-<?= $semester['status'] ?>">
                                <?= ucfirst($semester['status']) ?>
                                <?php if ($semester['status'] == 'completed' && $semester['semester'] == 'Second Semester'): ?>
                                    <br><small>(Students processed)</small>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</body>
</html>