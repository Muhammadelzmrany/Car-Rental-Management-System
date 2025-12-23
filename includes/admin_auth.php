<?php
/**
 * Admin Authentication Helper
 * مساعد مصادقة المدير
 */

require_once __DIR__ . '/functions.php';

/**
 * Check if user is admin and redirect if not
 * التحقق من صلاحيات المدير وإعادة التوجيه إذا لم يكن مديراً
 */
function require_admin_auth() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Check if user is logged in
    if (!check_session()) {
        header("Location: ../loginview.php?show=signin");
        exit;
    }
    
    // Check if user is admin
    if (!check_admin()) {
        header("Location: ../index.php");
        exit;
    }
}





