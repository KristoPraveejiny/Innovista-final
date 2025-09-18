<?php
require_once '../public/session.php';
require_once '../handlers/flash_message.php';
require_once '../config/Database.php';

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
$transaction_id = filter_input(INPUT_POST, 'transaction_id', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
$otp_entered = filter_input(INPUT_POST, 'otp', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

// Validate inputs
if (!$project_id || !$transaction_id || !$otp_entered) {
    set_flash_message('error', 'All fields are required.');
    header('Location: ../customer/verify_payment_otp.php?project_id=' . $project_id);
    exit();
}

// Check if there's a pending payment
if (!isset($_SESSION['pending_final_payment'])) {
    set_flash_message('error', 'No pending payment found. Please start the payment process again.');
    header('Location: ../customer/my_projects.php');
    exit();
}

$pending_payment = $_SESSION['pending_final_payment'];

// Verify project ID and transaction ID match
if ($pending_payment['project_id'] != $project_id || $pending_payment['transaction_id'] != $transaction_id) {
    unset($_SESSION['pending_final_payment']);
    set_flash_message('error', 'Invalid payment session. Please start the payment process again.');
    header('Location: ../customer/my_projects.php');
    exit();
}

// Check if OTP has expired (10 minutes)
$otp_age = time() - $pending_payment['created_at'];
if ($otp_age > 600) { // 10 minutes
    unset($_SESSION['pending_final_payment']);
    set_flash_message('error', 'OTP has expired. Please start the payment process again.');
    header('Location: ../customer/payment_details.php?project_id=' . $project_id);
    exit();
}

// Verify OTP
if ($otp_entered !== $pending_payment['otp_code']) {
    set_flash_message('error', 'Invalid OTP. Please check and try again.');
    header('Location: ../customer/verify_payment_otp.php?project_id=' . $project_id);
    exit();
}

// OTP is correct, process the payment
$database = new Database();
$conn = $database->getConnection();

if (!$conn) {
    unset($_SESSION['pending_final_payment']);
    set_flash_message('error', 'Database connection failed.');
    header('Location: ../customer/my_projects.php');
    exit();
}

try {
    $conn->beginTransaction();

    // Verify the project belongs to the logged-in customer
    $stmt_check = $conn->prepare("
        SELECT p.id, cq.amount, cq.advance, cq.provider_id
        FROM projects p
        JOIN custom_quotations cq ON p.quotation_id = cq.id
        WHERE p.id = :project_id AND cq.customer_id = :user_id AND p.status = 'completed'
    ");
    $stmt_check->bindParam(':project_id', $project_id, PDO::PARAM_INT);
    $stmt_check->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt_check->execute();
    $project_data = $stmt_check->fetch(PDO::FETCH_ASSOC);

    if (!$project_data) {
        $conn->rollBack();
        unset($_SESSION['pending_final_payment']);
        set_flash_message('error', 'Project not found or you do not have permission to make this payment.');
        header('Location: ../customer/my_projects.php');
        exit();
    }

    // Calculate remaining balance
    $remaining_balance = $project_data['amount'] - $project_data['advance'];
    
    if ($pending_payment['amount'] > $remaining_balance) {
        $conn->rollBack();
        unset($_SESSION['pending_final_payment']);
        set_flash_message('error', 'Payment amount exceeds remaining balance.');
        header('Location: ../customer/payment_details.php?project_id=' . $project_id);
        exit();
    }

    // Update the advance amount in custom_quotations
    $new_advance = $project_data['advance'] + $pending_payment['amount'];
    $stmt_update_advance = $conn->prepare("
        UPDATE custom_quotations 
        SET advance = :new_advance 
        WHERE id = :custom_quotation_id
    ");
    $stmt_update_advance->bindParam(':new_advance', $new_advance);
    $stmt_update_advance->bindParam(':custom_quotation_id', $pending_payment['custom_quotation_id'], PDO::PARAM_INT);
    $stmt_update_advance->execute();

    // Insert payment record
    $stmt_payment = $conn->prepare("
        INSERT INTO payments (
            quotation_id, 
            amount, 
            payment_type, 
            transaction_id
        ) VALUES (
            :quotation_id, 
            :amount, 
            'final', 
            :transaction_id
        )
    ");
    $stmt_payment->bindParam(':quotation_id', $pending_payment['custom_quotation_id'], PDO::PARAM_INT);
    $stmt_payment->bindParam(':amount', $pending_payment['amount']);
    $stmt_payment->bindParam(':transaction_id', $pending_payment['transaction_id']);
    $stmt_payment->execute();

    // Create project update for payment
    $payment_message = "Final payment of Rs " . number_format($pending_payment['amount'], 2) . " has been made via " . ucfirst(str_replace('_', ' ', $pending_payment['payment_method'])) . ". Transaction ID: " . $pending_payment['transaction_id'];
    $stmt_update = $conn->prepare("
        INSERT INTO project_updates (project_id, user_id, update_text, created_at) 
        VALUES (:project_id, :user_id, :update_text, NOW())
    ");
    $stmt_update->bindParam(':project_id', $project_id, PDO::PARAM_INT);
    $stmt_update->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt_update->bindParam(':update_text', $payment_message);
    $stmt_update->execute();

    // Create notification for provider
    $stmt_notification = $conn->prepare("
        INSERT INTO notifications (user_id, title, message, type, priority, action_url, related_id, related_type, created_at) 
        VALUES (:user_id, :title, :message, 'project', 'high', :action_url, :related_id, 'project', NOW())
    ");
    $notification_title = "Final Payment Received";
    $notification_message = "You have received the final payment of Rs " . number_format($pending_payment['amount'], 2) . " for project completion.";
    $action_url = "../provider/updateProgress.php?id=" . $project_id;
    
    $stmt_notification->bindParam(':user_id', $project_data['provider_id'], PDO::PARAM_INT);
    $stmt_notification->bindParam(':title', $notification_title);
    $stmt_notification->bindParam(':message', $notification_message);
    $stmt_notification->bindParam(':action_url', $action_url);
    $stmt_notification->bindParam(':related_id', $project_id, PDO::PARAM_INT);
    $stmt_notification->execute();

    $conn->commit();
    
    // Clear the pending payment session
    unset($_SESSION['pending_final_payment']);
    
    set_flash_message('success', 'Payment of Rs ' . number_format($pending_payment['amount'], 2) . ' has been processed successfully!');
    header('Location: ../customer/my_projects.php');
    exit();

} catch (PDOException $e) {
    $conn->rollBack();
    unset($_SESSION['pending_final_payment']);
    error_log("Verify Payment OTP Error: " . $e->getMessage());
    set_flash_message('error', 'A database error occurred while processing payment. Please try again.');
    header('Location: ../customer/my_projects.php');
    exit();
} catch (Exception $e) {
    $conn->rollBack();
    unset($_SESSION['pending_final_payment']);
    error_log("Verify Payment OTP General Error: " . $e->getMessage());
    set_flash_message('error', 'An unexpected error occurred. Please try again.');
    header('Location: ../customer/my_projects.php');
    exit();
}
?>
