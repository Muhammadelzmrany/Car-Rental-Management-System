<?php
/**
 * Signup Processing
 * معالجة التسجيل
 */

require_once '../includes/functions.php';

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check CSRF token
if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
    $error = urlencode("Security token mismatch. Please try again.");
    header("Location: loginview.php?show=signup&signuperror=" . $error);
    exit;
}

// Get and sanitize input
$name = isset($_POST["name"]) ? sanitize_input(trim($_POST["name"])) : '';
$email = isset($_POST["email"]) ? sanitize_input(trim($_POST["email"])) : '';
$password = isset($_POST["password"]) ? $_POST["password"] : '';
$phone = isset($_POST["phone"]) ? sanitize_input(trim($_POST["phone"])) : '';
$address = isset($_POST["address"]) ? sanitize_input(trim($_POST["address"])) : '';

// Validate all fields are filled
if (empty($name) || empty($email) || empty($password) || empty($phone) || empty($address)) {
    $error = urlencode("Please fill in all fields.");
    header("Location: loginview.php?show=signup&signuperror=" . $error);
    exit;
}

// Validate email format
if (!validate_email($email)) {
    $error = urlencode("Invalid email format.");
    header("Location: loginview.php?show=signup&signuperror=" . $error);
    exit;
}

// Validate password strength
if (!validate_password($password)) {
    $error = urlencode("Password must be at least 8 characters long.");
    header("Location: loginview.php?show=signup&signuperror=" . $error);
    exit;
}

// Validate phone number
if (!validate_phone($phone)) {
    $error = urlencode("Invalid phone number format.");
    header("Location: loginview.php?show=signup&signuperror=" . $error);
    exit;
}

// Validate name length
if (strlen($name) > 100) {
    $error = urlencode("Name is too long (maximum 100 characters).");
    header("Location: loginview.php?show=signup&signuperror=" . $error);
    exit;
}

// Validate address length
if (strlen($address) > 255) {
    $error = urlencode("Address is too long (maximum 255 characters).");
    header("Location: loginview.php?show=signup&signuperror=" . $error);
    exit;
}

// Get database connection
require_once '../includes/db.php';

// Check if email already exists
$check_sql = "SELECT id FROM users WHERE email = ?";
$check_stmt = $conn->prepare($check_sql);

if (!$check_stmt) {
    log_error("Signup check prepare failed: " . $conn->error, __FILE__, __LINE__);
    $error = urlencode("Database error. Please try again later.");
    header("Location: loginview.php?show=signup&signuperror=" . $error);
    exit;
}

$check_stmt->bind_param("s", $email);
$check_stmt->execute();
$check_result = $check_stmt->get_result();

if ($check_result->num_rows > 0) {
    $error = urlencode("Email already registered. Please use a different email.");
    header("Location: loginview.php?show=signup&signuperror=" . $error);
    $check_stmt->close();
    exit;
}
$check_stmt->close();

// Hash the password for security
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

// Insert data into the database
$insert_sql = "INSERT INTO users (name, email, password, phone, address, isadmin) VALUES (?, ?, ?, ?, ?, 0)";
$stmt = $conn->prepare($insert_sql);

if (!$stmt) {
    log_error("Signup insert prepare failed: " . $conn->error, __FILE__, __LINE__);
    $error = urlencode("Database error. Please try again later.");
    header("Location: loginview.php?show=signup&signuperror=" . $error);
    exit;
}

$stmt->bind_param("sssss", $name, $email, $hashed_password, $phone, $address);

try {
    $stmt->execute();
    
    // Regenerate session ID for security
    session_regenerate_id(true);
    
    // Set session variables
    $_SESSION["id"] = $stmt->insert_id;
    $_SESSION["userName"] = $name;
    $_SESSION["isAdmin"] = false;
    
    $stmt->close();
    
    header("Location: ../index.php");
    exit;
    
} catch (Exception $e) {
    log_error("Signup error: " . $e->getMessage(), __FILE__, __LINE__);
    
    // Check if error is due to duplicate email (race condition)
    if (strpos($e->getMessage(), 'Duplicate') !== false || strpos($e->getMessage(), 'UNIQUE') !== false) {
        $error = urlencode("Email already registered. Please use a different email.");
    } else {
        $error = urlencode("An error occurred during registration. Please try again.");
    }
    
    header("Location: loginview.php?show=signup&signuperror=" . $error);
    if (isset($stmt)) {
        $stmt->close();
    }
    exit;
}
