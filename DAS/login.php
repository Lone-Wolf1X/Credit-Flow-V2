<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Login - Document Automation System</title>
  
  <!-- Bootstrap 5 CDN -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Bootstrap Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
  <!-- Google Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

  <style>
    :root {
      --sbi-blue: #280071;
      --sbi-light-blue: #00b5ef;
      --bg-color: #f0f2f5;
    }

    body {
      font-family: 'Inter', sans-serif;
      background-color: var(--bg-color);
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      background-image: radial-gradient(circle at 10% 20%, rgb(242, 246, 252) 0%, rgb(235, 242, 255) 90%);
    }

    .login-card {
      border: none;
      border-radius: 16px;
      box-shadow: 0 10px 40px rgba(0, 0, 0, 0.08);
      overflow: hidden;
      width: 100%;
      max-width: 440px;
      background: #fff;
      transition: transform 0.3s ease;
    }

    .login-header {
      background-color: #fff;
      padding: 2.5rem 2rem 1.5rem;
      text-align: center;
    }

    .login-logo {
      height: 60px;
      width: auto;
      margin-bottom: 1rem;
      object-fit: contain;
    }

    .app-title {
      font-weight: 700;
      color: var(--sbi-blue);
      font-size: 1.25rem;
      margin: 0;
    }

    .app-subtitle {
      color: #6c757d;
      font-size: 0.875rem;
      margin-top: 0.5rem;
    }

    .login-body {
      padding: 2rem;
    }

    .form-floating > .form-control {
      border-radius: 8px;
      border-color: #dee2e6;
    }

    .form-floating > .form-control:focus {
      border-color: var(--sbi-light-blue);
      box-shadow: 0 0 0 0.25rem rgba(0, 181, 239, 0.15);
    }

    .btn-login {
      background-color: var(--sbi-blue);
      border-color: var(--sbi-blue);
      color: white;
      font-weight: 600;
      padding: 0.8rem;
      border-radius: 8px;
      font-size: 1rem;
      transition: all 0.3s ease;
    }

    .btn-login:hover {
      background-color: #1a004b;
      border-color: #1a004b;
      transform: translateY(-1px);
    }

    .input-group-text {
      background: transparent;
      border-left: none;
      border-color: #dee2e6;
      border-radius: 0 8px 8px 0;
      cursor: pointer;
    }
    
    .password-field {
      border-right: none;
      border-radius: 8px 0 0 8px !important;
    }

    .footer-links {
      font-size: 0.875rem;
      color: #6c757d;
      text-align: center;
      margin-top: 1.5rem;
    }

    .footer-links a {
      color: var(--sbi-blue);
      text-decoration: none;
      font-weight: 500;
    }

    .footer-links a:hover {
      text-decoration: underline;
    }
  </style>
</head>
<body>

  <div class="login-card">
    <div class="login-header">
      <img src="asstes/images/sbilogo.png" alt="SBI Logo" class="login-logo">
      <h1 class="app-title">Document Automation System</h1>
      <p class="app-subtitle">Secure Access Portal</p>
    </div>

    <div class="login-body">
      <?php 
      session_start();
      if(isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert" style="font-size: 0.9rem;">
          <i class="bi bi-exclamation-circle-fill me-2"></i> <?php echo $_SESSION['error']; ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['error']); ?>
      <?php endif; ?>

      <form id="loginForm" method="POST" action="auth.php">
        
        <div class="form-floating mb-3">
          <input type="text" class="form-control" id="username" name="username" placeholder="Staff ID (e.g. 100)" required>
          <label for="username">Staff ID (e.g. 100)</label>
        </div>

        <div class="input-group mb-4">
          <div class="form-floating flex-grow-1">
            <input type="password" class="form-control password-field" id="password" name="password" placeholder="Password" required>
            <label for="password">Password</label>
          </div>
          <span class="input-group-text" id="togglePassword">
            <i class="bi bi-eye-slash" id="toggleIcon"></i>
          </span>
        </div>

        <div class="d-flex justify-content-between align-items-center mb-4">
          <div class="form-check">
            <input class="form-check-input" type="checkbox" value="" id="rememberMe">
            <label class="form-check-label" for="rememberMe" style="font-size: 0.9rem; color: #495057;">
              Remember me
            </label>
          </div>
          <a href="#" class="text-decoration-none" style="font-size: 0.9rem; color: var(--sbi-blue);">Forgot password?</a>
        </div>

        <div class="d-grid">
          <button type="submit" class="btn btn-login">
            Sign In <i class="bi bi-arrow-right-short ms-1"></i>
          </button>
        </div>
      </form>

      <div class="footer-links">
        <p>&copy; <?php echo date("Y"); ?> Document Automation System<br>All rights reserved.</p>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

  <script>
    document.addEventListener("DOMContentLoaded", function () {
      const loginForm = document.getElementById("loginForm");
      const togglePassword = document.getElementById("togglePassword");
      const passwordInput = document.getElementById("password");
      const toggleIcon = document.getElementById("toggleIcon");

      // Form Validation
      loginForm.addEventListener("submit", function (e) {
        if (!loginForm.checkValidity()) {
          e.preventDefault();
          e.stopPropagation();
        }
        loginForm.classList.add('was-validated');
      });

      // Password Toggle
      togglePassword.addEventListener('click', function () {
        const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
        passwordInput.setAttribute('type', type);
        
        toggleIcon.classList.toggle('bi-eye');
        toggleIcon.classList.toggle('bi-eye-slash');
      });
    });
  </script>
</body>
</html>
