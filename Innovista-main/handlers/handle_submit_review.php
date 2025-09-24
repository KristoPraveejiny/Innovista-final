<?php
// C:\xampp1\htdocs\Innovista-final\Innovista-main\handlers\handle_submit_review.php

require_once '../public/session.php';
require_once '../handlers/flash_message.php';
require_once '../config/Database.php';

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../public/index.php');
    exit();
}

// Ensure user is logged in as a customer
if (!isUserLoggedIn() || getUserRole() !== 'customer') {
    set_flash_message('error', 'Please log in as a customer to submit a review.');
    header('Location: ../public/login.php');
    exit();
}

$customer_id = getUserId();
$provider_id = filter_input(INPUT_POST, 'provider_id', FILTER_VALIDATE_INT);
$custom_quotation_id = filter_input(INPUT_POST, 'custom_quotation_id', FILTER_VALIDATE_INT);
$rating = filter_input(INPUT_POST, 'rating', FILTER_VALIDATE_INT);
$review_text = filter_input(INPUT_POST, 'review_text', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

// Input Validation
if (!$provider_id || !$custom_quotation_id || !$rating || empty($review_text)) {
    set_flash_message('error', 'All fields (Provider, Project, Rating, Review Text) are required.');
    header('Location: ../customer/leave_review.php?provider_id=' . ($provider_id ?? '') . '&custom_quotation_id=' . ($custom_quotation_id ?? ''));
    exit();
}
if ($rating < 1 || $rating > 5) {
    set_flash_message('error', 'Rating must be between 1 and 5 stars.');
    header('Location: ../customer/leave_review.php?provider_id=' . ($provider_id ?? '') . '&custom_quotation_id=' . ($custom_quotation_id ?? ''));
    exit();
}

$database = new Database();
$conn = $database->getConnection();

try {
    $conn->beginTransaction();

    // 1. Verify that this customer completed the project with this provider
    //    And that the project status is 'completed'
    $stmt_verify_project = $conn->prepare("
        SELECT p.id FROM projects p
        JOIN custom_quotations cq ON p.quotation_id = cq.id
        WHERE cq.id = :custom_quotation_id AND cq.customer_id = :customer_id AND cq.provider_id = :provider_id AND p.status = 'completed'
        LIMIT 1
    ");
    $stmt_verify_project->bindParam(':custom_quotation_id', $custom_quotation_id, PDO::PARAM_INT);
    $stmt_verify_project->bindParam(':customer_id', $customer_id, PDO::PARAM_INT);
    $stmt_verify_project->bindParam(':provider_id', $provider_id, PDO::PARAM_INT);
    $stmt_verify_project->execute();
    $project_exists = $stmt_verify_project->fetch(PDO::FETCH_ASSOC);

    if (!$project_exists) {
        $conn->rollBack();
        set_flash_message('error', 'You can only review providers for projects you have completed.');
        header('Location: ../customer/my_projects.php');
        exit();
    }

    // 2. Check if a review already exists for this customer-provider-project combination
    $stmt_check_review = $conn->prepare("SELECT id FROM reviews WHERE customer_id = :customer_id AND provider_id = :provider_id AND quotation_id = :quotation_id LIMIT 1");
    $stmt_check_review->bindParam(':customer_id', $customer_id, PDO::PARAM_INT);
    $stmt_check_review->bindParam(':provider_id', $provider_id, PDO::PARAM_INT);
    $stmt_check_review->bindParam(':quotation_id', $custom_quotation_id, PDO::PARAM_INT);
    $stmt_check_review->execute();
    if ($stmt_check_review->fetch(PDO::FETCH_ASSOC)) {
        $conn->rollBack();
        set_flash_message('info', 'You have already submitted a review for this provider on this project.');
        header('Location: ../customer/view_project_history.php?id=' . htmlspecialchars($custom_quotation_id));
        exit();
    }

    // 3. Insert the new review (match table: quotation_id, comment)
    $stmt_insert_review = $conn->prepare("
        INSERT INTO reviews (quotation_id, customer_id, provider_id, rating, comment, created_at)
        VALUES (:quotation_id, :customer_id, :provider_id, :rating, :comment, NOW())
    ");
    $stmt_insert_review->bindParam(':quotation_id', $custom_quotation_id, PDO::PARAM_INT);
    $stmt_insert_review->bindParam(':customer_id', $customer_id, PDO::PARAM_INT);
    $stmt_insert_review->bindParam(':provider_id', $provider_id, PDO::PARAM_INT);
    $stmt_insert_review->bindParam(':rating', $rating, PDO::PARAM_INT);
    $stmt_insert_review->bindParam(':comment', $review_text);
    $stmt_insert_review->execute();

    $conn->commit();
    set_flash_message('success', 'Thank you for your review! It has been submitted.');
    header('Location: ../customer/view_project_history.php?id=' . htmlspecialchars($custom_quotation_id));
    exit();

} catch (PDOException $e) {
    $conn->rollBack();
    error_log("Submit Review PDO Exception: " . $e->getMessage());
    set_flash_message('error', 'A database error occurred while submitting your review. Please try again.');
    header('Location: ../customer/leave_review.php?provider_id=' . ($provider_id ?? '') . '&custom_quotation_id=' . ($custom_quotation_id ?? ''));
    exit();
} catch (Exception $e) {
    $conn->rollBack();
    error_log("Submit Review General Exception: " . $e->getMessage());
    set_flash_message('error', 'An unexpected error occurred. Please try again later.');
    header('Location: ../customer/leave_review.php?provider_id=' . ($provider_id ?? '') . '&custom_quotation_id=' . ($custom_quotation_id ?? ''));
    exit();
}