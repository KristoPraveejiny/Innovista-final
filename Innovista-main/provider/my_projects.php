<?php 
$pageTitle = 'My Projects';
require_once '../provider/provider_header.php';
require_once '../config/session.php';
protectPage('provider'); 

// Get provider ID
$provider_id = $_SESSION['user_id'];

// Fetch real projects from database
try {
    $db = (new Database())->getConnection();
    
    
    // Fetch all projects for this provider first
    $stmt = $db->prepare("
        SELECT 
            p.id as project_id,
            p.status,
            p.start_date,
            p.end_date,
            cq.project_description,
            cq.amount,
            cq.advance,
            u.name as customer_name,
            u.email as customer_email,
            cq.id as custom_quotation_id,
            COALESCE(notification_counts.unread_count, 0) as unread_messages
        FROM projects p
        JOIN custom_quotations cq ON p.quotation_id = cq.id
        JOIN users u ON cq.customer_id = u.id
        LEFT JOIN (
            SELECT 
                n.related_id as project_id,
                COUNT(*) as unread_count
            FROM notifications n
            WHERE n.user_id = :provider_id 
            AND n.type = 'project' 
            AND n.is_read = 0
            GROUP BY n.related_id
        ) notification_counts ON p.id = notification_counts.project_id
        WHERE cq.provider_id = :provider_id
        ORDER BY p.start_date DESC
    ");
    $stmt->execute([':provider_id' => $provider_id]);
    $all_projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Categorize projects based on payment status
    $awaiting_full_payment = [];
    
    foreach ($all_projects as $project) {
        // If advance payment was made but amount is greater than advance, it's awaiting full payment
        if ($project['advance'] > 0 && $project['advance'] < $project['amount']) {
            $awaiting_full_payment[] = $project;
        }
    }
    
    // Fetch completed projects
    $stmt = $db->prepare("
        SELECT 
            p.id as project_id,
            p.status,
            p.start_date,
            p.end_date,
            cq.project_description,
            cq.amount,
            cq.advance,
            u.name as customer_name,
            u.email as customer_email,
            cq.id as custom_quotation_id,
            COALESCE(SUM(py.amount), 0) as total_paid
        FROM projects p
        JOIN custom_quotations cq ON p.quotation_id = cq.id
        JOIN users u ON cq.customer_id = u.id
        LEFT JOIN payments py ON py.quotation_id = cq.id
        WHERE cq.provider_id = :provider_id AND p.status = 'completed'
        GROUP BY p.id, p.status, p.start_date, p.end_date, cq.project_description, cq.amount, cq.advance, u.name, u.email, cq.id
        ORDER BY p.end_date DESC
    ");
    $stmt->execute([':provider_id' => $provider_id]);
    $completed_projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    error_log("Error fetching projects: " . $e->getMessage());
    $awaiting_full_payment = [];
    $completed_projects = [];
}
?>

<h2>My Projects</h2>
<p>Track your active jobs, manage payments, and view your completed work history.</p>

<div class="dashboard-section">
    
    <div class="content-card">
        <div class="table-wrapper">
            <table>
                <thead><tr><th>Customer</th><th>Project</th><th>Status</th><th>Action</th></tr></thead>
                <tbody>
                    <?php if (!empty($awaiting_full_payment)): foreach ($awaiting_full_payment as $project): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($project['customer_name']); ?></td>
                        <td><?php echo htmlspecialchars($project['project_description']); ?></td>
                        <td><span class="status-badge status-pending">Awaiting Full Payment</span></td>
                        <td>
                            <div class="action-container">
                                <a href="updateProgress.php?id=<?php echo $project['project_id']; ?>" class="btn-view">
                                    Update Progress
                                    <?php if (($project['unread_messages'] ?? 0) > 0): ?>
                                        <span class="message-badge"><?php echo ($project['unread_messages'] ?? 0); ?></span>
                                    <?php endif; ?>
                                </a>
                                <small style="display: block; color: #666; margin-top: 2px;">
                                    Amount: <?php echo number_format($project['amount'], 2); ?> | 
                                    Advance: <?php echo number_format($project['advance'], 2); ?> | 
                                    Balance: <?php echo number_format($project['amount'] - $project['advance'], 2); ?>
                                </small>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; else: ?>
                        <tr><td colspan="4" style="text-align: center;">No projects awaiting full payment.</td></tr>
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
                <thead><tr><th>Customer</th><th>Project</th><th>Status</th><th>Action</th></tr></thead>
                <tbody>
                    <?php if (!empty($completed_projects)): foreach ($completed_projects as $project): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($project['customer_name']); ?></td>
                        <td><?php echo htmlspecialchars($project['project_description']); ?></td>
                        <td>
                            <?php 
                            // Check if project is fully paid
                            $balance = $project['amount'] - $project['total_paid'];
                            if ($balance <= 0): 
                            ?>
                                <span class="status-badge status-approved">Fully Paid</span>
                            <?php else: ?>
                                <span class="status-badge status-pending">Completed</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="action-container">
                                <a href="updateProgress.php?id=<?php echo $project['project_id']; ?>" class="btn-view">
                                    View History
                                    <?php if (($project['unread_messages'] ?? 0) > 0): ?>
                                        <span class="message-badge"><?php echo ($project['unread_messages'] ?? 0); ?></span>
                                    <?php endif; ?>
                                </a>
                                <small style="display: block; color: #666; margin-top: 2px;">
                                    Amount: <?php echo number_format($project['amount'], 2); ?> | 
                                    Completed: <?php echo date('M d, Y', strtotime($project['end_date'])); ?>
                                </small>
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
</style>

<?php require_once '../includes/user_dashboard_footer.php'; ?>