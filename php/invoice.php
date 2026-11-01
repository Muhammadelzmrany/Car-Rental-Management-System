<?php
/**
 * Invoice Display
 * عرض الفاتورة
 */

require_once '../includes/functions.php';

// Check if user is logged in
require_login();

// Check if reservation data exists in session
if (!isset($_SESSION['reservation_data'])) {
    $error = urlencode("No reservation data found. Please make a reservation first.");
    header("Location: ../index.php?error=" . $error);
    exit;
}

$reservation_data = $_SESSION['reservation_data'];
$car_id = (int)$reservation_data['car_id'];
$daily_rate = (float)$reservation_data['daily_rate'];
$total_cost = (float)$reservation_data['total_cost'];
$reservation_id = (int)$reservation_data['reservation_id'];

// Support flexible duration data (hours/days/weeks/months/years)
$pickup_display = $reservation_data['pickup_datetime'] ?? ($reservation_data['pickup_date'] ?? '');
$return_display = $reservation_data['return_datetime'] ?? ($reservation_data['return_date'] ?? '');
$duration_label = $reservation_data['duration_label'] ?? (($reservation_data['days'] ?? 0) . ' days');
$duration_days = $reservation_data['duration_days'] ?? ($reservation_data['days'] ?? 0);
$duration_hours = $reservation_data['duration_hours'] ?? ($duration_days * 24);
$return_csrf = generate_csrf_token();

// Get database connection
require_once '../includes/db.php';

// Verify reservation belongs to current user
$verify_stmt = $conn->prepare("SELECT customer_id, reservation_status FROM reservations WHERE id = ? AND customer_id = ?");
if (!$verify_stmt) {
    log_error("Invoice verify prepare failed: " . $conn->error, __FILE__, __LINE__);
    $error = urlencode("Database error. Please try again later.");
    header("Location: ../index.php?error=" . $error);
    exit;
}

$verify_stmt->bind_param("ii", $reservation_id, $_SESSION['id']);
$verify_stmt->execute();
$verify_result = $verify_stmt->get_result();

if ($verify_result->num_rows === 0) {
    $verify_stmt->close();
    $error = urlencode("Unauthorized access to invoice.");
    header("Location: ../index.php?error=" . $error);
    exit;
}
$reservation_row = $verify_result->fetch_assoc();
$reservation_status = $reservation_row['reservation_status'];
$verify_stmt->close();

// Get car information
$stmt = $conn->prepare("SELECT name, model FROM cars WHERE id = ?");
if (!$stmt) {
    log_error("Invoice car query prepare failed: " . $conn->error, __FILE__, __LINE__);
    $error = urlencode("Database error. Please try again later.");
    header("Location: ../index.php?error=" . $error);
    exit;
}

$stmt->bind_param("i", $car_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $stmt->close();
    $error = urlencode("Car not found.");
    header("Location: ../index.php?error=" . $error);
    exit;
}

$car = $result->fetch_assoc();
$stmt->close();

// Get user phone from database
$user_stmt = $conn->prepare("SELECT phone FROM users WHERE id = ?");
$user_phone = 'Not available';
if ($user_stmt) {
    $user_stmt->bind_param("i", $_SESSION['id']);
    $user_stmt->execute();
    $user_result = $user_stmt->get_result();
    if ($user_row = $user_result->fetch_assoc()) {
        $user_phone = $user_row['phone'] ?? 'Not available';
    }
    $user_stmt->close();
}
?>

<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Reservation #<?= (int)$reservation_id ?> | Car Rental</title>
  <link rel="stylesheet" href="../css/booking-pages.css?v=2">
</head>
<body class="cr-page">
  <nav class="cr-nav cr-no-print"><div class="cr-nav-inner">
    <a class="cr-logo" href="../index.php">CAR <span>RENTAL</span></a>
    <a class="cr-link" href="../index.php">← Back to cars</a>
  </div></nav>
  <main class="cr-main">
    <div class="cr-invoice">
      <p class="cr-overline">Step 2 of 2 · Reservation</p>
      <h1 class="cr-title">Reservation summary</h1>
      <p class="cr-subtitle cr-no-print">Review your details and proceed to simulated payment.</p>
      <article class="cr-card">
        <header class="cr-invoice-header">
          <div>
            <span class="cr-tag">CAR RENTAL · DEMO</span>
            <h2>Reservation #<?= (int)$reservation_id ?></h2>
            <p class="cr-foot">Created <?= escape_output(date('M j, Y')) ?></p>
          </div>
          <span class="cr-status cr-status-<?= escape_output($reservation_status) ?>"><?= escape_output(str_replace('_', ' ', ucfirst($reservation_status))) ?></span>
        </header>
        <p class="cr-note">This is a portfolio demonstration, not a real rental contract or tax invoice. No real payments.</p>
        <div class="cr-meta">
          <div><span>Customer</span><strong><?= escape_output($_SESSION['userName'] ?? '') ?></strong></div>
          <div><span>Phone</span><strong><?= escape_output($user_phone) ?></strong></div>
        </div>
        <table class="cr-table">
          <thead><tr><th scope="col">Description</th><th scope="col">Details</th></tr></thead>
          <tbody>
            <tr><td>Vehicle</td><td><?= escape_output($car['name'] . ' ' . $car['model']) ?></td></tr>
            <tr><td>Pickup</td><td><?= escape_output($pickup_display) ?></td></tr>
            <tr><td>Return</td><td><?= escape_output($return_display) ?></td></tr>
            <tr><td>Duration</td><td><?= escape_output($duration_label) ?></td></tr>
            <tr><td>Daily rate</td><td>$<?= number_format($daily_rate, 2) ?></td></tr>
            <tr class="cr-total"><td>Total (demo)</td><td>$<?= number_format($total_cost, 2) ?></td></tr>
          </tbody>
        </table>
        <div class="cr-actions">
          <?php if ($reservation_status === 'pending'): ?>
            <a class="cr-btn" href="payment.php?reservation_id=<?= (int)$reservation_id ?>">Simulate payment — no charge</a>
          <?php elseif (in_array($reservation_status, ['confirmed', 'picked_up'], true)): ?>
            <form method="POST" action="return_confirm.php">
              <input type="hidden" name="csrf_token" value="<?= escape_output($return_csrf) ?>">
              <input type="hidden" name="reservation_id" value="<?= (int)$reservation_id ?>">
              <button class="cr-btn cr-btn-green" type="submit">Confirm demo return</button>
            </form>
          <?php else: ?>
            <span>Reservation status: <?= escape_output($reservation_status) ?></span>
          <?php endif; ?>
          <button class="cr-btn cr-btn-alt cr-no-print" type="button" onclick="window.print()">Print summary</button>
        </div>
        <p class="cr-foot">All prices are illustrative; this document does not prove that any money was paid.</p>
      </article>
    </div>
  </main>
</body>
</html>
