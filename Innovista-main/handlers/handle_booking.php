<?php
// C:\xampp1\htdocs\Innovista-final\Innovista-main\handlers\handle_booking.php

require_once '../public/session.php'; // For session_start() and helper functions
require_once '../handlers/flash_message.php'; // For setting flash messages
require_once '../config/Database.php';

// --- PHPMailer Autoload ---
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require_once '../vendor/phpmailer/phpmailer/src/Exception.php';
require_once '../vendor/phpmailer/phpmailer/src/PHPMailer.php';
require_once '../vendor/phpmailer/phpmailer/src/SMTP.php';

// --- SMTP Configuration ---
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_USERNAME', 'jathushan006@gmail.com');
define('SMTP_PASSWORD', 'qhaqwgaovdnvjzkm');
define('SMTP_PORT', 587);
define('SMTP_ENCRYPTION', 'tls');
define('SENDER_NAME', 'Innovista Support');

header('Content-Type: application/json'); // This handler will respond with JSON

// Check if user is logged in
if (!isUserLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'You must be logged in to book a service.']);
    exit();
}

$loggedInUserId = getUserId();
$loggedInUserRole = getUserRole();

// Get the PDO connection object
$database = new Database();
$conn = $database->getConnection();

// Helper to send JSON response and exit
function sendJsonResponse(bool $success, string $message, array $data = []): void {
    echo json_encode(array_merge(['success' => $success, 'message' => $message], $data));
    exit();
}

// Helper to send OTP email
function sendOtpEmail(string $recipientEmail, string $otpCode, string $transactionId): bool {
    $mail = new PHPMailer(true);
    try {
        $mail->SMTPDebug = 0;
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USERNAME;
        $mail->Password   = SMTP_PASSWORD;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = SMTP_PORT;

        $mail->setFrom(SMTP_USERNAME, SENDER_NAME);
        $mail->addAddress($recipientEmail);

        $mail->isHTML(true);
        $mail->Subject = 'Innovista: Your One-Time Password (OTP) for Booking';
        $mail->Body    = "
            <p>Dear Customer,</p>
            <p>To confirm your booking with Innovista, please use the following One-Time Password (OTP):</p>
            <h3 style='font-size: 24px; color: #0d9488;'>OTP: <strong>{$otpCode}</strong></h3>
            <p>This OTP is valid for 5 minutes.</p>
            <p>Your booking transaction ID is: <strong>{$transactionId}</strong></p>
            <p>If you did not initiate this booking, please ignore this email.</p>
            <p>Best regards,<br>Innovista Team</p>
        ";
        $mail->AltBody = "Innovista: Your One-Time Password (OTP) for Booking\n\nOTP: {$otpCode}\n\nThis OTP is valid for 5 minutes.\nYour booking transaction ID is: {$transactionId}\n\nIf you did not initiate this booking, please ignore this email.\nBest regards,\nInnovista Team";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Booking OTP Email could not be sent. Mailer Error: {$mail->ErrorInfo}");
        return false;
    }
}

