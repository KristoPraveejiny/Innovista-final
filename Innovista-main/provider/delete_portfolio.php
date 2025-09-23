<?php
require_once '../config/session.php';
require_once '../config/Database.php';
protectPage('provider');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['photo'])) {
    $provider_id = $_SESSION['user_id'];
    $photo = basename($_POST['photo']);
    $db = (new Database())->getConnection();

    // Remove from service table
    $stmt = $db->prepare('SELECT portfolio FROM service WHERE provider_id = :provider_id');
    $stmt->bindParam(':provider_id', $provider_id);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $portfolio = isset($row['portfolio']) ? explode(',', $row['portfolio']) : [];
    $portfolio = array_filter($portfolio, function($item) use ($photo) { return $item !== $photo; });
    $portfolio_str = $portfolio ? implode(',', $portfolio) : '';
    $stmt2 = $db->prepare('UPDATE service SET portfolio = :portfolio WHERE provider_id = :provider_id');
    $stmt2->bindParam(':portfolio', $portfolio_str);
    $stmt2->bindParam(':provider_id', $provider_id);
    $stmt2->execute();

    // Remove from users table
    $stmt3 = $db->prepare('UPDATE users SET portfolio = :portfolio WHERE id = :provider_id');
    $stmt3->bindParam(':portfolio', $portfolio_str);
    $stmt3->bindParam(':provider_id', $provider_id);
    $stmt3->execute();

    // Optionally delete the file from disk
    $file_path = realpath(__DIR__ . '/../public/assets/images/' . $photo);
    if ($file_path && strpos($file_path, realpath(__DIR__ . '/../public/assets/images/')) === 0 && file_exists($file_path)) {
        unlink($file_path);
    }

    set_flash_message('success', 'Portfolio image deleted successfully!');
    $referer = $_SERVER['HTTP_REFERER'] ?? '';
    if (strpos($referer, 'my_profile.php') !== false) {
        header('Location: my_profile.php');
    } else {
        header('Location: manage_portfolio.php');
    }
    exit();
} else {
    set_flash_message('error', 'Invalid request.');
    $referer = $_SERVER['HTTP_REFERER'] ?? '';
    if (strpos($referer, 'my_profile.php') !== false) {
        header('Location: my_profile.php');
    } else {
        header('Location: manage_portfolio.php');
    }
    exit();
}
