<?php 
$pageTitle = 'My Earnings & Transactions';
require_once 'provider_header.php';
require_once '../config/session.php';
require_once '../config/Database.php';
protectPage('provider'); 

$provider_id = $_SESSION['user_id'];
$db = (new Database())->getConnection();

// Calculate real stats from database
$total_earnings = 0;
$stmt_total = $db->prepare("SELECT SUM(py.amount) as total_earnings FROM payments py JOIN custom_quotations cq ON py.quotation_id = cq.id WHERE cq.provider_id = :provider_id");
$stmt_total->bindParam(':provider_id', $provider_id, PDO::PARAM_INT);
$stmt_total->execute();
$total_earnings = $stmt_total->fetch(PDO::FETCH_ASSOC)['total_earnings'] ?? 0;

$pending_payout = 0;
// For now, we'll set pending payout to 0 since there's no status column
// In a real system, you might have a separate payouts table or different logic
$pending_payout = 0;

$this_month_earnings = 0;
$stmt_month = $db->prepare("SELECT SUM(py.amount) as this_month FROM payments py JOIN custom_quotations cq ON py.quotation_id = cq.id WHERE cq.provider_id = :provider_id AND MONTH(py.payment_date) = MONTH(CURRENT_DATE()) AND YEAR(py.payment_date) = YEAR(CURRENT_DATE())");
$stmt_month->bindParam(':provider_id', $provider_id, PDO::PARAM_INT);
$stmt_month->execute();
$this_month_earnings = $stmt_month->fetch(PDO::FETCH_ASSOC)['this_month'] ?? 0;

$stats = [
    'total_earnings' => $total_earnings,
    'pending_payout' => $pending_payout,
    'this_month' => $this_month_earnings
];

// Fetch real transaction history from database
$transactions = [];
$stmt_transactions = $db->prepare("
    SELECT 
        py.payment_date as date,
        cq.project_description as project,
        py.payment_type as type,
        py.amount,
        'completed' as status
    FROM payments py 
    JOIN custom_quotations cq ON py.quotation_id = cq.id 
    WHERE cq.provider_id = :provider_id 
    ORDER BY py.payment_date DESC 
    LIMIT 20
");
$stmt_transactions->bindParam(':provider_id', $provider_id, PDO::PARAM_INT);
$stmt_transactions->execute();
$transactions = $stmt_transactions->fetchAll(PDO::FETCH_ASSOC);
?>

<h2>My Earnings & Transactions</h2>
<p>View your total earnings, pending payouts, and detailed transaction history.</p>

<!-- Stat Cards -->
<div class="stats-container-customer">
    <div class="stat-card-customer">
        <div class="stat-icon-customer green"><i class="fas fa-coins"></i></div>
        <div class="stat-info-customer">
            <h4>Total Earnings (All Time)</h4>
            <p>Rs. <?php echo number_format($stats['total_earnings'], 2); ?></p>
        </div>
    </div>
    <div class="stat-card-customer">
        <div class="stat-icon-customer yellow"><i class="fas fa-hourglass-half"></i></div>
        <div class="stat-info-customer">
            <h4>Pending Payout</h4>
            <p>Rs. <?php echo number_format($stats['pending_payout'], 2); ?></p>
        </div>
    </div>
    <div class="stat-card-customer">
        <div class="stat-icon-customer blue"><i class="fas fa-calendar-alt"></i></div>
        <div class="stat-info-customer">
            <h4>This Month's Earnings</h4>
            <p>Rs. <?php echo number_format($stats['this_month'], 2); ?></p>
        </div>
    </div>
</div>

<!-- Transaction History Table -->
<div class="dashboard-section">
    <h3>Transaction History</h3>
    <div class="content-card">
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Description</th>
                        <th>Type</th>
                        <th>Amount</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($transactions)): foreach ($transactions as $t): ?>
                    <tr>
                        <td><?php echo date('d M Y', strtotime($t['date'])); ?></td>
                        <td><?php echo htmlspecialchars($t['project']); ?></td>
                        <td><?php echo htmlspecialchars($t['type']); ?></td>
                        <td style="color: <?php echo $t['amount'] < 0 ? '#e74c3c' : '#27ae60'; ?>; font-weight: 600;">
                            <?php echo $t['amount'] < 0 ? '-' : '+'; ?>Rs. <?php echo number_format(abs($t['amount']), 2); ?>
                        </td>
                        <td>
                            <span class="status-badge status-<?php echo strtolower(str_replace(' ', '-', $t['status'])); ?>">
                                <?php echo htmlspecialchars($t['status']); ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; else: ?>
                        <tr><td colspan="5" style="text-align: center;">You have no transactions yet.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once 'provider_footer.php'; ?>