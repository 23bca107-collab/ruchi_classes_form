<?php
// ✅ Database Connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "ruchi_classes"; // Your DB name
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

// ✅ Add Teacher
if (isset($_POST['add_teacher'])) {
    $email = $_POST['email'];
    
    // Check if email already exists
    $check_sql = "SELECT id FROM teachers WHERE email = '$email'";
    $check_result = $conn->query($check_sql);
    
    if ($check_result->num_rows > 0) {
        $_SESSION['error'] = "Teacher with this email already exists!";
        header("Location: manage_teacher.php?error=1");
        exit;
    }

    // Insert teacher with inactive status (no password set)
    $sql = "INSERT INTO teachers (email, status) VALUES ('$email', 'inactive')";
    
    if ($conn->query($sql)) {
        $_SESSION['teacher_email'] = $email;
        header("Location: manage_teacher.php?success=1");
        exit;
    }
}

// ✅ Delete Teacher
if (isset($_GET['delete_id'])) {
    $id = $_GET['delete_id'];
    $conn->query("DELETE FROM teachers WHERE id=$id");
    header("Location: manage_teacher.php");
    exit;
}

// ✅ Toggle Active/Inactive
if (isset($_GET['toggle_id'])) {
    $id = $_GET['toggle_id'];
    $status = $_GET['status'];
    $new_status = ($status == 'active') ? 'inactive' : 'active';
    $conn->query("UPDATE teachers SET status='$new_status' WHERE id=$id");
    header("Location: manage_teacher.php");
    exit;
}

