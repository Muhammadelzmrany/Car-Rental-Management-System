<?php
/**
 * Public-safe application configuration.
 *
 * Never put passwords or remote database credentials into this committed file.
 * For local development: copy config.local.php.example to config.local.php.
 * Alternatively set DB_HOST, DB_USER, DB_PASS and DB_NAME in the environment.
 *
 * Precedence: config.local.php definitions, then environment variables,
 * then local XAMPP demo defaults.
 */
$localConfigPath = dirname(__DIR__) . '/config.local.php';
if (is_file($localConfigPath)) {
    require_once $localConfigPath;
}

if (!function_exists('car_rental_env_value')) {
    function car_rental_env_value(string $name, string $default): string {
        $value = getenv($name);
        return $value === false ? $default : $value;
    }
}

// Database: no real credentials are committed.
defined('DB_HOST') || define('DB_HOST', car_rental_env_value('DB_HOST', 'localhost'));
defined('DB_USER') || define('DB_USER', car_rental_env_value('DB_USER', 'root'));
defined('DB_PASS') || define('DB_PASS', car_rental_env_value('DB_PASS', ''));
defined('DB_NAME') || define('DB_NAME', car_rental_env_value('DB_NAME', 'carrentalsystem'));
defined('DB_CHARSET') || define('DB_CHARSET', 'utf8mb4');

// Security
defined('SESSION_LIFETIME') || define('SESSION_LIFETIME', 3600);
defined('CSRF_TOKEN_NAME') || define('CSRF_TOKEN_NAME', 'csrf_token');
defined('MAX_LOGIN_ATTEMPTS') || define('MAX_LOGIN_ATTEMPTS', 5);
defined('LOGIN_LOCKOUT_TIME') || define('LOGIN_LOCKOUT_TIME', 900);

// Application settings
defined('UPLOAD_DIR') || define('UPLOAD_DIR', dirname(__DIR__) . '/uploads/');
defined('MAX_FILE_SIZE') || define('MAX_FILE_SIZE', 2097152);
defined('ALLOWED_IMAGE_TYPES') || define('ALLOWED_IMAGE_TYPES', ['jpg', 'jpeg', 'png', 'gif']);
defined('DISPLAY_ERRORS') || define('DISPLAY_ERRORS', filter_var(car_rental_env_value('DISPLAY_ERRORS', 'false'), FILTER_VALIDATE_BOOLEAN));
defined('LOG_ERRORS') || define('LOG_ERRORS', true);
defined('ERROR_LOG_FILE') || define('ERROR_LOG_FILE', dirname(__DIR__) . '/logs/error.log');

date_default_timezone_set('UTC');
