<?php
// C:\xampp1\htdocs\Innovista-final\Innovista-main\public\customer\pay_balance.php

// Include session and protect the page FIRST
require_once '../../public/session.php';
require_once '../../handlers/flash_message.php';

protectPage('customer');

$pageTitle = 'Pay Balance';
require_once '../../includes/user_dashboard_header.php';
require_once '../../config/Database.php';

$customer_id = getUserId();
$database = new Database();
$conn = $database->getConnection();

$order_id = filter_input(INPUT_GET, 'order_id', FILTER_VALIDATE_INT);
$order_data = null;
$error_message = '';

if (!$order_id) {
    set_flash_message('error', 'Invalid Order ID provided for balance payment.');
    header('Location: my_orders.php');
    exit();
}

try {
    // Fetch specific order details, ensuring it belongs to the logged-in customer
    // and has a balance due (status 'advance_paid')
    $stmt = $conn->prepare("SELECT id, total_amount, advance_amount, balance_due, status, payment_method, shipping_email FROM orders WHERE id = :order_id AND user_id = :user_id AND status = 'advance_paid' AND balance_due > 0");
    $stmt->bindParam(':order_id', $order_id, PDO::PARAM_INT);
    $stmt->bindParam(':user_id', $customer_id, PDO::PARAM_INT);
    $stmt->execute();
    $order_data = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order_data) {
        set_flash_message('error', 'Order not found, balance already paid, or you do not have permission.');
        header('Location: my_orders.php');
        exit();
    }

} catch (PDOException $e) {
    error_log("Pay Balance PDO Exception: " . $e->getMessage());
    $error_message = 'Failed to load payment details due to a database error. Please try again.';
} catch (Exception $e) {
    error_log("Pay Balance General Exception: " . $e->getMessage());
    $error_message = 'An unexpected error occurred while loading payment details.';
}
?>

