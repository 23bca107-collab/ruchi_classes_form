<?php
session_start();
require '../db.php';

// Redirect if admin not logged in
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: admin_login.php");
    exit();
}

// Create teacher_classes table for class assignments
$create_teacher_classes_table = "
CREATE TABLE IF NOT EXISTS teacher_classes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    teacher_id INT NOT NULL,
    class VARCHAR(5) NOT NULL,
    medium ENUM('English', 'Hindi') NOT NULL,
    assigned_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_teacher_class (teacher_id, class, medium),
    FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE CASCADE
)";

if ($conn->query($create_teacher_classes_table) === FALSE) {
    error_log("Error creating teacher_classes table: " . $conn->error);
}

// Create teacher_students table if it doesn't exist
$create_table_sql = "
CREATE TABLE IF NOT EXISTS teacher_students (
    id INT PRIMARY KEY AUTO_INCREMENT,
    teacher_id INT NOT NULL,
    student_id INT NOT NULL,
    medium ENUM('English', 'Hindi') NOT NULL,
    assigned_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_assignment (teacher_id, student_id, medium),
    FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE CASCADE
)";

if ($conn->query($create_table_sql) === FALSE) {
    error_log("Error creating teacher_students table: " . $conn->error);
}

// Get all teachers
$teachers = [];
$stmt = $conn->prepare("SELECT id, first_name, last_name, subject, photo FROM teachers ORDER BY first_name ASC");
if ($stmt) {
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $teachers[] = $row;
    }
    $stmt->close();
}

// Get teacher class assignments
$teacher_classes = [];
$stmt = $conn->prepare("SELECT teacher_id, class, medium FROM teacher_classes ORDER BY teacher_id, class");
if ($stmt) {
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        if (!isset($teacher_classes[$row['teacher_id']])) {
            $teacher_classes[$row['teacher_id']] = [];
        }
        $teacher_classes[$row['teacher_id']][] = $row;
    }
    $stmt->close();
}

// Get all students from both tables
$all_students = [];

// Get English medium students
$stmt = $conn->prepare("SELECT id, first_name, last_name, photo, class, 'English' as medium FROM student_english ORDER BY class ASC, first_name ASC");
if ($stmt) {
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $all_students[] = $row;
    }
    $stmt->close();
}

// Get Hindi medium students
$stmt = $conn->prepare("SELECT id, first_name, last_name, photo, class, 'Hindi' as medium FROM student_hindi ORDER BY class ASC, first_name ASC");
if ($stmt) {
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $all_students[] = $row;
    }
    $stmt->close();
}

// Get assigned students
$assigned_students = [];
$stmt = $conn->prepare("
    SELECT ts.*, 
           COALESCE(se.first_name, sh.first_name) as first_name,
           COALESCE(se.last_name, sh.last_name) as last_name,
           COALESCE(se.class, sh.class) as class
    FROM teacher_students ts
    LEFT JOIN student_english se ON ts.student_id = se.id AND ts.medium = 'English'
    LEFT JOIN student_hindi sh ON ts.student_id = sh.id AND ts.medium = 'Hindi'
");
if ($stmt) {
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $assigned_students[$row['student_id'] . '_' . $row['medium']] = $row['teacher_id'];
    }
    $stmt->close();
}

// Handle teacher class assignment
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['assign_classes'])) {
        $teacher_id = intval($_POST['teacher_id']);
        $selected_classes = $_POST['classes'] ?? [];
        
        // Remove existing class assignments for this teacher
        $stmt = $conn->prepare("DELETE FROM teacher_classes WHERE teacher_id = ?");
        if ($stmt) {
            $stmt->bind_param("i", $teacher_id);
            $stmt->execute();
            $stmt->close();
        }
        
        // Insert new class assignments
        $assigned_class_count = 0;
        foreach ($selected_classes as $class_key) {
            list($class, $medium) = explode('_', $class_key);
            
            $stmt = $conn->prepare("INSERT INTO teacher_classes (teacher_id, class, medium) VALUES (?, ?, ?)");
            if ($stmt) {
                $stmt->bind_param("iss", $teacher_id, $class, $medium);
                if ($stmt->execute()) {
                    $assigned_class_count++;
                }
                $stmt->close();
            }
        }
        
        $_SESSION['success_message'] = "Successfully assigned $assigned_class_count classes to the teacher!";
        header("Location: admin_assign_students.php?teacher_id=" . $teacher_id);
        exit();
        
    } elseif (isset($_POST['assign_students'])) {
        $teacher_id = intval($_POST['teacher_id']);
        $selected_students = $_POST['students'] ?? [];
        
        // First, remove all existing student assignments for this teacher
        $stmt = $conn->prepare("DELETE FROM teacher_students WHERE teacher_id = ?");
        if ($stmt) {
            $stmt->bind_param("i", $teacher_id);
            $stmt->execute();
            $stmt->close();
        }
        
        // Then insert new assignments
        $assigned_count = 0;
        foreach ($selected_students as $student_key) {
            list($student_id, $medium) = explode('_', $student_key);
            $student_id = intval($student_id);
            
            $stmt = $conn->prepare("INSERT INTO teacher_students (teacher_id, student_id, medium) VALUES (?, ?, ?)");
            if ($stmt) {
                $stmt->bind_param("iis", $teacher_id, $student_id, $medium);
                if ($stmt->execute()) {
                    $assigned_count++;
                }
                $stmt->close();
            }
        }
        
        $_SESSION['success_message'] = "Successfully assigned $assigned_count students to the teacher!";
        header("Location: admin_assign_students.php?teacher_id=" . $teacher_id);
        exit();
    }
}

