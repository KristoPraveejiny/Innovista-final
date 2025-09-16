<?php
// C:\xampp1\htdocs\Innovista-final\Innovista-main\handlers\process_order.php

require_once '../config/session.php'; // For session_start(), isUserLoggedIn(), getUserId()
require_once '../config/Database.php'; // For database connection

// --- PHPMailer Autoload ---
// IMPORTANT: These paths are relative from handlers/process_order.php
// Ensure your PHPMailer 'src' folder is exactly at:
// Innovista-final/Innovista-main/vendor/phpmailer/phpmailer/src/
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require_once '../vendor/phpmailer/phpmailer/src/Exception.php';
require_once '../vendor/phpmailer/phpmailer/src/PHPMailer.php';
require_once '../vendor/phpmailer/phpmailer/src/SMTP.php';


// --- SMTP Configuration (!!! YOU MUST FILL THESE IN !!!) ---
// These are the details you got from your Gmail account (App Password)
define('SMTP_HOST', 'smtp.gmail.com');      // For Gmail, use 'smtp.gmail.com'
define('SMTP_USERNAME', 'denujesunesan09@gmail.com'); // <-- REPLACE with your FULL GMAIL ADDRESS (e.g., innovista.app@gmail.com)
define('SMTP_PASSWORD', 'aesv djby dvav hnjf'); // <-- REPLACE with the 16-CHARACTER APP PASSWORD you generated
define('SMTP_PORT', 587);                   // For Gmail TLS, use 587
define('SMTP_ENCRYPTION', 'tls');           // For Gmail, use 'tls'
define('SENDER_NAME', 'Innovista Support'); // The name that appears as the sender in the email


header('Content-Type: application/json'); // Ensure this is set early

// Helper to send JSON response and exit
function sendJsonResponse(bool $success, string $message, array $data = []): void {
    echo json_encode(array_merge(['success' => $success, 'message' => $message], $data));
    exit();
}

// Helper to send OTP email
function sendOtpEmail(string $recipientEmail, string $otpCode, string $transactionId): bool {
    $mail = new PHPMailer(true); // true enables exceptions
    try {
        // Server settings
        $mail->SMTPDebug = 0;                       // Set to 2 for verbose debug output in dev (0 for no output)
        $mail->isSMTP();                            // Send using SMTP
        $mail->Host       = SMTP_HOST;              // Set the SMTP server to send through
        $mail->SMTPAuth   = true;                   // Enable SMTP authentication
        $mail->Username   = SMTP_USERNAME;          // SMTP username (your Gmail address)
        $mail->Password   = SMTP_PASSWORD;          // SMTP password (the App Password)
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // Enable TLS encryption
        $mail->Port       = SMTP_PORT;              // TCP port to connect to

        // Recipients
        $mail->setFrom(SMTP_USERNAME, SENDER_NAME);
        $mail->addAddress($recipientEmail);         // Add a recipient

        // Content
        $mail->isHTML(true);                        // Set email format to HTML
        $mail->Subject = 'Innovista: Your One-Time Password (OTP) for Payment Verification';
        $mail->Body    = "
            <p>Dear Customer,</p>
            <p>Thank you for placing an order with Innovista. To complete your payment, please use the following One-Time Password (OTP):</p>
            <h3 style='font-size: 24px; color: #0d9488;'>OTP: <strong>{$otpCode}</strong></h3>
            <p>This OTP is valid for 5 minutes.</p>
            <p>Your transaction ID is: <strong>{$transactionId}</strong></p>
            <p>If you did not initiate this payment, please ignore this email.</p>
            <p>Best regards,<br>Innovista Team</p>
        ";
        $mail->AltBody = "Innovista: Your One-Time Password (OTP) for Payment Verification\n\nOTP: {$otpCode}\n\nThis OTP is valid for 5 minutes.\nYour transaction ID is: {$transactionId}\n\nIf you did not initiate this payment, please ignore this email.\nBest regards,\nInnovista Team";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("OTP Email could not be sent. Mailer Error: {$mail->ErrorInfo}");
        return false;
    }
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

                // --- Send OTP Email ---
                if (!sendOtpEmail($shipping_email, $otp_code, $transaction_id)) {
                    error_log("Failed to send OTP email to " . $shipping_email);
                    // Crucial: If email fails, the user can't verify.
                    throw new Exception("Failed to send OTP email. Please check your email address and try again.");
                }

                $conn->commit(); 
                sendJsonResponse(true, $payment_gateway_response['message'], ['requires_otp' => true, 'transaction_id' => $transaction_id]);

            } else {
                throw new Exception($payment_gateway_response['message'] ?? 'Payment initiation failed at gateway.');
            }

        } else { // Cash on Delivery (COD) 
            $transaction_id = 'COD-ADV-' . uniqid(); // Internal reference for COD advance

            // For COD, the order is finalized directly with status `advance_paid` or `pending`
            // based on whether it's an advance or full payment.
            // If advance is 0, status is 'pending'. If advance > 0, status is 'advance_paid'.
            $status_for_cod = ($advanceAmount > 0) ? 'advance_paid' : 'pending'; 

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
                $payment_method, $selected_payment_terms, 
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
                $order_data_to_finalize['payment_method'], $order_data_to_finalize['payment_terms'], 
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

        // --- Send NEW OTP Email ---
        if (!sendOtpEmail($pending_otp_data['order_data']['shipping_email'], $new_otp_code, $transaction_id_sent)) {
             error_log("Failed to resend OTP email to " . $pending_otp_data['order_data']['shipping_email']);
             throw new Exception("Failed to resend OTP email. Please try again.");
        }

        $conn->commit(); // Commit the transaction for the resend
        sendJsonResponse(true, 'New OTP sent to your registered email address!', ['transaction_id' => $transaction_id_sent]);

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
    string $paymentTerms, 
    ?string $transactionId 
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
    $stmt_order->bindParam(':payment_terms', $paymentTerms); 
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