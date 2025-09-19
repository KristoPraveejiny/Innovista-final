<?php
// C:\xampp1\htdocs\Innovista-final\Innovista-main\handlers\handle_project_communication.php

require_once '../public/session.php';
require_once '../handlers/flash_message.php';
require_once '../config/Database.php';

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../public/index.php'); // Redirect to home if not POST
    exit();
}

// Ensure user is logged in
if (!isUserLoggedIn()) {
    set_flash_message('error', 'You must be logged in to send messages.');
    header('Location: ../public/login.php');
    exit();
}

$user_id = getUserId();
$project_id = filter_input(INPUT_POST, 'project_id', FILTER_VALIDATE_INT);
$message_text = filter_input(INPUT_POST, 'message', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

if (!$project_id || empty($message_text)) {
    set_flash_message('error', 'Missing project ID or message content.');
    header('Location: ../customer/track_project.php?id=' . ($project_id ?? '')); // Redirect back to project page
    exit();
}

$database = new Database();
$conn = $database->getConnection();
if (!$conn) {
    set_flash_message('error', 'Database connection failed.');
    header('Location: ../public/index.php');
    exit();
}

try {
    $conn->beginTransaction();

    // Verify the project belongs to the logged-in user (customer, provider, or admin)
    // Join with custom_quotations to ensure ownership
    $stmt_check_project = $conn->prepare("
        SELECT p.id, cq.customer_id, cq.provider_id
        FROM projects p
        JOIN custom_quotations cq ON p.quotation_id = cq.id
        WHERE p.id = :project_id AND (cq.customer_id = :user_id OR cq.provider_id = :user_id OR :user_role = 'admin')
    ");
    $stmt_check_project->bindParam(':project_id', $project_id, PDO::PARAM_INT);
    $stmt_check_project->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $user_role = getUserRole();
    $stmt_check_project->bindParam(':user_role', $user_role);
    $stmt_check_project->execute();
    $project_info = $stmt_check_project->fetch(PDO::FETCH_ASSOC);

    if (!$project_info) {
        $conn->rollBack();
        set_flash_message('error', 'Project not found or you do not have permission to send messages.');
        // Redirect based on user role
        if (getUserRole() === 'provider') {
            header('Location: ../provider/my_projects.php');
        } else {
            header('Location: ../customer/my_projects.php');
        }
        exit();
    }

    // Insert the message as a project update
    $stmt_insert_update = $conn->prepare("INSERT INTO project_updates (project_id, user_id, update_text, created_at) VALUES (:project_id, :user_id, :update_text, NOW())");
    $stmt_insert_update->bindParam(':project_id', $project_id, PDO::PARAM_INT);
    $stmt_insert_update->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt_insert_update->bindParam(':update_text', $message_text);
    $stmt_insert_update->execute();

    // Get sender name for notification
    $stmt_sender = $conn->prepare("SELECT name FROM users WHERE id = :user_id");
    $stmt_sender->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt_sender->execute();
    $sender_data = $stmt_sender->fetch(PDO::FETCH_ASSOC);
    $sender_name = $sender_data['name'] ?? 'Unknown User';

    // Create notification for the other party
    $notification_title = "New Project Message";
    $notification_message = "You have received a new message from " . $sender_name . " regarding your project.";
    
    // Determine who to notify (if provider sent, notify customer; if customer sent, notify provider)
    $notify_user_id = ($project_info['customer_id'] == $user_id) ? $project_info['provider_id'] : $project_info['customer_id'];
    
    $stmt_notification = $conn->prepare("
        INSERT INTO notifications (user_id, title, message, type, priority, action_url, related_id, related_type, created_at) 
        VALUES (:user_id, :title, :message, 'project', 'medium', :action_url, :related_id, 'project', NOW())
    ");
    
    // Get custom_quotation_id for proper customer notification URL
    $stmt_cq_notification = $conn->prepare("SELECT cq.id as custom_quotation_id FROM custom_quotations cq JOIN projects p ON cq.id = p.quotation_id WHERE p.id = :project_id");
    $stmt_cq_notification->bindParam(':project_id', $project_id, PDO::PARAM_INT);
    $stmt_cq_notification->execute();
    $cq_notification_data = $stmt_cq_notification->fetch(PDO::FETCH_ASSOC);
    $custom_quotation_id_for_notification = $cq_notification_data['custom_quotation_id'] ?? $project_id;
    
    $action_url = (getUserRole() === 'provider') ? 
        '../customer/track_project.php?id=' . $custom_quotation_id_for_notification : 
        '../provider/updateProgress.php?id=' . $project_id;
    
    $stmt_notification->bindParam(':user_id', $notify_user_id, PDO::PARAM_INT);
    $stmt_notification->bindParam(':title', $notification_title);
    $stmt_notification->bindParam(':message', $notification_message);
    $stmt_notification->bindParam(':action_url', $action_url);
    $stmt_notification->bindParam(':related_id', $project_id, PDO::PARAM_INT);
    $stmt_notification->execute();

    $conn->commit();
    set_flash_message('success', 'Message sent successfully!');
    
    // Redirect based on user role
    if (getUserRole() === 'provider') {
        header('Location: ../provider/updateProgress.php?id=' . $project_id);
    } else {
        // For customer, we need to get the custom_quotation_id to redirect properly
        $stmt_cq = $conn->prepare("SELECT cq.id as custom_quotation_id FROM custom_quotations cq JOIN projects p ON cq.id = p.quotation_id WHERE p.id = :project_id");
        $stmt_cq->bindParam(':project_id', $project_id, PDO::PARAM_INT);
        $stmt_cq->execute();
        $cq_data = $stmt_cq->fetch(PDO::FETCH_ASSOC);
        $custom_quotation_id = $cq_data['custom_quotation_id'] ?? $project_id;
        header('Location: ../customer/track_project.php?id=' . $custom_quotation_id);
    }
    exit();

} catch (PDOException $e) {
    $conn->rollBack();
    error_log("Project Communication Error: " . $e->getMessage());
    set_flash_message('error', 'A database error occurred while sending message. Please try again.');
    // Redirect based on user role
    if (getUserRole() === 'provider') {
        header('Location: ../provider/my_projects.php');
    } else {
        header('Location: ../customer/my_projects.php');
    }
    exit();
} catch (Exception $e) {
    $conn->rollBack();
    error_log("Project Communication General Error: " . $e->getMessage());
    set_flash_message('error', 'An unexpected error occurred. Please try again.');
    // Redirect based on user role
    if (getUserRole() === 'provider') {
        header('Location: ../provider/my_projects.php');
    } else {
        header('Location: ../customer/my_projects.php');
    }
    exit();
}