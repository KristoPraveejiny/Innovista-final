<?php
// admin_header.php
session_start(); // MUST be the very first line of PHP in your script

// Basic authentication check
// Ensure user_id and user_role are set in session upon successful login
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    // Redirect to the login page if not logged in as admin
    header("Location: ../public/login.php");
    exit();
}

$admin_name = $_SESSION['user_name'] ?? 'Admin'; // Fetch admin's name from session if available

$currentPage = basename($_SERVER['SCRIPT_NAME']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Innovista Admin Panel</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- This path is corrected to go up one directory to the assets folder -->
    <link rel="stylesheet" href="../public/assets/css/admin.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="admin-container">
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <a href="admin_dashboard.php" class="sidebar-logo">
                    <!-- Use a white/light version of your logo for dark backgrounds -->
                    <img src="../public/assets/images/logo1.png" alt="Logo">
                    <span>Innovista</span>
                </a>
            </div>
            <nav class="sidebar-nav">
                <a href="admin_dashboard.php" class="nav-link <?php if($currentPage == 'admin_dashboard.php') echo 'active'; ?>">
                    <i class="fas fa-tachometer-alt"></i><span>Dashboard</span>
                </a>
                <a href="manage_providers.php" class="nav-link <?php if($currentPage == 'manage_providers.php') echo 'active'; ?>">
                    <i class="fas fa-user-check"></i><span>Provider Approvals</span>
                </a>
                <a href="manage_users.php" class="nav-link <?php if($currentPage == 'manage_users.php') echo 'active'; ?>">
                    <i class="fas fa-users-cog"></i><span>User Management</span>
                </a>
                <a href="manage_quotations.php" class="nav-link <?php if($currentPage == 'manage_quotations.php') echo 'active'; ?>">
                    <i class="fas fa-file-invoice"></i><span>Quotations</span>
                </a>
                <a href="manage_reviews.php" class="nav-link <?php if($currentPage == 'manage_reviews.php') echo 'active'; ?>">
                    <i class="fas fa-star"></i><span>Customer Reviews</span>
                </a>
                <a href="manage_contacts.php" class="nav-link <?php if($currentPage == 'manage_contacts.php') echo 'active'; ?>">
                    <i class="fas fa-envelope-open-text"></i><span>Contact Messages</span>
                </a>
                <a href="manage_portfolio_items.php" class="nav-link <?php if($currentPage == 'manage_portfolio_items.php') echo 'active'; ?>">
                    <i class="fas fa-images"></i><span>Portfolio Items</span>
                </a>
                <a href="manage_products.php" class="nav-link <?php if($currentPage == 'manage_products.php') echo 'active'; ?>">
                    <i class="fas fa-shopping-bag"></i><span>Manage Products</span>
                </a>
                <a href="reports.php" class="nav-link <?php if($currentPage == 'reports.php') echo 'active'; ?>">
                    <i class="fas fa-chart-bar"></i><span>Reports</span>
                </a>
                <a href="settings.php" class="nav-link <?php if($currentPage == 'settings.php') echo 'active'; ?>">
                    <i class="fas fa-cogs"></i><span>System Settings</span>
                </a>
            </nav>
            <div class="sidebar-footer">
                <!-- NEW: Back to Home Link -->
                <a href="../public/index.php" class="nav-link">
                    <i class="fas fa-home"></i><span>Back to Home</span>
                </a>
                <a href="../public/logout.php" class="nav-link logout">
                    <i class="fas fa-sign-out-alt"></i><span>Logout</span>
                </a>
            </div>
        </aside>
        
        <div class="content-wrapper">
            <header class="main-header-bar">
                <button class="menu-toggle" id="menu-toggle">
                    <i class="fas fa-bars"></i>
                </button>
                <div class="admin-profile">
                    <span>Welcome, <?php echo htmlspecialchars($admin_name); ?></span>
                    <i class="fas fa-user-circle"></i>
                </div>
            </header>
            <main class="main-content">