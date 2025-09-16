<?php
// C:\xampp1\htdocs\Innovista-final\Innovista-main\public\order_confirmation.php

// Define the page title
$pageTitle = 'Order Confirmation'; 

// Include the master header, which also starts the session and provides isUserLoggedIn()
include 'header.php'; 
require_once '../handlers/flash_message.php'; // For display_flash_message()
require_once '../config/Database.php'; // For database connection

// Ensure the user is logged in
if (!isUserLoggedIn()) {
    header('Location: login.php?status=error&message=' . urlencode('You must be logged in to view your order confirmation.'));
    exit();
}

$order_id = filter_input(INPUT_GET, 'order_id', FILTER_VALIDATE_INT);
$order_data = null;
$order_items = [];

if ($order_id) {
    $database = new Database();
    $conn = $database->getConnection();
    $user_id = getUserId();

    try {
        // Fetch order details for the logged-in user, including new columns
        $stmt_order = $conn->prepare("SELECT id, order_date, total_amount, advance_amount, balance_due, status, payment_method, payment_terms, transaction_id, shipping_name, shipping_address, shipping_city, shipping_zip, shipping_phone, shipping_email, billing_name, billing_address, billing_city, billing_zip FROM orders WHERE id = :order_id AND user_id = :user_id");
        $stmt_order->bindParam(':order_id', $order_id, PDO::PARAM_INT);
        $stmt_order->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt_order->execute();
        $order_data = $stmt_order->fetch(PDO::FETCH_ASSOC);

        // Fetch order items if order found
        if ($order_data) {
            $stmt_items = $conn->prepare("SELECT * FROM order_items WHERE order_id = :order_id");
            $stmt_items->bindParam(':order_id', $order_id, PDO::PARAM_INT);
            $stmt_items->execute();
            $order_items = $stmt_items->fetchAll(PDO::FETCH_ASSOC);
        }

    } catch (PDOException $e) {
        error_log("Order Confirmation PDO Exception: " . $e->getMessage());
        set_flash_message('error', 'Error fetching order details. Please try again.');
        $order_data = null; // Ensure no data is displayed on error
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?> - Innovista</title>
    
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/header.css">
    <link rel="stylesheet" href="assets/css/footer.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
    
    <style>
        /* Basic inline styles for order confirmation - move to a dedicated CSS file */
        .confirmation-container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 2rem;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            text-align: center;
        }
        .confirmation-icon {
            font-size: 4rem;
            color: #28a745; /* Green for success */
            margin-bottom: 1.5rem;
        }
        .confirmation-container h1 {
            color: #333;
            margin-bottom: 1rem;
        }
        .confirmation-container p {
            color: #666;
            line-height: 1.6;
            margin-bottom: 1.5rem;
        }
        .order-summary-details {
            text-align: left;
            border-top: 1px solid #eee;
            padding-top: 1.5rem;
            margin-top: 1.5rem;
        }
        .order-summary-details h3 {
            font-size: 1.2rem;
            color: #333;
            margin-bottom: 1rem;
        }
        .order-summary-details ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .order-summary-details ul li {
            display: flex;
            justify-content: space-between;
            padding: 0.5rem 0;
            border-bottom: 1px dashed #eee;
        }
        .order-summary-details ul li:last-child {
            border-bottom: none;
        }
        .order-summary-details .total-row {
            font-weight: 700;
            font-size: 1.1rem;
            border-top: 1px solid #ccc;
            margin-top: 1rem;
            padding-top: 1rem;
        }
        .shipping-billing-info {
            text-align: left;
            margin-top: 2rem;
            border-top: 1px solid #eee;
            padding-top: 1.5rem;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
        }
        .shipping-billing-info div h4 {
            margin-top: 0;
            font-size: 1.1rem;
            color: #333;
        }
        .shipping-billing-info div p {
            margin: 0.2rem 0;
            font-size: 0.95rem;
        }
        .btn-back-home, .btn-view-orders {
            display: inline-block;
            padding: 0.8rem 1.5rem;
            margin-top: 2rem;
            border-radius: 5px;
            text-decoration: none;
            font-weight: 600;
            transition: background-color 0.3s ease;
        }
        .btn-back-home {
            background-color: #0d9488;
            color: white;
            margin-right: 1rem;
        }
        .btn-back-home:hover {
            background-color: #0a756b;
        }
        .btn-view-orders {
            background-color: #f0f0f0;
            color: #333;
            border: 1px solid #ccc;
        }
        .btn-view-orders:hover {
            background-color: #e0e0e0;
        }

        @media (max-width: 768px) {
            .confirmation-container {
                margin: 1rem;
                padding: 1rem;
            }
            .shipping-billing-info {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php 
    if (function_exists('display_flash_message')) {
        echo '<div class="flash-message-container">';
        display_flash_message();
        echo '</div>';
    }
    ?>

    <main class="container">
        <?php if ($order_data): ?>
            <div class="confirmation-container">
                <i class="fas fa-check-circle confirmation-icon"></i>
                <h1>Order Placed Successfully!</h1>
                <p>Thank you for your purchase. Your order #<?php echo htmlspecialchars($order_data['id']); ?> has been confirmed and is being processed.</p>
                <p>An email confirmation has been sent to <?php echo htmlspecialchars($order_data['shipping_email']); ?>.</p>

                <div class="order-summary-details">
                    <h3>Order Summary (ID: #<?php echo htmlspecialchars($order_data['id']); ?>)</h3>
                    <ul>
                        <?php foreach ($order_items as $item): ?>
                            <li>
                                <span><?php echo htmlspecialchars($item['product_name']); ?> (<?php echo htmlspecialchars($item['quantity']); ?>)</span>
                                <span>Rs. <?php echo htmlspecialchars(number_format($item['unit_price'] * $item['quantity'], 2)); ?></span>
                            </li>
                        <?php endforeach; ?>
                        <li class="total-row">
                            <span>Cart Total:</span>
                            <span>Rs. <?php echo htmlspecialchars(number_format($order_data['total_amount'], 2)); ?></span>
                        </li>
                        <li class="total-row" style="font-size: 1rem;">
                            <span>Advance Paid:</span>
                            <span>Rs. <?php echo htmlspecialchars(number_format($order_data['advance_amount'], 2)); ?></span>
                        </li>
                        <?php if ($order_data['balance_due'] > 0): ?>
                        <li class="total-row" style="font-size: 1rem; color: #dc3545;">
                            <span>Balance Due:</span>
                            <span>Rs. <?php echo htmlspecialchars(number_format($order_data['balance_due'], 2)); ?></span>
                        </li>
                        <?php endif; ?>
                    </ul>
                </div>

                <div class="shipping-billing-info">
                    <div>
                        <h4>Shipping Address</h4>
                        <p><?php echo htmlspecialchars($order_data['shipping_name']); ?></p>
                        <p><?php echo htmlspecialchars($order_data['shipping_address']); ?></p>
                        <p><?php echo htmlspecialchars($order_data['shipping_city']); ?>, <?php echo htmlspecialchars($order_data['shipping_zip']); ?></p>
                        <p>Phone: <?php echo htmlspecialchars($order_data['shipping_phone']); ?></p>
                        <p>Email: <?php echo htmlspecialchars($order_data['shipping_email']); ?></p>
                    </div>
                    <div>
                        <h4>Billing & Payment</h4>
                        <?php if ($order_data['billing_name']): ?>
                            <p><strong>Billing Name:</strong> <?php echo htmlspecialchars($order_data['billing_name']); ?></p>
                            <p><strong>Billing Address:</strong> <?php echo htmlspecialchars($order_data['billing_address']); ?></p>
                            <p><strong>Billing City:</strong> <?php echo htmlspecialchars($order_data['billing_city']); ?></p>
                            <p><strong>Billing Zip:</strong> <?php echo htmlspecialchars($order_data['billing_zip']); ?></p>
                        <?php else: ?>
                            <p>Billing address same as shipping.</p>
                        <?php endif; ?>
                        <h4 style="margin-top: 1rem;">Payment Method</h4>
                        <p><?php echo htmlspecialchars($order_data['payment_method'] === 'card' ? 'Credit/Debit Card' : 'Cash on Delivery'); ?></p>
                        <p><strong>Payment Terms:</strong> <?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $order_data['payment_terms']))); ?></p>
                        <?php if ($order_data['transaction_id']): ?>
                            <p>Transaction ID (Advance): <?php echo htmlspecialchars($order_data['transaction_id']); ?></p>
                        <?php endif; ?>
                        <?php if ($order_data['balance_due'] > 0): ?>
                        <p style="margin-top:1rem; font-weight:600; color:#dc3545;">
                            Balance of Rs. <?php echo htmlspecialchars(number_format($order_data['balance_due'], 2)); ?> due upon delivery.
                        </p>
                        <?php endif; ?>
                    </div>
                </div>

                <a href="index.php" class="btn-back-home">Back to Home</a>
                <a href="../customer/my_orders.php" class="btn-view-orders">View My Orders</a>

            </div>
        <?php else: ?>
            <div class="confirmation-container">
                <i class="fas fa-exclamation-circle confirmation-icon" style="color: #dc3545;"></i>
                <h1>Order Not Found</h1>
                <p>We could not find details for this order. It may have expired or is invalid.</p>
                <a href="index.php" class="btn-back-home">Back to Home</a>
            </div>
        <?php endif; ?>
    </main>

    <?php 
    // Include the master footer
    include 'footer.php'; 
    ?>
</body>
</html>