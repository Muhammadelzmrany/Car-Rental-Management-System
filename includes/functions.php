<?php
/**
 * Common Functions File
 * دوال مشتركة للأمان والتحقق
 */

// Load configuration if not already loaded
if (!defined('CSRF_TOKEN_NAME')) {
    require_once __DIR__ . '/config.php';
}

/**
 * Sanitize input data
 * تنظيف المدخلات من الأحرف الخطيرة
 */
function sanitize_input($data) {
    if (is_array($data)) {
        return array_map('sanitize_input', $data);
    }
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

/**
 * Escape output for XSS protection
 * تنظيف المخرجات لحماية من XSS
 */
function escape_output($data) {
    if (is_array($data)) {
        return array_map('escape_output', $data);
    }
    return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
}

/**
 * Validate email address
 * التحقق من صحة البريد الإلكتروني
 */
function validate_email($email) {
    if (empty($email)) {
        return false;
    }
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validate phone number (basic validation)
 * التحقق من صحة رقم الهاتف
 */
function validate_phone($phone) {
    if (empty($phone)) {
        return false;
    }
    // Remove all non-digit characters
    $phone = preg_replace('/[^0-9]/', '', $phone);
    // Check if phone has reasonable length (7-15 digits)
    return strlen($phone) >= 7 && strlen($phone) <= 15;
}

/**
 * Validate password strength
 * التحقق من قوة كلمة المرور
 */
function validate_password($password) {
    if (empty($password)) {
        return false;
    }
    // At least 8 characters
    if (strlen($password) < 8) {
        return false;
    }
    return true;
}

/**
 * Start or resume the same PHP session for every application route.
 * Cookie path "/" is shared by index.php, /php/ and /admin/.
 * Call before any HTML is sent. Never bypass CSRF validation.
 */
function car_rental_start_session() {
    if (session_status() === PHP_SESSION_ACTIVE) {
        return true;
    }
    if (session_status() === PHP_SESSION_DISABLED || headers_sent()) {
        error_log('Car Rental: session unavailable or response headers already sent.');
        return false;
    }

    ini_set('session.use_only_cookies', '1');
    ini_set('session.use_strict_mode', '1');
    $https = (!empty($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) !== 'off')
        || ((int)($_SERVER['SERVER_PORT'] ?? 0) === 443);
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $https,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);

    if (!session_start()) {
        error_log('Car Rental: PHP session_start() failed.');
        return false;
    }
    return true;
}

// Initialize session before page rendering and before loading form tokens.
car_rental_start_session();

/**
 * Check if user session is active
 * التحقق من وجود جلسة نشطة للمستخدم
 */
function check_session() {
    if (!car_rental_start_session()) {
        return false;
    }
    return isset($_SESSION['id']) && !empty($_SESSION['id']);
}

/**
 * Check if user is admin
 * التحقق من صلاحيات المدير
 */
function check_admin() {
    if (!check_session()) {
        return false;
    }
    return isset($_SESSION['isAdmin']) && $_SESSION['isAdmin'] == true;
}

/**
 * Require user to be logged in
 * إجبار المستخدم على تسجيل الدخول
 */
function require_login() {
    if (!check_session()) {
        header("Location: ../php/loginview.php?show=signin");
        exit;
    }
}

/**
 * Require admin privileges
 * إجبار المستخدم على صلاحيات المدير
 */
function require_admin() {
    if (!check_admin()) {
        header("Location: ../index.php");
        exit;
    }
}

/**
 * Generate CSRF token
 * توليد رمز CSRF
 */
function generate_csrf_token() {
    if (!car_rental_start_session()) {
        http_response_code(503);
        exit('Session service is temporarily unavailable. Please try again shortly.');
    }
    if (!isset($_SESSION[CSRF_TOKEN_NAME])
        || !is_string($_SESSION[CSRF_TOKEN_NAME])
        || !preg_match('/\A[a-f0-9]{64}\z/D', $_SESSION[CSRF_TOKEN_NAME])) {
        $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
    }
    return $_SESSION[CSRF_TOKEN_NAME];
}

/**
 * Check that a submitted token matches the current browser session.
 */
function verify_csrf_token($token) {
    if (!car_rental_start_session()
        || !is_string($token)
        || !preg_match('/\A[a-f0-9]{64}\z/D', $token)
        || !isset($_SESSION[CSRF_TOKEN_NAME])
        || !is_string($_SESSION[CSRF_TOKEN_NAME])) {
        return false;
    }
    return hash_equals($_SESSION[CSRF_TOKEN_NAME], $token);
}

/**
 * Get database connection
 * الحصول على اتصال قاعدة البيانات
 */
function get_db_connection() {
    require_once __DIR__ . '/config.php';
    
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($conn->connect_error) {
        if (DISPLAY_ERRORS) {
            die("Database connection failed: " . $conn->connect_error);
        } else {
            error_log("Database connection failed: " . $conn->connect_error);
            die("Database connection failed. Please try again later.");
        }
    }
    
    $conn->set_charset(DB_CHARSET);
    return $conn;
}

/**
 * Log error message
 * تسجيل رسالة خطأ
 */
function log_error($message, $file = '', $line = '') {
    if (!LOG_ERRORS) {
        return;
    }
    
    $log_dir = dirname(ERROR_LOG_FILE);
    if (!is_dir($log_dir)) {
        mkdir($log_dir, 0755, true);
    }
    
    $timestamp = date('Y-m-d H:i:s');
    $log_message = "[$timestamp] ";
    if ($file) {
        $log_message .= "[$file";
        if ($line) {
            $log_message .= ":$line";
        }
        $log_message .= "] ";
    }
    $log_message .= $message . PHP_EOL;
    
    file_put_contents(ERROR_LOG_FILE, $log_message, FILE_APPEND);
}

/**
 * Clean filename for safe storage
 * تنظيف اسم الملف للتخزين الآمن
 */
function clean_filename($filename) {
    // Remove path information
    $filename = basename($filename);
    // Remove special characters
    $filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename);
    // Limit length
    if (strlen($filename) > 255) {
        $ext = pathinfo($filename, PATHINFO_EXTENSION);
        $name = pathinfo($filename, PATHINFO_FILENAME);
        $filename = substr($name, 0, 255 - strlen($ext) - 1) . '.' . $ext;
    }
    return $filename;
}

/**
 * Validate uploaded file
 * التحقق من الملف المرفوع
 */
function validate_uploaded_file($file, $allowed_types = null, $max_size = null) {
    if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        return ['valid' => false, 'error' => 'No file uploaded'];
    }
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['valid' => false, 'error' => 'Upload error: ' . $file['error']];
    }
    
    $max_size = $max_size ?? MAX_FILE_SIZE;
    if ($file['size'] > $max_size) {
        return ['valid' => false, 'error' => 'File size exceeds maximum allowed size'];
    }
    
    $allowed_types = $allowed_types ?? ALLOWED_IMAGE_TYPES;
    $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    if (!in_array($file_ext, $allowed_types)) {
        return ['valid' => false, 'error' => 'File type not allowed'];
    }
    
    return ['valid' => true, 'extension' => $file_ext];
}