try {
    $conn->beginTransaction(); // Start transaction

    if (isset($_POST['action']) && $_POST['action'] === 'verify_otp') {
        // --- OTP Verification Logic (Simplified) ---
        $otp = $_POST['otp'] ?? '';
        $transaction_id = $_POST['transaction_id'] ?? '';
        $quotation_id = $_POST['quotation_id'] ?? '';

        if (empty($otp) || empty($transaction_id) || empty($quotation_id)) {
            sendJsonResponse(false, 'Missing OTP or transaction details.');
        }
        
        // --- Real OTP Verification ---
        // 1. Fetch the OTP associated with this transaction_id from session
        // 2. Verify it has not expired
        // 3. Compare the submitted OTP with the stored one
        $pending_otp_data = $_SESSION['pending_otp_transaction'] ?? null;
        
        if (!$pending_otp_data || $pending_otp_data['transaction_id'] !== $transaction_id || $pending_otp_data['quotation_id'] != $quotation_id) {
            sendJsonResponse(false, 'No matching pending transaction found or transaction expired. Please restart your payment.');
        }
        
        if (time() > $pending_otp_data['expires_at']) {
            unset($_SESSION['pending_otp_transaction']);
            sendJsonResponse(false, 'OTP has expired. Please resend or restart your payment.');
        }
        
        if ($otp === $pending_otp_data['otp_code']) { // Real OTP verification
            // OTP is correct, finalize booking
            $stmt_update_cq = $conn->prepare("UPDATE custom_quotations SET status = 'approved' WHERE id = :id AND customer_id = :customer_id");
            $stmt_update_cq->bindParam(':id', $quotation_id, PDO::PARAM_INT);
            $stmt_update_cq->bindParam(':customer_id', $loggedInUserId, PDO::PARAM_INT);
            $stmt_update_cq->execute();

            // Create a project entry if it doesn't already exist for this custom_quotation
            $stmt_check_project = $conn->prepare("SELECT id FROM projects WHERE quotation_id = :quotation_id");
            $stmt_check_project->bindParam(':quotation_id', $quotation_id, PDO::PARAM_INT);
            $stmt_check_project->execute();
            if (!$stmt_check_project->fetch(PDO::FETCH_ASSOC)) {
                 $stmt_cq_details = $conn->prepare("SELECT start_date, end_date FROM custom_quotations WHERE id = :quotation_id");
                 $stmt_cq_details->bindParam(':quotation_id', $quotation_id, PDO::PARAM_INT);
                 $stmt_cq_details->execute();
                 $cq_dates = $stmt_cq_details->fetch(PDO::FETCH_ASSOC);

                $stmt_create_project = $conn->prepare("INSERT INTO projects (quotation_id, status, start_date, end_date) VALUES (:quotation_id, 'in_progress', :start_date, :end_date)");
                $stmt_create_project->bindParam(':quotation_id', $quotation_id, PDO::PARAM_INT);
                $stmt_create_project->bindParam(':start_date', $cq_dates['start_date']);
                $stmt_create_project->bindParam(':end_date', $cq_dates['end_date']);
                $stmt_create_project->execute();
            }

            unset($_SESSION['pending_otp_transaction']); // Clear OTP data after successful verification
            $conn->commit();
            sendJsonResponse(true, 'OTP verified and booking confirmed successfully!');
        } else {
            $conn->rollBack();
            sendJsonResponse(false, 'Invalid OTP. Please try again.');
        }

    } elseif (isset($_POST['action']) && $_POST['action'] === 'resend_otp') {
        // --- Resend OTP Logic (Simplified) ---
        $transaction_id = $_POST['transaction_id'] ?? '';
        $quotation_id = $_POST['quotation_id'] ?? '';
        
        if (empty($transaction_id) || empty($quotation_id)) {
            sendJsonResponse(false, 'Missing transaction details to resend OTP.');
        }
        // SIMULATION: In a real system, you'd generate a new OTP, store it, and send it (e.g., via SMS API)
        sendJsonResponse(true, 'New OTP sent to your registered mobile number!');

    } else {
        // --- Initial Payment Processing & Booking Creation ---
        $customer_id = $loggedInUserId;
        $quotation_id = filter_input(INPUT_POST, 'quotation_id', FILTER_VALIDATE_INT); // This is custom_quotations.id
        $amount = filter_input(INPUT_POST, 'amount', FILTER_VALIDATE_FLOAT);
        $payment_type = filter_input(INPUT_POST, 'payment_type', FILTER_SANITIZE_FULL_SPECIAL_CHARS); // 'advance'
        $booking_date = filter_input(INPUT_POST, 'booking_date', FILTER_SANITIZE_FULL_SPECIAL_CHARS); // Proposed start_date
        $payment_method = filter_input(INPUT_POST, 'paymentMethod', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $cardNumber = filter_input(INPUT_POST, 'cardNumber', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $cardCVC = filter_input(INPUT_POST, 'cardCVC', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $cardExpiry = filter_input(INPUT_POST, 'cardExpiry', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

        // Input Validation
        if (!$quotation_id || !$amount || empty($payment_type) || empty($booking_date) || empty($payment_method) || empty($cardNumber) || empty($cardCVC) || empty($cardExpiry)) {
            sendJsonResponse(false, 'Missing required payment details.');
        }

        // --- IMPORTANT: Real Payment Gateway Integration ---
        // In a real system, you would NEVER process raw card details directly.
        // You would use a server-side payment gateway SDK (e.g., Stripe, PayPal, local payment provider)
        // This SDK would send the card details securely to the payment gateway.
        // It would return a transaction ID and confirmation.
        // For this example, we'll simulate a payment transaction.

        $simulated_transaction_id = 'TRX-' . uniqid();
        $payment_status = 'success'; // Simulate success

        if ($payment_status === 'success') {
            // Record the payment in the 'payments' table
            $stmt_payment = $conn->prepare("INSERT INTO payments (quotation_id, amount, payment_type, transaction_id, payment_date) VALUES (:quotation_id, :amount, :payment_type, :transaction_id, NOW())");
            $stmt_payment->bindParam(':quotation_id', $quotation_id, PDO::PARAM_INT);
            $stmt_payment->bindParam(':amount', $amount);
            $stmt_payment->bindParam(':payment_type', $payment_type);
            $stmt_payment->bindParam(':transaction_id', $simulated_transaction_id);
            $stmt_payment->execute();

            // Check if OTP is required (simulated)
            $requires_otp = true; // For demo, always require OTP
            // In a real system, this would come from the payment gateway response or your business logic

            if ($requires_otp) {
                // SIMULATION: Store transaction_id and OTP (e.g., in session or a temporary DB table)
                // for later verification. For simplicity here, we'll assume it's just passed to frontend.
                // In a real app, you'd trigger an SMS OTP here.
                // Generate real OTP
                $otp_code = str_pad(mt_rand(100000, 999999), 6, '0', STR_PAD_LEFT);
                
                $_SESSION['pending_otp_transaction'] = [
                    'transaction_id' => $simulated_transaction_id,
                    'quotation_id' => $quotation_id,
                    'otp_code' => $otp_code, // Real randomly generated OTP
                    'expires_at' => time() + 300 // 5 minutes
                ];
                
                // Send OTP email
                $customer_email = $_POST['customerEmail'] ?? '';
                if (!empty($customer_email) && !sendOtpEmail($customer_email, $otp_code, $simulated_transaction_id)) {
                    error_log("Failed to send booking OTP email to " . $customer_email);
                    $conn->rollBack();
                    sendJsonResponse(false, "Failed to send OTP email. Please try again.");
                }
                
                $conn->commit();
                sendJsonResponse(true, 'Payment initiated. OTP sent to your email. Please enter OTP to confirm.', ['requires_otp' => true, 'transaction_id' => $simulated_transaction_id]);
            } else {
                // If no OTP, directly finalize booking
                $stmt_update_cq = $conn->prepare("UPDATE custom_quotations SET status = 'approved' WHERE id = :id AND customer_id = :customer_id");
                $stmt_update_cq->bindParam(':id', $quotation_id, PDO::PARAM_INT);
                $stmt_update_cq->bindParam(':customer_id', $customer_id, PDO::PARAM_INT);
                $stmt_update_cq->execute();

                // Create a project entry if it doesn't already exist for this custom_quotation
                $stmt_check_project = $conn->prepare("SELECT id FROM projects WHERE quotation_id = :quotation_id");
                $stmt_check_project->bindParam(':quotation_id', $quotation_id, PDO::PARAM_INT);
                $stmt_check_project->execute();
                if (!$stmt_check_project->fetch(PDO::FETCH_ASSOC)) {
                    $stmt_cq_details = $conn->prepare("SELECT start_date, end_date FROM custom_quotations WHERE id = :quotation_id");
                    $stmt_cq_details->bindParam(':quotation_id', $quotation_id, PDO::PARAM_INT);
                    $stmt_cq_details->execute();
                    $cq_dates = $stmt_cq_details->fetch(PDO::FETCH_ASSOC);

                    $stmt_create_project = $conn->prepare("INSERT INTO projects (quotation_id, status, start_date, end_date) VALUES (:quotation_id, 'in_progress', :start_date, :end_date)");
                    $stmt_create_project->bindParam(':quotation_id', $quotation_id, PDO::PARAM_INT);
                    $stmt_create_project->bindParam(':start_date', $cq_dates['start_date']);
                    $stmt_create_project->bindParam(':end_date', $cq_dates['end_date']);
                    $stmt_create_project->execute();
                }

                $conn->commit();
                sendJsonResponse(true, 'Payment successful and booking confirmed!');
            }
        } else {
            $conn->rollBack();
            sendJsonResponse(false, 'Payment failed at gateway.');
        }
    }

} catch (PDOException $e) {
    $conn->rollBack();
    error_log("Booking Handler PDO Exception: " . $e->getMessage());
    sendJsonResponse(false, 'A system error occurred during booking. Please try again later.');
} catch (Exception $e) {
    $conn->rollBack();
    error_log("Booking Handler General Exception: " . $e->getMessage());
    sendJsonResponse(false, 'An unexpected error occurred. Please try again later.');
}