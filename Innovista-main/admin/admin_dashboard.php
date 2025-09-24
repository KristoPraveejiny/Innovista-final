<?php 
// admin_dashboard.php
require_once 'admin_header.php'; // session_start() and login check are handled here
require_once '../config/Database.php';
require_once '../public/session.php'; // Needed for getImageSrc in recent activity

// Initialize DB
$db = new Database();
$conn = $db->getConnection();

// --- FETCH DATA FOR DASHBOARD ---

// 1. Total Revenue (sum of all payments)
$total_revenue = 0;
$stmt = $conn->prepare("SELECT SUM(amount) as revenue FROM payments");
$stmt->execute();
$row = $stmt->fetch(PDO::FETCH_ASSOC);
$total_revenue = $row['revenue'] ?? 0;

// 2. Active Users (customers + providers with status='active', excluding admins)
$active_users = 0;
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM users WHERE status='active' AND role != 'admin'");
$stmt->execute();
$row = $stmt->fetch(PDO::FETCH_ASSOC);
$active_users = $row['total'];

// 3. Pending Providers
$pending_providers = 0;
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM users WHERE role='provider' AND provider_status='pending'");
$stmt->execute();
$row = $stmt->fetch(PDO::FETCH_ASSOC);
$pending_providers = $row['total'];

// 4. New Messages (contacts table, last 24 hours or unread if 'is_read' column used)
$new_messages = 0;
// If you added 'is_read' column:
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM contacts WHERE is_read = 0");
// If not, use time-based:
// $stmt = $conn->prepare("SELECT COUNT(*) as total FROM contacts WHERE created_at >= NOW() - INTERVAL 1 DAY");
$stmt->execute();
$row = $stmt->fetch(PDO::FETCH_ASSOC);
$new_messages = $row['total'];

// 5. Total Reviews count
$total_reviews_count = 0;
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM reviews");
$stmt->execute();
$row = $stmt->fetch(PDO::FETCH_ASSOC);
$total_reviews_count = $row['total'];

// 6. Total Products count
$total_products = 0;
try {
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM products WHERE status = 'active'");
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $total_products = $row['total'];
} catch (PDOException $e) {
    // Products table doesn't exist, set to 0
    $total_products = 0;
}

// 7. Low Stock Products count (less than 10 items)
$low_stock_products = 0;
try {
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM products WHERE stock_quantity < 10 AND status = 'active'");
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $low_stock_products = $row['total'];
} catch (PDOException $e) {
    // Products table doesn't exist, set to 0
    $low_stock_products = 0;
}

// 6. Total Services
$total_services = 0;
try {
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM service");
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $total_services = $row['total'];
} catch (PDOException $e) {
    // Service table doesn't exist, set to 0
    $total_services = 0;
}

// 7. Total Services with Portfolio
$total_portfolio_items = 0;
try {
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM service WHERE portfolio IS NOT NULL AND portfolio != ''");
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $total_portfolio_items = $row['total'];
} catch (PDOException $e) {
    // Service table doesn't exist, set to 0
    $total_portfolio_items = 0;
}


