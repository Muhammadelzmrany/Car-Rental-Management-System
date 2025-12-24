<?php
/**
 * Admin Dashboard
 * لوحة تحكم المدير
 */

require_once '../includes/functions.php';
require_once '../includes/db.php';

// Admin authentication check
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!check_session() || !check_admin()) {
    header("Location: ../php/loginview.php?show=signin");
    exit;
}

// Get statistics
$stats = [];

// Total cars
$stats['total_cars'] = $conn->query("SELECT COUNT(*) as count FROM cars")->fetch_assoc()['count'];

// Available cars
$stats['available_cars'] = $conn->query("SELECT COUNT(*) as count FROM cars WHERE isavailable = 1 AND status = 'available'")->fetch_assoc()['count'];

// Rented cars
$stats['rented_cars'] = $conn->query("SELECT COUNT(*) as count FROM cars WHERE status = 'rented'")->fetch_assoc()['count'];

// Total reservations
$stats['total_reservations'] = $conn->query("SELECT COUNT(*) as count FROM reservations")->fetch_assoc()['count'];

// Pending reservations
$stats['pending_reservations'] = $conn->query("SELECT COUNT(*) as count FROM reservations WHERE reservation_status = 'pending'")->fetch_assoc()['count'];

// Total customers
$stats['total_customers'] = $conn->query("SELECT COUNT(*) as count FROM users WHERE isadmin = 0")->fetch_assoc()['count'];

// Recent reservations
$recent_reservations = $conn->query("
    SELECT r.id, r.pickup_date, r.return_date, r.reservation_status, r.total_cost,
           u.name as customer_name, u.email as customer_email,
           c.name as car_name, c.model as car_model, c.plate_id,
           b.name as branch_name
    FROM reservations r
    JOIN users u ON r.customer_id = u.id
    JOIN cars c ON r.car_id = c.id
    JOIN branches b ON r.branch_id = b.id
    ORDER BY r.created_at DESC
    LIMIT 10
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Car Rental System</title>
    <link rel="stylesheet" href="../css/style.css?v=3">
    <link rel="stylesheet" href="../css/admin.css?v=3">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/boxicons@latest/css/boxicons.min.css">
    <!-- Admin page styles are in css/admin.css -->
</head>
<body>
    <header>
        <a href="../index.php" class="logo"><img src="../img/log.png" alt="logo"></a>
        <div class="head-btn">
            <label class="welcome">Admin: <?= escape_output($_SESSION["userName"]) ?></label>
            <a href="../php/logout.php" class="sign-in">Sign out</a>
        </div>
    </header>

    <div class="admin-container">
        <h1 style="margin-bottom: 30px;">Admin Dashboard</h1>

        <div class="stats-grid">
            <div class="stat-card">
                <p>Total Cars</p>
                <h3><?= $stats['total_cars'] ?></h3>
            </div>
            <div class="stat-card">
                <p>Available Cars</p>
                <h3><?= $stats['available_cars'] ?></h3>
            </div>
            <div class="stat-card">
                <p>Rented Cars</p>
                <h3><?= $stats['rented_cars'] ?></h3>
            </div>
            <div class="stat-card">
                <p>Total Reservations</p>
                <h3><?= $stats['total_reservations'] ?></h3>
            </div>
            <div class="stat-card">
                <p>Pending Reservations</p>
                <h3><?= $stats['pending_reservations'] ?></h3>
            </div>
            <div class="stat-card">
                <p>Total Customers</p>
                <h3><?= $stats['total_customers'] ?></h3>
            </div>
        </div>

        <div class="admin-nav">
            <ul>
                <li><a href="addcars.php"><i class='bx bx-plus'></i> Add New Car</a></li>
                <li><a href="updatecar.php"><i class='bx bx-edit'></i> Update Car Status</a></li>
                <li><a href="pickup_return.php"><i class='bx bx-car'></i> Manage Pickup/Return</a></li>
                <li><a href="advanced_reports.php"><i class='bx bx-bar-chart'></i> View Reports</a></li>
                <li><a href="users.php"><i class='bx bx-user'></i> Manage Users</a></li>
                <li><a href="../index.php"><i class='bx bx-home'></i> Back to Site</a></li>
            </ul>
        </div>

        <div class="recent-table">
            <h2 style="margin-bottom: 20px;">Recent Reservations</h2>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Customer</th>
                        <th>Car</th>
                        <th>Plate ID</th>
                        <th>Branch</th>
                        <th>Pickup Date</th>
                        <th>Return Date</th>
                        <th>Total Cost</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($recent_reservations->num_rows > 0): ?>
                        <?php while ($row = $recent_reservations->fetch_assoc()): ?>
                            <tr>
                                <td><?= $row['id'] ?></td>
                                <td><?= escape_output($row['customer_name']) ?><br><small><?= escape_output($row['customer_email']) ?></small></td>
                                <td><?= escape_output($row['car_name'] . ' ' . $row['car_model']) ?></td>
                                <td><?= escape_output($row['plate_id']) ?></td>
                                <td><?= escape_output($row['branch_name']) ?></td>
                                <td><?= $row['pickup_date'] ?></td>
                                <td><?= $row['return_date'] ?></td>
                                <td>$<?= number_format($row['total_cost'], 2) ?></td>
                                <td><span class="status-badge status-<?= $row['reservation_status'] ?>"><?= ucfirst(str_replace('_', ' ', $row['reservation_status'])) ?></span></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="9" style="text-align: center; padding: 20px;">No reservations found</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>

