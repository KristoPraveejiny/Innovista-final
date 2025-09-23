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

// Handle direct product purchase or get cart from session
$cart = [];
$cartTotal = 0;

// Check if this is a direct product purchase
if (isset($_POST['product_data'])) {
    $productData = json_decode($_POST['product_data'], true);
    if ($productData) {
        $cart = [$productData]; // Single product as cart
        $cartTotal = $productData['price'] * ($productData['quantity'] ?? 1);
    }
} else {
// Get the user's cart from the session
$cart = $_SESSION['cart'] ?? [];

// Calculate cart total
if (!empty($cart)) {
    foreach ($cart as $item) {
            $cartTotal += ($item['price'] * ($item['quantity'] ?? 1));
        }
    }
}

// Default payment terms for initial display (full payment)
$initialAdvanceAmount = $cartTotal; // Full payment by default
$initialBalanceDue = 0; // No balance due for full payment

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
                <form id="checkoutForm" action="../handlers/process_direct_order.php" method="POST">
                    <!-- Hidden inputs for calculated amounts -->
                    <input type="hidden" id="cart_total_amount_hidden" name="cart_total_amount" value="<?php echo htmlspecialchars(number_format($cartTotal, 2, '.', '')); ?>">
                    <input type="hidden" id="advance_amount_to_pay_hidden" name="advance_amount_to_pay" value="<?php echo htmlspecialchars(number_format($initialAdvanceAmount, 2, '.', '')); ?>">
                    <input type="hidden" id="balance_due_amount_hidden" name="balance_due_amount" value="<?php echo htmlspecialchars(number_format($initialBalanceDue, 2, '.', '')); ?>">
                    <input type="hidden" id="selected_payment_terms_hidden" name="selected_payment_terms" value="full_card"> <!-- Default -->
                    <input type="hidden" name="product_data" value="<?php echo htmlspecialchars(json_encode($cart[0] ?? [])); ?>">

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

                    <!-- Quantity -->
                    <h3 style="margin-top: 2rem;">Order Details</h3>
                    <div class="form-group">
                        <label for="quantity">Quantity</label>
                        <input type="number" id="quantity" name="quantity" min="1" value="<?php echo $cart[0]['quantity'] ?? 1; ?>" required>
                    </div>

                    <h3 style="margin-top: 2rem;">Payment Method</h3>
                    <div class="form-group">
                        <label for="payment_method">Select Payment Option:</label>
                        <select id="payment_method" name="payment_method" required>
                            <option value="card" selected>Credit/Debit Card Payment</option>
                        </select>
                    </div>
                    
                    <div id="card-details-section" style="display:block;">
                        <h4>Card Details</h4>
                        <div class="form-group">
                            <label for="card_number">Card Number *</label>
                            <input type="text" id="card_number" name="card_number" placeholder="•••• •••• •••• ••••" maxlength="19" required pattern="[0-9\s]{13,19}" title="Enter a valid 16-digit card number">
                            <div id="card_number_error" class="error-message" style="color: red; font-size: 12px; display: none;"></div>
                        </div>
                        <div class="form-group">
                            <label for="card_expiry">Expiry Date (MM/YY) *</label>
                            <input type="text" id="card_expiry" name="card_expiry" placeholder="MM/YY" maxlength="5" required pattern="(0[1-9]|1[0-2])\/([0-9]{2})" title="Enter expiry date in MM/YY format">
                            <div id="card_expiry_error" class="error-message" style="color: red; font-size: 12px; display: none;"></div>
                        </div>
                        <div class="form-group">
                            <label for="card_cvc">CVC *</label>
                            <input type="text" id="card_cvc" name="card_cvc" placeholder="CVC" maxlength="4" required pattern="[0-9]{3,4}" title="Enter 3 or 4 digit CVC">
                            <div id="card_cvc_error" class="error-message" style="color: red; font-size: 12px; display: none;"></div>
                        </div>
                        <div class="form-group">
                            <label for="cardholder_name">Cardholder Name *</label>
                            <input type="text" id="cardholder_name" name="cardholder_name" placeholder="Name on Card" required pattern="[A-Za-z\s]{2,50}" title="Enter cardholder name">
                            <div id="cardholder_name_error" class="error-message" style="color: red; font-size: 12px; display: none;"></div>
                        </div>
                    </div>

                    <!-- OTP Verification Section -->
                    <div id="otp-section" style="display:none; margin-top: 2rem; padding: 1rem; border: 1px solid #ddd; border-radius: 8px; background-color: #f9f9f9;">
                        <h4>OTP Verification</h4>
                        <p>We've sent a 6-digit OTP to your registered email address. Please enter it below to complete your payment.</p>
                        <div class="form-group">
                            <label for="otp_code">Enter OTP *</label>
                            <input type="text" id="otp_code" name="otp_code" placeholder="000000" maxlength="6" pattern="[0-9]{6}">
                            <div id="otp_error" class="error-message" style="color: red; font-size: 12px; display: none;"></div>
                        </div>
                        <div style="margin-top: 1rem;">
                            <button type="button" id="verify_otp_btn" class="btn btn-primary" style="margin-right: 1rem;">Verify OTP & Complete Payment</button>
                            <button type="button" id="resend_otp_btn" class="btn btn-secondary">Resend OTP</button>
                        </div>
                        <div id="otp_timer" style="margin-top: 0.5rem; font-size: 12px; color: #666;"></div>
                    </div>

                    <!-- Dynamic payment choice display -->
                    <div id="payment-choice-summary" class="payment-choice-details">
                        <!-- This will be populated by JavaScript -->
                    </div>

                    <?php if (empty($cart)): ?>
                        <p class="cart-empty-message">Your cart is empty. Cannot proceed to checkout.</p>
                        <button type="submit" class="btn-place-order" disabled>Place Order</button>
                    <?php else: ?>
                        <button type="submit" class="btn-place-order">Pay Now (Rs. <?php echo htmlspecialchars(number_format($cartTotal, 2)); ?>)</button>
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
                    <span>Total Payment:</span>
                    <span id="summaryPaymentNow">Rs. <?php echo htmlspecialchars(number_format($cartTotal, 2)); ?></span>
                </div>
            </div>
        </div>
    </main>


    <script>
        // Global variables to store checkout form data temporarily
        let checkoutFormData = null;
        let currentTransactionId = null; // Store transaction ID for OTP verification
        const cartTotalAmount = parseFloat(document.getElementById('cart_total_amount_hidden').value); // Get total from PHP

        document.addEventListener('DOMContentLoaded', function() {
            const paymentMethodSelect = document.getElementById('payment_method');
            const cardDetailsSection = document.getElementById('card-details-section');
            const checkoutForm = document.getElementById('checkoutForm');
            const btnPlaceOrder = document.querySelector('.btn-place-order');

            const summaryCartTotal = document.getElementById('summaryCartTotal');
            const summaryPaymentNow = document.getElementById('summaryPaymentNow');
            const paymentChoiceSummary = document.getElementById('payment-choice-summary');


            const hiddenAdvanceAmountInput = document.getElementById('advance_amount_to_pay_hidden');
            const hiddenBalanceDueAmountInput = document.getElementById('balance_due_amount_hidden');
            const hiddenSelectedPaymentTermsInput = document.getElementById('selected_payment_terms_hidden');



            // --- Form Field Toggling ---

            // Card details are always shown since only card payment is available

            // --- Payment Calculation & Display Logic ---
            function updatePaymentDetails() {
                const quantity = parseInt(document.getElementById('quantity').value) || 1;
                
                // Update cart total based on quantity
                const basePrice = <?php echo $cart[0]['price'] ?? 0; ?>;
                const updatedCartTotal = basePrice * quantity;
                
                // Update cart total display
                document.getElementById('cart_total_amount_hidden').value = updatedCartTotal.toFixed(2);
                summaryCartTotal.textContent = `Rs. ${updatedCartTotal.toFixed(2)}`;
                
                // Only card payment available - full payment
                const totalPayment = updatedCartTotal;
                const paymentTermsText = "Credit/Debit Card Payment";
                const selectedPaymentTermsValue = "full_card";

                summaryPaymentNow.textContent = `Rs. ${totalPayment.toFixed(2)}`;
                btnPlaceOrder.textContent = `Pay Now (Rs. ${totalPayment.toFixed(2)})`;
                
                // Update hidden inputs for backend
                hiddenAdvanceAmountInput.value = totalPayment.toFixed(2);
                hiddenBalanceDueAmountInput.value = "0.00";
                hiddenSelectedPaymentTermsInput.value = selectedPaymentTermsValue;

                // Update dynamic summary text
                paymentChoiceSummary.innerHTML = `
                    <p>You have chosen: <span>${paymentTermsText}</span></p>
                    <p>Total amount to pay: <span>Rs. ${totalPayment.toFixed(2)}</span></p>
                `;
            }


            // --- Card Validation Functions ---
            function validateCardNumber(cardNumber) {
                // Remove spaces and check if it's all digits
                const cleaned = cardNumber.replace(/\s/g, '');
                if (!/^\d{13,19}$/.test(cleaned)) {
                    return false;
                }
                
                // Luhn algorithm validation
                let sum = 0;
                let isEven = false;
                for (let i = cleaned.length - 1; i >= 0; i--) {
                    let digit = parseInt(cleaned.charAt(i));
                    if (isEven) {
                        digit *= 2;
                        if (digit > 9) {
                            digit -= 9;
                        }
                    }
                    sum += digit;
                    isEven = !isEven;
                }
                return sum % 10 === 0;
            }

            function validateExpiryDate(expiry) {
                const regex = /^(0[1-9]|1[0-2])\/([0-9]{2})$/;
                if (!regex.test(expiry)) return false;
                
                const [month, year] = expiry.split('/');
                const currentDate = new Date();
                const currentYear = currentDate.getFullYear() % 100;
                const currentMonth = currentDate.getMonth() + 1;
                
                const expYear = parseInt(year);
                const expMonth = parseInt(month);
                
                if (expYear < currentYear) return false;
                if (expYear === currentYear && expMonth < currentMonth) return false;
                
                return true;
            }

            function validateCVC(cvc) {
                return /^\d{3,4}$/.test(cvc);
            }

            function validateCardholderName(name) {
                return /^[A-Za-z\s]{2,50}$/.test(name);
            }

            function showError(fieldId, message) {
                const errorDiv = document.getElementById(fieldId + '_error');
                if (errorDiv) {
                    errorDiv.textContent = message;
                    errorDiv.style.display = 'block';
                }
            }

            function hideError(fieldId) {
                const errorDiv = document.getElementById(fieldId + '_error');
                if (errorDiv) {
                    errorDiv.style.display = 'none';
                }
            }

            // --- OTP Functions ---
            let otpTimer = null;
            let otpAttempts = 0;
            const maxOtpAttempts = 3;

            function generateOTP() {
                return Math.floor(100000 + Math.random() * 900000).toString();
            }

            function sendOTP() {
                // Get email from the shipping email field
                const emailField = document.getElementById('shipping_email');
                const email = emailField ? emailField.value : '';
                
                if (!email) {
                    alert('Please enter your email address first');
                    return;
                }
                
                // Send OTP request to server
                fetch('../handlers/send_otp.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'action=send_otp&email=' + encodeURIComponent(email)
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Store transaction ID for later use
                        window.currentTransactionId = data.transaction_id;
                        
                        // Show OTP section
                        document.getElementById('otp-section').style.display = 'block';
                        document.getElementById('verify_otp_btn').disabled = false;
                        
                        // Update OTP message to show which email it was sent to
                        const otpMessage = document.querySelector('#otp-section p');
                        if (otpMessage) {
                            otpMessage.textContent = `We've sent a 6-digit OTP to ${data.email_sent_to || email}. Please enter it below to complete your payment.`;
                        }
                        
                        // Start timer (5 minutes)
                        startOTPTimer(300);
                        
                        alert('OTP sent to ' + (data.email_sent_to || email) + '! Please check your email.');
                    } else {
                        alert('Failed to send OTP: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Failed to send OTP. Please try again.');
                });
            }

            function startOTPTimer(seconds) {
                let timeLeft = seconds;
                const timerDiv = document.getElementById('otp_timer');
                const resendBtn = document.getElementById('resend_otp_btn');
                
                otpTimer = setInterval(() => {
                    const minutes = Math.floor(timeLeft / 60);
                    const secs = timeLeft % 60;
                    timerDiv.textContent = `OTP expires in: ${minutes}:${secs.toString().padStart(2, '0')}`;
                    
                    if (timeLeft <= 0) {
                        clearInterval(otpTimer);
                        timerDiv.textContent = 'OTP expired. Please request a new one.';
                        resendBtn.disabled = false;
                        document.getElementById('verify_otp_btn').disabled = true;
                    }
                    
                    timeLeft--;
                }, 1000);
            }

            function verifyOTP() {
                const enteredOTP = document.getElementById('otp_code').value;
                
                if (enteredOTP.length !== 6) {
                    showError('otp', 'Please enter a 6-digit OTP');
                    return false;
                }
                
                // For now, just validate the format - server will verify the actual OTP
                hideError('otp');
                return true;
            }

            // Initial state
            // Make card fields required since only card payment is available
            cardDetailsSection.querySelectorAll('input').forEach(input => {
                input.setAttribute('required', 'required');
            });
            updatePaymentDetails(); // Initial calculation

            // --- Card Validation Event Listeners ---
            const cardNumberInput = document.getElementById('card_number');
            const cardExpiryInput = document.getElementById('card_expiry');
            const cardCvcInput = document.getElementById('card_cvc');
            const cardholderNameInput = document.getElementById('cardholder_name');

            // Card number formatting and validation
            if (cardNumberInput) {
                cardNumberInput.addEventListener('input', function(e) {
                    let value = e.target.value.replace(/\s/g, '').replace(/[^0-9]/gi, '');
                    let formattedValue = value.match(/.{1,4}/g)?.join(' ') || value;
                    if (formattedValue !== e.target.value) {
                        e.target.value = formattedValue;
                    }
                    
                    if (value.length >= 13) {
                        if (validateCardNumber(e.target.value)) {
                            hideError('card_number');
                        } else {
                            showError('card_number', 'Invalid card number');
                        }
                    } else {
                        hideError('card_number');
                    }
                });
            }

            // Expiry date formatting and validation
            if (cardExpiryInput) {
                cardExpiryInput.addEventListener('input', function(e) {
                    let value = e.target.value.replace(/\D/g, '');
                    if (value.length >= 2) {
                        value = value.substring(0, 2) + '/' + value.substring(2, 4);
                    }
                    e.target.value = value;
                    
                    if (value.length === 5) {
                        if (validateExpiryDate(value)) {
                            hideError('card_expiry');
                        } else {
                            showError('card_expiry', 'Invalid or expired date');
                        }
                    } else {
                        hideError('card_expiry');
                    }
                });
            }

            // CVC validation
            if (cardCvcInput) {
                cardCvcInput.addEventListener('input', function(e) {
                    e.target.value = e.target.value.replace(/[^0-9]/g, '');
                    if (e.target.value.length >= 3) {
                        if (validateCVC(e.target.value)) {
                            hideError('card_cvc');
                        } else {
                            showError('card_cvc', 'Invalid CVC');
                        }
                    } else {
                        hideError('card_cvc');
                    }
                });
            }

            // Cardholder name validation
            if (cardholderNameInput) {
                cardholderNameInput.addEventListener('input', function(e) {
                    if (e.target.value.length >= 2) {
                        if (validateCardholderName(e.target.value)) {
                            hideError('cardholder_name');
                        } else {
                            showError('cardholder_name', 'Invalid name format');
                        }
                    } else {
                        hideError('cardholder_name');
                    }
                });
            }

            // --- OTP Event Listeners ---
            const verifyOtpBtn = document.getElementById('verify_otp_btn');
            const resendOtpBtn = document.getElementById('resend_otp_btn');

            if (verifyOtpBtn) {
                verifyOtpBtn.addEventListener('click', function() {
                    if (verifyOTP()) {
                        // OTP verified, proceed with payment
                        processPayment();
                    }
                });
            }

            if (resendOtpBtn) {
                resendOtpBtn.addEventListener('click', function() {
                    sendOTP();
                    this.disabled = true;
                });
            }

            // --- Payment Processing ---
            function processPayment() {
                // Validate all card fields before proceeding
                const cardNumber = cardNumberInput.value;
                const cardExpiry = cardExpiryInput.value;
                const cardCvc = cardCvcInput.value;
                const cardholderName = cardholderNameInput.value;
                const otpCode = document.getElementById('otp_code').value;
                const otpSection = document.getElementById('otp-section');

                let isValid = true;

                if (!validateCardNumber(cardNumber)) {
                    showError('card_number', 'Invalid card number');
                    isValid = false;
                }
                if (!validateExpiryDate(cardExpiry)) {
                    showError('card_expiry', 'Invalid or expired date');
                    isValid = false;
                }
                if (!validateCVC(cardCvc)) {
                    showError('card_cvc', 'Invalid CVC');
                    isValid = false;
                }
                if (!validateCardholderName(cardholderName)) {
                    showError('cardholder_name', 'Invalid name format');
                    isValid = false;
                }
                
                // Only validate OTP if OTP section is visible
                if (otpSection.style.display !== 'none' && otpCode.length !== 6) {
                    showError('otp', 'Please enter a 6-digit OTP');
                    isValid = false;
                }

                if (!isValid) {
                    alert('Please fix the errors above before proceeding.');
                    return;
                }

                // Add transaction ID to form data if OTP section is visible
                const form = document.getElementById('checkoutForm');
                if (otpSection.style.display !== 'none') {
                    // Add transaction ID
                    const transactionInput = document.createElement('input');
                    transactionInput.type = 'hidden';
                    transactionInput.name = 'transaction_id';
                    transactionInput.value = window.currentTransactionId || '';
                    form.appendChild(transactionInput);
                }

                // Submit the form
                form.submit();
            }

            // Event listeners
            if (paymentMethodSelect) {
                paymentMethodSelect.addEventListener('change', updatePaymentDetails);
            }
            
            // Quantity change listener
            const quantityInput = document.getElementById('quantity');
            if (quantityInput) {
                quantityInput.addEventListener('change', updatePaymentDetails);
                quantityInput.addEventListener('input', updatePaymentDetails);
            }

            // Single form submission handler
            if (checkoutForm) {
                checkoutForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    
                    // Check if OTP section is visible (meaning OTP was sent)
                    const otpSection = document.getElementById('otp-section');
                    if (otpSection.style.display === 'none') {
                        // OTP not sent yet, send it first
                        sendOTP();
                    } else {
                        // OTP section is visible, verify OTP first
                        const otpCode = document.getElementById('otp_code').value;
                        if (otpCode.length === 6) {
                            // OTP entered, proceed with payment
                            processPayment();
                        } else {
                            showError('otp', 'Please enter a 6-digit OTP');
                        }
                    }
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