<?php
require_once 'connection.php';

try {
    // Create accounts table (common information for all users)
    $sql_accounts = "CREATE TABLE IF NOT EXISTS accounts (
        accounts_id VARCHAR(64) PRIMARY KEY, 
        email VARCHAR(128) NOT NULL UNIQUE,
        phone VARCHAR(32),
        pass VARCHAR(255) NOT NULL,
        sur_name VARCHAR(64) NOT NULL,
        other_name VARCHAR(64) NOT NULL,
        fullname VARCHAR(128) GENERATED ALWAYS AS (CONCAT(sur_name, ' ', other_name)) STORED,
        role ENUM('student','instructor','admin') NOT NULL,
        is_active TINYINT(1) DEFAULT 1,
        is_verified TINYINT(1) DEFAULT 0,
        verification_token VARCHAR(64),
        reset_token VARCHAR(64),
        reset_token_expiry DATETIME,
        last_login DATETIME,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB";
    
    $pdo->exec($sql_accounts);
    echo "✓ Accounts table created successfully\n";

    // Create students table (student-specific information)
    $sql_students = "CREATE TABLE IF NOT EXISTS students (
        accounts_id VARCHAR(64) PRIMARY KEY,
        program VARCHAR(64) NOT NULL,
        level ENUM('100','200','300','400') NOT NULL,
        academic_year VARCHAR(9) DEFAULT '2025-2026',
        semester VARCHAR(64) DEFAULT 'Semester 1',
        FOREIGN KEY (accounts_id) REFERENCES accounts(accounts_id) ON DELETE CASCADE
    ) ENGINE=InnoDB";
    
    $pdo->exec($sql_students);
    echo "✓ Students table created successfully\n";

    // Create instructors table (instructor-specific information)
    $sql_programs = "CREATE TABLE IF NOT EXISTS programs (
        program_id VARCHAR(64) PRIMARY KEY,
        program_name VARCHAR(64),
        program_type VARCHAR(64),
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB";

    $pdo->exec($sql_programs);
    echo "✓ Programs table created successfully\n";

    $sql_academic = "CREATE TABLE IF NOT EXISTS academic_calendar (
        academic_id INT AUTO_INCREMENT PRIMARY KEY,
        academic_year VARCHAR(9) NOT NULL DEFAULT '2025-2026',
        semester VARCHAR(32) NOT NULL,
        start_date DATE NOT NULL,
        end_date DATE NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_academic_year (academic_year),
        INDEX idx_semester (semester),
        INDEX idx_dates (start_date, end_date)
    ) ENGINE=InnoDB";

    $pdo->exec($sql_academic);
    echo "✓ Academic calendar table created successfully\n";

    // Create courses table
    $sql_courses = "CREATE TABLE IF NOT EXISTS courses (
        id INT AUTO_INCREMENT PRIMARY KEY,
        course_code VARCHAR(32) NOT NULL UNIQUE,
        course_name VARCHAR(128) NOT NULL,
        credits INT NOT NULL,
        program VARCHAR(64) NOT NULL,
        semester VARCHAR(32) NOT NULL,
        instructor_id VARCHAR(64),  
        description TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (instructor_id) REFERENCES accounts(accounts_id) ON DELETE SET NULL
    ) ENGINE=InnoDB";
    
    $pdo->exec($sql_courses);
    echo "✓ Courses table created successfully\n";

    // Create topics table
    $sql_topics = "CREATE TABLE IF NOT EXISTS topics (
        id INT AUTO_INCREMENT PRIMARY KEY,
        topic_code VARCHAR(32) NOT NULL UNIQUE,
        topic_title VARCHAR(128) NOT NULL,
        course_code VARCHAR(32) NOT NULL,
        description TEXT,
        instructor_id VARCHAR(64), 
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (course_code) REFERENCES courses(course_code) ON DELETE CASCADE,
        FOREIGN KEY (instructor_id) REFERENCES accounts(accounts_id) ON DELETE SET NULL
    ) ENGINE=InnoDB";
    
    $pdo->exec($sql_topics);
    echo "✓ Topics table created successfully\n";

    // Create tasks table
    $sql_tasks = "CREATE TABLE IF NOT EXISTS tasks (
        id INT AUTO_INCREMENT PRIMARY KEY,
        task_title VARCHAR(128) NOT NULL,
        course_code VARCHAR(32) NOT NULL,
        topic_code VARCHAR(32) NOT NULL,
        task_type ENUM('assignment','project','quiz','practical','research') NOT NULL,
        description TEXT NOT NULL,
        due_date DATETIME NOT NULL,
        max_marks INT NOT NULL,
        allow_late_submission ENUM('yes','no') DEFAULT 'no',
        instructor_id VARCHAR(64),
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (course_code) REFERENCES courses(course_code) ON DELETE CASCADE,
        FOREIGN KEY (topic_code) REFERENCES topics(topic_code) ON DELETE CASCADE,
        FOREIGN KEY (instructor_id) REFERENCES accounts(accounts_id) ON DELETE SET NULL
    ) ENGINE=InnoDB";
    
    $pdo->exec($sql_tasks);
    echo "✓ Tasks table created successfully\n";

    // Create task_assignments table
    $sql_task_assignments = "CREATE TABLE IF NOT EXISTS task_assignments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        task_id INT NOT NULL,
        student_id VARCHAR(64) NOT NULL,  
        assigned_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE,
        FOREIGN KEY (student_id) REFERENCES accounts(accounts_id) ON DELETE CASCADE,
        UNIQUE KEY unique_task_student (task_id, student_id)
    ) ENGINE=InnoDB";
    
    $pdo->exec($sql_task_assignments);
    echo "✓ Task assignments table created successfully\n";

    // Create submissions table
    $sql_submissions = "CREATE TABLE IF NOT EXISTS submissions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        task_id INT NOT NULL,
        student_id VARCHAR(64) NOT NULL,
        submission_text TEXT,
        file_path VARCHAR(255),
        submitted_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        status ENUM('pending','graded','late') DEFAULT 'pending',
        grade INT DEFAULT NULL,
        letter_grade VARCHAR(2) DEFAULT NULL,
        feedback TEXT,
        graded_at DATETIME DEFAULT NULL,
        graded_by VARCHAR(64) DEFAULT NULL,
        FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE,
        FOREIGN KEY (student_id) REFERENCES accounts(accounts_id) ON DELETE CASCADE,
        FOREIGN KEY (graded_by) REFERENCES accounts(accounts_id) ON DELETE SET NULL,
        UNIQUE KEY unique_task_student_submission (task_id, student_id)
    ) ENGINE=InnoDB";
    
    $pdo->exec($sql_submissions);
    echo "✓ Submissions table created successfully\n";

    // Create course_enrollments table
    $sql_enrollments = "CREATE TABLE IF NOT EXISTS course_enrollments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        course_code VARCHAR(32) NOT NULL,
        accounts_id VARCHAR(64) NOT NULL, 
        enrolled_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (course_code) REFERENCES courses(course_code) ON DELETE CASCADE,
        FOREIGN KEY (accounts_id) REFERENCES accounts(accounts_id) ON DELETE CASCADE,
        UNIQUE KEY unique_course_student (course_code, accounts_id)
    ) ENGINE=InnoDB";
    
    $pdo->exec($sql_enrollments);
    echo "✓ Course enrollments table created successfully\n";

    echo "\n✅ Database setup completed successfully!\n";
    // Create graduations table
    $sql_graduation = "CREATE TABLE IF NOT EXISTS graduations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id VARCHAR(64) NOT NULL,
    graduation_year VARCHAR(9) NOT NULL,
    program VARCHAR(64) NOT NULL,
    level VARCHAR(32) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES accounts(accounts_id)
)";
$pdo->exec($sql_graduation);

} catch (PDOException $e) {
    die("Error setting up database: " . $e->getMessage());
}
?>