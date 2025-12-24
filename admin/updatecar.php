<?php
/**
 * Update Car Status (Admin)
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
// Handle update action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update') {
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $msg = 'Security token mismatch.';
    } else {
        $id = (int)($_POST['car_id'] ?? 0);
        $status = sanitize_input($_POST['status'] ?? 'available');
        $price = (float)($_POST['price'] ?? 0);
        $isavailable = isset($_POST['isavailable']) ? 1 : 0;

        $sql = "UPDATE cars SET status = ?, price_per_day = ?, isavailable = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param('sdii', $status, $price, $isavailable, $id);
            if ($stmt->execute()) {
                $msg = 'Car updated successfully.';
            } else {
                log_error('Update car execute failed: ' . $stmt->error, __FILE__, __LINE__);
                $msg = 'Failed to update car.';
            }
            $stmt->close();
        } else {
            log_error('Update car prepare failed: ' . $conn->error, __FILE__, __LINE__);
            $msg = 'Database error.';
        }
    }
}

// Handle delete action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $msg = 'Security token mismatch.';
    } else {
        $id = (int)($_POST['car_id'] ?? 0);
        if ($id <= 0) {
            $msg = 'Invalid car id.';
        } else {
            // Check for active reservations referencing this car
            $chk = $conn->prepare("SELECT COUNT(*) as cnt FROM reservations WHERE car_id = ? AND reservation_status IN ('pending','confirmed','picked_up')");
            if ($chk) {
                $chk->bind_param('i', $id);
                $chk->execute();
                $res = $chk->get_result()->fetch_assoc();
                $chk->close();
                if ($res && $res['cnt'] > 0) {
                    $msg = 'Cannot delete car with active reservations.';
                } else {
                    // get image name to unlink
                    $g = $conn->prepare('SELECT image_name FROM cars WHERE id = ?');
                    if ($g) {
                        $g->bind_param('i', $id);
                        $g->execute();
                        $gres = $g->get_result()->fetch_assoc();
                        $g->close();
                        $image = $gres['image_name'] ?? '';
                    } else {
                        $image = '';
                    }

                    $del = $conn->prepare('DELETE FROM cars WHERE id = ?');
                    if ($del) {
                        $del->bind_param('i', $id);
                        if ($del->execute()) {
                            // remove uploaded image if exists
                            if (!empty($image)) {
                                $path = dirname(__DIR__) . '/uploads/' . $image;
                                if (file_exists($path)) {
                                    @unlink($path);
                                }
                            }
                            $msg = 'Car deleted successfully.';
                        } else {
                            log_error('Delete car failed: ' . $del->error, __FILE__, __LINE__);
                            $msg = 'Failed to delete car.';
                        }
                        $del->close();
                    } else {
                        log_error('Delete car prepare failed: ' . $conn->error, __FILE__, __LINE__);
                        $msg = 'Database error.';
                    }
                }
            } else {
                $msg = 'Database error.';
            }
        }
    }
}

// Fetch cars (use alias so template code continues using 'price')
$cars = $conn->query('SELECT id, name, model, year, plate_id, status, price_per_day AS price, isavailable FROM cars ORDER BY id DESC');
$csrf = generate_csrf_token();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Car - Admin</title>
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
        <h1>Update Car Status</h1>
        <?php if ($msg): ?><p class="error"><?= escape_output($msg) ?></p><?php endif; ?>
        <table>
            <thead>
                <tr><th>ID</th><th>Name</th><th>Model</th><th>Plate</th><th>Year</th><th>Price</th><th>Status</th><th>Available</th><th>Action</th></tr>
            </thead>
            <tbody>
                <?php while ($c = $cars->fetch_assoc()): ?>
                    <tr>
                        <td><?= $c['id'] ?></td>
                        <td><?= escape_output($c['name']) ?></td>
                        <td><?= escape_output($c['model']) ?></td>
                        <td><?= escape_output($c['plate_id']) ?></td>
                        <td><?= escape_output($c['year']) ?></td>
                        <td>
                            <form method="post" style="display:inline-block">
                                <input type="hidden" name="csrf_token" value="<?= escape_output($csrf) ?>">
                                <input type="hidden" name="action" value="update">
                                <input type="hidden" name="car_id" value="<?= $c['id'] ?>">
                                <input type="number" step="0.01" name="price" value="<?= $c['price'] ?>" style="width:90px">
                        </td>
                        <td>
                                <select name="status">
                                    <option value="available" <?= $c['status'] === 'available' ? 'selected' : '' ?>>available</option>
                                    <option value="rented" <?= $c['status'] === 'rented' ? 'selected' : '' ?>>rented</option>
                                    <option value="out_of_service" <?= $c['status'] === 'out_of_service' ? 'selected' : '' ?>>out_of_service</option>
                                </select>
                        </td>
                        <td><input type="checkbox" name="isavailable" <?= $c['isavailable'] ? 'checked' : '' ?>></td>
                        <td>
                                <button type="submit">Save</button>
                            </form>
                            
                            <form method="post" style="display:inline-block;margin-left:8px" onsubmit="return confirm('Delete this car? This action is irreversible.')">
                                <input type="hidden" name="csrf_token" value="<?= escape_output($csrf) ?>">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="car_id" value="<?= $c['id'] ?>">
                                <button type="submit" style="background:#b91c1c;padding:6px 10px;border-radius:6px;color:#fff;border:none">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        <p><a href="index.php">&larr; Back to Dashboard</a></p>
    </div>
</body>
</html>
