<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Form Modal</title>
    <link rel="stylesheet" href="../public/assets/css/main.css"> <!-- General styles -->
    <link rel="stylesheet" href="../public/assets/css/booking-form.css"> <!-- Specific modal styles -->
    <link rel="stylesheet" href="../public/assets/css/customer-dashboard.css"> <!-- For flash message styles -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <!-- Booking Modal HTML -->
    <div id="bookingModal" class="booking-modal active">
        <div class="booking-modal-content">
            <span class="close-modal-btn">&times;</span>
            <form id="bookingForm" class="booking-form" action="../handlers/handle_booking.php" method="POST">
                <h3 class="form-title">Booking Form</h3>
                <!-- Placeholder for flash messages from backend or JS -->
                <div id="modalFlashMessage" class="flash-message-container" style="position: static; transform: none; width: auto; max-width: none;"></div>

                <div class="form-section">
                    <label class="section-label">Account Details</label>
                    <div class="form-row">
                        <div class="input-group">
                            <span class="input-icon fas fa-user"></span>
                            <input type="text" id="customerName" name="customerName" required placeholder="Full Name" readonly>
                            <input type="hidden" id="customer_id" name="customer_id">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="input-group">
                            <span class="input-icon fas fa-envelope"></span>
                            <input type="email" id="customerEmail" name="customerEmail" required placeholder="Email Address" readonly>
                        </div>
                    </div>
                </div>
                <div class="form-section">
                    <label class="section-label">Service & Provider Details</label>
                    <div class="form-row">
                        <input type="text" id="projectDescription" name="projectDescription" required placeholder="Service Description" readonly>
                        <input type="text" id="providerName" name="providerName" required placeholder="Provider" readonly>
                        <input type="hidden" id="provider_id" name="provider_id">
                        <input type="hidden" id="quotation_id" name="quotation_id"> <!-- This is custom_quotations.id -->
                        <input type="hidden" id="booking_date" name="booking_date">
                    </div>
                    <div class="form-row">
                        <label class="section-label-inline">Advance Amount to Pay</label>
                        <input type="text" id="amount" name="amount" required placeholder="Amount" readonly>
                        <input type="hidden" id="payment_type" name="payment_type" value="advance"> <!-- Always advance payment for booking -->
                    </div>
                </div>
                <div class="form-section">
                    <label class="section-label">Payment Method</label>
                    <div class="form-row">
                        <select id="paymentMethod" name="paymentMethod" required class="full-width-select">
                            <option value="">Select Payment Method</option>
                            <option value="visa">Visa Card</option>
                            <option value="mastercard">MasterCard</option>
                        </select>
                    </div>
                </div>
                <div class="form-section">
                    <label class="section-label">Card Details</label>
                    <div class="form-row card-details-row">
                        <input type="text" id="cardNumber" name="cardNumber" required placeholder="Card Number" maxlength="16">
                        <input type="text" id="cardCVC" name="cardCVC" required placeholder="CVC" maxlength="4" class="short-input">
                    </div>
                    <div class="form-row">
                        <input type="text" id="cardExpiry" name="cardExpiry" required placeholder="MM/YY" class="short-input">
                    </div>
                </div>
                <button type="submit" class="btn-book">Pay Now</button>
            </form>

            <div id="otpSection" class="otp-section" style="display:none;">
                <h3 class="form-title">Verify Payment</h3>
                <p>An OTP has been sent to your registered mobile number.</p>
                <div class="form-group">
                    <label for="otpInput">Enter OTP</label>
                    <input type="text" id="otpInput" name="otpInput" maxlength="6" placeholder="******" class="otp-input">
                </div>
                <button type="button" id="submitOtpBtn" class="btn-book">Verify OTP</button>
                <button type="button" id="resendOtpBtn" class="btn-secondary-outline mt-2">Resend OTP</button>
            </div>

            <div id="bookingConfirmedMsg" class="booking-confirmed" style="display:none;">
                <i class="fas fa-check-circle success-icon"></i>
                <h3 class="form-title">Booking Confirmed!</h3>
                <p>Your payment was successful and your booking is confirmed.</p>
                <button type="button" id="closeConfirmedMsg" class="btn-book">Close</button>
            </div>
        </div>
    </div>
    <script>
    // --- Frontend Modal JS for interactivity and passing data from parent ---
    document.addEventListener('DOMContentLoaded', function() {
        const urlParams = new URLSearchParams(window.location.search);

        // Form fields
        const customerNameInput = document.getElementById('customerName');
        const customerEmailInput = document.getElementById('customerEmail');
        const customerIdInput = document.getElementById('customer_id');
        const projectDescriptionInput = document.getElementById('projectDescription'); // Renamed from serviceName
        const providerNameInput = document.getElementById('providerName');
        const providerIdInput = document.getElementById('provider_id');
        const quotationIdInput = document.getElementById('quotation_id');
        const amountInput = document.getElementById('amount');
        const bookingDateInput = document.getElementById('booking_date');
        const bookingForm = document.getElementById('bookingForm');
        const otpSection = document.getElementById('otpSection');
        const bookingConfirmedMsg = document.getElementById('bookingConfirmedMsg');
        const closeModalBtn = document.querySelector('.close-modal-btn');
        const closeConfirmedMsgBtn = document.getElementById('closeConfirmedMsg');
        const payNowBtn = bookingForm.querySelector('.btn-book');

        // Autofill fields from URL parameters (passed by view_quote.php or similar)
        if (customerNameInput) customerNameInput.value = decodeURIComponent(urlParams.get('customer_name') || '');
        if (customerEmailInput) customerEmailInput.value = decodeURIComponent(urlParams.get('customer_email') || '');
        if (customerIdInput) customerIdInput.value = urlParams.get('customer_id') || '';
        if (projectDescriptionInput) projectDescriptionInput.value = decodeURIComponent(urlParams.get('project_description') || '');
        if (providerNameInput) providerNameInput.value = decodeURIComponent(urlParams.get('provider_name') || '');
        if (providerIdInput) providerIdInput.value = urlParams.get('provider_id') || '';
        if (quotationIdInput) quotationIdInput.value = urlParams.get('quotation_id') || ''; // This is custom_quotations.id
        if (amountInput) amountInput.value = urlParams.get('amount') || '';
        if (bookingDateInput) bookingDateInput.value = urlParams.get('booking_date') || ''; // From calendar selection

        // Close modal logic
        if (closeModalBtn) {
            closeModalBtn.addEventListener('click', function() {
                parent.postMessage('closeBookingModal', '*'); // Message parent window to close
            });
        }
        if (closeConfirmedMsgBtn) {
            closeConfirmedMsgBtn.addEventListener('click', function() {
                parent.postMessage('closeBookingModal', '*'); // Message parent window to close
            });
        }

        // Function to display flash messages within the modal
        function showFlashMessage(type, message) {
            const container = document.getElementById('modalFlashMessage');
            if (container) {
                container.innerHTML = `<div class="flash-message ${type}">${message}<button type="button" class="flash-close-btn">&times;</button></div>`;
                const closeBtn = container.querySelector('.flash-close-btn');
                if(closeBtn) {
                    closeBtn.onclick = function() {
                        container.innerHTML = ''; // Clear message
                    };
                }
                // Optionally clear message after some time
                setTimeout(() => container.innerHTML = '', 7000);
            }
        }

        // --- Payment Form Submission (AJAX) ---
        bookingForm.addEventListener('submit', function(e) {
            e.preventDefault(); // Prevent default form submission

            const formData = new FormData(bookingForm);
            
            // Client-side input masking/validation (e.g., for card number/expiry)
            const cardNumber = document.getElementById('cardNumber').value.replace(/\s/g, '');
            const cardCVC = document.getElementById('cardCVC').value;
            const cardExpiry = document.getElementById('cardExpiry').value; // MM/YY

            if (!/^\d{16}$/.test(cardNumber)) {
                showFlashMessage('error', 'Please enter a valid 16-digit card number.');
                return;
            }
            if (!/^\d{3,4}$/.test(cardCVC)) {
                showFlashMessage('error', 'Please enter a valid 3 or 4-digit CVC.');
                return;
            }
            if (!/^(0[1-9]|1[0-2])\/?([0-9]{2})$/.test(cardExpiry)) {
                 showFlashMessage('error', 'Please enter expiry in MM/YY format (e.g., 12/28).');
                 return;
            }
            
            // Show loading spinner/disable button
            payNowBtn.textContent = 'Processing...';
            payNowBtn.disabled = true;

            fetch(bookingForm.action, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json()) // Expect JSON response from handle_booking.php
            .then(data => {
                payNowBtn.textContent = 'Pay Now';
                payNowBtn.disabled = false;

                if (data.success) {
                    if (data.requires_otp) {
                        bookingForm.style.display = 'none';
                        otpSection.style.display = 'block';
                        // Store transaction_id for OTP verification
                        document.getElementById('submitOtpBtn').setAttribute('data-transaction-id', data.transaction_id);
                        document.getElementById('submitOtpBtn').setAttribute('data-quotation-id', quotationIdInput.value);
                        document.getElementById('resendOtpBtn').setAttribute('data-transaction-id', data.transaction_id);

                        document.getElementById('otpInput').focus();
                        showFlashMessage('info', data.message); // e.g., "OTP sent!"

                    } else {
                        // Directly confirmed without OTP
                        bookingForm.style.display = 'none';
                        bookingConfirmedMsg.style.display = 'block';
                        showFlashMessage('success', data.message); // e.g., "Payment successful!"
                    }

                } else {
                    showFlashMessage('error', data.message || 'Payment failed. Please try again.');
                }
            })
            .catch(error => {
                console.error('Error during payment submission:', error);
                payNowBtn.textContent = 'Pay Now';
                payNowBtn.disabled = false;
                showFlashMessage('error', 'A network error occurred. Please try again.');
            });
        });

        // --- OTP Verification Submission (AJAX) ---
        document.getElementById('submitOtpBtn').addEventListener('click', function() {
            const otpInput = document.getElementById('otpInput').value;
            const transactionId = this.getAttribute('data-transaction-id');
            const quotationId = this.getAttribute('data-quotation-id');

            if (!/^\d{6}$/.test(otpInput)) {
                showFlashMessage('error', 'Please enter a valid 6-digit OTP.');
                return;
            }

            const otpFormData = new FormData();
            otpFormData.append('action', 'verify_otp');
            otpFormData.append('otp', otpInput);
            otpFormData.append('transaction_id', transactionId); // Use actual transaction ID
            otpFormData.append('quotation_id', quotationId); 

            this.textContent = 'Verifying...';
            this.disabled = true;

            fetch('../handlers/handle_booking.php', { // Same handler, different action
                method: 'POST',
                body: otpFormData
            })
            .then(response => response.json())
            .then(data => {
                document.getElementById('submitOtpBtn').textContent = 'Verify OTP';
                document.getElementById('submitOtpBtn').disabled = false;

                if (data.success) {
                    otpSection.style.display = 'none';
                    bookingConfirmedMsg.style.display = 'block';
                    showFlashMessage('success', data.message);
                } else {
                    showFlashMessage('error', data.message || 'OTP verification failed.');
                }
            })
            .catch(error => {
                console.error('Error during OTP verification:', error);
                document.getElementById('submitOtpBtn').textContent = 'Verify OTP';
                document.getElementById('submitOtpBtn').disabled = false;
                showFlashMessage('error', 'A network error occurred during OTP verification.');
            });
        });
        
        // --- Resend OTP Logic ---
        document.getElementById('resendOtpBtn').addEventListener('click', function() {
            const transactionId = this.getAttribute('data-transaction-id');
            const quotationId = document.getElementById('quotation_id').value; // Get from hidden field

            if (!transactionId || !quotationId) {
                showFlashMessage('error', 'Could not resend OTP. Missing transaction details.');
                return;
            }

            const resendFormData = new FormData();
            resendFormData.append('action', 'resend_otp');
            resendFormData.append('transaction_id', transactionId);
            resendFormData.append('quotation_id', quotationId);

            this.textContent = 'Sending...';
            this.disabled = true;

            fetch('../handlers/handle_booking.php', {
                method: 'POST',
                body: resendFormData
            })
            .then(response => response.json())
            .then(data => {
                document.getElementById('resendOtpBtn').textContent = 'Resend OTP';
                document.getElementById('resendOtpBtn').disabled = false;
                if (data.success) {
                    showFlashMessage('success', data.message);
                } else {
                    showFlashMessage('error', data.message || 'Failed to resend OTP.');
                }
            })
            .catch(error => {
                console.error('Error resending OTP:', error);
                document.getElementById('resendOtpBtn').textContent = 'Resend OTP';
                document.getElementById('resendOtpBtn').disabled = false;
                showFlashMessage('error', 'Network error during OTP resend.');
            });
        });

        // Initial setup for flash messages if coming from PHP redirect (e.g., if handle_booking.php redirected back)
        const phpFlashType = urlParams.get('flash_type');
        const phpFlashMessage = urlParams.get('flash_message');
        if (phpFlashType && phpFlashMessage) {
            showFlashMessage(phpFlashType, decodeURIComponent(phpFlashMessage));
        }

    });
    </script>
    <!-- <script src="../public/assets/js/booking-modal.js"></script> --> <!-- This JS is now embedded -->
</body>
</html>