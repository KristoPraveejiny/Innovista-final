<?php
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

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../public/index.php');
    exit();
}

// Ensure user is logged in
if (!isUserLoggedIn()) {
    set_flash_message('error', 'You must be logged in to make payments.');
    header('Location: ../public/login.php');
    exit();
}

$user_id = getUserId();
$project_id = filter_input(INPUT_POST, 'project_id', FILTER_VALIDATE_INT);
$custom_quotation_id = filter_input(INPUT_POST, 'custom_quotation_id', FILTER_VALIDATE_INT);
$amount = filter_input(INPUT_POST, 'amount', FILTER_VALIDATE_FLOAT);
$payment_method = filter_input(INPUT_POST, 'payment_method', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
$card_number = filter_input(INPUT_POST, 'card_number', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
$expiry_date = filter_input(INPUT_POST, 'expiry_date', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
$cvv = filter_input(INPUT_POST, 'cvv', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
$cardholder_name = filter_input(INPUT_POST, 'cardholder_name', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

// Validate required fields
if (!$project_id || !$custom_quotation_id || !$amount || !$payment_method || !$card_number || !$expiry_date || !$cvv || !$cardholder_name) {
    set_flash_message('error', 'All payment fields are required.');
    header('Location: ../customer/payment_details.php?project_id=' . $project_id);
    exit();
}

// Validate amount
if ($amount <= 0) {
    set_flash_message('error', 'Invalid payment amount.');
    header('Location: ../customer/payment_details.php?project_id=' . $project_id);
    exit();
}

$database = new Database();
$conn = $database->getConnection();

if (!$conn) {
    set_flash_message('error', 'Database connection failed.');
    header('Location: ../customer/my_projects.php');
    exit();
}

try {
    // Verify the project belongs to the logged-in customer
    $stmt_check = $conn->prepare("
        SELECT p.id, cq.amount, cq.advance, cq.provider_id, u.email as customer_email, u.name as customer_name
        FROM projects p
        JOIN custom_quotations cq ON p.quotation_id = cq.id
        JOIN users u ON cq.customer_id = u.id
        WHERE p.id = :project_id AND cq.customer_id = :user_id AND p.status = 'completed'
    ");
    $stmt_check->bindParam(':project_id', $project_id, PDO::PARAM_INT);
    $stmt_check->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt_check->execute();
    $project_data = $stmt_check->fetch(PDO::FETCH_ASSOC);

    if (!$project_data) {
        set_flash_message('error', 'Project not found or you do not have permission to make this payment.');
        header('Location: ../customer/my_projects.php');
        exit();
    }

    // Calculate remaining balance
    $remaining_balance = $project_data['amount'] - $project_data['advance'];
    
    if ($amount > $remaining_balance) {
        set_flash_message('error', 'Payment amount exceeds remaining balance.');
        header('Location: ../customer/payment_details.php?project_id=' . $project_id);
        exit();
    }

    // Generate OTP
    $otp_code = str_pad(mt_rand(100000, 999999), 6, '0', STR_PAD_LEFT);
    $transaction_id = 'TXN_' . time() . '_' . mt_rand(1000, 9999);

    // Store payment details and OTP in session
    $_SESSION['pending_final_payment'] = [
        'project_id' => $project_id,
        'custom_quotation_id' => $custom_quotation_id,
        'amount' => $amount,
        'payment_method' => $payment_method,
        'card_number' => $card_number,
        'expiry_date' => $expiry_date,
        'cvv' => $cvv,
        'cardholder_name' => $cardholder_name,
        'otp_code' => $otp_code,
        'transaction_id' => $transaction_id,
        'created_at' => time()
    ];

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
            $mail->SMTPDebug = 0; // Set to 2 for debugging

            $mail->setFrom(SMTP_USERNAME, 'Innovista Payment System');
            $mail->addAddress($email, $customer_name);

            $mail->isHTML(true);
            $mail->Subject = 'OTP for Final Payment - Innovista';
            
            $mail->Body = "
                <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                    <h2 style='color: #333;'>Payment Verification</h2>
                    <p>Dear " . htmlspecialchars($customer_name) . ",</p>
                    <p>You are attempting to make a final payment of <strong>Rs " . number_format($amount, 2) . "</strong> for your completed project.</p>
                    <p>Please use the following OTP to complete your payment:</p>
                    <div style='background-color: #f8f9fa; padding: 20px; text-align: center; margin: 20px 0; border-radius: 8px; border: 2px solid #007bff;'>
                        <h1 style='color: #007bff; font-size: 32px; margin: 0; letter-spacing: 5px;'>" . $otp . "</h1>
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
            error_log("OTP Email Error: " . $e->getMessage());
            return false;
        }
    }

    // Send OTP email
    $email_sent = sendOtpEmail(
        $project_data['customer_email'], 
        $otp_code, 
        $project_data['customer_name'], 
        $amount, 
        $transaction_id
    );

    if ($email_sent) {
        set_flash_message('success', 'OTP has been sent to your email address. Please check your inbox and enter the OTP to complete the payment.');
        header('Location: ../customer/verify_payment_otp.php?project_id=' . $project_id);
        exit();
    } else {
        unset($_SESSION['pending_final_payment']);
        set_flash_message('error', 'Failed to send OTP email. Please try again.');
        header('Location: ../customer/payment_details.php?project_id=' . $project_id);
        exit();
    }

} catch (PDOException $e) {
    error_log("Initiate Final Payment Error: " . $e->getMessage());
    set_flash_message('error', 'A database error occurred. Please try again.');
    header('Location: ../customer/payment_details.php?project_id=' . $project_id);
    exit();
} catch (Exception $e) {
    error_log("Initiate Final Payment General Error: " . $e->getMessage());
    set_flash_message('error', 'An unexpected error occurred. Please try again.');
    header('Location: ../customer/my_projects.php');
    exit();
}
?>
