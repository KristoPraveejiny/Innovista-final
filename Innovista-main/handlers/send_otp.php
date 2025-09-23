<?php
session_start();
require_once '../config/Database.php';

// Check if user is logged in
if (!function_exists('isUserLoggedIn')) {
    function isUserLoggedIn() {
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }
}

if (!isUserLoggedIn()) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $database = new Database();
        $conn = $database->getConnection();
        
        // Get user email
        $user_id = $_SESSION['user_id'];
        $stmt = $conn->prepare("SELECT email FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            throw new Exception('User not found');
        }
        
        $user_email = $user['email'];
        
        // Generate OTP
        $otp = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
        $transaction_id = 'TXN_' . time() . '_' . $user_id;
        
        // Store OTP in session using quotation system structure
        $_SESSION['pending_otp_transaction'] = [
            'transaction_id' => $transaction_id,
            'otp_code' => $otp,
            'otp_expires_at' => time() + (5 * 60), // 5 minutes
            'user_id' => $user_id,
            'user_email' => $user_email
        ];
        
        // Send OTP via email (simplified - in production use PHPMailer)
        $subject = "Payment OTP - Innovista";
        $message = "Your payment verification OTP is: " . $otp . "\n\nThis OTP is valid for 5 minutes.\n\nIf you did not request this, please ignore this email.";
        $headers = "From: noreply@innovista.com\r\n";
        $headers .= "Reply-To: noreply@innovista.com\r\n";
        $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
        
        // For testing purposes, we'll just log the OTP
        error_log("OTP for " . $user_email . ": " . $otp);
        
        // In production, uncomment this line:
        // mail($user_email, $subject, $message, $headers);
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true, 
            'message' => 'OTP sent to your email address',
            'otp' => $otp, // For testing purposes only
            'transaction_id' => $transaction_id
        ]);
        
    } catch (Exception $e) {
        error_log("OTP sending error: " . $e->getMessage());
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Failed to send OTP: ' . $e->getMessage()]);
    }
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>