// Handle remove assignment
if (isset($_GET['remove_assignment'])) {
    $assignment_id = intval($_GET['remove_assignment']);
    $teacher_id = isset($_GET['teacher_id']) ? intval($_GET['teacher_id']) : 0;
    
    $stmt = $conn->prepare("DELETE FROM teacher_students WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $assignment_id);
        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Assignment removed successfully!";
        }
        $stmt->close();
    }
    header("Location: admin_assign_students.php?teacher_id=" . $teacher_id);
    exit();
}

// Handle remove class assignment
if (isset($_GET['remove_class'])) {
    $class_id = intval($_GET['remove_class']);
    $teacher_id = isset($_GET['teacher_id']) ? intval($_GET['teacher_id']) : 0;
    
    $stmt = $conn->prepare("DELETE FROM teacher_classes WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $class_id);
        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Class assignment removed successfully!";
        }
        $stmt->close();
    }
    header("Location: admin_assign_students.php?teacher_id=" . $teacher_id);
    exit();
}

// Get teacher's current class assignments for display
$teacher_class_assignments = [];
if (isset($_GET['teacher_id']) && !empty($_GET['teacher_id'])) {
    $teacher_id = intval($_GET['teacher_id']);
    
    // Get class assignments
    $stmt = $conn->prepare("SELECT * FROM teacher_classes WHERE teacher_id = ? ORDER BY class, medium");
    if ($stmt) {
        $stmt->bind_param("i", $teacher_id);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $teacher_class_assignments[] = $row;
        }
        $stmt->close();
    }
    
    // Get student assignments
    $stmt = $conn->prepare("
        SELECT ts.*, 
               COALESCE(se.first_name, sh.first_name) as first_name,
               COALESCE(se.last_name, sh.last_name) as last_name,
               COALESCE(se.photo, sh.photo) as photo,
               COALESCE(se.class, sh.class) as class,
               ts.medium
        FROM teacher_students ts
        LEFT JOIN student_english se ON ts.student_id = se.id AND ts.medium = 'English'
        LEFT JOIN student_hindi sh ON ts.student_id = sh.id AND ts.medium = 'Hindi'
        WHERE ts.teacher_id = ?
        ORDER BY class ASC, first_name ASC
    ");
    if ($stmt) {
        $stmt->bind_param("i", $teacher_id);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $teacher_assignments[] = $row;
        }
        $stmt->close();
    }
}

