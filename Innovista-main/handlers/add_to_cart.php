<?php
// C:\xampp1\htdocs\Innovista-final\Innovista-main\handlers\add_to_cart.php

require_once '../public/session.php'; // For session_start() and isUserLoggedIn()

header('Content-Type: application/json');

// Ensure user is logged in
if (!isUserLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'You must be logged in to manage your cart.']);
    exit();
}

// Initialize cart in session if it doesn't exist
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$action = $_REQUEST['action'] ?? ''; // Can be 'add', 'remove', 'get_cart', 'clear_cart'

// --- IMPORTANT: Implement CSRF Protection Here (HIGHLY RECOMMENDED) ---
// For POST requests, you would typically check a CSRF token.
// Example:
// if ($_SERVER['REQUEST_METHOD'] === 'POST' && !validate_csrf_token($_POST['csrf_token'])) {
//     echo json_encode(['success' => false, 'message' => 'Invalid CSRF token.']);
//     exit();
// }

try {
    switch ($action) {
        case 'add':
            $productId = filter_input(INPUT_POST, 'product_id', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
            $productName = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
            $productPrice = filter_input(INPUT_POST, 'price', FILTER_VALIDATE_FLOAT);
            $quantity = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT);
            $imagePath = filter_input(INPUT_POST, 'image_path', FILTER_SANITIZE_URL); 
            $color = filter_input(INPUT_POST, 'color', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
            $isUpdate = filter_input(INPUT_POST, 'is_update', FILTER_VALIDATE_BOOLEAN);

            if (!$productId || !$productName || $productPrice === false || $productPrice <= 0 || $quantity === false || $quantity <= 0) {
                echo json_encode(['success' => false, 'message' => 'Invalid product data provided.']);
                exit();
            }

            // Create a unique cart item ID if product can have different options (e.g., color)
            // This is crucial for painting colors where the 'product_id' is dynamically generated
            $cartItemId = $productId . ($color ? '-' . md5($color) : ''); 

            if ($isUpdate || !isset($_SESSION['cart'][$cartItemId])) {
                $_SESSION['cart'][$cartItemId] = [
                    'db_product_id' => $productId, // Store original product ID (or generated for paint)
                    'id' => $cartItemId, // Unique ID for cart item
                    'name' => $productName,
                    'price' => $productPrice,
                    'quantity' => $quantity,
                    'image_path' => $imagePath,
                    'color' => $color
                ];
            } else {
                $_SESSION['cart'][$cartItemId]['quantity'] += $quantity;
            }

            echo json_encode(['success' => true, 'message' => 'Product added to cart!', 'cart' => $_SESSION['cart']]);
            break;

        case 'remove':
            $cartItemId = filter_input(INPUT_POST, 'cart_item_id', FILTER_SANITIZE_FULL_SPECIAL_CHARS); // Expect cart item ID

            if (!$cartItemId || !isset($_SESSION['cart'][$cartItemId])) {
                echo json_encode(['success' => false, 'message' => 'Product not found in cart.']);
                exit();
            }

            unset($_SESSION['cart'][$cartItemId]);
            echo json_encode(['success' => true, 'message' => 'Product removed from cart!', 'cart' => $_SESSION['cart']]);
            break;

        case 'get_cart':
            // Return the current state of the cart
            echo json_encode(['success' => true, 'cart' => $_SESSION['cart']]);
            break;

        case 'clear_cart': // Optional: for clearing entire cart
            $_SESSION['cart'] = [];
            echo json_encode(['success' => true, 'message' => 'Cart cleared!', 'cart' => $_SESSION['cart']]);
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Invalid cart action.']);
            break;
    }
} catch (Exception $e) {
    error_log("Cart Handler Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An unexpected error occurred.']);
}

?>