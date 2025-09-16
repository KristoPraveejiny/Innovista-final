<?php
// C:\xampp1\htdocs\Innovista-final\Innovista-main\handlers\process_balance_payment.php

require_once '../public/session.php'; // For session_start(), isUserLoggedIn(), getUserId()
require_once '../handlers/flash_message.php'; // For set_flash_message()
require_once '../config/Database.php'; // For database connection

header('Content-Type: application/json'); // Ensure this is set early

// Helper to send JSON response and exit
function sendJsonResponse(bool $success, string $message, array $data = []): void {
    echo json_encode(array_merge(['success' => $success, 'message' => $message], $data));
    exit();
}

// Ensure only POST requests are processed
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJsonResponse(false, 'Invalid request method.');
}

// Ensure user is logged in
if (!isUserLoggedIn()) {
    sendJsonResponse(false, 'You must be logged in to pay the balance.');
}

$user_id = getUserId();

// --- IMPORTANT: Implement CSRF Protection Here (HIGHLY RECOMMENDED) ---
// if (!validate_csrf_token($_POST['csrf_token'])) {
//     sendJsonResponse(false, 'Invalid request. Please try again.');
// }

$database = new Database();
$conn = null;
try {
    $conn = $database->getConnection();
} catch (PDOException $e) {
    error_log("Database connection error in process_balance_payment.php: " . $e->getMessage());
    sendJsonResponse(false, 'Database connection failed. Please try again later.');
}

