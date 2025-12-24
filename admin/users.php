<?php
/**
 * Admin Users Management
 */
require_once '../includes/functions.php';
require_once '../includes/db.php';

// Admin authentication
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!check_session() || !check_admin()) {
    header("Location: ../php/loginview.php?show=signin");
    exit;
}

$msg = '';
// Handle actions: toggle_admin, delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $msg = 'Security token mismatch.';
    } else {
        $action = $_POST['action'];
        $user_id = (int)($_POST['user_id'] ?? 0);
        if ($user_id <= 0) {
            $msg = 'Invalid user id.';
        } else {
            // Prevent self-modification (dangerous)
            if ($user_id === (int)($_SESSION['id'] ?? 0) && $action === 'delete') {
                $msg = 'You cannot delete your own account.';
            } else {
                if ($action === 'toggle_admin') {
                    // flip isadmin
                    $stmt = $conn->prepare('UPDATE users SET isadmin = 1 - isadmin WHERE id = ?');
                    if ($stmt) {
                        $stmt->bind_param('i', $user_id);
                        if ($stmt->execute()) {
                            $msg = 'User admin status updated.';
                        } else {
                            log_error('Toggle admin failed: ' . $stmt->error, __FILE__, __LINE__);
                            $msg = 'Failed to update user.';
                        }
                        $stmt->close();
                    }
                } elseif ($action === 'delete') {
                    $stmt = $conn->prepare('DELETE FROM users WHERE id = ?');
                    if ($stmt) {
                        $stmt->bind_param('i', $user_id);
                        if ($stmt->execute()) {
                            $msg = 'User deleted.';
                        } else {
                            log_error('Delete user failed: ' . $stmt->error, __FILE__, __LINE__);
                            $msg = 'Failed to delete user.';
                        }
                        $stmt->close();
                    }
                }
            }
        }
    }
}

// Fetch users
$users = $conn->query('SELECT id, name, email, phone, isadmin, created_at FROM users ORDER BY id DESC');
$csrf = generate_csrf_token();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Users - Admin</title>
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
        <h1>Manage Users</h1>
        <?php if ($msg): ?><p class="error"><?= escape_output($msg) ?></p><?php endif; ?>
        <table>
            <thead>
                <tr><th>ID</th><th>Name</th><th>Email</th><th>Phone</th><th>Admin</th><th>Created</th><th>Action</th></tr>
            </thead>
            <tbody>
                <?php while ($u = $users->fetch_assoc()): ?>
                    <tr>
                        <td><?= $u['id'] ?></td>
                        <td><?= escape_output($u['name']) ?></td>
                        <td><?= escape_output($u['email']) ?></td>
                        <td><?= escape_output($u['phone']) ?></td>
                        <td><?= $u['isadmin'] ? 'Yes' : 'No' ?></td>
                        <td><?= $u['created_at'] ?></td>
                        <td>
                            <form method="post" style="display:inline-block;margin-right:6px">
                                <input type="hidden" name="csrf_token" value="<?= escape_output($csrf) ?>">
                                <input type="hidden" name="action" value="toggle_admin">
                                <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                <button type="submit">Toggle Admin</button>
                            </form>
                            <form method="post" style="display:inline-block" onsubmit="return confirm('Delete user? This will remove their reservations.')">
                                <input type="hidden" name="csrf_token" value="<?= escape_output($csrf) ?>">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                <button type="submit">Delete</button>
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
