<?php
// C:\xampp1\htdocs\Innovista-final\Innovista-main\public\api\get_products.php

require_once '../../config/Database.php'; // Path from public/api/ to config/
require_once '../../public/session.php'; // Path from public/api/ to public/

header('Content-Type: application/json');

$database = new Database();
$conn = $database->getConnection();

$service_type = filter_input(INPUT_GET, 'service_type', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
$category = filter_input(INPUT_GET, 'category', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
$product_id = filter_input(INPUT_GET, 'product_id', FILTER_VALIDATE_INT);
$brand_id = filter_input(INPUT_GET, 'brand_id', FILTER_VALIDATE_INT);

$products = [];
$success = false;
$message = '';

try {
    $query = "SELECT id, service_type, category, brand, name, description, price, image_path, rating_avg, review_count, badge, color_options 
              FROM products 
              WHERE is_active = 1";
    $params = [];

    if ($product_id) {
        $query .= " AND id = :product_id";
        $params[':product_id'] = $product_id;
    }

    if ($service_type) {
        $query .= " AND service_type = :service_type";
        $params[':service_type'] = $service_type;
    }

    if ($category && $category !== 'all') { // 'all' is a client-side filter, not a DB category
        $query .= " AND category = :category";
        $params[':category'] = $category;
    }

    // Special handling for painting brands if requested
    if ($service_type === 'Painting' && $category === 'Brand' && $brand_id) {
         // This query fetches details for a specific brand, including its color_options.
         // Note: Your current DB schema has color_options NULL for Painting Brands.
         // This assumes you will populate the color_options for paint brands in the DB.
         $query = "SELECT id, name, image_path, color_options, price FROM products WHERE id = :brand_id AND service_type = 'Painting' AND category = 'Brand' AND is_active = 1";
         $params = [':brand_id' => $brand_id];

    } elseif ($service_type === 'Painting' && $category === 'Brand') {
        // If just getting a list of all painting brands
        $query = "SELECT id, name, image_path FROM products WHERE service_type = 'Painting' AND category = 'Brand' AND is_active = 1 ORDER BY name";
        $params = []; 
    }


    $query .= " ORDER BY service_type, category, name";

    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Process color_options and image paths to be usable by JS
    foreach ($products as &$product) {
        if ($product['color_options']) {
            $product['color_options_parsed'] = json_decode($product['color_options'], true);
        } else {
            $product['color_options_parsed'] = [];
        }
        // Resolve image paths using getImageSrc for full public URL
        $product['image_path_full'] = getImageSrc($product['image_path']);
    }
    unset($product); // Break reference

    $success = true;

} catch (PDOException $e) {
    error_log("API Error fetching products: " . $e->getMessage());
    $message = 'Database error fetching products.';
} catch (Exception $e) {
    error_log("API General Error fetching products: " . $e->getMessage());
    $message = 'An unexpected error occurred.';
}

echo json_encode(['success' => $success, 'message' => $message, 'products' => $products]);

?>