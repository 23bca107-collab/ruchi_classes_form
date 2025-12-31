<?php
session_start();
require '../db.php';

// Redirect if teacher not logged in
if (!isset($_SESSION['teacher_logged_in'])) {
    header("Location: teacher_login.php");
    exit();
}

// Get teacher info
$teacher_id = $_SESSION['teacher_id'] ?? null;
$teacher = [];
if ($teacher_id) {
    $stmt = $conn->prepare("SELECT first_name, last_name, photo, subject FROM teachers WHERE id = ?");
    $stmt->bind_param("i", $teacher_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $teacher = $result->fetch_assoc() ?? [];
}

// Get teacher's assigned classes from teacher_classes table
$teacher_classes = [];
$assigned_classes_stmt = $conn->prepare("SELECT class, medium FROM teacher_classes WHERE teacher_id = ?");
if ($assigned_classes_stmt) {
    $assigned_classes_stmt->bind_param("i", $teacher_id);
    $assigned_classes_stmt->execute();
    $result = $assigned_classes_stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $teacher_classes[] = $row;
    }
    $assigned_classes_stmt->close();
}

// Get all students from teacher's assigned classes (Classes 8-12)
$all_students = [];
$student_counts = [
    'english' => 0,
    'hindi' => 0,
    'total' => 0
];

// If teacher has assigned classes, get students from those classes
if (!empty($teacher_classes)) {
    // Get all classes assigned to this teacher
    $assigned_class_list = [];
    foreach ($teacher_classes as $class) {
        $assigned_class_list[$class['class'] . '_' . $class['medium']] = $class;
    }
    
    // Get English medium students from assigned classes
    $stmt = $conn->prepare("
        SELECT id, first_name, last_name, photo, class, father_name, mother_name, 
               parent_mobile, personal_mobile, whatsapp, 'English' as medium 
        FROM student_english 
        WHERE class IN ('8', '9', '10', '11', '12')
        ORDER BY class ASC, first_name ASC
    ");
    if ($stmt) {
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            // Check if this class/medium is assigned to the teacher
            $class_key = $row['class'] . '_' . $row['medium'];
            if (isset($assigned_class_list[$class_key])) {
                $all_students[] = $row;
                $student_counts['english']++;
                $student_counts['total']++;
            }
        }
        $stmt->close();
    }
    
    // Get Hindi medium students from assigned classes
    $stmt = $conn->prepare("
        SELECT id, first_name, last_name, photo, class, father_name, mother_name, 
               parent_mobile, personal_mobile, whatsapp, 'Hindi' as medium 
        FROM student_hindi 
        WHERE class IN ('8', '9', '10', '11', '12')
        ORDER BY class ASC, first_name ASC
    ");
    if ($stmt) {
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            // Check if this class/medium is assigned to the teacher
            $class_key = $row['class'] . '_' . $row['medium'];
            if (isset($assigned_class_list[$class_key])) {
                $all_students[] = $row;
                $student_counts['hindi']++;
                $student_counts['total']++;
            }
        }
        $stmt->close();
    }
}

// Filter students by class if requested
$filter_class = $_GET['class'] ?? '';
$filter_medium = $_GET['medium'] ?? '';
$filtered_students = $all_students;

if ($filter_class) {
    $filtered_students = array_filter($filtered_students, function($student) use ($filter_class) {
        return $student['class'] == $filter_class;
    });
}

if ($filter_medium) {
    $filtered_students = array_filter($filtered_students, function($student) use ($filter_medium) {
        return $student['medium'] == $filter_medium;
    });
}

