<?php
session_start();
require '../db.php';

// Only logged-in teachers can access
if (!isset($_SESSION['teacher_logged_in'])) {
    header("Location: teacher_login.php");
    exit();
}

// Handle form submit (marks save)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['save_marks'])) {
        $exam_id = $_POST['exam_id'];
        $medium  = $_POST['medium'];
        $class   = $_POST['class'];
        $subject = $_POST['subject'] ?? '';
        $exam_date = $_POST['exam_date'] ?? date('Y-m-d');

        // If custom subject is selected, create a new exam entry
        if ($exam_id === 'custom' && !empty($subject)) {
            $stmt = $conn->prepare("INSERT INTO exams (subject, exam_date, class, medium) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $subject, $exam_date, $class, $medium);
            if ($stmt->execute()) {
                $exam_id = $stmt->insert_id;
            }
        }

        foreach ($_POST['marks'] as $student_id => $marks) {
            $stmt = $conn->prepare("INSERT INTO exam_marks (student_id, exam_id, class, medium, marks) 
                                    VALUES (?, ?, ?, ?, ?)
                                    ON DUPLICATE KEY UPDATE marks=?");
            $stmt->bind_param("iissii", $student_id, $exam_id, $class, $medium, $marks, $marks);
            $stmt->execute();
        }
        $marks_saved = true;
    }
}

// Filter students & exams
$students = [];
$exams    = [];
$class    = $_GET['class'] ?? '';
$medium   = $_GET['medium'] ?? '';

if (!empty($class) && !empty($medium)) {
    // Select student table
    if ($medium === "English") {
        $student_table = "student_english";
    } elseif ($medium === "Hindi") {
        $student_table = "student_hindi";
    } else {
        $student_table = "";
    }

    if ($student_table != "") {
        $s = $conn->prepare("SELECT * FROM $student_table WHERE class=?");
        $s->bind_param("s", $class);
        $s->execute();
        $students = $s->get_result()->fetch_all(MYSQLI_ASSOC);

        $ex = $conn->prepare("SELECT * FROM exams WHERE class=? AND medium=?");
        $ex->bind_param("ss", $class, $medium);
        $ex->execute();
        $exams = $ex->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Exam Marks Entry System</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }
    
    body {
        background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
        min-height: 100vh;
        padding: 20px;
        color: #333;
    }
    
    .container {
        max-width: 1200px;
        margin: 0 auto;
    }
    
    header {
        text-align: center;
        margin-bottom: 30px;
        animation: fadeInDown 1s ease;
    }
    
    h1 {
        color: #2c3e50;
        font-size: 2.8rem;
        margin-bottom: 10px;
        font-weight: 700;
        text-shadow: 1px 1px 3px rgba(0,0,0,0.1);
    }
    
    .subtitle {
        color: #7f8c8d;
        font-size: 1.2rem;
        margin-bottom: 20px;
    }
    
    .card {
        background: white;
        border-radius: 16px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        padding: 30px;
        margin-bottom: 30px;
        animation: fadeInUp 0.8s ease;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        overflow: hidden;
    }
    
    .card:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 35px rgba(0, 0, 0, 0.15);
    }
    
    .card-title {
        color: #3498db;
        margin-bottom: 25px;
        font-size: 1.6rem;
        border-bottom: 2px solid #f1f1f1;
        padding-bottom: 15px;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .card-title i {
        font-size: 1.4rem;
    }
    
    .form-group {
        margin-bottom: 20px;
    }
    
    label {
        display: block;
        margin-bottom: 8px;
        font-weight: 600;
        color: #2c3e50;
        font-size: 1rem;
    }
    
    select, input {
        width: 100%;
        padding: 14px 18px;
        border: 2px solid #e0e0e0;
        border-radius: 10px;
        font-size: 16px;
        transition: all 0.3s ease;
        background: #fafafa;
    }
    
    select:focus, input:focus {
        border-color: #3498db;
        outline: none;
        box-shadow: 0 0 0 4px rgba(52, 152, 219, 0.2);
        background: #fff;
    }
    
    .btn {
        color: white;
        border: none;
        padding: 14px 25px;
        border-radius: 10px;
        cursor: pointer;
        font-size: 16px;
        font-weight: 600;
        transition: all 0.3s ease;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
    }
    
    .btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.15);
    }
    
    .btn-filter {
        background: #2ecc71;
    }
    
    .btn-filter:hover {
        background: #27ae60;
    }
    
    .btn-save {
        background: #9b59b6;
        width: 100%;
        padding: 16px;
        margin-top: 25px;
        font-size: 18px;
    }
    
    .btn-save:hover {
        background: #8e44ad;
    }
    
    .btn-custom {
        background: #e67e22;
        margin-top: 10px;
        width: 100%;
    }
    
    .btn-custom:hover {
        background: #d35400;
    }
    
    table {
        width: 100%;
        border-collapse: collapse;
        margin: 25px 0;
        animation: fadeIn 1s ease;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        border-radius: 10px;
        overflow: hidden;
    }
    
    th, td {
        padding: 16px 20px;
        text-align: left;
        border-bottom: 1px solid #e0e0e0;
    }
    
    th {
        background-color: #3498db;
        color: white;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.9rem;
        letter-spacing: 0.5px;
    }
    
    tr:nth-child(even) {
        background-color: #f8f9fa;
    }
    
    tr:hover {
        background-color: #e8f4fc;
    }
    
    input[type="number"] {
        width: 100px;
        text-align: center;
        font-weight: 600;
    }
    
    .filter-form {
        display: grid;
        grid-template-columns: 1fr 1fr auto;
        gap: 20px;
        align-items: end;
    }
    
    .exam-selector {
        display: flex;
        flex-direction: column;
        gap: 15px;
        margin-bottom: 20px;
    }
    
    .custom-exam-fields {
        background: #f8f9fa;
        padding: 20px;
        border-radius: 10px;
        border-left: 4px solid #e67e22;
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 15px;
        margin-top: 15px;
        animation: fadeIn 0.5s ease;
    }
    
    @media (max-width: 900px) {
        .filter-form {
            grid-template-columns: 1fr;
        }
        
        .custom-exam-fields {
            grid-template-columns: 1fr;
        }
        
        h1 {
            font-size: 2.2rem;
        }
    }
    
    /* Animations */
    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }
    
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
    
    .pulse {
        animation: pulse 2s infinite;
    }
    
    @keyframes pulse {
        0% { transform: scale(1); }
        50% { transform: scale(1.02); }
        100% { transform: scale(1); }
    }
    
    .success-checkmark {
        display: inline-block;
        width: 80px;
        height: 80px;
        margin: 0 auto;
        position: relative;
    }
    
    .hidden {
        display: none;
    }
    
    .toggle-custom {
        color: #e67e22;
        cursor: pointer;
        font-size: 0.9rem;
        margin-top: 8px;
        display: inline-flex;
        align-items: center;
        gap: 5px;
    }
    
    .toggle-custom:hover {
        text-decoration: underline;
    }
    
    .stats-bar {
        display: flex;
        justify-content: space-around;
        background: #2c3e50;
        color: white;
        padding: 15px;
        border-radius: 10px;
        margin-bottom: 20px;
    }
    
    .stat-item {
        text-align: center;
    }
    
    .stat-value {
        font-size: 1.8rem;
        font-weight: 700;
    }
    
    .stat-label {
        font-size: 0.9rem;
        opacity: 0.8;
    }
  </style>
