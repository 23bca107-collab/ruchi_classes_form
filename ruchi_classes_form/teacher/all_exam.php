<?php
session_start();
require '../db.php';

if (!isset($_SESSION['teacher_logged_in'])) {
    header("Location: teacher_login.php");
    exit();
}

// ---------- DELETE ----------
if (isset($_GET['delete_id'])) {
    $id = $_GET['delete_id'];
    $stmt = $conn->prepare("DELETE FROM exams WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    
    $_SESSION['success_message'] = "Exam deleted successfully!";
    header("Location: all_exam.php");
    exit();
}

// ---------- EDIT ----------
$edit_exam = null;
if (isset($_GET['edit_id'])) {
    $id = $_GET['edit_id'];
    $stmt = $conn->prepare("SELECT * FROM exams WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $edit_exam = $result->fetch_assoc();
    $stmt->close();
}

// ---------- ADD OR UPDATE ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $class = $_POST['class'];
    $medium = $_POST['medium'];
    $subject = $_POST['subject'];
    $topic = $_POST['topic'];
    $exam_date = $_POST['exam_date'];
    $exam_time = $_POST['exam_time'];

    if (isset($_POST['update_exam'])) {
        // UPDATE
        $id = $_POST['id'];
        $stmt = $conn->prepare("UPDATE exams SET class=?, medium=?, subject=?, topic=?, exam_date=?, exam_time=? WHERE id=?");
        $stmt->bind_param("ssssssi", $class, $medium, $subject, $topic, $exam_date, $exam_time, $id);
        $stmt->execute();
        $stmt->close();
        
        $_SESSION['success_message'] = "Exam updated successfully!";
    } else {
        // ADD NEW
        $stmt = $conn->prepare("INSERT INTO exams (class, medium, subject, topic, exam_date, exam_time) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssss", $class, $medium, $subject, $topic, $exam_date, $exam_time);
        $stmt->execute();
        $stmt->close();
        
        $_SESSION['success_message'] = "Exam added successfully!";
    }

    header("Location: all_exam.php");
    exit();
}

// ---------- FETCH ALL EXAMS ----------
$result = $conn->query("SELECT * FROM exams ORDER BY exam_date ASC, exam_time ASC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Exams Management</title>
<!-- SweetAlert CSS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<!-- Font Awesome -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
    :root {
        --primary: #4361ee;
        --secondary: #3f37c9;
        --success: #4cc9f0;
        --danger: #f72585;
        --light: #f8f9fa;
        --dark: #212529;
        --gray: #6c757d;
        --light-gray: #e9ecef;
    }
    
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
        transition: all 0.3s ease;
    }
    
    body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
        min-height: 100vh;
        padding: 20px;
        color: var(--dark);
    }
    
    .container {
        max-width: 1200px;
        margin: 0 auto;
    }
    
    .card {
        background: white;
        border-radius: 12px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        padding: 25px;
        margin-bottom: 30px;
        animation: fadeIn 0.5s ease;
    }
    
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    h2 {
        color: var(--primary);
        margin-bottom: 20px;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    h2 i {
        font-size: 1.5rem;
    }
    
    .form-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 20px;
    }
    
    .form-group {
        margin-bottom: 15px;
    }
    
    label {
        display: block;
        margin-bottom: 8px;
        font-weight: 500;
        color: var(--dark);
    }
    
    input, select {
        width: 100%;
        padding: 12px 15px;
        border: 1px solid var(--light-gray);
        border-radius: 8px;
        font-size: 16px;
        transition: all 0.3s;
    }
    
    input:focus, select:focus {
        outline: none;
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.2);
    }
    
    .btn {
        padding: 12px 25px;
        border: none;
        border-radius: 8px;
        font-size: 16px;
        font-weight: 600;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        transition: all 0.3s;
    }
    
    .btn-primary {
        background: var(--primary);
        color: white;
    }
    
    .btn-primary:hover {
        background: var(--secondary);
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(67, 97, 238, 0.3);
    }
    
    .btn-danger {
        background: var(--danger);
        color: white;
    }
    
    .btn-danger:hover {
        background: #e01b74;
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(247, 37, 133, 0.3);
    }
    
    .btn-edit {
        background: #ff9800;
        color: white;
    }
    
    .btn-edit:hover {
        background: #f57c00;
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(255, 152, 0, 0.3);
    }
    
    .table-responsive {
        overflow-x: auto;
        border-radius: 12px;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
    }
    
    table {
        width: 100%;
        border-collapse: collapse;
        background: white;
        border-radius: 12px;
        overflow: hidden;
    }
    
    th, td {
        padding: 15px;
        text-align: left;
        border-bottom: 1px solid var(--light-gray);
    }
    
    th {
        background: var(--primary);
        color: white;
        font-weight: 600;
        position: sticky;
        top: 0;
    }
    
    tr:nth-child(even) {
        background-color: #f8f9fa;
    }
    
    tr:hover {
        background-color: #e9ecef;
        transform: scale(1.01);
    }
    
    .action-buttons {
        display: flex;
        gap: 10px;
    }
    
    .btn-sm {
        padding: 8px 12px;
        font-size: 14px;
    }
    
    .notification {
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 15px 25px;
        border-radius: 8px;
        color: white;
        font-weight: 500;
        z-index: 1000;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        animation: slideIn 0.5s ease, fadeOut 0.5s ease 2.5s forwards;
    }
    
    @keyframes slideIn {
        from { transform: translateX(100px); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }
    
    @keyframes fadeOut {
        from { opacity: 1; }
        to { opacity: 0; visibility: hidden; }
    }
    
    .notification.success {
        background: #4caf50;
    }
    
    .notification.error {
        background: #f44336;
    }
    
    @media (max-width: 768px) {
        .form-grid {
            grid-template-columns: 1fr;
        }
        
        .action-buttons {
            flex-direction: column;
        }
    }
</style>
</head>
<body>
<div class="container">
    <?php if (isset($_SESSION['success_message'])): ?>
    <div class="notification success">
        <i class="fas fa-check-circle"></i> <?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?>
    </div>
    <?php endif; ?>

    <div class="card">
        <h2><i class="fas <?= $edit_exam ? 'fa-edit' : 'fa-plus-circle' ?>"></i> <?= $edit_exam ? "Edit Exam" : "Add New Exam" ?></h2>
        <form method="POST" id="examForm">
            <?php if ($edit_exam) echo '<input type="hidden" name="id" value="'.$edit_exam['id'].'">'; ?>
            
            <div class="form-grid">
                <div class="form-group">
                    <label for="class">Class</label>
                    <select name="class" id="class" required>
                        <?php
                        for($i=8;$i<=12;$i++){
                            $selected = ($edit_exam && $edit_exam['class']==$i) ? "selected" : "";
                            echo "<option value='$i' $selected>Class $i</option>";
                        }
                        ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="medium">Medium</label>
                    <select name="medium" id="medium" required>
                        <option value="English" <?= ($edit_exam && $edit_exam['medium']=='English')?'selected':'' ?>>English</option>
                        <option value="Hindi" <?= ($edit_exam && $edit_exam['medium']=='Hindi')?'selected':'' ?>>Hindi</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="subject">Subject</label>
                    <input type="text" name="subject" id="subject" value="<?= $edit_exam ? $edit_exam['subject'] : '' ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="topic">Topic</label>
                    <input type="text" name="topic" id="topic" value="<?= $edit_exam ? $edit_exam['topic'] : '' ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="exam_date">Exam Date</label>
                    <input type="date" name="exam_date" id="exam_date" value="<?= $edit_exam ? $edit_exam['exam_date'] : '' ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="exam_time">Exam Time</label>
                    <input type="time" name="exam_time" id="exam_time" value="<?= $edit_exam ? $edit_exam['exam_time'] : '' ?>" required>
                </div>
            </div>
            
            <button type="submit" class="btn btn-primary" name="<?= $edit_exam ? 'update_exam' : 'add_exam' ?>">
                <i class="fas <?= $edit_exam ? 'fa-sync' : 'fa-plus' ?>"></i> <?= $edit_exam ? 'Update Exam' : 'Add Exam' ?>
            </button>
            
            <?php if ($edit_exam): ?>
            <a href="all_exam.php" class="btn btn-danger" style="margin-left: 10px;">
                <i class="fas fa-times"></i> Cancel Edit
            </a>
            <?php endif; ?>
        </form>
    </div>

    <div class="card">
        <h2><i class="fas fa-list-alt"></i> All Exams</h2>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Class</th>
                        <th>Medium</th>
                        <th>Subject</th>
                        <th>Topic</th>
                        <th>Exam Date</th>
                        <th>Exam Time</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = $result->fetch_assoc()){ ?>
                    <tr>
                        <td><?= $row['id'] ?></td>
                        <td>Class <?= $row['class'] ?></td>
                        <td><?= $row['medium'] ?></td>
                        <td><?= $row['subject'] ?></td>
                        <td><?= $row['topic'] ?></td>
                        <td><?= date('M j, Y', strtotime($row['exam_date'])) ?></td>
                        <td><?= date('h:i A', strtotime($row['exam_time'])) ?></td>
                        <td>
                            <div class="action-buttons">
                                <a href="all_exam.php?edit_id=<?= $row['id'] ?>" class="btn btn-edit btn-sm">
                                    <i class="fas fa-edit"></i> Edit
                                </a>
                                <a href="#" onclick="confirmDelete(<?= $row['id'] ?>)" class="btn btn-danger btn-sm">
                                    <i class="fas fa-trash"></i> Delete
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- SweetAlert JS -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    function confirmDelete(id) {
        Swal.fire({
            title: 'Are you sure?',
            text: "You won't be able to revert this!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#4361ee',
            cancelButtonColor: '#f72585',
            confirmButtonText: 'Yes, delete it!',
            cancelButtonText: 'Cancel',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = `all_exam.php?delete_id=${id}`;
            }
        });
    }

    // Add animation to form submission
    document.getElementById('examForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Simple validation
        let isValid = true;
        const inputs = this.querySelectorAll('input[required], select[required]');
        
        inputs.forEach(input => {
            if (!input.value.trim()) {
                input.style.borderColor = '#f72585';
                isValid = false;
            } else {
                input.style.borderColor = '';
            }
        });
        
        if (isValid) {
            this.submit();
        } else {
            Swal.fire({
                title: 'Missing Information',
                text: 'Please fill in all required fields',
                icon: 'error',
                confirmButtonColor: '#4361ee'
            });
        }
    });
</script>
</body>
</html>