// Get unique classes from assigned students for filter dropdown
$classes = array_unique(array_column($all_students, 'class'));
sort($classes);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Students | Ruchi Classes</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #2563eb;
            --primary-light: #3b82f6;
            --primary-dark: #1d4ed8;
            --secondary: #f8fafc;
            --secondary-light: #f1f5f9;
            --accent: #f59e0b;
            --accent-light: #fbbf24;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --info: #06b6d4;

            --text-primary: #1e293b;
            --text-secondary: #475569;
            --text-muted: #64748b;

            --bg-primary: #ffffff;
            --bg-secondary: #f8fafc;
            --bg-card: #ffffff;
            --bg-hover: #f1f5f9;

            --border: #e2e8f0;
            --shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);

            --gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --gradient-primary: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            --gradient-accent: linear-gradient(135deg, var(--accent) 0%, #ea580c 100%);

            --sidebar-bg: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
            --main-bg: #ffffff;
            --card-bg: #ffffff;
            --header-bg: rgba(255, 255, 255, 0.9);
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            display: flex;
            overflow-x: hidden;
        }
        
        /* ----------------- SIDEBAR ------------------ */
        .sidebar {
            width: 280px;
            background: var(--sidebar-bg);
            padding: 1.5rem 1rem;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: var(--shadow);
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            border-right: 1px solid var(--border);
            z-index: 1000;
        }

        .sidebar.collapsed {
            width: 85px;
            padding: 1.5rem 0.5rem;
        }

        .logo-container {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 2rem;
            padding: 0 10px;
            transition: all 0.4s ease;
            height: 90px;
            overflow: hidden;
        }

        .sidebar.collapsed .logo-container {
            padding: 0 5px;
            justify-content: center;
            gap: 0;
            height: 85px;
            margin-bottom: 1.5rem;
        }

        .logo-img {
            width: 85px;
            height: 85px;
            border-radius: 16px;
            object-fit: contain;
            background: white;
            padding: 8px;
            border: 4px solid var(--primary);
            box-shadow: 0 6px 20px rgba(37, 99, 235, 0.25);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            display: block;
            flex-shrink: 0;
        }

        .sidebar.collapsed .logo-img {
            width: 70px;
            height: 70px;
            border-radius: 14px;
            border-width: 3px;
            padding: 6px;
            box-shadow: 0 4px 15px rgba(37, 99, 235, 0.2);
        }

        .logo-img:hover {
            transform: scale(1.05);
            box-shadow: 0 8px 25px rgba(37, 99, 235, 0.3);
        }

        .logo-text {
            font-size: 26px;
            font-weight: 800;
            color: var(--primary);
            line-height: 1.2;
            white-space: nowrap;
            overflow: visible;
            transition: all 0.4s ease;
            min-width: 150px;
        }

        .logo-text span {
            display: block;
            font-size: 11px;
            font-weight: 500;
            color: var(--text-secondary);
            margin-top: 5px;
            white-space: normal;
            overflow: visible;
            word-break: keep-all;
            max-width: 180px;
        }

        .sidebar.collapsed .logo-text {
            opacity: 0;
            width: 0;
            height: 0;
            overflow: hidden;
            margin: 0;
            padding: 0;
            font-size: 0;
            min-width: 0;
        }

        .sidebar.collapsed .logo-text span {
            display: none;
        }

        .nav-item {
            display: flex;
            align-items: center;
            padding: 16px 18px;
            border-radius: 14px;
            margin-bottom: 10px;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            color: var(--text-secondary);
            position: relative;
            text-decoration: none;
            white-space: nowrap;
        }

        .nav-item::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            height: 100%;
            width: 4px;
            background: var(--primary);
            transform: scaleY(0);
            transition: 0.3s ease;
            border-radius: 0 4px 4px 0;
        }

        .nav-item:hover {
            background: var(--bg-hover);
            color: var(--text-primary);
            transform: translateX(5px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        }

        .nav-item:hover::before {
            transform: scaleY(1);
        }

        .nav-item.active {
            background: var(--gradient-primary);
            color: white;
            box-shadow: 0 4px 15px rgba(37, 99, 235, 0.2);
        }

        .nav-item.active::before {
            transform: scaleY(1);
            background: var(--accent-light);
        }

        .nav-icon {
            margin-right: 16px;
            font-size: 20px;
            width: 28px;
            text-align: center;
            transition: all 0.3s ease;
            flex-shrink: 0;
        }

        .nav-item:hover .nav-icon {
            transform: scale(1.1);
        }

        .nav-text {
            font-size: 15px;
            font-weight: 500;
            transition: all 0.4s ease;
            white-space: nowrap;
            overflow: hidden;
        }

        .sidebar.collapsed .nav-text {
            opacity: 0;
            width: 0;
            height: 0;
            overflow: hidden;
            margin: 0;
            padding: 0;
            font-size: 0;
        }

        .sidebar.collapsed .nav-item {
            justify-content: center;
            padding: 18px 0;
            margin: 0 5px 10px;
        }

        .sidebar.collapsed .nav-icon {
            margin-right: 0;
            font-size: 22px;
            width: 30px;
        }

        .sidebar.collapsed .dropdown-icon {
            display: none;
        }

        .sidebar.collapsed .dropdown-menu {
            display: none !important;
        }

        /* ---------------- MOBILE SIDEBAR OVERLAY ---------------- */
        .sidebar-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 999;
            backdrop-filter: blur(5px);
        }

        .sidebar-overlay.active {
            display: block;
            animation: fadeIn 0.3s ease;
        }

        /* ---------------- MAIN CONTENT ----------------- */

        .main-content {
            flex: 1;
            margin-left: 280px;
            padding: 2rem;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            background: var(--main-bg);
            position: relative;
            min-height: 100vh;
        }

        .main-content.expanded {
            margin-left: 85px;
        }

        .main-content::before {
            content: '';
            position: absolute;
            inset: 0;
            background:
                radial-gradient(circle at 20% 80%, rgba(37, 99, 235, 0.05) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(245, 158, 11, 0.05) 0%, transparent 50%),
                radial-gradient(circle at 40% 40%, rgba(16, 185, 129, 0.03) 0%, transparent 50%);
            pointer-events: none;
        }

        /* ---------------- HEADER ----------------- */

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding: 1.5rem;
            background: var(--header-bg);
            border-radius: 16px;
            backdrop-filter: blur(10px);
            border: 1px solid var(--border);
            z-index: 1;
            position: relative;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        }

        .toggle-sidebar {
            background: var(--gradient-primary);
            border: none;
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            color: white;
            box-shadow: 0 4px 15px rgba(37, 99, 235, 0.2);
        }

        .toggle-sidebar:hover {
            transform: rotate(90deg) scale(1.1);
            box-shadow: 0 6px 20px rgba(37, 99, 235, 0.3);
        }

        .toggle-sidebar:active {
            transform: rotate(90deg) scale(0.95);
        }

        .user-menu {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .notifications {
            position: relative;
            padding: 12px;
            border-radius: 12px;
            cursor: pointer;
            transition: 0.3s ease;
            color: var(--text-secondary);
            background: var(--bg-card);
            border: 1px solid var(--border);
        }

        .notifications:hover {
            background: var(--bg-hover);
            color: var(--text-primary);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        }

        .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background: var(--danger);
            color: white;
            font-size: 11px;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            border: 2px solid white;
        }

        .user-profile {
            display: flex;
            align-items: center;
            gap: 15px;
            cursor: pointer;
            padding: 10px 18px;
            border-radius: 14px;
            transition: 0.3s ease;
            background: var(--bg-card);
            border: 1px solid var(--border);
        }

        .user-profile:hover {
            background: var(--bg-hover);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        }

        .user-avatar {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid var(--primary);
            box-shadow: 0 4px 15px rgba(37, 99, 235, 0.2);
        }

        .user-name {
            font-weight: 600;
            color: var(--text-primary);
        }

        /* ---------------- DROPDOWN ---------------- */
        .dropdown {
            position: relative;
            cursor: pointer;
        }

        .dropdown-icon {
            margin-left: auto;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            font-size: 16px;
            opacity: 0.7;
        }

        .dropdown-menu {
            display: none;
            flex-direction: column;
            margin-left: 50px;
            margin-top: 10px;
            background: var(--bg-card);
            border-radius: 12px;
            border: 1px solid var(--border);
            overflow: hidden;
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        }

        .dropdown.open + .dropdown-menu {
            display: flex;
            animation: slideDown 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .dropdown.open .dropdown-icon {
            transform: rotate(180deg);
            opacity: 1;
        }

        .dropdown-item {
            padding: 15px 20px;
            text-decoration: none;
            font-size: 15px;
            margin: 0;
            color: var(--text-secondary);
            transition: all 0.3s ease;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .dropdown-item:last-child {
            border-bottom: none;
        }

        .dropdown-item:hover {
            background: var(--gradient-primary);
            color: white;
            transform: translateX(8px);
            box-shadow: 0 4px 15px rgba(37, 99, 235, 0.2);
        }

        /* ---------------- TEACHER INFO CARD ---------------- */
        .teacher-info-card {
            background: var(--gradient-primary);
            border-radius: 16px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            color: white;
            box-shadow: 0 8px 25px rgba(37, 99, 235, 0.25);
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .teacher-info-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid white;
            box-shadow: 0 6px 20px rgba(0,0,0,0.2);
        }

        .teacher-info-details h2 {
            margin: 0 0 10px 0;
            font-size: 1.8rem;
        }

        .teacher-info-details p {
            margin: 5px 0;
            opacity: 0.9;
        }

        /* ---------------- ASSIGNED CLASSES SECTION ---------------- */
        .assigned-classes {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: var(--shadow);
        }

        .assigned-classes h3 {
            color: var(--text-primary);
            margin-bottom: 1rem;
            font-size: 1.3rem;
        }

        .class-chips {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .class-chip {
            padding: 8px 16px;
            background: var(--secondary);
            border-radius: 25px;
            font-size: 14px;
            font-weight: 500;
            color: var(--text-primary);
            border: 2px solid var(--border);
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .class-chip:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }

        .class-chip.english {
            border-left: 4px solid var(--primary);
        }

        .class-chip.hindi {
            border-left: 4px solid var(--warning);
        }

        /* ---------------- STUDENTS PAGE STYLES ---------------- */
        .container {
            max-width: 1200px;
            margin: 0 auto;
            width: 100%;
        }
        
        .page-header {
            text-align: center;
            margin-bottom: 30px;
            animation: fadeInDown 0.8s ease;
        }
        
        .page-header h1 {
            color: var(--text-primary);
            font-size: 2.5rem;
            margin-bottom: 10px;
        }
        
        .page-header p {
            color: var(--text-secondary);
            font-size: 1.1rem;
        }
        
        .card {
            background: white;
            border-radius: 12px;
            box-shadow: var(--shadow);
            padding: 25px;
            margin-bottom: 30px;
            animation: fadeInUp 0.8s ease;
            transition: all 0.3s ease;
        }
        
        .card:hover {
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.15);
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: var(--gradient-primary);
            color: white;
            border-radius: 16px;
            padding: 1.5rem;
            text-align: center;
            box-shadow: 0 8px 25px rgba(37, 99, 235, 0.25);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            opacity: 0;
            animation: fadeIn 0.6s ease forwards;
        }
        
        .stat-card:nth-child(1) { animation-delay: 0.1s; }
        .stat-card:nth-child(2) { animation-delay: 0.2s; }
        .stat-card:nth-child(3) { animation-delay: 0.3s; }
        
        .stat-card:hover {
            transform: translateY(-5px) scale(1.05);
            box-shadow: 0 12px 30px rgba(37, 99, 235, 0.35);
        }
        
        .stat-icon {
            font-size: 32px;
            margin-bottom: 12px;
        }
        
        .stat-value {
            font-size: 36px;
            font-weight: 800;
            margin-bottom: 8px;
            line-height: 1;
        }
        
        .stat-label {
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 1px;
            opacity: 0.9;
        }
        
        .filter-form {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--text-primary);
        }
        
        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid var(--border);
            border-radius: 8px;
            font-size: 16px;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.2);
        }
        
        .btn {
            display: inline-block;
            padding: 12px 25px;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-align: center;
        }
        
        .btn:hover:not(:disabled) {
            background: var(--primary-dark);
            transform: translateY(-2px);
        }
        
        .btn:disabled {
            background: var(--text-muted);
            cursor: not-allowed;
            opacity: 0.7;
        }
        
        .btn-block {
            display: block;
            width: 100%;
        }
        
        .btn-success {
            background: var(--success);
        }
        
        .btn-success:hover:not(:disabled) {
            background: #0da271;
        }
        
        .btn-danger {
            background: var(--danger);
        }
        
        .btn-danger:hover:not(:disabled) {
            background: #dc2626;
        }
        
        .students-table {
            width: 100%;
            border-collapse: collapse;
            margin: 25px 0;
            font-size: 16px;
            animation: fadeIn 1s ease;
        }
        
        .students-table th,
        .students-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid var(--border);
        }
        
        .students-table th {
            background: var(--primary);
            color: white;
            font-weight: 600;
            position: sticky;
            top: 0;
        }
        
        .students-table tr {
            transition: all 0.3s ease;
        }
        
        .students-table tr:hover {
            background: rgba(37, 99, 235, 0.05);
        }
        
        .student-photo {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid white;
            box-shadow: var(--shadow);
        }
        
        .medium-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
        }
        
        .medium-english {
            background: rgba(37, 99, 235, 0.1);
            color: var(--primary);
            border: 1px solid rgba(37, 99, 235, 0.3);
        }
        
        .medium-hindi {
            background: rgba(245, 158, 11, 0.1);
            color: var(--warning);
            border: 1px solid rgba(245, 158, 11, 0.3);
        }
        
        .no-data {
            text-align: center;
            padding: 40px;
            color: var(--text-secondary);
            font-size: 18px;
        }
        
        .no-data i {
            font-size: 48px;
            margin-bottom: 15px;
            color: var(--border);
        }
        
        .footer {
            text-align: center;
            margin-top: 40px;
            color: var(--text-secondary);
            font-size: 14px;
        }
        
        .action-buttons {
            display: flex;
            gap: 8px;
        }
        
        .btn-sm {
            padding: 6px 12px;
            font-size: 14px;
        }
        
        .btn-icon {
            padding: 8px;
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .view-details-btn {
            background: var(--info);
        }
        
        .view-details-btn:hover {
            background: #0ea5e9;
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        @keyframes fadeInDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        .fade-in {
            animation: fadeIn 0.6s ease;
        }
        
        /* ---------------- ANIMATIONS ---------------- */
        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-15px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        @keyframes slideInLeft {
            from { transform: translateX(-100%); }
            to { transform: translateX(0); }
        }

        /* ---------------- RESPONSIVE DESIGN ---------------- */

        /* Tablet */
        @media (max-width: 1024px) {
            .sidebar {
                transform: translateX(-100%);
                z-index: 1000;
                box-shadow: 0 0 50px rgba(0, 0, 0, 0.2);
                width: 320px;
            }
            
            .sidebar.active {
                transform: translateX(0);
                animation: slideInLeft 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            }
            
            .main-content {
                margin-left: 0;
                padding: 1.5rem;
            }
            
            .header {
                padding: 1.2rem;
                margin-bottom: 1.5rem;
            }
            
            .page-header h1 {
                font-size: 2rem;
            }
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .teacher-info-card {
                flex-direction: column;
                text-align: center;
            }
        }

        /* Mobile */
        @media (max-width: 768px) {
            .main-content {
                padding: 1rem;
            }
            
            .header {
                padding: 1rem;
                margin-bottom: 1rem;
                border-radius: 14px;
            }
            
            .page-header h1 {
                font-size: 1.75rem;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .students-table {
                display: block;
                overflow-x: auto;
            }
            
            .action-buttons {
                flex-direction: column;
                min-width: 120px;
            }
            
            .btn-sm {
                width: 100%;
            }
            
            .logo-img {
                width: 75px;
                height: 75px;
            }
            
            .logo-text {
                font-size: 22px;
            }
            
            .user-menu {
                gap: 15px;
            }
            
            .toggle-sidebar {
                width: 45px;
                height: 45px;
            }
        }

        /* Small Mobile */
        @media (max-width: 480px) {
            .main-content {
                padding: 0.75rem;
            }
            
            .header {
                padding: 0.75rem;
            }
            
            .toggle-sidebar {
                width: 40px;
                height: 40px;
                font-size: 16px;
            }
            
            .user-avatar {
                width: 40px;
                height: 40px;
            }
            
            .notifications {
                padding: 10px;
            }
            
            .notification-badge {
                width: 18px;
                height: 18px;
                font-size: 10px;
            }
            
            .page-header h1 {
                font-size: 1.5rem;
            }
            
            .stat-card {
                padding: 1rem;
            }
            
            .stat-value {
                font-size: 28px;
            }
            
            .stat-label {
                font-size: 12px;
            }
        }

        /* Desktop */
        @media (min-width: 1025px) {
            .sidebar {
                width: 280px;
            }
            
            .main-content {
                margin-left: 280px;
            }
            
            .sidebar.collapsed {
                width: 85px;
            }
            
            .main-content.expanded {
                margin-left: 85px;
            }
        }
    </style>
</head>
<body>
    <!-- Mobile Sidebar Overlay -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <!-- Sidebar Navigation -->
    <div class="sidebar" id="sidebar">
        <div class="logo-container">
            <img src="../assets/Ruchi logo.jpg" alt="Ruchi Classes Logo" class="logo-img" id="logoImg">
            <div class="logo-text" id="logoText">
                Ruchi <br>Classes
                <span>Education for Excellence</span>
            </div>
        </div>
        <a href="teacher_dashboard.php" class="nav-item">
            <div class="nav-icon"><i class="fas fa-home"></i></div>
            <div class="nav-text">Dashboard</div>
        </a>
        <a href="teacher_attendance.php" class="nav-item">
            <div class="nav-icon"><i class="fas fa-clipboard-check"></i></div>
            <div class="nav-text">Attendance</div>
        </a>

        <!-- Exams Dropdown -->
        <div class="nav-item dropdown" id="examsDropdown">
            <div class="nav-icon"><i class="fas fa-file-alt"></i></div>
            <div class="nav-text">Exams</div>
            <i class="fas fa-caret-down dropdown-icon"></i>
        </div>
        <div class="dropdown-menu" id="examsMenu">
            <a href="teacher_add_exam.php" class="dropdown-item">➤ Add Exam</a>
            <a href="exam_marks_entry.php" class="dropdown-item">➤ Marks Entry</a>
        </div>

        <a href="teacher_complain.php" class="nav-item">
            <div class="nav-icon"><i class="fas fa-comment-dots"></i></div>
            <div class="nav-text">Complaint</div>
        </a>

        <a href="teacher_students.php" class="nav-item active">
            <div class="nav-icon"><i class="fas fa-users"></i></div>
            <div class="nav-text">Students</div>
        </a>
        
        <a href="teacher_grades.php" class="nav-item">
            <div class="nav-icon"><i class="fas fa-chart-bar"></i></div>
            <div class="nav-text">Grades</div>
        </a>
        
        <a href="teacher_schedule.php" class="nav-item">
            <div class="nav-icon"><i class="fas fa-calendar-alt"></i></div>
            <div class="nav-text">Schedule</div>
        </a>
        
        <a href="teacher_settings.php" class="nav-item">
            <div class="nav-icon"><i class="fas fa-cog"></i></div>
            <div class="nav-text">Settings</div>
        </a>
        
        <a href="../logout.php" class="nav-item">
            <div class="nav-icon"><i class="fas fa-sign-out-alt"></i></div>
            <div class="nav-text">Logout</div>
        </a>
    </div>

    <!-- Main Content -->
    <div class="main-content" id="mainContent">
        <!-- Header -->
        <div class="header">
            <button class="toggle-sidebar" id="toggleSidebar">
                <i class="fas fa-bars" id="toggleIcon"></i>
            </button>
            <div class="user-menu">
                <div class="notifications">
                    <i class="fas fa-bell"></i>
                    <span class="notification-badge">3</span>
                </div>
                <div class="user-profile">
                    <?php if (!empty($teacher['photo'])): ?>
                        <img src="<?php echo htmlspecialchars($teacher['photo']); ?>" alt="Profile" class="user-avatar"
                            onerror="this.src='../<?php echo htmlspecialchars($teacher['photo']); ?>'">
                    <?php else: ?>
                        <div class="user-avatar" style="background: var(--primary); display: flex; align-items: center; justify-content: center; color: white; font-weight: bold;">
                            <?php 
                            $initials = '';
                            if (!empty($teacher['first_name'])) {
                                $initials = substr($teacher['first_name'], 0, 1) . substr($teacher['last_name'] ?? '', 0, 1);
                            } else {
                                $initials = 'TC';
                            }
                            echo $initials;
                            ?>
                        </div>
                    <?php endif; ?>
                    <div class="user-name">
                        <?php 
                        if (!empty($teacher['first_name'])) {
                            echo htmlspecialchars($teacher['first_name'] . ' ' . ($teacher['last_name'] ?? ''));
                        } else {
                            echo 'Teacher';
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="container">
            <!-- Teacher Info Card -->
            <div class="teacher-info-card">
                <?php if (!empty($teacher['photo'])): ?>
                    <img src="<?php echo htmlspecialchars($teacher['photo']); ?>" alt="Teacher" class="teacher-info-avatar">
                <?php else: ?>
                    <div class="teacher-info-avatar" style="background: white; color: var(--primary); display: flex; align-items: center; justify-content: center; font-size: 2rem; font-weight: bold;">
                        <?php 
                        $initials = '';
                        if (!empty($teacher['first_name'])) {
                            $initials = substr($teacher['first_name'], 0, 1) . substr($teacher['last_name'] ?? '', 0, 1);
                        } else {
                            $initials = 'TC';
                        }
                        echo $initials;
                        ?>
                    </div>
                <?php endif; ?>
                <div class="teacher-info-details">
                    <h2><?php echo htmlspecialchars($teacher['first_name'] . ' ' . ($teacher['last_name'] ?? '')); ?></h2>
                    <p><i class="fas fa-book"></i> Subject: <?php echo htmlspecialchars($teacher['subject'] ?? 'Not specified'); ?></p>
                    <?php if (!empty($teacher_classes)): ?>
                        <p><i class="fas fa-user-graduate"></i> Assigned Students: <?php echo $student_counts['total']; ?></p>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Assigned Classes Section (Only show if teacher has classes assigned) -->
            <?php if (!empty($teacher_classes)): ?>
            <div class="assigned-classes">
                <h3><i class="fas fa-graduation-cap"></i> Your Assigned Classes (8-12)</h3>
                <div class="class-chips">
                    <?php foreach ($teacher_classes as $class): ?>
                    <div class="class-chip <?php echo strtolower($class['medium']); ?>">
                        <i class="fas fa-chalkboard-teacher"></i>
                        Class <?php echo htmlspecialchars($class['class']); ?>
                        <span class="medium-badge <?php echo $class['medium'] === 'English' ? 'medium-english' : 'medium-hindi'; ?>" style="font-size: 11px;">
                            <?php echo htmlspecialchars($class['medium']); ?>
                        </span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <div class="page-header">
                <h1><i class="fas fa-users"></i> My Students</h1>
                <p>View students from your assigned classes (Classes 8-12)</p>
            </div>
            
            <!-- Stats Cards -->
            <?php if (!empty($teacher_classes)): ?>
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-user-graduate"></i>
                    </div>
                    <div class="stat-value"><?php echo $student_counts['total']; ?></div>
                    <div class="stat-label">Total Students</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-language"></i>
                    </div>
                    <div class="stat-value"><?php echo $student_counts['english']; ?></div>
                    <div class="stat-label">English Medium</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-book"></i>
                    </div>
                    <div class="stat-value"><?php echo $student_counts['hindi']; ?></div>
                    <div class="stat-label">Hindi Medium</div>
                </div>
            </div>
            
            <!-- Filter Section -->
            <?php if (!empty($all_students)): ?>
            <div class="card">
                <h2 style="margin-bottom: 20px; color: var(--primary);"><i class="fas fa-filter"></i> Filter Students</h2>
                <form method="get" class="filter-form">
                    <div class="form-group">
                        <label for="class">Class</label>
                        <select name="class" id="class" class="form-control" onchange="this.form.submit()">
                            <option value="">All Classes</option>
                            <?php foreach ($classes as $class): ?>
                                <option value="<?php echo $class; ?>" <?php if($filter_class == $class) echo 'selected'; ?>>
                                    Class <?php echo $class; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="medium">Medium</label>
                        <select name="medium" id="medium" class="form-control" onchange="this.form.submit()">
                            <option value="">All Mediums</option>
                            <option value="English" <?php if($filter_medium == "English") echo 'selected'; ?>>English</option>
                            <option value="Hindi" <?php if($filter_medium == "Hindi") echo 'selected'; ?>>Hindi</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>&nbsp;</label>
                        <a href="teacher_students.php" class="btn" style="width: 100%;">
                            <i class="fas fa-redo"></i> Reset Filters
                        </a>
                    </div>
                </form>
            </div>
            
            <!-- Students Table -->
            <div class="card fade-in">
                <h2 style="margin-bottom: 20px; color: var(--primary);">
                    <i class="fas fa-list"></i> Student List 
                    <?php if ($filter_class || $filter_medium): ?>
                        <span style="font-size: 16px; color: var(--text-secondary);">
                            (Filtered: 
                            <?php echo $filter_class ? "Class $filter_class " : ""; ?>
                            <?php echo $filter_medium ? "$filter_medium Medium" : ""; ?>
                            )
                        </span>
                    <?php endif; ?>
                </h2>
                
                <table class="students-table">
                    <thead>
                        <tr>
                            <th>Photo</th>
                            <th>Student Name</th>
                            <th>Class</th>
                            <th>Medium</th>
                            <th>Parents</th>
                            <th>Contact</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($filtered_students as $student): 
                            // Determine which phone number to display (priority: whatsapp > parent_mobile > personal_mobile)
                            $phone = !empty($student['whatsapp']) ? $student['whatsapp'] : 
                                    (!empty($student['parent_mobile']) ? $student['parent_mobile'] : 
                                    $student['personal_mobile']);
                        ?>
                        <tr>
                            <td>
                                <?php 
                                $photoPath = "/ruchi_classes_form/student/" . $student['photo'];
                                if (!empty($student['photo']) && file_exists($_SERVER['DOCUMENT_ROOT'] . $photoPath)) {
                                    echo '<img src="'.$photoPath.'" alt="Student Photo" class="student-photo">';
                                } else {
                                    echo '<img src="https://ui-avatars.com/api/?name='.urlencode($student['first_name'].' '.$student['last_name']).'&size=60&background=random" alt="Student Photo" class="student-photo">';
                                }
                                ?>
                            </td>
                            <td>
                                <strong><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></strong>
                            </td>
                            <td>Class <?php echo htmlspecialchars($student['class']); ?></td>
                            <td>
                                <span class="medium-badge <?php echo $student['medium'] === 'English' ? 'medium-english' : 'medium-hindi'; ?>">
                                    <?php echo htmlspecialchars($student['medium']); ?>
                                </span>
                            </td>
                            <td>
                                <small>
                                    <div><i class="fas fa-male"></i> <?php echo htmlspecialchars($student['father_name'] ?? 'N/A'); ?></div>
                                    <div><i class="fas fa-female"></i> <?php echo htmlspecialchars($student['mother_name'] ?? 'N/A'); ?></div>
                                </small>
                            </td>
                            <td>
                                <?php if (!empty($phone)): ?>
                                    <div style="display: flex; flex-direction: column; gap: 5px;">
                                        <a href="tel:<?php echo htmlspecialchars($phone); ?>" class="btn btn-sm" style="background: var(--success); padding: 4px 8px;">
                                            <i class="fas fa-phone"></i> Call
                                        </a>
                                        <?php if (!empty($student['whatsapp'])): ?>
                                            <a href="https://wa.me/<?php echo htmlspecialchars($student['whatsapp']); ?>" target="_blank" class="btn btn-sm" style="background: #25D366; color: white; padding: 4px 8px;">
                                                <i class="fab fa-whatsapp"></i> WhatsApp
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                <?php else: ?>
                                    <span style="color: var(--text-muted);">N/A</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <button class="btn btn-sm btn-icon view-details-btn" onclick="viewStudentDetails(<?php echo htmlspecialchars(json_encode($student)); ?>)">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button class="btn btn-sm btn-icon" style="background: var(--warning);" onclick="viewAttendance(<?php echo $student['id']; ?>, '<?php echo $student['medium']; ?>')">
                                        <i class="fas fa-clipboard-check"></i>
                                    </button>
                                    <button class="btn btn-sm btn-icon" style="background: var(--info);" onclick="viewPerformance(<?php echo $student['id']; ?>)">
                                        <i class="fas fa-chart-line"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="card fade-in">
                <div class="no-data">
                    <i class="fas fa-user-slash"></i>
                    <h3>No Students Found</h3>
                    <p>
                        No students found in your assigned classes. 
                        <?php if ($filter_class || $filter_medium): ?>
                            Try different filters or 
                            <a href="teacher_students.php" style="color: var(--primary); text-decoration: none;">clear all filters</a>.
                        <?php else: ?>
                            This could mean:
                            <br>1. No students are enrolled in your assigned classes yet
                            <br>2. Students haven't been added to the system for your classes
                        <?php endif; ?>
                    </p>
                </div>
            </div>
            <?php endif; ?>
            
            <?php else: ?>
            <!-- No Classes Assigned Message -->
            <div class="card fade-in">
                <div class="no-data">
                    <i class="fas fa-exclamation-triangle"></i>
                    <h3>No Classes Assigned</h3>
                    <p>
                        You don't have any classes assigned to you yet. Please contact the administrator to get classes assigned.
                        <br><br>
                        <strong>Note:</strong> Teachers can only view students from classes they are assigned to teach (Classes 8-12).
                    </p>
                </div>
            </div>
            <?php endif; ?>
            
            <div class="footer">
                <p>&copy; <?php echo date('Y'); ?> Ruchi Classes. All rights reserved.</p>
            </div>
        </div>
    </div>

    <!-- Student Details Modal -->
    <div id="studentModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 10000; align-items: center; justify-content: center;">
        <div style="background: white; border-radius: 16px; padding: 2rem; max-width: 500px; width: 90%; max-height: 80vh; overflow-y: auto; position: relative;">
            <button onclick="closeStudentModal()" style="position: absolute; top: 15px; right: 15px; background: var(--danger); color: white; border: none; width: 30px; height: 30px; border-radius: 50%; cursor: pointer;">×</button>
            <div id="studentModalContent"></div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.getElementById('mainContent');
            const toggleBtn = document.getElementById('toggleSidebar');
            const toggleIcon = document.getElementById('toggleIcon');
            const sidebarOverlay = document.getElementById('sidebarOverlay');
            const logoImg = document.getElementById('logoImg');
            const logoText = document.getElementById('logoText');
            
            function handleSidebarToggle() {
                if (window.innerWidth < 1025) {
                    sidebar.classList.toggle('active');
                    sidebarOverlay.classList.toggle('active');
                    document.body.style.overflow = sidebar.classList.contains('active') ? 'hidden' : '';

                    logoImg.style.width = '85px';
                    logoImg.style.height = '85px';
                    logoText.style.display = 'block';

                } else {
                    sidebar.classList.toggle('collapsed');
                    mainContent.classList.toggle('expanded');

                    if (sidebar.classList.contains('collapsed')) {
                        toggleIcon.classList.replace('fa-bars', 'fa-ellipsis-h');
                        logoImg.style.width = '70px';
                        logoImg.style.height = '70px';
                        logoText.style.display = 'none';
                    } else {
                        toggleIcon.classList.replace('fa-ellipsis-h', 'fa-bars');
                        logoImg.style.width = '85px';
                        logoImg.style.height = '85px';
                        logoText.style.display = 'block';
                    }
                }
            }

            if (toggleBtn) {
                toggleBtn.addEventListener('click', handleSidebarToggle);
            }

            if (sidebarOverlay) {
                sidebarOverlay.addEventListener('click', function () {
                    sidebar.classList.remove('active');
                    sidebarOverlay.classList.remove('active');
                    document.body.style.overflow = '';
                });
            }

            if (window.innerWidth < 1025) {
                document.querySelectorAll('.nav-item, .dropdown-item').forEach(link => {
                    link.addEventListener('click', function () {
                        sidebar.classList.remove('active');
                        sidebarOverlay.classList.remove('active');
                        document.body.style.overflow = '';
                    });
                });
            }

            const examsDropdown = document.getElementById('examsDropdown');

            if (examsDropdown) {
                examsDropdown.addEventListener('click', function(e) {
                    e.stopPropagation();
                    this.classList.toggle('open');
                });
            }

            document.addEventListener('click', function() {
                document.querySelectorAll('.dropdown').forEach(drop => {
                    drop.classList.remove('open');
                });
            });

            let resizeTimer;
            window.addEventListener('resize', function() {
                clearTimeout(resizeTimer);
                resizeTimer = setTimeout(function() {

                    if (window.innerWidth >= 1025) {
                        sidebar.classList.remove('active');
                        sidebarOverlay.classList.remove('active');
                        document.body.style.overflow = '';

                        if (sidebar.classList.contains('collapsed')) {
                            toggleIcon.classList.replace('fa-bars', 'fa-ellipsis-h');
                            logoImg.style.width = '70px';
                            logoImg.style.height = '70px';
                            logoText.style.display = 'none';
                        } else {
                            toggleIcon.classList.replace('fa-ellipsis-h', 'fa-bars');
                            logoImg.style.width = '85px';
                            logoImg.style.height = '85px';
                            logoText.style.display = 'block';
                        }

                    } else {
                        sidebar.classList.remove('collapsed');
                        mainContent.classList.remove('expanded');
                        toggleIcon.classList.replace('fa-ellipsis-h', 'fa-bars');

                        logoImg.style.width = '85px';
                        logoImg.style.height = '85px';
                        logoText.style.display = 'block';
                    }

                }, 250);
            });

            if (window.innerWidth < 1025) {
                sidebar.classList.remove('collapsed');
                mainContent.classList.remove('expanded');
            } else {
                if (sidebar.classList.contains('collapsed')) {
                    logoImg.style.width = '70px';
                    logoImg.style.height = '70px';
                } else {
                    logoImg.style.width = '85px';
                    logoImg.style.height = '85px';
                }
            }
        });

        function viewStudentDetails(student) {
            const modal = document.getElementById('studentModal');
            const content = document.getElementById('studentModalContent');
            
            // Determine which phone number to display
            const phone = student.whatsapp || student.parent_mobile || student.personal_mobile || '';
            const whatsapp = student.whatsapp || '';
            
            let photoHtml = '';
            if (student.photo) {
                photoHtml = `<img src="/ruchi_classes_form/student/${student.photo}" alt="Student Photo" style="width: 120px; height: 120px; border-radius: 50%; object-fit: cover; border: 4px solid var(--primary); margin-bottom: 20px;">`;
            } else {
                photoHtml = `<div style="width: 120px; height: 120px; border-radius: 50%; background: var(--primary); color: white; display: flex; align-items: center; justify-content: center; font-size: 2rem; font-weight: bold; margin-bottom: 20px;">${student.first_name.charAt(0)}${student.last_name.charAt(0)}</div>`;
            }
            
            const mediumBadge = student.medium === 'English' ? 'medium-english' : 'medium-hindi';
            
            content.innerHTML = `
                <div style="text-align: center;">
                    ${photoHtml}
                    <h2 style="color: var(--text-primary); margin-bottom: 10px;">${student.first_name} ${student.last_name}</h2>
                    <div style="display: flex; justify-content: center; gap: 10px; margin-bottom: 20px;">
                        <span class="medium-badge ${mediumBadge}" style="font-size: 14px;">${student.medium} Medium</span>
                        <span style="background: var(--primary); color: white; padding: 4px 12px; border-radius: 20px; font-size: 14px;">Class ${student.class}</span>
                    </div>
                </div>
                
                <div style="background: var(--bg-secondary); border-radius: 12px; padding: 20px; margin-top: 20px;">
                    <h3 style="color: var(--text-primary); margin-bottom: 15px; border-bottom: 2px solid var(--border); padding-bottom: 10px;">Student Information</h3>
                    
                    <div style="display: grid; gap: 12px;">
                        <div style="display: flex; justify-content: space-between;">
                            <span style="color: var(--text-secondary); font-weight: 600;">Father's Name:</span>
                            <span>${student.father_name || 'N/A'}</span>
                        </div>
                        <div style="display: flex; justify-content: space-between;">
                            <span style="color: var(--text-secondary); font-weight: 600;">Mother's Name:</span>
                            <span>${student.mother_name || 'N/A'}</span>
                        </div>
                        <div style="display: flex; justify-content: space-between;">
                            <span style="color: var(--text-secondary); font-weight: 600;">Parent Mobile:</span>
                            <span>${student.parent_mobile || 'N/A'}</span>
                        </div>
                        <div style="display: flex; justify-content: space-between;">
                            <span style="color: var(--text-secondary); font-weight: 600;">Student Mobile:</span>
                            <span>${student.personal_mobile || 'N/A'}</span>
                        </div>
                        ${student.whatsapp ? `
                        <div style="display: flex; justify-content: space-between;">
                            <span style="color: var(--text-secondary); font-weight: 600;">WhatsApp:</span>
                            <span>${student.whatsapp}</span>
                        </div>
                        ` : ''}
                        <div style="display: flex; justify-content: space-between;">
                            <span style="color: var(--text-secondary); font-weight: 600;">Medium:</span>
                            <span>${student.medium}</span>
                        </div>
                        <div style="display: flex; justify-content: space-between;">
                            <span style="color: var(--text-secondary); font-weight: 600;">Class:</span>
                            <span>${student.class}</span>
                        </div>
                    </div>
                </div>
                
                <div style="margin-top: 25px; display: flex; justify-content: center; gap: 10px; flex-wrap: wrap;">
                    ${phone ? `<a href="tel:${phone}" class="btn btn-success" style="padding: 10px 20px;">
                        <i class="fas fa-phone"></i> Call Parents
                    </a>` : ''}
                    ${whatsapp ? `<a href="https://wa.me/${whatsapp}" target="_blank" class="btn" style="background: #25D366; color: white; padding: 10px 20px;">
                        <i class="fab fa-whatsapp"></i> WhatsApp
                    </a>` : ''}
                    <button class="btn" style="padding: 10px 20px;" onclick="viewAttendance(${student.id}, '${student.medium}')">
                        <i class="fas fa-clipboard-check"></i> View Attendance
                    </button>
                </div>
            `;
            
            modal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }

        function closeStudentModal() {
            document.getElementById('studentModal').style.display = 'none';
            document.body.style.overflow = '';
        }

        function viewAttendance(studentId, medium) {
            alert(`View attendance for Student ID: ${studentId} (${medium} Medium)\n\nThis feature will be implemented soon.`);
            // window.location.href = `student_attendance.php?id=${studentId}&medium=${medium}`;
        }

        function viewPerformance(studentId) {
            alert(`View performance for Student ID: ${studentId}\n\nThis feature will be implemented soon.`);
            // window.location.href = `student_performance.php?id=${studentId}`;
        }

        // Close modal when clicking outside
        document.getElementById('studentModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeStudentModal();
            }
        });
    </script>
</body>
</html>