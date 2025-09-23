<?php
// C:\xampp1\htdocs\Innovista-final\Innovista-main\public\customer\order_detail.php

// Include session and protect the page FIRST
// Path from public/customer/order_detail.php to public/session.php
require_once '../config/session.php'; 
// flash_message.php is included by public/session.php, so no direct include here.

// The protectPage function is defined ONLY in public/session.php and is available globally after it's required.
protectPage('customer'); 

// Now, include all other necessary files
$pageTitle = 'Order Details';
// Path from public/customer/order_detail.php to includes/user_dashboard_header.php
require_once '../includes/user_dashboard_header.php'; 
// Path from public/customer/order_detail.php to config/Database.php
require_once '../config/Database.php';

// Get the customer ID from the session
$customer_id = getUserId();

$database = new Database();
$conn = $database->getConnection();

$order_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$order_data = null;
$order_items = [];
$error_message = '';

if (!$order_id) {
    set_flash_message('error', 'Invalid Order ID provided.');
    header('Location: my_orders.php'); // Path is correct from customer/ to customer/
    exit();
}

try {
    // Fetch specific order details for the logged-in customer
    $stmt = $conn->prepare("SELECT id, created_at, total_amount, status, payment_method, transaction_id, shipping_address FROM orders WHERE id = :order_id AND user_id = :user_id");
    $stmt->bindParam(':order_id', $order_id, PDO::PARAM_INT);
    $stmt->bindParam(':user_id', $customer_id, PDO::PARAM_INT);
    $stmt->execute();
    $order_data = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order_data) {
        set_flash_message('error', 'Order not found or you do not have permission to view it.');
        header('Location: my_orders.php'); // Path is correct from customer/ to customer/
        exit();
    }

    // Fetch items for this order
    $stmt_items = $conn->prepare("SELECT * FROM order_items WHERE order_id = :order_id");
    $stmt_items->bindParam(':order_id', $order_id, PDO::PARAM_INT);
    $stmt_items->execute();
    $order_items = $stmt_items->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Order Details PDO Exception: " . $e->getMessage());
    $error_message = 'Failed to load order details due to a database error. Please try again.';
} catch (Exception $e) {
    error_log("Order Details General Exception: " . $e->getMessage());
    $error_message = 'An unexpected error occurred while loading order details.';
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

    <h2>Order Details: #<?php echo htmlspecialchars($order_data['id'] ?? 'N/A'); ?></h2>
    <p>Detailed breakdown of your order.</p>

    <?php if ($error_message): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
    <?php elseif (!$order_data): ?>
        <p class="text-center">Order not found.</p>
        <p class="text-center"><a href="my_orders.php" class="btn btn-primary">Back to My Orders</a></p>
    <?php else: ?>
        <div class="content-card">
            <div class="order-summary-header">
                <h3>Order #<?php echo htmlspecialchars($order_data['id']); ?></h3>
                <p>Date: <?php echo htmlspecialchars(date('d M Y, H:i', strtotime($order_data['created_at']))); ?></p>
                <p>Status: <span class="status-badge status-<?php echo htmlspecialchars(strtolower(str_replace('_', '-', $order_data['status']))); ?>"><?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $order_data['status']))); ?></span></p>
            </div>

            <div class="order-items-list" style="margin-top: 2rem;">
                <h3>Ordered Items</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Color</th>
                            <th>Unit Price</th>
                            <th>Quantity</th>
                            <th>Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($order_items)): ?>
                            <tr><td colspan="5" class="text-center">No items found for this order.</td></tr>
                        <?php else: ?>
                            <?php foreach ($order_items as $item): ?>
                                <tr>
                                    <td style="display:flex; align-items:center; gap:10px;">
                                        <?php 
                                        $originalPath = $item['image_path'];
                                        $imagePath = getImageSrc($originalPath);
                                        ?>
                                        
                                        <img src="<?php echo $imagePath; ?>" 
                                             alt="<?php echo htmlspecialchars($item['product_name']); ?>" 
                                             style="width:50px; height:50px; object-fit:cover; border-radius:4px;"
                                             onerror="this.src='../assets/images/placeholder.jpg'">
                                        <?php echo htmlspecialchars($item['product_name']); ?>
                                    </td>
                                    <td><?php echo !empty($item['color']) ? htmlspecialchars($item['color']) : 'N/A'; ?></td>
                                    <td>Rs. <?php echo htmlspecialchars(number_format($item['unit_price'], 2)); ?></td>
                                    <td><?php echo htmlspecialchars(number_format($item['quantity'], 2)); ?></td>
                                    <td>Rs. <?php echo htmlspecialchars(number_format($item['unit_price'] * $item['quantity'], 2)); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="shipping-billing-details" style="margin-top: 2rem; display:grid; grid-template-columns:1fr 1fr; gap:2rem;">
                <div>
                    <h3>Shipping Address</h3>
                    <p><strong>Address:</strong> <?php echo htmlspecialchars($order_data['shipping_address']); ?></p>
                </div>
                <div>
                    <h3>Payment Summary</h3>
                    <p><strong>Payment Method:</strong> <?php echo htmlspecialchars(ucfirst($order_data['payment_method'])); ?></p>
                    <?php if ($order_data['transaction_id']): ?>
                        <p><strong>Transaction ID:</strong> <?php echo htmlspecialchars($order_data['transaction_id']); ?></p>
                    <?php endif; ?>
                    <p style="margin-top:0.5rem;"><strong>Total Amount:</strong> Rs. <?php echo htmlspecialchars(number_format($order_data['total_amount'], 2)); ?></p>
                    <p><strong>Status:</strong> <span style="color: #28a745;"><?php echo htmlspecialchars(ucfirst($order_data['status'])); ?></span></p>
                </div>
            </div>

            <div class="action-buttons" style="margin-top: 2rem; text-align:right;">
                <a href="my_orders.php" class="btn btn-secondary">Back to My Orders</a>
                <!-- You could add a "Reorder" or "Download Invoice" button here -->
            </div>
        </div>
    <?php endif; ?>
</main>

<?php 
// Include the user dashboard footer
require_once '../includes/user_dashboard_footer.php'; 
?>