<main class="dashboard-main-content">
    <?php 
    if (function_exists('display_flash_message')) {
        echo '<div class="flash-message-container">';
        display_flash_message();
        echo '</div>';
    }
    ?>

    <h2>Pay Outstanding Balance for Order #<?php echo htmlspecialchars($order_data['id'] ?? 'N/A'); ?></h2>
    <p>Please complete your payment of Rs. <?php echo htmlspecialchars(number_format($order_data['balance_due'] ?? 0, 2)); ?> to finalize your order.</p>

    <?php if ($error_message): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
    <?php elseif (!$order_data): ?>
        <p class="text-center">Order not found or no balance is due.</p>
        <p class="text-center"><a href="my_orders.php" class="btn btn-primary">Back to My Orders</a></p>
    <?php else: ?>
        <div class="content-card">
            <div class="summary-box" style="text-align:center; margin-bottom: 2rem; padding: 1rem; border: 1px solid #eee; border-radius: 8px;">
                <p style="font-size: 1.1rem; margin-bottom: 0.5rem;">Total Order Amount: <strong>Rs. <?php echo htmlspecialchars(number_format($order_data['total_amount'], 2)); ?></strong></p>
                <p style="font-size: 1.1rem; margin-bottom: 0.5rem;">Advance Paid: <strong>Rs. <?php echo htmlspecialchars(number_format($order_data['advance_amount'], 2)); ?></strong></p>
                <p style="font-size: 1.5rem; color: #dc3545;">Amount to Pay Now: <strong>Rs. <?php echo htmlspecialchars(number_format($order_data['balance_due'], 2)); ?></strong></p>
            </div>

            <form id="balancePaymentForm" action="../../handlers/process_balance_payment.php" method="POST">
                <input type="hidden" name="order_id" value="<?php echo htmlspecialchars($order_data['id']); ?>">
                <input type="hidden" name="balance_amount" value="<?php echo htmlspecialchars(number_format($order_data['balance_due'], 2, '.', '')); ?>">
                <input type="hidden" name="shipping_email" value="<?php echo htmlspecialchars($order_data['shipping_email']); ?>">

                <h3 style="margin-top: 2rem;">Payment Method</h3>
                <div class="form-group">
                    <label for="payment_method">Select Payment Gateway:</label>
                    <select id="payment_method" name="payment_method" required>
                        <option value="">-- Select Payment Method --</option>
                        <option value="card" <?php echo ($order_data['payment_method'] === 'card') ? 'selected' : ''; ?>>Credit/Debit Card</option>
                        <option value="cod" <?php echo ($order_data['payment_method'] === 'cod') ? 'selected' : ''; ?>>Cash on Delivery (COD)</option>
                    </select>
                </div>
                
                <div id="card-details-section" style="display:none;">
                    <h4>Card Details</h4>
                    <div class="form-group">
                        <label for="card_number">Card Number</label>
                        <input type="text" id="card_number" name="card_number" placeholder="•••• •••• •••• ••••" maxlength="19">
                    </div>
                    <div class="form-group">
                        <label for="card_expiry">Expiry Date (MM/YY)</label>
                        <input type="text" id="card_expiry" name="card_expiry" placeholder="MM/YY" maxlength="5">
                    </div>
                    <div class="form-group">
                        <label for="card_cvc">CVC</label>
                        <input type="text" id="card_cvc" name="card_cvc" placeholder="CVC" maxlength="4">
                    </div>
                </div>

                <button type="submit" id="btnPayBalance" class="btn btn-primary" style="margin-top: 2rem;">Pay Rs. <?php echo htmlspecialchars(number_format($order_data['balance_due'], 2)); ?></button>
                <a href="order_detail.php?id=<?php echo htmlspecialchars($order_data['id']); ?>" class="btn btn-secondary" style="margin-top: 1rem;">Cancel & Back to Order Details</a>
            </form>
        </div>
    <?php endif; ?>

    <!-- OTP Modal (reused from checkout.php) -->
    <div id="otpModal" class="otp-modal" style="display:none;">
        <div class="otp-modal-content">
            <span class="close-btn" onclick="closeOtpModal()">&times;</span>
            <h2>Verify Your Payment</h2>
            <p>An OTP has been sent to your registered email address.</p>
            <div class="otp-input-group">
                <input type="text" id="otp_digit_1" class="otp-input" maxlength="1">
                <input type="text" id="otp_digit_2" class="otp-input" maxlength="1">
                <input type="text" id="otp_digit_3" class="otp-input" maxlength="1">
                <input type="text" id="otp_digit_4" class="otp-input" maxlength="1">
                <input type="text" id="otp_digit_5" class="otp-input" maxlength="1">
                <input type="text" id="otp_digit_6" class="otp-input" maxlength="1">
            </div>
            <p id="otpMessage" class="otp-message"></p>
            <div class="otp-action-buttons">
                <button type="button" id="verifyOtpBtn" class="btn btn-verify">Verify OTP</button>
                <button type="button" id="resendOtpBtn" class="btn btn-resend">Resend OTP</button>
            </div>
        </div>
    </div>

    <script>
        let currentTransactionId = null; // Store transaction ID for OTP verification

        document.addEventListener('DOMContentLoaded', function() {
            const paymentMethodSelect = document.getElementById('payment_method');
            const cardDetailsSection = document.getElementById('card-details-section');
            const balancePaymentForm = document.getElementById('balancePaymentForm');
            const btnPayBalance = document.getElementById('btnPayBalance');
            
            const otpModal = document.getElementById('otpModal');
            const otpInputs = document.querySelectorAll('.otp-input-group .otp-input');
            const verifyOtpBtn = document.getElementById('verifyOtpBtn');
            const resendOtpBtn = document.getElementById('resendOtpBtn');
            const otpMessage = document.getElementById('otpMessage');

            // --- Form Field Toggling ---
            function toggleCardDetails() {
                if (paymentMethodSelect.value === 'card') {
                    cardDetailsSection.style.display = 'block';
                    cardDetailsSection.querySelectorAll('input').forEach(input => {
                        input.setAttribute('required', 'required');
                    });
                } else {
                    cardDetailsSection.style.display = 'none';
                    cardDetailsSection.querySelectorAll('input').forEach(input => {
                        input.removeAttribute('required');
                        input.value = '';
                    });
                }
            }

            // Initial state
            toggleCardDetails();

            // Event listener for toggling
            if (paymentMethodSelect) paymentMethodSelect.addEventListener('change', toggleCardDetails);

            // --- OTP Input Handling (focus and auto-advance) ---
            otpInputs.forEach((input, index) => {
                input.addEventListener('input', () => {
                    if (input.value.length === 1 && index < otpInputs.length - 1) {
                        otpInputs[index + 1].focus();
                    }
                });
                input.addEventListener('keydown', (e) => {
                    if (e.key === 'Backspace' && input.value.length === 0 && index > 0) {
                        otpInputs[index - 1].focus();
                    }
                });
            });

            // --- OTP Modal Functions (reused from checkout.php) ---
            window.openOtpModal = function(message = 'An OTP has been sent to your registered email address.') {
                otpMessage.textContent = message;
                otpMessage.className = 'otp-message';
                otpInputs.forEach(input => input.value = '');
                if (otpModal) otpModal.style.display = 'flex';
                otpInputs[0].focus();
            };

            window.closeOtpModal = function() {
                if (otpModal) otpModal.style.display = 'none';
                otpMessage.textContent = '';
                currentTransactionId = null;
            };

            function showOtpMessage(message, isSuccess = false) {
                otpMessage.textContent = message;
                otpMessage.className = `otp-message ${isSuccess ? 'success' : 'error'}`;
            }

            // --- Balance Payment Form Submission (AJAX) ---
            balancePaymentForm.addEventListener('submit', function(e) {
                e.preventDefault();

                btnPayBalance.disabled = true;
                btnPayBalance.textContent = 'Processing...';

                const formData = new FormData(balancePaymentForm);
                formData.append('action', 'pay_balance'); // Specific action for this handler

                fetch('../../handlers/process_balance_payment.php', { // Path to new handler
                    method: 'POST',
                    body: formData
                })
                .then(response => {
                    if (!response.ok) { 
                        console.error('HTTP Error:', response.status, response.statusText);
                        return response.text().then(text => { throw new Error('Server responded with status ' + response.status + ': ' + text); });
                    }
                    return response.json();
                })
                .then(data => {
                    btnPayBalance.disabled = false;
                    btnPayBalance.textContent = `Pay Rs. <?php echo htmlspecialchars(number_format($order_data['balance_due'] ?? 0, 2)); ?>`;

                    if (data.success) {
                        if (data.requires_otp) {
                            currentTransactionId = data.transaction_id;
                            openOtpModal(data.message || 'An OTP has been sent to your registered email address.');
                        } else {
                            // No OTP required (e.g., COD), redirect directly to order detail
                            window.location.href = `order_detail.php?id=${data.order_id}&status=success&message=${encodeURIComponent(data.message)}`;
                        }
                    } else {
                        alert('Payment failed: ' + (data.message || 'An unknown error occurred.'));
                    }
                })
                .catch(error => {
                    console.error('Error during balance payment submission:', error);
                    btnPayBalance.disabled = false;
                    btnPayBalance.textContent = `Pay Rs. <?php echo htmlspecialchars(number_format($order_data['balance_due'] ?? 0, 2)); ?>`;
                    alert('A network error occurred. Please try again.');
                });
            });

            // --- OTP Verification Submission (for balance payment) ---
            if (verifyOtpBtn) {
                verifyOtpBtn.addEventListener('click', function() {
                    const otp = Array.from(otpInputs).map(input => input.value).join('');

                    if (otp.length !== 6) {
                        showOtpMessage('Please enter a complete 6-digit OTP.');
                        return;
                    }

                    if (!currentTransactionId) {
                        showOtpMessage('No active transaction found. Please restart the payment process.');
                        return;
                    }

                    verifyOtpBtn.disabled = true;
                    verifyOtpBtn.textContent = 'Verifying...';

                    const otpFormData = new FormData();
                    otpFormData.append('action', 'verify_otp_balance'); // Specific action for balance OTP
                    otpFormData.append('otp', otp);
                    otpFormData.append('transaction_id', currentTransactionId);
                    otpFormData.append('order_id', <?php echo htmlspecialchars($order_data['id'] ?? 0); ?>);

                    fetch('../../handlers/process_balance_payment.php', { // Path to new handler
                        method: 'POST',
                        body: otpFormData
                    })
                    .then(response => {
                        if (!response.ok) { 
                            console.error('HTTP Error:', response.status, response.statusText);
                            return response.text().then(text => { throw new Error('Server responded with status ' + response.status + ': ' + text); });
                        }
                        return response.json();
                    })
                    .then(data => {
                        verifyOtpBtn.disabled = false;
                        verifyOtpBtn.textContent = 'Verify OTP';

                        if (data.success) {
                            showOtpMessage(data.message || 'OTP verified! Balance paid.', true);
                            setTimeout(() => {
                                closeOtpModal();
                                window.location.href = `order_detail.php?id=${data.order_id}&status=success&message=${encodeURIComponent(data.message)}`;
                            }, 1000);
                        } else {
                            showOtpMessage(data.message || 'Invalid OTP. Please try again.');
                        }
                    })
                    .catch(error => {
                        console.error('Error during OTP verification:', error);
                        verifyOtpBtn.disabled = false;
                        verifyOtpBtn.textContent = 'Verify OTP';
                        showOtpMessage('A network error occurred during OTP verification.');
                    });
                });
            }

            // --- Resend OTP Logic (for balance payment) ---
            if (resendOtpBtn) {
                resendOtpBtn.addEventListener('click', function() {
                    if (!currentTransactionId) {
                        showOtpMessage('No active transaction to resend OTP for.');
                        return;
                    }

                    resendOtpBtn.disabled = true;
                    resendOtpBtn.textContent = 'Resending...';

                    const resendFormData = new FormData();
                    resendFormData.append('action', 'resend_otp_balance'); // Specific action for balance OTP
                    resendFormData.append('transaction_id', currentTransactionId);
                    resendFormData.append('order_id', <?php echo htmlspecialchars($order_data['id'] ?? 0); ?>);

                    fetch('../../handlers/process_balance_payment.php', { // Path to new handler
                        method: 'POST',
                        body: resendFormData
                    })
                    .then(response => {
                        if (!response.ok) { 
                            console.error('HTTP Error:', response.status, response.statusText);
                            return response.text().then(text => { throw new Error('Server responded with status ' + response.status + ': ' + text); });
                        }
                        return response.json();
                    })
                    .then(data => {
                        resendOtpBtn.disabled = false;
                        resendOtpBtn.textContent = 'Resend OTP';
                        if (data.success) {
                            showOtpMessage(data.message || 'New OTP sent!', true);
                            otpInputs.forEach(input => input.value = '');
                            otpInputs[0].focus();
                        } else {
                            showOtpMessage(data.message || 'Failed to resend OTP.');
                        }
                    })
                    .catch(error => {
                        console.error('Error resending OTP:', error);
                        resendOtpBtn.disabled = false;
                        resendOtpBtn.textContent = 'Resend OTP';
                        showOtpMessage('A network error occurred during OTP resend.');
                    });
                });
            }
        });
    </script>
</main>

<?php 
// Include the user dashboard footer
require_once '../../includes/user_dashboard_footer.php'; 
?>