<?php
session_start();
// Prevent caching
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

// Check if already logged in
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header("Location: admin_dashboard.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Login | Ruchi Classes</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
    body { display: flex; justify-content: center; align-items: center; min-height: 100vh; background: #f5f7fa; padding: 20px; }
    .container { width: 400px; max-width: 100%; background: white; border-radius: 10px; box-shadow: 0 5px 20px rgba(0,0,0,0.1); padding: 40px; }
    
    .logo { text-align: center; margin-bottom: 30px; }
    .logo h1 { color: #2c3e50; font-size: 28px; margin-bottom: 5px; }
    .logo span { color: #4a6fa5; }
    .logo p { color: #666; font-size: 14px; }
    
    .login-form { width: 100%; }
    .form-group { margin-bottom: 20px; }
    .form-group input { width: 100%; padding: 12px 15px; border: 1px solid #ddd; border-radius: 5px; font-size: 14px; }
    .form-group input:focus { border-color: #4a6fa5; outline: none; }
    
    .login-btn { 
      width: 100%; 
      padding: 12px; 
      background: #4a6fa5; 
      border: none; 
      border-radius: 5px; 
      color: white; 
      font-size: 14px; 
      font-weight: 600; 
      cursor: pointer; 
      transition: background 0.3s; 
    }
    .login-btn:hover { background: #3a5a80; }
    .login-btn:disabled { background: #95a5a6; cursor: not-allowed; }
    
    .error { color: #e74c3c; font-size: 12px; margin-top: 5px; display: none; }
    
    .remember-forgot { display: flex; justify-content: space-between; margin-bottom: 20px; font-size: 13px; }
    .remember-forgot a { color: #4a6fa5; text-decoration: none; }
    .remember-forgot a:hover { text-decoration: underline; }
    
    .security-notice { 
      background: #f8f9fa; 
      border: 1px solid #e9ecef; 
      border-radius: 5px; 
      padding: 10px; 
      text-align: center; 
      margin-top: 20px; 
      font-size: 12px; 
      color: #666;
    }
  </style>
</head>
<body>
  <div class="container">
    <div class="logo">
      <h1>Ruchi <span>Classes</span></h1>
      <p>Admin Login</p>
    </div>

    <form class="login-form" id="loginForm" method="POST">
      <div class="form-group">
        <input type="email" id="email" name="email" placeholder="Email Address" required>
        <div class="error" id="email-error"></div>
      </div>
      
      <div class="form-group">
        <input type="password" id="password" name="password" placeholder="Password" required>
        <div class="error" id="password-error"></div>
      </div>
      
      <div class="remember-forgot">
        <label><input type="checkbox" name="remember"> Remember me</label>
        <a href="#" id="forgot-password">Forgot Password?</a>
      </div>
      
      <button type="submit" class="login-btn" id="loginBtn">Login</button>

      <div class="security-notice">
        <i class="fas fa-shield-alt"></i> Secure access for authorized personnel only
      </div>
    </form>
  </div>

  <script>
    document.getElementById('loginForm').addEventListener('submit', function(e) {
      e.preventDefault();
      
      const email = document.getElementById('email').value.trim();
      const password = document.getElementById('password').value.trim();
      const btn = document.getElementById('loginBtn');
      
      // Reset errors
      document.querySelectorAll('.error').forEach(el => {
        el.style.display = 'none';
        el.textContent = '';
      });
      
      // Basic validation
      if (!email) {
        showError('email', 'Please enter email address');
        return;
      }
      
      if (!password) {
        showError('password', 'Please enter password');
        return;
      }
      
      // Disable button and show loading
      btn.disabled = true;
      btn.textContent = 'Logging in...';
      
      // Send AJAX request
      const formData = new FormData();
      formData.append('email', email);
      formData.append('password', password);
      
      fetch('admin_login_check.php', {
        method: 'POST',
        body: formData
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          Swal.fire({
            icon: 'success',
            title: 'Login Successful',
            text: data.message,
            confirmButtonColor: '#4a6fa5'
          }).then(() => {
            window.location.href = data.redirect;
          });
        } else {
          Swal.fire({
            icon: 'error',
            title: 'Login Failed',
            text: data.message,
            confirmButtonColor: '#e74c3c'
          });
        }
      })
      .catch(error => {
        Swal.fire({
          icon: 'error',
          title: 'Error',
          text: 'Network error occurred. Please try again.',
          confirmButtonColor: '#e74c3c'
        });
      })
      .finally(() => {
        btn.disabled = false;
        btn.textContent = 'Login';
      });
    });
    
    function showError(field, message) {
      const errorEl = document.getElementById(field + '-error');
      errorEl.textContent = message;
      errorEl.style.display = 'block';
      document.getElementById(field).focus();
    }
    
    // Forgot password handler
    document.getElementById('forgot-password').addEventListener('click', function(e) {
      e.preventDefault();
      Swal.fire({
        title: 'Password Recovery',
        text: 'Please contact system administrator at admin@ruchiclasses.com',
        icon: 'info',
        confirmButtonColor: '#4a6fa5'
      });
    });
  </script>
</body>
</html>