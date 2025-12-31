<?php
session_start();
require '../db.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: ../login.php");
    exit;
}

$email = $_SESSION['student_email'] ?? '';
$medium = $_SESSION['student_medium'] ?? '';

$table = ($medium === 'English') ? 'student_english' : 'student_hindi';

$stmt = $conn->prepare("SELECT * FROM $table WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();
$student_details = $result->fetch_assoc();

if (!$student_details) {
    session_destroy();
    header("Location: ../login.php");
    exit;
}

$defaultImage = "http://localhost/ruchi_classes_form/student/uploads/default.png";
foreach (['png','jpg','jpeg'] as $ext) {
    $path = __DIR__ . "/uploads/default.$ext";
    if (file_exists($path)) {
        $defaultImage = "http://localhost/ruchi_classes_form/student/uploads/default.$ext";
        break;
    }
}

function getPhotoPath($photo, $defaultImage) {
    if (!empty($photo)) {
        if (strpos($photo, 'uploads/') === 0) {
            return "http://localhost/ruchi_classes_form/student/" . $photo;
        }
        if (strpos($photo, 'student/uploads/') === 0) {
            return "http://localhost/ruchi_classes_form/" . $photo;
        }
        if (strpos($photo, 'http') === 0) {
            return $photo;
        }
    }
    return $defaultImage;
}
$photoPath = getPhotoPath($student_details['photo'] ?? '', $defaultImage);

function h($str) {
    return htmlspecialchars((string)$str, ENT_QUOTES, 'UTF-8');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $update_data = [];
    $allowed_fields = [
        'first_name','last_name','father_name','mother_name','dob','gender',
        'class','board','school','previous_marks','parent_mobile','personal_mobile',
        'whatsapp','city','state','pincode','address','reference'
    ];

    foreach ($allowed_fields as $field) {
        if (isset($_POST[$field])) {
            $update_data[$field] = trim($_POST[$field]);
        }
    }

    if (!empty($_FILES['photo']['name'])) {
        $uploadDir = __DIR__ . '/uploads/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $fileName = time() . '_' . basename($_FILES['photo']['name']);
        $targetFilePath = $uploadDir . $fileName;
        $fileType = strtolower(pathinfo($targetFilePath, PATHINFO_EXTENSION));

        $allowTypes = ['jpg','jpeg','png','gif','webp'];
        if (in_array($fileType, $allowTypes)) {
            if (move_uploaded_file($_FILES['photo']['tmp_name'], $targetFilePath)) {
                $update_data['photo'] = 'uploads/' . $fileName;

                $oldPhoto = $student_details['photo'] ?? '';
                if (!empty($oldPhoto) && $oldPhoto !== 'uploads/default.png' &&
                    file_exists(__DIR__ . '/' . $oldPhoto)) {
                    unlink(__DIR__ . '/' . $oldPhoto);
                }
            }
        }
    }

    if (!empty($update_data)) {
        $setClause = [];
        $types = '';
        $values = [];

        foreach ($update_data as $field => $value) {
            $setClause[] = "$field = ?";
            $types .= 's';
            $values[] = $value;
        }

        $values[] = $email;
        $types .= 's';

        $sql = "UPDATE $table SET " . implode(', ', $setClause) . " WHERE email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$values);

        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Profile updated successfully!";
            header("Location: " . $_SERVER['PHP_SELF']);
            exit;
        } else {
            $_SESSION['error_message'] = "Error updating profile: " . $conn->error;
        }
    } else {
        $_SESSION['info_message'] = "No changes were made.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile - Ruchi Classes</title>
    <!-- SweetAlert2 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #4e73df;
            --secondary: #6f42c1;
            --success: #1cc88a;
            --info: #36b9cc;
            --warning: #f6c23e;
            --danger: #e74a3b;
            --light: #f8f9fc;
            --dark: #5a5c69;
            --bg-gradient: linear-gradient(180deg, #4e73df 10%, #224abe 100%);
            --card-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f8f9fc;
            color: #333;
            line-height: 1.6;
        }
        
        .container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
            animation: fadeInDown 0.8s ease;
        }
        
        .header h1 {
            color: var(--primary);
            font-size: 2.5rem;
            margin-bottom: 10px;
            font-weight: 700;
        }
        
        .header p {
            color: var(--dark);
            font-size: 1.1rem;
        }
        
        .profile-card {
            background: white;
            border-radius: 15px;
            box-shadow: var(--card-shadow);
            overflow: hidden;
            margin-bottom: 30px;
            animation: slideUp 0.5s ease;
        }
        
        .card-header {
            background: var(--bg-gradient);
            color: white;
            padding: 20px;
            text-align: center;
        }
        
        .card-body {
            padding: 30px;
        }
        
        .profile-img-section {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .profile-img {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            border: 5px solid #f0f0f0;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }
        
        .profile-img:hover {
            transform: scale(1.05);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }
        
        .img-upload {
            margin-top: 15px;
        }
        
        .img-upload-label {
            display: inline-block;
            padding: 10px 20px;
            background: var(--primary);
            color: white;
            border-radius: 50px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .img-upload-label:hover {
            background: var(--secondary);
            transform: translateY(-2px);
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--dark);
        }
        
        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #d1d3e2;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s;
        }
        
        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 0.2rem rgba(78, 115, 223, 0.25);
            outline: none;
        }
        
        textarea.form-control {
            min-height: 100px;
            resize: vertical;
        }
        
        .btn {
            display: inline-block;
            padding: 12px 30px;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 50px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-align: center;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }
        
        .btn:hover {
            background: var(--secondary);
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }
        
        .btn:active {
            transform: translateY(0);
        }
        
        .btn-block {
            display: block;
            width: 100%;
        }
        
        .action-buttons {
            display: flex;
            gap: 15px;
            margin-top: 30px;
        }
        
        .btn-success {
            background: var(--success);
        }
        
        .btn-danger {
            background: var(--danger);
        }
        
        .btn-info {
            background: var(--info);
        }
        
        /* Animations */
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
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
        
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .animated {
            animation-duration: 0.5s;
            animation-fill-mode: both;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            .action-buttons {
                flex-direction: column;
            }
            
            .header h1 {
                font-size: 2rem;
            }
        }
        
        /* Custom file input */
        input[type="file"] {
            display: none;
        }
        
        /* Toast notification */
        .toast-notification {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 25px;
            border-radius: 8px;
            color: white;
            z-index: 1000;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            animation: toastIn 0.5s ease, toastOut 0.5s ease 2.5s forwards;
        }
        
        .toast-success {
            background: var(--success);
        }
        
        .toast-error {
            background: var(--danger);
        }
        
        .toast-info {
            background: var(--info);
        }
        
        @keyframes toastIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        
        @keyframes toastOut {
            from {
                transform: translateX(0);
                opacity: 1;
            }
            to {
                transform: translateX(100%);
                opacity: 0;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-user-edit"></i> Edit Your Profile</h1>
            <p>Update your information to keep your profile current</p>
        </div>
        
        <div class="profile-card">
            <div class="card-header">
                <h2><i class="fas fa-user-graduate"></i> Student Information</h2>
            </div>
            
            <div class="card-body">
                <form method="POST" enctype="multipart/form-data" id="profileForm">
                    <div class="profile-img-section">
                        <img src="<?=$photoPath?>" alt="Profile Photo" class="profile-img" id="profileImage">
                        <div class="img-upload">
                            <label for="photoUpload" class="img-upload-label">
                                <i class="fas fa-camera"></i> Change Photo
                            </label>
                            <input type="file" id="photoUpload" name="photo" accept="image/*">
                        </div>
                    </div>
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="first_name"><i class="fas fa-signature"></i> First Name</label>
                            <input type="text" id="first_name" name="first_name" class="form-control" value="<?=h($student_details['first_name'])?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="last_name"><i class="fas fa-signature"></i> Last Name</label>
                            <input type="text" id="last_name" name="last_name" class="form-control" value="<?=h($student_details['last_name'])?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="father_name"><i class="fas fa-user-friends"></i> Father's Name</label>
                            <input type="text" id="father_name" name="father_name" class="form-control" value="<?=h($student_details['father_name'])?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="mother_name"><i class="fas fa-user-friends"></i> Mother's Name</label>
                            <input type="text" id="mother_name" name="mother_name" class="form-control" value="<?=h($student_details['mother_name'])?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="dob"><i class="fas fa-birthday-cake"></i> Date of Birth</label>
                            <input type="date" id="dob" name="dob" class="form-control" value="<?=h($student_details['dob'])?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="gender"><i class="fas fa-venus-mars"></i> Gender</label>
                            <select id="gender" name="gender" class="form-control" required>
                                <option value="Male" <?=($student_details['gender']=="Male"?"selected":"")?>>Male</option>
                                <option value="Female" <?=($student_details['gender']=="Female"?"selected":"")?>>Female</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="class"><i class="fas fa-graduation-cap"></i> Class</label>
                            <input type="text" id="class" name="class" class="form-control" value="<?=h($student_details['class'])?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="board"><i class="fas fa-school"></i> Board</label>
                            <input type="text" id="board" name="board" class="form-control" value="<?=h($student_details['board'])?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="school"><i class="fas fa-university"></i> School</label>
                            <input type="text" id="school" name="school" class="form-control" value="<?=h($student_details['school'])?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="previous_marks"><i class="fas fa-chart-line"></i> Previous Marks</label>
                            <input type="text" id="previous_marks" name="previous_marks" class="form-control" value="<?=h($student_details['previous_marks'])?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="parent_mobile"><i class="fas fa-phone"></i> Parent Mobile</label>
                            <input type="text" id="parent_mobile" name="parent_mobile" class="form-control" value="<?=h($student_details['parent_mobile'])?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="personal_mobile"><i class="fas fa-mobile-alt"></i> Personal Mobile</label>
                            <input type="text" id="personal_mobile" name="personal_mobile" class="form-control" value="<?=h($student_details['personal_mobile'])?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="whatsapp"><i class="fab fa-whatsapp"></i> WhatsApp</label>
                            <input type="text" id="whatsapp" name="whatsapp" class="form-control" value="<?=h($student_details['whatsapp'])?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="city"><i class="fas fa-city"></i> City</label>
                            <input type="text" id="city" name="city" class="form-control" value="<?=h($student_details['city'])?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="state"><i class="fas fa-map-marked"></i> State</label>
                            <input type="text" id="state" name="state" class="form-control" value="<?=h($student_details['state'])?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="pincode"><i class="fas fa-map-pin"></i> Pincode</label>
                            <input type="text" id="pincode" name="pincode" class="form-control" value="<?=h($student_details['pincode'])?>" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="address"><i class="fas fa-address-card"></i> Address</label>
                        <textarea id="address" name="address" class="form-control" required><?=h($student_details['address'])?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="reference"><i class="fas fa-handshake"></i> Reference</label>
                        <input type="text" id="reference" name="reference" class="form-control" value="<?=h($student_details['reference'])?>">
                    </div>
                    
                    <div class="action-buttons">
                        <button type="submit" class="btn btn-success"><i class="fas fa-save"></i> Update Profile</button>
                        <button type="button" class="btn btn-info" id="resetBtn"><i class="fas fa-undo"></i> Reset Changes</button>
                        <a href="dashboard.php" class="btn btn-danger"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <script>
        // Show image preview when a new file is selected
        document.getElementById('photoUpload').addEventListener('change', function(e) {
            if (e.target.files && e.target.files[0]) {
                var reader = new FileReader();
                
                reader.onload = function(e) {
                    document.getElementById('profileImage').src = e.target.result;
                }
                
                reader.readAsDataURL(e.target.files[0]);
            }
        });
        
        // Form reset functionality
        document.getElementById('resetBtn').addEventListener('click', function() {
            Swal.fire({
                title: 'Reset Form?',
                text: 'Are you sure you want to reset all changes?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#4e73df',
                cancelButtonColor: '#e74a3b',
                confirmButtonText: 'Yes, reset it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('profileForm').reset();
                    document.getElementById('profileImage').src = '<?=$photoPath?>';
                    
                    Swal.fire(
                        'Reset!',
                        'Your form has been reset.',
                        'success'
                    );
                }
            });
        });
        
        // Enhanced form submission
        document.getElementById('profileForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            Swal.fire({
                title: 'Update Profile?',
                text: 'Are you sure you want to update your profile information?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#1cc88a',
                cancelButtonColor: '#e74a3b',
                confirmButtonText: 'Yes, update it!',
                showLoaderOnConfirm: true,
                preConfirm: () => {
                    return new Promise((resolve) => {
                        // Submit the form after confirmation
                        setTimeout(() => {
                            document.getElementById('profileForm').submit();
                            resolve();
                        }, 1000);
                    });
                }
            });
        });
        
        // Show toast notifications based on PHP session messages
        <?php if(isset($_SESSION['success_message'])): ?>
            Swal.fire({
                icon: 'success',
                title: 'Success!',
                text: '<?=h($_SESSION['success_message'])?>',
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true
            });
            <?php unset($_SESSION['success_message']); ?>
        <?php endif; ?>
        
        <?php if(isset($_SESSION['error_message'])): ?>
            Swal.fire({
                icon: 'error',
                title: 'Error!',
                text: '<?=h($_SESSION['error_message'])?>',
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 5000,
                timerProgressBar: true
            });
            <?php unset($_SESSION['error_message']); ?>
        <?php endif; ?>
        
        <?php if(isset($_SESSION['info_message'])): ?>
            Swal.fire({
                icon: 'info',
                title: 'Info',
                text: '<?=h($_SESSION['info_message'])?>',
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true
            });
            <?php unset($_SESSION['info_message']); ?>
        <?php endif; ?>
        
        // Add animations to form elements
        document.addEventListener('DOMContentLoaded', function() {
            const formGroups = document.querySelectorAll('.form-group');
            
            formGroups.forEach((group, index) => {
                group.style.opacity = '0';
                group.style.transform = 'translateY(20px)';
                
                setTimeout(() => {
                    group.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
                    group.style.opacity = '1';
                    group.style.transform = 'translateY(0)';
                }, 100 + (index * 50));
            });
        });
    </script>
</body>
</html>