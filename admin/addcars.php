<?php
/**
 * Add New Car (Admin)
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
// Handle POST (add car)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $msg = 'Security token mismatch.';
    } else {
        $name = sanitize_input($_POST['name'] ?? '');
        $model = sanitize_input($_POST['model'] ?? '');
        $year = (int)($_POST['year'] ?? 0);
        $plate = sanitize_input($_POST['plate_id'] ?? '');
        $status = sanitize_input($_POST['status'] ?? 'available');
        $price = (float)($_POST['price'] ?? 0);
        $branch_id = (int)($_POST['branch_id'] ?? 0);
        $isavailable = isset($_POST['isavailable']) ? 1 : 0;

        // Basic validation
        if (empty($name) || empty($model) || empty($plate)) {
            $msg = 'Please fill required fields (name, model, plate id).';
        } else {
            // Optional image
            $image_name = null;
            if (!empty($_FILES['image']['tmp_name'])) {
                $valid = validate_uploaded_file($_FILES['image']);
                if (!$valid['valid']) {
                    $msg = $valid['error'];
                } else {
                    $image_name = clean_filename($_FILES['image']['name']);
                    $dest = dirname(__DIR__) . '/uploads/' . $image_name;
                    if (!move_uploaded_file($_FILES['image']['tmp_name'], $dest)) {
                        $msg = 'Failed to save uploaded image.';
                    }
                }
            }

            if (empty($msg)) {
                $sql = "INSERT INTO cars (name, model, year, plate_id, status, price_per_day, branch_id, isavailable, image_name) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                if (!$stmt) {
                    log_error('Add car prepare failed: ' . $conn->error, __FILE__, __LINE__);
                    $msg = 'Database error.';
                } else {
                    $image_name = $image_name ?? '';
                    $types = 'ssissdiis';
                    $stmt->bind_param($types, $name, $model, $year, $plate, $status, $price, $branch_id, $isavailable, $image_name);
                    if ($stmt->execute()) {
                        $msg = 'Car added successfully.';
                    } else {
                        log_error('Add car execute failed: ' . $stmt->error, __FILE__, __LINE__);
                        $msg = 'Failed to add car.';
                    }
                    $stmt->close();
                }
            }
        }
    }
}

$csrf = generate_csrf_token();
// Load branches for dropdown
$branches = $conn->query('SELECT id, name FROM branches');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Car - Admin</title>
    <link rel="stylesheet" href="../css/style.css?v=3">
    <link rel="stylesheet" href="../css/admin.css?v=3">
    <style>label{display:block;margin-top:8px}</style>
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
        <h1>Add New Car</h1>
        <?php if ($msg): ?><p class="error"><?= escape_output($msg) ?></p><?php endif; ?>
        <form method="post" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?= escape_output($csrf) ?>">
            <label>Name <input type="text" name="name" required></label>
            <label>Model <input type="text" name="model" required></label>
            <label>Year <input type="number" name="year" min="1900" max="2100"></label>
            <label>Plate ID <input type="text" name="plate_id" required></label>
            <label>Status
                <select name="status">
                    <option value="available">available</option>
                    <option value="rented">rented</option>
                    <option value="out_of_service">out_of_service</option>
                </select>
            </label>
            <label>Price/day <input type="number" step="0.01" name="price"></label>
            <label>Branch
                <select name="branch_id">
                    <option value="0">-- Select branch --</option>
                    <?php while ($b = $branches->fetch_assoc()): ?>
                        <option value="<?= $b['id'] ?>"><?= escape_output($b['name']) ?></option>
                    <?php endwhile; ?>
                </select>
            </label>
            <label><input type="checkbox" name="isavailable" checked> Is Available</label>
            <label>Image <input type="file" name="image" accept="image/*"></label>
            <div style="margin-top:12px"><button type="submit">Add Car</button> <a href="index.php">Cancel</a></div>
        </form>
    </div>
</body>
</html>
