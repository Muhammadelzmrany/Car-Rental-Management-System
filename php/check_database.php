<?php
/**
 * Database Connection Checker
 * فحص اتصال قاعدة البيانات
 */

require_once '../includes/config.php';
require_once '../includes/functions.php';

echo "<h2>Car Rental System - Database Check</h2>";
echo "<hr>";

// Check database connection
echo "<h3>1. Database Connection:</h3>";
try {
    $conn = get_db_connection();
    echo "✅ <strong>SUCCESS:</strong> Database connection established!<br>";
    echo "Database: " . DB_NAME . "<br>";
    echo "Host: " . DB_HOST . "<br><br>";
} catch (Exception $e) {
    echo "❌ <strong>ERROR:</strong> " . $e->getMessage() . "<br>";
    echo "Please check your database configuration in config.php<br><br>";
    exit;
}

// Check tables
echo "<h3>2. Database Tables:</h3>";
$required_tables = ['admin', 'users', 'branches', 'cars', 'reservations', 'bookings', 'payments'];
$missing_tables = [];

foreach ($required_tables as $table) {
    $result = $conn->query("SHOW TABLES LIKE '$table'");
    if ($result->num_rows > 0) {
        echo "✅ Table '$table' exists<br>";
    } else {
        echo "❌ Table '$table' is missing<br>";
        $missing_tables[] = $table;
    }
}

echo "<br>";

if (!empty($missing_tables)) {
    echo "<strong>⚠️ WARNING:</strong> Some tables are missing. Please import final.sql<br>";
    echo "Missing tables: " . implode(', ', $missing_tables) . "<br><br>";
} else {
    echo "✅ <strong>All required tables exist!</strong><br><br>";
}

// Check sample data
echo "<h3>3. Sample Data:</h3>";

// Check users
$result = $conn->query("SELECT COUNT(*) as count FROM users");
if ($result) {
    $row = $result->fetch_assoc();
    echo "Users: " . $row['count'] . "<br>";
} else {
    echo "Users: Error checking data<br>";
}

// Check branches
if (in_array('branches', $required_tables) && !in_array('branches', $missing_tables)) {
    $result = $conn->query("SELECT COUNT(*) as count FROM branches");
    if ($result) {
        $row = $result->fetch_assoc();
        echo "Branches: " . $row['count'] . "<br>";
    } else {
        echo "Branches: Error checking data<br>";
    }
} else {
    echo "Branches: Table missing<br>";
}

// Check cars
$result = $conn->query("SELECT COUNT(*) as count FROM cars");
if ($result) {
    $row = $result->fetch_assoc();
    echo "Cars: " . $row['count'] . "<br>";
} else {
    echo "Cars: Error checking data<br>";
}

echo "<br>";

// Check uploads directory
echo "<h3>4. Directories:</h3>";
if (is_dir(UPLOAD_DIR) && is_writable(UPLOAD_DIR)) {
    echo "✅ Uploads directory exists and is writable<br>";
} else {
    echo "❌ Uploads directory missing or not writable<br>";
}

$log_dir = dirname(ERROR_LOG_FILE);
if (is_dir($log_dir) && is_writable($log_dir)) {
    echo "✅ Logs directory exists and is writable<br>";
} else {
    echo "❌ Logs directory missing or not writable<br>";
}

echo "<br>";

// Final status
echo "<h3>5. Status:</h3>";
if (empty($missing_tables)) {
    echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; color: #155724;'>";
    echo "<strong>✅ System is ready!</strong><br>";
    echo "You can now use the application at: <a href='index.php'>index.php</a>";
    echo "</div>";
} else {
    echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px; color: #721c24;'>";
    echo "<strong>⚠️ System needs setup!</strong><br>";
    echo "Please import final.sql to create the required tables.";
    echo "</div>";
}

$conn->close();
?>

<style>
body {
    font-family: Arial, sans-serif;
    max-width: 800px;
    margin: 50px auto;
    padding: 20px;
    background: #f5f5f5;
}
h2 {
    color: #333;
}
h3 {
    color: #555;
    margin-top: 20px;
}
</style>

