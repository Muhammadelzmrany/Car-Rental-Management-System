<?php
/**
 * Local database diagnostics (CLI only).
 *
 * Usage from project root: php php/check_database.php
 * Never expose host/database information through a public web endpoint.
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Database diagnostics are available from the command line only.');
}

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

$requiredTables = ['branches', 'users', 'cars', 'reservations', 'payment_transactions'];

try {
    $conn = get_db_connection();
    echo "Database connection: OK\n";
    $missing = [];

    foreach ($requiredTables as $table) {
        // Table names come from this fixed, trusted list.
        $result = $conn->query("SHOW TABLES LIKE '" . $conn->real_escape_string($table) . "'");
        if ($result && $result->num_rows > 0) {
            echo "[OK] " . $table . "\n";
        } else {
            echo "[MISSING] " . $table . "\n";
            $missing[] = $table;
        }
    }

    $conn->close();

    if (count($missing) > 0) {
        fwrite(STDERR, "Import database/schema_and_seed.sql to create missing tables.\n");
        exit(1);
    }
    echo "All required tables are present.\n";
} catch (Throwable $error) {
    fwrite(STDERR, "Database check failed. Review DB_HOST, DB_USER, DB_PASS and DB_NAME.\n");
    exit(1);
}
