<?php
// C:\xampp1\htdocs\Innovista-final\Innovista-main\public\checkout.php

// Define the page title
$pageTitle = 'Checkout'; 

// Include the master header, which also starts the session and provides isUserLoggedIn()
include 'header.php'; 
require_once '../handlers/flash_message.php'; // For display_flash_message()

// Ensure the user is logged in
if (!isUserLoggedIn()) {
    header('Location: login.php?status=error&message=' . urlencode('You must be logged in to checkout.'));
    exit();
}

// Get the user's cart from the session
$cart = $_SESSION['cart'] ?? [];

// Calculate cart total
$cartTotal = 0;
if (!empty($cart)) {
    foreach ($cart as $item) {
        $cartTotal += ($item['price'] * $item['quantity']);
    }
}

// Default payment terms for initial display
$defaultAdvancePercentage = 0.25; // 25%
$initialAdvanceAmount = $cartTotal * $defaultAdvancePercentage;
$initialBalanceDue = $cartTotal - $initialAdvanceAmount;

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?> - Innovista</title>
    
    <!-- Include necessary CSS files -->
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/header.css">
    <link rel="stylesheet" href="assets/css/footer.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
    
    <!-- Add specific CSS for checkout page if you have one -->
    <style>
        /* Basic inline styles for checkout - move to a dedicated CSS file (e.g., assets/css/checkout.css) later */
        .checkout-container {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 2rem;
            padding: 2rem 0;
            max-width: 1200px;
            margin: 0 auto;
        }
        .checkout-details, .checkout-summary {
            background-color: #fff;
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        .checkout-details h2, .checkout-summary h2 {
            margin-top: 0;
            color: #333;
            font-size: 1.5rem;
            margin-bottom: 1.5rem;
        }
        .form-group {
            margin-bottom: 1rem;
        }
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
        }
        .form-group input, .form-group textarea, .form-group select {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box;
        }
        .cart-item {
            display: flex;
            align-items: center;
            margin-bottom: 1rem;
            border-bottom: 1px solid #eee;
            padding-bottom: 1rem;
        }
        .cart-item:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }
        .cart-item-image {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 4px;
            margin-right: 1rem;
        }
        .cart-item-details {
            flex-grow: 1;
        }
        .cart-item-details h4 {
            margin: 0 0 0.2rem 0;
            font-size: 1rem;
        }
        .cart-item-details p {
            margin: 0;
            font-size: 0.85rem;
            color: #666;
        }
        .cart-item-price {
            font-weight: 600;
            white-space: nowrap;
        }
        .order-total {
            display: flex;
            justify-content: space-between;
            font-size: 1.2rem;
            font-weight: 700;
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 1px solid #eee;
        }
        .btn-place-order {
            width: 100%;
            padding: 1rem;
            background-color: #0d9488; /* Innovista primary color */
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        .btn-place-order:hover {
            background-color: #0a756b;
        }
        .cart-empty-message {
            text-align: center;
            color: #888;
            padding: 2rem 0;
        }
        @media (max-width: 768px) {
            .checkout-container {
                grid-template-columns: 1fr;
            }
        }
        /* Styles for OTP Modal */
        .otp-modal {
            display: flex;
            align-items: center;
            justify-content: center;
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            z-index: 1001; /* Higher than other modals */
            background: rgba(0,0,0,0.6);
        }
        .otp-modal-content {
            background: #fff;
            padding: 2rem;
            border-radius: 10px;
            text-align: center;
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
            max-width: 400px;
            width: 90%;
            position: relative;
        }
        .otp-modal-content .close-btn {
            position: absolute;
            top: 10px;
            right: 15px;
            font-size: 1.5rem;
            cursor: pointer;
            color: #666;
        }
        .otp-input-group {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin: 1.5rem 0;
        }
        .otp-input {
            width: 40px;
            height: 40px;
            text-align: center;
            font-size: 1.2rem;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        .otp-action-buttons button {
            margin-top: 1rem;
            padding: 0.8rem 1.5rem;
            border-radius: 5px;
            cursor: pointer;
        }
        .otp-action-buttons .btn-verify {
            background-color: #0d9488;
            color: white;
            border: none;
        }
        .otp-action-buttons .btn-resend {
            background: none;
            border: 1px solid #0d9488;
            color: #0d9488;
            margin-left: 10px;
        }
        .otp-message {
            margin-top: 1rem;
            font-size: 0.9rem;
            color: #dc3545; /* Error color */
        }
        .otp-message.success {
            color: #28a745;
        }
        /* New styles for payment choice display */
        .payment-choice-details {
            margin-top: 1rem;
            padding: 1rem;
            background-color: #f8f8f8;
            border: 1px solid #eee;
            border-radius: 6px;
        }
        .payment-choice-details p {
            margin: 0.5rem 0;
        }
        .payment-choice-details span {
            font-weight: 600;
        }
    </style>
</head>
<body>
    <?php // Flash message display
    if (function_exists('display_flash_message')) {
        echo '<div class="flash-message-container">';
        display_flash_message();
        echo '</div>';
    }
    ?>

    <main class="container">
        <div class="checkout-container">
            <div class="checkout-details">
                <h2>Shipping & Billing Information</h2>
                <form id="checkoutForm" action="../handlers/process_order.php" method="POST">
                    <!-- Hidden inputs for calculated amounts -->
                    <input type="hidden" id="cart_total_amount_hidden" name="cart_total_amount" value="<?php echo htmlspecialchars(number_format($cartTotal, 2, '.', '')); ?>">
                    <input type="hidden" id="advance_amount_to_pay_hidden" name="advance_amount_to_pay" value="<?php echo htmlspecialchars(number_format($initialAdvanceAmount, 2, '.', '')); ?>">
                    <input type="hidden" id="balance_due_amount_hidden" name="balance_due_amount" value="<?php echo htmlspecialchars(number_format($initialBalanceDue, 2, '.', '')); ?>">
                    <input type="hidden" id="selected_payment_terms_hidden" name="selected_payment_terms" value="advance_25"> <!-- Default -->

                    <!-- Shipping Information -->
                    <h3>Shipping Address</h3>
                    <div class="form-group">
                        <label for="shipping_name">Full Name</label>
                        <input type="text" id="shipping_name" name="shipping_name" required>
                    </div>
                    <div class="form-group">
                        <label for="shipping_address">Address Line 1</label>
                        <input type="text" id="shipping_address" name="shipping_address" required>
                    </div>
                    <div class="form-group">
                        <label for="shipping_city">City</label>
                        <input type="text" id="shipping_city" name="shipping_city" required>
                    </div>
                    <div class="form-group">
                        <label for="shipping_zip">Zip/Postal Code</label>
                        <input type="text" id="shipping_zip" name="shipping_zip" required>
                    </div>
                    <div class="form-group">
                        <label for="shipping_phone">Phone Number</label>
                        <input type="tel" id="shipping_phone" name="shipping_phone" required>
                    </div>
                    <div class="form-group">
                        <label for="shipping_email">Email</label>
                        <input type="email" id="shipping_email" name="shipping_email" required>
                    </div>

                    <!-- Billing Information (Optional: "Same as shipping" checkbox) -->
                    <h3 style="margin-top: 2rem;">Billing Address</h3>
                    <div class="form-group">
                        <input type="checkbox" id="same_as_shipping" name="same_as_shipping" checked>
                        <label for="same_as_shipping" style="display: inline-block; margin-left: 0.5rem;">Same as shipping address</label>
                    </div>
                    <div id="billing-fields" style="display:none;">
                        <div class="form-group">
                            <label for="billing_name">Full Name</label>
                            <input type="text" id="billing_name" name="billing_name">
                        </div>
                        <div class="form-group">
                            <label for="billing_address">Address Line 1</label>
                            <input type="text" id="billing_address" name="billing_address">
                        </div>
                        <div class="form-group">
                            <label for="billing_city">City</label>
                            <input type="text" id="billing_city" name="billing_city">
                        </div>
                        <div class="form-group">
                            <label for="billing_zip">Zip/Postal Code</label>
                            <input type="text" id="billing_zip" name="billing_zip">
                        </div>
                    </div>

                    <h3 style="margin-top: 2rem;">Payment Method & Terms</h3>
                    <div class="form-group">
                        <label for="payment_type_choice">Choose Payment Option:</label>
                        <select id="payment_type_choice" name="payment_type_choice" required>
                            <option value="advance">Advance Payment (25%, 50%, or 75%)</option>
                            <option value="full">Full Payment (100%)</option>
                        </select>
                    </div>

                    <div class="form-group" id="advance-percentage-group">
                        <label for="advance_percentage_choice">Select Advance Percentage:</label>
                        <select id="advance_percentage_choice" name="advance_percentage_choice">
                            <option value="25">25% Advance</option>
                            <option value="50">50% Advance</option>
                            <option value="75">75% Advance</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="payment_method">Select Payment Gateway:</label>
                        <select id="payment_method" name="payment_method" required>
                            <option value="">-- Select Payment Gateway --</option>
                            <option value="card">Credit/Debit Card</option>
                            <option value="cod">Cash on Delivery (COD)</option>
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

                    <!-- Dynamic payment choice display -->
                    <div id="payment-choice-summary" class="payment-choice-details">
                        <!-- This will be populated by JavaScript -->
                    </div>

                    <?php if (empty($cart)): ?>
                        <p class="cart-empty-message">Your cart is empty. Cannot proceed to checkout.</p>
                        <button type="submit" class="btn-place-order" disabled>Place Order</button>
                    <?php else: ?>
                        <button type="submit" class="btn-place-order">Pay Advance (Rs. <?php echo htmlspecialchars(number_format($initialAdvanceAmount, 2)); ?>)</button>
                    <?php endif; ?>
                </form>
            </div>

            <div class="checkout-summary">
                <h2>Order Summary</h2>
                <div class="cart-items-summary">
                    <?php if (empty($cart)): ?>
                        <p class="cart-empty-message">No items in your cart.</p>
                    <?php else: ?>
                        <?php foreach ($cart as $cartItemId => $item): ?>
                            <div class="cart-item">
                                <img src="<?php echo htmlspecialchars($item['image_path']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" class="cart-item-image">
                                <div class="cart-item-details">
                                    <h4><?php echo htmlspecialchars($item['name']); ?></h4>
                                    <?php if ($item['color']): ?><p>Color: <?php echo htmlspecialchars($item['color']); ?></p><?php endif; ?>
                                    <p>Qty: <?php echo htmlspecialchars($item['quantity']); ?> @ Rs. <?php echo htmlspecialchars(number_format($item['price'], 2)); ?> each</p>
                                </div>
                                <span class="cart-item-price">Rs. <?php echo htmlspecialchars(number_format($item['price'] * $item['quantity'], 2)); ?></span>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <div class="order-total">
                    <span>Cart Total:</span>
                    <span id="summaryCartTotal">Rs. <?php echo htmlspecialchars(number_format($cartTotal, 2)); ?></span>
                </div>
                <div class="order-total" style="font-size: 1rem; border-top: none; padding-top: 0.5rem;">
                    <span>Payment Now:</span>
                    <span id="summaryPaymentNow">Rs. <?php echo htmlspecialchars(number_format($initialAdvanceAmount, 2)); ?></span>
                </div>
                <div class="order-total" style="font-size: 1rem; border-top: none; padding-top: 0.5rem;">
                    <span>Balance Due:</span>
                    <span id="summaryBalanceDue">Rs. <?php echo htmlspecialchars(number_format($initialBalanceDue, 2)); ?></span>
                </div>
            </div>
        </div>
    </main>

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
        // Global variables to store checkout form data temporarily
        let checkoutFormData = null;
        let currentTransactionId = null; // Store transaction ID for OTP verification
        const cartTotalAmount = parseFloat(document.getElementById('cart_total_amount_hidden').value); // Get total from PHP

        document.addEventListener('DOMContentLoaded', function() {
            const sameAsShippingCheckbox = document.getElementById('same_as_shipping');
            const billingFields = document.getElementById('billing-fields');
            const paymentTypeChoiceSelect = document.getElementById('payment_type_choice'); // New
            const advancePercentageChoiceSelect = document.getElementById('advance_percentage_choice'); // New
            const advancePercentageGroup = document.getElementById('advance-percentage-group'); // New
            const paymentMethodSelect = document.getElementById('payment_method');
            const cardDetailsSection = document.getElementById('card-details-section');
            const checkoutForm = document.getElementById('checkoutForm');
            const btnPlaceOrder = document.querySelector('.btn-place-order');

            const summaryCartTotal = document.getElementById('summaryCartTotal');
            const summaryPaymentNow = document.getElementById('summaryPaymentNow');
            const summaryBalanceDue = document.getElementById('summaryBalanceDue');
            const paymentChoiceSummary = document.getElementById('payment-choice-summary');


            const hiddenAdvanceAmountInput = document.getElementById('advance_amount_to_pay_hidden');
            const hiddenBalanceDueAmountInput = document.getElementById('balance_due_amount_hidden');
            const hiddenSelectedPaymentTermsInput = document.getElementById('selected_payment_terms_hidden');


            const otpModal = document.getElementById('otpModal');
            const otpInputs = document.querySelectorAll('.otp-input-group .otp-input');
            const verifyOtpBtn = document.getElementById('verifyOtpBtn');
            const resendOtpBtn = document.getElementById('resendOtpBtn');
            const otpMessage = document.getElementById('otpMessage');

            // --- Form Field Toggling ---
            function toggleBillingFields() {
                if (sameAsShippingCheckbox.checked) {
                    billingFields.style.display = 'none';
                    // Clear and un-require billing fields
                    billingFields.querySelectorAll('input').forEach(input => {
                        input.removeAttribute('required');
                        input.value = '';
                    });
                } else {
                    billingFields.style.display = 'block';
                    // Make billing fields required
                    billingFields.querySelectorAll('input').forEach(input => {
                        input.setAttribute('required', 'required');
                    });
                }
            }

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

            // --- Payment Calculation & Display Logic ---
            function updatePaymentDetails() {
                const paymentTypeChoice = paymentTypeChoiceSelect.value;
                const advancePercentageChoice = parseInt(advancePercentageChoiceSelect.value); // Will be NaN if full payment
                
                let paymentNowAmount = 0;
                let balanceAmount = 0;
                let paymentTermsText = "";
                let selectedPaymentTermsValue = "";

                if (paymentTypeChoice === 'full') {
                    paymentNowAmount = cartTotalAmount;
                    balanceAmount = 0;
                    paymentTermsText = "Full Payment (100%)";
                    selectedPaymentTermsValue = 'full';
                    advancePercentageGroup.style.display = 'none';
                } else { // advance payment
                    const percentage = advancePercentageChoice / 100;
                    paymentNowAmount = cartTotalAmount * percentage;
                    balanceAmount = cartTotalAmount - paymentNowAmount;
                    paymentTermsText = `${advancePercentageChoice}% Advance Payment`;
                    selectedPaymentTermsValue = `advance_${advancePercentageChoice}`;
                    advancePercentageGroup.style.display = 'block';
                }

                summaryPaymentNow.textContent = `Rs. ${paymentNowAmount.toFixed(2)}`;
                summaryBalanceDue.textContent = `Rs. ${balanceAmount.toFixed(2)}`;
                btnPlaceOrder.textContent = `Pay ${paymentTermsText.replace(' Payment', '')} (Rs. ${paymentNowAmount.toFixed(2)})`;
                
                // Update hidden inputs for backend
                hiddenAdvanceAmountInput.value = paymentNowAmount.toFixed(2);
                hiddenBalanceDueAmountInput.value = balanceAmount.toFixed(2);
                hiddenSelectedPaymentTermsInput.value = selectedPaymentTermsValue;

                // Update dynamic summary text
                paymentChoiceSummary.innerHTML = `
                    <p>You have chosen: <span>${paymentTermsText}</span></p>
                    <p>Amount to pay now: <span>Rs. ${paymentNowAmount.toFixed(2)}</span></p>
                    <p>Balance due later: <span>Rs. ${balanceAmount.toFixed(2)}</span></p>
                `;
            }


            // Initial state
            toggleBillingFields();
            toggleCardDetails();
            updatePaymentDetails(); // Initial calculation

            // Event listeners for toggling
            if (sameAsShippingCheckbox) sameAsShippingCheckbox.addEventListener('change', toggleBillingFields);
            if (paymentMethodSelect) paymentMethodSelect.addEventListener('change', toggleCardDetails);
            if (paymentTypeChoiceSelect) paymentTypeChoiceSelect.addEventListener('change', updatePaymentDetails); // New listener
            if (advancePercentageChoiceSelect) advancePercentageChoiceSelect.addEventListener('change', updatePaymentDetails); // New listener


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

            // --- OTP Modal Functions ---
            window.openOtpModal = function(message = 'An OTP has been sent to your registered email address.') {
                otpMessage.textContent = message;
                otpMessage.className = 'otp-message'; // Reset class
                otpInputs.forEach(input => input.value = ''); // Clear inputs
                if (otpModal) otpModal.style.display = 'flex';
                otpInputs[0].focus(); // Focus first input
            };

            window.closeOtpModal = function() {
                if (otpModal) otpModal.style.display = 'none';
                otpMessage.textContent = '';
                checkoutFormData = null; // Clear saved form data
                currentTransactionId = null;
            };

            function showOtpMessage(message, isSuccess = false) {
                otpMessage.textContent = message;
                otpMessage.className = `otp-message ${isSuccess ? 'success' : 'error'}`;
            }

            // --- Checkout Form Submission (AJAX) ---
            checkoutForm.addEventListener('submit', function(e) {
                e.preventDefault(); // Prevent default form submission

                // Disable button and show loading indicator
                btnPlaceOrder.disabled = true;
                btnPlaceOrder.textContent = 'Processing...';

                checkoutFormData = new FormData(checkoutForm); // Store form data

                // Append a specific action for the initial order processing
                checkoutFormData.append('action', 'place_order');

                fetch('../handlers/process_order.php', { // Path from public/ to handlers/
                    method: 'POST',
                    body: checkoutFormData
                })
                .then(response => {
                    // Check if response is OK (200 status) before trying to parse JSON
                    if (!response.ok) { 
                        console.error('HTTP Error:', response.status, response.statusText);
                        // Try to read response text for more detailed error from server
                        return response.text().then(text => { throw new Error('Server responded with status ' + response.status + ': ' + text); });
                    }
                    return response.json();
                })
                .then(data => {
                    btnPlaceOrder.disabled = false;
                    updatePaymentDetails(); // Reset button text based on current selections

                    if (data.success) {
                        if (data.requires_otp) {
                            currentTransactionId = data.transaction_id;
                            openOtpModal(data.message || 'An OTP has been sent to your registered email address.');
                        } else {
                            // No OTP required (e.g., COD for advance or full payment), redirect directly
                            window.location.href = `order_confirmation.php?order_id=${data.order_id}`;
                        }
                    } else {
                        alert('Order failed: ' + (data.message || 'An unknown error occurred.'));
                    }
                })
                .catch(error => {
                    console.error('Error during checkout submission:', error);
                    btnPlaceOrder.disabled = false;
                    updatePaymentDetails(); // Reset button text
                    alert('A network error occurred. Please try again.'); // Generic user-facing message
                });
            });

            // --- OTP Verification Submission ---
            if (verifyOtpBtn) {
                verifyOtpBtn.addEventListener('click', function() {
                    const otp = Array.from(otpInputs).map(input => input.value).join('');

                    if (otp.length !== 6) {
                        showOtpMessage('Please enter a complete 6-digit OTP.');
                        return;
                    }

                    if (!currentTransactionId) {
                        showOtpMessage('No active transaction found. Please restart the order process.');
                        return;
                    }

                    verifyOtpBtn.disabled = true;
                    verifyOtpBtn.textContent = 'Verifying...';

                    const otpFormData = new FormData();
                    otpFormData.append('action', 'verify_otp');
                    otpFormData.append('otp', otp);
                    otpFormData.append('transaction_id', currentTransactionId);

                    fetch('../handlers/process_order.php', { // Path from public/ to handlers/
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
                            showOtpMessage(data.message || 'OTP verified! Order confirmed.', true);
                            setTimeout(() => {
                                closeOtpModal();
                                window.location.href = `order_confirmation.php?order_id=${data.order_id}`;
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

            // --- Resend OTP Logic ---
            if (resendOtpBtn) {
                resendOtpBtn.addEventListener('click', function() {
                    if (!currentTransactionId) {
                        showOtpMessage('No active transaction to resend OTP for.');
                        return;
                    }

                    resendOtpBtn.disabled = true;
                    resendOtpBtn.textContent = 'Resending...';

                    const resendFormData = new FormData();
                    resendFormData.append('action', 'resend_otp');
                    resendFormData.append('transaction_id', currentTransactionId);

                    fetch('../handlers/process_order.php', { // Path from public/ to handlers/
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
                            otpInputs.forEach(input => input.value = ''); // Clear OTP inputs
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

    <?php 
    // Include the master footer
    include 'footer.php'; 
    ?>
</body>
</html>