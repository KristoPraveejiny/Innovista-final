<?php
// C:\xampp1\htdocs\Innovista-final\Innovista-main\customer\view_project_history.php

require_once '../public/session.php';
require_once '../handlers/flash_message.php';

// --- User-specific authentication function ---
if (!function_exists('protectPage')) {
    function protectPage(string $requiredRole): void {
        if (!isUserLoggedIn()) {
            header("Location: ../public/login.php");
            exit();
        }
        if (getUserRole() !== $requiredRole && getUserRole() !== 'admin') { 
            set_flash_message('error', 'Access denied. You do not have permission to view this page.');
            header("Location: ../public/index.php");
            exit();
        }
    }
}
protectPage('customer');

$pageTitle = 'Project History';
require_once '../includes/user_dashboard_header.php';
require_once '../config/Database.php';

$db = (new Database())->getConnection();
$loggedInUserId = getUserId();

$custom_quotation_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$custom_quotation_id) {
    set_flash_message('error', 'Invalid Project ID provided for history view.');
    header('Location: my_projects.php');
    exit();
}

$project_data = null;
$review_status = 'not_left'; // 'not_left', 'left', 'error'

// Fetch completed project details
try {
    $stmt_project = $db->prepare("
        SELECT 
            p.id AS project_id, p.status AS project_status, p.start_date, p.end_date,
            cq.id AS custom_quotation_id, cq.project_description, cq.amount AS quoted_amount, cq.advance,
            prov.id AS provider_id, prov.name AS provider_name, prov.email AS provider_email,
            cust.id AS customer_id, cust.name AS customer_name
        FROM projects p
        JOIN custom_quotations cq ON p.quotation_id = cq.id
        JOIN users prov ON cq.provider_id = prov.id
        JOIN users cust ON cq.customer_id = cust.id
        WHERE p.quotation_id = :custom_quotation_id AND cq.customer_id = :customer_id AND p.status = 'completed'
    ");
    $stmt_project->bindParam(':custom_quotation_id', $custom_quotation_id, PDO::PARAM_INT);
    $stmt_project->bindParam(':customer_id', $loggedInUserId, PDO::PARAM_INT);
    $stmt_project->execute();
    $project_data = $stmt_project->fetch(PDO::FETCH_ASSOC);

    if (!$project_data) {
        set_flash_message('error', 'Completed project not found or you do not have permission to view it.');
        header('Location: my_projects.php');
        exit();
    }

    // Check if customer has already left a review for this specific provider/project
    $stmt_check_review = $db->prepare("
        SELECT id FROM reviews 
        WHERE customer_id = :customer_id AND provider_id = :provider_id AND quotation_id = :quotation_id
        LIMIT 1
    ");
    $stmt_check_review->bindParam(':customer_id', $loggedInUserId, PDO::PARAM_INT);
    $stmt_check_review->bindParam(':provider_id', $project_data['provider_id'], PDO::PARAM_INT);
    $stmt_check_review->bindParam(':quotation_id', $custom_quotation_id, PDO::PARAM_INT);
    $stmt_check_review->execute();
    if ($stmt_check_review->fetch(PDO::FETCH_ASSOC)) {
        $review_status = 'left';
    }

} catch (PDOException $e) {
    error_log("View Project History page error: " . $e->getMessage());
    set_flash_message('error', 'Error loading project history. Please try again.');
    header('Location: my_projects.php');
    exit();
}
?>

<?php display_flash_message(); ?>

<h2>Project History: <?php echo htmlspecialchars($project_data['project_description']); ?></h2>
<p>Detailed overview of your completed project.</p>

<div class="project-details-grid">
    <div class="content-card" style="grid-column: 1 / -1;"> <!-- Full width card for overall summary -->
        <h3>Project Summary</h3>
        <div class="form-grid">
            <div class="detail-item"><strong>Provider:</strong> <a href="../public/provider_profile.php?id=<?php echo htmlspecialchars($project_data['provider_id']); ?>"><?php echo htmlspecialchars($project_data['provider_name']); ?></a></div>
            <div class="detail-item"><strong>Status:</strong> <span class="status-badge status-approved"><?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $project_data['project_status']))); ?></span></div>
            <div class="detail-item"><strong>Quoted Cost:</strong> Rs <?php echo number_format($project_data['quoted_amount'], 2); ?></div>
            <div class="detail-item"><strong>Advance Paid:</strong> Rs <?php echo number_format($project_data['advance'], 2); ?></div>
            <div class="detail-item"><strong>Project Period:</strong> <?php echo date('d M Y', strtotime($project_data['start_date'])); ?> to <?php echo date('d M Y', strtotime($project_data['end_date'])); ?></div>
            <div class="detail-item"><strong>Final Payment:</strong> 
                <?php
                    // Fetch final payment for this custom_quotation_id
                    $stmt_final_payment = $db->prepare("SELECT amount FROM payments WHERE quotation_id = :cq_id AND payment_type = 'final'");
                    $stmt_final_payment->bindParam(':cq_id', $custom_quotation_id, PDO::PARAM_INT);
                    $stmt_final_payment->execute();
                    $final_payment_amount = $stmt_final_payment->fetchColumn();

                    if ($final_payment_amount !== false && $final_payment_amount !== null) {
                        echo 'Rs ' . number_format($final_payment_amount, 2);
                    } else {
                        echo 'N/A';
                    }
                ?>
            </div>
        </div>
    </div>
    
    <!-- Link to full project timeline for completed projects -->
    <div class="content-card" style="grid-column: 1 / -1; display: flex; justify-content: center; gap: 1rem;">
        <a href="track_project.php?id=<?php echo htmlspecialchars($custom_quotation_id); ?>" class="btn-submit" style="background-color: var(--status-verified);">View Full Project Timeline</a>
        
        <?php if ($review_status === 'not_left'): ?>
            <a href="leave_review.php?provider_id=<?php echo htmlspecialchars($project_data['provider_id']); ?>&custom_quotation_id=<?php echo htmlspecialchars($custom_quotation_id); ?>" class="btn-submit" style="background-color: var(--status-pending);">Leave a Review</a>
        <?php else: ?>
            <p class="text-info" style="align-self: center;">You have already left a review for this provider.</p>
        <?php endif; ?>
    </div>

</div>

<div class="action-buttons mt-4">
    <a href="my_projects.php" class="btn-link">Back to My Projects</a>
</div>

<?php require_once '../includes/user_dashboard_footer.php'; ?>

<!-- Add some styling if not present in dashboard.css -->
<style>
/* For view_project_history.php */
.project-details-grid {
    display: grid;
    grid-template-columns: 1fr; /* Default to single column */
    gap: 2rem;
}
.project-details-grid .content-card .form-grid {
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
    padding: 1rem 0;
}
.project-details-grid .detail-item {
    font-size: 1rem;
    color: var(--text-dark);
}
.project-details-grid .detail-item strong {
    color: var(--text-dark);
}
.project-details-grid .detail-item a {
    color: var(--primary-color);
    text-decoration: none;
    font-weight: 500;
}
.project-details-grid .detail-item a:hover {
    text-decoration: underline;
}
</style>