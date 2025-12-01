<?php
// login.php
session_start();
require_once "db.php";

$err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $err = "Please enter username and password.";
    } 
    // Hardcoded admin check
    elseif ($username === 'admin' && $password === 'admin1234') {
        session_regenerate_id(true);
        $_SESSION['user_id'] = 0;
        $_SESSION['username'] = 'admin';
        $_SESSION['user_type'] = 'Admin';
        header("Location: admin.php");
        exit;
    } 
    else {
        $stmt = $conn->prepare("SELECT user_id, username, password, user_type, account_status FROM users WHERE username = ? LIMIT 1");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($res && $res->num_rows === 1) {
            $user = $res->fetch_assoc();
            if (!password_verify($password, $user['password'])) {
                $err = "Incorrect password.";
            } else {
                if ($user['account_status'] === 'Pending') {
                    $err = "Your account is pending admin approval.";
                } elseif ($user['account_status'] === 'Declined') {
                    $err = "Your account was declined. Contact admin.";
                } else {
                    session_regenerate_id(true);
                    $_SESSION['user_id'] = $user['user_id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['user_type'] = $user['user_type'];

                    if ($user['user_type'] === 'Admin') {
                        header("Location: admin.php");
                        exit;
                    } elseif ($user['user_type'] === 'CharitableInstitution') {
                        header("Location: charity.php");
                        exit;
                    } else {
                        header("Location: donator_dashboard.php");
                        exit;
                    }
                }
            }
        } else {
            $err = "User not found.";
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Login — ZeroHunger</title>
<style>
@import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');

:root {
  --pastel-purple: #E6D5F5;
  --pastel-purple-light: #F3EBFC;
  --purple-primary: #B794F6;
  --purple-dark: #9F7AEA;
  --purple-accent: #D6BCFA;
  --white: #FFFFFF;
  --text-primary: #4A5568;
  --text-secondary: #718096;
  --shadow-sm: 0 2px 8px rgba(183, 148, 246, 0.08);
  --shadow-md: 0 4px 16px rgba(183, 148, 246, 0.12);
  --shadow-lg: 0 10px 40px rgba(183, 148, 246, 0.18);
  --radius: 16px;
  --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
}

body {
  font-family: 'Poppins', sans-serif;
  background: linear-gradient(135deg, #F3EBFC 0%, #E6D5F5 50%, #D6BCFA 100%);
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 20px;
  position: relative;
  overflow: hidden;
}

/* Animated background shapes */
body::before,
body::after {
  content: '';
  position: absolute;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.1);
  animation: float 20s infinite ease-in-out;
}

body::before {
  width: 400px;
  height: 400px;
  top: -100px;
  left: -100px;
  animation-delay: 0s;
}

body::after {
  width: 300px;
  height: 300px;
  bottom: -80px;
  right: -80px;
  animation-delay: 5s;
}

@keyframes float {
  0%, 100% {
    transform: translate(0, 0) scale(1);
  }
  33% {
    transform: translate(30px, -50px) scale(1.1);
  }
  66% {
    transform: translate(-20px, 30px) scale(0.9);
  }
}

.container {
  max-width: 450px;
  width: 100%;
  position: relative;
  z-index: 1;
  animation: slideUp 0.6s ease-out;
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

.card {
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(20px);
  border-radius: var(--radius);
  padding: 48px 40px;
  box-shadow: var(--shadow-lg);
  border: 1px solid rgba(255, 255, 255, 0.8);
  transition: var(--transition);
}

.card:hover {
  transform: translateY(-2px);
  box-shadow: 0 15px 50px rgba(183, 148, 246, 0.25);
}

/* Logo section */
.logo-container {
  text-align: center;
  margin-bottom: 32px;
  animation: fadeIn 0.8s ease-out 0.2s both;
}

.logo-wrapper {
  width: 100px;
  height: 100px;
  margin: 0 auto 20px;
  border-radius: 50%;
  background: linear-gradient(135deg, var(--purple-primary), var(--purple-dark));
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: var(--shadow-md);
  transition: var(--transition);
  position: relative;
  overflow: hidden;
}

.logo-wrapper:hover {
  transform: scale(1.05) rotate(5deg);
  box-shadow: var(--shadow-lg);
}

.logo-wrapper img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  border-radius: 50%;
}

.logo-placeholder {
  font-size: 48px;
  color: var(--white);
  font-weight: 600;
}

@keyframes fadeIn {
  from {
    opacity: 0;
    transform: scale(0.9);
  }
  to {
    opacity: 1;
    transform: scale(1);
  }
}

h1 {
  text-align: center;
  color: var(--purple-dark);
  font-size: 28px;
  font-weight: 600;
  margin-bottom: 8px;
  animation: fadeIn 0.8s ease-out 0.3s both;
}

.subtitle {
  text-align: center;
  color: var(--text-secondary);
  font-size: 14px;
  font-weight: 400;
  margin-bottom: 32px;
  animation: fadeIn 0.8s ease-out 0.4s both;
}

.form-group {
  margin-bottom: 24px;
  animation: fadeIn 0.8s ease-out 0.5s both;
}

label {
  display: block;
  color: var(--text-primary);
  font-size: 14px;
  font-weight: 500;
  margin-bottom: 8px;
  transition: var(--transition);
}

.input-wrapper {
  position: relative;
}

.input-icon {
  position: absolute;
  left: 16px;
  top: 50%;
  transform: translateY(-50%);
  color: var(--purple-primary);
  font-size: 18px;
  transition: var(--transition);
}

input[type="text"],
input[type="password"] {
  width: 100%;
  padding: 14px 16px 14px 46px;
  border: 2px solid var(--pastel-purple);
  border-radius: 12px;
  font-size: 15px;
  color: var(--text-primary);
  background: var(--white);
  transition: var(--transition);
  font-family: 'Poppins', sans-serif;
}

input[type="text"]:focus,
input[type="password"]:focus {
  outline: none;
  border-color: var(--purple-primary);
  background: var(--pastel-purple-light);
  box-shadow: 0 0 0 4px rgba(183, 148, 246, 0.1);
  transform: translateY(-2px);
}

input[type="text"]:focus + .input-icon,
input[type="password"]:focus + .input-icon {
  color: var(--purple-dark);
  transform: translateY(-50%) scale(1.1);
}

.btn {
  width: 100%;
  padding: 14px 24px;
  background: linear-gradient(135deg, var(--purple-primary), var(--purple-dark));
  color: var(--white);
  border: none;
  border-radius: 12px;
  font-size: 16px;
  font-weight: 600;
  cursor: pointer;
  transition: var(--transition);
  box-shadow: var(--shadow-md);
  margin-top: 8px;
  font-family: 'Poppins', sans-serif;
  position: relative;
  overflow: hidden;
}

.btn::before {
  content: '';
  position: absolute;
  top: 0;
  left: -100%;
  width: 100%;
  height: 100%;
  background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
  transition: left 0.5s;
}

.btn:hover::before {
  left: 100%;
}

.btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 25px rgba(183, 148, 246, 0.4);
}

.btn:active {
  transform: translateY(0);
}

.register-link {
  text-align: center;
  margin-top: 24px;
  color: var(--text-secondary);
  font-size: 14px;
  animation: fadeIn 0.8s ease-out 0.6s both;
}

.register-link a {
  color: var(--purple-dark);
  text-decoration: none;
  font-weight: 600;
  transition: var(--transition);
}

.register-link a:hover {
  color: var(--purple-primary);
  text-decoration: underline;
}

.msg {
  padding: 14px 16px;
  border-radius: 12px;
  margin-bottom: 24px;
  font-size: 14px;
  animation: shake 0.5s ease-in-out, fadeIn 0.5s ease-out;
}

.msg.error {
  background: linear-gradient(135deg, #FED7D7, #FEB2B2);
  color: #C53030;
  border: 1px solid #FC8181;
}

.msg.success {
  background: linear-gradient(135deg, #C6F6D5, #9AE6B4);
  color: #22543D;
  border: 1px solid #68D391;
}

@keyframes shake {
  0%, 100% { transform: translateX(0); }
  10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
  20%, 40%, 60%, 80% { transform: translateX(5px); }
}

/* Floating particles */
.particle {
  position: absolute;
  width: 8px;
  height: 8px;
  background: rgba(183, 148, 246, 0.3);
  border-radius: 50%;
  animation: rise 15s infinite ease-in-out;
}

.particle:nth-child(1) { left: 10%; animation-delay: 0s; }
.particle:nth-child(2) { left: 20%; animation-delay: 2s; }
.particle:nth-child(3) { left: 30%; animation-delay: 4s; }
.particle:nth-child(4) { left: 40%; animation-delay: 1s; }
.particle:nth-child(5) { left: 50%; animation-delay: 3s; }
.particle:nth-child(6) { left: 60%; animation-delay: 5s; }
.particle:nth-child(7) { left: 70%; animation-delay: 2.5s; }
.particle:nth-child(8) { left: 80%; animation-delay: 4.5s; }
.particle:nth-child(9) { left: 90%; animation-delay: 1.5s; }

@keyframes rise {
  0% {
    bottom: -20px;
    opacity: 0;
    transform: translateX(0) scale(0);
  }
  10% {
    opacity: 1;
  }
  90% {
    opacity: 1;
  }
  100% {
    bottom: 100vh;
    opacity: 0;
    transform: translateX(100px) scale(1.5);
  }
}

@media (max-width: 576px) {
  .card {
    padding: 36px 24px;
  }
  
  h1 {
    font-size: 24px;
  }
  
  .logo-wrapper {
    width: 80px;
    height: 80px;
  }
}
</style>
</head>
<body>
<!-- Floating particles -->
<div class="particle"></div>
<div class="particle"></div>
<div class="particle"></div>
<div class="particle"></div>
<div class="particle"></div>
<div class="particle"></div>
<div class="particle"></div>
<div class="particle"></div>
<div class="particle"></div>

<div class="container">
  <div class="card">
    <div class="logo-container">
      <div class="logo-wrapper">
        <!-- Replace the src with your logo path: src="path/to/your/logo.png" -->
        <img id="logoImg" src="" alt="Logo" style="display: none;">
        <div class="logo-placeholder" id="logoPlaceholder">ZH</div>
      </div>
    </div>

    <h1>Welcome Back</h1>
    <p class="subtitle">Log in to manage donations and support the community</p>

    <?php if ($err): ?>
      <div class="msg error"><?= htmlspecialchars($err) ?></div>
    <?php endif; ?>

    <form method="post" novalidate>
      <div class="form-group">
        <label for="username">Username</label>
        <div class="input-wrapper">
          <input 
            id="username" 
            name="username" 
            type="text" 
            required 
            value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
            placeholder="Enter your username"
          >
          <span class="input-icon">👤</span>
        </div>
      </div>

      <div class="form-group">
        <label for="password">Password</label>
        <div class="input-wrapper">
          <input 
            id="password" 
            name="password" 
            type="password" 
            required
            placeholder="Enter your password"
          >
          <span class="input-icon">🔒</span>
        </div>
      </div>

      <button class="btn" type="submit">Login</button>
    </form>

    <div class="register-link">
      Don't have an account? <a href="register.php">Register here</a>
    </div>
  </div>
</div>

<script>
// Logo handling - Update the src path to your logo image
document.addEventListener('DOMContentLoaded', function() {
  const logoImg = document.getElementById('logoImg');
  const logoPlaceholder = document.getElementById('logoPlaceholder');
  
  // Replace 'path/to/your/logo.png' with your actual logo path
  // Example: 'images/logo.png' or 'assets/logo.png'
  const logoPath = 'images/logo.png'; // UPDATE THIS PATH
  
  logoImg.src = logoPath;
  
  logoImg.onload = function() {
    logoImg.style.display = 'block';
    logoPlaceholder.style.display = 'none';
  };
  
  logoImg.onerror = function() {
    // If logo fails to load, show placeholder
    logoImg.style.display = 'none';
    logoPlaceholder.style.display = 'flex';
  };
  
  // Add ripple effect on button click
  const btn = document.querySelector('.btn');
  btn.addEventListener('click', function(e) {
    const ripple = document.createElement('span');
    const rect = btn.getBoundingClientRect();
    const size = Math.max(rect.width, rect.height);
    const x = e.clientX - rect.left - size / 2;
    const y = e.clientY - rect.top - size / 2;
    
    ripple.style.width = ripple.style.height = size + 'px';
    ripple.style.left = x + 'px';
    ripple.style.top = y + 'px';
    ripple.style.position = 'absolute';
    ripple.style.borderRadius = '50%';
    ripple.style.background = 'rgba(255, 255, 255, 0.5)';
    ripple.style.transform = 'scale(0)';
    ripple.style.animation = 'ripple 0.6s ease-out';
    
    btn.appendChild(ripple);
    
    setTimeout(() => ripple.remove(), 600);
  });
});

// Add ripple animation
const style = document.createElement('style');
style.textContent = `
  @keyframes ripple {
    to {
      transform: scale(4);
      opacity: 0;
    }
  }
`;
document.head.appendChild(style);
</script>
</body>
</html>