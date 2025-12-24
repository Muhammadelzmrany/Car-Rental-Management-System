<?php
/**
 * Login Processing
 * معالجة تسجيل الدخول
 */

require_once '../includes/functions.php';

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check CSRF token
if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
    $error = urlencode("Security token mismatch. Please try again.");
    header("Location: loginview.php?show=signin&signinerror=" . $error);
    exit;
}

// Get and sanitize input
$email = isset($_POST["email"]) ? sanitize_input($_POST["email"]) : '';
$password = isset($_POST["password"]) ? $_POST["password"] : '';

// Validate input
if (empty($email) || empty($password)) {
    $error = urlencode("Please fill in all fields.");
    header("Location: loginview.php?show=signin&signinerror=" . $error);
    exit;
}

// Validate email format
if (!validate_email($email)) {
    $error = urlencode("Invalid email format.");
    header("Location: loginview.php?show=signin&signinerror=" . $error);
    exit;
}

// Get database connection
require_once '../includes/db.php';

// Initialize login attempts tracking in session
if (!isset($_SESSION['login_attempts'])) {
    $_SESSION['login_attempts'] = [];
}

// Check lockout for this email
$attempts = $_SESSION['login_attempts'][$email]['count'] ?? 0;
$last_attempt = $_SESSION['login_attempts'][$email]['last_time'] ?? 0;
if ($attempts >= MAX_LOGIN_ATTEMPTS && (time() - $last_attempt) < LOGIN_LOCKOUT_TIME) {
    $wait = LOGIN_LOCKOUT_TIME - (time() - $last_attempt);
    $error = urlencode("Too many failed attempts. Try again in $wait seconds.");
    header("Location: loginview.php?show=signin&signinerror=" . $error);
    exit;
}

// Prepare and execute query
$sql = "SELECT id, name, email, password, isadmin FROM users WHERE email = ?";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    log_error("Login prepare failed: " . $conn->error, __FILE__, __LINE__);
    $error = urlencode("Database error. Please try again later.");
    header("Location: loginview.php?show=signin&signinerror=" . $error);
    exit;
}

$stmt->bind_param("s", $email);

try {
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    if (empty($row)) {
        // Increment failed attempts
        $_SESSION['login_attempts'][$email]['count'] = $attempts + 1;
        $_SESSION['login_attempts'][$email]['last_time'] = time();

        $error = urlencode("Invalid email or password.");
        header("Location: loginview.php?show=signin&signinerror=" . $error);
        $stmt->close();
        exit;
    }
    
    // Verify password
    if (!password_verify($password, $row['password'])) {
        // Increment failed attempts
        $_SESSION['login_attempts'][$email]['count'] = $attempts + 1;
        $_SESSION['login_attempts'][$email]['last_time'] = time();

        $error = urlencode("Invalid email or password.");
        header("Location: loginview.php?show=signin&signinerror=" . $error);
        $stmt->close();
        exit;
    }
    
    // Regenerate session ID for security
    session_regenerate_id(true);
    
    // Set session variables
    $_SESSION["id"] = $row['id'];
    $_SESSION["userName"] = $row['name'];
    $_SESSION["isAdmin"] = isset($row['isadmin']) ? (bool)$row['isadmin'] : false;
    // Reset login attempts on successful login
    if (isset($_SESSION['login_attempts'][$email])) {
        unset($_SESSION['login_attempts'][$email]);
    }
    
    // Close statement
    $stmt->close();
    
    // Redirect based on user type
    if ($_SESSION["isAdmin"]) {
        header("Location: ../admin/index.php");
    } else {
        header("Location: ../index.php");
    }
    exit;
    
} catch (Exception $e) {
    log_error("Login error: " . $e->getMessage(), __FILE__, __LINE__);
    $error = urlencode("An error occurred. Please try again later.");
    header("Location: loginview.php?show=signin&signinerror=" . $error);
    if (isset($stmt)) {
        $stmt->close();
    }
    exit;
}
