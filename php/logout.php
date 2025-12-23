<?php
/**
 * Logout Processing
 * معالجة تسجيل الخروج
 */

require_once '../includes/functions.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Regenerate session ID before destroying
session_regenerate_id(true);

// Unset all session variables
session_unset();

// Destroy the session
session_destroy();

// Redirect to home page
header("Location: ../index.php");
exit;
