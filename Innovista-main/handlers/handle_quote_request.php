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

    // Check for duplicate requests within the last 30 seconds (rate limiting)
    $stmt = $db->prepare('SELECT id FROM quotations WHERE customer_id = :customer_id AND provider_id = :provider_id AND project_description = :project_description AND created_at > DATE_SUB(NOW(), INTERVAL 30 SECOND)');
    $stmt->execute([
        ':customer_id' => $customer_id,
        ':provider_id' => $provider_id,
        ':project_description' => $project_description
    ]);
    if ($stmt->fetch()) {
        throw new Exception('Duplicate request detected. Please wait a moment before submitting again.');
    }

    // Begin transaction
    $db->beginTransaction();

    // Handle photo uploads
    $uploadedPhotoPaths = [];
    if (!empty($_FILES['photos']['name'][0])) {
        $uploadDir = __DIR__ . '/../uploads/quotations/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        foreach ($_FILES['photos']['tmp_name'] as $idx => $tmpName) {
            if ($_FILES['photos']['error'][$idx] === UPLOAD_ERR_OK) {
                $originalName = basename($_FILES['photos']['name'][$idx]);
                $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
                $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp'];
                if (in_array($ext, $allowed)) {
                    $newName = 'quote_' . uniqid() . '.' . $ext;
                    $dest = $uploadDir . $newName;
                    if (move_uploaded_file($tmpName, $dest)) {
                        $uploadedPhotoPaths[] = 'uploads/quotations/' . $newName;
                    }
                }
            }
        }
    }
    $photosStr = !empty($uploadedPhotoPaths) ? implode(',', $uploadedPhotoPaths) : null;

    $subcategory = isset($_POST['subcategory']) ? $_POST['subcategory'] : '';
    $status = 'Awaiting Quote';
    $stmt = $db->prepare("INSERT INTO quotations (customer_id, provider_id, service_type, subcategory, project_description, photos, status, created_at) VALUES (:customer_id, :provider_id, :service_type, :subcategory, :project_description, :photos, :status, NOW())");
    $stmt->bindParam(':customer_id', $customer_id);
    $stmt->bindParam(':provider_id', $provider_id);
    $stmt->bindParam(':service_type', $service_type);
    $stmt->bindParam(':subcategory', $subcategory);
    $stmt->bindParam(':project_description', $project_description);
    $stmt->bindParam(':photos', $photosStr);
    $stmt->bindParam(':status', $status);

    if ($stmt->execute()) {
        $quotationId = $db->lastInsertId();

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
    } else {
        $db->rollBack();
        $errorInfo = $stmt->errorInfo();
        $errorMsg = isset($errorInfo[2]) ? $errorInfo[2] : 'Unknown error.';
        if ($isAjax) {
            echo json_encode(['success' => false, 'message' => 'Could not send request. DB Error: ' . $errorMsg]);
            exit();
        } else {
            set_flash_message('error', 'There was an error submitting your request. DB Error: ' . $errorMsg);
            header('Location: ../customer/request_quotation.php');
            exit();
        }
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