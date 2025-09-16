<?php
// C:\xampp1\htdocs\Innovista-final\Innovista-main\handlers\flash_message.php

// Ensure session is started, as flash messages rely on $_SESSION
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

/**
 * Sets a flash message to be displayed on the next page load.
 * @param string $type The type of message (e.g., 'success', 'error', 'info', 'warning').
 * @param string $message The message content.
 */
if (!function_exists('set_flash_message')) { // ADDED THIS CHECK
    function set_flash_message(string $type, string $message): void {
        $_SESSION['flash_message'] = [
            'type' => $type,
            'message' => $message
        ];
    }
}

/**
 * Displays any flash message set in the session and then clears it.
 * Should be called in the HTML where you want the message to appear.
 */
if (!function_exists('display_flash_message')) { // ADDED THIS CHECK
    function display_flash_message(): void {
        if (isset($_SESSION['flash_message'])) {
            $message = $_SESSION['flash_message']['message'];
            $type = $_SESSION['flash_message']['type'];
            
            // Output HTML for the alert. You can style these classes in your CSS.
            echo "<div class='alert alert-{$type}'>" . htmlspecialchars($message) . "</div>";
            
            // Clear the message so it only shows once
            unset($_SESSION['flash_message']);
        }
    }
}