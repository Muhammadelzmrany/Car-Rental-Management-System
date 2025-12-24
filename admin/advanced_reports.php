<?php
/**
 * Advanced Reports (Admin)
 */
require_once '../includes/functions.php';
require_once '../includes/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!check_session() || !check_admin()) {
    header("Location: ../php/loginview.php?show=signin");
    exit;
}

$report_html = '';
$csrf = generate_csrf_token();

// Reservations by period
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['report']) && $_GET['report'] === 'reservations') {
    $from = sanitize_input($_GET['from'] ?? '');
    $to = sanitize_input($_GET['to'] ?? '');
    if ($from && $to) {
        $stmt = $conn->prepare("SELECT r.id, r.pickup_date, r.return_date, r.reservation_status, r.total_cost, u.name as customer, c.name as car, c.plate_id, b.name as branch
            FROM reservations r
            JOIN users u ON r.customer_id = u.id
            JOIN cars c ON r.car_id = c.id
            JOIN branches b ON r.branch_id = b.id
            WHERE r.created_at BETWEEN ? AND ?
            ORDER BY r.created_at DESC");
        if ($stmt) {
            $stmt->bind_param('ss', $from, $to);
            $stmt->execute();
            $res = $stmt->get_result();
            $report_html .= '<h3>Reservations from ' . escape_output($from) . ' to ' . escape_output($to) . '</h3>';
            if ($res->num_rows) {
                $report_html .= '<table><thead><tr><th>ID</th><th>Customer</th><th>Car</th><th>Plate</th><th>Branch</th><th>Pickup</th><th>Return</th><th>Status</th><th>Total</th></tr></thead><tbody>';
                while ($r = $res->fetch_assoc()) {
                    $report_html .= '<tr>';
                    $report_html .= '<td>' . $r['id'] . '</td>';
                    $report_html .= '<td>' . escape_output($r['customer']) . '</td>';
                    $report_html .= '<td>' . escape_output($r['car']) . '</td>';
                    $report_html .= '<td>' . escape_output($r['plate_id']) . '</td>';
                    $report_html .= '<td>' . escape_output($r['branch']) . '</td>';
                    $report_html .= '<td>' . escape_output($r['pickup_date']) . '</td>';
                    $report_html .= '<td>' . escape_output($r['return_date']) . '</td>';
                    $report_html .= '<td>' . escape_output($r['reservation_status']) . '</td>';
                    $report_html .= '<td>$' . number_format($r['total_cost'], 2) . '</td>';
                    $report_html .= '</tr>';
                }
                $report_html .= '</tbody></table>';
            } else {
                $report_html .= '<p>No reservations found in this period.</p>';
            }
            $stmt->close();
        }
    }
}

// Daily payments
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['report']) && $_GET['report'] === 'payments') {
    $from = sanitize_input($_GET['from'] ?? '');
    $to = sanitize_input($_GET['to'] ?? '');
    if ($from && $to) {
        $stmt = $conn->prepare("SELECT DATE(pt.created_at) as day, SUM(pt.amount) as total_amount, COUNT(*) as count
            FROM payment_transactions pt
            WHERE pt.created_at BETWEEN ? AND ?
            GROUP BY DATE(pt.created_at)
            ORDER BY day DESC");
        if ($stmt) {
            $stmt->bind_param('ss', $from, $to);
            $stmt->execute();
            $res = $stmt->get_result();
            $report_html .= '<h3>Daily Payments from ' . escape_output($from) . ' to ' . escape_output($to) . '</h3>';
            if ($res->num_rows) {
                $report_html .= '<table><thead><tr><th>Date</th><th>Transactions</th><th>Total Amount</th></tr></thead><tbody>';
                while ($r = $res->fetch_assoc()) {
                    $report_html .= '<tr>';
                    $report_html .= '<td>' . escape_output($r['day']) . '</td>';
                    $report_html .= '<td>' . $r['count'] . '</td>';
                    $report_html .= '<td>$' . number_format($r['total_amount'], 2) . '</td>';
                    $report_html .= '</tr>';
                }
                $report_html .= '</tbody></table>';
            } else {
                $report_html .= '<p>No payments found in this period.</p>';
            }
            $stmt->close();
        }
    }
}

// Cars status by date
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['report']) && $_GET['report'] === 'cars_status') {
    $date = sanitize_input($_GET['date'] ?? '');
    if ($date) {
        $stmt = $conn->prepare("SELECT c.id, c.name, c.model, c.year, c.plate_id, COALESCE(c.status, CASE WHEN c.isavailable = 1 THEN 'available' ELSE 'rented' END) AS status, b.name AS branch
            FROM cars c
            JOIN branches b ON c.branch_id = b.id
            ORDER BY c.id ASC");
        if ($stmt) {
            $stmt->execute();
            $res = $stmt->get_result();
            $report_html .= '<h3>Car Status on ' . escape_output($date) . '</h3>';
            if ($res->num_rows) {
                $report_html .= '<table><thead><tr><th>ID</th><th>Name</th><th>Model</th><th>Year</th><th>Plate</th><th>Status</th><th>Branch</th></tr></thead><tbody>';
                while ($r = $res->fetch_assoc()) {
                    $report_html .= '<tr>';
                    $report_html .= '<td>' . $r['id'] . '</td>';
                    $report_html .= '<td>' . escape_output($r['name']) . '</td>';
                    $report_html .= '<td>' . escape_output($r['model']) . '</td>';
                    $report_html .= '<td>' . $r['year'] . '</td>';
                    $report_html .= '<td>' . escape_output($r['plate_id']) . '</td>';
                    $report_html .= '<td><span class="status-badge status-' . escape_output(str_replace('_', '', $r['status'])) . '">' . escape_output($r['status']) . '</span></td>';
                    $report_html .= '<td>' . escape_output($r['branch']) . '</td>';
                    $report_html .= '</tr>';
                }
                $report_html .= '</tbody></table>';
            } else {
                $report_html .= '<p>No cars found.</p>';
            }
            $stmt->close();
        }
    }
}

// Customer reservations
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['report']) && $_GET['report'] === 'customer_reservations') {
    $customer_id = (int)($_GET['customer_id'] ?? 0);
    if ($customer_id > 0) {
        $stmt = $conn->prepare("SELECT r.id, r.pickup_date, r.return_date, r.reservation_status, r.total_cost, c.name AS car, c.plate_id, c.model, b.name AS branch
            FROM reservations r
            JOIN cars c ON r.car_id = c.id
            JOIN branches b ON r.branch_id = b.id
            WHERE r.customer_id = ?
            ORDER BY r.created_at DESC");
        if ($stmt) {
            $stmt->bind_param('i', $customer_id);
            $stmt->execute();
            $res = $stmt->get_result();
            
            // Get customer name
            $cust_stmt = $conn->prepare("SELECT name FROM users WHERE id = ?");
            $cust_stmt->bind_param('i', $customer_id);
            $cust_stmt->execute();
            $cust_result = $cust_stmt->get_result();
            $cust_row = $cust_result->fetch_assoc();
            $cust_stmt->close();
            
            $report_html .= '<h3>Reservations for ' . escape_output($cust_row['name'] ?? 'Unknown Customer') . '</h3>';
            if ($res->num_rows) {
                $report_html .= '<table><thead><tr><th>ID</th><th>Car</th><th>Model</th><th>Plate</th><th>Branch</th><th>Pickup</th><th>Return</th><th>Status</th><th>Total</th></tr></thead><tbody>';
                while ($r = $res->fetch_assoc()) {
                    $report_html .= '<tr>';
                    $report_html .= '<td>' . $r['id'] . '</td>';
                    $report_html .= '<td>' . escape_output($r['car']) . '</td>';
                    $report_html .= '<td>' . escape_output($r['model']) . '</td>';
                    $report_html .= '<td>' . escape_output($r['plate_id']) . '</td>';
                    $report_html .= '<td>' . escape_output($r['branch']) . '</td>';
                    $report_html .= '<td>' . escape_output($r['pickup_date']) . '</td>';
                    $report_html .= '<td>' . escape_output($r['return_date']) . '</td>';
                    $report_html .= '<td>' . escape_output($r['reservation_status']) . '</td>';
                    $report_html .= '<td>$' . number_format($r['total_cost'], 2) . '</td>';
                    $report_html .= '</tr>';
                }
                $report_html .= '</tbody></table>';
            } else {
                $report_html .= '<p>No reservations found for this customer.</p>';
            }
            $stmt->close();
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - Admin</title>
    <link rel="stylesheet" href="../css/style.css?v=3">
    <link rel="stylesheet" href="../css/admin.css?v=3">
    <style>table{width:100%;border-collapse:collapse}th,td{padding:8px;border:1px solid #eee}fieldset{margin-bottom:16px}</style>
</head>
<body>
    <header>
        <a href="../index.php" class="logo"><img src="../img/log.png" alt="logo"></a>
        <div class="head-btn">
            <label class="welcome">Admin: <?= escape_output($_SESSION['userName'] ?? '') ?></label>
            <a href="../php/logout.php" class="sign-in">Sign out</a>
        </div>
    </header>

    <div class="admin-container">
        <h1>Advanced Reports</h1>
        <p>Use the forms below to run reports. All 4 required reports are available.</p>

        <fieldset>
            <legend>1. Reservations by Period</legend>
            <form method="get">
                <input type="hidden" name="report" value="reservations">
                From: <input type="date" name="from" required> To: <input type="date" name="to" required>
                <button type="submit">Run</button>
            </form>
        </fieldset>

        <fieldset>
            <legend>2. Car Status by Date</legend>
            <form method="get">
                <input type="hidden" name="report" value="cars_status">
                Date: <input type="date" name="date" required>
                <button type="submit">Run</button>
            </form>
        </fieldset>

        <fieldset>
            <legend>3. Customer Reservations</legend>
            <form method="get">
                <input type="hidden" name="report" value="customer_reservations">
                Customer ID: <input type="number" name="customer_id" min="1" required>
                <button type="submit">Run</button>
            </form>
        </fieldset>

        <fieldset>
            <legend>4. Daily Payments</legend>
            <form method="get">
                <input type="hidden" name="report" value="payments">
                From: <input type="date" name="from" required> To: <input type="date" name="to" required>
                <button type="submit">Run</button>
            </form>
        </fieldset>

        <div>
            <?= $report_html ?>
        </div>

        <p><a href="index.php">&larr; Back to Dashboard</a></p>
    </div>
</body>
</html>
