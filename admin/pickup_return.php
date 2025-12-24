<?php
/**
 * Manage Pickup / Return (Admin)
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

$msg = '';
// Handle actions: pick_up or return
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $msg = 'Security token mismatch.';
    } else {
        $action = $_POST['action'];
        $reservation_id = (int)($_POST['reservation_id'] ?? 0);

        // Fetch reservation to know car id
        $rstmt = $conn->prepare('SELECT car_id FROM reservations WHERE id = ?');
        $rstmt->bind_param('i', $reservation_id);
        $rstmt->execute();
        $rres = $rstmt->get_result()->fetch_assoc();
        $rstmt->close();
        $car_id = $rres['car_id'] ?? 0;

        if ($car_id <= 0) {
            $msg = 'Reservation not found.';
        } else {
            if ($action === 'pick_up') {
                $stmt = $conn->prepare("UPDATE reservations SET reservation_status = 'picked_up' WHERE id = ?");
                $stmt2 = $conn->prepare("UPDATE cars SET status = 'rented', isavailable = 0 WHERE id = ?");
                if ($stmt && $stmt2) {
                    $stmt->bind_param('i', $reservation_id);
                    $stmt2->bind_param('i', $car_id);
                    $stmt->execute();
                    $stmt2->execute();
                    $msg = 'Marked as picked up.';
                }
                if ($stmt) $stmt->close();
                if ($stmt2) $stmt2->close();
            } elseif ($action === 'return') {
                $stmt = $conn->prepare("UPDATE reservations SET reservation_status = 'returned' WHERE id = ?");
                $stmt2 = $conn->prepare("UPDATE cars SET status = 'available', isavailable = 1 WHERE id = ?");
                if ($stmt && $stmt2) {
                    $stmt->bind_param('i', $reservation_id);
                    $stmt2->bind_param('i', $car_id);
                    $stmt->execute();
                    $stmt2->execute();
                    $msg = 'Marked as returned.';
                }
                if ($stmt) $stmt->close();
                if ($stmt2) $stmt2->close();
            }
        }
    }
}

// List recent reservations that may need actions
$sql = "SELECT r.id, r.pickup_date, r.return_date, r.reservation_status, u.name as customer_name, c.name as car_name, c.plate_id
        FROM reservations r
        JOIN users u ON r.customer_id = u.id
        JOIN cars c ON r.car_id = c.id
        ORDER BY r.created_at DESC
        LIMIT 50";
$reservations = $conn->query($sql);
$csrf = generate_csrf_token();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pickup / Return - Admin</title>
    <link rel="stylesheet" href="../css/style.css?v=3">
    <link rel="stylesheet" href="../css/admin.css?v=3">
    <style>table{width:100%;border-collapse:collapse}th,td{padding:8px;border:1px solid #eee}</style>
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
        <h1>Manage Pickup / Return</h1>
        <?php if ($msg): ?><p class="error"><?= escape_output($msg) ?></p><?php endif; ?>
        <table>
            <thead>
                <tr><th>ID</th><th>Customer</th><th>Car</th><th>Plate</th><th>Pickup</th><th>Return</th><th>Status</th><th>Action</th></tr>
            </thead>
            <tbody>
                <?php if ($reservations && $reservations->num_rows): ?>
                    <?php while ($r = $reservations->fetch_assoc()): ?>
                        <tr>
                            <td><?= $r['id'] ?></td>
                            <td><?= escape_output($r['customer_name']) ?></td>
                            <td><?= escape_output($r['car_name']) ?></td>
                            <td><?= escape_output($r['plate_id']) ?></td>
                            <td><?= escape_output($r['pickup_date']) ?></td>
                            <td><?= escape_output($r['return_date']) ?></td>
                            <td><?= escape_output($r['reservation_status']) ?></td>
                            <td>
                                <?php if ($r['reservation_status'] === 'confirmed'): ?>
                                    <form method="post" style="display:inline-block">
                                        <input type="hidden" name="csrf_token" value="<?= escape_output($csrf) ?>">
                                        <input type="hidden" name="reservation_id" value="<?= $r['id'] ?>">
                                        <input type="hidden" name="action" value="pick_up">
                                        <button type="submit">Mark Picked Up</button>
                                    </form>
                                <?php elseif ($r['reservation_status'] === 'picked_up'): ?>
                                    <form method="post" style="display:inline-block">
                                        <input type="hidden" name="csrf_token" value="<?= escape_output($csrf) ?>">
                                        <input type="hidden" name="reservation_id" value="<?= $r['id'] ?>">
                                        <input type="hidden" name="action" value="return">
                                        <button type="submit">Mark Returned</button>
                                    </form>
                                <?php else: ?>
                                    &mdash;
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="8" style="text-align:center">No reservations found</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
        <p><a href="index.php">&larr; Back to Dashboard</a></p>
    </div>
</body>
</html>
