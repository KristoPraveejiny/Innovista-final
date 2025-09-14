<?php
$pageTitle = 'Provider Dashboard';
require_once '../config/session.php';
require_once '../config/Database.php';
protectPage('provider');
require_once 'provider_header.php';
$provider_id = $_SESSION['user_id'];
$db = (new Database())->getConnection();
$count_stmt = $db->prepare('SELECT COUNT(*) FROM quotations WHERE provider_id = :provider_id AND (status = "Awaiting Quote" OR status = "Awaiting Your Quote")');
$count_stmt->bindParam(':provider_id', $provider_id);
$count_stmt->execute();
$new_quote_count = $count_stmt->fetchColumn();
$stats = [ 'new_requests' => $new_quote_count, 'active_projects' => 2, 'awaiting_payment' => 1, 'total_earnings' => 15750.00 ];

// Handle success/error messages from URL parameters
if (isset($_GET['success'])) {
    $success_message = urldecode($_GET['success']);
    echo "<div class='flash-message-container'><div class='flash-message'>" . htmlspecialchars($success_message) . "</div></div>";
}
if (isset($_GET['error'])) {
    $error_message = urldecode($_GET['error']);
    echo "<div class='flash-message-container'><div class='flash-message error'>" . htmlspecialchars($error_message) . "</div></div>";
}

// Fetch real quote requests from the database
$real_quote_requests = [];
$quote_stmt = $db->prepare('SELECT q.*, u.name as customer_name FROM quotations q JOIN users u ON q.customer_id = u.id WHERE q.provider_id = :provider_id AND (q.status = "Awaiting Quote" OR q.status = "Awaiting Your Quote") ORDER BY q.created_at DESC');
$quote_stmt->bindParam(':provider_id', $provider_id);
$quote_stmt->execute();
$real_quote_requests = $quote_stmt->fetchAll(PDO::FETCH_ASSOC);

$active_projects = [ ['customer' => 'Alice Johnson', 'project' => 'Living Room Renovation', 'status' => 'In Progress', 'link' => 'my_projects.php'] ];
?>
<h2>Provider Dashboard</h2>
<p>Welcome back, <?php echo $_SESSION['user_name'] ?? 'Provider'; ?>! Manage your business, respond to clients, and showcase your work.</p>

<!-- Stat Cards -->
<div class="stats-container-customer">
    <div class="stat-card-customer">
        <div class="stat-icon-customer yellow"><i class="fas fa-file-signature"></i></div>
        <div class="stat-info-customer">
            <h4>New Quote Requests</h4>
            <p><?php echo $stats['new_requests']; ?></p>
        </div>
    </div>
    <div class="stat-card-customer">
        <div class="stat-icon-customer blue"><i class="fas fa-tasks"></i></div>
        <div class="stat-info-customer">
            <h4>Active Projects</h4>
            <p><?php echo $stats['active_projects']; ?></p>
        </div>
    </div>
    <div class="stat-card-customer">
        <div class="stat-icon-customer red"><i class="fas fa-hand-holding-usd"></i></div>
        <div class="stat-info-customer">
            <h4>Awaiting Payment</h4>
            <p><?php echo $stats['awaiting_payment']; ?></p>
        </div>
    </div>
    <div class="stat-card-customer">
        <div class="stat-icon-customer green"><i class="fas fa-coins"></i></div>
        <div class="stat-info-customer">
            <h4>Total Earnings</h4>
            <p>Rs. <?php echo number_format($stats['total_earnings'], 2); ?></p>
        </div>
    </div>
</div>

<!-- Quick Access Action Hub -->
<div class="dashboard-section">
    <h3>My Business Tools</h3>
    <div class="quick-access-grid">
        <a href="./manage_portfolio.php" class="access-card"><i class="fas fa-images"></i><span>Manage Portfolio</span></a>
        <a href="./manage_calendar.php" class="access-card"><i class="fas fa-calendar-alt"></i><span>Update Availability</span></a>
        <a href="./view_transactions.php" class="access-card"><i class="fas fa-receipt"></i><span>View Transactions</span></a>
        <a href="./my_profile.php" class="access-card"><i class="fas fa-user-edit"></i><span>Edit My Profile</span></a>
    </div>
</div>

<!-- New Quote Requests Table -->
<div class="dashboard-section">
    <h3><a href="manage_quotations.php" style="color:inherit;text-decoration:none;">Recent Quote Requests</a></h3>
    <div class="content-card">
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Customer</th>
                        <th>Project Summary</th>
                        <th>Service Type</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($real_quote_requests)): foreach ($real_quote_requests as $request): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($request['customer_name']); ?></strong></td>
                        <td><?php echo htmlspecialchars(substr($request['project_description'], 0, 100)) . (strlen($request['project_description']) > 100 ? '...' : ''); ?></td>
                        <td><?php echo htmlspecialchars($request['service_type'] ?? 'N/A'); ?></td>
                        <td><span class="status-badge status-pending"><?php echo htmlspecialchars($request['status']); ?></span></td>
                        <td><a href="create_quotation.php?id=<?php echo $request['id']; ?>" class="btn-view">Create Quote</a></td>
                    </tr>
                    <?php endforeach; else: ?>
                    <tr><td colspan="5" style="text-align:center; padding: 2rem; color: var(--text-light);">No new quote requests at the moment.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Active Projects Table -->
<div class="dashboard-section">
    <h3><a href="my_projects.php" style="color:inherit;text-decoration:none;">Active Projects</a></h3>
    <div class="content-card">
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Customer</th>
                        <th>Project</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($active_projects)): foreach ($active_projects as $project): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($project['customer']); ?></strong></td>
                        <td><?php echo htmlspecialchars($project['project']); ?></td>
                        <td><span class="status-badge status-active"><?php echo htmlspecialchars($project['status']); ?></span></td>
                        <td><a href="<?php echo $project['link']; ?>" class="btn-view">View Details</a></td>
                    </tr>
                    <?php endforeach; else: ?>
                    <tr><td colspan="4" style="text-align:center; padding: 2rem; color: var(--text-light);">No active projects at the moment.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once 'provider_footer.php'; ?>