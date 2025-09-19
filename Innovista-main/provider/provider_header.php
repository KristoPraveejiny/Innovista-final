<?php
    // Start session if not already started
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    
    // Include session functions if not already included
    if (!function_exists('isUserLoggedIn')) {
        require_once __DIR__ . '/../public/session.php';
    }
    
    $currentPage = basename($_SERVER['SCRIPT_NAME']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' - Innovista Provider' : 'Provider Dashboard - Innovista'; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../public/assets/css/dashboard.css">

    <style>
    /* Fallback flash message style for guaranteed visibility */
    .flash-message-container {
        position: fixed;
        top: 32px;
        left: 50%;
        transform: translateX(-50%);
        z-index: 9999;
        width: 100%;
        max-width: 420px;
        text-align: center;
        pointer-events: none;
    }
    .flash-message {
        padding: 1rem 1.5rem 1rem 1.5rem;
        border-radius: 10px;
        font-size: 1.13rem;
        font-weight: 600;
        margin: 0 auto 0.7rem auto;
        display: inline-block;
        box-shadow: 0 4px 24px rgba(0,0,0,0.13);
        letter-spacing: 0.5px;
        animation: fadeInScale 0.7s cubic-bezier(.4,2,.6,1) both;
        position: relative;
        pointer-events: auto;
        transition: opacity 0.5s;
        background: #e6f9ed;
        color: #1a7f4f;
        border: 1.5px solid #1a7f4f;
    }
    .flash-message.error {
        background: #fdeaea;
        color: #c0392b;
        border: 1.5px solid #c0392b;
    }
    .flash-close-btn {
        position: absolute;
        top: 8px;
        right: 12px;
        background: none;
        border: none;
        font-size: 1.2rem;
        cursor: pointer;
        color: inherit;
        opacity: 0.7;
        transition: color 0.2s;
    }
    .flash-close-btn:hover {
        opacity: 1;
    }
    .hide {
        opacity: 0;
        transform: scale(0.95) translateY(-10px);
        transition: opacity 0.5s;
    }
    @keyframes fadeInScale {
        0% { opacity: 0; transform: scale(0.95) translateY(-10px); }
        100% { opacity: 1; transform: scale(1) translateY(0); }
    }
    </style>
</head>
<body class="user-dashboard-body">
    <!-- Flash message for profile update (guaranteed visibility) -->
    <?php if (function_exists('display_flash_message')): ?>
    <div class="flash-message-container" id="flashMessageContainer">
        <?php ob_start(); display_flash_message(); $msg = ob_get_clean();
        if (trim($msg)) {
            // Add close button if message exists
            $msg = preg_replace('/(<div class=\'flash-message [^\']+\'>)/', '$1<button class=\"flash-close-btn\" onclick=\"closeFlashMessage(event)\">&times;</button>', $msg);
            echo $msg;
        }
        ?>
    </div>
    <script>
    function closeFlashMessage(e) {
        var msg = e.target.closest('.flash-message');
        if(msg) { msg.classList.add('hide'); setTimeout(function(){ msg.remove(); }, 500); }
    }
    window.addEventListener('DOMContentLoaded', function() {
        var msg = document.querySelector('.flash-message');
        if(msg) {
            setTimeout(function(){ msg.classList.add('hide'); setTimeout(function(){ msg.remove(); }, 500); }, 3500);
        }
    });
    </script>
    <?php endif; ?>
    
    <div class="user-dashboard-container">
        <aside class="dashboard-sidebar" id="sidebar">
            <div class="sidebar-header">
                <a href="../index.php" class="sidebar-logo">
                    <img src="../public/assets/images/logo1.png" alt="Logo">
                    <span>Innovista</span>
                </a>
            </div>
            <nav class="sidebar-nav">
                <a href="./provider_dashboard.php" class="nav-link <?php if($currentPage == 'provider_dashboard.php') echo 'active'; ?>"><i class="fas fa-tachometer-alt"></i><span>Dashboard</span></a>
                <a href="./manage_quotations.php" class="nav-link <?php if($currentPage == 'manage_quotations.php') echo 'active'; ?>"><i class="fas fa-file-invoice-dollar"></i><span>Quotations</span></a>
                <a href="./my_projects.php" class="nav-link <?php if($currentPage == 'my_projects.php') echo 'active'; ?>"><i class="fas fa-tasks"></i><span>My Projects</span></a>
                <a href="./manage_portfolio.php" class="nav-link <?php if($currentPage == 'manage_portfolio.php') echo 'active'; ?>"><i class="fas fa-images"></i><span>My Portfolio</span></a>
                <a href="./manage_calendar.php" class="nav-link <?php if($currentPage == 'manage_calendar.php') echo 'active'; ?>"><i class="fas fa-calendar-alt"></i><span>My Calendar</span></a>
                <a href="./view_transactions.php" class="nav-link <?php if($currentPage == 'view_transactions.php') echo 'active'; ?>"><i class="fas fa-receipt"></i><span>My Earnings</span></a>
                <a href="./my_profile.php" class="nav-link <?php if($currentPage == 'my_profile.php') echo 'active'; ?>"><i class="fas fa-user-edit"></i><span>My Profile</span></a>
            </nav>
            <div class="sidebar-footer">
                <a href="../public/logout.php" class="nav-link logout"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a>
            </div>
        </aside>
        
        <div class="dashboard-content-wrapper">
            <header class="dashboard-main-header">
                <div class="header-left">
                    <button class="dashboard-menu-toggle" id="dashboard-menu-toggle">
                        <i class="fas fa-bars"></i>
                    </button>
                    <div class="dashboard-user-profile">
                        <span>Welcome, <?php echo $_SESSION['user_name'] ?? 'Provider'; ?></span>
                        <!-- <i class="fas fa-user-circle dashboard-avatar-sm"></i> -->
                    </div>
                </div>
                <div class="header-right">
                    <!-- Notification Bell -->
                    <?php include '../notifications/working_bell.php'; ?>
                </div>
            </header>
            <main class="dashboard-main-content">