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
// Calculate real stats from database
$active_projects_count = 0;
$stmt_active = $db->prepare("SELECT COUNT(p.id) as count FROM projects p JOIN custom_quotations cq ON p.quotation_id = cq.id WHERE cq.provider_id = :provider_id AND p.status IN ('in_progress', 'awaiting_final_payment', 'disputed')");
$stmt_active->bindParam(':provider_id', $provider_id, PDO::PARAM_INT);
$stmt_active->execute();
$active_projects_count = $stmt_active->fetch(PDO::FETCH_ASSOC)['count'];

$awaiting_payment_count = 0;
$stmt_payment = $db->prepare("SELECT COUNT(p.id) as count FROM projects p JOIN custom_quotations cq ON p.quotation_id = cq.id WHERE cq.provider_id = :provider_id AND p.status = 'awaiting_final_payment'");
$stmt_payment->bindParam(':provider_id', $provider_id, PDO::PARAM_INT);
$stmt_payment->execute();
$awaiting_payment_count = $stmt_payment->fetch(PDO::FETCH_ASSOC)['count'];

$total_earnings = 0;
$consultation_earnings = 0;
$combined_earnings = 0;

// Calculate regular project earnings
$stmt_earnings = $db->prepare("SELECT SUM(py.amount) as total_earnings FROM payments py JOIN custom_quotations cq ON py.quotation_id = cq.id WHERE cq.provider_id = :provider_id");
$stmt_earnings->bindParam(':provider_id', $provider_id, PDO::PARAM_INT);
$stmt_earnings->execute();
$total_earnings = $stmt_earnings->fetch(PDO::FETCH_ASSOC)['total_earnings'] ?? 0;

// Calculate consultation earnings
$stmt_consult_earnings = $db->prepare("SELECT SUM(book_consult_amount) as consultation_earnings FROM payments WHERE payment_type = 'consultation'");
$stmt_consult_earnings->execute();
$consultation_earnings = $stmt_consult_earnings->fetch(PDO::FETCH_ASSOC)['consultation_earnings'] ?? 0;

// Try to get earnings from provider_earnings table (more accurate if available)
try {
    $stmt_provider_earnings = $db->prepare("SELECT total_earnings, book_consult_earnings, total_combined_earnings FROM provider_earnings WHERE provider_id = :provider_id");
    $stmt_provider_earnings->bindParam(':provider_id', $provider_id, PDO::PARAM_INT);
    $stmt_provider_earnings->execute();
    $provider_earnings = $stmt_provider_earnings->fetch(PDO::FETCH_ASSOC);
    
    if ($provider_earnings) {
        $total_earnings = $provider_earnings['total_earnings'] ?? 0;
        $consultation_earnings = $provider_earnings['book_consult_earnings'] ?? 0;
        $combined_earnings = $provider_earnings['total_combined_earnings'] ?? ($total_earnings + $consultation_earnings);
    } else {
        $combined_earnings = $total_earnings + $consultation_earnings;
    }
} catch (PDOException $e) {
    // If provider_earnings table doesn't exist, use calculated values
    $combined_earnings = $total_earnings + $consultation_earnings;
}

$stats = [ 
    'new_requests' => $new_quote_count, 
    'active_projects' => $active_projects_count, 
    'awaiting_payment' => $awaiting_payment_count, 
    'total_earnings' => $total_earnings,
    'consultation_earnings' => $consultation_earnings,
    'combined_earnings' => $combined_earnings
];

// Handle success/error messages from URL parameters
if (isset($_GET['success'])) {
    $success_message = urldecode($_GET['success']);
    echo "<div class='flash-message-container'><div class='flash-message'>" . htmlspecialchars($success_message) . "</div></div>";
}
if (isset($_GET['error'])) {
    $error_message = urldecode($_GET['error']);
    echo "<div class='flash-message-container'><div class='flash-message error'>" . htmlspecialchars($error_message) . "</div></div>";
}

// Display session-based flash messages
if (function_exists('display_flash_message')) {
    echo '<div class="flash-message-container">';
    display_flash_message();
    echo '</div>';
}

