<?php
/**
 * Handle customer return confirmation then redirect to payment (Visa).
 */

require_once '../includes/functions.php';
require_login();
require_once '../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../index.php");
    exit;
}

// CSRF check
if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
    header("Location: ../index.php?error=" . urlencode("Security token mismatch. Please try again."));
    exit;
}

$reservation_id = isset($_POST['reservation_id']) ? (int)$_POST['reservation_id'] : 0;
if ($reservation_id <= 0) {
    header("Location: ../index.php?error=" . urlencode("Invalid reservation."));
    exit;
}

try {
    // Fetch reservation and ownership
    $fetch_stmt = $conn->prepare("SELECT id, car_id, reservation_status FROM reservations WHERE id = ? AND customer_id = ?");
    if (!$fetch_stmt) {
        throw new Exception("Failed to prepare reservation lookup.");
    }
    $fetch_stmt->bind_param("ii", $reservation_id, $_SESSION['id']);
    $fetch_stmt->execute();
    $reservation = $fetch_stmt->get_result()->fetch_assoc();
    $fetch_stmt->close();

    if (!$reservation) {
        throw new Exception("Reservation not found or unauthorized.");
    }

    $car_id = (int)$reservation['car_id'];
    $current_status = $reservation['reservation_status'];

    // Allow return only if not cancelled
    if ($current_status === 'cancelled') {
        throw new Exception("Cancelled reservations cannot be returned.");
    }

    $return_time = date('Y-m-d H:i:s');
    $actual_return_date = date('Y-m-d');

    $conn->begin_transaction();

    // Mark reservation as returned
    $update_stmt = $conn->prepare("UPDATE reservations SET return_time = ?, actual_return_date = ?, reservation_status = 'returned' WHERE id = ?");
    if (!$update_stmt) {
        throw new Exception("Failed to prepare reservation update.");
    }
    $update_stmt->bind_param("ssi", $return_time, $actual_return_date, $reservation_id);
    $update_stmt->execute();
    $update_stmt->close();

    // Free up the car
    $car_stmt = $conn->prepare("UPDATE cars SET status = 'available', isavailable = 1 WHERE id = ?");
    if ($car_stmt) {
        $car_stmt->bind_param("i", $car_id);
        $car_stmt->execute();
        $car_stmt->close();
    }

    $conn->commit();

    // Redirect to Visa payment step
    header("Location: payment.php?reservation_id={$reservation_id}&from_return=1");
    exit;
} catch (Exception $e) {
    if ($conn->in_transaction) {
        $conn->rollback();
    }
    log_error("Return confirmation error: " . $e->getMessage(), __FILE__, __LINE__);
    header("Location: ../index.php?error=" . urlencode("Return processing failed. Please try again."));
    exit;
}
