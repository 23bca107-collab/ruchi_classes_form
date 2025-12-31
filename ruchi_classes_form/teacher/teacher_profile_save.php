<?php
session_start();

// Check if teacher is logged in
if (!isset($_SESSION['teacher_logged_in']) || $_SESSION['teacher_logged_in'] !== true) {
    header("Location: teacher_login.php");
    exit;
}

// Database connection
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "ruchi_classes";

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $teacher_id = $_SESSION['teacher_id']; // Logged-in teacher ID
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $mobile = trim($_POST['mobile'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $subject = trim($_POST['subject'] ?? '');

    // Photo upload
    $photo_path = ""; // Default empty
    if (!empty($_FILES['photo']['name'])) {
        $upload_dir = __DIR__ . "/teacher/uploads/"; // Save under teacher folder
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

        $ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg','jpeg','png','gif','webp'];

        if (in_array($ext, $allowed)) {
            $file_name = time() . "_" . $teacher_id . "." . $ext;
            $target_file = $upload_dir . $file_name;

            if (move_uploaded_file($_FILES['photo']['tmp_name'], $target_file)) {
                // Path to store in DB (relative to project root)
                $photo_path = "teacher/uploads/" . $file_name;
            }
        }
    }

    // Update teacher profile only once
    $sql = "UPDATE teachers 
            SET first_name=?, last_name=?, mobile=?, address=?, subject=?, photo=?, profile_completed=1 
            WHERE id=? AND profile_completed=0"; // Only update if first time

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssssi", $first_name, $last_name, $mobile, $address, $subject, $photo_path, $teacher_id);

    if ($stmt->execute()) {
        header("Location: teacher_dashboard.php"); // Redirect after successful submission
        exit;
    } else {
        echo "❌ Error: " . $conn->error;
    }
}
?>