// 8. Recent Registrations (latest 5 users, excluding admins)
$recent_users = [];
$stmt = $conn->prepare("SELECT id, name, role, 
        CASE 
            WHEN role='provider' THEN provider_status 
            ELSE status 
        END AS status 
        FROM users 
        WHERE role != 'admin' -- Exclude admin users from recent registrations
        ORDER BY created_at DESC LIMIT 5");
$stmt->execute();
$recent_users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 9. Recent Activity Feed (Example: last 10 activities - new quotes, payments, reviews, provider registrations)
$recent_activities = [];
try {
    $stmt_activities = $conn->prepare("
        (SELECT 'quotation_request' as type, q.id as entity_id, q.created_at, u.name as user_name, u.role, q.project_description as description
         FROM quotations q JOIN users u ON q.customer_id = u.id ORDER BY q.created_at DESC LIMIT 5)
        UNION ALL
        (SELECT 'payment' as type, p.id as entity_id, p.payment_date as created_at, u.name as user_name, 'customer' as role, CONCAT(p.payment_type, ' payment of Rs ', p.amount) as description
         FROM payments p JOIN custom_quotations cq ON p.quotation_id = cq.id JOIN users u ON cq.customer_id = u.id ORDER BY p.payment_date DESC LIMIT 5)
        UNION ALL
        (SELECT 'new_review' as type, r.id as entity_id, r.created_at, u.name as user_name, 'customer' as role, CONCAT('New review for provider ', p.name, ' (Rating: ', r.rating, ')') as description
         FROM reviews r JOIN users u ON r.customer_id = u.id JOIN users p ON r.provider_id = p.id ORDER BY r.created_at DESC LIMIT 5)
        UNION ALL
        (SELECT 'new_provider' as type, u.id as entity_id, u.created_at, u.name as user_name, u.role, CONCAT('New provider registration: ', u.name) as description
         FROM users u WHERE u.role = 'provider' ORDER BY u.created_at DESC LIMIT 5)
        ORDER BY created_at DESC LIMIT 10
    ");
    $stmt_activities->execute();
    $recent_activities = $stmt_activities->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Database error fetching recent activities: " . $e->getMessage());
}

?>

<h2>Dashboard</h2>
<p>An overview of platform activity and key metrics.</p>

<!-- Stat Cards -->
<div class="stats-container">
    <div class="stat-card">
        <div class="stat-icon" style="background: linear-gradient(45deg, #27ae60, #2ecc71);">
            <i class="fas fa-dollar-sign"></i>
        </div>
        <div class="stat-info">
            <h4>Total Revenue</h4>
            <p class="revenue-amount">Rs <?php echo number_format($total_revenue, 2); ?></p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background: linear-gradient(45deg, #3498db, #5dade2);">
            <i class="fas fa-users"></i>
        </div>
        <div class="stat-info">
            <h4>Active Users</h4>
            <p><?php echo $active_users; ?></p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background: linear-gradient(45deg, #f39c12, #f5b041);">
            <i class="fas fa-user-clock"></i>
        </div>
        <div class="stat-info">
            <h4>Pending Providers</h4>
            <p><?php echo $pending_providers; ?></p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background: linear-gradient(45deg, #5eead4, #88d4d4);">
            <i class="fas fa-envelope"></i>
        </div>
        <div class="stat-info">
            <h4>New Messages</h4>
            <p><?php echo $new_messages; ?></p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background: linear-gradient(45deg, #f39c12, #f7dc6f);">
            <i class="fas fa-star"></i>
        </div>
        <div class="stat-info">
            <h4>Total Reviews</h4>
            <p><?php echo $total_reviews_count; ?></p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background: linear-gradient(45deg, #0d9488, #3e9a92);">
            <i class="fas fa-tools"></i>
        </div>
        <div class="stat-info">
            <h4>Total Services</h4>
            <p><?php echo $total_services; ?></p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background: linear-gradient(45deg, #8e44ad, #a569bd);">
            <i class="fas fa-shopping-bag"></i>
        </div>
        <div class="stat-info">
            <h4>Total Products</h4>
            <p><?php echo $total_products; ?></p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background: linear-gradient(45deg, #f39c12, #f5b041);">
            <i class="fas fa-exclamation-triangle"></i>
        </div>
        <div class="stat-info">
            <h4>Low Stock</h4>
            <p><?php echo $low_stock_products; ?></p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background: linear-gradient(45deg, #9b59b6, #af7ac5);">
            <i class="fas fa-images"></i>
        </div>
        <div class="stat-info">
            <h4>Services with Portfolio</h4>
            <p><?php echo $total_portfolio_items; ?></p>
        </div>
    </div>
</div>

<!-- Quick Access -->
<div class="dashboard-section">
    <h3>Quick Access</h3>
    <div class="quick-access-grid">
        <a href="manage_providers.php" class="access-card">
            <i class="fas fa-user-check"></i>
            <span>Provider Approvals</span>
        </a>
        <a href="manage_users.php" class="access-card">
            <i class="fas fa-users-cog"></i>
            <span>User Management</span>
        </a>
        <a href="manage_quotations.php" class="access-card">
            <i class="fas fa-file-invoice"></i>
            <span>Quotations</span>
        </a>
        <a href="manage_contacts.php" class="access-card">
            <i class="fas fa-envelope-open-text"></i>
            <span>Contact Messages</span>
        </a>
        <a href="manage_reviews.php" class="access-card">
            <i class="fas fa-star"></i>
            <span>Customer Reviews</span>
        </a>
        <a href="manage_portfolio_items.php" class="access-card">
            <i class="fas fa-images"></i>
            <span>Portfolio Items</span>
        </a>
        <a href="manage_products.php" class="access-card">
            <i class="fas fa-shopping-bag"></i>
            <span>Manage Products</span>
        </a>
        <a href="reports.php" class="access-card">
            <i class="fas fa-chart-bar"></i>
            <span>Platform Reports</span>
        </a>
        <a href="settings.php" class="access-card">
            <i class="fas fa-cogs"></i>
            <span>System Settings</span>
        </a>
    </div>
</div>

<!-- Recent Registrations -->
<div class="dashboard-section">
    <h3>Recent Registrations</h3>
    <div class="content-card">
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($recent_users)): ?>
                        <?php foreach($recent_users as $user): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($user['name']); ?></td>
                            <td><?php echo ucfirst(htmlspecialchars($user['role'])); ?></td>
                            <td>
                                <?php 
                                    $status_class = strtolower($user['status']);
                                    if ($user['role'] === 'provider') {
                                        $status_class = (strtolower($user['status']) === 'pending') ? 'pending' : (strtolower($user['status']) === 'approved' ? 'active' : 'inactive');
                                    }
                                    echo "<span class='status-badge status-{$status_class}'>" . htmlspecialchars($user['status']) . "</span>";
                                ?>
                            </td>
                            <td>
                                <?php if ($user['role'] === 'provider'): ?>
                                    <a href="manage_providers.php" class="btn-view">View</a>
                                <?php elseif ($user['role'] === 'customer'): ?>
                                    <a href="manage_users.php" class="btn-view">View</a>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="4">No recent registrations found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Recent Activity Feed -->
<div class="dashboard-section">
    <h3>Recent Activity</h3>
    <div class="content-card">
        <ul class="activity-feed">
            <?php if (!empty($recent_activities)): ?>
                <?php foreach ($recent_activities as $activity): ?>
                    <li class="activity-item">
                        <?php
                            $icon = '';
                            $icon_class = '';
                            $link = '#'; // Default, update with specific links later
                            switch ($activity['type']) {
                                case 'quotation_request':
                                    $icon = 'fas fa-file-invoice-dollar';
                                    $icon_class = 'blue';
                                    $link = 'view_quotation.php?id=' . htmlspecialchars($activity['entity_id']);
                                    break;
                                case 'payment':
                                    $icon = 'fas fa-dollar-sign';
                                    $icon_class = 'green';
                                    // Assuming you might create a view_payment.php
                                    // $link = 'view_payment.php?id=' . htmlspecialchars($activity['entity_id']);
                                    break;
                                case 'dispute':
                                    $icon = 'fas fa-gavel';
                                    $icon_class = 'red';
                                    $link = 'view_dispute.php?id=' . htmlspecialchars($activity['entity_id']);
                                    break;
                                case 'new_review':
                                    $icon = 'fas fa-star';
                                    $icon_class = 'yellow';
                                    // $link = 'view_review.php?id=' . htmlspecialchars($activity['entity_id']); // If you create a review view page
                                    break;
                                case 'new_provider':
                                    $icon = 'fas fa-user-plus';
                                    $icon_class = 'purple'; // Using purple from CSS
                                    $link = 'view_provider.php?id=' . htmlspecialchars($activity['entity_id']);
                                    break;
                                default:
                                    $icon = 'fas fa-info-circle';
                                    $icon_class = 'grey';
                                    break;
                            }
                        ?>
                        <div class="activity-icon <?php echo $icon_class; ?>"><i class="<?php echo $icon; ?>"></i></div>
                        <div class="activity-content">
                            <p class="activity-text">
                                <a href="<?php echo htmlspecialchars($link); ?>">
                                    <?php
                                        echo ucfirst(str_replace('_', ' ', $activity['type']));
                                        if ($activity['user_name']) {
                                            echo " by <strong>" . htmlspecialchars($activity['user_name']) . "</strong>";
                                        }
                                        echo ": " . htmlspecialchars(substr($activity['description'], 0, 100)) . (strlen($activity['description']) > 100 ? '...' : '');
                                    ?>
                                </a>
                            </p>
                            <span class="activity-time"><?php echo date('d M Y H:i', strtotime($activity['created_at'])); ?></span>
                        </div>
                    </li>
                <?php endforeach; ?>
            <?php else: ?>
                <p>No recent activity.</p>
            <?php endif; ?>
        </ul>
    </div>
</div>


<style>
/* Custom styling for revenue box to handle large numbers */
.revenue-amount {
    font-size: 1.8rem !important; /* Smaller font for large numbers */
    font-weight: 700 !important;
    color: var(--text-dark) !important;
    line-height: 1.1 !important;
    word-wrap: break-word !important;
    overflow-wrap: break-word !important;
}

/* Responsive font sizing for revenue */
@media (max-width: 1200px) {
    .revenue-amount {
        font-size: 1.6rem !important;
    }
}

@media (max-width: 992px) {
    .revenue-amount {
        font-size: 1.4rem !important;
    }
}

@media (max-width: 768px) {
    .revenue-amount {
        font-size: 1.2rem !important;
    }
}

/* Make the revenue stat card slightly wider to accommodate the number */
.stat-card:first-child {
    min-width: 280px;
}

/* Ensure proper spacing for all stat cards */
.stats-container {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

@media (max-width: 768px) {
    .stats-container {
        grid-template-columns: 1fr;
    }
    
    .stat-card:first-child {
        min-width: auto;
    }
}
</style>

<?php require_once 'admin_footer.php'; ?>