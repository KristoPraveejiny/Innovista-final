<?php
require_once '../config/session.php';
require_once '../handlers/flash_message.php';
require_once '../config/Database.php';
require_once '../notifications/NotificationManager.php';
protectPage('customer');

// Check if this is an AJAX request
$isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest';

function sendError($message, $code = 400) {
    global $isAjax;
    http_response_code($code);
    if ($isAjax) {
        echo json_encode(['success' => false, 'message' => $message]);
    } else {
        set_flash_message('error', $message);
        header('Location: ../customer/view_quote.php?id=' . ($_POST['quotation_id'] ?? ''));
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendError('Invalid request method', 405);
}

try {
    $quotation_id = filter_var($_POST['quotation_id'] ?? 0, FILTER_VALIDATE_INT);
    $action = strtolower(trim($_POST['action'] ?? ''));
    $quote_type = $_POST['quote_type'] ?? '';


    if (!$quotation_id || $quotation_id <= 0) {
        sendError('Invalid quotation ID');
    }

    if (!in_array($action, ['confirm', 'cancel', 'decline'])) {
        sendError('Invalid action');
    }

    $db = (new Database())->getConnection();

    // Check both quotations and custom_quotations tables
    $quotation = null;
    
    if ($quote_type === 'custom') {
        // Check custom_quotations table
        $stmt = $db->prepare('SELECT cq.*, u.name as provider_name FROM custom_quotations cq LEFT JOIN users u ON cq.provider_id = u.id WHERE cq.id = :id AND cq.customer_id = :customer_id');
        $stmt->execute([
            ':id' => $quotation_id,
            ':customer_id' => $_SESSION['user_id']
        ]);
        $quotation = $stmt->fetch(PDO::FETCH_ASSOC);
    } else {
        // Check regular quotations table
        $stmt = $db->prepare('SELECT * FROM quotations WHERE id = :id AND customer_id = :customer_id');
        $stmt->execute([
            ':id' => $quotation_id,
            ':customer_id' => $_SESSION['user_id']
        ]);
        $quotation = $stmt->fetch(PDO::FETCH_ASSOC);
    }

    if (!$quotation) {
        sendError('Unauthorized access or quotation not found', 403);
    }

    $db->beginTransaction();

    // Send notification first (before deletion)
    $notificationManager = new NotificationManager($db);
    if ($action === 'confirm') {
        // Update quotation status to Booked
        if ($quote_type === 'custom') {
            $stmt = $db->prepare('UPDATE custom_quotations SET status = :status WHERE id = :id');
            $stmt->execute([
                ':status' => 'approved',
                ':id' => $quotation_id
            ]);
        } else {
            $stmt = $db->prepare('UPDATE quotations SET status = :status, updated_at = NOW() WHERE id = :id');
            $stmt->execute([
                ':status' => 'Booked',
                ':id' => $quotation_id
            ]);
        }
        
        // Get service type for notification
        $serviceType = 'Custom Service';
        if (isset($quotation['service_type'])) {
            $serviceType = $quotation['service_type'];
        } elseif (isset($quotation['project_description'])) {
            $serviceType = 'Custom Project';
        }
        
        $notificationManager->notifyQuotationAccepted(
            $quotation['provider_id'],
            $_SESSION['user_id'],
            $quotation_id,
            $serviceType
        );
    } else {
        // Send notification BEFORE deleting quotation
        $reason = $_POST['reason'] ?? '';
        
        
        // Get service type for notification
        $serviceType = 'Custom Service';
        if (isset($quotation['service_type'])) {
            $serviceType = $quotation['service_type'];
        } elseif (isset($quotation['project_description'])) {
            $serviceType = 'Custom Project';
        }
        
        $notificationResult = $notificationManager->notifyQuotationRejected(
            $quotation['provider_id'],
            $_SESSION['user_id'],
            $quotation_id,
            $serviceType,
            $reason
        );
        
        
        // Delete quotation and related records when declined/cancelled
        // Delete related records first (due to foreign key constraints)
        $tables_to_clean = [
            'disputes' => 'quotation_id',
            'payments' => 'quotation_id', 
            'projects' => 'quotation_id',
            'reviews' => 'quotation_id'
        ];
        
        foreach ($tables_to_clean as $table => $column) {
            $stmt = $db->prepare("DELETE FROM {$table} WHERE {$column} = :quotation_id");
            $stmt->execute([':quotation_id' => $quotation_id]);
        }
        
        // Delete the quotation from the appropriate table
        if ($quote_type === 'custom') {
            $stmt = $db->prepare('DELETE FROM custom_quotations WHERE id = :id');
            $stmt->execute([':id' => $quotation_id]);
        } else {
            $stmt = $db->prepare('DELETE FROM quotations WHERE id = :id');
            $stmt->execute([':id' => $quotation_id]);
        }
    }

    $db->commit();

    $message = $action === 'confirm' ? 'Quotation has been accepted successfully' : 'Quotation has been declined and removed successfully';
    
    if ($isAjax) {
        echo json_encode([
            'success' => true,
            'message' => $message
        ]);
    } else {
        set_flash_message('success', $message);
        header('Location: ../customer/view_quote.php?id=' . $quotation_id);
        exit;
    }

} catch (PDOException $e) {
    if ($db->inTransaction()) $db->rollBack();
    error_log("Database error: " . $e->getMessage());
    sendError('A database error occurred', 500);
} catch (Exception $e) {
    if ($db->inTransaction()) $db->rollBack();
    error_log("Error: " . $e->getMessage());
    sendError($e->getMessage(), 500);
}
?>