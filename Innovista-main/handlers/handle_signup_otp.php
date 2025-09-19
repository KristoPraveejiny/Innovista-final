<?php
// OTP Verification Handler for Signup
require_once '../public/session.php';
require_once '../handlers/flash_message.php';
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

header('Content-Type: application/json');

// Check if user is already logged in
if (isUserLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'You are already logged in.']);
    exit();
}

$database = new Database();
$conn = $database->getConnection();

// Helper to send JSON response and exit
function sendJsonResponse(bool $success, string $message, array $data = []): void {
    echo json_encode(array_merge(['success' => $success, 'message' => $message], $data));
    exit();
}

// Helper to send OTP email
function sendSignupOtpEmail(string $recipientEmail, string $otpCode, string $userType): bool {
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
        $mail->Subject = 'Innovista: Verify Your Email Address';
        $mail->Body    = "
            <p>Dear User,</p>
            <p>Welcome to Innovista! To complete your " . ucfirst($userType) . " account registration, please use the following One-Time Password (OTP):</p>
            <h3 style='font-size: 24px; color: #0d9488;'>OTP: <strong>{$otpCode}</strong></h3>
            <p>This OTP is valid for 10 minutes.</p>
            <p>If you did not create an account with Innovista, please ignore this email.</p>
            <p>Best regards,<br>Innovista Team</p>
        ";
        $mail->AltBody = "Innovista: Verify Your Email Address\n\nOTP: {$otpCode}\n\nThis OTP is valid for 10 minutes.\n\nIf you did not create an account with Innovista, please ignore this email.\nBest regards,\nInnovista Team";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Signup OTP Email could not be sent. Mailer Error: {$mail->ErrorInfo}");
        return false;
    }
}

try {
    $conn->beginTransaction();

    if (isset($_POST['action']) && $_POST['action'] === 'send_otp') {
        // --- Send OTP Logic ---
        $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
        $userType = filter_input(INPUT_POST, 'userType', FILTER_SANITIZE_STRING);
        
        if (!$email || !$userType) {
            $conn->rollBack();
            sendJsonResponse(false, 'Invalid email or user type.');
        }

        // Check if email already exists
        $stmt_check = $conn->prepare("SELECT id FROM users WHERE email = :email");
        $stmt_check->bindParam(':email', $email);
        $stmt_check->execute();
        
        if ($stmt_check->fetch()) {
            $conn->rollBack();
            sendJsonResponse(false, 'An account with this email already exists.');
        }

        // Generate 6-digit OTP
        $otp_code = str_pad(random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
        $expires_at = date('Y-m-d H:i:s', time() + 600); // 10 minutes from now

        // Store OTP in session for verification
        $_SESSION['signup_otp'] = [
            'email' => $email,
            'otp_code' => $otp_code,
            'user_type' => $userType,
            'expires_at' => time() + 600,
            'created_at' => time()
        ];

        // Send OTP email
        if (sendSignupOtpEmail($email, $otp_code, $userType)) {
            $conn->commit();
            sendJsonResponse(true, 'OTP sent to your email address. Please check your inbox.');
        } else {
            $conn->rollBack();
            sendJsonResponse(false, 'Failed to send OTP. Please try again.');
        }

    } elseif (isset($_POST['action']) && $_POST['action'] === 'verify_otp') {
        // --- Verify OTP Logic ---
        $otp = filter_input(INPUT_POST, 'otp', FILTER_SANITIZE_STRING);
        $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
        
        if (!$otp || !$email) {
            $conn->rollBack();
            sendJsonResponse(false, 'Invalid OTP or email.');
        }

        // Check if OTP session exists
        if (!isset($_SESSION['signup_otp'])) {
            $conn->rollBack();
            sendJsonResponse(false, 'No OTP session found. Please request a new OTP.');
        }

        $otp_data = $_SESSION['signup_otp'];

        // Check if email matches
        if ($otp_data['email'] !== $email) {
            $conn->rollBack();
            sendJsonResponse(false, 'Email mismatch. Please try again.');
        }

        // Check if OTP has expired
        if (time() > $otp_data['expires_at']) {
            unset($_SESSION['signup_otp']);
            $conn->rollBack();
            sendJsonResponse(false, 'OTP has expired. Please request a new one.');
        }

        // Verify OTP
        if ($otp_data['otp_code'] === $otp) {
            // Mark OTP as verified
            $_SESSION['signup_otp']['verified'] = true;
            $conn->commit();
            sendJsonResponse(true, 'Email verified successfully! You can now complete your registration.');
        } else {
            $conn->rollBack();
            sendJsonResponse(false, 'Invalid OTP. Please try again.');
        }

    } elseif (isset($_POST['action']) && $_POST['action'] === 'resend_otp') {
        // --- Resend OTP Logic ---
        $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
        $userType = filter_input(INPUT_POST, 'userType', FILTER_SANITIZE_STRING);
        
        if (!$email || !$userType) {
            $conn->rollBack();
            sendJsonResponse(false, 'Invalid email or user type.');
        }

        // Check if email already exists
        $stmt_check = $conn->prepare("SELECT id FROM users WHERE email = :email");
        $stmt_check->bindParam(':email', $email);
        $stmt_check->execute();
        
        if ($stmt_check->fetch()) {
            $conn->rollBack();
            sendJsonResponse(false, 'An account with this email already exists.');
        }

        // Generate new OTP
        $otp_code = str_pad(random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
        $expires_at = date('Y-m-d H:i:s', time() + 600); // 10 minutes from now

        // Update OTP in session
        $_SESSION['signup_otp'] = [
            'email' => $email,
            'otp_code' => $otp_code,
            'user_type' => $userType,
            'expires_at' => time() + 600,
            'created_at' => time()
        ];

        // Send OTP email
        if (sendSignupOtpEmail($email, $otp_code, $userType)) {
            $conn->commit();
            sendJsonResponse(true, 'New OTP sent to your email address.');
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
    error_log("Signup OTP PDO Exception: " . $e->getMessage());
    sendJsonResponse(false, 'A system error occurred. Please try again later.');
} catch (Exception $e) {
    $conn->rollBack();
    error_log("Signup OTP General Exception: " . $e->getMessage());
    sendJsonResponse(false, 'An unexpected error occurred. Please try again later.');
}
?>
