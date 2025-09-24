<?php
require_once '../config/session.php';
require_once '../handlers/flash_message.php';
require_once '../config/Database.php';

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../public/index.php');
    exit();
}

// Ensure user is logged in and is a provider
if (!isUserLoggedIn() || getUserRole() !== 'provider') {
    set_flash_message('error', 'You must be logged in as a provider to update project status.');
    header('Location: ../public/login.php');
    exit();
}

$user_id = getUserId();
$project_id = filter_input(INPUT_POST, 'project_id', FILTER_VALIDATE_INT);
$new_status = filter_input(INPUT_POST, 'status', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
$status_message = filter_input(INPUT_POST, 'status_message', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

if (!$project_id || !$new_status) {
    set_flash_message('error', 'Missing required project details.');
    header('Location: ../provider/my_projects.php');
    exit();
}

// Validate status
$valid_statuses = ['awaiting_advance', 'in_progress', 'awaiting_final_payment', 'completed', 'disputed'];
if (!in_array($new_status, $valid_statuses)) {
    set_flash_message('error', 'Invalid project status.');
    header('Location: ../provider/updateProgress.php?id=' . $project_id);
    exit();
}

$database = new Database();
$conn = $database->getConnection();
if (!$conn) {
    set_flash_message('error', 'Database connection failed.');
    header('Location: ../provider/my_projects.php');
    exit();
}

try {
    $conn->beginTransaction();

    // Verify the project belongs to the logged-in provider
    $stmt_check_project = $conn->prepare("
        SELECT p.id, p.status as current_status, cq.provider_id, cq.customer_id
        FROM projects p
        JOIN custom_quotations cq ON p.quotation_id = cq.id
        WHERE p.id = :project_id AND cq.provider_id = :provider_id
    ");
    $stmt_check_project->bindParam(':project_id', $project_id, PDO::PARAM_INT);
    $stmt_check_project->bindParam(':provider_id', $user_id, PDO::PARAM_INT);
    $stmt_check_project->execute();
    $project_info = $stmt_check_project->fetch(PDO::FETCH_ASSOC);

    if (!$project_info) {
        $conn->rollBack();
        set_flash_message('error', 'Project not found or you do not have permission to update it.');
        header('Location: ../provider/my_projects.php');
        exit();
    }

    // Check if status is actually changing
    if ($project_info['current_status'] === $new_status) {
        // If a status message is provided, save it as a project update even if status is unchanged
        if (!empty($status_message)) {
            $stmt_insert_update = $conn->prepare("
                INSERT INTO project_updates (project_id, user_id, update_text, created_at) 
                VALUES (:project_id, :user_id, :update_text, NOW())
            ");
            $stmt_insert_update->bindParam(':project_id', $project_id, PDO::PARAM_INT);
            $stmt_insert_update->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $stmt_insert_update->bindParam(':update_text', $status_message);
            $stmt_insert_update->execute();
                $conn->commit();
            set_flash_message('success', 'Status message added successfully!');
        } else {
            set_flash_message('info', 'Project status is already set to ' . ucfirst(str_replace('_', ' ', $new_status)) . '.');
        }
        header('Location: ../provider/updateProgress.php?id=' . $project_id);
        exit();
    }

    // Update the project status
    $stmt_update_status = $conn->prepare("
        UPDATE projects 
        SET status = :new_status 
        WHERE id = :project_id
    ");
    $stmt_update_status->bindParam(':new_status', $new_status);
    $stmt_update_status->bindParam(':project_id', $project_id, PDO::PARAM_INT);
    $stmt_update_status->execute();

    // If there's a status message, add it as a project update
    if (!empty($status_message)) {
        $stmt_insert_update = $conn->prepare("
            INSERT INTO project_updates (project_id, user_id, update_text, created_at) 
            VALUES (:project_id, :user_id, :update_text, NOW())
        ");
        $stmt_insert_update->bindParam(':project_id', $project_id, PDO::PARAM_INT);
        $stmt_insert_update->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt_insert_update->bindParam(':update_text', $status_message);
        $stmt_insert_update->execute();
    }

    // Add automatic status update message
    $auto_message = "Project status updated to: " . ucfirst(str_replace('_', ' ', $new_status));
    $stmt_auto_update = $conn->prepare("
        INSERT INTO project_updates (project_id, user_id, update_text, created_at) 
        VALUES (:project_id, :user_id, :update_text, NOW())
    ");
    $stmt_auto_update->bindParam(':project_id', $project_id, PDO::PARAM_INT);
    $stmt_auto_update->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt_auto_update->bindParam(':update_text', $auto_message);
    $stmt_auto_update->execute();

    // Create notification for customer
    $notification_title = "Project Status Updated";
    $notification_message = "Your project status has been updated to: " . ucfirst(str_replace('_', ' ', $new_status));
    
    $stmt_notification = $conn->prepare("
        INSERT INTO notifications (user_id, title, message, type, priority, action_url, related_id, related_type, created_at) 
        VALUES (:user_id, :title, :message, 'project', 'medium', :action_url, :related_id, 'project', NOW())
    ");
    $action_url = '../customer/track_project.php?id=' . $project_info['customer_id'];
    $stmt_notification->bindParam(':user_id', $project_info['customer_id'], PDO::PARAM_INT);
    $stmt_notification->bindParam(':title', $notification_title);
    $stmt_notification->bindParam(':message', $notification_message);
    $stmt_notification->bindParam(':action_url', $action_url);
    $stmt_notification->bindParam(':related_id', $project_id, PDO::PARAM_INT);
    $stmt_notification->execute();

    $conn->commit();
    set_flash_message('success', 'Project status updated successfully!');
    header('Location: ../provider/updateProgress.php?id=' . $project_id);
    exit();

} catch (PDOException $e) {
    $conn->rollBack();
    error_log("Project Status Update Error: " . $e->getMessage());
    set_flash_message('error', 'A database error occurred while updating project status. Please try again.');
    header('Location: ../provider/updateProgress.php?id=' . $project_id);
    exit();
} catch (Exception $e) {
    $conn->rollBack();
    error_log("Project Status Update General Error: " . $e->getMessage());
    set_flash_message('error', 'An unexpected error occurred. Please try again.');
    header('Location: ../provider/updateProgress.php?id=' . $project_id);
    exit();
}
