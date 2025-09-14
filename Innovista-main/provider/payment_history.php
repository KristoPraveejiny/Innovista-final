<?php 
$pageTitle = 'Payment History';
require_once '../includes/user_dashboard_header.php';

// --- DUMMY DATA ---
$payments = [
    ['date' => '20 Jul 2025', 'project' => 'Exterior House Painting', 'type' => 'Final Payment', 'amount' => 1500.00, 'invoice_link' => '#'],
    ['date' => '15 Jul 2025', 'project' => 'Exterior House Painting', 'type' => 'Advance Payment', 'amount' => 500.00, 'invoice_link' => '#'],
    ['date' => '10 Jun 2025', 'project' => 'Living Room Renovation', 'type' => 'Advance Payment', 'amount' => 2500.00, 'invoice_link' => '#'],
];
?>

<h2>My Payments</h2>
<p>View a complete history of all your transactions on the platform.</p>

<div class="content-card">
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Project</th>
                    <th>Payment Type</th>
                    <th>Amount</th>
                    <th>Invoice</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($payments)): foreach ($payments as $payment): ?>
                <tr>
                    <td><?php echo htmlspecialchars($payment['date']); ?></td>
                    <td><?php echo htmlspecialchars($payment['project']); ?></td>
                    <td><?php echo htmlspecialchars($payment['type']); ?></td>
                    <td>Rs. <?php echo number_format($payment['amount'], 2); ?></td>
                    <td><a href="<?php echo $payment['invoice_link']; ?>" class="btn-view">Download</a></td>
                </tr>
                <?php endforeach; else: ?>
                    <tr><td colspan="5" style="text-align: center;">You have not made any payments yet.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once '../includes/user_dashboard_footer.php'; ?>