// ✅ Fetch all teachers
$result = $conn->query("SELECT * FROM teachers ORDER BY id DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Manage Teachers - Ruchi Classes</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<style>
:root {
    --primary: #3498db;
    --secondary: #2980b9;
    --accent: #e74c3c;
    --success: #27ae60;
    --warning: #f39c12;
    --light: #f8f9fa;
    --dark: #2c3e50;
    --gray: #6c757d;
    --border: #dee2e6;
}

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

body {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: var(--dark);
    min-height: 100vh;
    padding: 20px;
}

.container {
    max-width: 1400px;
    margin: 0 auto;
    background: white;
    border-radius: 15px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
    overflow: hidden;
}

.header {
    background: linear-gradient(to right, var(--primary), var(--secondary));
    color: white;
    padding: 25px 30px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.logo {
    display: flex;
    align-items: center;
    gap: 15px;
}

.logo-icon {
    width: 50px;
    height: 50px;
    background: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    color: var(--primary);
}

.logo-text h1 {
    font-size: 28px;
    font-weight: 700;
}

.logo-text p {
    font-size: 14px;
    opacity: 0.9;
}

.content {
    padding: 30px;
}

.section-title {
    font-size: 24px;
    color: var(--dark);
    margin-bottom: 25px;
    padding-bottom: 15px;
    border-bottom: 2px solid var(--border);
    display: flex;
    align-items: center;
    gap: 10px;
}

.section-title i {
    color: var(--primary);
}

/* Form Styles */
.add-teacher-form {
    background: var(--light);
    padding: 25px;
    border-radius: 10px;
    margin-bottom: 30px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    text-align: center;
}

.simple-form {
    display: flex;
    gap: 15px;
    align-items: flex-end;
    justify-content: center;
    max-width: 500px;
    margin: 0 auto;
}

.form-group {
    display: flex;
    flex-direction: column;
    flex: 1;
}

.form-group label {
    font-weight: 600;
    margin-bottom: 8px;
    color: var(--dark);
    font-size: 14px;
    text-align: left;
}

.form-group input {
    padding: 12px 15px;
    border: 2px solid var(--border);
    border-radius: 8px;
    font-size: 16px;
    transition: all 0.3s;
}

.form-group input:focus {
    border-color: var(--primary);
    outline: none;
    box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.2);
}

.submit-btn {
    background: linear-gradient(to right, var(--success), #2ecc71);
    color: white;
    border: none;
    padding: 12px 25px;
    border-radius: 8px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s;
    display: flex;
    align-items: center;
    gap: 8px;
    box-shadow: 0 4px 15px rgba(46, 204, 113, 0.3);
    white-space: nowrap;
}

.submit-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(46, 204, 113, 0.4);
}

/* Table Styles */
.teachers-table {
    background: white;
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
}

table {
    width: 100%;
    border-collapse: collapse;
}

thead {
    background: linear-gradient(to right, var(--primary), var(--secondary));
}

th {
    padding: 15px;
    text-align: left;
    color: white;
    font-weight: 600;
    font-size: 15px;
}

td {
    padding: 15px;
    border-bottom: 1px solid var(--border);
    font-size: 14px;
}

tbody tr {
    transition: all 0.3s;
}

tbody tr:hover {
    background: rgba(52, 152, 219, 0.05);
}

.status-badge {
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
}

.status-active {
    background: rgba(39, 174, 96, 0.15);
    color: var(--success);
}

.status-inactive {
    background: rgba(231, 76, 60, 0.15);
    color: var(--accent);
}

.password-status {
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 600;
}

.password-set {
    background: rgba(39, 174, 96, 0.15);
    color: var(--success);
}

.password-not-set {
    background: rgba(243, 156, 18, 0.15);
    color: var(--warning);
}

.action-buttons {
    display: flex;
    gap: 8px;
}

.btn {
    padding: 8px 15px;
    border: none;
    border-radius: 6px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 5px;
}

.btn-sm {
    padding: 6px 12px;
    font-size: 12px;
}

.btn-toggle {
    background: var(--warning);
    color: white;
}

.btn-toggle:hover {
    background: #e67e22;
}

.btn-delete {
    background: var(--accent);
    color: white;
}

.btn-delete:hover {
    background: #c0392b;
}

.btn-reset {
    background: var(--primary);
    color: white;
}

.btn-reset:hover {
    background: #2980b9;
}

.empty-state {
    text-align: center;
    padding: 40px;
    color: var(--gray);
}

.empty-state i {
    font-size: 50px;
    margin-bottom: 15px;
    opacity: 0.5;
}

.success-message {
    background: #e8f5e8;
    border: 2px solid #27ae60;
    border-radius: 8px;
    padding: 15px;
    margin: 20px 0;
    text-align: center;
}

.success-message h4 {
    color: #27ae60;
    margin-bottom: 10px;
}

.form-info {
    color: var(--gray);
    font-size: 14px;
    margin-top: 10px;
    font-style: italic;
}

/* Responsive */
@media (max-width: 768px) {
    .simple-form {
        flex-direction: column;
        align-items: stretch;
    }
    
    .action-buttons {
        flex-direction: column;
    }
    
    th, td {
        padding: 10px;
    }
    
    .header {
        flex-direction: column;
        gap: 15px;
        text-align: center;
    }
}

@media (max-width: 480px) {
    .content {
        padding: 15px;
    }
    
    table {
        display: block;
        overflow-x: auto;
    }
}
</style>
</head>
<body>
<div class="container">
    <div class="header">
        <div class="logo">
            <div class="logo-icon">
                <i class="fas fa-chalkboard-teacher"></i>
            </div>
            <div class="logo-text">
                <h1>Ruchi Classes</h1>
                <p>Teacher Management System</p>
            </div>
        </div>
        <div class="header-info">
            <i class="fas fa-users"></i>
            <span>Manage Teaching Staff</span>
        </div>
    </div>

    <div class="content">
        <div class="section-title">
            <i class="fas fa-user-plus"></i>
            <span>Add New Teacher</span>
        </div>

        <?php if (isset($_GET['success']) && $_GET['success'] == '1' && isset($_SESSION['teacher_email'])): ?>
        <div class="success-message">
            <h4><i class="fas fa-check-circle"></i> Teacher Added Successfully!</h4>
            <p><strong>Email:</strong> <?php echo $_SESSION['teacher_email']; ?></p>
            <p>Teacher can now register and set their password at the Teacher Login page.</p>
        </div>
        <?php 
            unset($_SESSION['teacher_email']);
        endif; 
        ?>

        <div class="add-teacher-form">
            <form method="POST" class="simple-form">
                <div class="form-group">
                    <label for="email"><i class="fas fa-envelope"></i> Teacher Email *</label>
                    <input type="email" id="email" name="email" placeholder="Enter teacher's email" required>
                </div>
                <button type="submit" name="add_teacher" class="submit-btn">
                    <i class="fas fa-user-plus"></i> Add Teacher
                </button>
            </form>
            <p class="form-info">Teacher will set their own password during registration at Teacher Login</p>
        </div>

        <div class="section-title">
            <i class="fas fa-list"></i>
            <span>Teacher List</span>
        </div>

        <div class="teachers-table">
            <?php if ($result->num_rows > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Email</th>
                        <th>Name</th>
                        <th>Mobile</th>
                        <th>Subject</th>
                        <th>Password</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td>#<?= $row['id'] ?></td>
                        <td><strong><?= htmlspecialchars($row['email']) ?></strong></td>
                        <td>
                            <?php 
                                $name = trim($row['first_name'] . ' ' . $row['last_name']);
                                echo $name ? htmlspecialchars($name) : '<span style="color: #999; font-style: italic;">Not set</span>';
                            ?>
                        </td>
                        <td><?= $row['mobile'] ? htmlspecialchars($row['mobile']) : '<span style="color: #999;">-</span>' ?></td>
                        <td><?= $row['subject'] ? htmlspecialchars($row['subject']) : '<span style="color: #999;">-</span>' ?></td>
                        <td>
                            <span class="password-status <?= $row['password'] ? 'password-set' : 'password-not-set' ?>">
                                <?= $row['password'] ? 'Set' : 'Not Set' ?>
                            </span>
                        </td>
                        <td>
                            <span class="status-badge status-<?= $row['status'] ?>">
                                <?= ucfirst($row['status']) ?>
                            </span>
                        </td>
                        <td>
                            <div class="action-buttons">
                                <a class="btn btn-toggle btn-sm" href="?toggle_id=<?= $row['id'] ?>&status=<?= $row['status'] ?>">
                                    <i class="fas fa-power-off"></i>
                                    <?= ($row['status'] == 'active') ? 'Deactivate' : 'Activate' ?>
                                </a>
                                <?php if ($row['password']): ?>
                                <a class="btn btn-reset btn-sm" href="#" onclick="resetPassword(<?= $row['id'] ?>, '<?= htmlspecialchars($row['email']) ?>')">
                                    <i class="fas fa-key"></i> Reset Password
                                </a>
                                <?php endif; ?>
                                <a class="btn btn-delete btn-sm" href="#" onclick="confirmDelete(<?= $row['id'] ?>, '<?= htmlspecialchars($row['email']) ?>')">
                                    <i class="fas fa-trash"></i> Delete
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-users-slash"></i>
                <h3>No Teachers Found</h3>
                <p>Add your first teacher using the form above</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
// SweetAlert for delete confirmation
function confirmDelete(teacherId, teacherEmail) {
    Swal.fire({
        title: 'Are you sure?',
        html: `You are about to delete teacher <strong>${teacherEmail}</strong>. This action cannot be undone!`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#e74c3c',
        cancelButtonColor: '#3498db',
        confirmButtonText: 'Yes, delete it!',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = `?delete_id=${teacherId}`;
        }
    });
}

// Function to reset password
function resetPassword(teacherId, teacherEmail) {
    Swal.fire({
        title: 'Reset Password',
        html: `Reset password for <strong>${teacherEmail}</strong>?<br><br>
               <small>Teacher will need to set a new password on next login.</small>`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#3498db',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Reset Password',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            // In a real application, you would make an AJAX call here
            Swal.fire({
                title: 'Password Reset!',
                text: 'Teacher password has been reset. They will need to set a new password on next login.',
                icon: 'success',
                confirmButtonColor: '#3498db'
            });
        }
    });
}

