<?php
require_once '../config/session.php';
require_once '../config/Database.php';
require_once '../classes/NotificationManager.php';

header('Content-Type: application/json');

// Check authentication
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

try {
    $db = (new Database())->getConnection();
    $notificationManager = new NotificationManager($db);

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        try {
            // Fetch notifications
            $notifications = $notificationManager->getUserNotifications($_SESSION['user_id']);
            $unreadCount = $notificationManager->getUnreadCount($_SESSION['user_id']);
            
            echo json_encode([
                'success' => true,
                'notifications' => $notifications,
                'unreadCount' => $unreadCount
            ]);
        } catch (PDOException $e) {
            echo json_encode(['error' => 'Failed to fetch notifications']);
            exit;
        }
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($data['action'])) {
                echo json_encode(['error' => 'Action is required']);
                exit;
            }
            
            if ($data['action'] === 'markAsRead') {
                if (!isset($data['notificationId'])) {
                    echo json_encode(['error' => 'Notification ID is required']);
                    exit;
                }
                
                // Mark single notification as read
                $success = $notificationManager->markAsRead(
                    $data['notificationId'],
                    $_SESSION['user_id']
                );
                echo json_encode(['success' => $success]);
            } elseif ($data['action'] === 'markAllAsRead') {
                // Mark all notifications as read
                $success = $notificationManager->markAllAsRead($_SESSION['user_id']);
                echo json_encode(['success' => $success]);
            } else {
                echo json_encode(['error' => 'Invalid action']);
                exit;
            }
        } catch (PDOException $e) {
            echo json_encode(['error' => 'Database operation failed']);
            exit;
        }
    } else {
        echo json_encode(['error' => 'Method not allowed']);
        exit;
    }
} catch (PDOException $e) {
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}
