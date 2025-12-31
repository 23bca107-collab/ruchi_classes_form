<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: admin_login.php");
    exit;
}

$host = "localhost";
$user = "root";
$pass = "";
$dbname = "ruchi_classes";

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle adding new teacher (admin only enters email)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_teacher'])) {
    $email = trim($_POST['email']);

    // Check if email already exists
    $stmt = $conn->prepare("SELECT id FROM teachers WHERE email=?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $error = "Teacher with this email already exists!";
    } else {
        $stmt2 = $conn->prepare("INSERT INTO teachers (email) VALUES (?)");
        $stmt2->bind_param("s", $email);
        if ($stmt2->execute()) {
            $success = "Teacher added successfully! Teacher will set password at first login.";
        } else {
            $error = "Error: " . $conn->error;
        }
    }
}

// Fetch all teachers
$teachers = $conn->query("SELECT id, first_name, last_name, email, profile_completed FROM teachers ORDER BY id DESC");

// Get stats for dashboard
$total_teachers = $conn->query("SELECT COUNT(*) as count FROM teachers")->fetch_assoc()['count'];
$active_teachers = $conn->query("SELECT COUNT(*) as count FROM teachers WHERE profile_completed = 1")->fetch_assoc()['count'];
$pending_teachers = $total_teachers - $active_teachers;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Dashboard | Ruchi Classes</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
    :root {
        --primary: #4a6cf7;
        --primary-dark: #3b5be3;
        --secondary: #6c757d;
        --success: #28a745;
        --danger: #dc3545;
        --warning: #ffc107;
        --info: #17a2b8;
        --light: #f8f9fa;
        --dark: #212529;
        --sidebar-bg: #1e2a4a;
        --card-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        --transition: all 0.3s ease;
    }
    
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }
    
    body {
        font-family: 'Poppins', sans-serif;
        background-color: #f5f7fb;
        color: #333;
        line-height: 1.6;
        display: flex;
        min-height: 100vh;
    }
    
    /* Sidebar Styles */
    .sidebar {
        width: 260px;
        background: var(--sidebar-bg);
        color: white;
        height: 100vh;
        position: fixed;
        top: 0;
        left: 0;
        padding: 20px 0;
        transition: var(--transition);
        z-index: 1000;
        box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
    }
    
    .logo {
        text-align: center;
        padding: 20px 0;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        margin-bottom: 20px;
    }
    
    .logo h2 {
        font-weight: 600;
        font-size: 1.5rem;
        color: white;
    }
    
    .logo span {
        color: var(--primary);
    }
    
    .nav-links {
        list-style: none;
        padding: 0 15px;
    }
    
    .nav-links li {
        margin-bottom: 5px;
    }
    
    .nav-links a {
        display: flex;
        align-items: center;
        padding: 12px 15px;
        text-decoration: none;
        color: rgba(255, 255, 255, 0.8);
        border-radius: 6px;
        transition: var(--transition);
    }
    
    .nav-links a:hover, .nav-links a.active {
        background: rgba(255, 255, 255, 0.1);
        color: white;
    }
    
    .nav-links i {
        margin-right: 10px;
        font-size: 1.1rem;
        width: 20px;
        text-align: center;
    }
    
    /* Main Content */
    .main-content {
        flex: 1;
        margin-left: 260px;
        padding: 30px;
        transition: var(--transition);
    }
    
    /* Header */
    .header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        background: white;
        padding: 20px 30px;
        border-radius: 12px;
        box-shadow: var(--card-shadow);
        margin-bottom: 30px;
    }
    
    .header h1 {
        font-weight: 600;
        color: var(--dark);
        font-size: 1.8rem;
    }
    
    .user-info {
        display: flex;
        align-items: center;
        gap: 15px;
    }
    
    .user-info .notification {
        position: relative;
        cursor: pointer;
    }
    
    .user-info .notification i {
        font-size: 1.2rem;
        color: var(--secondary);
    }
    
    .user-info .notification .badge {
        position: absolute;
        top: -5px;
        right: -5px;
        background: var(--danger);
        color: white;
        border-radius: 50%;
        width: 18px;
        height: 18px;
        font-size: 0.7rem;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .user-profile {
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .user-profile img {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        object-fit: cover;
        border: 2px solid var(--primary);
    }
    
    .user-profile .info {
        line-height: 1.3;
    }
    
    .user-profile .info .name {
        font-weight: 500;
        color: var(--dark);
    }
    
    .user-profile .info .role {
        font-size: 0.8rem;
        color: var(--secondary);
    }
    
    /* Dashboard Stats */
    .dashboard-cards {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }
    
    .card {
        background: white;
        border-radius: 12px;
        padding: 25px;
        box-shadow: var(--card-shadow);
        display: flex;
        align-items: center;
        transition: var(--transition);
        border-left: 4px solid var(--primary);
    }
    
    .card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 15px rgba(0, 0, 0, 0.1);
    }
    
    .card-teachers {
        border-left-color: var(--primary);
    }
    
    .card-active {
        border-left-color: var(--success);
    }
    
    .card-pending {
        border-left-color: var(--warning);
    }
    
    .card-icon {
        width: 60px;
        height: 60px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 15px;
        font-size: 1.8rem;
        color: white;
    }
    
    .card-teachers .card-icon {
        background: var(--primary);
    }
    
    .card-active .card-icon {
        background: var(--success);
    }
    
    .card-pending .card-icon {
        background: var(--warning);
    }
    
    .card-content h3 {
        font-size: 1.8rem;
        font-weight: 600;
        margin-bottom: 5px;
        color: var(--dark);
    }
    
    .card-content p {
        color: var(--secondary);
        font-size: 0.9rem;
    }
    
    /* Form Styles */
    .form-container {
        background: white;
        border-radius: 12px;
        padding: 25px;
        box-shadow: var(--card-shadow);
        margin-bottom: 30px;
    }
    
    .form-container h2 {
        margin-bottom: 20px;
        color: var(--dark);
        font-weight: 600;
        display: flex;
        align-items: center;
        padding-bottom: 15px;
        border-bottom: 1px solid #eee;
    }
    
    .form-container h2 i {
        margin-right: 10px;
        color: var(--primary);
        background: rgba(74, 108, 247, 0.1);
        width: 40px;
        height: 40px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .form-group {
        display: flex;
        align-items: center;
        margin-bottom: 15px;
        gap: 15px;
    }
    
    .form-group input {
        flex: 1;
        padding: 15px;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        font-size: 1rem;
        transition: var(--transition);
        font-family: 'Poppins', sans-serif;
    }
    
    .form-group input:focus {
        outline: none;
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(74, 108, 247, 0.2);
    }
    
    .btn {
        padding: 15px 25px;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        font-weight: 500;
        transition: var(--transition);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-family: 'Poppins', sans-serif;
    }
    
    .btn i {
        margin-right: 8px;
    }
    
    .btn-primary {
        background: var(--primary);
        color: white;
    }
    
    .btn-primary:hover {
        background: var(--primary-dark);
        transform: translateY(-2px);
    }
    
    /* Table Styles */
    .table-container {
        background: white;
        border-radius: 12px;
        padding: 25px;
        box-shadow: var(--card-shadow);
        overflow-x: auto;
    }
    
    .table-container h2 {
        margin-bottom: 20px;
        color: var(--dark);
        font-weight: 600;
        display: flex;
        align-items: center;
        padding-bottom: 15px;
        border-bottom: 1px solid #eee;
    }
    
    .table-container h2 i {
        margin-right: 10px;
        color: var(--primary);
        background: rgba(74, 108, 247, 0.1);
        width: 40px;
        height: 40px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    table {
        width: 100%;
        border-collapse: collapse;
    }
    
    th, td {
        padding: 15px;
        text-align: left;
        border-bottom: 1px solid #eee;
    }
    
    th {
        background-color: #f8fafc;
        font-weight: 600;
        color: var(--dark);
        position: sticky;
        top: 0;
    }
    
    tr:hover {
        background-color: #f8fafc;
    }
    
    .status-badge {
        padding: 8px 15px;
        border-radius: 20px;
        font-size: 0.85rem;
        font-weight: 500;
        display: inline-flex;
        align-items: center;
        gap: 5px;
    }
    
    .status-complete {
        background: rgba(40, 167, 69, 0.15);
        color: var(--success);
    }
    
    .status-pending {
        background: rgba(255, 193, 7, 0.15);
        color: var(--warning);
    }
    
    /* Alert Styles */
    .alert {
        padding: 15px 20px;
        border-radius: 8px;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 15px;
    }
    
    .alert i {
        font-size: 1.5rem;
    }
    
    .alert-success {
        background: rgba(40, 167, 69, 0.15);
        color: var(--success);
        border-left: 4px solid var(--success);
    }
    
    .alert-error {
        background: rgba(220, 53, 69, 0.15);
        color: var(--danger);
        border-left: 4px solid var(--danger);
    }
    
    /* Responsive */
    @media (max-width: 1200px) {
        .sidebar {
            width: 80px;
        }
        
        .logo h2, .nav-links span {
            display: none;
        }
        
        .nav-links a {
            justify-content: center;
            padding: 15px;
        }
        
        .nav-links i {
            margin-right: 0;
            font-size: 1.3rem;
        }
        
        .main-content {
            margin-left: 80px;
        }
    }
    
    @media (max-width: 768px) {
        .dashboard-cards {
            grid-template-columns: 1fr;
        }
        
        .header {
            flex-direction: column;
            text-align: center;
            padding: 20px;
            gap: 15px;
        }
        
        .user-info {
            flex-direction: column;
        }
        
        .form-group {
            flex-direction: column;
            align-items: stretch;
        }
        
        .form-group input {
            margin-bottom: 10px;
        }
        
        .sidebar {
            width: 0;
            overflow: hidden;
        }
        
        .main-content {
            margin-left: 0;
            padding: 20px;
        }
        
        .menu-toggle {
            display: block;
            position: fixed;
            top: 20px;
            left: 20px;
            z-index: 1100;
            background: var(--primary);
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
        }
    }
</style>
</head>
<body>

<!-- Mobile Menu Toggle -->
<div class="menu-toggle" id="menuToggle">
    <i class="fas fa-bars"></i>
</div>

<!-- Sidebar -->
<div class="sidebar" id="sidebar">
    <div class="logo">
        <h2>Ruchi <span>Classes</span></h2>
    </div>
    <ul class="nav-links">
        <li><a href="#" class="active"><i class="fas fa-home"></i> <span>Dashboard</span></a></li>
        <li><a href="manage_teacher.php"><i class="fas fa-chalkboard-teacher"></i> <span>Teachers</span></a></li>
        <li><a href="admission_report.php"><i class="fas fa-user-graduate"></i> <span>Students</span></a></li>
        <li><a href="admin_assign_students.php"><i class="fas fa-user-graduate"></i> <span>Students</span></a></li>
        <li>
  <a href="admin_complaints.php">
    <i class="fas fa-comment-dots"></i>
    <span>Complaint</span>
  </a>
</li>
        <li><a href="add_schedule.php"><i class="fas fa-calendar-alt"></i> <span>Schedule</span></a></li>
        <li><a href="admin_assign_attendance.php"><i class="fas fa-chart-bar"></i> <span>Reports</span></a></li>
        <li><a href="#"><i class="fas fa-cog"></i> <span>Settings</span></a></li>
        <li><a href="admin_logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a></li>
    </ul>
</div>

<!-- Main Content -->
<div class="main-content">
    <!-- Header -->
    <div class="header">
        <h1>Admin Dashboard</h1>
        <div class="user-info">
            <div class="notification">
                <i class="far fa-bell"></i>
                <span class="badge">3</span>
            </div>
            <div class="user-profile">
                <img src="data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIyNCIgaGVpZ2h0PSIyNCIgdmlld0JveD0iMCAwIDI0IDI0IiBmaWxsPSJub25lIiBzdHJva2U9IiM0YTZjZjciIHN0cm9rZS13aWR0aD0iMiIgc3Ryb2tlLWxpbmVjYXA9InJvdW5kIiBzdHJva2UtbGluZWpvaW49InJvdW5kIj48cGF0aCBkPSJNMTkgMjF2LTJhNCA0IDAgMCAwLTQtNEg5YTQgNCAwIDAgMC00IDR2MiI+PC9wYXRoPjxjaXJjbGUgY3g9IjEyIiBjeT0iNyIgcj0iNCI+PC9jaXJjbGU+PC9zdmc+" alt="Admin">
                <div class="info">
                    <div class="name">Admin User</div>
                    <div class="role">Administrator</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Dashboard Stats -->
    <div class="dashboard-cards">
        <div class="card card-teachers">
            <div class="card-icon">
                <i class="fas fa-chalkboard-teacher"></i>
            </div>
            <div class="card-content">
                <h3><?php echo $total_teachers; ?></h3>
                <p>Total Teachers</p>
            </div>
        </div>
        
        <div class="card card-active">
            <div class="card-icon">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="card-content">
                <h3><?php echo $active_teachers; ?></h3>
                <p>Active Teachers</p>
            </div>
        </div>
        
        <div class="card card-pending">
            <div class="card-icon">
                <i class="fas fa-clock"></i>
            </div>
            <div class="card-content">
                <h3><?php echo $pending_teachers; ?></h3>
                <p>Pending Profiles</p>
            </div>
        </div>
    </div>

    <!-- Alerts -->
    <?php if (isset($success)): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> <?php echo $success; ?>
        </div>
    <?php endif; ?>
    
    <?php if (isset($error)): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
        </div>
    <?php endif; ?>

    <!-- Add Teacher Form -->
    <div class="form-container">
        <h2><i class="fas fa-user-plus"></i> Add New Teacher</h2>
        <form method="POST">
            <div class="form-group">
                <input type="email" name="email" placeholder="Enter teacher's email address" required>
                <button type="submit" name="add_teacher" class="btn btn-primary">
                    <i class="fas fa-user-plus"></i> Add Teacher
                </button>
            </div>
        </form>
    </div>

    <!-- Teachers Table -->
    <div class="table-container">
        <h2><i class="fas fa-users"></i> All Teachers</h2>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($teachers->num_rows > 0): ?>
                    <?php while ($row = $teachers->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $row['id']; ?></td>
                            <td>
                                <?php 
                                    $name = trim($row['first_name'] . ' ' . $row['last_name']);
                                    echo $name ?: '—'; // show dash if no name
                                ?>
                            </td>
                            <td><?php echo $row['email']; ?></td>
                            <td>
                                <?php if ($row['profile_completed']): ?>
                                    <span class="status-badge status-complete">
                                        <i class="fas fa-check-circle"></i> Completed
                                    </span>
                                <?php else: ?>
                                    <span class="status-badge status-pending">
                                        <i class="fas fa-clock"></i> Pending
                                    </span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4" style="text-align: center; padding: 20px;">
                            No teachers found. Add your first teacher using the form above.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
    // Toggle sidebar on mobile
    document.getElementById('menuToggle').addEventListener('click', function() {
        const sidebar = document.getElementById('sidebar');
        if (sidebar.style.width === '0px' || sidebar.style.width === '') {
            sidebar.style.width = '260px';
        } else {
            sidebar.style.width = '0';
        }
    });
</script>

</body>
</html>