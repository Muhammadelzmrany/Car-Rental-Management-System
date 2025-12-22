<?php
/**
 * Login and Signup View
 * صفحة تسجيل الدخول والتسجيل
 */

require_once 'functions.php';

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Generate CSRF tokens
$login_csrf_token = generate_csrf_token();
$signup_csrf_token = generate_csrf_token();
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">

<head>
  <meta charset="UTF-8">
  <title>Login and Registration Form</title>
  <!-- Fontawesome CDN Link -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
  <link rel="stylesheet" href="login.css">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>

<body>
  <div class="container">
    <input type="checkbox" id="flip" <?= (isset($_GET['show']) && $_GET['show'] === 'signup') ? 'checked' : '' ?>>
    <div class="cover">
      <div class="front">
        <img src="img/log.png" alt="">
        <div class="text">
          <span class="text-1">Every drive begins <br> a new story</span>
          <span class="text-2">Let's hit the road together!</span>
        </div>
      </div>
      <div class="back">
        <img src="img/chat555555.webp" alt="">
        <div class="text">
          <span class="text-1">Turn every journey <br> into an experience</span>
          <span class="text-2">Start your adventure today!</span>
        </div>
      </div>
    </div>
    <div class="forms">
      <div class="form-content">
        <!-- Login Form -->
        <div class="login-form">
          <div class="title">Login</div>
          <form action="login.php" method="post">
            <input type="hidden" name="csrf_token" value="<?= escape_output($login_csrf_token) ?>">
            <div class="input-boxes">
              <div class="input-box">
                <i class="fas fa-envelope"></i>
                <input type="email" name="email" placeholder="Enter your email" required>
              </div>
              <div class="input-box">
                <i class="fas fa-lock"></i>
                <input type="password" name="password" placeholder="Enter your password" required>
              </div>
              <!--<div class="input-box">
                  <i class="fas fa-phone"></i>
                  <input type="number" placeholder="Enter your phone number" min="0" required>
                </div>-->
              <!-- Role Section -->
              <!--<div class="role-section">
                  <label>
                    <input type="radio" name="role" value="admin" required>
                    Admin
                  </label>
                  <label>
                    <input type="radio" name="role" value="customer" required>
                    Customer
                  </label>
                </div>-->

              <div class="text"><a href="#">Forgot password?</a></div>
              <div class="button input-box">
                <input type="submit" value="Submit">
              </div>
              <div class="text sign-up-text">Don't have an account? <label for="flip">SIGNUP NOW!</label>
                <p class="error"><?= escape_output(urldecode($_REQUEST['signinerror'] ?? '')) ?></p>
              </div>
            </div>
          </form>
        </div>

        <!-- Signup Form -->
        <div class="signup-form">
          <div class="title">Signup</div>
          <form action="signup.php" method="post">
            <input type="hidden" name="csrf_token" value="<?= escape_output($signup_csrf_token) ?>">
            <div class="input-boxes">
              <div class="input-box">
                <i class="fas fa-user"></i>
                <input type="text" name="name" placeholder="Enter your name" maxlength="50" required>
              </div>
              <div class="input-box">
                <i class="fas fa-envelope"></i>
                <input type="email" name="email" placeholder="Enter your email" maxlength="50" required>
              </div>
              <div class="input-box">
                <i class="fas fa-lock"></i>
                <input type="password" name="password" placeholder="Enter your password" maxlength="12" required>
              </div>
              <div class="input-box">
                <i class="fas fa-phone"></i>
                <input type="number" name="phone" placeholder="Enter your phone number" maxlength="11" required>
              </div>
              <div class="input-box">
                <i class="fas fa-map-marker-alt"></i>
                <input type="text" name="address" placeholder="Enter your address" maxlength="50" required>
              </div>


              <!-- Role Section -->
              <!--<div class="role-section">
                  <label>
                    <input type="radio" name="role" value="admin" required>
                    Admin
                  </label>
                  <label>
                    <input type="radio" name="role" value="customer" required>
                    Customer
                  </label>
                </div>-->

              <div class="button input-box">
                <input type="submit" value="Submit">
              </div>
              <div class="text sign-up-text">Already have an account? <label for="flip">LOGIN NOW!</label>
                <p class="error"><?= escape_output(urldecode($_REQUEST['signuperror'] ?? '')) ?></p>
              </div>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</body>

</html>