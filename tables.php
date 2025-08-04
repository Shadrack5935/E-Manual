<?php
require_once 'connection.php';

try {
    // Create accounts table
    $sql_accounts = "CREATE TABLE IF NOT EXISTS accounts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        student_id VARCHAR(32) DEFAULT NULL,
        staff_id VARCHAR(32) DEFAULT NULL,
        first_name VARCHAR(64) NOT NULL,
        last_name VARCHAR(64) NOT NULL,
        fullname VARCHAR(128) GENERATED ALWAYS AS (CONCAT(first_name, ' ', last_name)) STORED,
        email VARCHAR(128) NOT NULL UNIQUE,
        phone VARCHAR(32),
        pass VARCHAR(255) NOT NULL,
        program VARCHAR(64),
        academic_year VARCHAR(9) DEFAULT '2024-2025',
        role ENUM('student','instructor','admin') DEFAULT 'student',
        is_active TINYINT(1) DEFAULT 1,
        is_verified TINYINT(1) DEFAULT 0,
        verification_token VARCHAR(64),
        reset_token VARCHAR(64),
        reset_token_expiry DATETIME,
        last_login DATETIME,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_staff_id (staff_id),
        UNIQUE KEY unique_student_id (student_id)
    ) ENGINE=InnoDB";
    
    $pdo->exec($sql_accounts);
    echo "✓ Accounts table created successfully\n";

    // Create courses table
    $sql_courses = "CREATE TABLE IF NOT EXISTS courses (
        id INT AUTO_INCREMENT PRIMARY KEY,
        course_code VARCHAR(32) NOT NULL UNIQUE,
        course_name VARCHAR(128) NOT NULL,
        credits INT NOT NULL,
        program VARCHAR(64) NOT NULL,
        semester VARCHAR(32) NOT NULL,
        instructor_id VARCHAR(32),
        description TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (instructor_id) REFERENCES accounts(staff_id) ON DELETE SET NULL
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
        program VARCHAR(64) NOT NULL,
        instructor_id VARCHAR(32),
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (course_code) REFERENCES courses(course_code) ON DELETE CASCADE,
        FOREIGN KEY (instructor_id) REFERENCES accounts(staff_id) ON DELETE SET NULL
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
        instructor_id VARCHAR(32),
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (course_code) REFERENCES courses(course_code) ON DELETE CASCADE,
        FOREIGN KEY (topic_code) REFERENCES topics(topic_code) ON DELETE CASCADE,
        FOREIGN KEY (instructor_id) REFERENCES accounts(staff_id) ON DELETE SET NULL
    ) ENGINE=InnoDB";
    
    $pdo->exec($sql_tasks);
    echo "✓ Tasks table created successfully\n";

    // Create task_assignments table
    $sql_task_assignments = "CREATE TABLE IF NOT EXISTS task_assignments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        task_id INT NOT NULL,
        student_id VARCHAR(32) NOT NULL,
        assigned_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE,
        FOREIGN KEY (student_id) REFERENCES accounts(student_id) ON DELETE CASCADE,
        UNIQUE KEY unique_task_student (task_id, student_id)
    ) ENGINE=InnoDB";
    
    $pdo->exec($sql_task_assignments);
    echo "✓ Task assignments table created successfully\n";

    // Create submissions table
    $sql_submissions = "CREATE TABLE IF NOT EXISTS submissions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        task_id INT NOT NULL,
        student_id VARCHAR(32) NOT NULL,
        submission_text TEXT,
        file_path VARCHAR(255),
        submitted_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        status ENUM('pending','graded','late') DEFAULT 'pending',
        grade INT DEFAULT NULL,
        letter_grade VARCHAR(2) DEFAULT NULL,
        feedback TEXT,
        graded_at DATETIME DEFAULT NULL,
        graded_by VARCHAR(32) DEFAULT NULL,
        FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE,
        FOREIGN KEY (student_id) REFERENCES accounts(student_id) ON DELETE CASCADE,
        FOREIGN KEY (graded_by) REFERENCES accounts(staff_id) ON DELETE SET NULL,
        UNIQUE KEY unique_task_student_submission (task_id, student_id)
    ) ENGINE=InnoDB";
    
    $pdo->exec($sql_submissions);
    echo "✓ Submissions table created successfully\n";

    // Create course_enrollments table
    $sql_enrollments = "CREATE TABLE IF NOT EXISTS course_enrollments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        course_code VARCHAR(32) NOT NULL,
        student_id VARCHAR(32) NOT NULL,
        enrolled_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (course_code) REFERENCES courses(course_code) ON DELETE CASCADE,
        FOREIGN KEY (student_id) REFERENCES accounts(student_id) ON DELETE CASCADE,
        UNIQUE KEY unique_course_student (course_code, student_id)
    ) ENGINE=InnoDB";
    
    $pdo->exec($sql_enrollments);
    echo "✓ Course enrollments table created successfully\n";

    echo "\n✅ Database setup completed successfully!\n";

} catch (PDOException $e) {
    die("Error setting up database: " . $e->getMessage());
}

?>