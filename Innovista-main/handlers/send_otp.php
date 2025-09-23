<?php
// OTP Handler for Payment Verification
require_once '../public/session.php';
require_once '../config/Database.php';

// --- PHPMailer Autoload ---
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require_once '../vendor/phpmailer/phpmailer/src/Exception.php';
require_once '../vendor/phpmailer/phpmailer/src/PHPMailer.php';
require_once '../vendor/phpmailer/phpmailer/src/SMTP.php';

// --- SMTP Configuration (Same as booking and signup) ---
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_USERNAME', 'jathushan006@gmail.com');
define('SMTP_PASSWORD', 'qhaqwgaovdnvjzkm');
define('SMTP_PORT', 587);
define('SMTP_ENCRYPTION', 'tls');
define('SENDER_NAME', 'Innovista Support');

header('Content-Type: application/json');

// Check if user is logged in
if (!isUserLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'You must be logged in to send OTP.']);
    exit();
}

$database = new Database();
$conn = $database->getConnection();

// Helper to send JSON response and exit
function sendJsonResponse(bool $success, string $message, array $data = []): void {
    echo json_encode(array_merge(['success' => $success, 'message' => $message], $data));
    exit();
}

// Helper to send OTP email (Same as booking form)
function sendPaymentOtpEmail(string $recipientEmail, string $otpCode, string $transactionId, string $userName = ''): bool {
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
        $mail->Subject = 'Innovista: Your Payment Verification OTP';
        $mail->Body    = "
            <p>Dear " . ($userName ? htmlspecialchars($userName) : 'Customer') . ",</p>
            <p>To complete your payment with Innovista, please use the following One-Time Password (OTP):</p>
            <h3 style='font-size: 24px; color: #0d9488;'>OTP: <strong>{$otpCode}</strong></h3>
            <p>This OTP is valid for 5 minutes.</p>
            <p>Your payment transaction ID is: <strong>{$transactionId}</strong></p>
            <p>If you did not initiate this payment, please ignore this email.</p>
            <p>Best regards,<br>Innovista Team</p>
        ";
        $mail->AltBody = "Innovista: Your Payment Verification OTP\n\nOTP: {$otpCode}\n\nThis OTP is valid for 5 minutes.\nYour payment transaction ID is: {$transactionId}\n\nIf you did not initiate this payment, please ignore this email.\nBest regards,\nInnovista Team";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Payment OTP Email could not be sent. Mailer Error: {$mail->ErrorInfo}");
        return false;
    }
}

try {
    $conn->beginTransaction();

    if (isset($_POST['action']) && $_POST['action'] === 'send_otp') {
        // --- Send OTP Logic ---
        $user_id = getUserId();
        
        // Get user information
        $stmt = $conn->prepare("SELECT email, name FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            $conn->rollBack();
            sendJsonResponse(false, 'User not found.');
        }
        
        // Get email from form data if provided, otherwise use user's registered email
        $target_email = $user['email']; // Default to user's registered email
        $user_name = $user['name'] ?? '';
        
        // Check if email is provided in POST data (from checkout form)
        if (isset($_POST['email']) && !empty($_POST['email'])) {
            $target_email = filter_var($_POST['email'], FILTER_VALIDATE_EMAIL);
            if (!$target_email) {
                $conn->rollBack();
                sendJsonResponse(false, 'Invalid email address provided.');
            }
        }
        
        // Generate 6-digit OTP
        $otp_code = str_pad(random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
        $transaction_id = 'PAY_' . time() . '_' . $user_id;
        
        // Store OTP in session (Same structure as booking)
        $_SESSION['pending_otp_transaction'] = [
            'transaction_id' => $transaction_id,
            'otp_code' => $otp_code,
            'otp_expires_at' => time() + (5 * 60), // 5 minutes
            'user_id' => $user_id,
            'user_email' => $target_email,
            'user_name' => $user_name
        ];
        
        // Send OTP email
        if (sendPaymentOtpEmail($target_email, $otp_code, $transaction_id, $user_name)) {
            $conn->commit();
            sendJsonResponse(true, 'OTP sent to ' . $target_email, [
                'transaction_id' => $transaction_id,
                'email_sent_to' => $target_email
            ]);
        } else {
            $conn->rollBack();
            sendJsonResponse(false, 'Failed to send OTP. Please try again.');
        }

    } elseif (isset($_POST['action']) && $_POST['action'] === 'resend_otp') {
        // --- Resend OTP Logic ---
        $user_id = getUserId();
        
        // Get user information
        $stmt = $conn->prepare("SELECT email, name FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            $conn->rollBack();
            sendJsonResponse(false, 'User not found.');
        }
        
        // Get email from form data if provided, otherwise use user's registered email
        $target_email = $user['email'];
        $user_name = $user['name'] ?? '';
        
        // Check if email is provided in POST data
        if (isset($_POST['email']) && !empty($_POST['email'])) {
            $target_email = filter_var($_POST['email'], FILTER_VALIDATE_EMAIL);
            if (!$target_email) {
                $conn->rollBack();
                sendJsonResponse(false, 'Invalid email address provided.');
            }
        }
        
        // Generate new OTP
        $otp_code = str_pad(random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
        $transaction_id = 'PAY_' . time() . '_' . $user_id;
        
        // Update OTP in session
        $_SESSION['pending_otp_transaction'] = [
            'transaction_id' => $transaction_id,
            'otp_code' => $otp_code,
            'otp_expires_at' => time() + (5 * 60), // 5 minutes
            'user_id' => $user_id,
            'user_email' => $target_email,
            'user_name' => $user_name
        ];
        
        // Send OTP email
        if (sendPaymentOtpEmail($target_email, $otp_code, $transaction_id, $user_name)) {
            $conn->commit();
            sendJsonResponse(true, 'New OTP sent to ' . $target_email, [
                'transaction_id' => $transaction_id,
                'email_sent_to' => $target_email
            ]);
        } else {
            $conn->rollBack();
            sendJsonResponse(false, 'Failed to send OTP. Please try again.');
        }

    } else {
        $conn->rollBack();
        sendJsonResponse(false, 'Invalid action.');
    }

} catch (PDOException $e) {
    $conn->rollBack();
    error_log("Payment OTP PDO Exception: " . $e->getMessage());
    sendJsonResponse(false, 'A system error occurred. Please try again later.');
} catch (Exception $e) {
    $conn->rollBack();
    error_log("Payment OTP General Exception: " . $e->getMessage());
    sendJsonResponse(false, 'An unexpected error occurred. Please try again later.');
}
?>
