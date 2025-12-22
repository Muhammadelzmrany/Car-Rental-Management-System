<?php
/**
 * Rent Car Flow with flexible duration units (hours/days/weeks/months/years)
 */

require_once 'functions.php';
require_login();
require_once 'db.php';

$method = $_SERVER['REQUEST_METHOD'];

/**
 * Helper: fetch car by id (optionally for update)
 */
function fetch_car($conn, $car_id, $for_update = false) {
    $sql = $for_update
        ? "SELECT id, name, model, year, price_per_day, branch_id, isavailable FROM cars WHERE id = ? FOR UPDATE"
        : "SELECT id, name, model, year, price_per_day, branch_id, isavailable FROM cars WHERE id = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception("Database query preparation failed.");
    }
        $stmt->bind_param("i", $car_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows === 0) {
        $stmt->close();
            throw new Exception("Car not found.");
        }
    $car = $result->fetch_assoc();
    $stmt->close();
    return $car;
}

if ($method === 'GET') {
    $car_id = isset($_GET['car_id']) ? (int)$_GET['car_id'] : 0;
    if ($car_id <= 0) {
        header("Location: index.php?error=" . urlencode("No car selected."));
        exit;
    }

    try {
        $car = fetch_car($conn, $car_id, false);
        if ((int)$car['isavailable'] === 0) {
            header("Location: index.php?error=" . urlencode("The selected car is not available."));
            exit;
        }
    } catch (Exception $e) {
        log_error("Rent car load error: " . $e->getMessage(), __FILE__, __LINE__);
        header("Location: index.php?error=" . urlencode($e->getMessage()));
        exit;
    }

    $csrf_token = generate_csrf_token();
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Rent Car</title>
        <link rel="stylesheet" href="style.css">
        <style>
            .rent-form-wrapper { max-width: 500px; margin: 40px auto; padding: 20px; background: #fff; border: 1px solid #ddd; border-radius: 8px; }
            .rent-form-wrapper h2 { margin-bottom: 15px; }
            .rent-form-wrapper label { display: block; margin: 10px 0 4px; }
            .rent-form-wrapper input, .rent-form-wrapper select { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; }
            .rent-form-wrapper button { margin-top: 15px; width: 100%; padding: 12px; background: #1e3a63; color: #fff; border: none; border-radius: 4px; cursor: pointer; }
            .rent-form-wrapper button:hover { background: #162c4a; }
            .rent-summary { margin-bottom: 10px; }
        </style>
    </head>
    <body>
        <div class="rent-form-wrapper">
            <div class="rent-summary">
                <strong><?= escape_output($car['name'] . ' ' . $car['model'] . ' ' . $car['year']) ?></strong><br>
                Daily rate: $<?= number_format((float)$car['price_per_day'], 2) ?>
            </div>
            <form method="POST" action="rent.php">
                <input type="hidden" name="csrf_token" value="<?= escape_output($csrf_token) ?>">
                <input type="hidden" name="car_id" value="<?= (int)$car['id'] ?>">

                <label for="pickup_datetime">Pickup date & time</label>
                <input type="datetime-local" id="pickup_datetime" name="pickup_datetime" required>

                <label for="duration_value">Duration</label>
                <input type="number" id="duration_value" name="duration_value" value="1" min="0.25" step="0.25" required>

                <label for="duration_unit">Unit</label>
                <select id="duration_unit" name="duration_unit" required>
                    <option value="hours">Hours</option>
                    <option value="days" selected>Days</option>
                    <option value="weeks">Weeks</option>
                    <option value="months">Months</option>
                    <option value="years">Years</option>
                </select>

                <button type="submit">Confirm Rental</button>
            </form>
        </div>
        <script>
            // Set default and min to the client's current local time (up to minutes)
            (function() {
                const input = document.getElementById('pickup_datetime');
                if (!input) return;
                const now = new Date();
                now.setSeconds(0, 0);
                const pad = (value) => String(value).padStart(2, '0');
                const localValue = [
                    now.getFullYear(),
                    pad(now.getMonth() + 1),
                    pad(now.getDate())
                ].join('-') + 'T' + [
                    pad(now.getHours()),
                    pad(now.getMinutes())
                ].join(':');
                input.value = localValue;
                input.min = localValue;
            })();
        </script>
    </body>
    </html>
    <?php
    exit;
}

// POST: process rental
if ($method !== 'POST') {
    header("Location: index.php?error=" . urlencode("Invalid request method."));
    exit;
}

if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
    header("Location: index.php?error=" . urlencode("Security token mismatch. Please try again."));
    exit;
}

$car_id = isset($_POST['car_id']) ? (int)$_POST['car_id'] : 0;
if ($car_id <= 0) {
    header("Location: index.php?error=" . urlencode("No car selected."));
    exit;
}

$pickup_input = $_POST['pickup_datetime'] ?? '';
$duration_value = isset($_POST['duration_value']) ? (float)$_POST['duration_value'] : 0;
$duration_unit = $_POST['duration_unit'] ?? 'days';

if ($duration_value <= 0) {
    header("Location: index.php?error=" . urlencode("Duration must be greater than zero."));
    exit;
}

$pickup_dt = DateTime::createFromFormat('Y-m-d\TH:i', $pickup_input);
if (!$pickup_dt) {
    // Try generic parsing as a fallback
    try {
        $pickup_dt = new DateTime($pickup_input);
    } catch (Exception $e) {
        $pickup_dt = false;
    }
}

if (!$pickup_dt) {
    header("Location: index.php?error=" . urlencode("Invalid pickup date/time."));
    exit;
}

$pickup_ts = $pickup_dt->getTimestamp();

// Convert duration to seconds (approximate months/years as 30/365 days respectively)
switch ($duration_unit) {
    case 'hours':
        $seconds = (int)round($duration_value * 3600);
        break;
    case 'days':
        $seconds = (int)round($duration_value * 86400);
        break;
    case 'weeks':
        $seconds = (int)round($duration_value * 7 * 86400);
        break;
    case 'months':
        $seconds = (int)round($duration_value * 30 * 86400);
        break;
    case 'years':
        $seconds = (int)round($duration_value * 365 * 86400);
        break;
    default:
        header("Location: index.php?error=" . urlencode("Invalid duration unit."));
        exit;
}

if ($seconds <= 0) {
    header("Location: index.php?error=" . urlencode("Duration must be greater than zero."));
    exit;
}

$return_ts = $pickup_ts + $seconds;
$return_dt = (new DateTime())->setTimestamp($return_ts);

if ($return_ts <= $pickup_ts) {
    header("Location: index.php?error=" . urlencode("Return time must be after pickup time."));
    exit;
}

$duration_hours = $seconds / 3600;
$duration_days_fraction = $duration_hours / 24;

// Pre-check: prevent multiple reservations for the same customer until the first rental period ends
$active_check = $conn->prepare("
    SELECT id
    FROM reservations
    WHERE customer_id = ?
      AND COALESCE(return_time, CONCAT(return_date, ' 23:59:59')) >= NOW()
      AND reservation_status <> 'cancelled'
    LIMIT 1
");
if ($active_check) {
    $active_check->bind_param("i", $_SESSION['id']);
    $active_check->execute();
    $active_result = $active_check->get_result();
    if ($active_result && $active_result->num_rows > 0) {
        $active_check->close();
        header("Location: index.php?error=" . urlencode("You already have an active reservation."));
        exit;
    }
    $active_check->close();
}

try {
    // Begin transaction
    $conn->begin_transaction();

    // Prevent double booking by the same customer until the first rental period ends
    $active_stmt = $conn->prepare("
        SELECT id
        FROM reservations
        WHERE customer_id = ?
          AND COALESCE(return_time, CONCAT(return_date, ' 23:59:59')) >= NOW()
          AND reservation_status <> 'cancelled'
        LIMIT 1
        FOR UPDATE
    ");
    if (!$active_stmt) {
        throw new Exception("Active reservation check failed.");
    }
    $active_stmt->bind_param("i", $_SESSION['id']);
    $active_stmt->execute();
    $active_result = $active_stmt->get_result();
    if ($active_result && $active_result->num_rows > 0) {
        $active_stmt->close();
        throw new Exception("You already have an active reservation.");
    }
    $active_stmt->close();

    // Lock and fetch car
    $car = fetch_car($conn, $car_id, true);
    if ((int)$car['isavailable'] === 0) {
        throw new Exception("The selected car is not available.");
    }

    // Calculate costs
    $daily_rate = (float)$car['price_per_day'];
    $total_cost = round($daily_rate * $duration_days_fraction, 2);

    // Insert reservation (store date parts for compatibility)
    $pickup_date = $pickup_dt->format('Y-m-d');
    $return_date = $return_dt->format('Y-m-d');
    $branch_id = (int)$car['branch_id'];

    // CRITICAL: Check for double booking (date overlap prevention)
    $overlap_check = $conn->prepare("
        SELECT COUNT(*) as overlap_count 
        FROM reservations 
        WHERE car_id = ? 
          AND reservation_status IN ('pending', 'confirmed', 'picked_up')
          AND (
            (pickup_date <= ? AND return_date >= ?) OR
            (pickup_date <= ? AND return_date >= ?) OR
            (pickup_date >= ? AND return_date <= ?)
          )
    ");
    if (!$overlap_check) {
        throw new Exception("Database overlap check preparation failed.");
    }
    $overlap_check->bind_param("issssss", $car_id, 
        $pickup_date, $pickup_date,  // New pickup overlaps existing
        $return_date, $return_date,   // New return overlaps existing
        $pickup_date, $return_date    // New reservation inside existing
    );
    $overlap_check->execute();
    $overlap_result = $overlap_check->get_result()->fetch_assoc();
    $overlap_check->close();
    
    if ($overlap_result['overlap_count'] > 0) {
        throw new Exception("Car is already reserved for overlapping dates. Please choose different dates.");
    }

    // Insert reservation
    $insert_stmt = $conn->prepare("
        INSERT INTO reservations (customer_id, car_id, branch_id, pickup_date, return_date, pickup_time, actual_pickup_date, total_cost, reservation_status)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    if (!$insert_stmt) {
        throw new Exception("Database insert preparation failed.");
    }
    $user_id = $_SESSION['id'];
    $reservation_status = 'pending';
    $pickup_time = $pickup_dt->format('Y-m-d H:i:s');
    $actual_pickup_date = $pickup_dt->format('Y-m-d');
    $insert_stmt->bind_param(
        "iiissssds",
        $user_id,
        $car_id,
        $branch_id,
        $pickup_date,
        $return_date,
        $pickup_time,
        $actual_pickup_date,
        $total_cost,
        $reservation_status
    );
    $insert_stmt->execute();
    $reservation_id = $insert_stmt->insert_id;
    $insert_stmt->close();

    // Update car availability and status
    $update_stmt = $conn->prepare("UPDATE cars SET isavailable = 0, status = 'rented' WHERE id = ?");
    if (!$update_stmt) {
        throw new Exception("Database update preparation failed.");
    }
    $update_stmt->bind_param("i", $car_id);
    $update_stmt->execute();
    $update_stmt->close();

    // Record payment transaction linked to the reservation and car
    $payment_method = 'cash'; // default method for on-site/standard bookings
    $payment_stmt = $conn->prepare("
        INSERT INTO payment_transactions (reservation_id, car_id, amount, payment_method, status, transaction_date, created_at)
        VALUES (?, ?, ?, ?, 'completed', NOW(), NOW())
    ");
    if (!$payment_stmt) {
        throw new Exception("Payment transaction preparation failed.");
    }
    $payment_stmt->bind_param("iids", $reservation_id, $car_id, $total_cost, $payment_method);
    $payment_stmt->execute();
    $payment_stmt->close();

    $conn->commit();

    // Store reservation data in session for invoice
    $_SESSION['reservation_data'] = [
        'reservation_id' => $reservation_id,
        'car_id' => $car_id,
        'pickup_datetime' => $pickup_dt->format('Y-m-d H:i'),
        'return_datetime' => $return_dt->format('Y-m-d H:i'),
        'pickup_date' => $pickup_date,
        'return_date' => $return_date,
        'duration_label' => $duration_value . ' ' . $duration_unit,
        'duration_hours' => $duration_hours,
        'duration_days' => $duration_days_fraction,
        'daily_rate' => $daily_rate,
        'total_cost' => $total_cost
    ];

    header("Location: invoice.php");
        exit;

    } catch (Exception $e) {
    if ($conn->in_transaction) {
        $conn->rollback();
    }
    log_error("Rent car error: " . $e->getMessage(), __FILE__, __LINE__);
    header("Location: index.php?error=" . urlencode("An error occurred: " . $e->getMessage()));
    exit;
}
