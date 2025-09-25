<?php
// A simple script to generate a secure password hash for your admin user.

// --- IMPORTANT: SET THE ADMIN PASSWORD YOU WANT TO USE HERE ---
$admin_password = 'DENU@jp59';

// Generate the secure hash using PHP's standard, secure method
$hashed_password = password_hash($admin_password, PASSWORD_BCRYPT);

// Display the hash and the exact SQL query you need to run
echo "<h1>Admin Password Reset Tool</h1>";
echo "<p>Use this page to reset the admin password.</p>";
echo "<hr>";
echo "<p><strong>Password to use:</strong> " . htmlspecialchars($admin_password) . "</p>";
echo "<p><strong>Your NEW SECURE Hashed Password is:</strong></p>";
echo "<textarea rows='3' cols='80' readonly style='font-size: 1rem;'>" . htmlspecialchars($hashed_password) . "</textarea>";
echo "<hr>";
echo "<h2>Run This SQL Query</h2>";
echo "<p>Copy the complete SQL query below and run it in the 'SQL' tab of phpMyAdmin for your 'innovista' database. This will update the admin's password.</p>";
echo "<pre style='background-color:#f0f0f0; padding:1rem; border:1px solid #ccc;'><code>";
echo "UPDATE `users` SET `password` = '" . $hashed_password . "' WHERE `email` = 'admin@innovista.com';";
echo "</code></pre>";

?>