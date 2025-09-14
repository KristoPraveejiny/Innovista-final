<?php
$pageTitle = 'Manage Quotations';
require_once '../provider/provider_header.php';
require_once '../config/Database.php';
$provider_id = $_SESSION['user_id'];
$db = (new Database())->getConnection();
$stmt = $db->prepare('SELECT q.*, u.name as customer_name FROM quotations q JOIN users u ON q.customer_id = u.id WHERE q.provider_id = :provider_id ORDER BY q.created_at DESC');
$stmt->bindParam(':provider_id', $provider_id);
$stmt->execute();
$quote_requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<h2>Manage Quotations</h2>

<div class="content-card">
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>Customer</th>
                    <th>Project Summary</th>
                    <th>Date Received</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($quote_requests)): foreach ($quote_requests as $request): ?>
                <tr>
                    <td><?php echo htmlspecialchars($request['customer_name']); ?></td>
                    <td><?php echo htmlspecialchars($request['project_description']); ?></td>
                    <td><?php echo htmlspecialchars(date('d M Y', strtotime($request['created_at']))); ?></td>
                    <td>
                        <span class="status-badge <?php echo $request['status'] == 'Awaiting Quote' ? 'status-pending' : 'status-approved'; ?>">
                            <?php echo htmlspecialchars($request['status']); ?>
                        </span>
                    </td>
                    <td><a href="create_quotation.php?id=<?php echo $request['id']; ?>" class="btn-view">Create & Send Quote</a></td>
                </tr>
                <?php endforeach; else: ?>
                    <tr><td colspan="5" style="text-align: center;">You have no new quote requests.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once '../includes/user_dashboard_footer.php'; ?>