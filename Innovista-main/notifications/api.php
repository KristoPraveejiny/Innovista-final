<?php
/**
 * Notifications API
 * 
 * Handles all notification API requests
 * 
 * @author Innovista Development Team
 * @version 1.0
 * @since 2025-01-13
 */

require_once __DIR__ . '/../public/session.php';
require_once __DIR__ . '/NotificationManager.php';

// Check if user is logged in
if (!isUserLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$database = new Database();
$db = $database->getConnection();
$notificationManager = new NotificationManager($db);

$userId = getUserId();
$method = $_SERVER['REQUEST_METHOD'];

// Handle different HTTP methods
switch ($method) {
    case 'GET':
        handleGetRequest($notificationManager, $userId);
        break;
    case 'POST':
        handlePostRequest($notificationManager, $userId);
        break;
    case 'DELETE':
        handleDeleteRequest($notificationManager, $userId);
        break;
    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}

function handleGetRequest($notificationManager, $userId) {
    $action = $_GET['action'] ?? 'list';
    $limit = intval($_GET['limit'] ?? 20);
    $offset = intval($_GET['offset'] ?? 0);
    $type = $_GET['type'] ?? null;
    
    switch ($action) {
        case 'list':
            $notifications = $notificationManager->getNotifications($userId, $limit, $offset, $type);
            $unreadCount = $notificationManager->getUnreadCount($userId);
            
            echo json_encode([
                'success' => true,
                'notifications' => $notifications,
                'unreadCount' => $unreadCount,
                'pagination' => [
                    'limit' => $limit,
                    'offset' => $offset,
                    'hasMore' => count($notifications) === $limit
                ]
            ]);
            break;
            
        case 'count':
            $unreadCount = $notificationManager->getUnreadCount($userId);
            echo json_encode([
                'success' => true,
                'unreadCount' => $unreadCount
            ]);
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
}

function handlePostRequest($notificationManager, $userId) {
    $data = json_decode(file_get_contents('php://input'), true);
    $action = $data['action'] ?? '';
    
    switch ($action) {
        case 'mark_read':
            $notificationId = intval($data['notification_id'] ?? 0);
            $success = $notificationManager->markAsRead($notificationId, $userId);
            echo json_encode(['success' => $success]);
            break;
            
        case 'mark_all_read':
            $success = $notificationManager->markAllAsRead($userId);
            echo json_encode(['success' => $success]);
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
}

function handleDeleteRequest($notificationManager, $userId) {
    $data = json_decode(file_get_contents('php://input'), true);
    $notificationId = intval($data['notification_id'] ?? 0);
    
    if ($notificationId > 0) {
        $success = $notificationManager->deleteNotification($notificationId, $userId);
        echo json_encode(['success' => $success]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid notification ID']);
    }
}
?>
