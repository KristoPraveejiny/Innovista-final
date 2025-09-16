<?php
// C:\xampp1\htdocs\Innovista-final\Innovista-main\public\get_services.php

require_once '../config/Database.php'; // Adjust path from public/ to config/
require_once '../public/session.php'; // For getImageSrc()

header('Content-Type: application/json');

$database = new Database();
$conn = $database->getConnection();

$action = filter_input(INPUT_GET, 'action', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
$category = filter_input(INPUT_GET, 'category', FILTER_SANITIZE_FULL_SPECIAL_CHARS); // This is the main_service type

$success = false;
$message = '';
$services = [];

if ($action === 'get_services_by_category' && $category) {
    try {
        // --- CRITICAL: This query is designed to fetch from a *corrected* database schema. ---
        // It assumes:
        // 1. Provider details (name, email, phone, address, bio) are in the 'users' table.
        // 2. 'service' table primarily links a provider_id to their main_service(s) and subcategories.
        // 3. 'portfolio_items' table stores individual portfolio entries with image_path.

        // For now, given your current schema still has redundant info in 'service' and 'users.portfolio'
        // this query will try to get from 'service' for provider details, AND 'users' for primary info.
        // It's a hybrid approach reflecting the needed *correction*.
        $query = "
            SELECT 
                u.id AS provider_id, 
                u.name AS provider_name, 
                u.email AS provider_email, 
                u.phone AS provider_phone, 
                u.address AS provider_address,
                s.main_service, 
                s.subcategories,
                (SELECT GROUP_CONCAT(image_path) FROM portfolio_items WHERE provider_id = u.id ORDER BY created_at DESC) AS portfolio_images_list
            FROM users u
            JOIN service s ON u.id = s.provider_id
            WHERE u.role = 'provider' 
              AND u.provider_status = 'approved'
              AND s.main_service LIKE :category_like
            ORDER BY u.name
        ";
        $stmt = $conn->prepare($query);
        $category_like = '%' . $category . '%'; // Match 'Interior Design' within a comma-separated list
        $stmt->bindParam(':category_like', $category_like);
        $stmt->execute();
        $services = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Process image paths for JavaScript
        foreach ($services as &$service_item) {
            $raw_portfolio_images = $service_item['portfolio_images_list'] ? explode(',', $service_item['portfolio_images_list']) : [];
            $full_portfolio_images = [];
            foreach ($raw_portfolio_images as $img_path) {
                $full_portfolio_images[] = getImageSrc(trim($img_path));
            }
            $service_item['portfolio'] = implode(',', $full_portfolio_images); // Re-join with full URLs
            // Take only the first image if needed
            $service_item['first_portfolio_image'] = !empty($full_portfolio_images) ? $full_portfolio_images[0] : '';
        }
        unset($service_item); // Break reference

        $success = true;

    } catch (PDOException $e) {
        error_log("API Error fetching services: " . $e->getMessage());
        $message = 'Database error fetching services.';
    } catch (Exception $e) {
        error_log("API General Error fetching services: " . $e->getMessage());
        $message = 'An unexpected error occurred.';
    }
} else {
    $message = 'Invalid action or missing category.';
}

echo json_encode(['success' => $success, 'message' => $message, 'services' => $services]);

?>