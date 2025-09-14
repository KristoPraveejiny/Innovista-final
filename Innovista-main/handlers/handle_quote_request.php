<?php
// C:\xampp1\htdocs\Innovista-final\Innovista-main\handlers\handle_quote_request.php

require_once '../public/session.php';
require_once '../handlers/flash_message.php';
require_once '../config/Database.php';

// Check if this is an AJAX request
$isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest';

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Invalid request method']);
        exit();
    }
    header('Location: ../public/index.php');
    exit();
}

// Ensure user is logged in as a customer
if (!isUserLoggedIn() || getUserRole() !== 'customer') {
    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Please log in as a customer to request a quote.']);
        exit();
    }
    set_flash_message('error', 'Please log in as a customer to request a quote.');
    header('Location: ../public/login.php');
    exit();
}

$customer_id = getUserId();
$service_type = filter_input(INPUT_POST, 'service_type', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
$project_description = filter_input(INPUT_POST, 'project_description', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
// provider_id can be NULL if customer submits a general request
$provider_id = filter_input(INPUT_POST, 'provider_id', FILTER_VALIDATE_INT);

// Input validation
if (empty($service_type) || empty($project_description)) {
    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Service type and project description are required.']);
        exit();
    }
    set_flash_message('error', 'Service type and project description are required.');
    header('Location: ../customer/request_quotation.php' . ($provider_id ? '?provider_id=' . $provider_id : ''));
    exit();
}

$database = new Database();
$conn = $database->getConnection();

try {
    $conn->beginTransaction();

    // --- Image Upload Handling ---
    $uploaded_image_paths = [];
    if (isset($_FILES['attachments']) && is_array($_FILES['attachments']['name'])) {
        $upload_dir = '../public/uploads/quotation_attachments/'; // Create this folder
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $total_files = count($_FILES['attachments']['name']);
        for ($i = 0; $i < $total_files; $i++) {
            if ($_FILES['attachments']['error'][$i] === UPLOAD_ERR_OK) {
                $file_tmp = $_FILES['attachments']['tmp_name'][$i];
                $file_ext = strtolower(pathinfo($_FILES['attachments']['name'][$i], PATHINFO_EXTENSION));
                $allowed_ext = ['jpg', 'jpeg', 'png', 'gif'];

                if (in_array($file_ext, $allowed_ext)) {
                    $new_file_name = 'quote_attach_' . $customer_id . '_' . uniqid() . '.' . $file_ext;
                    $destination_path = $upload_dir . $new_file_name;
                    $public_image_path_for_db = 'uploads/quotation_attachments/' . $new_file_name; // Path relative to public/

                    if (move_uploaded_file($file_tmp, $destination_path)) {
                        $uploaded_image_paths[] = $public_image_path_for_db;
                    } else {
                        set_flash_message('warning', 'Failed to upload one or more images.');
                        // Continue processing without the failed image
                    }
                } else {
                    set_flash_message('warning', 'One or more uploaded files had an invalid format (only JPG, PNG, GIF allowed).');
                    // Continue processing without the invalid image
                }
            }
        }
    }
    $attachments_str = !empty($uploaded_image_paths) ? implode(',', $uploaded_image_paths) : NULL;

    // --- Determine Provider and Initial Status ---
    $initial_status = 'Awaiting Quote'; // Default status if provider is specified
    $assigned_provider_id = $provider_id;

    if (!$provider_id) {
        // If no specific provider is selected, mark it for admin assignment
        $initial_status = 'Awaiting Provider Assignment';
        $assigned_provider_id = NULL; // Explicitly set to NULL if no provider chosen
        set_flash_message('info', 'Your request has been submitted for review. An administrator will assign a suitable provider soon.');
    } else {
        // Validate provider_id if it was provided
        $stmt_validate_provider = $conn->prepare("SELECT id FROM users WHERE id = :provider_id AND role = 'provider' AND provider_status = 'approved'");
        $stmt_validate_provider->bindParam(':provider_id', $provider_id, PDO::PARAM_INT);
        $stmt_validate_provider->execute();
        if (!$stmt_validate_provider->fetch(PDO::FETCH_ASSOC)) {
            $conn->rollBack();
            set_flash_message('error', 'The selected provider is invalid or not approved. Your request could not be sent to them.');
            header('Location: ../customer/request_quotation.php');
            exit();
        }
        set_flash_message('success', 'Your quote request has been sent to the selected provider!');
    }

    // Insert into 'quotations' table
    $stmt_insert_quote = $conn->prepare("
        INSERT INTO quotations (customer_id, provider_id, service_type, project_description, status, created_at, photos)
        VALUES (:customer_id, :provider_id, :service_type, :project_description, :status, NOW(), :photos)
    ");
    $stmt_insert_quote->bindParam(':customer_id', $customer_id, PDO::PARAM_INT);
    // Use $assigned_provider_id which can be NULL
    $stmt_insert_quote->bindParam(':provider_id', $assigned_provider_id, PDO::PARAM_INT);
    $stmt_insert_quote->bindParam(':service_type', $service_type);
    $stmt_insert_quote->bindParam(':project_description', $project_description);
    $stmt_insert_quote->bindParam(':status', $initial_status); // Use the determined status
    $stmt_insert_quote->bindParam(':photos', $attachments_str);
    $stmt_insert_quote->execute();

    $conn->commit();
    
    // Send notification to provider if one was assigned
    if ($provider_id) {
        require_once '../notifications/NotificationManager.php';
        $notificationManager = new NotificationManager($conn);
        
        $notificationManager->notifyNewQuotationRequest($provider_id, [
            'id' => $conn->lastInsertId(),
            'service_type' => $service_type
        ]);
    }
    
    // Return success response
    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'Quotation request submitted successfully!']);
        exit();
    }
    
    // Redirect to my projects page regardless of provider assignment
    header('Location: ../customer/my_projects.php'); 
    exit();

} catch (PDOException $e) {
    $conn->rollBack();
    error_log("Quote Request Error: " . $e->getMessage());
    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'A database error occurred while submitting your request. Please try again.']);
        exit();
    }
    set_flash_message('error', 'A database error occurred while submitting your request. Please try again.');
    header('Location: ../customer/request_quotation.php' . ($provider_id ? '?provider_id=' . $provider_id : ''));
    exit();
} catch (Exception $e) {
    $conn->rollBack();
    error_log("Quote Request General Error: " . $e->getMessage());
    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'An unexpected error occurred. Please try again.']);
        exit();
    }
    set_flash_message('error', 'An unexpected error occurred. Please try again.');
    header('Location: ../customer/request_quotation.php' . ($provider_id ? '?provider_id=' . $provider_id : ''));
    exit();
}