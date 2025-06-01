<?php
require_once 'config.php';


$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    // Validation
    if (empty($username)) {
        $errors[] = "Username is required";
    }

    if (empty($password)) {
        $errors[] = "Password is required";
    }

    if (empty($errors)) {
        $stmt = $conn->prepare("SELECT * FROM admins WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $admin = $result->fetch_assoc();
            if (password_verify($password, $admin['password'])) {
                // Set session variables
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_username'] = $admin['username'];
                
                // Redirect to admin dashboard
                header("Location: admin.php");
                exit();
            } else {
                $errors[] = "Invalid password";
            }
        } else {
            $errors[] = "Username not found";
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
  <title>Admin Login</title>
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
    
    .register-link {
      text-align: center;
      margin-top: 20px;
    }
    
    .register-link a {
      color: #4CAF50;
      text-decoration: none;
      font-weight: bold;
    }
    
    .remember-forgot {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 20px;
    }
    
    .remember-me {
      display: flex;
      align-items: center;
    }
    
    .remember-me input {
      margin-right: 8px;
    }
    
    .forgot-password a {
      color: #4CAF50;
      text-decoration: none;
    }
  </style>
</head>
<body>
  <div class="container">
    <div class="card">
      <h2><i class="fas fa-user-shield"></i> Admin Login</h2>
      
      <?php if (!empty($errors)): ?>
        <div class="error-message">
          <?php foreach ($errors as $error): ?>
            <p><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></p>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
      
      <form method="POST" action="">
        <div class="form-group">
          <label for="username">Username</label>
          <input type="text" id="username" name="username" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required>
        </div>
        
        <div class="form-group">
          <label for="password">Password</label>
          <input type="password" id="password" name="password" required>
        </div>
        
        <div class="remember-forgot">
          <div class="remember-me">
            <input type="checkbox" id="remember" name="remember">
            <label for="remember">Remember me</label>
          </div>
          <div class="forgot-password">
            <a href="#">Forgot password?</a>
          </div>
        </div>
        
        <button type="submit" class="submit-btn">
          <i class="fas fa-sign-in-alt"></i> Login
        </button>
      </form>
      
      <div class="register-link">
        Don't have an account? <a href="register.php">Register here</a>
      </div>
    </div>
  </div>
</body>
</html>