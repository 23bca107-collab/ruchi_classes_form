<?php
session_start();
require '../db.php';

if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: admin_login.php");
    exit;
}

$admin_id = $_SESSION['admin_id'] ?? 1;

// Get admin info
$admin = [];
$stmt = $conn->prepare("SELECT name, email FROM admins WHERE id = ?");
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$result = $stmt->get_result();
$admin = $result->fetch_assoc() ?? ['name' => 'Admin', 'email' => ''];

// Handle form submissions
if (isset($_POST['assign'])) {
    $teacher_id = $_POST['teacher_id'];
    $class = $_POST['class'];
    $medium = $_POST['medium'];
    $subject = trim($_POST['subject']);

    // Validate subject based on class
    if ($class >= 8 && $class <= 10) {
        $allowed_subjects = ["Maths", "Science", "Hindi", "English", "Gujarati", "Social Science"];
        if (!in_array($subject, $allowed_subjects)) {
            $_SESSION['error_message'] = 'Invalid subject for this class!';
            header("Location: admin_assign_teacher.php");
            exit;
        }
    } elseif ($class >= 11 && $class <= 12) {
        $allowed_subjects = ["Accounts", "Statistics", "Economics", "Secretarial Practice", "English", "Business Studies"];
        if (!in_array($subject, $allowed_subjects)) {
            $_SESSION['error_message'] = 'Invalid subject for this class!';
            header("Location: admin_assign_teacher.php");
            exit;
        }
    }

    // Check if assignment already exists
    $checkStmt = $conn->prepare("SELECT id FROM teacher_assignments WHERE teacher_id=? AND class=? AND medium=? AND subject=?");
    $checkStmt->bind_param("isss", $teacher_id, $class, $medium, $subject);
    $checkStmt->execute();
    $exists = $checkStmt->get_result()->fetch_assoc();

    if (!$exists) {
        $stmt = $conn->prepare("
            INSERT INTO teacher_assignments (teacher_id, class, medium, subject, status, created_at)
            VALUES (?, ?, ?, ?, 'assigned', NOW())
        ");
        $stmt->bind_param("isss", $teacher_id, $class, $medium, $subject);
        
        if ($stmt->execute()) {
            $_SESSION['success_message'] = 'Teacher assigned successfully!';
        } else {
            $_SESSION['error_message'] = 'Failed to assign teacher. Please try again.';
        }
    } else {
        $_SESSION['error_message'] = 'This teacher is already assigned to this class/subject!';
    }
    
    header("Location: admin_assign_teacher.php");
    exit;
}

// Unassign teacher
if (isset($_GET['unassign'])) {
    $id = $_GET['unassign'];
    $stmt = $conn->prepare("UPDATE teacher_assignments SET status='unassigned', updated_at=NOW() WHERE id=?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        $_SESSION['success_message'] = 'Teacher unassigned successfully!';
    } else {
        $_SESSION['error_message'] = 'Failed to unassign teacher. Please try again.';
    }
    
    header("Location: admin_assign_teacher.php");
    exit;
}

// Reactivate assignment
if (isset($_GET['reactivate'])) {
    $id = $_GET['reactivate'];
    $stmt = $conn->prepare("UPDATE teacher_assignments SET status='assigned', updated_at=NOW() WHERE id=?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        $_SESSION['success_message'] = 'Assignment reactivated successfully!';
    } else {
        $_SESSION['error_message'] = 'Failed to reactivate assignment. Please try again.';
    }
    
    header("Location: admin_assign_teacher.php");
    exit;
}

// Delete assignment permanently
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM teacher_assignments WHERE id=?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        $_SESSION['success_message'] = 'Assignment deleted permanently!';
    } else {
        $_SESSION['error_message'] = 'Failed to delete assignment. Please try again.';
    }
    
    header("Location: admin_assign_teacher.php");
    exit;
}

