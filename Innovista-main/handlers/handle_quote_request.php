<?php
require_once '../public/session.php';
require_once '../handlers/flash_message.php';
require_once '../config/Database.php';
require_once '../notifications/NotificationManager.php';

if (session_status() === PHP_SESSION_NONE) { 
    session_start(); 
}

$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

// Check login & role
if (!isset($_SESSION['user_id'], $_SESSION['user_role']) || $_SESSION['user_role'] !== 'customer') {
    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'You must be logged in as a customer.']);
        exit;
    } else {
        header('Location: ../public/login.php');
        exit;
    }
}

// Method check
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    if ($isAjax) {
        echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
        exit;
    } else {
        header('Location: ../customer/request_quotation.php');
        exit;
    }
}

// Validate required fields
if (empty($_POST['service_type']) || empty($_POST['project_description']) || empty($_POST['provider_id'])) {
    $msg = 'Service type, project description, and provider are required.';
    if ($isAjax) {
        echo json_encode(['success' => false, 'message' => $msg]);
        exit;
    } else {
        set_flash_message('error', $msg);
        header('Location: ../customer/request_quotation.php');
        exit;
    }
}

$customer_id = $_SESSION['user_id'];
$provider_id = $_POST['provider_id'];
$service_type = $_POST['service_type'];
$project_description = $_POST['project_description'];

try {
    $db = (new Database())->getConnection();

    // Verify provider exists
    $stmt = $db->prepare('SELECT id FROM users WHERE id = :id AND role = "provider"');
    $stmt->execute([':id' => $provider_id]);
    if (!$stmt->fetch()) {
        throw new Exception('Selected provider is invalid.');
    }

    // Begin transaction
    $db->beginTransaction();

    // Insert quotation
    $stmt = $db->prepare("INSERT INTO quotations 
        (customer_id, provider_id, service_type, project_description, status, created_at) 
        VALUES (:customer_id, :provider_id, :service_type, :project_description, :status, NOW())");

    $stmt->execute([
        ':customer_id' => $customer_id,
        ':provider_id' => $provider_id,
        ':service_type' => $service_type,
        ':project_description' => $project_description,
        ':status' => 'Awaiting Quote'
    ]);

    $quotationId = $db->lastInsertId();

    // Handle file uploads
    if (!empty($_FILES['attachments']['name'][0])) {
        $uploadDir = '../uploads/quotations/' . $quotationId . '/';
        if (!file_exists($uploadDir)) mkdir($uploadDir, 0777, true);

        foreach ($_FILES['attachments']['tmp_name'] as $key => $tmp_name) {
            if ($_FILES['attachments']['error'][$key] === UPLOAD_ERR_OK) {
                $filename = basename($_FILES['attachments']['name'][$key]);
                $destination = $uploadDir . $filename;
                if (move_uploaded_file($tmp_name, $destination)) {
                    $fileStmt = $db->prepare("INSERT INTO quotation_attachments 
                        (quotation_id, file_path, uploaded_at) VALUES (:quotation_id, :file_path, NOW())");
                    $fileStmt->execute([
                        ':quotation_id' => $quotationId,
                        ':file_path' => 'uploads/quotations/' . $quotationId . '/' . $filename
                    ]);
                }
            }
        }
    }

    // Send notification to the selected provider
    $notificationManager = new NotificationManager($db);
    $notificationManager->notifyNewQuotationRequest($provider_id, [
        'id' => $quotationId,
        'service_type' => $service_type,
        'customer_name' => $_SESSION['user_name'] ?? 'Customer'
    ]);

    $db->commit();

    $response = [
        'success' => true,
        'message' => 'Quotation request sent successfully.',
        'quotation_id' => $quotationId
    ];
    
    if ($isAjax) {
        echo json_encode($response);
        exit;
    } else {
        set_flash_message('success', $response['message']);
        header('Location: ../customer/customer_dashboard.php');
        exit;
    }

} catch (Exception $e) {
    if ($db->inTransaction()) $db->rollBack();
    error_log('Quotation Request Error: ' . $e->getMessage());

    $msg = 'Failed to create quotation request. ' . $e->getMessage();
    if ($isAjax) {
        echo json_encode(['success' => false, 'message' => $msg]);
        exit;
    } else {
        set_flash_message('error', $msg);
        header('Location: ../customer/request_quotation.php');
        exit;
    }
}
?>