<?php
session_start();
require '../db.php';

// Check login
if (!isset($_SESSION['teacher_logged_in'])) {
    header("Location: teacher_login.php");
    exit();
}

// Get teacher info for sidebar
$teacher_id = $_SESSION['teacher_id'] ?? null;
$teacher = [];
if ($teacher_id) {
    $stmt = $conn->prepare("SELECT first_name, last_name, photo FROM teachers WHERE id = ?");
    $stmt->bind_param("i", $teacher_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $teacher = $result->fetch_assoc() ?? [];
}

// Handle Complaint Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_type = "teacher";
    $user_id   = $_SESSION['teacher_id'];
    $complaint = trim($_POST['complaint']);

    if (!empty($complaint)) {
        $stmt = $conn->prepare("INSERT INTO complaints (user_type, user_id, complaint) VALUES (?, ?, ?)");
        $stmt->bind_param("sis", $user_type, $user_id, $complaint);
        if ($stmt->execute()) {
            $msg = "Complaint submitted successfully!";
            $msg_type = "success";
        } else {
            $msg = "Error: " . $conn->error;
            $msg_type = "error";
        }
    } else {
        $msg = "Complaint cannot be empty!";
        $msg_type = "error";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submit Complaint | Ruchi Classes</title>
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
            height = 0;
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

        /* ---------------- DROPDOWN STYLES ---------------- */
        .dropdown {
            position: relative;
        }

        .dropdown-icon {
            margin-left: auto;
            transition: all 0.3s ease;
            font-size: 14px;
        }

        .dropdown.open .dropdown-icon {
            transform: rotate(180deg);
        }

        .dropdown-menu {
            display: none;
            flex-direction: column;
            margin-left: 50px;
            background: var(--bg-card);
            border-radius: 12px;
            border: 1px solid var(--border);
            overflow: hidden;
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
            animation: slideDown 0.3s ease;
        }

        .dropdown-menu.show {
            display: flex;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
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

        /* ---------------- COMPLAINT FORM STYLES ---------------- */
        .complaint-container {
            max-width: 800px;
            margin: 0 auto;
            animation: fadeIn 0.8s ease-out;
        }

        .complaint-card {
            background: white;
            border-radius: 16px;
            box-shadow: var(--shadow);
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .complaint-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.15);
        }

        .card-header {
            background: var(--gradient-primary);
            color: white;
            padding: 25px 30px;
            text-align: center;
        }

        .card-header h2 {
            font-weight: 700;
            font-size: 28px;
            margin-bottom: 10px;
        }

        .card-header p {
            font-size: 16px;
            opacity: 0.9;
        }

        .card-body {
            padding: 35px;
        }

        .alert {
            padding: 16px 20px;
            border-radius: 12px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            animation: slideDown 0.5s ease-out;
            font-weight: 500;
        }

        .alert-success {
            background-color: rgba(16, 185, 129, 0.15);
            color: #065f46;
            border-left: 4px solid var(--success);
        }

        .alert-error {
            background-color: rgba(239, 68, 68, 0.15);
            color: #991b1b;
            border-left: 4px solid var(--danger);
        }

        .alert-icon {
            margin-right: 12px;
            font-size: 22px;
        }

        .form-group {
            margin-bottom: 25px;
        }

        label {
            display: block;
            margin-bottom: 10px;
            font-weight: 600;
            color: var(--text-primary);
            font-size: 16px;
        }

        textarea {
            width: 100%;
            padding: 18px;
            border: 1px solid var(--border);
            border-radius: 12px;
            resize: vertical;
            min-height: 180px;
            font-size: 16px;
            transition: all 0.3s ease;
            font-family: 'Inter', sans-serif;
            background: var(--bg-secondary);
        }

        textarea:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.2);
            background: white;
        }

        .char-count {
            text-align: right;
            font-size: 14px;
            color: var(--text-muted);
            margin-top: 8px;
            font-weight: 500;
        }

        .char-count.warning {
            color: var(--warning);
        }

        .char-count.danger {
            color: var(--danger);
        }

        .btn {
            display: inline-block;
            background: var(--gradient-primary);
            color: white;
            border: none;
            padding: 16px 32px;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            text-align: center;
            width: 100%;
            box-shadow: 0 4px 15px rgba(37, 99, 235, 0.2);
        }

        .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(37, 99, 235, 0.3);
        }

        .btn:active {
            transform: translateY(-1px);
        }

        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        .footer-links {
            display: flex;
            justify-content: space-between;
            margin-top: 30px;
            padding-top: 25px;
            border-top: 1px solid var(--border);
        }

        .footer-links a {
            color: var(--primary);
            text-decoration: none;
            font-size: 15px;
            font-weight: 500;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .footer-links a:hover {
            color: var(--primary-dark);
            text-decoration: none;
            transform: translateX(5px);
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-15px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes slideInLeft {
            from { transform: translateX(-100%); }
            to { transform: translateX(0); }
        }

        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }

        .pulse {
            animation: pulse 2s infinite;
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
            
            .card-body {
                padding: 25px;
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
            
            .card-header {
                padding: 20px;
            }
            
            .card-header h2 {
                font-size: 24px;
            }
            
            .card-body {
                padding: 20px;
            }
            
            .footer-links {
                flex-direction: column;
                gap: 15px;
            }
            
            .footer-links a {
                justify-content: center;
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
            
            .card-header {
                padding: 15px;
            }
            
            .card-header h2 {
                font-size: 20px;
            }
            
            textarea {
                padding: 15px;
                min-height: 150px;
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

        <a href="teacher_complain.php" class="nav-item active">
            <div class="nav-icon"><i class="fas fa-comment-dots"></i></div>
            <div class="nav-text">Complaint</div>
        </a>

        <a href="teacher_students.php" class="nav-item">
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

        <div class="complaint-container">
            <div class="complaint-card">
                <div class="card-header">
                    <h2>Submit a Complaint</h2>
                    <p>We're here to help. Please describe your issue in detail.</p>
                </div>
                <div class="card-body">
                    <?php if (isset($msg)): ?>
                        <div class="alert alert-<?php echo $msg_type === 'success' ? 'success' : 'error'; ?>">
                            <span class="alert-icon"><?php echo $msg_type === 'success' ? '✓' : '⚠'; ?></span>
                            <?php echo $msg; ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" id="complaintForm">
                        <div class="form-group">
                            <label for="complaint">Complaint Details</label>
                            <textarea 
                                name="complaint" 
                                id="complaint" 
                                placeholder="Please provide a detailed description of your complaint or issue..."
                                required
                                maxlength="1000"
                            ><?php echo isset($_POST['complaint']) ? htmlspecialchars($_POST['complaint']) : ''; ?></textarea>
                            <div class="char-count" id="charCountContainer">
                                <span id="charCount">0</span>/1000 characters
                            </div>
                        </div>
                        
                        <button type="submit" class="btn pulse" id="submitBtn">Submit Complaint</button>
                    </form>
                    
                    <div class="footer-links">
                        <a href="teacher_dashboard.php"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
                        <a href="complaint_history.php">View Complaint History <i class="fas fa-history"></i></a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Character counter for textarea
        const complaintTextarea = document.getElementById('complaint');
        const charCount = document.getElementById('charCount');
        const charCountContainer = document.getElementById('charCountContainer');
        
        complaintTextarea.addEventListener('input', function() {
            const currentLength = this.value.length;
            charCount.textContent = currentLength;
            
            // Update character count styling
            charCountContainer.classList.remove('warning', 'danger');
            if (currentLength > 900) {
                charCountContainer.classList.add('danger');
            } else if (currentLength > 700) {
                charCountContainer.classList.add('warning');
            }
        });
        
        // Initialize character count on page load
        charCount.textContent = complaintTextarea.value.length;
        if (complaintTextarea.value.length > 900) {
            charCountContainer.classList.add('danger');
        } else if (complaintTextarea.value.length > 700) {
            charCountContainer.classList.add('warning');
        }
        
        // Form submission animation
        const form = document.getElementById('complaintForm');
        const submitBtn = document.getElementById('submitBtn');
        
        form.addEventListener('submit', function(e) {
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting...';
            submitBtn.disabled = true;
        });
        
        // Auto-hide success message after 5 seconds
        const successAlert = document.querySelector('.alert-success');
        if (successAlert) {
            setTimeout(() => {
                successAlert.style.opacity = '0';
                successAlert.style.transform = 'translateY(-10px)';
                setTimeout(() => {
                    successAlert.style.display = 'none';
                }, 500);
            }, 5000);
        }

        // Sidebar toggle functionality
        const sidebar = document.getElementById('sidebar');
        const mainContent = document.getElementById('mainContent');
        const toggleSidebar = document.getElementById('toggleSidebar');
        const toggleIcon = document.getElementById('toggleIcon');
        const sidebarOverlay = document.getElementById('sidebarOverlay');
        const logoImg = document.getElementById('logoImg');
        const logoText = document.getElementById('logoText');

        // Toggle sidebar function
        function toggleSidebar() {
            if (window.innerWidth < 1025) {
                // Mobile/tablet view
                sidebar.classList.toggle('active');
                sidebarOverlay.classList.toggle('active');
                document.body.style.overflow = sidebar.classList.contains('active') ? 'hidden' : '';
                
                // Ensure logo is properly sized for mobile
                if (sidebar.classList.contains('active')) {
                    logoImg.style.width = '85px';
                    logoImg.style.height = '85px';
                    logoText.style.display = 'block';
                }
            } else {
                // Desktop view - toggle collapsed state
                sidebar.classList.toggle('collapsed');
                mainContent.classList.toggle('expanded');
                
                // Update toggle icon
                if (sidebar.classList.contains('collapsed')) {
                    toggleIcon.classList.replace('fa-bars', 'fa-ellipsis-h');
                    // Adjust logo size for collapsed state
                    logoImg.style.width = '70px';
                    logoImg.style.height = '70px';
                    logoImg.style.margin = '0 auto';
                    logoText.style.opacity = '0';
                    logoText.style.width = '0';
                    logoText.style.height = '0';
                    logoText.style.overflow = 'hidden';
                    logoText.style.margin = '0';
                    logoText.style.padding = '0';
                    logoText.style.fontSize = '0';
                } else {
                    toggleIcon.classList.replace('fa-ellipsis-h', 'fa-bars');
                    // Restore logo size for expanded state
                    logoImg.style.width = '85px';
                    logoImg.style.height = '85px';
                    logoImg.style.margin = '0';
                    logoText.style.opacity = '1';
                    logoText.style.width = 'auto';
                    logoText.style.height = 'auto';
                    logoText.style.overflow = 'visible';
                    logoText.style.margin = '';
                    logoText.style.padding = '';
                    logoText.style.fontSize = '26px';
                }
            }
        }
        
        // Toggle sidebar button
        toggleSidebar.addEventListener('click', toggleSidebar);
        
        // Close sidebar when clicking on overlay
        sidebarOverlay.addEventListener('click', function() {
            sidebar.classList.remove('active');
            sidebarOverlay.classList.remove('active');
            document.body.style.overflow = '';
        });
        
        // Close sidebar when clicking on mobile links
        if (window.innerWidth < 1025) {
            document.querySelectorAll('.nav-item, .dropdown-item').forEach(link => {
                link.addEventListener('click', function() {
                    sidebar.classList.remove('active');
                    sidebarOverlay.classList.remove('active');
                    document.body.style.overflow = '';
                });
            });
        }
        
        // Exams dropdown functionality
        const examsDropdown = document.getElementById('examsDropdown');
        const examsMenu = document.getElementById('examsMenu');
        
        if (examsDropdown && examsMenu) {
            examsDropdown.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                // Toggle open class on dropdown
                this.classList.toggle('open');
                
                // Toggle show class on dropdown menu
                examsMenu.classList.toggle('show');
                
                // Rotate dropdown icon
                const dropdownIcon = this.querySelector('.dropdown-icon');
                if (dropdownIcon) {
                    if (this.classList.contains('open')) {
                        dropdownIcon.classList.remove('fa-caret-down');
                        dropdownIcon.classList.add('fa-caret-up');
                    } else {
                        dropdownIcon.classList.remove('fa-caret-up');
                        dropdownIcon.classList.add('fa-caret-down');
                    }
                }
            });
        }
        
        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.dropdown') && !e.target.closest('.dropdown-menu')) {
                // Close all dropdowns
                document.querySelectorAll('.dropdown').forEach(dropdown => {
                    dropdown.classList.remove('open');
                    
                    // Find and hide the dropdown menu
                    const dropdownId = dropdown.id;
                    let menu;
                    if (dropdownId === 'examsDropdown') {
                        menu = document.getElementById('examsMenu');
                    }
                    
                    if (menu) {
                        menu.classList.remove('show');
                    }
                    
                    // Reset dropdown icon
                    const dropdownIcon = dropdown.querySelector('.dropdown-icon');
                    if (dropdownIcon) {
                        dropdownIcon.classList.remove('fa-caret-up');
                        dropdownIcon.classList.add('fa-caret-down');
                    }
                });
            }
        });

        // Handle window resize
        let resizeTimer;
        window.addEventListener('resize', function() {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(function() {
                if (window.innerWidth >= 1025) {
                    // Desktop - ensure sidebar is not in mobile active state
                    sidebar.classList.remove('active');
                    sidebarOverlay.classList.remove('active');
                    document.body.style.overflow = '';
                    
                    // Ensure toggle button icon is correct
                    if (sidebar.classList.contains('collapsed')) {
                        toggleIcon.classList.replace('fa-bars', 'fa-ellipsis-h');
                        // Adjust logo for collapsed state
                        logoImg.style.width = '70px';
                        logoImg.style.height = '70px';
                        logoText.style.opacity = '0';
                        logoText.style.width = '0';
                        logoText.style.height = '0';
                    } else {
                        toggleIcon.classList.replace('fa-ellipsis-h', 'fa-bars');
                        // Restore logo for expanded state
                        logoImg.style.width = '85px';
                        logoImg.style.height = '85px';
                        logoText.style.opacity = '1';
                        logoText.style.width = 'auto';
                        logoText.style.height = 'auto';
                    }
                } else {
                    // Mobile/tablet - ensure sidebar is not in collapsed state
                    sidebar.classList.remove('collapsed');
                    mainContent.classList.remove('expanded');
                    toggleIcon.classList.replace('fa-ellipsis-h', 'fa-bars');
                    
                    // Restore logo for mobile
                    logoImg.style.width = '85px';
                    logoImg.style.height = '85px';
                    logoText.style.display = 'block';
                    logoText.style.opacity = '1';
                    logoText.style.width = 'auto';
                    logoText.style.height = 'auto';
                    logoText.style.fontSize = '26px';
                    
                    // Close dropdowns on mobile resize
                    document.querySelectorAll('.dropdown').forEach(dropdown => {
                        dropdown.classList.remove('open');
                        const dropdownIcon = dropdown.querySelector('.dropdown-icon');
                        if (dropdownIcon) {
                            dropdownIcon.classList.remove('fa-caret-up');
                            dropdownIcon.classList.add('fa-caret-down');
                        }
                    });
                    
                    // Hide dropdown menus
                    document.querySelectorAll('.dropdown-menu').forEach(menu => {
                        menu.classList.remove('show');
                    });
                }
            }, 250);
        });
        
        // Initialize based on current screen size
        if (window.innerWidth < 1025) {
            sidebar.classList.remove('collapsed');
            mainContent.classList.remove('expanded');
        } else {
            // Initialize logo size based on initial state
            if (sidebar.classList.contains('collapsed')) {
                logoImg.style.width = '70px';
                logoImg.style.height = '70px';
            } else {
                logoImg.style.width = '85px';
                logoImg.style.height = '85px';
            }
        }
    </script>
</body>
</html>