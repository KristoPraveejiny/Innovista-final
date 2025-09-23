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

$order_id = $_GET['order_id'] ?? null;
if (!$order_id) {
    header('Location: index.php');
    exit();
}

$database = new Database();
$conn = $database->getConnection();

// Get order details
$stmt = $conn->prepare("
    SELECT o.*, u.name as user_name, u.email as user_email
    FROM orders o
    JOIN users u ON o.user_id = u.id
    WHERE o.id = ? AND o.user_id = ?
");
$stmt->execute([$order_id, $_SESSION['user_id']]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    header('Location: index.php');
    exit();
}

// Get order items
$stmt = $conn->prepare("
    SELECT * FROM order_items WHERE order_id = ?
");
$stmt->execute([$order_id]);
$order_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'Order Confirmation';
include 'header.php';
?>

<div class="container" style="max-width: 800px; margin: 50px auto; padding: 20px;">
    <div class="order-confirmation">
        <div class="success-icon" style="text-align: center; margin-bottom: 30px;">
            <i class="fas fa-check-circle" style="font-size: 64px; color: #28a745;"></i>
        </div>
        
        <h1 style="text-align: center; color: #28a745; margin-bottom: 20px;">Order Placed Successfully!</h1>
        
        <div class="order-details" style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 30px;">
            <h3>Order Details</h3>
            <p><strong>Order ID:</strong> #<?php echo $order['id']; ?></p>
            <p><strong>Order Date:</strong> <?php echo date('F j, Y g:i A', strtotime($order['created_at'])); ?></p>
            <p><strong>Status:</strong> <span style="color: #007bff;"><?php echo ucfirst($order['status']); ?></span></p>
            <p><strong>Total Amount:</strong> Rs. <?php echo number_format($order['total_amount'], 2); ?></p>
            <p><strong>Payment Method:</strong> <?php echo ucfirst($order['payment_method']); ?></p>
        </div>
        
        <div class="order-items" style="margin-bottom: 30px;">
            <h3>Order Items</h3>
            <?php foreach ($order_items as $item): ?>
            <div class="order-item" style="display: flex; align-items: center; padding: 15px; border: 1px solid #ddd; border-radius: 8px; margin-bottom: 10px;">
                <img src="<?php echo htmlspecialchars($item['image_path']); ?>" alt="<?php echo htmlspecialchars($item['product_name']); ?>" 
                     style="width: 80px; height: 80px; object-fit: cover; border-radius: 4px; margin-right: 15px;">
                <div>
                    <h4 style="margin: 0 0 5px 0;"><?php echo htmlspecialchars($item['product_name']); ?></h4>
                    <p style="margin: 0; color: #666;">Quantity: <?php echo $item['quantity']; ?></p>
                    <p style="margin: 0; color: #666;">Price: Rs. <?php echo number_format($item['unit_price'], 2); ?> each</p>
                    <?php if ($item['color']): ?>
                    <p style="margin: 0; color: #666;">Color: <?php echo htmlspecialchars($item['color']); ?></p>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <div class="shipping-info" style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 30px;">
            <h3>Shipping Address</h3>
            <p style="white-space: pre-line;"><?php echo htmlspecialchars($order['shipping_address']); ?></p>
        </div>
        
        <div class="actions" style="text-align: center;">
            <a href="index.php" class="btn btn-primary" style="margin-right: 10px;">Continue Shopping</a>
            <a href="my_orders.php" class="btn btn-secondary">View My Orders</a>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>