</head>
<body>
<div class="container">
  <header>
    <h1><i class="fas fa-graduation-cap"></i> Exam Marks Entry System</h1>
    <p class="subtitle">Enter and manage student examination marks with ease</p>
  </header>

  <div class="card">
    <h2 class="card-title"><i class="fas fa-filter"></i> Filter Students</h2>
    <form method="get" class="filter-form">
      <div class="form-group">
        <label for="class"><i class="fas fa-users"></i> Class:</label>
        <select name="class" id="class" required>
          <option value="">Select Class</option>
          <?php for ($i=8; $i<=12; $i++): ?>
            <option value="<?= $i ?>" <?= ($class==$i)?'selected':''; ?>>Class <?= $i ?></option>
          <?php endfor; ?>
        </select>
      </div>

      <div class="form-group">
        <label for="medium"><i class="fas fa-language"></i> Medium:</label>
        <select name="medium" id="medium" required>
          <option value="">Select Medium</option>
          <option value="English" <?= ($medium=="English")?'selected':''; ?>>English</option>
          <option value="Hindi" <?= ($medium=="Hindi")?'selected':''; ?>>Hindi</option>
        </select>
      </div>

      <div class="form-group">
        <button type="submit" class="btn btn-filter"><i class="fas fa-search"></i> Filter Students</button>
      </div>
    </form>
  </div>

  <?php if (!empty($students)): ?>
  <div class="card">
    <h2 class="card-title"><i class="fas fa-edit"></i> Enter Exam Marks</h2>
    
    <?php if (!empty($students)): ?>
    <div class="stats-bar">
      <div class="stat-item">
        <div class="stat-value"><?= count($students) ?></div>
        <div class="stat-label">Students</div>
      </div>
      <div class="stat-item">
        <div class="stat-value"><?= $class ?></div>
        <div class="stat-label">Class</div>
      </div>
      <div class="stat-item">
        <div class="stat-value"><?= $medium ?></div>
        <div class="stat-label">Medium</div>
      </div>
    </div>
    <?php endif; ?>
    
    <form method="post" id="marksForm">
      <input type="hidden" name="class" value="<?= htmlspecialchars($class) ?>">
      <input type="hidden" name="medium" value="<?= htmlspecialchars($medium) ?>">

      <div class="exam-selector">
        <div class="form-group">
          <label for="exam_id"><i class="fas fa-book"></i> Select Exam:</label>
          <select name="exam_id" id="exam_id" required>
            <option value="">Select Exam</option>
            <?php foreach ($exams as $exam): ?>
              <option value="<?= $exam['id'] ?>">
                <?= $exam['subject'] ?> (<?= $exam['exam_date'] ?>)
              </option>
            <?php endforeach; ?>
            <option value="custom">+ Add Custom Exam</option>
          </select>
          <div class="toggle-custom" onclick="toggleCustomExam()">
            <i class="fas fa-plus-circle"></i> Add New Exam Subject
          </div>
        </div>

        <div id="customExamFields" class="custom-exam-fields hidden">
          <div class="form-group">
            <label for="subject"><i class="fas fa-pen"></i> Subject Name:</label>
            <input type="text" name="subject" id="subject" placeholder="Enter subject name">
          </div>
          <div class="form-group">
            <label for="exam_date"><i class="fas fa-calendar"></i> Exam Date:</label>
            <input type="date" name="exam_date" id="exam_date" value="<?= date('Y-m-d') ?>">
          </div>
        </div>
      </div>

      <table>
        <thead>
          <tr>
            <th>Student ID</th>
            <th>Student Name</th>
            <th>Marks (0-100)</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($students as $stu): ?>
          <tr>
            <td><?= htmlspecialchars($stu['id']) ?></td>
            <td><?= htmlspecialchars(($stu['first_name'] ?? '') . " " . ($stu['last_name'] ?? '')) ?></td>
            <td>
              <input type="number" name="marks[<?= $stu['id'] ?>]" min="0" max="100" class="marks-input" 
                     placeholder="0-100" onchange="validateMark(this)">
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>

      <button type="submit" name="save_marks" class="btn btn-save pulse">
        <i class="fas fa-save"></i> Save All Marks
      </button>
    </form>
  </div>
  <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