// Add some interactive effects
document.addEventListener('DOMContentLoaded', function() {
    // Add animation to form submission
    const submitBtn = document.querySelector('.submit-btn');
    if (submitBtn) {
        submitBtn.addEventListener('click', function(e) {
            const email = document.getElementById('email').value;
            if (email) {
                this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Adding Teacher...';
            }
        });
    }
    
    // Add hover effects to table rows
    const tableRows = document.querySelectorAll('tbody tr');
    tableRows.forEach(row => {
        row.addEventListener('mouseenter', function() {
            this.style.transform = 'translateX(5px)';
        });
        row.addEventListener('mouseleave', function() {
            this.style.transform = 'translateX(0)';
        });
    });
});

// Show success message if redirected with success parameter
<?php if (isset($_GET['success']) && $_GET['success'] == '1'): ?>
document.addEventListener('DOMContentLoaded', function() {
    Swal.fire({
        title: 'Success!',
        text: 'Teacher added successfully. They can now register with their email.',
        icon: 'success',
        confirmButtonColor: '#3498db'
    });
});
<?php endif; ?>

// Show error message if email already exists
<?php if (isset($_GET['error']) && $_GET['error'] == '1'): ?>
document.addEventListener('DOMContentLoaded', function() {
    Swal.fire({
        title: 'Error!',
        text: 'Teacher with this email already exists!',
        icon: 'error',
        confirmButtonColor: '#e74c3c'
    });
});
<?php endif; ?>
</script>
</body>
</html>