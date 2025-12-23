<?php
/**
 * Database Connection File
 * ملف اتصال قاعدة البيانات
 * 
 * This file provides database connection using configuration from config.php
 * هذا الملف يوفر اتصال قاعدة البيانات باستخدام الإعدادات من config.php
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';

// Get database connection using the function from functions.php
// الحصول على اتصال قاعدة البيانات باستخدام الدالة من functions.php
$conn = get_db_connection();
?>
