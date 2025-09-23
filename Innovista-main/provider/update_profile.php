<?php
require_once '../config/session.php';
require_once '../config/Database.php';
protectPage('provider');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['update_details']) || isset($_POST['update_services']))) {
    $provider_id = $_SESSION['user_id'];
    $company_name = $_POST['company_name'] ?? '';
    // Email is now read-only, so we don't process it from POST data
    $phone = $_POST['phone'] ?? '';
    $bio = $_POST['bio'] ?? '';
    $provider_bio = $_POST['provider_bio'] ?? '';
    $main_service = '';
    $subcategories = '';

    $db = (new Database())->getConnection();

    if (isset($_POST['update_services'])) {
        // Fetch old values from DB
        $stmtOld = $db->prepare('SELECT main_service, subcategories FROM service WHERE provider_id = :provider_id');
        $stmtOld->bindParam(':provider_id', $provider_id);
        $stmtOld->execute();
        $old = $stmtOld->fetch(PDO::FETCH_ASSOC);
        $old_services = isset($old['main_service']) ? explode(',', $old['main_service']) : [];
        $old_subcategories = isset($old['subcategories']) ? explode(',', $old['subcategories']) : [];

        $new_services = isset($_POST['providerService']) ? $_POST['providerService'] : [];
        $new_subcategories = isset($_POST['providerSubcategories']) ? $_POST['providerSubcategories'] : [];

        // Merge, trim, and remove duplicates
        $all_services = array_unique(array_filter(array_map('trim', array_merge($old_services, $new_services))));
        $all_subcategories = array_unique(array_filter(array_map('trim', array_merge($old_subcategories, $new_subcategories))));

        $main_service = implode(',', $all_services);
        $subcategories = implode(',', $all_subcategories);
    }

    $db = (new Database())->getConnection();

    $updateUserTable = false;
    // If address (bio) is changed, update both tables (email is now read-only)
    if (isset($_POST['bio'])) {
        $updateUserTable = true;
    }

    // Always update service table
    if (isset($_POST['update_services'])) {
        $stmt = $db->prepare('UPDATE service SET main_service = :main_service, subcategories = :subcategories WHERE provider_id = :provider_id');
        $stmt->bindParam(':main_service', $main_service);
        $stmt->bindParam(':subcategories', $subcategories);
        $stmt->bindParam(':provider_id', $provider_id);
        $stmt->execute();
        set_flash_message('success', 'Services updated successfully!');
        header('Location: provider_dashboard.php');
        exit();
    } else {
        $stmt = $db->prepare('UPDATE service SET provider_name = :company_name, provider_phone = :phone, provider_address = :bio, provider_bio = :provider_bio WHERE provider_id = :provider_id');
        $stmt->bindParam(':company_name', $company_name);
        $stmt->bindParam(':phone', $phone);
        $stmt->bindParam(':bio', $bio);
        $stmt->bindParam(':provider_bio', $provider_bio);
        $stmt->bindParam(':provider_id', $provider_id);
        $stmt->execute();
    }

    // Update user table only if address (bio) is changed (email is now read-only)
    if ($updateUserTable) {
        $stmt2 = $db->prepare('UPDATE users SET name = :company_name WHERE id = :provider_id');
        $stmt2->bindParam(':company_name', $company_name);
        $stmt2->bindParam(':provider_id', $provider_id);
        $stmt2->execute();
    }

    set_flash_message('success', 'Profile updated successfully!');
    header('Location: provider_dashboard.php');
    exit();
} else {
    set_flash_message('error', 'Invalid request.');
    header('Location: provider_dashboard.php');
    exit();
}
