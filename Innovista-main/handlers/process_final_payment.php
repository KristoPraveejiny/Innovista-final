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
        set_flash_message('error', 'Project not found or you do not have permission to make this payment.');
        header('Location: ../customer/my_projects.php');
        exit();
    }

    // Calculate remaining balance
    $remaining_balance = $project_data['amount'] - $project_data['advance'];
    
    if ($amount > $remaining_balance) {
        $conn->rollBack();
        set_flash_message('error', 'Payment amount exceeds remaining balance.');
        header('Location: ../customer/payment_details.php?project_id=' . $project_id);
        exit();
    }

    // Generate transaction ID
    $transaction_id = 'TXN_' . time() . '_' . mt_rand(1000, 9999);

    // Simulate payment processing (in real implementation, integrate with payment gateway)
    $payment_status = 'success'; // Simulated successful payment

    if ($payment_status === 'success') {
        // Update the advance amount in custom_quotations
        $new_advance = $project_data['advance'] + $amount;
        $stmt_update_advance = $conn->prepare("
            UPDATE custom_quotations 
            SET advance = :new_advance 
            WHERE id = :custom_quotation_id
        ");
        $stmt_update_advance->bindParam(':new_advance', $new_advance);
        $stmt_update_advance->bindParam(':custom_quotation_id', $custom_quotation_id, PDO::PARAM_INT);
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
        $stmt_payment->bindParam(':quotation_id', $custom_quotation_id, PDO::PARAM_INT);
        $stmt_payment->bindParam(':amount', $amount);
        $stmt_payment->bindParam(':transaction_id', $transaction_id);
        $stmt_payment->execute();

        // Create project update for payment
        $payment_message = "Final payment of Rs " . number_format($amount, 2) . " has been made via " . ucfirst(str_replace('_', ' ', $payment_method)) . ". Transaction ID: " . $transaction_id;
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
        $notification_message = "You have received the final payment of Rs " . number_format($amount, 2) . " for project completion.";
        $action_url = "../provider/updateProgress.php?id=" . $project_id;
        
        $stmt_notification->bindParam(':user_id', $project_data['provider_id'], PDO::PARAM_INT);
        $stmt_notification->bindParam(':title', $notification_title);
        $stmt_notification->bindParam(':message', $notification_message);
        $stmt_notification->bindParam(':action_url', $action_url);
        $stmt_notification->bindParam(':related_id', $project_id, PDO::PARAM_INT);
        $stmt_notification->execute();

        $conn->commit();
        set_flash_message('success', 'Payment of Rs ' . number_format($amount, 2) . ' has been processed successfully!');
        header('Location: ../customer/my_projects.php');
        exit();

    } else {
        $conn->rollBack();
        set_flash_message('error', 'Payment processing failed. Please try again.');
        header('Location: ../customer/payment_details.php?project_id=' . $project_id);
        exit();
    }

} catch (PDOException $e) {
    $conn->rollBack();
    error_log("Final Payment Error: " . $e->getMessage());
    set_flash_message('error', 'A database error occurred while processing payment. Please try again.');
    header('Location: ../customer/payment_details.php?project_id=' . $project_id);
    exit();
} catch (Exception $e) {
    $conn->rollBack();
    error_log("Final Payment General Error: " . $e->getMessage());
    set_flash_message('error', 'An unexpected error occurred. Please try again.');
    header('Location: ../customer/my_projects.php');
    exit();
}
?>
