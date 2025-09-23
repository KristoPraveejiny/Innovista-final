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
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit();
}

$database = new Database();
$conn = $database->getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $conn->beginTransaction();
        
        // Get form data
        $user_id = $_SESSION['user_id'];
        $total_amount = floatval($_POST['cart_total_amount']);
        $shipping_address = trim($_POST['shipping_address']);
        $payment_method = $_POST['payment_method'];
        $quantity = intval($_POST['quantity']) ?: 1;
        $product_data = json_decode($_POST['product_data'], true);
        $otp_code = $_POST['otp_code'] ?? '';
        
        // Update product data with new quantity
        if ($product_data) {
            $product_data['quantity'] = $quantity;
        }
        
        // Debug: Log all received data
        error_log("Order Processing Debug:");
        error_log("shipping_address: " . ($shipping_address ?: 'EMPTY'));
        error_log("payment_method: " . ($payment_method ?: 'EMPTY'));
        error_log("product_data: " . ($product_data ? json_encode($product_data) : 'EMPTY'));
        error_log("otp_code: " . ($otp_code ?: 'EMPTY'));
        error_log("transaction_id: " . ($_POST['transaction_id'] ?? 'EMPTY'));
        error_log("POST data: " . json_encode($_POST));
        error_log("Session pending_otp_transaction: " . json_encode($_SESSION['pending_otp_transaction'] ?? 'EMPTY'));
        
        // Validate required fields
        if (empty($shipping_address) || empty($payment_method) || !$product_data || empty($otp_code)) {
            $missing = [];
            if (empty($shipping_address)) $missing[] = 'shipping_address';
            if (empty($payment_method)) $missing[] = 'payment_method';
            if (!$product_data) $missing[] = 'product_data';
            if (empty($otp_code)) $missing[] = 'otp_code';
            throw new Exception('Missing required information: ' . implode(', ', $missing));
        }

        // Verify OTP using quotation system approach
        $pending_otp_data = $_SESSION['pending_otp_transaction'] ?? null;
        
        if (!$pending_otp_data) {
            throw new Exception('No pending OTP transaction found. Please request OTP first.');
        }
        
        // Check if OTP is expired (5 minutes)
        if (time() > $pending_otp_data['otp_expires_at']) {
            unset($_SESSION['pending_otp_transaction']);
            throw new Exception('OTP has expired. Please request a new one.');
        }
        
        // Verify OTP
        if ($otp_code !== $pending_otp_data['otp_code']) {
            throw new Exception('Invalid OTP code');
        }
        
        // Create order
        $stmt = $conn->prepare("
            INSERT INTO orders (user_id, total_amount, status, shipping_address, payment_method, transaction_id)
            VALUES (?, ?, 'completed', ?, ?, ?)
        ");
        
        $transaction_id = 'TXN_' . time() . '_' . rand(1000, 9999);
        $stmt->execute([
            $user_id,
            $total_amount,
            $shipping_address,
            $payment_method,
            $transaction_id
        ]);
        
        $order_id = $conn->lastInsertId();
        
        // Add order items
        $stmt = $conn->prepare("
            INSERT INTO order_items (order_id, product_id, product_name, unit_price, quantity, color, image_path)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $order_id,
            $product_data['id'],
            $product_data['name'],
            $product_data['price'],
            $product_data['quantity'],
            $product_data['color'] ?? '',
            $product_data['image_path']
        ]);
        
        // Create payment record
        $stmt = $conn->prepare("
            INSERT INTO payments (quotation_id, amount, payment_type, transaction_id)
            VALUES (?, ?, 'full', ?)
        ");
        
        $stmt->execute([
            $order_id, // Using order_id as quotation_id since this is the existing table structure
            $total_amount,
            $transaction_id
        ]);
        
        $conn->commit();
        
        // Clear OTP session data after successful order
        unset($_SESSION['pending_otp_transaction']);
        
        // Set success message and redirect
        set_flash_message('success', 'Order placed successfully! Order ID: ' . $order_id);
        header('Location: ../customer/my_orders.php');
        exit();
        
    } catch (Exception $e) {
        $conn->rollBack();
        error_log("Order processing error: " . $e->getMessage());
        
        set_flash_message('error', 'Failed to process order: ' . $e->getMessage());
        header('Location: ../public/checkout.php');
        exit();
    }
} else {
    set_flash_message('error', 'Invalid request method');
    header('Location: ../public/checkout.php');
    exit();
}
?>
