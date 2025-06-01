<?php
require_once 'config.php';

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate and sanitize inputs
    $full_name = trim($_POST['full_name']);
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Validation
    if (empty($full_name)) {
        $errors[] = "Full name is required";
    } elseif (strlen($full_name) > 100) {
        $errors[] = "Full name must be less than 100 characters";
    }

    if (empty($username)) {
        $errors[] = "Username is required";
    } elseif (strlen($username) < 4) {
        $errors[] = "Username must be at least 4 characters";
    } elseif (strlen($username) > 30) {
        $errors[] = "Username must be less than 30 characters";
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        $errors[] = "Username can only contain letters, numbers, and underscores";
    }

    if (empty($email)) {
        $errors[] = "Email is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    }

    if (empty($password)) {
        $errors[] = "Password is required";
    } elseif (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters";
    }

    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match";
    }

    // Check if username or email already exists
    if (empty($errors)) {
        $stmt = $conn->prepare("SELECT id FROM admins WHERE username = ? OR email = ?");
        $stmt->bind_param("ss", $username, $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $errors[] = "Username or email already exists";
        }
        $stmt->close();
    }

    // Create admin if no errors
    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        $stmt = $conn->prepare("INSERT INTO admins (username, email, password, full_name) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $username, $email, $hashed_password, $full_name);
        
        if ($stmt->execute()) {
            $success = true;
        } else {
            $errors[] = "Registration failed. Please try again.";
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Registration</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }
    
    body {
      background: linear-gradient(135deg, #1a2a6c, #b21f1f, #1a2a6c);
      color: #fff;
      min-height: 100vh;
      display: flex;
      justify-content: center;
      align-items: center;
      padding: 20px;
    }
    
    .container {
      max-width: 500px;
      width: 100%;
    }
    
    .card {
      background: rgba(255, 255, 255, 0.1);
      backdrop-filter: blur(10px);
      border-radius: 15px;
      padding: 30px;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
    }
    
    h2 {
      text-align: center;
      margin-bottom: 25px;
      font-size: 1.8rem;
    }
    
    .error-message {
      background: rgba(220, 53, 69, 0.3);
      padding: 15px;
      border-radius: 8px;
      margin-bottom: 20px;
      border: 2px solid #dc3545;
    }
    
    .success-message {
      background: rgba(40, 167, 69, 0.3);
      padding: 15px;
      border-radius: 8px;
      margin-bottom: 20px;
      text-align: center;
      border: 2px solid #28a745;
    }
    
    .form-group {
      margin-bottom: 20px;
    }
    
    .form-group label {
      display: block;
      margin-bottom: 8px;
      font-weight: 600;
      font-size: 1.1rem;
    }
    
    .form-group input {
      width: 100%;
      padding: 14px;
      border-radius: 8px;
      border: none;
      background: rgba(255, 255, 255, 0.15);
      color: white;
      font-size: 1rem;
    }
    
    .form-group input:focus {
      outline: 2px solid #4CAF50;
      background: rgba(255, 255, 255, 0.2);
    }
    
    .submit-btn {
      background: linear-gradient(to right, #4CAF50, #2E7D32);
      color: white;
      border: none;
      padding: 16px;
      border-radius: 8px;
      cursor: pointer;
      width: 100%;
      font-size: 1.1rem;
      font-weight: bold;
      transition: all 0.3s;
      margin-top: 10px;
      box-shadow: 0 4px 10px rgba(0,0,0,0.2);
    }
    
    .submit-btn:hover {
      transform: translateY(-3px);
      box-shadow: 0 6px 15px rgba(0,0,0,0.3);
    }
    
    .login-link {
      text-align: center;
      margin-top: 20px;
    }
    
    .login-link a {
      color: #4CAF50;
      text-decoration: none;
      font-weight: bold;
    }
    
    .password-strength {
      margin-top: 5px;
      height: 5px;
      background: rgba(255,255,255,0.1);
      border-radius: 3px;
      overflow: hidden;
    }
    
    .strength-meter {
      height: 100%;
      width: 0%;
      transition: width 0.3s, background 0.3s;
    }
  </style>
</head>
<body>
  <div class="container">
    <div class="card">
      <h2><i class="fas fa-user-shield"></i> Admin Registration</h2>
      
      <?php if ($success): ?>
        <div class="success-message">
          <p>Registration successful! You can now <a href="admin.php">login</a>.</p>
        </div>
      <?php else: ?>
        <?php if (!empty($errors)): ?>
          <div class="error-message">
            <?php foreach ($errors as $error): ?>
              <p><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></p>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
        
        <form method="POST" action="">
          <div class="form-group">
            <label for="full_name">Full Name</label>
            <input type="text" id="full_name" name="full_name" value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>" required>
          </div>
          
          <div class="form-group">
            <label for="username">Username</label>
            <input type="text" id="username" name="username" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required>
          </div>
          
          <div class="form-group">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
          </div>
          
          <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" required>
            <div class="password-strength">
              <div class="strength-meter" id="strength-meter"></div>
            </div>
          </div>
          
          <div class="form-group">
            <label for="confirm_password">Confirm Password</label>
            <input type="password" id="confirm_password" name="confirm_password" required>
          </div>
          
          <button type="submit" class="submit-btn">
            <i class="fas fa-user-plus"></i> Register
          </button>
        </form>
        
        <div class="login-link">
          Already have an account? <a href="login.php">Login here</a>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <script>
    // Password strength indicator
    document.getElementById('password').addEventListener('input', function() {
      const password = this.value;
      const strengthMeter = document.getElementById('strength-meter');
      let strength = 0;
      
      // Check for length
      if (password.length >= 8) strength += 1;
      if (password.length >= 12) strength += 1;
      
      // Check for character variety
      if (/[A-Z]/.test(password)) strength += 1;
      if (/[0-9]/.test(password)) strength += 1;
      if (/[^A-Za-z0-9]/.test(password)) strength += 1;
      
      // Update strength meter
      const width = strength * 20;
      let color;
      
      if (strength <= 2) {
        color = '#f44336'; // Red
      } else if (strength <= 4) {
        color = '#ff9800'; // Orange
      } else {
        color = '#4CAF50'; // Green
      }
      
      strengthMeter.style.width = `${width}%`;
      strengthMeter.style.background = color;
    });
  </script>
</body>
</html>