// Fetch teachers with photo
$teachers = $conn->query("SELECT id, first_name, last_name, email, mobile, photo FROM teachers ORDER BY first_name");

// Fetch assignments with teacher details
$assignments = $conn->query("
    SELECT ta.*, 
           t.first_name, 
           t.last_name,
           t.email,
           t.mobile,
           t.photo,
           (SELECT COUNT(*) FROM student_english se WHERE se.class = ta.class AND se.medium = ta.medium) + 
           (SELECT COUNT(*) FROM student_hindi sh WHERE sh.class = ta.class AND sh.medium = ta.medium) as student_count
    FROM teacher_assignments ta
    JOIN teachers t ON ta.teacher_id = t.id
    ORDER BY ta.status DESC, ta.class ASC, ta.medium ASC, ta.subject ASC
");

// Get statistics
$stats = $conn->query("
    SELECT 
        (SELECT COUNT(*) FROM teacher_assignments WHERE status='assigned') as total_assigned,
        (SELECT COUNT(*) FROM teacher_assignments WHERE status='unassigned') as total_unassigned,
        (SELECT COUNT(*) FROM teachers) as total_teachers,
        (SELECT COUNT(DISTINCT class) FROM teacher_assignments WHERE status='assigned') as classes_covered
")->fetch_assoc();

// Define subject arrays
$subjects_8_10 = ["Maths", "Science", "Hindi", "English", "Gujarati", "Social Science"];
$subjects_11_12 = ["Accounts", "Statistics", "Economics", "Secretarial Practice", "English", "Business Studies"];

// Function to get correct photo path
function getTeacherPhotoPath($photo) {

    if (empty($photo)) {
        return '../assets/default_teacher.png';
    }

    // DB me already: teacher/uploads/filename
    if (strpos($photo, 'teacher/uploads/') === 0) {
        return '../' . $photo;
    }

    return '../assets/default_teacher.png';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Assign Teachers | Ruchi Classes</title>
    <!-- SweetAlert CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <!-- Font Awesome -->
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
            --purple: #8b5cf6;
            --pink: #ec4899;

            --text-primary: #1e293b;
            --text-secondary: #475569;
            --text-muted: #64748b;

            --bg-primary: #ffffff;
            --bg-secondary: #f8fafc;
            --bg-card: #ffffff;
            --bg-hover: #f1f5f9;

            --border: #e2e8f0;
            --shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
            --shadow-lg: 0 20px 40px -10px rgba(0, 0, 0, 0.15);

            --gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --gradient-primary: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            --gradient-accent: linear-gradient(135deg, var(--accent) 0%, #ea580c 100%);
            --gradient-success: linear-gradient(135deg, var(--success) 0%, #059669 100%);
            --gradient-danger: linear-gradient(135deg, var(--danger) 0%, #dc2626 100%);

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

        .user-menu {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .admin-profile {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 10px 18px;
            border-radius: 14px;
            background: var(--bg-card);
            border: 1px solid var(--border);
        }

        .admin-avatar {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background: var(--gradient-primary);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 20px;
            font-weight: 600;
            box-shadow: 0 4px 15px rgba(37, 99, 235, 0.2);
        }

        .admin-info h3 {
            color: var(--text-primary);
            font-size: 16px;
            margin-bottom: 4px;
        }

        .admin-info p {
            color: var(--text-secondary);
            font-size: 13px;
        }

        /* ---------------- STATS CARDS ----------------- */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            border-radius: 16px;
            padding: 25px;
            box-shadow: var(--shadow);
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: white;
        }

        .stat-icon.teachers { background: var(--gradient-primary); }
        .stat-icon.assigned { background: var(--gradient-success); }
        .stat-icon.unassigned { background: var(--gradient-danger); }
        .stat-icon.classes { background: var(--gradient-accent); }

        .stat-info h3 {
            font-size: 28px;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 5px;
        }

        .stat-info p {
            color: var(--text-secondary);
            font-size: 14px;
        }

        /* ---------------- FORM STYLES ----------------- */
        .form-card {
            background: white;
            border-radius: 16px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: var(--shadow);
            animation: fadeInUp 0.8s ease;
        }

        .form-card h2 {
            color: var(--text-primary);
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 22px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 10px;
            font-weight: 600;
            color: var(--text-primary);
            font-size: 15px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .form-control {
            width: 100%;
            padding: 15px 18px;
            border: 2px solid var(--border);
            border-radius: 12px;
            font-size: 16px;
            transition: all 0.3s ease;
            background: var(--bg-secondary);
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
            background: white;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 16px 32px;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
        }

        .btn-primary {
            background: var(--gradient-primary);
            color: white;
            box-shadow: 0 4px 15px rgba(37, 99, 235, 0.2);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(37, 99, 235, 0.3);
        }

        .btn-danger {
            background: var(--gradient-danger);
            color: white;
            box-shadow: 0 4px 15px rgba(239, 68, 68, 0.2);
        }

        .btn-danger:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(239, 68, 68, 0.3);
        }

        .btn-success {
            background: var(--gradient-success);
            color: white;
            box-shadow: 0 4px 15px rgba(16, 185, 129, 0.2);
        }

        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(16, 185, 129, 0.3);
        }

        .btn-warning {
            background: var(--gradient-accent);
            color: white;
            box-shadow: 0 4px 15px rgba(245, 158, 11, 0.2);
        }

        .btn-warning:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(245, 158, 11, 0.3);
        }

        .btn-sm {
            padding: 8px 16px;
            font-size: 14px;
            border-radius: 8px;
        }

        .action-buttons {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        /* ---------------- TABLE STYLES ----------------- */
        .table-card {
            background: white;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: var(--shadow);
            animation: fadeInUp 0.8s ease;
        }

        .table-header {
            padding: 25px 30px;
            background: var(--bg-secondary);
            border-bottom: 1px solid var(--border);
        }

        .table-header h2 {
            color: var(--text-primary);
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 22px;
        }

        .data-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            font-size: 15px;
        }

        .data-table thead {
            background: var(--gradient-primary);
        }

        .data-table th {
            padding: 20px 25px;
            text-align: left;
            color: white;
            font-weight: 600;
            font-size: 15px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .data-table td {
            padding: 20px 25px;
            border-bottom: 1px solid var(--border);
            background: white;
            transition: all 0.3s ease;
        }

        .data-table tr:hover td {
            background: var(--bg-hover);
            transform: translateX(5px);
        }

        .data-table tr:last-child td {
            border-bottom: none;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
        }

        .status-assigned {
            background: #d1fae5;
            color: #065f46;
        }

        .status-unassigned {
            background: #fee2e2;
            color: #991b1b;
        }

        .status-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
        }

        .status-assigned .status-dot { background: #10b981; }
        .status-unassigned .status-dot { background: #ef4444; }

        .teacher-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .teacher-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid var(--primary);
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.15);
        }

        .teacher-details h4 {
            color: var(--text-primary);
            font-size: 15px;
            margin-bottom: 3px;
        }

        .teacher-details p {
            color: var(--text-secondary);
            font-size: 12px;
        }

        /* ---------------- ANIMATIONS ----------------- */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes fadeInDown {
            from {
                opacity: 0;
                transform: translateY(-30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* ---------------- RESPONSIVE ----------------- */
        @media (max-width: 1024px) {
            .sidebar {
                transform: translateX(-100%);
                width: 320px;
            }
            
            .sidebar.active {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
                padding: 1.5rem;
            }
            
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 768px) {
            .data-table {
                display: block;
                overflow-x: auto;
            }
            
            .data-table th,
            .data-table td {
                padding: 15px 20px;
                font-size: 14px;
                white-space: nowrap;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .action-buttons {
                flex-direction: column;
                gap: 8px;
            }
            
            .teacher-info {
                flex-direction: column;
                align-items: flex-start;
                gap: 8px;
            }
            
            .teacher-avatar {
                width: 40px;
                height: 40px;
            }
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: var(--text-secondary);
        }

        .empty-state i {
            font-size: 64px;
            margin-bottom: 20px;
            color: var(--border);
            opacity: 0.5;
        }

        .empty-state h3 {
            margin-bottom: 10px;
            color: var(--text-primary);
        }

        .footer {
            text-align: center;
            margin-top: 40px;
            padding: 20px;
            color: var(--text-secondary);
            font-size: 14px;
            border-top: 1px solid var(--border);
        }

        .page-header {
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

        .alert {
            padding: 15px 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 12px;
            animation: fadeInUp 0.6s ease;
        }

        .alert-success {
            background: #d1fae5;
            border: 2px solid #10b981;
            color: #065f46;
        }

        .alert-error {
            background: #fee2e2;
            border: 2px solid #ef4444;
            color: #991b1b;
        }

        .alert i {
            font-size: 18px;
        }
        
        /* Tooltip styles */
        [data-tooltip] {
            position: relative;
        }
        
        [data-tooltip]:hover::before {
            content: attr(data-tooltip);
            position: absolute;
            bottom: 100%;
            left: 50%;
            transform: translateX(-50%);
            background: var(--text-primary);
            color: white;
            padding: 6px 10px;
            border-radius: 6px;
            font-size: 12px;
            white-space: nowrap;
            z-index: 1000;
            margin-bottom: 5px;
        }
        
        [data-tooltip]:hover::after {
            content: '';
            position: absolute;
            bottom: 100%;
            left: 50%;
            transform: translateX(-50%);
            border-width: 5px;
            border-style: solid;
            border-color: var(--text-primary) transparent transparent transparent;
            margin-bottom: 0;
        }
    </style>
</head>
<body>
    <!-- Sidebar Navigation -->
    <div class="sidebar" id="sidebar">
        <div class="logo-container">
            <img src="../assets/Ruchi logo.jpg" alt="Ruchi Classes Logo" class="logo-img" id="logoImg">
            <div class="logo-text" id="logoText">
                Ruchi <br>Classes
                <span>Admin Panel</span>
            </div>
        </div>
        <a href="admin_dashboard.php" class="nav-item">
            <div class="nav-icon"><i class="fas fa-home"></i></div>
            <div class="nav-text">Dashboard</div>
        </a>
        <a href="admin_teachers.php" class="nav-item">
            <div class="nav-icon"><i class="fas fa-chalkboard-teacher"></i></div>
            <div class="nav-text">Teachers</div>
        </a>
        <a href="admin_assign_teacher.php" class="nav-item active">
            <div class="nav-icon"><i class="fas fa-tasks"></i></div>
            <div class="nav-text">Assign Teachers</div>
        </a>
        <a href="admin_students.php" class="nav-item">
            <div class="nav-icon"><i class="fas fa-users"></i></div>
            <div class="nav-text">Students</div>
        </a>
        <a href="admin_exams.php" class="nav-item">
            <div class="nav-icon"><i class="fas fa-file-alt"></i></div>
            <div class="nav-text">Exams</div>
        </a>
        <a href="admin_schedule.php" class="nav-item">
            <div class="nav-icon"><i class="fas fa-calendar-alt"></i></div>
            <div class="nav-text">Schedule</div>
        </a>
        <a href="admin_reports.php" class="nav-item">
            <div class="nav-icon"><i class="fas fa-chart-bar"></i></div>
            <div class="nav-text">Reports</div>
        </a>
        <a href="admin_settings.php" class="nav-item">
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
                <div class="admin-profile">
                    <div class="admin-avatar">
                        <?php echo strtoupper(substr($admin['name'], 0, 1)); ?>
                    </div>
                    <div class="admin-info">
                        <h3><?php echo htmlspecialchars($admin['name']); ?></h3>
                        <p><?php echo htmlspecialchars($admin['email']); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <div class="container">
            <!-- Page Header -->
            <div class="page-header">
                <h1><i class="fas fa-tasks"></i> Assign Teachers to Classes</h1>
                <p>Manage teacher assignments to classes and subjects</p>
            </div>

            <!-- Flash Messages -->
            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?>
                </div>
            <?php endif; ?>

            <!-- Statistics Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon teachers">
                        <i class="fas fa-chalkboard-teacher"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $stats['total_teachers'] ?? 0; ?></h3>
                        <p>Total Teachers</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon assigned">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $stats['total_assigned'] ?? 0; ?></h3>
                        <p>Active Assignments</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon unassigned">
                        <i class="fas fa-times-circle"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $stats['total_unassigned'] ?? 0; ?></h3>
                        <p>Inactive Assignments</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon classes">
                        <i class="fas fa-graduation-cap"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $stats['classes_covered'] ?? 0; ?></h3>
                        <p>Classes Covered</p>
                    </div>
                </div>
            </div>

            <!-- Assignment Form -->
            <div class="form-card">
                <h2><i class="fas fa-user-plus"></i> Assign New Teacher</h2>
                <form method="post" id="assignForm">
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="teacher_id"><i class="fas fa-user-tie"></i> Select Teacher</label>
                            <select name="teacher_id" id="teacher_id" class="form-control" required>
                                <option value="">-- Choose Teacher --</option>
                                <?php 
                                if ($teachers && $teachers->num_rows > 0) {
                                    $teachers->data_seek(0); // Reset pointer
                                    while($t = $teachers->fetch_assoc()): 
                                ?>
                                    <option value="<?php echo $t['id']; ?>">
                                        <?php echo htmlspecialchars($t['first_name'] . ' ' . $t['last_name']); ?> 
                                        (<?php echo htmlspecialchars($t['email']); ?>)
                                    </option>
                                <?php 
                                    endwhile;
                                } else {
                                    echo '<option value="">No teachers available</option>';
                                }
                                ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="class"><i class="fas fa-graduation-cap"></i> Class</label>
                            <select name="class" id="class" class="form-control" required onchange="updateSubjects()">
                                <option value="">-- Select Class --</option>
                                <?php for($i = 8; $i <= 12; $i++): ?>
                                    <option value="<?php echo $i; ?>">Class <?php echo $i; ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="medium"><i class="fas fa-language"></i> Medium</label>
                            <select name="medium" id="medium" class="form-control" required onchange="updateSubjects()">
                                <option value="">-- Select Medium --</option>
                                <option value="English">English Medium</option>
                                <option value="Hindi">Hindi Medium</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="subject"><i class="fas fa-book"></i> Subject</label>
                            <select name="subject" id="subject" class="form-control" required>
                                <option value="">-- Select Subject --</option>
                                <!-- Subjects will be populated by JavaScript -->
                            </select>
                        </div>
                    </div>
                    
                    <button type="submit" name="assign" class="btn btn-primary">
                        <i class="fas fa-check"></i> Assign Teacher
                    </button>
                </form>
            </div>

            <!-- Assignments Table -->
            <div class="table-card">
                <div class="table-header">
                    <h2><i class="fas fa-list"></i> All Assignments</h2>
                </div>
                
                <?php if ($assignments && $assignments->num_rows > 0): ?>
                <div style="overflow-x: auto;">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Teacher</th>
                                <th>Class & Medium</th>
                                <th>Subject</th>
                                <th>Students</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $assignments->data_seek(0); // Reset pointer
                            while($assignment = $assignments->fetch_assoc()): 
                            ?>
                            <tr>
                                <td>
                                    <div class="teacher-info">
                                        <?php 
                                        $photoPath = getTeacherPhotoPath($assignment['photo']);
                                        ?>
                                        <img src="<?php echo htmlspecialchars($photoPath); ?>" 
                                             alt="Teacher Photo"
                                             class="teacher-avatar"
                                             onerror="this.onerror=null; this.src='../assets/default_teacher.png';">
                                        <div class="teacher-details">
                                            <h4><?php echo htmlspecialchars($assignment['first_name'] . ' ' . $assignment['last_name']); ?></h4>
                                            <p><?php echo htmlspecialchars($assignment['email']); ?></p>
                                            <p style="font-size: 11px;"><?php echo htmlspecialchars($assignment['mobile'] ?? 'N/A'); ?></p>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div style="font-weight: 600; font-size: 16px;">
                                        Class <?php echo htmlspecialchars($assignment['class']); ?>
                                    </div>
                                    <div style="color: var(--text-secondary); font-size: 14px;">
                                        <?php echo htmlspecialchars($assignment['medium']); ?> Medium
                                    </div>
                                </td>
                                <td style="font-weight: 500; color: var(--text-primary);">
                                    <?php echo htmlspecialchars($assignment['subject']); ?>
                                </td>
                                <td style="text-align: center; font-weight: 600;">
                                    <div style="background: var(--bg-hover); padding: 8px 12px; border-radius: 8px; display: inline-block;">
                                        <?php echo $assignment['student_count'] ?? 0; ?>
                                        <div style="font-size: 12px; color: var(--text-muted); font-weight: normal;">Students</div>
                                    </div>
                                </td>
                                <td>
                                    <span class="status-badge status-<?php echo $assignment['status']; ?>">
                                        <span class="status-dot"></span>
                                        <?php echo ucfirst($assignment['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <?php if($assignment['status'] == 'assigned'): ?>
                                            <a href="?unassign=<?php echo $assignment['id']; ?>" 
                                               class="btn btn-danger btn-sm"
                                               onclick="return confirmUnassign()"
                                               data-tooltip="Unassign teacher from this class">
                                                <i class="fas fa-times"></i> Unassign
                                            </a>
                                        <?php else: ?>
                                            <a href="?reactivate=<?php echo $assignment['id']; ?>" 
                                               class="btn btn-success btn-sm"
                                               onclick="return confirmReactivate()"
                                               data-tooltip="Reactivate this assignment">
                                                <i class="fas fa-redo"></i> Reactivate
                                            </a>
                                        <?php endif; ?>
                                        <a href="?delete=<?php echo $assignment['id']; ?>" 
                                           class="btn btn-warning btn-sm"
                                           onclick="return confirmDelete()"
                                           data-tooltip="Delete assignment permanently">
                                            <i class="fas fa-trash"></i> Delete
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-clipboard-list"></i>
                    <h3>No Assignments Found</h3>
                    <p>Start by assigning a teacher to a class and subject.</p>
                </div>
                <?php endif; ?>
            </div>

            <div class="footer">
                <p>&copy; <?php echo date('Y'); ?> Ruchi Classes. All rights reserved.</p>
                <p style="margin-top: 5px; font-size: 12px; opacity: 0.7;">
                    <i class="fas fa-shield-alt"></i> Secure Admin Panel
                </p>
            </div>
        </div>
    </div>

    <!-- SweetAlert JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
    // Subject arrays
    const subjects_8_10 = ["Maths", "Science", "Hindi", "English", "Gujarati", "Social Science"];
    const subjects_11_12 = ["Accounts", "Statistics", "Economics", "Secretarial Practice", "English", "Business Studies"];

    // Update subjects based on class selection
    function updateSubjects() {
        const classSelect = document.getElementById('class');
        const subjectSelect = document.getElementById('subject');
        const classValue = parseInt(classSelect.value);
        
        if (!classValue) {
            subjectSelect.innerHTML = '<option value="">-- Select Subject --</option>';
            return;
        }
        
        let subjects = [];
        if (classValue >= 8 && classValue <= 10) {
            subjects = subjects_8_10;
        } else if (classValue >= 11 && classValue <= 12) {
            subjects = subjects_11_12;
        }
        
        let options = '<option value="">-- Select Subject --</option>';
        subjects.forEach(subject => {
            options += `<option value="${subject}">${subject}</option>`;
        });
        
        subjectSelect.innerHTML = options;
    }

    // Initialize subjects on page load if class is selected
    document.addEventListener('DOMContentLoaded', function() {
        const classSelect = document.getElementById('class');
        if (classSelect.value) {
            updateSubjects();
        }
    });

    // Confirmation dialogs
    function confirmUnassign() {
        return Swal.fire({
            title: 'Unassign Teacher?',
            text: "This will mark the assignment as inactive. The teacher won't be able to take attendance for this class.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Yes, unassign!',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            return result.isConfirmed;
        });
    }

    function confirmReactivate() {
        return Swal.fire({
            title: 'Reactivate Assignment?',
            text: "This will mark the assignment as active. The teacher will be able to take attendance again.",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#10b981',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Yes, reactivate!',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            return result.isConfirmed;
        });
    }

    function confirmDelete() {
        return Swal.fire({
            title: 'Delete Assignment Permanently?',
            text: "This action cannot be undone. The assignment will be permanently removed from the system.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#f59e0b',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Yes, delete!',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            return result.isConfirmed;
        });
    }

    // Form validation
    document.getElementById('assignForm').addEventListener('submit', function(e) {
        const teacherId = document.getElementById('teacher_id').value;
        const classVal = document.getElementById('class').value;
        const medium = document.getElementById('medium').value;
        const subject = document.getElementById('subject').value;
        
        if (!teacherId || !classVal || !medium || !subject) {
            e.preventDefault();
            Swal.fire({
                icon: 'warning',
                title: 'Incomplete Form',
                text: 'Please fill in all fields before submitting.',
                confirmButtonColor: '#2563eb'
            });
            return false;
        }
        return true;
    });

    // Sidebar toggle functionality
    const sidebar = document.getElementById('sidebar');
    const mainContent = document.getElementById('mainContent');
    const toggleBtn = document.getElementById('toggleSidebar');
    const toggleIcon = document.getElementById('toggleIcon');
    const logoImg = document.getElementById('logoImg');
    const logoText = document.getElementById('logoText');
    
    function toggleSidebar() {
        if (window.innerWidth < 1025) {
            sidebar.classList.toggle('active');
        } else {
            sidebar.classList.toggle('collapsed');
            mainContent.classList.toggle('expanded');
            
            if (sidebar.classList.contains('collapsed')) {
                toggleIcon.classList.replace('fa-bars', 'fa-ellipsis-h');
                logoImg.style.width = '70px';
                logoImg.style.height = '70px';
            } else {
                toggleIcon.classList.replace('fa-ellipsis-h', 'fa-bars');
                logoImg.style.width = '85px';
                logoImg.style.height = '85px';
            }
        }
    }
    
    if (toggleBtn) {
        toggleBtn.addEventListener('click', toggleSidebar);
    }

    // Responsive adjustments
    window.addEventListener('resize', function() {
        if (window.innerWidth >= 1025) {
            sidebar.classList.remove('active');
        } else {
            sidebar.classList.remove('collapsed');
            mainContent.classList.remove('expanded');
            toggleIcon.classList.replace('fa-ellipsis-h', 'fa-bars');
        }
    });

    // Fix for teacher photo error handling
    document.addEventListener('DOMContentLoaded', function() {
        // Set up error handling for all teacher avatar images
        document.querySelectorAll('.teacher-avatar').forEach(img => {
            img.onerror = function() {
                this.src = '../assets/default_teacher.png';
            };
        });
        
        // Initialize tooltips
        document.querySelectorAll('[data-tooltip]').forEach(element => {
            element.addEventListener('mouseenter', function(e) {
                // Tooltip is handled by CSS
            });
        });
    });
    </script>
</body>
</html>