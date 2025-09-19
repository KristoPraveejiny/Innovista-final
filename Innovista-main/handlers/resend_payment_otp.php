<?php
require_once '../public/session.php';
require_once '../config/Database.php';

// --- PHPMailer Autoload ---
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require_once '../vendor/phpmailer/phpmailer/src/Exception.php';
require_once '../vendor/phpmailer/phpmailer/src/PHPMailer.php';
require_once '../vendor/phpmailer/phpmailer/src/SMTP.php';

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

// Ensure user is logged in
if (!isUserLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit();
}

$user_id = getUserId();
$project_id = filter_input(INPUT_POST, 'project_id', FILTER_VALIDATE_INT);

if (!$project_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid project ID']);
    exit();
}

// Check if there's a pending payment
if (!isset($_SESSION['pending_final_payment'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'No pending payment found']);
    exit();
}

$pending_payment = $_SESSION['pending_final_payment'];

// Verify project ID matches
if ($pending_payment['project_id'] != $project_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid payment session']);
    exit();
}

// Check if resend is allowed (at least 1 minute since last send)
$time_since_last_send = time() - $pending_payment['created_at'];
if ($time_since_last_send < 60) {
    http_response_code(429);
    echo json_encode(['success' => false, 'message' => 'Please wait before requesting another OTP']);
    exit();
}

$database = new Database();
$conn = $database->getConnection();

if (!$conn) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

try {
    // Get customer email and name
    $stmt = $conn->prepare("
        SELECT u.email as customer_email, u.name as customer_name
        FROM projects p
        JOIN custom_quotations cq ON p.quotation_id = cq.id
        JOIN users u ON cq.customer_id = u.id
        WHERE p.id = :project_id AND cq.customer_id = :user_id
    ");
    $stmt->bindParam(':project_id', $project_id, PDO::PARAM_INT);
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    $customer_data = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$customer_data) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Customer not found']);
        exit();
    }

    // Generate new OTP
    $new_otp = str_pad(mt_rand(100000, 999999), 6, '0', STR_PAD_LEFT);
    
    // Update session with new OTP and timestamp
    $_SESSION['pending_final_payment']['otp_code'] = $new_otp;
    $_SESSION['pending_final_payment']['created_at'] = time();

    // Send OTP email

    // --- SMTP Configuration ---
    define('SMTP_HOST', 'smtp.gmail.com');
    define('SMTP_USERNAME', 'jathushan006@gmail.com');
    define('SMTP_PASSWORD', 'qhaqwgaovdnvjzkm');
    define('SMTP_PORT', 587);
    define('SMTP_ENCRYPTION', 'tls');

    function sendOtpEmail($email, $otp, $customer_name, $amount, $transaction_id) {
        $mail = new PHPMailer(true);
        
        try {
            $mail->isSMTP();
            $mail->Host = SMTP_HOST;
            $mail->SMTPAuth = true;
            $mail->Username = SMTP_USERNAME;
            $mail->Password = SMTP_PASSWORD;
            $mail->SMTPSecure = SMTP_ENCRYPTION;
            $mail->Port = SMTP_PORT;
            $mail->SMTPDebug = 0;

            $mail->setFrom(SMTP_USERNAME, 'Innovista Payment System');
            $mail->addAddress($email, $customer_name);

            $mail->isHTML(true);
            $mail->Subject = 'New OTP for Final Payment - Innovista';
            
            $mail->Body = "
                <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                    <h2 style='color: #333;'>New Payment Verification Code</h2>
                    <p>Dear " . htmlspecialchars($customer_name) . ",</p>
                    <p>You requested a new OTP for your final payment of <strong>Rs " . number_format($amount, 2) . "</strong>.</p>
                    <p>Please use the following OTP to complete your payment:</p>
                    <div style='background-color: #f8f9fa; padding: 20px; text-align: center; margin: 20px 0; border-radius: 8px; border: 2px solid #007bff;'>
                        <h1 style='color: #007bff; font-size: 32px; margin: 0; letter-spacing: 5px;'>" . $new_otp . "</h1>
                    </div>
                    <p><strong>Transaction ID:</strong> " . $transaction_id . "</p>
                    <p><strong>Important:</strong></p>
                    <ul>
                        <li>This OTP is valid for 10 minutes only</li>
                        <li>Do not share this OTP with anyone</li>
                        <li>If you did not request this payment, please ignore this email</li>
                    </ul>
                    <p>Best regards,<br>Innovista Team</p>
                </div>
            ";

            $mail->send();
            return true;
        } catch (Exception $e) {
            error_log("Resend OTP Email Error: " . $e->getMessage());
            return false;
        }
    }

    // Send new OTP email
    $email_sent = sendOtpEmail(
        $customer_data['customer_email'], 
        $new_otp, 
        $customer_data['customer_name'], 
        $pending_payment['amount'], 
        $pending_payment['transaction_id']
    );

    if ($email_sent) {
        echo json_encode(['success' => true, 'message' => 'New OTP has been sent to your email']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to send OTP email']);
    }

} catch (PDOException $e) {
    error_log("Resend Payment OTP Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
} catch (Exception $e) {
    error_log("Resend Payment OTP General Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'An unexpected error occurred']);
}
?>
