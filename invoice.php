<?php
/**
 * Invoice Display
 * عرض الفاتورة
 */

require_once 'functions.php';

// Check if user is logged in
require_login();

// Check if reservation data exists in session
if (!isset($_SESSION['reservation_data'])) {
    $error = urlencode("No reservation data found. Please make a reservation first.");
    header("Location: index.php?error=" . $error);
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
require_once 'db.php';

// Verify reservation belongs to current user
$verify_stmt = $conn->prepare("SELECT customer_id FROM reservations WHERE id = ? AND customer_id = ?");
if (!$verify_stmt) {
    log_error("Invoice verify prepare failed: " . $conn->error, __FILE__, __LINE__);
    $error = urlencode("Database error. Please try again later.");
    header("Location: index.php?error=" . $error);
    exit;
}

$verify_stmt->bind_param("ii", $reservation_id, $_SESSION['id']);
$verify_stmt->execute();
$verify_result = $verify_stmt->get_result();

if ($verify_result->num_rows === 0) {
    $verify_stmt->close();
    $error = urlencode("Unauthorized access to invoice.");
    header("Location: index.php?error=" . $error);
    exit;
}
$verify_stmt->close();

// Get car information
$stmt = $conn->prepare("SELECT name, model FROM cars WHERE id = ?");
if (!$stmt) {
    log_error("Invoice car query prepare failed: " . $conn->error, __FILE__, __LINE__);
    $error = urlencode("Database error. Please try again later.");
    header("Location: index.php?error=" . $error);
    exit;
}

$stmt->bind_param("i", $car_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $stmt->close();
    $error = urlencode("Car not found.");
    header("Location: index.php?error=" . $error);
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

<!DOCTYPE HTML>
<html lang="en">
<head>
    <title>Car Rental Portal | Invoice</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f6f9;
            margin: 0;
            padding: 0;
            color: #333;
        }

        #container {
            margin: 50px auto;
            max-width: 800px;
            padding: 20px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .receipt-main {
            width: 100%;
            background: #fff;
            padding: 20px;
            border-radius: 8px;
        }

        .receipt-header {
            margin-bottom: 20px;
        }

        .receipt-header img {
            max-width: 100px;
        }

        .receipt-header h1 {
            font-size: 24px;
            color:rgb(31, 58, 87);
            text-align: right;
        }

        .receipt-header-mid {
            margin-bottom: 20px;
        }

        .receipt-header-mid p {
            margin: 5px 0;
        }

        .receipt-header-mid b {
            color:rgb(28, 65, 104);
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .table thead th {
            background:rgb(48, 89, 132);
            color: white;
            padding: 10px;
            text-align: left;
        }

        .table tbody td {
            padding: 10px;
            border: 1px solid #ddd;
            background: #f9f9f9;
        }

        .table tbody tr:nth-child(even) td {
            background: #eef5ff;
        }

        .receipt-footer {
            margin-top: 20px;
            text-align: center;
            font-size: 14px;
            color: #555;
        }

        .receipt-footer h5 {
            color:rgb(22, 76, 133);
            margin-bottom: 10px;
        }

        @media (max-width: 768px) {
            #container {
                padding: 10px;
            }

            .receipt-header img {
                max-width: 80px;
            }

            .receipt-header h1 {
                font-size: 20px;
            }

            .table thead th, 
            .table tbody td {
                padding: 8px;
                font-size: 14px;
            }
        }
    </style>
</head>
<body>
    <div id="container">
        <div class="receipt-main">
            <div class="receipt-header">
                <div class="row">
                    <div class="col-xs-6">
                          <img alt="Logo" src="img/icon b copy.png" class="img-responsive">
                    </div>
                    <div class="col-xs-6">
                        <h1>Receipt</h1>
                    </div>
                </div>
            </div>
            <div class="receipt-header-mid">
                <div class="row">
                    <div>
                        <p><b>Username:</b> <?= escape_output($_SESSION['userName']) ?></p>
                        <p><b>Phone:</b> <?= escape_output($user_phone) ?></p>

                    </div>
                </div>
            </div>
            <table class="table">
                <thead>
                    <tr>
                        <th>Details</th>
                        <th>Value</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Car Model</td>
                        <td><?= escape_output($car['name'] . ' ' . $car['model']) ?></td>
                    </tr>
                    <tr>
                        <td>Rental Period</td>
                        <td><?= escape_output($pickup_display) ?> - <?= escape_output($return_display) ?> (<?= escape_output($duration_label) ?>)</td>
                    </tr>
                    <tr>
                        <td>Daily Rate</td>
                        <td>$<?= number_format($daily_rate, 2) ?></td>
                    </tr>
                    <tr>
                        <td>Subtotal</td>
                        <td>$<?= number_format($total_cost, 2) ?></td>
                    </tr>
                    <tr>
                        <td>Tax (10%)</td>
                        <td>$<?= number_format($total_cost * 0.1, 2) ?></td>
                    </tr>
                    <tr>
                        <td>Total</td>
                        <td><b>$<?= number_format($total_cost + ($total_cost * 0.1), 2) ?></b></td>
                    </tr>
                </tbody>
            </table>
            <div class="receipt-footer">
                <h5>Thank you for your business!</h5>
                <p>Date: <?= date('Y-m-d') ?> | Time: <?= date('h:i A') ?></p>
                <div style="margin-top: 20px;">
                    <a href="payment.php?reservation_id=<?= (int)$reservation_id ?>" 
                       style="display: inline-block; padding: 12px 30px; background: #4a90e2; color: white; text-decoration: none; border-radius: 5px; font-weight: bold;">
                        Proceed to Payment
                    </a>
                    <form method="POST" action="return_confirm.php" style="display: inline-block; margin-left: 10px;">
                        <input type="hidden" name="csrf_token" value="<?= escape_output($return_csrf) ?>">
                        <input type="hidden" name="reservation_id" value="<?= (int)$reservation_id ?>">
                        <button type="submit" style="padding: 12px 30px; background: #2c7a7b; color: white; border: none; border-radius: 5px; font-weight: bold; cursor: pointer;">
                            Confirm Return
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
