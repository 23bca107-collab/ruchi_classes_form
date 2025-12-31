<?php
session_start();

// Database connection
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "ruchi_classes";

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$error = "";
$success = "";
$alertType = ""; // success / error / warning / info

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    // Validate empty fields
    if (empty($email)) {
        $error = "Email Address is required!";
        $alertType = "warning";
    } elseif (empty($password)) {
        $error = "Password is required!";
        $alertType = "warning";
    } else {
        // Check if teacher exists
        $stmt = $conn->prepare("SELECT * FROM teachers WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $teacher = $result->fetch_assoc();

            // First-time login: password is empty in DB
            if (empty($teacher['password'])) {
                $stmt2 = $conn->prepare("UPDATE teachers SET password=? WHERE id=?");
                $stmt2->bind_param("si", $password, $teacher['id']);
                $stmt2->execute();

                $_SESSION['teacher_logged_in'] = true;
                $_SESSION['teacher_id'] = $teacher['id'];
                $_SESSION['teacher_email'] = $teacher['email'];
                $_SESSION['first_login'] = true;

                $success = "Welcome! Please complete your profile.";
                $alertType = "success";

                echo "<script>
                    setTimeout(function(){
                        window.location.href='teacher_profile.php';
                    },2000);
                </script>";
            }
            // Normal login
            elseif ($password === $teacher['password']) {
                $_SESSION['teacher_logged_in'] = true;
                $_SESSION['teacher_id'] = $teacher['id'];
                $_SESSION['teacher_email'] = $teacher['email'];

                if ($teacher['profile_completed'] == 0) {
                    $success = "Login successful! Please complete your profile.";
                    $alertType = "info";

                    echo "<script>
                        setTimeout(function(){
                            window.location.href='teacher_profile.php';
                        },2000);
                    </script>";
                } else {
                    $success = "Login successful! Redirecting to dashboard...";
                    $alertType = "success";

                    echo "<script>
                        setTimeout(function(){
                            window.location.href='teacher_dashboard.php';
                        },2000);
                    </script>";
                }
            } else {
                $error = "Incorrect Password! Try again.";
                $alertType = "error";
            }
        } else {
            $error = "Teacher not found with this Email!";
            $alertType = "error";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Teacher Login | Ruchi Classes</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
:root {
    --primary: #2c3e50;
    --secondary: #34495e;
    --accent: #3498db;
    --success: #27ae60;
    --warning: #f39c12;
    --danger: #e74c3c;
    --light: #ecf0f1;
    --dark: #2c3e50;
    --gray: #95a5a6;
    --border-radius: 8px;
    --box-shadow: 0 8px 30px rgba(0,0,0,0.12);
    --transition: all 0.3s ease;
}

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background: linear-gradient(135deg, #f5f7fa 0%, #e4e8f0 100%);
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 100vh;
    padding: 20px;
}

.login-container {
    display: flex;
    max-width: 1000px;
    width: 100%;
    background: white;
    border-radius: var(--border-radius);
    overflow: hidden;
    box-shadow: var(--box-shadow);
}

.brand-section {
    flex: 1;
    background: linear-gradient(to bottom right, var(--primary), var(--secondary));
    color: white;
    padding: 40px;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    text-align: center;
    position: relative;
}

.brand-logo {
    width: 150px;
    height: 150px;
    margin-bottom: 25px;
    border-radius: 12px;
    overflow: hidden;
    display: flex;
    justify-content: center;
    align-items: center;
    background: white;
    padding: 8px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.2);
    border: 3px solid rgba(255,255,255,0.3);
}

.brand-logo img {
    width: 100%;
    height: 100%;
    object-fit: contain;
    border-radius: 6px;
}

.brand-section h1 {
    font-size: 32px;
    margin-bottom: 15px;
    font-weight: 700;
    letter-spacing: 0.5px;
}

.brand-section p {
    opacity: 0.9;
    line-height: 1.7;
    font-size: 16px;
    max-width: 350px;
}

.brand-section::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(to right, var(--accent), #2980b9);
}

.login-section {
    flex: 1;
    padding: 50px 40px;
    background: #fff;
}

.login-header {
    text-align: center;
    margin-bottom: 35px;
}

.login-header h2 {
    color: var(--primary);
    font-size: 32px;
    margin-bottom: 10px;
    font-weight: 600;
}

.login-header p {
    color: var(--gray);
    font-size: 16px;
}

.form-group {
    margin-bottom: 25px;
}

.form-group label {
    display: block;
    margin-bottom: 10px;
    font-weight: 600;
    color: var(--dark);
    font-size: 15px;
}

.input-with-icon {
    position: relative;
}

.input-with-icon i.fa-envelope,
.input-with-icon i.fa-lock {
    position: absolute;
    left: 15px;
    top: 50%;
    transform: translateY(-50%);
    color: var(--gray);
    font-size: 18px;
    z-index: 2;
}

.input-with-icon input {
    padding-left: 50px;
    padding-right: 50px;
}

.password-toggle {
    position: absolute;
    right: 12px;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    color: var(--gray);
    cursor: pointer;
    font-size: 16px;
    z-index: 2;
    transition: var(--transition);
    padding: 8px;
    border-radius: 4px;
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.password-toggle:hover {
    color: var(--accent);
    background: rgba(52, 152, 219, 0.1);
}

.password-toggle:focus {
    outline: none;
    box-shadow: 0 0 0 2px rgba(52, 152, 219, 0.3);
}

.form-control {
    width: 100%;
    padding: 14px 18px;
    border: 2px solid #e2e8f0;
    border-radius: var(--border-radius);
    font-size: 16px;
    transition: var(--transition);
    background: #fafbfc;
    position: relative;
    z-index: 1;
}

.form-control:focus {
    border-color: var(--accent);
    background: white;
    outline: none;
    box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.15);
}

.btn {
    display: block;
    width: 100%;
    padding: 15px;
    background: var(--primary);
    color: white;
    border: none;
    border-radius: var(--border-radius);
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    transition: var(--transition);
    letter-spacing: 0.5px;
    text-transform: uppercase;
    margin-top: 10px;
}

.btn:hover {
    background: var(--secondary);
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}

.btn:active {
    transform: translateY(0);
}

.forgot-password {
    text-align: right;
    margin-top: 12px;
}

.forgot-password a {
    color: var(--accent);
    text-decoration: none;
    font-size: 14px;
    transition: var(--transition);
    font-weight: 500;
}

.forgot-password a:hover {
    text-decoration: underline;
    color: #2980b9;
}

.powered-by {
    text-align: center;
    margin-top: 30px;
    padding-top: 20px;
    border-top: 1px solid #eee;
    color: var(--gray);
    font-size: 13px;
}

/* Logo fallback styling */
.logo-fallback {
    width: 100%;
    height: 100%;
    background: var(--accent);
    border-radius: 8px;
    display: flex;
    justify-content: center;
    align-items: center;
    color: white;
    font-size: 48px;
}

@media (max-width: 768px) {
    .login-container {
        flex-direction: column;
        max-width: 450px;
    }
    
    .brand-section {
        padding: 30px 25px;
    }
    
    .login-section {
        padding: 35px 25px;
    }
    
    .brand-logo {
        width: 120px;
        height: 120px;
    }
    
    .brand-section h1 {
        font-size: 26px;
    }
}

@media (max-width: 480px) {
    .brand-logo {
        width: 100px;
        height: 100px;
    }
    
    .login-section {
        padding: 25px 20px;
    }
    
    .password-toggle {
        right: 8px;
        width: 35px;
        height: 35px;
    }
    
    .input-with-icon input {
        padding-right: 45px;
    }
}
</style>
</head>
<body>

<div class="login-container">
    <div class="brand-section">
        <div class="brand-logo">
            <img src="../assets/Ruchi logo.jpg" alt="Ruchi Classes Logo" onerror="this.style.display='none'; document.getElementById('logoFallback').style.display='flex';">
            <div class="logo-fallback" id="logoFallback" style="display: none;">
                <i class="fas fa-graduation-cap"></i>
            </div>
        </div>
        <h1>Ruchi Classes</h1>
        <p>Empowering educators to shape the future. Access your teaching dashboard and manage your classes with professional tools.</p>
    </div>
    
    <div class="login-section">
        <div class="login-header">
            <h2>Teacher Portal</h2>
            <p>Welcome back! Please login to access your account</p>
        </div>
        
        <form method="POST" autocomplete="off" id="loginForm">
            <div class="form-group">
                <label for="email">Email Address</label>
                <div class="input-with-icon">
                    <i class="fas fa-envelope"></i>
                    <input type="email" class="form-control" id="email" name="email" placeholder="Enter your email address" required autocomplete="email" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                </div>
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <div class="input-with-icon">
                    <i class="fas fa-lock"></i>
                    <input type="password" class="form-control" id="password" name="password" placeholder="Enter your password" required autocomplete="current-password">
                    <button type="button" class="password-toggle" id="passwordToggle" aria-label="Toggle password visibility">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
                <div class="forgot-password">
                    <a href="forgot_password.php">Forgot Password?</a>
                </div>
            </div>
            
            <button type="submit" class="btn">Sign In</button>
        </form>
        
        <div class="powered-by">
            Ruchi Classes Teacher Management System
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    <?php if(!empty($error)): ?>
    Swal.fire({
        icon: '<?php echo $alertType; ?>',
        title: 'Login Alert',
        text: '<?php echo $error; ?>',
        confirmButtonColor: '#2c3e50',
        confirmButtonText: 'Try Again'
    });
    <?php endif; ?>

    <?php if(!empty($success)): ?>
    Swal.fire({
        icon: '<?php echo $alertType; ?>',
        title: 'Success',
        text: '<?php echo $success; ?>',
        confirmButtonColor: '#2c3e50',
        timer: 2000,
        showConfirmButton: false
    });
    <?php endif; ?>
    
    // Password toggle functionality
    const passwordToggle = document.getElementById('passwordToggle');
    const passwordInput = document.getElementById('password');
    const passwordIcon = passwordToggle.querySelector('i');
    
    passwordToggle.addEventListener('click', function() {
        const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
        passwordInput.setAttribute('type', type);
        
        // Toggle eye icon
        if (type === 'text') {
            passwordIcon.classList.remove('fa-eye');
            passwordIcon.classList.add('fa-eye-slash');
            passwordToggle.setAttribute('aria-label', 'Hide password');
            passwordToggle.title = 'Hide password';
        } else {
            passwordIcon.classList.remove('fa-eye-slash');
            passwordIcon.classList.add('fa-eye');
            passwordToggle.setAttribute('aria-label', 'Show password');
            passwordToggle.title = 'Show password';
        }
        
        // Keep focus on password field
        passwordInput.focus();
    });
    
    // Form validation
    const form = document.getElementById('loginForm');
    form.addEventListener('submit', function(e) {
        const email = document.getElementById('email').value.trim();
        const password = document.getElementById('password').value.trim();
        
        if (!email) {
            e.preventDefault();
            Swal.fire({
                icon: 'warning',
                title: 'Email Required',
                text: 'Please enter your email address',
                confirmButtonColor: '#2c3e50'
            });
            document.getElementById('email').focus();
            return;
        }
        
        if (!password) {
            e.preventDefault();
            Swal.fire({
                icon: 'warning',
                title: 'Password Required',
                text: 'Please enter your password',
                confirmButtonColor: '#2c3e50'
            });
            document.getElementById('password').focus();
            return;
        }
    });

    // Auto-focus on email field
    document.getElementById('email').focus();
});
</script>

</body>
</html>