try {
    $conn->beginTransaction(); // Start a database transaction

    $action = filter_input(INPUT_POST, 'action', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $order_id = filter_input(INPUT_POST, 'order_id', FILTER_VALIDATE_INT);
    $balance_amount_to_pay = filter_input(INPUT_POST, 'balance_amount', FILTER_VALIDATE_FLOAT);
    $payment_method = filter_input(INPUT_POST, 'payment_method', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $shipping_email = filter_input(INPUT_POST, 'shipping_email', FILTER_SANITIZE_EMAIL); // For OTP email

    // Card details (only for 'card' payment method)
    $card_number = ($payment_method === 'card') ? (filter_input(INPUT_POST, 'card_number', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? null) : null;
    $card_expiry = ($payment_method === 'card') ? (filter_input(INPUT_POST, 'card_expiry', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? null) : null;
    $card_cvc = ($payment_method === 'card') ? (filter_input(INPUT_POST, 'card_cvc', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? null) : null;

    if (!$order_id || $balance_amount_to_pay === false || $balance_amount_to_pay <= 0 || empty($payment_method)) {
        throw new Exception('Invalid payment details or missing order ID.');
    }

    // Verify order and balance due
    $stmt_order = $conn->prepare("SELECT id, user_id, balance_due, status FROM orders WHERE id = :order_id AND user_id = :user_id AND status = 'advance_paid' AND balance_due > 0");
    $stmt_order->bindParam(':order_id', $order_id, PDO::PARAM_INT);
    $stmt_order->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt_order->execute();
    $order_data = $stmt_order->fetch(PDO::FETCH_ASSOC);

    if (!$order_data) {
        throw new Exception('Order not found, balance already paid, or you do not have permission.');
    }
    if (abs($order_data['balance_due'] - $balance_amount_to_pay) > 0.01) { // Compare floats
        throw new Exception('Balance amount mismatch. Possible tampering.');
    }

    if ($action === 'pay_balance') {
        if ($payment_method === 'card') {
            if (empty($card_number) || empty($card_expiry) || empty($card_cvc)) {
                throw new Exception('Please provide complete card details for balance payment.');
            }
            // Simulate payment gateway call for the BALANCE AMOUNT
            $payment_gateway_response = [
                'success' => true, // Assume success for demo
                'transaction_id' => 'TRX-BAL-' . uniqid('order_'), // New transaction ID for balance
                'requires_otp' => true, // Force OTP for demo (3D Secure)
                'message' => 'Simulated card payment for balance initiated. OTP required.'
            ];

            if ($payment_gateway_response['success'] && $payment_gateway_response['requires_otp']) {
                $transaction_id = $payment_gateway_response['transaction_id'];

                // Generate and store OTP securely
                $otp_code = str_pad(mt_rand(100000, 999999), 6, '0', STR_PAD_LEFT);
                $_SESSION['pending_otp_balance_transaction'] = [
                    'order_id' => $order_id,
                    'transaction_id' => $transaction_id,
                    'payment_method' => $payment_method,
                    'otp_code' => $otp_code,
                    'otp_expires_at' => time() + (5 * 60), // OTP valid for 5 minutes
                    'shipping_email' => $shipping_email // Pass email for log
                ];

                error_log("Simulated OTP sent for balance payment to " . $shipping_email . " for order " . $order_id . " (Transaction: " . $transaction_id . "): " . $otp_code);

                $conn->commit();
                sendJsonResponse(true, $payment_gateway_response['message'], ['requires_otp' => true, 'transaction_id' => $transaction_id, 'order_id' => $order_id]);

            } else {
                throw new Exception($payment_gateway_response['message'] ?? 'Balance payment initiation failed at gateway.');
            }
        } else { // COD for balance payment
            // For COD, balance payment is assumed to be handled offline later,
            // but we update the order status now to 'completed' as payment terms are settled.
            updateOrderStatusAndBalance($conn, $order_id, 'completed', 0.00, $order_data['total_amount'], null, 'COD_BALANCE', 'COD');
            
            $conn->commit();
            sendJsonResponse(true, 'Balance payment confirmed via Cash on Delivery. Order is now complete!', ['order_id' => $order_id]);
        }

    } elseif ($action === 'verify_otp_balance') {
        $otp_entered = filter_input(INPUT_POST, 'otp', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $transaction_id_sent = filter_input(INPUT_POST, 'transaction_id', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

        if (empty($otp_entered) || empty($transaction_id_sent)) {
            throw new Exception('Missing OTP or transaction details for verification.');
        }

        $pending_otp_data = $_SESSION['pending_otp_balance_transaction'] ?? null;

        if (!$pending_otp_data || $pending_otp_data['transaction_id'] !== $transaction_id_sent || $pending_otp_data['order_id'] !== $order_id) {
            throw new Exception('No matching pending balance transaction or transaction expired. Please restart your payment.');
        }

        if (time() > $pending_otp_data['otp_expires_at']) {
            unset($_SESSION['pending_otp_balance_transaction']);
            throw new Exception('OTP has expired. Please resend or restart your payment.');
        }

        if ($otp_entered === $pending_otp_data['otp_code']) {
            // OTP is correct! Finalize balance payment
            updateOrderStatusAndBalance($conn, $order_id, 'completed', 0.00, $order_data['total_amount'], $pending_otp_data['transaction_id'], 'CARD_BALANCE', $pending_otp_data['payment_method']);

            unset($_SESSION['pending_otp_balance_transaction']); // Clear OTP data

            $conn->commit();
            sendJsonResponse(true, 'OTP verified and balance paid. Order is now complete!', ['order_id' => $order_id]);

        } else {
            throw new Exception('Invalid OTP. Please try again.');
        }

    } elseif ($action === 'resend_otp_balance') {
        $transaction_id_sent = filter_input(INPUT_POST, 'transaction_id', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

        if (empty($transaction_id_sent)) {
            throw new Exception('Missing transaction details for resend.');
        }

        $pending_otp_data = $_SESSION['pending_otp_balance_transaction'] ?? null;

        if (!$pending_otp_data || $pending_otp_data['transaction_id'] !== $transaction_id_sent || $pending_otp_data['order_id'] !== $order_id) {
            throw new Exception('No matching pending balance transaction found to resend OTP.');
        }

        $new_otp_code = str_pad(mt_rand(100000, 999999), 6, '0', STR_PAD_LEFT);
        $_SESSION['pending_otp_balance_transaction']['otp_code'] = $new_otp_code;
        $_SESSION['pending_otp_balance_transaction']['otp_expires_at'] = time() + (5 * 60);

        error_log("Simulated NEW OTP sent for balance payment to " . $pending_otp_data['shipping_email'] . " for order " . $order_id . " (Transaction: " . $transaction_id_sent . "): " . $new_otp_code);

        $conn->commit();
        sendJsonResponse(true, 'New OTP sent to your registered email address!', ['order_id' => $order_id]);

    } else {
        throw new Exception('Invalid action specified.');
    }

} catch (Exception $e) {
    if ($conn && $conn->inTransaction()) {
        $conn->rollBack();
    }
    error_log("Balance Payment Error: " . $e->getMessage());
    sendJsonResponse(false, $e->getMessage());
}


// Helper function to update order status and record payment (for balance)
function updateOrderStatusAndBalance(PDO $conn, int $orderId, string $newStatus, float $newBalanceDue, float $finalTotalAmount, ?string $transactionId, string $paymentTypeRef = 'BALANCE_PAYMENT', string $paymentMethodRef = 'card'): void {
    
    // Update the main order status and balance_due
    $stmt_update_order = $conn->prepare("
        UPDATE orders 
        SET status = :new_status, 
            balance_due = :new_balance_due, 
            balance_paid_date = NOW() 
        WHERE id = :order_id
    ");
    $stmt_update_order->bindParam(':new_status', $newStatus);
    $stmt_update_order->bindParam(':new_balance_due', $newBalanceDue);
    $stmt_update_order->bindParam(':order_id', $orderId, PDO::PARAM_INT);
    $stmt_update_order->execute();

    // Record this balance payment in the 'payments' table (assuming 'payments' table is generic for all transactions)
    $stmt_record_payment = $conn->prepare("
        INSERT INTO payments (quotation_id, amount, payment_type, transaction_id, payment_date)
        VALUES (:quotation_id, :amount, :payment_type, :transaction_id, NOW())
    ");
    // Note: 'quotation_id' in payments table typically links to 'custom_quotations.id'.
    // If you need to link to 'orders.id' for balance payments, you might need a new column or a separate table.
    // For now, we'll use a placeholder/internal ID to avoid strict FK issues for order payments in the 'payments' table.
    $placeholder_quotation_id = 0; // Use 0 or a special value to indicate it's an order balance payment not linked to a specific quotation
    
    $stmt_record_payment->bindParam(':quotation_id', $orderId, PDO::PARAM_INT); // Using order_id as foreign ID in payments table
    $stmt_record_payment->bindParam(':amount', $finalTotalAmount); // The amount being paid now (the balance)
    $stmt_record_payment->bindParam(':payment_type', $paymentTypeRef); // e.g., 'BALANCE_PAYMENT'
    $stmt_record_payment->bindParam(':transaction_id', $transactionId);
    $stmt_record_payment->execute();
}```

---

### **5. Updated `handlers/process_order.php` (Complete Code)**

*   **Changes:**
    *   **New Parameters:** The `finalizeOrder` helper function now correctly accepts `advanceAmount` and `balanceDue`.
    *   **OTP Flow Integration:**
        *   `place_order` action: Generates OTP, stores all order data (including advance/balance) in `$_SESSION['pending_otp_transaction']`, and responds with `requires_otp: true`.
        *   `verify_otp` action: Retrieves stored order data, verifies OTP, then calls `finalizeOrder` with `advance_paid` status.
        *   `resend_otp` action: Generates and logs a new OTP.
    *   **SQL `INSERT` into `orders`:** Now includes `advance_amount` and `balance_due` columns.
    *   **Order Status:** Sets the initial order `status` to `advance_paid` (for both card and COD) after the advance is processed (or initiated).
    *   **Payment Terms:** The `payment_terms` field is now handled for insertion.

**Please copy and paste this entire code block to replace the content of `C:\xampp1\htdocs\Innovista-final\Innovista-main\handlers\process_order.php`.**

```php
<?php
// C:\xampp1\htdocs\Innovista-final\Innovista-main\handlers\process_order.php

require_once '../public/session.php'; // For session_start(), isUserLoggedIn(), getUserId()
require_once '../config/Database.php'; // For database connection

header('Content-Type: application/json'); // Ensure this is set early

// Helper to send JSON response and exit
function sendJsonResponse(bool $success, string $message, array $data = []): void {
    echo json_encode(array_merge(['success' => $success, 'message' => $message], $data));
    exit();
}

// Ensure only POST requests are processed
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJsonResponse(false, 'Invalid request method.');
}

// Ensure user is logged in
if (!isUserLoggedIn()) {
    sendJsonResponse(false, 'You must be logged in to place an order.');
}

$user_id = getUserId();
$cart = $_SESSION['cart'] ?? [];

// Redirect if cart is empty (this shouldn't happen with JS checks, but as server-side safeguard)
if (empty($cart)) {
    sendJsonResponse(false, 'Your cart is empty. Please add products before checking out.');
}

// --- IMPORTANT: Implement CSRF Protection Here (HIGHLY RECOMMENDED) ---
// For POST requests, you would typically check a CSRF token.
// Example:
// if (!validate_csrf_token($_POST['csrf_token'])) {
//     sendJsonResponse(false, 'Invalid request. Please try again.');
// }


$database = new Database();
$conn = null; // Initialize $conn to null to handle connection errors gracefully
try {
    $conn = $database->getConnection();
} catch (PDOException $e) {
    error_log("Database connection error in process_order.php: " . $e->getMessage());
    sendJsonResponse(false, 'Database connection failed. Please try again later.');
}


try {
    $conn->beginTransaction(); // Start a database transaction

    $action = filter_input(INPUT_POST, 'action', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

    if ($action === 'place_order') {
        // --- 1. Retrieve and Sanitize Form Data ---
        $shipping_name = filter_input(INPUT_POST, 'shipping_name', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $shipping_address = filter_input(INPUT_POST, 'shipping_address', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $shipping_city = filter_input(INPUT_POST, 'shipping_city', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $shipping_zip = filter_input(INPUT_POST, 'shipping_zip', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $shipping_phone = filter_input(INPUT_POST, 'shipping_phone', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $shipping_email = filter_input(INPUT_POST, 'shipping_email', FILTER_SANITIZE_EMAIL);

        $same_as_shipping = isset($_POST['same_as_shipping']);

        $billing_name = $same_as_shipping ? $shipping_name : (filter_input(INPUT_POST, 'billing_name', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? null);
        $billing_address = $same_as_shipping ? $shipping_address : (filter_input(INPUT_POST, 'billing_address', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? null);
        $billing_city = $same_as_shipping ? $shipping_city : (filter_input(INPUT_POST, 'billing_city', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? null);
        $billing_zip = $same_as_shipping ? $shipping_zip : (filter_input(INPUT_POST, 'billing_zip', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? null);

        $payment_method = filter_input(INPUT_POST, 'payment_method', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

        $card_number = ($payment_method === 'card') ? (filter_input(INPUT_POST, 'card_number', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? null) : null;
        $card_expiry = ($payment_method === 'card') ? (filter_input(INPUT_POST, 'card_expiry', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? null) : null;
        $card_cvc = ($payment_method === 'card') ? (filter_input(INPUT_POST, 'card_cvc', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? null) : null;

        // NEW: Get amounts and payment terms from hidden inputs
        $cartTotal = filter_input(INPUT_POST, 'cart_total_amount', FILTER_VALIDATE_FLOAT);
        $advanceAmount = filter_input(INPUT_POST, 'advance_amount_to_pay', FILTER_VALIDATE_FLOAT);
        $balanceDue = filter_input(INPUT_POST, 'balance_due_amount', FILTER_VALIDATE_FLOAT);
        $selected_payment_terms = filter_input(INPUT_POST, 'selected_payment_terms', FILTER_SANITIZE_FULL_SPECIAL_CHARS);


        // --- 2. Server-Side Validation ---
        if (empty($shipping_name) || empty($shipping_address) || empty($shipping_city) || empty($shipping_zip) || empty($shipping_phone) || empty($shipping_email) ||
            !filter_var($shipping_email, FILTER_VALIDATE_EMAIL) || empty($payment_method) || empty($selected_payment_terms)) {
            throw new Exception('Please fill in all required shipping and payment details, and select payment terms.');
        }
        if ($cartTotal === false || $cartTotal <= 0 || $advanceAmount === false || $advanceAmount < 0 || $balanceDue === false || $balanceDue < 0) {
             throw new Exception('Invalid cart amounts detected.');
        }
        // Basic check that advance + balance == total (allowing for float precision)
        if (abs(($advanceAmount + $balanceDue) - $cartTotal) > 0.01) {
            throw new Exception('Payment calculation mismatch. Please re-check order totals.');
        }


        if ($payment_method === 'card') {
            if (empty($card_number) || empty($card_expiry) || empty($card_cvc)) {
                throw new Exception('Please provide complete card details.');
            }
            // Add more robust card validation (regex for format, expiry date check) here.
        }

        $transaction_id = null; // Will be set for card payments
        $order_status = 'pending'; // Default initial status

        // --- 3. Payment Processing (Simulated) and OTP Trigger ---
        if ($payment_method === 'card') {
            // Simulate a payment gateway call for pre-authorization of the ADVANCE AMOUNT
            $payment_gateway_response = [
                'success' => true, // Assume pre-auth success for demo
                'transaction_id' => 'TRX-' . uniqid('order_'),
                'requires_otp' => true, // Force OTP for demo (3D Secure)
                'message' => 'Simulated card payment for advance initiated. OTP required.'
            ];

            if ($payment_gateway_response['success'] && $payment_gateway_response['requires_otp']) {
                $transaction_id = $payment_gateway_response['transaction_id'];
                
                // Generate and store OTP securely
                $otp_code = str_pad(mt_rand(100000, 999999), 6, '0', STR_PAD_LEFT); // 6-digit OTP
                $_SESSION['pending_otp_transaction'] = [
                    'transaction_id' => $transaction_id,
                    'order_data' => [ // Store full order data for later finalization
                        'user_id' => $user_id,
                        'total_amount' => $cartTotal,
                        'advance_amount' => $advanceAmount, 
                        'balance_due' => $balanceDue,     
                        'shipping_name' => $shipping_name,
                        'shipping_address' => $shipping_address,
                        'shipping_city' => $shipping_city,
                        'shipping_zip' => $shipping_zip,
                        'shipping_phone' => $shipping_phone,
                        'shipping_email' => $shipping_email,
                        'billing_name' => $billing_name,
                        'billing_address' => $billing_address,
                        'billing_city' => $billing_city,
                        'billing_zip' => $billing_zip,
                        'payment_method' => $payment_method,
                        'payment_terms' => $selected_payment_terms, // Store payment terms
                        'transaction_id' => $transaction_id, // Store this as the advance transaction ID
                    ],
                    'cart_items' => $cart,
                    'otp_code' => $otp_code,
                    'otp_expires_at' => time() + (5 * 60) // OTP valid for 5 minutes
                ];

                // Simulate sending email (in a real app, integrate an email sending library)
                error_log("Simulated OTP sent to " . $shipping_email . " for transaction " . $transaction_id . ": " . $otp_code); // Log for testing

                $conn->commit(); 
                sendJsonResponse(true, $payment_gateway_response['message'], ['requires_otp' => true, 'transaction_id' => $transaction_id]);

            } else {
                throw new Exception($payment_gateway_response['message'] ?? 'Payment initiation failed at gateway.');
            }

        } else { // Cash on Delivery (COD) 
            $transaction_id = 'COD-ADV-' . uniqid(); // Internal reference for COD advance

            // For COD, the order is finalized directly with status `partial_payment` or `pending`
            // based on whether it's an advance or full payment.
            $status_for_cod = ($balanceDue > 0) ? 'advance_paid' : 'pending'; // 'pending' for full COD, 'advance_paid' for advance COD

            $finalized_order_id = finalizeOrder(
                $conn, 
                $user_id, 
                $cart, 
                $cartTotal, 
                $advanceAmount, 
                $balanceDue,    
                $status_for_cod, // Use determined status for COD
                $shipping_name, $shipping_address, $shipping_city, $shipping_zip, $shipping_phone, $shipping_email, 
                $billing_name, $billing_address, $billing_city, $billing_zip, 
                $payment_method, $selected_payment_terms, // Pass payment terms
                $transaction_id
            );
            $conn->commit();
            sendJsonResponse(true, 'Order placed successfully (Cash on Delivery)!', ['order_id' => $finalized_order_id]);
        }

    } elseif ($action === 'verify_otp') {
        $otp_entered = filter_input(INPUT_POST, 'otp', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $transaction_id_sent = filter_input(INPUT_POST, 'transaction_id', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

        if (empty($otp_entered) || empty($transaction_id_sent)) {
            throw new Exception('Missing OTP or transaction details for verification.');
        }

        $pending_otp_data = $_SESSION['pending_otp_transaction'] ?? null;

        if (!$pending_otp_data || $pending_otp_data['transaction_id'] !== $transaction_id_sent) {
            throw new Exception('No matching pending transaction or transaction expired. Please restart your order.');
        }

        // Check OTP expiry
        if (time() > $pending_otp_data['otp_expires_at']) {
            unset($_SESSION['pending_otp_transaction']); // Clear expired OTP
            throw new Exception('OTP has expired. Please resend or restart your order.');
        }

        // Verify OTP
        if ($otp_entered === $pending_otp_data['otp_code']) {
            // OTP is correct! Finalize the order
            $order_data_to_finalize = $pending_otp_data['order_data'];
            $cart_items_to_finalize = $pending_otp_data['cart_items'];
            
            // Re-calculate cart total to ensure integrity
            $recalculated_total = 0;
            foreach ($cart_items_to_finalize as $item) {
                $recalculated_total += ($item['price'] * $item['quantity']);
            }
            if (abs($recalculated_total - $order_data_to_finalize['total_amount']) > 0.01) { // Compare floats
                 throw new Exception('Cart total mismatch during finalization. Possible tampering.');
            }

            // Determine final status based on payment terms
            $final_order_status = ($order_data_to_finalize['balance_due'] > 0) ? 'advance_paid' : 'completed';

            $finalized_order_id = finalizeOrder(
                $conn, 
                $order_data_to_finalize['user_id'], 
                $cart_items_to_finalize, 
                $order_data_to_finalize['total_amount'], 
                $order_data_to_finalize['advance_amount'], 
                $order_data_to_finalize['balance_due'],    
                $final_order_status, // Status after advance payment
                $order_data_to_finalize['shipping_name'], $order_data_to_finalize['shipping_address'], $order_data_to_finalize['shipping_city'], $order_data_to_finalize['shipping_zip'], $order_data_to_finalize['shipping_phone'], $order_data_to_finalize['shipping_email'], 
                $order_data_to_finalize['billing_name'], $order_data_to_finalize['billing_address'], $order_data_to_finalize['billing_city'], $order_data_to_finalize['billing_zip'], 
                $order_data_to_finalize['payment_method'], $order_data_to_finalize['payment_terms'], // Pass payment terms
                $order_data_to_finalize['transaction_id']
            );

            unset($_SESSION['pending_otp_transaction']); // Clear OTP data
            unset($_SESSION['cart']); // Clear actual cart after successful order

            $conn->commit();
            sendJsonResponse(true, 'OTP verified and order placed successfully!', ['order_id' => $finalized_order_id]);

        } else {
            throw new Exception('Invalid OTP. Please try again.');
        }

    } elseif ($action === 'resend_otp') {
        $transaction_id_sent = filter_input(INPUT_POST, 'transaction_id', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

        if (empty($transaction_id_sent)) {
            throw new Exception('Missing transaction details for resend.');
        }

        $pending_otp_data = $_SESSION['pending_otp_transaction'] ?? null;

        if (!$pending_otp_data || $pending_otp_data['transaction_id'] !== $transaction_id_sent) {
            throw new Exception('No matching pending transaction found to resend OTP.');
        }

        // Generate a new OTP
        $new_otp_code = str_pad(mt_rand(100000, 999999), 6, '0', STR_PAD_LEFT);
        $_SESSION['pending_otp_transaction']['otp_code'] = $new_otp_code;
        $_SESSION['pending_otp_transaction']['otp_expires_at'] = time() + (5 * 60); // Reset expiry

        // Simulate sending new email
        error_log("Simulated NEW OTP sent to " . $pending_otp_data['order_data']['shipping_email'] . " for transaction " . $transaction_id_sent . ": " . $new_otp_code); // Log for testing

        $conn->commit(); // Commit the transaction for the resend
        sendJsonResponse(true, 'New OTP sent to your registered email address!');

    } else {
        throw new Exception('Invalid action specified.');
    }

} catch (Exception $e) {
    if ($conn && $conn->inTransaction()) {
        $conn->rollBack(); // Rollback if any part of the transaction fails
    }
    error_log("Order Processing Error: " . $e->getMessage());
    sendJsonResponse(false, $e->getMessage()); // Send specific error message from exception
}


// --- Helper function to finalize the order (insert into DB) ---
function finalizeOrder(
    PDO $conn, 
    int $userId, 
    array $cartItems, 
    float $totalAmount, 
    float $advanceAmount, 
    float $balanceDue,    
    string $status,
    string $shippingName, 
    string $shippingAddress, 
    string $shippingCity, 
    string $shippingZip, 
    string $shippingPhone, 
    string $shippingEmail, 
    ?string $billingName, 
    ?string $billingAddress, 
    ?string $billingCity, 
    ?string $billingZip, 
    string $paymentMethod, 
    string $paymentTerms, // NEW PARAMETER
    ?string $transactionId // This is the transaction ID for the ADVANCE payment
): int {
    $stmt_order = $conn->prepare("
        INSERT INTO orders 
        (user_id, total_amount, advance_amount, balance_due, status, shipping_name, shipping_address, shipping_city, shipping_zip, shipping_phone, shipping_email, 
         billing_name, billing_address, billing_city, billing_zip, payment_method, payment_terms, transaction_id)
        VALUES 
        (:user_id, :total_amount, :advance_amount, :balance_due, :status, :shipping_name, :shipping_address, :shipping_city, :shipping_zip, :shipping_phone, :shipping_email, 
         :billing_name, :billing_address, :billing_city, :billing_zip, :payment_method, :payment_terms, :transaction_id)
    ");

    $stmt_order->bindParam(':user_id', $userId, PDO::PARAM_INT);
    $stmt_order->bindParam(':total_amount', $totalAmount);
    $stmt_order->bindParam(':advance_amount', $advanceAmount); 
    $stmt_order->bindParam(':balance_due', $balanceDue);       
    $stmt_order->bindParam(':status', $status);
    $stmt_order->bindParam(':shipping_name', $shippingName);
    $stmt_order->bindParam(':shipping_address', $shippingAddress);
    $stmt_order->bindParam(':shipping_city', $shippingCity);
    $stmt_order->bindParam(':shipping_zip', $shippingZip);
    $stmt_order->bindParam(':shipping_phone', $shippingPhone);
    $stmt_order->bindParam(':shipping_email', $shippingEmail);
    $stmt_order->bindParam(':billing_name', $billingName);
    $stmt_order->bindParam(':billing_address', $billingAddress);
    $stmt_order->bindParam(':billing_city', $billingCity);
    $stmt_order->bindParam(':billing_zip', $billingZip);
    $stmt_order->bindParam(':payment_method', $paymentMethod);
    $stmt_order->bindParam(':payment_terms', $paymentTerms); // BIND NEW COLUMN
    $stmt_order->bindParam(':transaction_id', $transactionId); 
    $stmt_order->execute();

    $order_id = $conn->lastInsertId();

    $stmt_order_item = $conn->prepare("
        INSERT INTO order_items 
        (order_id, product_id, product_name, unit_price, quantity, image_path, color)
        VALUES 
        (:order_id, :product_id, :product_name, :unit_price, :quantity, :image_path, :color)
    ");

    foreach ($cartItems as $item) {
        $stmt_order_item->bindParam(':order_id', $order_id, PDO::PARAM_INT);
        $product_db_id = (int)$item['db_product_id']; 
        $stmt_order_item->bindParam(':product_id', $product_db_id, PDO::PARAM_INT);
        $stmt_order_item->bindParam(':product_name', $item['name']);
        $stmt_order_item->bindParam(':unit_price', $item['price']);
        $stmt_order_item->bindParam(':quantity', $item['quantity'], PDO::PARAM_INT);
        $stmt_order_item->bindParam(':image_path', $item['image_path']);
        $stmt_order_item->bindParam(':color', $item['color']);
        $stmt_order_item->execute();
    }
    
    return $order_id;
}

?>