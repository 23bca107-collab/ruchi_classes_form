<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Teacher Profile | Ruchi Classes</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    :root {
      --primary: #3498db;
      --secondary: #2980b9;
      --accent: #e74c3c;
      --light: #f5f7fa;
      --dark: #2c3e50;
      --success: #27ae60;
      --gray: #ecf0f1;
    }
    
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }
    
    body {
      background: linear-gradient(135deg, var(--primary), var(--secondary));
      color: var(--dark);
      min-height: 100vh;
      display: flex;
      justify-content: center;
      align-items: center;
      padding: 20px;
    }
    
    .container {
      display: flex;
      max-width: 1000px;
      width: 100%;
      background: white;
      border-radius: 15px;
      overflow: hidden;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
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
    }
    
    .logo {
      width: 120px;
      height: 120px;
      border-radius: 50%;
      background: white;
      display: flex;
      align-items: center;
      justify-content: center;
      margin-bottom: 20px;
      box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
    }
    
    .logo i {
      font-size: 50px;
      color: var(--primary);
    }
    
    .brand-section h1 {
      font-size: 28px;
      margin-bottom: 10px;
      font-weight: 700;
    }
    
    .brand-section p {
      font-size: 16px;
      opacity: 0.9;
      line-height: 1.6;
    }
    
    .form-section {
      flex: 1.5;
      padding: 40px;
    }
    
    .form-header {
      text-align: center;
      margin-bottom: 30px;
    }
    
    .form-header h2 {
      color: var(--dark);
      font-size: 28px;
      margin-bottom: 10px;
      position: relative;
      display: inline-block;
    }
    
    .form-header h2:after {
      content: '';
      position: absolute;
      bottom: -10px;
      left: 50%;
      transform: translateX(-50%);
      width: 50px;
      height: 3px;
      background: var(--primary);
    }
    
    .form-header p {
      color: #666;
      font-size: 16px;
    }
    
    .form-group {
      margin-bottom: 20px;
    }
    
    .form-row {
      display: flex;
      gap: 15px;
    }
    
    .form-row .form-group {
      flex: 1;
    }
    
    label {
      display: block;
      margin-bottom: 8px;
      font-weight: 600;
      color: var(--dark);
    }
    
    input, textarea, select {
      width: 100%;
      padding: 12px 15px;
      border: 2px solid #e1e5eb;
      border-radius: 8px;
      font-size: 16px;
      transition: all 0.3s;
    }
    
    input:focus, textarea:focus, select:focus {
      border-color: var(--primary);
      outline: none;
      box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.2);
    }
    
    .file-input-wrapper {
      position: relative;
      overflow: hidden;
      display: inline-block;
      width: 100%;
    }
    
    .file-input-wrapper input[type=file] {
      position: absolute;
      left: 0;
      top: 0;
      opacity: 0;
      width: 100%;
      height: 100%;
      cursor: pointer;
    }
    
    .file-input-button {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 10px;
      padding: 12px 15px;
      background: var(--light);
      border: 2px dashed #c8d0e0;
      border-radius: 8px;
      color: #666;
      font-size: 16px;
      transition: all 0.3s;
    }
    
    .file-input-button:hover {
      border-color: var(--primary);
      color: var(--primary);
    }
    
    .submit-btn {
      background: linear-gradient(to right, var(--primary), var(--secondary));
      color: white;
      border: none;
      padding: 15px;
      font-size: 18px;
      font-weight: 600;
      border-radius: 8px;
      cursor: pointer;
      transition: all 0.3s;
      width: 100%;
      margin-top: 10px;
      box-shadow: 0 4px 15px rgba(52, 152, 219, 0.3);
    }
    
    .submit-btn:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 20px rgba(52, 152, 219, 0.4);
    }
    
    .submit-btn:active {
      transform: translateY(0);
    }
    
    .form-footer {
      text-align: center;
      margin-top: 20px;
      color: #666;
      font-size: 14px;
    }
    
    @media (max-width: 768px) {
      .container {
        flex-direction: column;
      }
      
      .brand-section {
        padding: 30px 20px;
      }
      
      .form-section {
        padding: 30px 20px;
      }
      
      .form-row {
        flex-direction: column;
        gap: 0;
      }
    }
  </style>
</head>
<body>
  <div class="container">
    <div class="brand-section">
      <div class="logo">
        <i class="fas fa-chalkboard-teacher"></i>
      </div>
      <h1>Ruchi Classes</h1>
      <p>Complete your teacher profile to get started with our advanced teaching platform. Join our community of dedicated educators.</p>
    </div>
    
    <div class="form-section">
      <div class="form-header">
        <h2>Complete Your Profile</h2>
        <p>Fill in your details to create your teacher account</p>
      </div>
      
      <form action="teacher_profile_save.php" method="POST" enctype="multipart/form-data">
        <div class="form-row">
          <div class="form-group">
            <label for="first_name">First Name</label>
            <input type="text" id="first_name" name="first_name" placeholder="Enter your first name" required>
          </div>
          <div class="form-group">
            <label for="last_name">Last Name</label>
            <input type="text" id="last_name" name="last_name" placeholder="Enter your last name" required>
          </div>
        </div>
        
        <div class="form-group">
          <label for="mobile">Mobile Number</label>
          <input type="text" id="mobile" name="mobile" placeholder="Enter your mobile number" required>
        </div>
        
        <div class="form-group">
          <label for="address">Address</label>
          <textarea id="address" name="address" rows="3" placeholder="Enter your complete address" required></textarea>
        </div>
        
        <div class="form-group">
          <label for="subject">Subject You Teach</label>
          <select id="subject" name="subject" required>
            <option value="" disabled selected>Select your subject</option>
            <option value="Mathematics">Mathematics</option>
            <option value="Science">Science</option>
            <option value="English">English</option>
            <option value="Social Studies">Social Studies</option>
            <option value="Computer Science">Computer Science</option>
            <option value="Physics">Physics</option>
            <option value="Chemistry">Chemistry</option>
            <option value="Biology">Biology</option>
            <option value="History">History</option>
            <option value="Geography">Geography</option>
            <option value="Other">Other</option>
          </select>
        </div>
        
        <div class="form-group">
          <label for="photo">Profile Photo</label>
          <div class="file-input-wrapper">
            <div class="file-input-button">
              <i class="fas fa-cloud-upload-alt"></i>
              <span>Choose Profile Photo</span>
            </div>
            <input type="file" id="photo" name="photo" accept="image/*" required>
          </div>
        </div>
        
        <button type="submit" class="submit-btn">
          <i class="fas fa-user-check"></i> Save Profile
        </button>
        
        <div class="form-footer">
          <p>Your information is secure and will only be used for educational purposes.</p>
        </div>
      </form>
    </div>
  </div>

  <script>
    // Update file input text to show selected file name
    document.getElementById('photo').addEventListener('change', function(e) {
      const fileName = e.target.files[0] ? e.target.files[0].name : 'Choose Profile Photo';
      document.querySelector('.file-input-button span').textContent = fileName;
    });
    
    // Add some animation to form elements
    document.addEventListener('DOMContentLoaded', function() {
      const formGroups = document.querySelectorAll('.form-group');
      formGroups.forEach((group, index) => {
        group.style.opacity = '0';
        group.style.transform = 'translateY(20px)';
        
        setTimeout(() => {
          group.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
          group.style.opacity = '1';
          group.style.transform = 'translateY(0)';
        }, index * 100);
      });
    });
  </script>
</body>
</html>