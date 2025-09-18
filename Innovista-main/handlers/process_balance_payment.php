<?php
// C:\xampp1\htdocs\Innovista-final\Innovista-main\handlers\process_balance_payment.php

// --- TEMPORARY DEBUGGING START: SHOW ALL PHP ERRORS ---
// Remove these lines once you confirm everything is working
error_reporting(E_ALL); 
ini_set('display_errors', 1); 
// --- TEMPORARY DEBUGGING END ---

require_once '../public/session.php'; // For session_start(), isUserLoggedIn(), getUserId()
// flash_message.php is included by public/session.php, so set_flash_message is available.

require_once '../config/Database.php'; // For database connection

// --- PHPMailer Autoload ---
// IMPORTANT: These paths are relative from handlers/process_balance_payment.php
// Ensure your PHPMailer 'src' folder is exactly at:
// Innovista-final/Innovista-main/vendor/phpmailer/phpmailer/src/
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require_once '../vendor/phpmailer/phpmailer/src/Exception.php';
require_once '../vendor/phpmailer/phpmailer/src/PHPMailer.php';
require_once '../vendor/phpmailer/phpmailer/src/SMTP.php';


// --- SMTP Configuration (!!! YOU MUST FILL THESE IN !!!) ---
// These are the same details you used in process_order.php
define('SMTP_HOST', 'smtp.gmail.com');      // e.g., 'smtp.gmail.com' for Gmail
define('SMTP_USERNAME', 'jathushan006@gmail.com'); // <--- REPLACE with your FULL GMAIL ADDRESS
define('SMTP_PASSWORD', 'qhaqwgaovdnvjzkm'); // <--- REPLACE with the 16-CHARACTER APP PASSWORD you generated
define('SMTP_PORT', 587);                   // e.g., 587 (for TLS)
define('SMTP_ENCRYPTION', 'tls');           // e.g., 'tls'
define('SENDER_NAME', 'Innovista Support'); // Name that appears as sender
//qhaq wgao vdnv jzkm

header('Content-Type: application/json'); // Ensure this is set early

// Helper to send JSON response and exit
function sendJsonResponse(bool $success, string $message, array $data = []): void {
    if (ob_get_length() > 0) {
        ob_clean(); // Clear any buffered output like warnings/errors
    }
    echo json_encode(array_merge(['success' => $success, 'message' => $message], $data));
    exit();
}

// Helper to send OTP email (copied from process_order.php)
function sendOtpEmail(string $recipientEmail, string $otpCode, string $transactionId): bool {
    $mail = new PHPMailer(true); // true enables exceptions
    try {
        // Server settings
        $mail->SMTPDebug = 0;                       // Set to 2 for verbose debug output in dev (0 for no output)
        $mail->isSMTP();                            // Send using SMTP
        $mail->Host       = SMTP_HOST;              // Set the SMTP server to send through
        $mail->SMTPAuth   = true;                   // Enable SMTP authentication
        $mail->Username   = SMTP_USERNAME;          // SMTP username
        $mail->Password   = SMTP_PASSWORD;          // SMTP password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // Enable TLS encryption
        $mail->Port       = SMTP_PORT;              // TCP port to connect to

        // Recipients
        $mail->setFrom(SMTP_USERNAME, SENDER_NAME);
        $mail->addAddress($recipientEmail);         // Add a recipient

        // Content
        $mail->isHTML(true);                        // Set email format to HTML
        $mail->Subject = 'Innovista: Your One-Time Password (OTP) for Balance Payment';
        $mail->Body    = "
            <p>Dear Customer,</p>
            <p>To finalize your balance payment for Innovista Order, please use the following One-Time Password (OTP):</p>
            <h3 style='font-size: 24px; color: #0d9488;'>OTP: <strong>{$otpCode}</strong></h3>
            <p>This OTP is valid for 5 minutes.</p>
            <p>Your balance payment transaction ID is: <strong>{$transactionId}</strong></p>
            <p>If you did not initiate this payment, please ignore this email.</p>
            <p>Best regards,<br>Innovista Team</p>
        ";
        $mail->AltBody = "Innovista: Your One-Time Password (OTP) for Balance Payment\n\nOTP: {$otpCode}\n\nThis OTP is valid for 5 minutes.\nYour balance payment transaction ID is: {$transactionId}\n\nIf you did not initiate this payment, please ignore this email.\nBest regards,\nInnovista Team";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Balance OTP Email could not be sent. Mailer Error: {$mail->ErrorInfo}");
        return false;
    }
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

    if (!$order_id || $balance_amount_to_pay === false || $balance_amount_to_pay <= 0 || empty($payment_method) || !filter_var($shipping_email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Invalid payment details, missing order ID, or missing recipient email.');
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

                // --- Send OTP Email for Balance Payment ---
                if (!sendOtpEmail($shipping_email, $otp_code, $transaction_id)) {
                    error_log("Failed to send balance OTP email to " . $shipping_email);
                    throw new Exception("Failed to send OTP email for balance payment. Please try resending.");
                }

                $conn->commit();
                sendJsonResponse(true, $payment_gateway_response['message'], ['requires_otp' => true, 'transaction_id' => $transaction_id, 'order_id' => $order_id]);

            } else {
                throw new Exception($payment_gateway_response['message'] ?? 'Balance payment initiation failed at gateway.');
            }
        } else { // COD for balance payment
            // For COD, balance payment is assumed to be handled offline later,
            // but we update the order status now to 'completed' as payment terms are settled.
            updateOrderStatusAndRecordPayment($conn, $order_id, 'completed', 0.00, $balance_amount_to_pay, null, 'COD_BALANCE', 'COD');
            
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
            updateOrderStatusAndRecordPayment($conn, $order_id, 'completed', 0.00, $balance_amount_to_pay, $pending_otp_data['transaction_id'], 'CARD_BALANCE', $pending_otp_data['payment_method']);

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

        // --- Send NEW OTP Email for Balance Payment ---
        if (!sendOtpEmail($pending_otp_data['shipping_email'], $new_otp_code, $transaction_id_sent)) {
             error_log("Failed to resend OTP email for balance payment to " . $pending_otp_data['shipping_email']);
             throw new Exception("Failed to resend OTP email. Please try again.");
        }

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
function updateOrderStatusAndRecordPayment(PDO $conn, int $orderId, string $newStatus, float $newBalanceDue, float $paymentAmount, ?string $transactionId, string $paymentTypeRef = 'BALANCE_PAYMENT', string $paymentMethodRef = 'card'): void {
    
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

    // Record this balance payment in the 'payments' table 
    // The 'quotation_id' column in the payments table is being reused to store 'order_id' for order-related payments.
    // This is a pragmatic choice to avoid creating a new table for order-specific payments.
    $stmt_record_payment = $conn->prepare("
        INSERT INTO payments (quotation_id, amount, payment_type, transaction_id, payment_date)
        VALUES (:order_id, :amount, :payment_type, :transaction_id, NOW())
    ");
    
    $stmt_record_payment->bindParam(':order_id', $orderId, PDO::PARAM_INT); // Using order_id
    $stmt_record_payment->bindParam(':amount', $paymentAmount); 
    $stmt_record_payment->bindParam(':payment_type', $paymentTypeRef); 
    $stmt_record_payment->bindParam(':transaction_id', $transactionId);
    $stmt_record_payment->execute();
}