// Get available classes for assignment (8-12)
$available_classes = ['8', '9', '10', '11', '12'];
$available_mediums = ['English', 'Hindi'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assign Teachers & Students | Ruchi Classes</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Roboto:wght@300;400;500&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #4a6bff;
            --secondary-color: #6c757d;
            --success-color: #28a745;
            --danger-color: #dc3545;
            --warning-color: #ffc107;
            --info-color: #17a2b8;
            --light-color: #f8f9fa;
            --dark-color: #343a40;
            --border-color: #dee2e6;
            --shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: var(--shadow);
            overflow: hidden;
        }
        
        .header {
            background: linear-gradient(135deg, var(--primary-color), #667eea);
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .header h1 {
            font-size: 2.5rem;
            margin-bottom: 10px;
            font-weight: 700;
        }
        
        .header p {
            font-size: 1.1rem;
            opacity: 0.9;
        }
        
        .content {
            padding: 30px;
        }
        
        .card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 25px;
            margin-bottom: 25px;
            border: 1px solid var(--border-color);
        }
        
        .card h2, .card h3 {
            color: var(--dark-color);
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid var(--primary-color);
            font-weight: 600;
        }
        
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 500;
        }
        
        .alert-success {
            background: rgba(40, 167, 69, 0.15);
            border: 1px solid rgba(40, 167, 69, 0.3);
            color: var(--success-color);
        }
        
        .alert-danger {
            background: rgba(220, 53, 69, 0.15);
            border: 1px solid rgba(220, 53, 69, 0.3);
            color: var(--danger-color);
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--dark-color);
        }
        
        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid var(--border-color);
            border-radius: 8px;
            font-size: 16px;
            transition: all 0.3s ease;
            font-family: 'Poppins', sans-serif;
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(74, 107, 255, 0.2);
        }
        
        select.form-control {
            cursor: pointer;
            appearance: none;
            background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 15px center;
            background-size: 16px;
            padding-right: 40px;
        }
        
        .btn {
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            font-family: 'Poppins', sans-serif;
        }
        
        .btn-primary {
            background: var(--primary-color);
            color: white;
        }
        
        .btn-primary:hover {
            background: #3a5bff;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(74, 107, 255, 0.3);
        }
        
        .btn-success {
            background: var(--success-color);
            color: white;
        }
        
        .btn-success:hover {
            background: #218838;
            transform: translateY(-2px);
        }
        
        .btn-danger {
            background: var(--danger-color);
            color: white;
        }
        
        .btn-danger:hover {
            background: #c82333;
            transform: translateY(-2px);
        }
        
        .btn-info {
            background: var(--info-color);
            color: white;
        }
        
        .btn-info:hover {
            background: #0da5e9;
            transform: translateY(-2px);
        }
        
        .btn-warning {
            background: var(--warning-color);
            color: black;
        }
        
        .btn-warning:hover {
            background: #e0a800;
            transform: translateY(-2px);
        }
        
        .btn-block {
            display: block;
            width: 100%;
        }
        
        .btn-lg {
            padding: 15px 30px;
            font-size: 18px;
        }
        
        .teacher-profile {
            display: flex;
            align-items: center;
            gap: 20px;
            padding: 20px;
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            border-radius: 10px;
            margin-bottom: 25px;
        }
        
        .teacher-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid white;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        
        .avatar-placeholder {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: var(--primary-color);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            font-weight: bold;
            border: 4px solid white;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        
        .teacher-info h3 {
            margin: 0;
            font-size: 1.5rem;
            color: var(--dark-color);
        }
        
        .teacher-info p {
            color: var(--secondary-color);
            margin: 5px 0;
        }
        
        .filters {
            display: flex;
            gap: 15px;
            margin-bottom: 25px;
            flex-wrap: wrap;
        }
        
        .filter-select {
            padding: 12px 15px;
            border: 2px solid var(--border-color);
            border-radius: 8px;
            min-width: 200px;
            font-size: 16px;
            font-family: 'Poppins', sans-serif;
        }
        
        .class-grid, .student-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }
        
        .class-card {
            background: white;
            border: 2px solid var(--border-color);
            border-radius: 10px;
            padding: 15px;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .class-card:hover {
            border-color: var(--primary-color);
            transform: translateY(-3px);
        }
        
        .class-card.selected {
            border-color: var(--success-color);
            background: rgba(40, 167, 69, 0.05);
        }
        
        .class-info {
            text-align: center;
        }
        
        .class-icon {
            font-size: 32px;
            margin-bottom: 10px;
            color: var(--primary-color);
        }
        
        .student-card {
            background: white;
            border: 2px solid var(--border-color);
            border-radius: 10px;
            padding: 20px;
            transition: all 0.3s ease;
            cursor: pointer;
            position: relative;
        }
        
        .student-card:hover {
            border-color: var(--primary-color);
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.1);
        }
        
        .student-card.selected {
            border-color: var(--success-color);
            background: rgba(40, 167, 69, 0.05);
        }
        
        .student-card.disabled {
            opacity: 0.6;
            cursor: not-allowed;
            background: #f8f9fa;
        }
        
        .student-info {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 15px;
        }
        
        .student-photo {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid white;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .student-details h4 {
            margin: 0 0 5px 0;
            color: var(--dark-color);
            font-weight: 600;
        }
        
        .student-details p {
            margin: 0;
            color: var(--secondary-color);
            font-size: 14px;
        }
        
        .badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            margin-top: 5px;
        }
        
        .badge-english {
            background: rgba(74, 107, 255, 0.1);
            color: var(--primary-color);
            border: 1px solid rgba(74, 107, 255, 0.3);
        }
        
        .badge-hindi {
            background: rgba(255, 193, 7, 0.1);
            color: var(--warning-color);
            border: 1px solid rgba(255, 193, 7, 0.3);
        }
        
        .checkbox-container {
            display: flex;
            align-items: center;
            gap: 10px;
            padding-top: 15px;
            border-top: 1px solid var(--border-color);
        }
        
        .checkbox-container input[type="checkbox"] {
            width: 20px;
            height: 20px;
            cursor: pointer;
            accent-color: var(--primary-color);
        }
        
        .assigned-list {
            margin-top: 30px;
        }
        
        .assigned-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            margin-bottom: 10px;
            background: white;
            transition: all 0.3s ease;
        }
        
        .assigned-item:hover {
            background: var(--light-color);
        }
        
        .actions {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        
        .status-badge {
            padding: 4px 10px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .status-assigned {
            background: rgba(40, 167, 69, 0.1);
            color: var(--success-color);
        }
        
        .status-other {
            background: rgba(220, 53, 69, 0.1);
            color: var(--danger-color);
        }
        
        .footer {
            text-align: center;
            padding: 20px;
            background: var(--light-color);
            color: var(--secondary-color);
            border-top: 1px solid var(--border-color);
        }
        
        .action-buttons {
            display: flex;
            gap: 15px;
            margin-top: 25px;
            justify-content: center;
        }
        
        .selection-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding: 15px;
            background: var(--light-color);
            border-radius: 8px;
        }
        
        .section-divider {
            height: 2px;
            background: var(--border-color);
            margin: 40px 0;
            position: relative;
        }
        
        .section-divider::before {
            content: '';
            position: absolute;
            top: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 40px;
            height: 20px;
            background: var(--primary-color);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 12px;
            font-weight: bold;
        }
        
        .section-divider.class-divider::before {
            content: 'Classes';
        }
        
        .section-divider.student-divider::before {
            content: 'Students';
        }
        
        @media (max-width: 768px) {
            .class-grid, .student-grid {
                grid-template-columns: 1fr;
            }
            
            .filters {
                flex-direction: column;
            }
            
            .filter-select {
                width: 100%;
            }
            
            .header h1 {
                font-size: 2rem;
            }
            
            .content {
                padding: 20px;
            }
            
            .action-buttons {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
                margin-bottom: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-user-graduate"></i> Teacher Management System</h1>
            <p>Assign teachers to classes and students</p>
        </div>
        
        <div class="content">
            <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?php 
                echo htmlspecialchars($_SESSION['success_message']); 
                unset($_SESSION['success_message']);
                ?>
            </div>
            <?php endif; ?>
            
            <!-- Teacher Selection -->
            <div class="card">
                <h2><i class="fas fa-chalkboard-teacher"></i> Select Teacher</h2>
                <form method="get">
                    <div class="form-group">
                        <label for="teacher_id">Choose a Teacher</label>
                        <select name="teacher_id" id="teacher_id" class="form-control" required onchange="this.form.submit()">
                            <option value="">-- Select Teacher --</option>
                            <?php foreach ($teachers as $teacher): 
                                $has_classes = isset($teacher_classes[$teacher['id']]) && !empty($teacher_classes[$teacher['id']]);
                            ?>
                                <option value="<?php echo $teacher['id']; ?>" <?php if(isset($_GET['teacher_id']) && $_GET['teacher_id'] == $teacher['id']) echo 'selected'; ?>>
                                    <?php echo htmlspecialchars($teacher['first_name'] . ' ' . $teacher['last_name']); ?> - <?php echo htmlspecialchars($teacher['subject']); ?>
                                    <?php if($has_classes): ?> (Has <?php echo count($teacher_classes[$teacher['id']]); ?> class assignments)<?php endif; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </form>
            </div>
            
            <?php if (isset($_GET['teacher_id']) && $_GET['teacher_id']): 
                $selected_teacher_id = $_GET['teacher_id'];
                $selected_teacher = null;
                foreach ($teachers as $teacher) {
                    if ($teacher['id'] == $selected_teacher_id) {
                        $selected_teacher = $teacher;
                        break;
                    }
                }
                
                if ($selected_teacher):
                    // Get teacher's assigned classes
                    $teacher_assigned_classes = isset($teacher_classes[$selected_teacher_id]) ? $teacher_classes[$selected_teacher_id] : [];
                    
                    // Filter students based on teacher's assigned classes
                    $filtered_students = [];
                    if (!empty($teacher_assigned_classes)) {
                        foreach ($all_students as $student) {
                            foreach ($teacher_assigned_classes as $class_assignment) {
                                if ($student['class'] == $class_assignment['class'] && 
                                    $student['medium'] == $class_assignment['medium']) {
                                    $filtered_students[] = $student;
                                    break;
                                }
                            }
                        }
                    }
            ?>
            
            <!-- Selected Teacher Info -->
            <div class="teacher-profile">
                <?php if (!empty($selected_teacher['photo'])): ?>
                    <img src="<?php echo htmlspecialchars($selected_teacher['photo']); ?>" alt="Teacher" class="teacher-avatar">
                <?php else: ?>
                    <div class="avatar-placeholder">
                        <?php echo substr($selected_teacher['first_name'], 0, 1) . substr($selected_teacher['last_name'], 0, 1); ?>
                    </div>
                <?php endif; ?>
                <div class="teacher-info">
                    <h3><?php echo htmlspecialchars($selected_teacher['first_name'] . ' ' . $selected_teacher['last_name']); ?></h3>
                    <p><strong>Subject:</strong> <?php echo htmlspecialchars($selected_teacher['subject']); ?></p>
                    <p><strong>Assigned Classes:</strong> <?php echo count($teacher_class_assignments); ?></p>
                    <p><strong>Assigned Students:</strong> <?php echo count($teacher_assignments ?? []); ?></p>
                </div>
            </div>
            
            <!-- Section 1: Assign Classes -->
            <div class="card">
                <h2><i class="fas fa-graduation-cap"></i> Step 1: Assign Classes to Teacher</h2>
                <p>Select the classes (8-12) and mediums this teacher will teach:</p>
                
                <form method="post" id="classAssignmentForm">
                    <input type="hidden" name="teacher_id" value="<?php echo $selected_teacher_id; ?>">
                    
                    <div class="selection-info">
                        <h3 style="margin: 0; color: var(--dark-color);">
                            <i class="fas fa-graduation-cap"></i> Select Classes to Assign
                        </h3>
                        <div style="font-size: 16px; color: var(--primary-color); font-weight: 600;">
                            <span id="selectedClassCount">0</span> classes selected
                        </div>
                    </div>
                    
                    <div class="action-buttons">
                        <button type="button" class="btn btn-primary" onclick="selectAllClasses()">
                            <i class="fas fa-check-double"></i> Select All
                        </button>
                        <button type="button" class="btn btn-secondary" onclick="deselectAllClasses()">
                            <i class="fas fa-times"></i> Deselect All
                        </button>
                    </div>
                    
                    <div class="class-grid" id="classGrid">
                        <?php foreach ($available_classes as $class): 
                            foreach ($available_mediums as $medium):
                                $class_key = $class . '_' . $medium;
                                $is_assigned = false;
                                foreach ($teacher_class_assignments as $assignment) {
                                    if ($assignment['class'] == $class && $assignment['medium'] == $medium) {
                                        $is_assigned = true;
                                        break;
                                    }
                                }
                        ?>
                        <div class="class-card <?php echo $is_assigned ? 'selected' : ''; ?>" 
                             data-class="<?php echo $class; ?>"
                             data-medium="<?php echo $medium; ?>">
                            <div class="class-info">
                                <div class="class-icon">
                                    <i class="fas fa-chalkboard-teacher"></i>
                                </div>
                                <h4>Class <?php echo $class; ?></h4>
                                <span class="badge <?php echo $medium === 'English' ? 'badge-english' : 'badge-hindi'; ?>">
                                    <?php echo $medium; ?> Medium
                                </span>
                            </div>
                            
                            <div class="checkbox-container">
                                <input type="checkbox" 
                                       name="classes[]" 
                                       value="<?php echo $class_key; ?>" 
                                       id="class_<?php echo $class_key; ?>"
                                       <?php echo $is_assigned ? 'checked' : ''; ?>
                                       onchange="updateClassSelectionCount()">
                                <label for="class_<?php echo $class_key; ?>" style="cursor: pointer; flex: 1;">
                                    <?php if ($is_assigned): ?>
                                        <span style="color: var(--success-color); font-weight: 600;">
                                            <i class="fas fa-check-circle"></i> Assigned
                                        </span>
                                    <?php else: ?>
                                        <span style="color: var(--primary-color); font-weight: 600;">
                                            <i class="fas fa-plus-circle"></i> Assign
                                        </span>
                                    <?php endif; ?>
                                </label>
                            </div>
                        </div>
                        <?php endforeach; endforeach; ?>
                    </div>
                    
                    <div class="action-buttons">
                        <button type="submit" name="assign_classes" class="btn btn-success btn-lg">
                            <i class="fas fa-save"></i> Save Class Assignments
                        </button>
                    </div>
                </form>
            </div>
            
            <!-- Current Class Assignments -->
            <?php if (!empty($teacher_class_assignments)): ?>
            <div class="card">
                <h3><i class="fas fa-list-check"></i> Currently Assigned Classes</h3>
                <div class="assigned-list">
                    <?php foreach ($teacher_class_assignments as $assignment): ?>
                    <div class="assigned-item">
                        <div style="display: flex; align-items: center; gap: 15px;">
                            <div style="width: 50px; height: 50px; border-radius: 50%; background: var(--primary-color); color: white; display: flex; align-items: center; justify-content: center; font-size: 20px; font-weight: bold;">
                                <?php echo $assignment['class']; ?>
                            </div>
                            <div>
                                <strong style="font-size: 16px;">Class <?php echo htmlspecialchars($assignment['class']); ?></strong>
                                <div style="font-size: 14px; color: var(--secondary-color); margin-top: 5px;">
                                    <span class="badge <?php echo $assignment['medium'] === 'English' ? 'badge-english' : 'badge-hindi'; ?>" style="font-size: 11px;">
                                        <?php echo htmlspecialchars($assignment['medium']); ?> Medium
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="actions">
                            <span class="status-badge status-assigned">
                                <i class="fas fa-user-check"></i> Assigned
                            </span>
                            <a href="?teacher_id=<?php echo $selected_teacher_id; ?>&remove_class=<?php echo $assignment['id']; ?>" 
                               class="btn btn-danger" 
                               onclick="return confirm('Are you sure you want to remove this class assignment?')">
                                <i class="fas fa-trash"></i> Remove
                            </a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Section Divider -->
            <div class="section-divider student-divider"></div>
            
            <!-- Section 2: Assign Students (Only if teacher has classes assigned) -->
            <?php if (!empty($filtered_students)): ?>
            <div class="card">
                <h2><i class="fas fa-users"></i> Step 2: Assign Students to Teacher</h2>
                <p>Select students from the assigned classes to assign to this teacher:</p>
                
                <!-- Filter Options -->
                <div class="filters">
                    <select id="filterClass" class="filter-select" onchange="filterStudents()">
                        <option value="">All Classes</option>
                        <?php 
                        $unique_classes = array_unique(array_column($filtered_students, 'class'));
                        sort($unique_classes);
                        foreach ($unique_classes as $class): ?>
                            <option value="<?php echo htmlspecialchars($class); ?>">Class <?php echo htmlspecialchars($class); ?></option>
                        <?php endforeach; ?>
                    </select>
                    
                    <select id="filterMedium" class="filter-select" onchange="filterStudents()">
                        <option value="">All Mediums</option>
                        <option value="English">English Medium</option>
                        <option value="Hindi">Hindi Medium</option>
                    </select>
                    
                    <input type="text" id="searchName" class="filter-select" placeholder="Search by name..." onkeyup="filterStudents()" style="flex: 1;">
                </div>
                
                <form method="post" id="studentAssignmentForm">
                    <input type="hidden" name="teacher_id" value="<?php echo $selected_teacher_id; ?>">
                    
                    <div class="selection-info">
                        <h3 style="margin: 0; color: var(--dark-color);">
                            <i class="fas fa-user-graduate"></i> Select Students to Assign
                        </h3>
                        <div style="font-size: 16px; color: var(--primary-color); font-weight: 600;">
                            <span id="selectedStudentCount">0</span> students selected
                        </div>
                    </div>
                    
                    <div class="action-buttons">
                        <button type="button" class="btn btn-primary" onclick="selectAllStudents()">
                            <i class="fas fa-check-double"></i> Select All
                        </button>
                        <button type="button" class="btn btn-secondary" onclick="deselectAllStudents()">
                            <i class="fas fa-times"></i> Deselect All
                        </button>
                    </div>
                    
                    <div class="student-grid" id="studentGrid">
                        <?php foreach ($filtered_students as $student): 
                            $student_key = $student['id'] . '_' . $student['medium'];
                            $is_assigned = isset($assigned_students[$student_key]) && $assigned_students[$student_key] == $selected_teacher_id;
                            $is_assigned_to_other = isset($assigned_students[$student_key]) && $assigned_students[$student_key] != $selected_teacher_id;
                        ?>
                        <div class="student-card <?php echo $is_assigned ? 'selected' : ''; ?> <?php echo $is_assigned_to_other ? 'disabled' : ''; ?>" 
                             data-class="<?php echo htmlspecialchars($student['class']); ?>"
                             data-medium="<?php echo htmlspecialchars($student['medium']); ?>"
                             data-name="<?php echo strtolower(htmlspecialchars($student['first_name'] . ' ' . $student['last_name'])); ?>">
                            <div class="student-info">
                                <?php 
                                $photoPath = "/ruchi_classes_form/student/" . $student['photo'];
                                if (!empty($student['photo']) && file_exists($_SERVER['DOCUMENT_ROOT'] . $photoPath)) {
                                    echo '<img src="'.$photoPath.'" alt="Student" class="student-photo">';
                                } else {
                                    echo '<img src="https://ui-avatars.com/api/?name='.urlencode($student['first_name'].' '.$student['last_name']).'&size=50&background='.($student['medium'] === 'English' ? '4a6bff' : 'ffc107').'&color=fff" alt="Student" class="student-photo">';
                                }
                                ?>
                                <div class="student-details">
                                    <h4><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></h4>
                                    <p>Class <?php echo htmlspecialchars($student['class']); ?></p>
                                    <span class="badge <?php echo $student['medium'] === 'English' ? 'badge-english' : 'badge-hindi'; ?>">
                                        <i class="fas fa-globe"></i> <?php echo htmlspecialchars($student['medium']); ?> Medium
                                    </span>
                                </div>
                            </div>
                            
                            <div class="checkbox-container">
                                <input type="checkbox" 
                                       name="students[]" 
                                       value="<?php echo $student_key; ?>" 
                                       id="student_<?php echo $student_key; ?>"
                                       <?php echo $is_assigned ? 'checked' : ''; ?>
                                       <?php echo $is_assigned_to_other ? 'disabled' : ''; ?>
                                       onchange="updateStudentSelectionCount()">
                                <label for="student_<?php echo $student_key; ?>" style="cursor: pointer; flex: 1;">
                                    <?php if ($is_assigned): ?>
                                        <span style="color: var(--success-color); font-weight: 600;">
                                            <i class="fas fa-check-circle"></i> Assigned
                                        </span>
                                    <?php elseif ($is_assigned_to_other): ?>
                                        <span style="color: var(--danger-color); font-weight: 600;">
                                            <i class="fas fa-exclamation-circle"></i> Assigned to Another Teacher
                                        </span>
                                    <?php else: ?>
                                        <span style="color: var(--primary-color); font-weight: 600;">
                                            <i class="fas fa-plus-circle"></i> Assign to Teacher
                                        </span>
                                    <?php endif; ?>
                                </label>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="action-buttons">
                        <button type="submit" name="assign_students" class="btn btn-success btn-lg">
                            <i class="fas fa-save"></i> Save Student Assignments
                        </button>
                    </div>
                </form>
            </div>
            
            <!-- Current Student Assignments -->
            <?php if (!empty($teacher_assignments)): ?>
            <div class="card">
                <h3><i class="fas fa-list-check"></i> Currently Assigned Students</h3>
                <div class="assigned-list">
                    <?php foreach ($teacher_assignments as $assignment): ?>
                    <div class="assigned-item">
                        <div style="display: flex; align-items: center; gap: 15px;">
                            <?php 
                            $photoPath = "/ruchi_classes_form/student/" . $assignment['photo'];
                            if (!empty($assignment['photo']) && file_exists($_SERVER['DOCUMENT_ROOT'] . $photoPath)) {
                                echo '<img src="'.$photoPath.'" alt="Student" style="width: 50px; height: 50px; border-radius: 50%; object-fit: cover;">';
                            } else {
                                echo '<div style="width: 50px; height: 50px; border-radius: 50%; background: '.($assignment['medium'] === 'English' ? 'var(--primary-color)' : 'var(--warning-color)').'; color: white; display: flex; align-items: center; justify-content: center; font-weight: bold;">'
                                    . substr($assignment['first_name'], 0, 1) . substr($assignment['last_name'], 0, 1)
                                    . '</div>';
                            }
                            ?>
                            <div>
                                <strong style="font-size: 16px;"><?php echo htmlspecialchars($assignment['first_name'] . ' ' . $assignment['last_name']); ?></strong>
                                <div style="font-size: 14px; color: var(--secondary-color); margin-top: 5px;">
                                    Class <?php echo htmlspecialchars($assignment['class']); ?> • 
                                    <span class="badge <?php echo $assignment['medium'] === 'English' ? 'badge-english' : 'badge-hindi'; ?>" style="font-size: 11px;">
                                        <?php echo htmlspecialchars($assignment['medium']); ?> Medium
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="actions">
                            <span class="status-badge status-assigned">
                                <i class="fas fa-user-check"></i> Assigned
                            </span>
                            <a href="?teacher_id=<?php echo $selected_teacher_id; ?>&remove_assignment=<?php echo $assignment['id']; ?>" 
                               class="btn btn-danger" 
                               onclick="return confirm('Are you sure you want to remove this assignment?')">
                                <i class="fas fa-trash"></i> Remove
                            </a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <?php else: ?>
            <div class="card">
                <div style="text-align: center; padding: 40px;">
                    <i class="fas fa-exclamation-triangle" style="font-size: 60px; color: var(--warning-color); margin-bottom: 20px;"></i>
                    <h3 style="color: var(--secondary-color);">No Students Available</h3>
                    <p>First assign classes to this teacher in Step 1. Students will appear here once classes are assigned.</p>
                </div>
            </div>
            <?php endif; ?>
            
            <?php else: ?>
            <div class="card">
                <div style="text-align: center; padding: 40px;">
                    <i class="fas fa-exclamation-triangle" style="font-size: 60px; color: var(--warning-color); margin-bottom: 20px;"></i>
                    <h3 style="color: var(--secondary-color);">No Classes Assigned</h3>
                    <p>This teacher doesn't have any class assignments. Please assign classes in Step 1 first.</p>
                </div>
            </div>
            <?php endif; ?>
            
            <?php endif; ?>
            <?php endif; ?>
        </div>
        
        <div class="footer">
            <p>&copy; <?php echo date('Y'); ?> Ruchi Classes. All rights reserved. | <i class="fas fa-graduation-cap"></i> Education Management System</p>
        </div>
    </div>

    <script>
        // Class Selection Functions
        function updateClassSelectionCount() {
            const checkboxes = document.querySelectorAll('#classAssignmentForm input[name="classes[]"]:checked');
            document.getElementById('selectedClassCount').textContent = checkboxes.length;
        }
        
        function selectAllClasses() {
            const checkboxes = document.querySelectorAll('#classAssignmentForm input[name="classes[]"]');
            checkboxes.forEach(cb => {
                cb.checked = true;
            });
            updateClassSelectionCount();
        }
        
        function deselectAllClasses() {
            const checkboxes = document.querySelectorAll('#classAssignmentForm input[name="classes[]"]');
            checkboxes.forEach(cb => {
                cb.checked = false;
            });
            updateClassSelectionCount();
        }
        
        // Student Selection Functions
        function updateStudentSelectionCount() {
            const checkboxes = document.querySelectorAll('#studentAssignmentForm input[name="students[]"]:checked:not(:disabled)');
            document.getElementById('selectedStudentCount').textContent = checkboxes.length;
        }
        
        function selectAllStudents() {
            const checkboxes = document.querySelectorAll('#studentAssignmentForm input[name="students[]"]:not(:disabled)');
            checkboxes.forEach(cb => {
                cb.checked = true;
            });
            updateStudentSelectionCount();
        }
        
        function deselectAllStudents() {
            const checkboxes = document.querySelectorAll('#studentAssignmentForm input[name="students[]"]:not(:disabled)');
            checkboxes.forEach(cb => {
                cb.checked = false;
            });
            updateStudentSelectionCount();
        }
        
        function filterStudents() {
            const classFilter = document.getElementById('filterClass').value;
            const mediumFilter = document.getElementById('filterMedium').value;
            const nameFilter = document.getElementById('searchName').value.toLowerCase();
            
            const studentCards = document.querySelectorAll('#studentGrid .student-card');
            
            studentCards.forEach(card => {
                const studentClass = card.getAttribute('data-class');
                const studentMedium = card.getAttribute('data-medium');
                const studentName = card.getAttribute('data-name');
                
                let show = true;
                
                if (classFilter && studentClass !== classFilter) {
                    show = false;
                }
                
                if (mediumFilter && studentMedium !== mediumFilter) {
                    show = false;
                }
                
                if (nameFilter && !studentName.includes(nameFilter)) {
                    show = false;
                }
                
                card.style.display = show ? 'block' : 'none';
            });
        }
        
        // Initialize selection counts
        document.addEventListener('DOMContentLoaded', function() {
            updateClassSelectionCount();
            updateStudentSelectionCount();
            
            // Make class cards clickable
            document.querySelectorAll('.class-card').forEach(card => {
                card.addEventListener('click', function(e) {
                    if (!e.target.closest('input[type="checkbox"]') && !e.target.closest('label')) {
                        const checkbox = this.querySelector('input[type="checkbox"]');
                        if (checkbox) {
                            checkbox.checked = !checkbox.checked;
                            updateClassSelectionCount();
                        }
                    }
                });
            });
            
            // Make student cards clickable
            document.querySelectorAll('.student-card:not(.disabled)').forEach(card => {
                card.addEventListener('click', function(e) {
                    if (!e.target.closest('input[type="checkbox"]') && !e.target.closest('label')) {
                        const checkbox = this.querySelector('input[type="checkbox"]:not(:disabled)');
                        if (checkbox) {
                            checkbox.checked = !checkbox.checked;
                            updateStudentSelectionCount();
                        }
                    }
                });
            });
        });
        
        // Form submission confirmation
        document.getElementById('classAssignmentForm').addEventListener('submit', function(e) {
            const checkboxes = document.querySelectorAll('input[name="classes[]"]:checked');
            if (checkboxes.length === 0) {
                e.preventDefault();
                alert('Please select at least one class to assign.');
                return false;
            }
            
            return confirm(`Are you sure you want to assign ${checkboxes.length} classes to this teacher?`);
        });
        
        document.getElementById('studentAssignmentForm').addEventListener('submit', function(e) {
            const checkboxes = document.querySelectorAll('#studentAssignmentForm input[name="students[]"]:checked');
            if (checkboxes.length === 0) {
                e.preventDefault();
                alert('Please select at least one student to assign.');
                return false;
            }
            
            return confirm(`Are you sure you want to assign ${checkboxes.length} students to this teacher?`);
        });
    </script>
</body>
</html>