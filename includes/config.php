<?php
/**
 * Configuration File
 * إعدادات التطبيق وقاعدة البيانات
 * 
 * IMPORTANT: Never commit this file with real credentials!
 * Create a config.local.php file for your local environment
 */

// Database Configuration
define('DB_HOST', 'sql7.freesqldatabase.com');
define('DB_USER', 'sql7812767');
define('DB_PASS', 'k4DVdqKavK');
define('DB_NAME', 'sql7812767');
define('DB_CHARSET', 'utf8mb4');

// Security Configuration
define('SESSION_LIFETIME', 3600); // 1 hour in seconds
define('CSRF_TOKEN_NAME', 'csrf_token');
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_TIME', 900); // 15 minutes in seconds

// Application Configuration
define('UPLOAD_DIR', dirname(__DIR__) . '/uploads/');
define('MAX_FILE_SIZE', 2097152); // 2MB in bytes
define('ALLOWED_IMAGE_TYPES', ['jpg', 'jpeg', 'png', 'gif']);

// Error Reporting (Set to false in production)
define('DISPLAY_ERRORS', false);
define('LOG_ERRORS', true);
define('ERROR_LOG_FILE', dirname(__DIR__) . '/logs/error.log');

// Timezone
date_default_timezone_set('UTC');

// Load local configuration if exists (for development)
if (file_exists(dirname(__DIR__) . '/config.local.php')) {
    require_once dirname(__DIR__) . '/config.local.php';
}






