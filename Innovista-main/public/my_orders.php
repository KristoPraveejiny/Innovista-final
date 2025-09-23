<?php
session_start();
require_once '../config/Database.php';
require_once '../handlers/flash_message.php';

// Check if user is logged in
if (!function_exists('isUserLoggedIn')) {
    function isUserLoggedIn() {
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }
}

if (!isUserLoggedIn()) {
    header('Location: login.php');
    exit();
}

$database = new Database();
$conn = $database->getConnection();

// Get user's orders
$stmt = $conn->prepare("
    SELECT o.*, 
           (SELECT COUNT(*) FROM order_items oi WHERE oi.order_id = o.id) as item_count
    FROM orders o
    WHERE o.user_id = ?
    ORDER BY o.created_at DESC
");
$stmt->execute([$_SESSION['user_id']]);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'My Orders';
include 'header.php';
?>

<div class="container" style="max-width: 1000px; margin: 50px auto; padding: 20px;">
    <h1 style="margin-bottom: 30px;">My Orders</h1>
    
    <?php if (empty($orders)): ?>
    <div class="no-orders" style="text-align: center; padding: 50px;">
        <i class="fas fa-shopping-bag" style="font-size: 64px; color: #ccc; margin-bottom: 20px;"></i>
        <h3>No orders found</h3>
        <p>You haven't placed any orders yet.</p>
        <a href="index.php" class="btn btn-primary">Start Shopping</a>
    </div>
    <?php else: ?>
    
    <div class="orders-list">
        <?php foreach ($orders as $order): ?>
        <div class="order-card" style="border: 1px solid #ddd; border-radius: 8px; padding: 20px; margin-bottom: 20px; background: white;">
            <div class="order-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                <div>
                    <h3 style="margin: 0;">Order #<?php echo $order['id']; ?></h3>
                    <p style="margin: 5px 0 0 0; color: #666;">Placed on <?php echo date('F j, Y g:i A', strtotime($order['created_at'])); ?></p>
                </div>
                <div class="order-status">
                    <span class="status-badge status-<?php echo $order['status']; ?>" 
                          style="padding: 5px 15px; border-radius: 20px; font-size: 12px; font-weight: 600; text-transform: uppercase;">
                        <?php echo ucfirst($order['status']); ?>
                    </span>
                </div>
            </div>
            
            <div class="order-summary" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 15px;">
                <div>
                    <strong>Total Amount:</strong><br>
                    Rs. <?php echo number_format($order['total_amount'], 2); ?>
                </div>
                <div>
                    <strong>Advance Paid:</strong><br>
                    Rs. <?php echo number_format($order['advance_amount'], 2); ?>
                </div>
                <div>
                    <strong>Balance Due:</strong><br>
                    Rs. <?php echo number_format($order['balance_due'], 2); ?>
                </div>
                <div>
                    <strong>Items:</strong><br>
                    <?php echo $order['item_count']; ?> item(s)
                </div>
            </div>
            
            <div class="order-actions" style="text-align: right;">
                <a href="order_detail.php?order_id=<?php echo $order['id']; ?>" class="btn btn-outline-primary btn-sm">View Details</a>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    
    <?php endif; ?>
</div>

<style>
.status-pending { background: #fff3cd; color: #856404; }
.status-paid { background: #d1ecf1; color: #0c5460; }
.status-shipped { background: #d4edda; color: #155724; }
.status-delivered { background: #d1ecf1; color: #0c5460; }
.status-cancelled { background: #f8d7da; color: #721c24; }
</style>

<?php include 'footer.php'; ?>
