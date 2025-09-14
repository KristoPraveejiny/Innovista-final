<?php 
$pageTitle = 'My Earnings & Transactions';
require_once 'provider_header.php';
require_once '../config/session.php';
protectPage('provider'); 

// --- In a real application, this data would come from your database ---
$stats = [
    'total_earnings' => 15750.00,
    'pending_payout' => 2500.00,
    'this_month' => 7500.00
];

$transactions = [
    ['date' => '22 Jul 2025', 'project' => 'Antique Chair Refurbishing', 'type' => 'Final Payment Received', 'amount' => 1500.00, 'status' => 'Processing Payout'],
    ['date' => '20 Jul 2025', 'project' => 'Exterior House Painting Payout', 'type' => 'Payout', 'amount' => -2000.00, 'status' => 'Completed'],
    ['date' => '15 Jul 2025', 'project' => 'Living Room Renovation', 'type' => 'Final Payment Received', 'amount' => 5000.00, 'status' => 'In Account'],
    ['date' => '10 Jun 2025', 'project' => 'Living Room Renovation', 'type' => 'Advance Payment Received', 'amount' => 2500.00, 'status' => 'Paid Out'],
];
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
                        <td><?php echo htmlspecialchars($t['date']); ?></td>
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