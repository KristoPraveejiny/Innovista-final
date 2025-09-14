<?php
require_once '../config/session.php';
require_once '../config/Database.php';
require_once '../notifications/NotificationManager.php';
protectPage('provider');

// Check if this is an AJAX request
$isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest';

if ($isAjax) {
    header('Content-Type: application/json');
}

function sendError($message, $code = 400) {
    global $isAjax;
    http_response_code($code);
    if ($isAjax) {
        echo json_encode(['success' => false, 'message' => $message]);
    } else {
        // For regular form submission, redirect with error
        header('Location: ../provider/provider_dashboard.php?error=' . urlencode($message));
    }
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

        // Send notification to customer about new quotation
        try {
            $notificationManager = new NotificationManager($db);
            
            $notificationManager->notifyNewQuotationReceived($quotation['customer_id'], [
                'id' => $quotation_id,
                'amount' => $amount,
                'service_type' => $quotation['service_type'] ?? 'Service',
                'provider_name' => $_SESSION['user_name'] ?? 'Provider'
            ]);
        } catch (Exception $e) {
            // Log error but don't stop the process
            error_log("Failed to send notification to customer: " . $e->getMessage());
        }

        // Commit the transaction
        $db->commit();

        if ($isAjax) {
            echo json_encode([
                'success' => true,
                'message' => 'Quotation has been submitted successfully',
                'quotation_id' => $quotation_id
            ]);
        } else {
            // For regular form submission, redirect to dashboard with success message
            header('Location: ../provider/provider_dashboard.php?success=Quotation+submitted+successfully');
            exit;
        }

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
    sendError('Failed to save quotation: ' . $e->getMessage());
} catch (PDOException $e) {
    error_log("Database error in save_quotation.php: " . $e->getMessage());
    sendError('An error occurred: ' . $e->getMessage());
}
?>