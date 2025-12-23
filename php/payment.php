<?php
require_once '../includes/functions.php';
require_login();
require_once '../includes/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$payment_error = '';
$payment_success = '';
$redirect_after_success = false;
$redirect_delay_seconds = 3;
$redirect_target = '../index.php';
$from_return = (isset($_GET['from_return']) && $_GET['from_return'] == '1') || (isset($_POST['from_return']) && $_POST['from_return'] == '1');
$car_id = 0;
$reservation_id = isset($_GET['reservation_id']) ? (int)$_GET['reservation_id'] : 0;

// Validate reservation_id and check ownership
if ($reservation_id <= 0) {
    $payment_error = 'Invalid reservation ID.';
} else {
    $verify_stmt = $conn->prepare("SELECT id, car_id, total_cost, reservation_status FROM reservations WHERE id = ? AND customer_id = ?");
    if ($verify_stmt) {
        $verify_stmt->bind_param("ii", $reservation_id, $_SESSION['id']);
        $verify_stmt->execute();
        $verify_result = $verify_stmt->get_result();
        if ($verify_result->num_rows === 0) {
            $payment_error = 'Reservation not found or unauthorized access.';
        } else {
            $reservation = $verify_result->fetch_assoc();
            $total_amount = (float)$reservation['total_cost'];
            $car_id = (int)$reservation['car_id'];
            if ($reservation['reservation_status'] === 'cancelled') {
                $payment_error = 'This reservation is already completed or cancelled.';
            } elseif ($reservation['reservation_status'] === 'returned' && !$from_return) {
                $payment_error = 'This reservation is already completed or cancelled.';
            } else {
                // Prevent duplicate payments
                $existing_payment_stmt = $conn->prepare("SELECT id FROM payment_transactions WHERE reservation_id = ? AND status = 'completed' LIMIT 1");
                if ($existing_payment_stmt) {
                    $existing_payment_stmt->bind_param("i", $reservation_id);
                    $existing_payment_stmt->execute();
                    $existing_payment_result = $existing_payment_stmt->get_result();
                    if ($existing_payment_result && $existing_payment_result->num_rows > 0 && !$from_return) {
                        $payment_error = 'Payment has already been completed for this reservation.';
                    }
                    $existing_payment_stmt->close();
                }
            }
        }
        $verify_stmt->close();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($payment_error)) {
    // Validate CSRF
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $payment_error = 'Security token mismatch. Please try again.';
    } else {
        $reservation_id = isset($_POST['reservation_id']) ? (int)$_POST['reservation_id'] : 0;
        $cardNumber = preg_replace('/\D/', '', $_POST['cardNumber'] ?? '');
        $cardName   = sanitize_input($_POST['cardName'] ?? '');
        $cardMonth  = (int)($_POST['cardMonth'] ?? 0);
        $cardYear   = (int)($_POST['cardYear'] ?? 0);
        $cardCvv    = preg_replace('/\D/', '', $_POST['cardCvv'] ?? '');
        $payment_method = sanitize_input($_POST['payment_method'] ?? 'credit_card');
        $from_return = isset($_POST['from_return']) && $_POST['from_return'] === '1';

        if ($reservation_id <= 0) {
            $payment_error = 'Invalid reservation ID.';
        } else {
            $dup_stmt = $conn->prepare("SELECT id FROM payment_transactions WHERE reservation_id = ? AND status = 'completed' LIMIT 1");
            if ($dup_stmt) {
                $dup_stmt->bind_param("i", $reservation_id);
                $dup_stmt->execute();
                $dup_result = $dup_stmt->get_result();
                if ($dup_result && $dup_result->num_rows > 0) {
                    $payment_error = 'Payment already completed for this reservation.';
                }
                $dup_stmt->close();
            }

            $period_stmt = $conn->prepare("SELECT return_date FROM reservations WHERE id = ? AND customer_id = ? LIMIT 1");
            if ($period_stmt) {
                $period_stmt->bind_param("ii", $reservation_id, $_SESSION['id']);
                $period_stmt->execute();
                $period_result = $period_stmt->get_result();
                if ($period_row = $period_result->fetch_assoc()) {
                    $return_date = $period_row['return_date'];
                    if (!empty($return_date) && $return_date > date('Y-m-d')) {
                        $payment_error = 'Your rental period has not ended yet.';
                    }
                } else {
                    $payment_error = 'Reservation not found or unauthorized access.';
                }
                $period_stmt->close();
            }
        }

        if (empty($payment_error)) {
            if (strlen($cardNumber) < 13 || strlen($cardNumber) > 19) {
                $payment_error = 'Invalid card number length.';
            } elseif (strlen($cardName) < 2) {
                $payment_error = 'Card holder name is required.';
            } elseif ($cardMonth < 1 || $cardMonth > 12) {
                $payment_error = 'Invalid expiry month.';
            } else {
                $currentYear = (int)date('Y');
                $maxYear     = $currentYear + 15;
                if ($cardYear < $currentYear || $cardYear > $maxYear) {
                    $payment_error = 'Invalid expiry year.';
                } elseif (strlen($cardCvv) < 3 || strlen($cardCvv) > 4) {
                    $payment_error = 'Invalid CVV.';
                } else {
                // Process payment and save to database
                try {
                    $conn->begin_transaction();
                    
                    // Verify reservation again
                    $verify_stmt = $conn->prepare("SELECT car_id, total_cost, reservation_status FROM reservations WHERE id = ? AND customer_id = ?");
                    $verify_stmt->bind_param("ii", $reservation_id, $_SESSION['id']);
                    $verify_stmt->execute();
                    $verify_result = $verify_stmt->get_result();
                    if ($verify_result->num_rows === 0) {
                        throw new Exception("Reservation not found.");
                    }
                    $reservation = $verify_result->fetch_assoc();
                    $verify_stmt->close();
                    
                    $amount = (float)$reservation['total_cost'];
                    $car_id = (int)$reservation['car_id'];
                    
                    // Duplicate payment prevention
                    $existing_payment_stmt = $conn->prepare("SELECT id FROM payment_transactions WHERE reservation_id = ? AND status = 'completed' LIMIT 1 FOR UPDATE");
                    if ($existing_payment_stmt) {
                        $existing_payment_stmt->bind_param("i", $reservation_id);
                        $existing_payment_stmt->execute();
                        $existing_payment_result = $existing_payment_stmt->get_result();
                        if ($existing_payment_result && $existing_payment_result->num_rows > 0) {
                            throw new Exception("Payment already completed.");
                        }
                        $existing_payment_stmt->close();
                    }
                    
                    // Insert payment transaction
                    $insert_stmt = $conn->prepare("
                        INSERT INTO payment_transactions (reservation_id, car_id, amount, payment_method, status, transaction_date, created_at)
                        VALUES (?, ?, ?, ?, 'completed', NOW(), NOW())
                    ");
                    if (!$insert_stmt) {
                        throw new Exception("Database error: " . $conn->error);
                    }
                    $insert_stmt->bind_param("iids", $reservation_id, $car_id, $amount, $payment_method);
                    $insert_stmt->execute();
                    $insert_stmt->close();
                    
                    // Update reservation status based on flow
                    $new_status = ($reservation['reservation_status'] === 'returned' || $from_return) ? 'returned' : 'confirmed';
                    $update_stmt = $conn->prepare("UPDATE reservations SET reservation_status = ? WHERE id = ?");
                    $update_stmt->bind_param("si", $new_status, $reservation_id);
                    $update_stmt->execute();
                    $update_stmt->close();
                    
                    $conn->commit();
                    $payment_success = $new_status === 'returned'
                        ? 'Payment completed successfully! Your return is confirmed.'
                        : 'Payment completed successfully! Your reservation is now confirmed.';
                    $redirect_after_success = true;
                    
                } catch (Exception $e) {
                    if ($conn->in_transaction) {
                        $conn->rollback();
                    }
                    log_error("Payment processing error: " . $e->getMessage(), __FILE__, __LINE__);
                    $payment_error = 'Payment processing failed. Please try again.';
                }
                }
            }
        }
    }
}

$csrf_token = generate_csrf_token();
$form_action = 'payment.php?reservation_id=' . (int)$reservation_id;
if ($from_return) {
    $form_action .= '&from_return=1';
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <?php if ($redirect_after_success): ?>
    <meta http-equiv="refresh" content="<?= (int)$redirect_delay_seconds ?>;url=<?= escape_output($redirect_target) ?>">
  <?php endif; ?>
  <link rel="stylesheet" href="../css/payment.css" />
  <title>Document</title>

</head>

<body>
  <div id="app">
    <?php if ($payment_error): ?>
      <div class="alert error"><?= escape_output($payment_error) ?></div>
    <?php endif; ?>
  <?php if ($payment_success): ?>
    <div class="alert success"><?= escape_output($payment_success) ?></div>
      <div style="text-align: center; margin: 15px 0;">
        <p style="margin: 0;">You will be redirected to the home page shortly.</p>
        <p style="margin: 6px 0 0;">Redirecting in <span id="redirect-timer"><?= (int)$redirect_delay_seconds ?></span> seconds...</p>
      </div>
    <?php endif; ?>

    <?php if (!$redirect_after_success): ?>
      <form class="wrapper" method="POST" action="<?= escape_output($form_action) ?>">
        <input type="hidden" name="csrf_token" value="<?= escape_output($csrf_token) ?>">
        <input type="hidden" name="reservation_id" value="<?= (int)$reservation_id ?>">
        <input type="hidden" name="from_return" value="<?= $from_return ? '1' : '0' ?>">
        <?php if ($reservation_id > 0 && isset($total_amount)): ?>
        <div style="text-align: center; margin-bottom: 20px; padding: 15px; background: #f0f8ff; border-radius: 8px;">
            <h3>Reservation #<?= (int)$reservation_id ?></h3>
            <p style="font-size: 18px; font-weight: bold; color: #4a90e2;">Total Amount: $<?= number_format($total_amount, 2) ?></p>
            <?php if ($from_return): ?>
              <p style="margin-top: 6px; color: #2c7a7b;">Return confirmed. Please complete Visa payment to finalize.</p>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        <div class="card-form">
          <div class="card-list">
            <div class="card-item" v-bind:class="{ '-active' : isCardFlipped }">
              <div class="card-item__side -front">
                <div class="card-item__focus" v-bind:class="{'-active' : focusElementStyle }" v-bind:style="focusElementStyle" ref="focusElement"></div>
                <div class="card-item__cover">
                  <img
                    v-bind:src="'https://raw.githubusercontent.com/muhammederdem/credit-card-form/master/src/assets/images/' + currentCardBackground + '.jpeg'" class="card-item__bg">
                </div>

                <div class="card-item__wrapper">
                  <div class="card-item__top">
                    <img src="../img/Cars Pixar copy.png" class="card-item__chip">
                    <div class="card-item__type">
                      <transition name="slide-fade-up">
                        <img v-bind:src="'https://raw.githubusercontent.com/muhammederdem/credit-card-form/master/src/assets/images/' + getCardType + '.png'" v-if="getCardType" v-bind:key="getCardType" alt="" class="card-item__typeImg">
                      </transition>
                    </div>
                  </div>
                  <label for="cardNumber" class="card-item__number" ref="cardNumber">
                    <template v-if="getCardType === 'amex'">
                      <span v-for="(n, $index) in amexCardMask" :key="$index">
                        <transition name="slide-fade-up">
                          <div
                            class="card-item__numberItem"
                            v-if="$index > 4 && $index < 14 && cardNumber.length > $index && n.trim() !== ''">*</div>
                          <div class="card-item__numberItem"
                            :class="{ '-active' : n.trim() === '' }"
                            :key="$index" v-else-if="cardNumber.length > $index">
                            {{cardNumber[$index]}}
                          </div>
                          <div
                            class="card-item__numberItem"
                            :class="{ '-active' : n.trim() === '' }"
                            v-else
                            :key="$index + 1">{{n}}</div>
                        </transition>
                      </span>
                    </template>

                    <template v-else>
                      <span v-for="(n, $index) in otherCardMask" :key="$index">
                        <transition name="slide-fade-up">
                          <div
                            class="card-item__numberItem"
                            v-if="$index > 4 && $index < 15 && cardNumber.length > $index && n.trim() !== ''">*</div>
                          <div class="card-item__numberItem"
                            :class="{ '-active' : n.trim() === '' }"
                            :key="$index" v-else-if="cardNumber.length > $index">
                            {{cardNumber[$index]}}
                          </div>
                          <div
                            class="card-item__numberItem"
                            :class="{ '-active' : n.trim() === '' }"
                            v-else
                            :key="$index + 1">{{n}}</div>
                        </transition>
                      </span>
                    </template>
                  </label>
                  <div class="card-item__content">
                    <label for="cardName" class="card-item__info" ref="cardName">
                      <div class="card-item__holder">Card Holder</div>
                      <transition name="slide-fade-up">
                        <div class="card-item__name" v-if="cardName.length" key="1">
                          <transition-group name="slide-fade-right">
                            <span class="card-item__nameItem" v-for="(n, $index) in cardName.replace(/\s\s+/g, ' ')" v-if="$index === $index" v-bind:key="$index + 1">{{n}}</span>
                          </transition-group>
                        </div>
                        <div class="card-item__name" v-else key="2">Full Name</div>
                      </transition>
                    </label>
                    <div class="card-item__date" ref="cardDate">
                      <label for="cardMonth" class="card-input__label card-item__dateTitle">Expires</label>
                      <label for="cardMonth" class="card-item__dateItem">
                        <transition name="slide-fade-up">
                          <span v-if="cardMonth" v-bind:key="cardMonth">{{cardMonth}}</span>
                          <span v-else key="2">MM</span>
                        </transition>
                      </label>
                      /
                      <label for="cardYear" class="card-item__dateItem">
                        <transition name="slide-fade-up">
                          <span v-if="cardYear" v-bind:key="cardYear">{{String(cardYear).slice(2,4)}}</span>
                          <span v-else key="2">YY</span>
                        </transition>
                      </label>
                    </div>
                  </div>
                </div>
              </div>
              <div class="card-item__side -back">
                <div class="card-item__cover">
                  <img
                    v-bind:src="'https://raw.githubusercontent.com/muhammederdem/credit-card-form/master/src/assets/images/' + currentCardBackground + '.jpeg'" class="card-item__bg">
                </div>
                <div class="card-item__band"></div>
                <div class="card-item__cvv">
                  <div class="card-item__cvvTitle">CVV</div>
                  <div class="card-item__cvvBand">
                    <span v-for="(n, $index) in cardCvv" :key="$index">
                      *
                    </span>

                  </div>
                  <div class="card-item__type">
                    <img v-bind:src="'https://raw.githubusercontent.com/muhammederdem/credit-card-form/master/src/assets/images/' + getCardType + '.png'" v-if="getCardType" class="card-item__typeImg">
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="card-form__inner">
            <div class="card-input">
              <label for="cardNumber" class="card-input__label">Card Number</label>
              <input type="text" id="cardNumber" name="cardNumber" class="card-input__input" v-mask="generateCardNumberMask" v-model="cardNumber" v-on:focus="focusInput" v-on:blur="blurInput" data-ref="cardNumber" autocomplete="off">
            </div>
            <div class="card-input">
              <label for="cardName" class="card-input__label">Card Holders</label>
              <input type="text" id="cardName" name="cardName" class="card-input__input" v-model="cardName" v-on:focus="focusInput" v-on:blur="blurInput" data-ref="cardName" autocomplete="off">
            </div>
            <div class="card-form__row">
              <div class="card-form__col">
                <div class="card-form__group">
                  <label for="cardMonth" class="card-input__label">Expiration Date</label>
                  <select class="card-input__input -select" id="cardMonth" name="cardMonth" v-model="cardMonth" v-on:focus="focusInput" v-on:blur="blurInput" data-ref="cardDate">
                    <option value="" disabled selected>Month</option>
                    <option v-bind:value="n < 10 ? '0' + n : n" v-for="n in 12" v-bind:disabled="n < minCardMonth" v-bind:key="n">
                      {{n < 10 ? '0' + n : n}}
                    </option>
                  </select>
                  <select class="card-input__input -select" id="cardYear" name="cardYear" v-model="cardYear" v-on:focus="focusInput" v-on:blur="blurInput" data-ref="cardDate">
                    <option value="" disabled selected>Year</option>
                    <option v-bind:value="$index + minCardYear" v-for="(n, $index) in 12" v-bind:key="n">
                      {{$index + minCardYear}}
                    </option>
                  </select>
                </div>
              </div>
              <div class="card-form__col -cvv">
                <div class="card-input">
                  <label for="cardCvv" class="card-input__label">CVV</label>
                  <input type="text" class="card-input__input" id="cardCvv" v-mask="'####'" maxlength="4" v-model="cardCvv" v-on:focus="flipCard(true)" v-on:blur="flipCard(false)" autocomplete="off">
                </div>
              </div>
            </div>
            
            <div class="card-input" style="margin-top: 15px;">
              <label for="payment_method" class="card-input__label">Payment Method</label>
              <select id="payment_method" name="payment_method" class="card-input__input" required>
                <option value="credit_card" selected>Credit Card</option>
                <option value="debit_card">Debit Card</option>
                <option value="online">Online Payment</option>
                <option value="bank_transfer">Bank Transfer</option>
                <option value="cash">Cash</option>
              </select>
            </div>

        <button class="card-form__button" type="submit" <?= empty($payment_error) && $reservation_id > 0 ? '' : 'disabled' ?>>
          <?= $reservation_id > 0 ? 'Complete Payment' : 'Submit' ?>
        </button>
        <a href="../index.php" class="card-form__button" style="display: inline-block; margin-top: 10px; text-align: center; background: #2c7a7b; text-decoration: none;">
          Back to Home
        </a>
      </div>
    </div>

  </form>
    <?php endif; ?>
  </div>

  <?php if (!$redirect_after_success): ?>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/vue/2.6.10/vue.min.js"></script>
    <script src="https://unpkg.com/vue-the-mask@0.11.1/dist/vue-the-mask.js"></script>

    <script>
      new Vue({
        el: "#app",
        data() {
          return {
            currentCardBackground: Math.floor(Math.random() * 25 + 1), // just for fun :D
            cardName: "",
            cardNumber: "",
            cardMonth: "",
            cardYear: "",
            cardCvv: "",
            minCardYear: new Date().getFullYear(),
            amexCardMask: "#### ###### #####",
            otherCardMask: "#### #### #### ####",
            cardNumberTemp: "",
            isCardFlipped: false,
            focusElementStyle: null,
            isInputFocused: false
          };
        },
        mounted() {
          this.cardNumberTemp = this.otherCardMask;
          const cardNumberInput = document.getElementById("cardNumber");
          if (cardNumberInput) {
            cardNumberInput.focus();
          }
        },
        computed: {
          getCardType() {
            let number = this.cardNumber;
            let re = new RegExp("^4");
            if (number.match(re) != null) return "visa";

            re = new RegExp("^(34|37)");
            if (number.match(re) != null) return "amex";

            re = new RegExp("^5[1-5]");
            if (number.match(re) != null) return "mastercard";

            re = new RegExp("^6011");
            if (number.match(re) != null) return "discover";

            re = new RegExp('^9792')
            if (number.match(re) != null) return 'troy'

            return "visa"; // default type
          },
          generateCardNumberMask() {
            return this.getCardType === "amex" ? this.amexCardMask : this.otherCardMask;
          },
          minCardMonth() {
            if (this.cardYear === this.minCardYear) return new Date().getMonth() + 1;
            return 1;
          }
        },
        watch: {
          cardYear() {
            if (this.cardMonth < this.minCardMonth) {
              this.cardMonth = "";
            }
          }
        },
        methods: {
          flipCard(status) {
            this.isCardFlipped = status;
          },
          focusInput(e) {
            this.isInputFocused = true;
            let targetRef = e.target.dataset.ref;
            let target = this.$refs[targetRef];
            this.focusElementStyle = {
              width: `${target.offsetWidth}px`,
              height: `${target.offsetHeight}px`,
              transform: `translateX(${target.offsetLeft}px) translateY(${target.offsetTop}px)`
            }
          },
          blurInput() {
            let vm = this;
            setTimeout(() => {
              if (!vm.isInputFocused) {
                vm.focusElementStyle = null;
              }
            }, 300);
            vm.isInputFocused = false;
          }
        }
      });
    </script>
  <?php endif; ?>

  <?php if ($redirect_after_success): ?>
    <script>
      (function() {
        const redirectDelayMs = <?= (int)$redirect_delay_seconds * 1000 ?>;
        const targetUrl = "<?= escape_output($redirect_target) ?>";
        const timerEl = document.getElementById("redirect-timer");
        let remainingSeconds = Math.ceil(redirectDelayMs / 1000);

        if (timerEl) {
          timerEl.textContent = remainingSeconds;
        }

        const countdown = setInterval(() => {
          remainingSeconds -= 1;
          if (timerEl && remainingSeconds >= 0) {
            timerEl.textContent = remainingSeconds;
          }
          if (remainingSeconds <= 0) {
            clearInterval(countdown);
          }
        }, 1000);

        setTimeout(() => {
          window.location.href = targetUrl;
        }, redirectDelayMs);
      })();
    </script>
  <?php endif; ?>
</body>

</html>
