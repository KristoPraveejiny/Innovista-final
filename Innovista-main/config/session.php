<?php
// This is the most important line. It MUST be called before any HTML is output.
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

/**
 * Checks if a user is currently logged in by checking for the session user_id.
 * @return bool True if user is logged in, false otherwise.
 */
if (!function_exists('isUserLoggedIn')) {
    function isUserLoggedIn() {
        return isset($_SESSION['user_id']);
    }
}

/**
 * Returns the user's ID from the session.
 * @return int|null The user's ID or null if not logged in.
 */
if (!function_exists('getUserId')) {
    function getUserId(): ?int {
        return $_SESSION['user_id'] ?? null;
    }
}

/**
 * Returns the user's role from the session.
 * @return string|null The user's role (e.g., 'admin', 'customer', 'provider') or null if not logged in.
 */
if (!function_exists('getUserRole')) {
    function getUserRole(): ?string {
        return $_SESSION['user_role'] ?? null;
    }
}

/**
 * An authentication guard to protect pages.
 * It checks if a user is logged in and optionally if they have a specific role.
 * If the checks fail, it redirects them to the login page.
 * @param string|null $requiredRole The role required to access the page (e.g., 'customer', 'admin').
 */
function protectPage($requiredRole = null) {
    if (!isUserLoggedIn()) {
        // If the user is not logged in at all, redirect to login
        set_flash_message('error', 'You must be logged in to view that page.');
        header('Location: ../public/login.php');
        exit(); // Stop script execution immediately
    }

    if ($requiredRole !== null && $_SESSION['user_role'] !== $requiredRole) {
        // If the user is logged in, but their role does not match the required role
        set_flash_message('error', 'You do not have permission to access that page.');
        // Redirect them to their own dashboard or a safe page
        $dashboard_path = '../' . $_SESSION['user_role'] . '/' . $_SESSION['user_role'] . '_dashboard.php';
        header('Location: ' . $dashboard_path);
        exit(); // Stop script execution immediately
    }
}

/**
 * Sets a "flash message" that will be displayed on the next page load.
 * @param string $type The type of message (e.g., 'success', 'error').
 * @param string $message The message content to display.
 */
function set_flash_message($type, $message) {
    $_SESSION['flash_message'] = ['type' => $type, 'message' => $message];
}

/**
 * Displays the flash message if one exists, and then clears it from the session.
 */
function display_flash_message() {
    if (isset($_SESSION['flash_message'])) {
        $type = $_SESSION['flash_message']['type'];
        $message = $_SESSION['flash_message']['message'];
        
        // This HTML will be styled by the .flash-message class in your CSS
        echo "<div class='flash-message {$type}'>{$message}</div>";
        
        // Remove the message so it doesn't show again on the next page load
        unset($_SESSION['flash_message']);
    }
}
?>