// Toggle custom exam fields
function toggleCustomExam() {
  const customFields = document.getElementById('customExamFields');
  const examSelect = document.getElementById('exam_id');
  
  examSelect.value = 'custom';
  customFields.classList.remove('hidden');
  
  // Smooth scroll to custom fields
  customFields.scrollIntoView({ behavior: 'smooth' });
}

// Validate mark input
function validateMark(input) {
  if (input.value < 0) input.value = 0;
  if (input.value > 100) input.value = 100;
}

// Show SweetAlert when page loads
document.addEventListener('DOMContentLoaded', function() {
  <?php if (!empty($students)): ?>
  Swal.fire({
    title: 'Students Loaded Successfully!',
    text: '<?= count($students) ?> students found for Class <?= $class ?> (<?= $medium ?> Medium)',
    icon: 'success',
    timer: 4000,
    showConfirmButton: false,
    background: '#f5f7fa',
    position: 'top'
  });
  <?php endif; ?>

  <?php if (isset($marks_saved) && $marks_saved): ?>
  Swal.fire({
    title: 'Success!',
    text: 'All marks have been saved successfully!',
    icon: 'success',
    confirmButtonText: 'OK',
    confirmButtonColor: '#9b59b6'
  }).then(() => {
    document.getElementById('marksForm').reset();
    document.getElementById('customExamFields').classList.add('hidden');
  });
  <?php endif; ?>

  // Handle exam selection change
  document.getElementById('exam_id').addEventListener('change', function() {
    const customFields = document.getElementById('customExamFields');
    if (this.value === 'custom') {
      customFields.classList.remove('hidden');
    } else {
      customFields.classList.add('hidden');
    }
  });
  
  // Add animation to table rows
  const rows = document.querySelectorAll('tbody tr');
  rows.forEach((row, index) => {
    row.style.animation = `fadeInUp 0.5s ease ${index * 0.1}s forwards`;
    row.style.opacity = '0';
  });
});
</script>
</body>
</html>