<?php
session_start();
require '../db.php'; // adjust path if needed

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $ip_address = $_SERVER['REMOTE_ADDR']; // Get user's IP

    // Function to show SweetAlert
    function showAlert($icon, $title, $text, $redirect = null){
        echo '<!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Login Alert</title>
            <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        </head>
        <body>
            <script>
                Swal.fire({
                    icon: "'.$icon.'",
                    title: "'.$title.'",
                    text: "'.$text.'",
                    timer: 2500,
                    timerProgressBar: true,
                    showConfirmButton: false
                }).then(() => {'; 
        if($redirect){
            echo "window.location.href = '$redirect';";
        } else {
            echo "window.history.back();";
        }
        echo '});
            </script>
        </body>
        </html>';
        exit;
    }

    // --- Check if email exists in signup table ---
    $stmt = $conn->prepare("SELECT * FROM signup WHERE BINARY email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if($result->num_rows == 0){
        showAlert('error', 'Email Not Found', 'This email is not registered in signup table.');
    }

    $user = $result->fetch_assoc();

    // Verify password (assuming hashed password in signup table)
    if(!password_verify($password, $user['password'])){
        showAlert('error', 'Incorrect Password', 'The password you entered is incorrect.');
    }

    // -----------------------------
    // CHECK STUDENT TABLE (English / Hindi)
    // -----------------------------
    $student = null;
    $medium = "";

    // Check English table
    $stmtEng = $conn->prepare("SELECT * FROM student_english WHERE email = ?");
    $stmtEng->bind_param("s", $email);
    $stmtEng->execute();
    $resEng = $stmtEng->get_result();
    if($resEng->num_rows > 0){
        $student = $resEng->fetch_assoc();
        $medium = "English";
    }

    // Check Hindi table (if not found in English)
    if(!$student){
        $stmtHin = $conn->prepare("SELECT * FROM student_hindi WHERE email = ?");
        $stmtHin->bind_param("s", $email);
        $stmtHin->execute();
        $resHin = $stmtHin->get_result();
        if($resHin->num_rows > 0){
            $student = $resHin->fetch_assoc();
            $medium = "Hindi";
        }
    }

    if(!$student){
        showAlert('error', 'No Admission Found', 'You are not admitted in English or Hindi student list.');
    }

    // ✅ Fix name column
    $student_name = "";
    if(isset($student['name'])){
        $student_name = $student['name'];
    } elseif(isset($student['first_name']) && isset($student['last_name'])){
        $student_name = $student['first_name'] . ' ' . $student['last_name'];
    }

    // ✅ Set session for dashboard.php
    $_SESSION['student_id']     = $student['id'];
    $_SESSION['student_email']  = $student['email'];
    $_SESSION['student_name']   = $student_name;
    $_SESSION['student_medium'] = $medium;
    $_SESSION['student_class']  = $student['class'];
    $_SESSION['logged_in']      = true;

    // 👇 Extra session for complaints system
    $_SESSION['user_type']      = 'student';      // later if teacher login, set 'teacher'
    $_SESSION['user_id']        = $student['id']; // link to complaint table

    // --- Insert/update login table ---
    $stmt = $conn->prepare("SELECT * FROM login WHERE BINARY email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    $status = 'success';
    if($result->num_rows > 0){
        // Update existing login record
        $stmt = $conn->prepare("UPDATE login SET status = ?, ip_address = ?, login_time = NOW() WHERE BINARY email = ?");
        $stmt->bind_param("sss", $status, $ip_address, $email);
        $stmt->execute();
        $stmt->close();
    } else {
        // Insert new login record
        $stmt = $conn->prepare("INSERT INTO login (email, status, ip_address, login_time) VALUES (?, ?, ?, NOW())");
        $stmt->bind_param("sss", $email, $status, $ip_address);
        $stmt->execute();
        $stmt->close();
    }

    // 👇 Redirect to dashboard.php
    showAlert('success', 'Login Successful', 'Welcome back '.$student_name.'!', 'dashboard.php');
}
?>
