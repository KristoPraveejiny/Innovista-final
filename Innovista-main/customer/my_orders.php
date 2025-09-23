<?php
// C:\xampp1\htdocs\Innovista-final\Innovista-main\public\customer\my_orders.php

// Include session and protect the page FIRST
// Path from public/customer/my_orders.php to public/session.php
require_once '../config/session.php'; 
// flash_message.php is included by public/session.php, so no direct include here.

// The protectPage function is defined ONLY in public/session.php and is available globally after it's required.
protectPage('customer'); 

// Now, include all other necessary files
$pageTitle = 'My Orders';
// Path from public/customer/my_orders.php to includes/user_dashboard_header.php
require_once '../includes/user_dashboard_header.php'; 
// Path from public/customer/my_orders.php to config/Database.php
require_once '../config/Database.php';

// Get the customer ID from the session
$customer_id = getUserId();

$database = new Database();
$conn = $database->getConnection();

$orders = [];
$error_message = '';

try {
    // Fetch all orders with product information for the logged-in customer
    $stmt = $conn->prepare("
        SELECT 
            o.id, 
            o.created_at as order_date, 
            o.total_amount, 
            o.status, 
            o.payment_method,
            oi.product_name,
            oi.quantity
        FROM orders o
        LEFT JOIN order_items oi ON o.id = oi.order_id
        WHERE o.user_id = :user_id 
        ORDER BY o.created_at DESC
    ");
    $stmt->bindParam(':user_id', $customer_id, PDO::PARAM_INT);
    $stmt->execute();
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("My Orders PDO Exception: " . $e->getMessage());
    $error_message = 'Failed to load your orders due to a database error. Please try again.';
} catch (Exception $e) {
    error_log("My Orders General Exception: " . $e->getMessage());
    $error_message = 'An unexpected error occurred while loading orders.';
}

?>

<main class="dashboard-main-content">
    <?php 
    // display_flash_message() is available because session.php was required.
    if (function_exists('display_flash_message')) {
        echo '<div class="flash-message-container">';
        display_flash_message();
        echo '</div>';
    }
    ?>

    <h2>My Orders</h2>
    <p>View the history and status of your purchases.</p>

    <div class="content-card">
        <?php if ($error_message): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
        <?php elseif (empty($orders)): ?>
            <p class="text-center">You have not placed any orders yet.</p>
            <p class="text-center"><a href="../public/product.php" class="btn btn-primary">Start Shopping</a></p>
        <?php else: ?>
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <!-- <th>Order ID</th> -->
                            <th>Order Date</th>
                            <th>Product Name</th>
                            <th>Quantity</th>
                            <th>Total Amount</th>
                            <th>Payment Method</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order): ?>
                            <tr>
                                <td><?php echo htmlspecialchars(date('d M Y', strtotime($order['order_date']))); ?></td>
                                <td><?php echo htmlspecialchars($order['product_name'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($order['quantity'] ?? '1'); ?></td>
                                <td>Rs. <?php echo htmlspecialchars(number_format($order['total_amount'], 2)); ?></td>
                                <td><?php echo htmlspecialchars(ucfirst($order['payment_method'])); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo htmlspecialchars(strtolower(str_replace('_', '-', $order['status']))); ?>">
                                        <?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $order['status']))); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($order['status'] === 'advance_paid' && $order['balance_due'] > 0): ?>
                                        <a href="pay_balance.php?order_id=<?php echo htmlspecialchars($order['id']); ?>" class="btn-view" style="background-color:#0d9488; color:white;">Pay Now</a>
                                    <?php endif; ?>
                                    <a href="order_detail.php?id=<?php echo htmlspecialchars($order['id']); ?>" class="btn-view">View Details</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php 
// Include the user dashboard footer
// Path from public/customer/my_orders.php to includes/user_dashboard_footer.php
require_once '../includes/user_dashboard_footer.php'; 
?>