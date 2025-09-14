<?php
require_once '../config/session.php';
require_once '../config/Database.php';
require_once '../classes/NotificationManager.php';
protectPage('provider');

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
    $db = (new Database())->getConnection();
    
    // Validate input
    $quotation_id = intval($_POST['quotation_id'] ?? 0);
    $amount = floatval($_POST['amount'] ?? 0);
    $advance = floatval($_POST['advance'] ?? 0);
    $start_date = $_POST['start_date'] ?? '';
    $end_date = $_POST['end_date'] ?? '';
    $validity = intval($_POST['validity'] ?? 0);
    $provider_notes = $_POST['provider_notes'] ?? '';

    if (!$quotation_id || !$amount) {
        throw new Exception('Missing required fields');
    }

    // Validate dates
    if (!empty($start_date) && !strtotime($start_date)) {
        throw new Exception('Invalid start date format');
    }
    if (!empty($end_date) && !strtotime($end_date)) {
        throw new Exception('Invalid end date format');
    }
    if (!empty($start_date) && !empty($end_date) && strtotime($end_date) <= strtotime($start_date)) {
        throw new Exception('End date must be after start date');
    }

    // Fetch quotation details
    $stmt = $db->prepare('SELECT provider_id, customer_id, project_description, service_type FROM quotations WHERE id = :id');
    $stmt->bindParam(':id', $quotation_id);
    $stmt->execute();
    $quotation = $stmt->fetch(PDO::FETCH_ASSOC);

<<<<<<< HEAD
    // Send notification to customer about new quotation
    try {
        require_once '../notifications/NotificationManager.php';
        $notificationManager = new NotificationManager($db);
        
        // Get quotation details for notification
        $stmt = $db->prepare('SELECT service_type, project_description FROM quotations WHERE id = ?');
        $stmt->execute([$quotation_id]);
        $quotationDetails = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $notificationManager->notifyNewQuotationReceived($customer_id, [
            'id' => $quotation_id,
            'amount' => $amount,
            'service_type' => $quotationDetails['service_type'] ?? 'Service',
            'provider_name' => $_SESSION['user_name'] ?? 'Provider'
        ]);
    } catch (Exception $e) {
        // Log error but don't stop the process
        error_log("Failed to send notification to customer: " . $e->getMessage());
    }

    // Redirect to customer dashboard with a success message
    header('Location: ../customer/customer_dashboard.php?quote_sent=1&id=' . $quotation_id);
    exit();
} else {
    echo '<div style="color:red;">Invalid request.</div>';
}
=======
    if (!$quotation) {
        throw new Exception('Quotation not found');
    }

    // Verify this provider owns this quotation
    if ($quotation['provider_id'] != $_SESSION['user_id']) {
        throw new Exception('Unauthorized access to this quotation');
    }

    // Start transaction
    $db->beginTransaction();

    try {
        // Insert custom quotation
        $stmt = $db->prepare('INSERT INTO custom_quotations (
            quotation_id, provider_id, customer_id, amount, advance, 
            start_date, end_date, validity, provider_notes, status, project_description
        ) VALUES (
            :quotation_id, :provider_id, :customer_id, :amount, :advance, 
            :start_date, :end_date, :validity, :provider_notes, "sent", :project_description
        )');
        
        $stmt->execute([
            ':quotation_id' => $quotation_id,
            ':provider_id' => $quotation['provider_id'],
            ':customer_id' => $quotation['customer_id'],
            ':amount' => $amount,
            ':advance' => $advance,
            ':start_date' => $start_date,
            ':end_date' => $end_date,
            ':validity' => $validity,
            ':provider_notes' => $provider_notes,
            ':project_description' => $quotation['project_description']
        ]);

        // Update the status of the original quotation
        $updateStmt = $db->prepare('UPDATE quotations SET status = "Quoted" WHERE id = :id');
        $updateStmt->execute([':id' => $quotation_id]);

        // Send notification to customer
        $notificationManager = new NotificationManager($db);
        if (!$notificationManager->notifyCustomerQuotationSubmitted(
            $quotation['customer_id'],
            $_SESSION['user_id'],
            $quotation_id,
            $amount
        )) {
            throw new Exception('Failed to send notification to customer');
        }

        // Commit the transaction
        $db->commit();

        echo json_encode([
            'success' => true,
            'message' => 'Quotation has been submitted successfully',
            'quotation_id' => $quotation_id
        ]);

    } catch (Exception $e) {
        // Rollback the transaction if anything failed
        $db->rollBack();
        sendError($e->getMessage());
    } catch (PDOException $e) {
        // Rollback the transaction if anything failed
        $db->rollBack();
        error_log("Database error in save_quotation.php: " . $e->getMessage());
        sendError('A database error occurred while processing your request');
    }
} catch (Exception $e) {
    error_log("Error in save_quotation.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Failed to save quotation: ' . $e->getMessage()
    ]);
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred: ' . $e->getMessage()
    ]);
} 
>>>>>>> aa57165eda4ae6bb88be077dc8252796dcb05bd9
