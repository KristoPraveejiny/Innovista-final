<?php
// C:\xampp1\htdocs\Innovista-final\Innovista-main\includes\user_dashboard_header.php

// This file is included AFTER session.php and protectPage() has been called by the dashboard page itself.
// So, $_SESSION['user_id'], $_SESSION['user_name'], $_SESSION['user_role'] are guaranteed to be set
// and the user is confirmed to be a customer or admin.
// $pageTitle is expected to be set by the calling page (e.g., customer_dashboard.php).

// Ensure session and auth functions are available
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
if (!function_exists('isUserLoggedIn')) {
    require_once '../config/session.php'; // Defines basic session functions
}
if (!function_exists('getImageSrc')) {
    require_once '../public/session.php'; // Defines getImageSrc function
}
if (!function_exists('protectPage')) { // Define protectPage if not already defined (e.g., in session.php)
    function protectPage(string $requiredRole): void {
        if (!isUserLoggedIn()) {
            header("Location: ../public/login.php");
            exit();
        }
        if (getUserRole() !== $requiredRole && getUserRole() !== 'admin') { 
            set_flash_message('error', 'Access denied. You do not have permission to view this page.');
            header("Location: ../public/index.php");
            exit();
        }
    }
}
// This header is designed for customer-specific pages, so we protect it here.
// The individual dashboard pages will call protectPage('customer') before this include.

$loggedInUserRole = getUserRole(); // Get the actual role from session
$loggedInUserName = htmlspecialchars($_SESSION['user_name'] ?? 'Customer'); // Fallback name for display
$currentPage = basename($_SERVER['SCRIPT_NAME']); // e.g., 'customer_dashboard.php'

// Get user's profile image path for the header
$profile_image_path = 'assets/images/default-avatar.jpg'; // Default fallback

// Fetch from database
require_once '../config/Database.php';
$db_conn = (new Database())->getConnection();
$stmt_profile = $db_conn->prepare("SELECT profile_image_path FROM users WHERE id = :id");

$current_user_id = getUserId(); 
$stmt_profile->bindParam(':id', $current_user_id, PDO::PARAM_INT);
$stmt_profile->execute();
$profile_img_row = $stmt_profile->fetch(PDO::FETCH_ASSOC);

if ($profile_img_row && !empty($profile_img_row['profile_image_path'])) {
    $profile_image_path = $profile_img_row['profile_image_path'];
    error_log("Header Debug - Found profile image in DB: " . $profile_image_path);
} else {
    error_log("Header Debug - No profile image found in DB");
}

// Use getImageSrc function to get the proper image URL
$profile_image_path = getImageSrc($profile_image_path);

// Add cache-busting parameter to ensure fresh image loads
$profile_image_path .= '?v=' . time();

error_log("Header Debug - Final profile image path: " . $profile_image_path);


// --- Customer-specific navigation links ---
$navLinks = [
    ['href' => 'customer_dashboard.php', 'icon' => 'fas fa-tachometer-alt', 'text' => 'Dashboard'],
    
    ['href' => 'my_projects.php', 'icon' => 'fas fa-tasks', 'text' => 'My Projects'],
    ['href' => 'payment_history.php', 'icon' => 'fas fa-receipt', 'text' => 'Payments'],
    ['href' => 'my_orders.php', 'icon' => 'fas fa-box', 'text' => 'My Orders'], // updated icon
    ['href' => 'my_profile.php', 'icon' => 'fas fa-user-edit', 'text' => 'My Profile'],
    // Add more customer-specific links here
];


?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle ?? 'Customer Dashboard'); ?> - Innovista</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Dashboard Specific Stylesheets -->
    <link rel="stylesheet" href="../public/assets/css/main.css">
    <link rel="stylesheet" href="../public/assets/css/dashboard.css"> <!-- General dashboard layout, stats, tables -->
    <link rel="stylesheet" href="../public/assets/css/customer-dashboard.css"> <!-- Customer-specific overrides/colors -->
    <link rel="stylesheet" href="../public/assets/css/notifications.css">
    
    <!-- Add this before closing head tag -->
    <script defer src="../public/assets/js/notifications.js"></script>

</head>
<body>
    <div class="customer-dashboard-container">
        <aside class="dashboard-sidebar" id="dashboard-sidebar">
            <div class="sidebar-header">
                <!-- Path from includes/ to public/index.php -->
                <a href="../public/index.php" class="sidebar-logo">
                    <img src="../public/assets/images/logo1.png" alt="Innovista Logo">
                    <span>Innovista</span>
                </a>
            </div>
            <nav class="sidebar-nav">
                <?php foreach ($navLinks as $link): ?>
                    <?php
                        $linkFileName = basename($link['href']);
                        $isActive = ($currentPage === $linkFileName);
                    ?>
                    <a href="<?php echo htmlspecialchars($link['href']); ?>" class="nav-link <?php echo $isActive ? 'active' : ''; ?>">
                        <i class="<?php echo htmlspecialchars($link['icon']); ?>"></i><span><?php echo htmlspecialchars($link['text']); ?></span>
                    </a>
                <?php endforeach; ?>
            </nav>
            <div class="sidebar-footer">
                <a href="../public/logout.php" class="nav-link logout">
                    <i class="fas fa-sign-out-alt"></i><span>Logout</span>
                </a>
            </div>
        </aside>
        
        <div class="dashboard-content-wrapper">
            <header class="dashboard-main-header">
                <div class="header-left">
                    <button class="dashboard-menu-toggle" id="dashboard-menu-toggle">
                        <i class="fas fa-bars"></i>
                    </button>
                    <div class="user-welcome">
                        <span>Welcome, <?php echo $loggedInUserName; ?></span>
                        <a href="my_profile.php" title="View Profile">
                            <img src="<?php echo htmlspecialchars($profile_image_path); ?>" 
                                 alt="User Avatar" 
                                 class="dashboard-avatar-img"
                                 onerror="this.src='../public/assets/images/default-avatar.jpg?v=<?php echo time(); ?>'">
                        </a>
                    </div>
                </div>
                <div class="header-right" style="display: flex; align-items: center; gap: 1.5rem;">
                    <!-- Notification Bell -->
                    <?php include '../notifications/working_bell.php'; ?>
                </div>
                <!-- Notification JavaScript is handled by working_bell.php -->
            </header>
            <main class="dashboard-main-content">