<?php
/**
 * Public portfolio demo: simulated payment ONLY.
 * No real card information is requested or handled.
 */
require_once '../includes/functions.php';
require_login();
require_once '../includes/db.php';

header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');

$reservationId = (int)($_POST['reservation_id'] ?? $_GET['reservation_id'] ?? 0);
$userId = (int)$_SESSION['id'];
$paymentError = '';
$paymentSuccess = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $paymentError = 'Security token mismatch. Please try again.';
    } elseif (array_intersect(['cardNumber', 'cardName', 'cardMonth', 'cardYear', 'cardCvv'], array_keys($_POST))) {
        $paymentError = 'Card details are not accepted by this demo.';
    } elseif (($_POST['action'] ?? '') !== 'simulate_demo_payment' || $reservationId <= 0) {
        $paymentError = 'Invalid demo payment request.';
    } else {
        $transactionStarted = false;
        try {
            $conn->begin_transaction();
            $transactionStarted = true;
            $stmt = $conn->prepare(
                'SELECT id, car_id, total_cost, reservation_status FROM reservations
                 WHERE id = ? AND customer_id = ? FOR UPDATE'
            );
            $stmt->bind_param('ii', $reservationId, $userId);
            $stmt->execute();
            $reservation = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            if (!$reservation || $reservation['reservation_status'] !== 'pending') {
                throw new RuntimeException('Reservation is missing or not pending.');
            }
            $check = $conn->prepare(
                "SELECT id FROM payment_transactions
                 WHERE reservation_id = ? AND status = 'completed' LIMIT 1"
            );
            $check->bind_param('i', $reservationId);
            $check->execute();
            $hasCompletedPayment = $check->get_result()->num_rows > 0;
            $check->close();
            if ($hasCompletedPayment) {
                throw new RuntimeException('A completed demo payment already exists.');
            }
            $carId = (int)$reservation['car_id'];
            $amount = (float)$reservation['total_cost'];
            $insert = $conn->prepare(
                "INSERT INTO payment_transactions
                (reservation_id, car_id, amount, payment_method, status, transaction_date, created_at)
                VALUES (?, ?, ?, 'online', 'completed', NOW(), NOW())"
            );
            $insert->bind_param('iid', $reservationId, $carId, $amount);
            $insert->execute();
            $insert->close();
            $update = $conn->prepare(
                "UPDATE reservations SET reservation_status = 'confirmed'
                 WHERE id = ? AND customer_id = ? AND reservation_status = 'pending'"
            );
            $update->bind_param('ii', $reservationId, $userId);
            $update->execute();
            if ($update->affected_rows !== 1) {
                throw new RuntimeException('Reservation could not be confirmed.');
            }
            $update->close();
            $conn->commit();
            $transactionStarted = false;
            $paymentSuccess = true;
        } catch (Throwable $error) {
            if ($transactionStarted) {
                $conn->rollback();
            }
            log_error('Demo payment failed: ' . $error->getMessage(), __FILE__, __LINE__);
            $paymentError = 'Unable to complete demo payment. Please check your reservation status.';
        }
    }
}
$reservation = null;
if ($reservationId > 0) {
    $stmt = $conn->prepare(
        'SELECT id, total_cost, reservation_status
         FROM reservations WHERE id = ? AND customer_id = ?'
    );
    $stmt->bind_param('ii', $reservationId, $userId);
    $stmt->execute();
    $reservation = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}
if (!$reservation && !$paymentError) {
    $paymentError = 'Reservation not found or not authorized.';
}
$canPay = $reservation && $reservation['reservation_status'] === 'pending' && !$paymentSuccess;
$csrfToken = generate_csrf_token();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Simulated Payment | Car Rental Management System</title>
  <link rel="stylesheet" href="../css/ui-payment.css?v=5">
  <style>
    * { box-sizing: border-box; }
    body { margin: 0; background: #f3f6fb; color: #203047; font: 16px/1.6 Arial, sans-serif; }
    main { width: min(100% - 32px, 620px); margin: 60px auto; padding: 30px; background: white; border-radius: 16px; box-shadow: 0 14px 40px rgba(0,0,0,.07); }
    h1 { margin-top: 4px; }
    .tag { display: inline-block; padding: 6px 12px; border-radius: 20px; background: #e5f1ff; color: #1c4f88; font-weight: bold; }
    .note { margin: 20px 0; padding: 16px; border-left: 4px solid #bd8a16; background: #fff3d9; }
    .error { padding: 12px; border-radius: 7px; background: #ffeaec; color: #822; }
    .success { padding: 12px; border-radius: 7px; background: #e4f6e9; color: #154f2d; }
    .summary { border-block: 1px solid #e1e6ef; padding: 18px 0; margin: 22px 0; }
    .amount { font-size: 1.6rem; font-weight: 700; margin: 0; }
    button, .back { display: block; width: 100%; padding: 13px 16px; border-radius: 9px; font: inherit; font-weight: 700; text-decoration: none; text-align: center; margin-top: 12px; cursor: pointer; }
    button { background: #2268b1; border: 0; color: white; }
    .back { border: 1px solid #d3d9e1; color: #24364d; background: white; }
  </style>
</head>
<body class="ui-payment">
  <main>
    <a class="payment-back" href="../index.php">← Browse vehicles</a>
    <span class="tag">PORTFOLIO DEMO</span>
    <h1>Complete your demo reservation</h1><p class="payment-description">A secure, transparent simulation to demonstrate the booking workflow.</p>
    <div class="note"><strong>No real payment or bank transfer.</strong>
      This is a demonstration only. Do not enter or send card information.
    </div>
    <?php if ($paymentError): ?>
      <p role="alert" class="error"><?= escape_output($paymentError) ?></p>
    <?php endif; ?>
    <?php if ($paymentSuccess): ?>
      <p role="status" class="success">Simulation completed. Reservation confirmed for demo purposes only.</p>
    <?php endif; ?>
    <?php if ($reservation): ?>
      <div class="summary">
        <p>Reservation #<?= (int)$reservation['id'] ?></p>
        <p class="amount">$<?= number_format((float)$reservation['total_cost'], 2) ?></p>
        <p>Status: <?= escape_output(ucfirst($reservation['reservation_status'])) ?></p>
        <small>Illustrative amount only. No funds are collected.</small>
      </div>
      <?php if ($canPay): ?>
        <form method="post" action="payment.php?reservation_id=<?= (int)$reservationId ?>">
          <input type="hidden" name="reservation_id" value="<?= (int)$reservationId ?>">
          <input type="hidden" name="csrf_token" value="<?= escape_output($csrfToken) ?>">
          <input type="hidden" name="action" value="simulate_demo_payment">
          <button type="submit">Confirm simulated payment (no charge)</button>
        </form>
      <?php elseif (!$paymentSuccess): ?>
        <p>No additional simulated payment is available for this reservation.</p>
      <?php endif; ?>
    <?php endif; ?>
    <a class="back" href="../index.php">Back to cars</a>
  </main>
</body>
</html>
