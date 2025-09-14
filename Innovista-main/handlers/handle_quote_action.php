<?php
// C:\xampp1\htdocs\Innovista-final\Innovista-main\handlers\handle_quote_action.php

require_once '../public/session.php';
require_once '../handlers/flash_message.php';
require_once '../config/Database.php';
<<<<<<< HEAD

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../public/index.php'); // Redirect to home if not POST
    exit();
=======
require_once '../classes/NotificationManager.php';
protectPage('customer');

header('Content-Type: application/json');

function sendError($message, $code = 400) {
    http_response_code($code);
    echo json_encode(['success' => false, 'message' => $message]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendError('Invalid request method', 405);
}

try {
    $quotation_id = filter_var($_POST['quotation_id'] ?? 0, FILTER_VALIDATE_INT);
    $action = strtolower(trim($_POST['action'] ?? ''));

    if (!$quotation_id || $quotation_id <= 0) {
        sendError('Invalid quotation ID');
    }

    if (!in_array($action, ['confirm', 'cancel'])) {
        sendError('Invalid action');
    }

    $db = (new Database())->getConnection();

    // Verify quotation belongs to current customer
    $stmt = $db->prepare('SELECT * FROM quotations WHERE id = :id AND customer_id = :customer_id');
    $stmt->execute([
        ':id' => $quotation_id,
        ':customer_id' => $_SESSION['user_id']
    ]);
    $quotation = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$quotation) {
        sendError('Unauthorized access or quotation not found', 403);
    }

    $db->beginTransaction();

    // Update quotation status
    $stmt = $db->prepare('UPDATE quotations SET status = :status, updated_at = NOW() WHERE id = :id');
    $stmt->execute([
        ':status' => $action === 'confirm' ? 'Booked' : 'Cancelled',
        ':id' => $quotation_id
    ]);

    // Send notification
    $notificationManager = new NotificationManager($db);
    if ($action === 'confirm') {
        $notificationManager->notifyQuotationAccepted(
            $quotation['provider_id'],
            $_SESSION['user_id'],
            $quotation_id,
            $quotation['service_type']
        );
    } else {
        $reason = $_POST['reason'] ?? '';
        $notificationManager->notifyQuotationRejected(
            $quotation['provider_id'],
            $_SESSION['user_id'],
            $quotation_id,
            $quotation['service_type'],
            $reason
        );
    }

    $db->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Quotation has been ' . ($action === 'confirm' ? 'accepted' : 'rejected') . ' successfully'
    ]);

} catch (PDOException $e) {
    if ($db->inTransaction()) $db->rollBack();
    error_log("Database error: " . $e->getMessage());
    sendError('A database error occurred', 500);
} catch (Exception $e) {
    if ($db->inTransaction()) $db->rollBack();
    error_log("Error: " . $e->getMessage());
    sendError($e->getMessage(), 500);
>>>>>>> aa57165eda4ae6bb88be077dc8252796dcb05bd9
}

// Ensure user is logged in as a customer
if (!isUserLoggedIn() || getUserRole() !== 'customer') {
    set_flash_message('error', 'Please log in as a customer to perform this action.');
    header('Location: ../public/login.php');
    exit();
}

$customer_id = getUserId();
$quote_id = filter_input(INPUT_POST, 'quote_id', FILTER_VALIDATE_INT);
$action = filter_input(INPUT_POST, 'action', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
$quote_type = filter_input(INPUT_POST, 'quote_type', FILTER_SANITIZE_FULL_SPECIAL_CHARS); // Should be 'custom' here

if (!$quote_id || empty($action) || $quote_type !== 'custom') {
    set_flash_message('error', 'Invalid request for quote action.');
    header('Location: ../customer/my_projects.php');
    exit();
}

$database = new Database();
$conn = $database->getConnection();

try {
    $conn->beginTransaction();

    // Verify the custom quotation belongs to this customer
    $stmt_check = $conn->prepare("SELECT status FROM custom_quotations WHERE id = :id AND customer_id = :customer_id");
    $stmt_check->bindParam(':id', $quote_id, PDO::PARAM_INT);
    $stmt_check->bindParam(':customer_id', $customer_id, PDO::PARAM_INT);
    $stmt_check->execute();
    $current_status = $stmt_check->fetchColumn();

    if (!$current_status) {
        $conn->rollBack();
        set_flash_message('error', 'Quotation not found or you do not have permission.');
        header('Location: ../customer/my_projects.php');
        exit();
    }

    // Only allow action if quote is in 'sent' or 'pending' status
    if ($current_status !== 'sent' && $current_status !== 'pending') {
        $conn->rollBack();
        set_flash_message('info', 'This quotation cannot be ' . $action . 'd at this time. Its status is ' . htmlspecialchars($current_status) . '.');
        header('Location: ../customer/view_quote.php?id=' . $quote_id . '&type=custom');
        exit();
    }

    if ($action === 'decline') {
        $stmt_update_quote = $conn->prepare("UPDATE custom_quotations SET status = 'declined' WHERE id = :id");
        $stmt_update_quote->bindParam(':id', $quote_id, PDO::PARAM_INT);
        $stmt_update_quote->execute();

        set_flash_message('success', 'Quotation successfully declined.');
        $conn->commit();
        header('Location: ../customer/my_projects.php');
        exit();

    } /* The 'accept' action is handled by handle_booking.php after payment */
    else {
        $conn->rollBack();
        set_flash_message('error', 'Invalid action specified.');
        header('Location: ../customer/view_quote.php?id=' . $quote_id . '&type=custom');
        exit();
    }

} catch (PDOException $e) {
    $conn->rollBack();
    error_log("Quote Action Error: " . $e->getMessage());
    set_flash_message('error', 'A database error occurred. Please try again.');
    header('Location: ../customer/view_quote.php?id=' . $quote_id . '&type=custom');
    exit();
}