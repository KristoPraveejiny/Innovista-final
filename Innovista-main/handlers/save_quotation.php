<?php
require_once '../config/session.php';
require_once '../config/Database.php';
require_once '../classes/NotificationManager.php';

// Ensure user is logged in as a provider
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'provider') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

try {
    $db = (new Database())->getConnection();
    
    // Get and validate POST data
    $quotation_id = filter_var($_POST['quotation_id'] ?? '', FILTER_VALIDATE_INT);
    $amount = filter_var($_POST['amount'] ?? '', FILTER_VALIDATE_FLOAT);
    $description = trim($_POST['description'] ?? '');
    $timeline = trim($_POST['timeline'] ?? '');

    // Validate required fields
    if (!$quotation_id || $quotation_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid quotation ID']);
        exit;
    }
    if (!$amount || $amount <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid amount']);
        exit;
    }
    if (empty($description)) {
        echo json_encode(['success' => false, 'message' => 'Description is required']);
        exit;
    }
    if (empty($timeline)) {
        echo json_encode(['success' => false, 'message' => 'Timeline is required']);
        exit;
    }

    // Verify quotation exists and belongs to provider
    $checkStmt = $db->prepare("SELECT id FROM quotations WHERE id = :id AND provider_id = :provider_id");
    $checkStmt->execute([
        ':id' => $quotation_id,
        ':provider_id' => $_SESSION['user_id']
    ]);
    if (!$checkStmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Quotation not found or unauthorized']);
        exit;
    }

    // Begin transaction
    $db->beginTransaction();

    // Update the quotation
    $stmt = $db->prepare("
        UPDATE quotations 
        SET amount = :amount, 
            provider_notes = :description, 
            estimated_timeline = :timeline,
            status = 'Quoted',
            updated_at = NOW()
        WHERE id = :quotation_id AND provider_id = :provider_id
    ");

    $stmt->execute([
        ':amount' => $amount,
        ':description' => $description,
        ':timeline' => $timeline,
        ':quotation_id' => $quotation_id,
        ':provider_id' => $_SESSION['user_id']
    ]);

    // Get quotation details for notification
    $stmt = $db->prepare("
        SELECT q.*, u.id as customer_id, q.service_type as project_title
        FROM quotations q
        JOIN users u ON q.customer_id = u.id
        WHERE q.id = :quotation_id
    ");
    $stmt->execute([':quotation_id' => $quotation_id]);
    $quotation = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($quotation) {
        // Check if any rows were updated
        if ($stmt->rowCount() === 0) {
            throw new Exception('No changes were made to the quotation');
        }

        // Send notification to customer
        $notificationManager = new NotificationManager($db);
        try {
            $success = $notificationManager->notifyCustomerQuotationSubmitted(
                $quotation['customer_id'],
                $_SESSION['user_id'],
                $quotation_id,
                $amount
            );
            if (!$success) {
                throw new Exception('Failed to send notification');
            }
        } catch (Exception $e) {
            // Log notification error but don't prevent quotation submission
            error_log("Notification error in save_quotation.php: " . $e->getMessage());
        }
    }

    // Commit transaction
    $db->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Quotation has been submitted successfully'
    ]);

} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    error_log("Error in save_quotation.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while saving the quotation'
    ]);
}