// Fetch real quote requests from the database
$real_quote_requests = [];
$quote_stmt = $db->prepare('SELECT q.*, u.name as customer_name FROM quotations q JOIN users u ON q.customer_id = u.id WHERE q.provider_id = :provider_id AND (q.status = "Awaiting Quote" OR q.status = "Awaiting Your Quote") ORDER BY q.created_at DESC');
$quote_stmt->bindParam(':provider_id', $provider_id);
$quote_stmt->execute();
$real_quote_requests = $quote_stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch real active projects from the database
$active_projects = [];
$projects_stmt = $db->prepare('
    SELECT DISTINCT 
        u.name as customer_name,
        q.project_description as project_name,
        q.status,
        q.id as quotation_id
    FROM quotations q 
    JOIN users u ON q.customer_id = u.id 
    WHERE q.provider_id = :provider_id 
    AND q.status IN ("Approved", "In Progress", "Completed")
    ORDER BY q.created_at DESC
    LIMIT 5
');
$projects_stmt->bindParam(':provider_id', $provider_id);
$projects_stmt->execute();
$active_projects = $projects_stmt->fetchAll(PDO::FETCH_ASSOC);

// Add link to each project
foreach ($active_projects as &$project) {
    $project['link'] = 'my_projects.php';
}

// Fetch customer reviews for this provider
$reviews = [];

try {
    // First, let's try a simpler query without joins to see if reviews exist
    $reviews_simple = $db->prepare('SELECT * FROM reviews WHERE provider_id = :provider_id ORDER BY created_at DESC LIMIT 10');
    $reviews_simple->bindParam(':provider_id', $provider_id, PDO::PARAM_INT);
    $reviews_simple->execute();
    $simple_reviews = $reviews_simple->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($simple_reviews) > 0) {
        // If simple query works, try the complex query with joins
        $reviews_stmt = $db->prepare('
            SELECT r.id, r.quotation_id, r.customer_id, r.provider_id, r.rating, r.comment, r.created_at as review_date,
                   u.name as customer_name, 
                   COALESCE(cq.project_description, q.project_description, "Project") as project_description
            FROM reviews r 
            JOIN users u ON r.customer_id = u.id 
            LEFT JOIN custom_quotations cq ON r.quotation_id = cq.id
            LEFT JOIN quotations q ON r.quotation_id = q.id
            WHERE r.provider_id = :provider_id 
            ORDER BY r.created_at DESC 
            LIMIT 10
        ');
        $reviews_stmt->bindParam(':provider_id', $provider_id, PDO::PARAM_INT);
        $reviews_stmt->execute();
        $reviews = $reviews_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // If complex query fails, fall back to simple query with manual project lookup
        if (count($reviews) == 0) {
            foreach ($simple_reviews as $simple_review) {
                $project_desc = "Project";
                // Try to get project description
                try {
                    $proj_stmt = $db->prepare('SELECT project_description FROM custom_quotations WHERE id = :qid LIMIT 1');
                    $proj_stmt->bindParam(':qid', $simple_review['quotation_id'], PDO::PARAM_INT);
                    $proj_stmt->execute();
                    $proj_data = $proj_stmt->fetch(PDO::FETCH_ASSOC);
                    if ($proj_data) {
                        $project_desc = $proj_data['project_description'];
                    }
                } catch (Exception $e) {
                    // Keep default project description
                }
                
                // Get customer name
                $customer_name = "Unknown Customer";
                try {
                    $cust_stmt = $db->prepare('SELECT name FROM users WHERE id = :uid LIMIT 1');
                    $cust_stmt->bindParam(':uid', $simple_review['customer_id'], PDO::PARAM_INT);
                    $cust_stmt->execute();
                    $cust_data = $cust_stmt->fetch(PDO::FETCH_ASSOC);
                    if ($cust_data) {
                        $customer_name = $cust_data['name'];
                    }
                } catch (Exception $e) {
                    // Keep default customer name
                }
                
                $reviews[] = array_merge($simple_review, [
                    'customer_name' => $customer_name,
                    'project_description' => $project_desc,
                    'review_date' => $simple_review['created_at']
                ]);
            }
        }
    }
} catch (PDOException $e) {
    error_log("Review fetch error: " . $e->getMessage());
    $reviews = [];
}

// Create timeline items from reviews only (since quotations table doesn't have updated_at)
$timeline_items = [];
foreach ($reviews as $review) {
    $timeline_items[] = [
        'type' => 'review',
        'customer_name' => $review['customer_name'],
        'content' => $review['comment'],
        'rating' => $review['rating'],
        'project' => $review['project_description'],
        'date' => $review['review_date']
    ];
}

// Sort timeline by date (most recent first)
usort($timeline_items, function($a, $b) {
    return strtotime($b['date']) - strtotime($a['date']);
});
$timeline_items = array_slice($timeline_items, 0, 8); // Show only recent 8 items
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
            <h4>Total Combined Earnings</h4>
            <p>Rs. <?php echo number_format($stats['combined_earnings'], 2); ?></p>
            <small style="color: #666; font-size: 0.85em;">
                Projects: Rs. <?php echo number_format($stats['total_earnings'], 2); ?><br>
                Consultations: Rs. <?php echo number_format($stats['consultation_earnings'], 2); ?>
            </small>
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

<!-- Project Timeline & Updates -->
<div class="dashboard-section">
    <h3>Customer Reviews</h3>
    <div class="content-card">
        <div class="timeline-container">
            <?php if (!empty($timeline_items)): ?>
                <?php foreach ($timeline_items as $item): ?>
                    <div class="timeline-item">
                        <div class="timeline-icon review-icon">
                            <i class="fas fa-star"></i>
                        </div>
                        <div class="timeline-content">
                            <div class="timeline-header">
                                <strong><?php echo htmlspecialchars($item['customer_name']); ?></strong>
                                <span class="timeline-role">(Customer)</span>
                                <span class="timeline-date"><?php echo date('M j, Y g:i A', strtotime($item['date'])); ?></span>
                            </div>
                            <div class="timeline-body">
                                <div class="review-rating">
                                    <?php for($i = 1; $i <= 5; $i++): ?>
                                        <i class="fas fa-star <?php echo $i <= $item['rating'] ? 'star-filled' : 'star-empty'; ?>"></i>
                                    <?php endfor; ?>
                                </div>
                                <p class="review-comment"><?php echo htmlspecialchars($item['content']); ?></p>
                                <small class="project-name">Project: <?php echo htmlspecialchars($item['project']); ?></small>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="timeline-empty">
                    <i class="fas fa-star" style="font-size: 3rem; color: #ddd; margin-bottom: 1rem;"></i>
                    <p>No customer reviews yet.</p>
                    <small style="color: #999;">Reviews from completed projects will appear here.</small>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
/* Custom styling for earnings box to handle large numbers */
.stat-card-customer:nth-child(4) .stat-info-customer p {
    font-size: 1.8rem !important; /* Smaller font for large earnings numbers */
    font-weight: 700 !important;
    color: var(--text-dark) !important;
    line-height: 1.1 !important;
    word-wrap: break-word !important;
    overflow-wrap: break-word !important;
}

/* Responsive font sizing for earnings */
@media (max-width: 1200px) {
    .stat-card-customer:nth-child(4) .stat-info-customer p {
        font-size: 1.6rem !important;
    }
}

@media (max-width: 992px) {
    .stat-card-customer:nth-child(4) .stat-info-customer p {
        font-size: 1.4rem !important;
    }
}

@media (max-width: 768px) {
    .stat-card-customer:nth-child(4) .stat-info-customer p {
        font-size: 1.2rem !important;
    }
}

/* Make the earnings stat card slightly wider to accommodate large numbers */
.stat-card-customer:nth-child(4) {
    min-width: 320px;
}

/* Ensure proper spacing for earnings breakdown text */
.stat-card-customer:nth-child(4) small {
    line-height: 1.4 !important;
    margin-top: 0.5rem !important;
    display: block !important;
}

/* Responsive adjustments for earnings card */
@media (max-width: 768px) {
    .stat-card-customer:nth-child(4) {
        min-width: auto;
        flex-direction: column;
        text-align: center;
        gap: 1rem;
    }
    
    .stat-card-customer:nth-child(4) .stat-info-customer {
        text-align: center;
    }
}

/* Ensure stats container handles the larger earnings card properly */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

@media (max-width: 768px) {
    .stats-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<?php require_once 'provider_footer.php'; ?>