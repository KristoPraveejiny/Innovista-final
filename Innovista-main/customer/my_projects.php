<?php
// C:\xampp1\htdocs\Innovista-final\Innovista-main\customer\my_projects.php

require_once '../public/session.php'; 
require_once '../handlers/flash_message.php'; // For display_flash_message

// --- User-specific authentication function ---
// This function should be defined in public/session.php or a dedicated auth.php
if (!function_exists('protectPage')) {
    function protectPage(string $requiredRole): void {
        if (!isUserLoggedIn()) {
            header("Location: ../public/login.php");
            exit();
        }
        // Allow admin to view customer/provider dashboards for management purposes
        if (getUserRole() !== $requiredRole && getUserRole() !== 'admin') { 
            set_flash_message('error', 'Access denied. You do not have permission to view this page.');
            header("Location: ../public/index.php"); // Redirect to homepage or appropriate dashboard
            exit();
        }
    }
}
protectPage('customer'); 

$pageTitle = 'My Projects & Quotations';
require_once '../includes/user_dashboard_header.php'; 
require_once '../config/Database.php';  

$customer_id = getUserId(); // Get the logged-in customer's ID

$database = new Database();
$conn = $database->getConnection(); // Get the PDO connection object

// --- Fetching Active Projects ---
// Active projects are those in_progress, awaiting_final_payment, or disputed
$active_projects_query = $conn->prepare("
    SELECT 
        prov.name as provider_name, 
        cq.project_description, 
        p.status, 
        p.id as project_id, 
        cq.id as custom_quotation_id,
        COALESCE(notification_counts.unread_count, 0) as unread_messages
    FROM projects p
    JOIN custom_quotations cq ON p.quotation_id = cq.id
    JOIN users prov ON cq.provider_id = prov.id
    LEFT JOIN (
        SELECT 
            n.related_id as project_id,
            COUNT(*) as unread_count
        FROM notifications n
        WHERE n.user_id = :id 
        AND n.type = 'project' 
        AND n.is_read = 0
        GROUP BY n.related_id
    ) notification_counts ON p.id = notification_counts.project_id
    WHERE cq.customer_id = :id AND p.status IN ('in_progress', 'awaiting_final_payment', 'disputed')
    ORDER BY p.id DESC
");
$active_projects_query->bindParam(':id', $customer_id, PDO::PARAM_INT);
$active_projects_query->execute();
$active_projects = $active_projects_query->fetchAll(PDO::FETCH_ASSOC);

// --- Fetching Pending Quotations ---
// This includes both:
// 1. Original requests from customer awaiting a custom quote from provider (q.status = 'Awaiting Quote')
// 2. Custom quotes sent by provider, awaiting customer's decision (cq.status = 'sent' or 'pending')
$pending_quotes_query = $conn->prepare("
    -- Original requests awaiting a custom quote from provider
    SELECT 'original' as quote_type, q.id as entity_id, prov.name as provider_name, q.project_description, q.status, q.created_at
    FROM quotations q
    JOIN users prov ON q.provider_id = prov.id
    WHERE q.customer_id = :id AND q.status = 'Awaiting Quote'
    UNION ALL
    -- Custom quotes sent by provider, awaiting customer's decision
    SELECT 'custom' as quote_type, cq.id as entity_id, prov.name as provider_name, cq.project_description, cq.status, cq.created_at
    FROM custom_quotations cq
    JOIN users prov ON cq.provider_id = prov.id
    WHERE cq.customer_id = :id AND cq.status IN ('sent', 'pending') -- 'pending' might mean awaiting customer approval for advance
    ORDER BY created_at DESC
");
$pending_quotes_query->bindParam(':id', $customer_id, PDO::PARAM_INT);
$pending_quotes_query->execute();
$pending_quotes = $pending_quotes_query->fetchAll(PDO::FETCH_ASSOC);


// --- Fetching Completed Projects ---
$completed_projects_query = $conn->prepare("
    SELECT 
        prov.name as provider_name, 
        cq.project_description, 
        p.id as project_id, 
        cq.id as custom_quotation_id,
        cq.amount,
        cq.advance,
        COALESCE(notification_counts.unread_count, 0) as unread_messages
    FROM projects p
    JOIN custom_quotations cq ON p.quotation_id = cq.id
    JOIN users prov ON cq.provider_id = prov.id
    LEFT JOIN (
        SELECT 
            n.related_id as project_id,
            COUNT(*) as unread_count
        FROM notifications n
        WHERE n.user_id = :id 
        AND n.type = 'project' 
        AND n.is_read = 0
        GROUP BY n.related_id
    ) notification_counts ON p.id = notification_counts.project_id
    WHERE cq.customer_id = :id AND p.status = 'completed'
    ORDER BY p.id DESC
");
$completed_projects_query->bindParam(':id', $customer_id, PDO::PARAM_INT);
$completed_projects_query->execute();
$completed_projects = $completed_projects_query->fetchAll(PDO::FETCH_ASSOC);
?>

<!-- Include HTML Header from user_dashboard_header.php -->

<h2>My Projects & Quotations</h2>
<p>Track your active projects, respond to quotes, and view your completed work.</p>

<div class="dashboard-section">
    <h3>Active Projects</h3>
    <div class="content-card">
        <div class="table-wrapper">
            <table>
                <thead><tr><th>Provider</th><th>Project</th><th>Status</th><th>Action</th></tr></thead>
                <tbody>
                    <?php if (!empty($active_projects)): foreach ($active_projects as $project): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($project['provider_name']); ?></td>
                        <td><?php echo htmlspecialchars($project['project_description']); ?></td>
                        <td><span class="status-badge status-<?php 
                                $status_lower = strtolower($project['status']);
                                if ($status_lower === 'in_progress') echo 'pending';
                                elseif ($status_lower === 'awaiting_final_payment') echo 'yellow'; 
                                elseif ($status_lower === 'disputed') echo 'rejected';
                                else echo 'info'; // Fallback
                            ?>"><?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $project['status']))); ?></span></td>
                        <td>
                            <div class="action-container">
                                <!-- Link to track_project.php, passing the custom_quotation_id -->
                                <a href="track_project.php?id=<?php echo htmlspecialchars($project['custom_quotation_id']); ?>" class="btn-view">
                                    Track Project
                                    <?php if (($project['unread_messages'] ?? 0) > 0): ?>
                                        <span class="message-badge"><?php echo ($project['unread_messages'] ?? 0); ?></span>
                                    <?php endif; ?>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; else: ?>
                        <tr><td colspan="4" style="text-align: center;">You have no active projects.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="dashboard-section">
    <h3>Pending Quotations</h3>
    <div class="content-card">
        <div class="table-wrapper">
            <table>
                <thead><tr><th>Provider</th><th>Project</th><th>Status</th><th>Action</th></tr></thead>
                <tbody>
                    <?php if (!empty($pending_quotes)): foreach ($pending_quotes as $quote): 
                        // The entity_id refers to quotations.id for 'original' and custom_quotations.id for 'custom'
                        $view_quote_id = htmlspecialchars($quote['entity_id']);
                        $quote_type_param = htmlspecialchars($quote['quote_type']); // Pass this to differentiate in view_quote.php

                        $status_badge_class = 'info'; // Default
                        $status_lower = strtolower($quote['status']);
                        if ($status_lower === 'awaiting quote') { // Customer has sent request, waiting for provider to make quote
                            $status_badge_class = 'pending';
                        } elseif ($status_lower === 'sent' || $status_lower === 'pending') { // Provider has sent quote, customer needs to act
                            $status_badge_class = 'yellow';
                        } elseif ($status_lower === 'approved') {
                             $status_badge_class = 'approved'; // If customer has approved
                        } elseif ($status_lower === 'rejected' || $status_lower === 'declined') {
                            $status_badge_class = 'rejected';
                        }
                    ?>
                    <tr>
                        <td><?php echo htmlspecialchars($quote['provider_name']); ?></td>
                        <td><?php echo htmlspecialchars($quote['project_description']); ?></td>
                        <td><span class="status-badge status-<?php echo $status_badge_class; ?>"><?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $quote['status']))); ?></span></td>
                        <td>
                            <a href="view_quote.php?id=<?php echo $view_quote_id; ?>&type=<?php echo $quote_type_param; ?>" class="btn-view">View & Respond</a>
                        </td>
                    </tr>
                    <?php endforeach; else: ?>
                        <tr><td colspan="4" style="text-align: center;">You have no pending quotations.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="dashboard-section">
    <h3>Completed Projects</h3>
    <div class="content-card">
        <div class="table-wrapper">
            <table>
                <thead><tr><th>Provider</th><th>Project</th><th>Remaining Balance</th><th>Action</th></tr></thead>
                <tbody>
                    <?php if (!empty($completed_projects)): foreach ($completed_projects as $project): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($project['provider_name']); ?></td>
                        <td><?php echo htmlspecialchars($project['project_description']); ?></td>
                        <td>
                            <?php 
                            $remaining_balance = $project['amount'] - $project['advance'];
                            if ($remaining_balance > 0): 
                            ?>
                                <span class="remaining-balance">Rs <?php echo number_format($remaining_balance, 2); ?></span>
                            <?php else: ?>
                                <span class="status-badge status-approved">Fully Paid</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="action-container">
                                <?php if ($remaining_balance > 0): ?>
                                    <a href="payment_details.php?project_id=<?php echo htmlspecialchars($project['project_id']); ?>&type=final" class="btn-submit" style="background-color: var(--status-active); margin-bottom: 5px; display: block;">
                                        Pay Remaining Balance
                                    </a>
                                <?php endif; ?>
                                <a href="view_project_history.php?id=<?php echo htmlspecialchars($project['custom_quotation_id']); ?>" class="btn-view">
                                    View & Review
                                    <?php if (($project['unread_messages'] ?? 0) > 0): ?>
                                        <span class="message-badge"><?php echo ($project['unread_messages'] ?? 0); ?></span>
                                    <?php endif; ?>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; else: ?>
                        <tr><td colspan="4" style="text-align: center;">You have no completed projects.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
.action-container {
    position: relative;
    display: inline-block;
}

.message-badge {
    position: absolute;
    top: -8px;
    right: -8px;
    background-color: #dc2626;
    color: white;
    border-radius: 50%;
    padding: 2px 6px;
    font-size: 0.7rem;
    font-weight: 600;
    min-width: 18px;
    height: 18px;
    display: flex;
    align-items: center;
    justify-content: center;
    line-height: 1;
}

.btn-view {
    position: relative;
    display: inline-block;
}

.remaining-balance {
    color: #dc2626;
    font-weight: 600;
    font-size: 1.1rem;
}
</style>

<?php require_once '../includes/user_dashboard_footer.php'; ?>