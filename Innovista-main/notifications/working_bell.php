<?php
/**
 * Working Notification Bell
 * 
 * This version fetches real notifications from the database
 */

// Check if user is logged in by checking session directly
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    // echo "<!-- User not logged in -->";
    return;
}

$userId = $_SESSION['user_id'];

// Try to get real notifications
$notifications = [];
$unreadCount = 0;

try {
    require_once __DIR__ . '/../config/Database.php';
    $database = new Database();
    $db = $database->getConnection();
    
    // Get notifications
    $stmt = $db->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
    $stmt->execute([$userId]);
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get unread count
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0");
    $stmt->execute([$userId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $unreadCount = $result['count'] ?? 0;
    
} catch (Exception $e) {
    // If database fails, show default
    $unreadCount = 0;
    $notifications = [];
}

// echo "<!-- Working bell loaded for user: " . $userId . " -->";
?>

<!-- Working Notification Bell -->
<div class="notification-dropdown">
    <button class="notification-bell" id="notificationBell" onclick="toggleNotifications()">
        <i class="fas fa-bell"></i>
        <?php if ($unreadCount > 0): ?>
            <span class="notification-badge"><?php echo $unreadCount; ?></span>
        <?php endif; ?>
    </button>
    
    <div class="notification-panel" id="notificationPanel">
        <div class="notification-header">
            <h4>Notifications</h4>
            <?php if ($unreadCount > 0): ?>
                <button class="mark-all-read" onclick="markAllAsRead()">Mark all as read</button>
            <?php endif; ?>
        </div>
        
        <div class="notification-list" id="notificationList">
            <?php if (empty($notifications)): ?>
                <div class="notification-item no-notifications">
                    <i class="fas fa-bell-slash"></i>
                    <span>No notifications yet</span>
                </div>
            <?php else: ?>
                <?php foreach ($notifications as $notification): ?>
                    <div class="notification-item <?php echo $notification['is_read'] ? 'read' : 'unread'; ?>" 
                         data-id="<?php echo $notification['id']; ?>">
                        <div class="notification-icon">
                            <i class="fas fa-file-invoice-dollar"></i>
                        </div>
                        <div class="notification-content" onclick="markAsRead(<?php echo $notification['id']; ?>)">
                            <div class="notification-title"><?php echo htmlspecialchars($notification['title']); ?></div>
                            <div class="notification-message"><?php echo htmlspecialchars($notification['message']); ?></div>
                            <div class="notification-time"><?php echo timeAgo($notification['created_at']); ?></div>
                        </div>
                        <div class="notification-actions">
                            <button class="delete-btn" onclick="deleteNotification(<?php echo $notification['id']; ?>)" title="Delete">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                        <?php if (!$notification['is_read']): ?>
                            <div class="unread-indicator"></div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <div class="notification-footer">
            <button class="clear-all" onclick="clearAllNotifications()">Clear all</button>
        </div>
    </div>
</div>

<style>
.notification-dropdown {
    position: relative;
    display: inline-block;
}

.notification-bell {
    background: none;
    border: none;
    cursor: pointer;
    position: relative;
    padding: 8px;
    border-radius: 50%;
    transition: background-color 0.3s;
}

.notification-bell:hover {
    background-color: rgba(0, 0, 0, 0.1);
}

.notification-bell i {
    font-size: 1.2rem;
    color: #666;
}

.notification-badge {
    position: absolute;
    top: 0;
    right: 0;
    background: #e74c3c;
    color: white;
    border-radius: 50%;
    width: 18px;
    height: 18px;
    font-size: 0.7rem;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
}

.notification-panel {
    position: absolute;
    top: 100%;
    right: 0;
    width: 350px;
    max-height: 400px;
    background: white;
    border: 1px solid #ddd;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    z-index: 1000;
    display: none;
    overflow: hidden;
}

.notification-panel.show {
    display: block;
}

.notification-header {
    padding: 15px;
    border-bottom: 1px solid #eee;
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: #f8f9fa;
}

.notification-header h4 {
    margin: 0;
    font-size: 1rem;
    color: #333;
}

.mark-all-read {
    background: none;
    border: none;
    color: #007bff;
    cursor: pointer;
    font-size: 0.8rem;
    text-decoration: underline;
}

.notification-list {
    max-height: 300px;
    overflow-y: auto;
}

.notification-item {
    padding: 12px 15px;
    border-bottom: 1px solid #f0f0f0;
    transition: background-color 0.2s;
    position: relative;
    display: flex;
    align-items: flex-start;
    gap: 10px;
}

.notification-item:hover {
    background-color: #f8f9fa;
}

.notification-item.unread {
    background-color: #f0f8ff;
    border-left: 3px solid #007bff;
}

.notification-item.read {
    opacity: 0.8;
}

.notification-icon {
    flex-shrink: 0;
    width: 32px;
    height: 32px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #e9ecef;
    color: #666;
}

.notification-content {
    flex: 1;
    min-width: 0;
    cursor: pointer;
}

.notification-actions {
    display: flex;
    align-items: center;
    gap: 5px;
}

.delete-btn {
    background: none;
    border: none;
    color: #dc3545;
    cursor: pointer;
    padding: 4px;
    border-radius: 3px;
    transition: background-color 0.2s;
    opacity: 0.7;
}

.delete-btn:hover {
    background-color: #f8d7da;
    opacity: 1;
}

.delete-btn i {
    font-size: 0.8rem;
}

.notification-title {
    font-weight: 600;
    color: #333;
    margin-bottom: 4px;
    font-size: 0.9rem;
}

.notification-message {
    color: #666;
    font-size: 0.8rem;
    line-height: 1.4;
    margin-bottom: 4px;
    word-wrap: break-word;
}

.notification-time {
    color: #999;
    font-size: 0.7rem;
}

.unread-indicator {
    position: absolute;
    top: 50%;
    right: 10px;
    width: 8px;
    height: 8px;
    background: #007bff;
    border-radius: 50%;
    transform: translateY(-50%);
}

.no-notifications {
    text-align: center;
    color: #999;
    padding: 20px;
    flex-direction: column;
    gap: 10px;
}

.no-notifications i {
    font-size: 2rem;
    color: #ddd;
}

.notification-footer {
    padding: 10px 15px;
    border-top: 1px solid #eee;
    text-align: center;
    background: #f8f9fa;
}

.clear-all {
    background: #dc3545;
    color: white;
    border: none;
    padding: 6px 12px;
    border-radius: 4px;
    cursor: pointer;
    font-size: 0.8rem;
    transition: background-color 0.2s;
}

.clear-all:hover {
    background: #c82333;
}

/* Responsive */
@media (max-width: 768px) {
    .notification-panel {
        width: 300px;
        right: -50px;
    }
}
</style>

<script>
function toggleNotifications() {
    const panel = document.getElementById('notificationPanel');
    panel.classList.toggle('show');
}

function markAsRead(notificationId) {
    fetch('../notifications/api.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'mark_read',
            notification_id: notificationId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update UI
            const item = document.querySelector(`[data-id="${notificationId}"]`);
            if (item) {
                item.classList.remove('unread');
                item.classList.add('read');
                const indicator = item.querySelector('.unread-indicator');
                if (indicator) {
                    indicator.remove();
                }
            }
            updateNotificationCount();
        }
    });
}

function markAllAsRead() {
    fetch('../notifications/api.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'mark_all_read'
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update UI
            const items = document.querySelectorAll('.notification-item.unread');
            items.forEach(item => {
                item.classList.remove('unread');
                item.classList.add('read');
                const indicator = item.querySelector('.unread-indicator');
                if (indicator) {
                    indicator.remove();
                }
            });
            updateNotificationCount();
        }
    });
}

function deleteNotification(notificationId) {
    if (confirm('Are you sure you want to delete this notification?')) {
        fetch('../notifications/api.php', {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                notification_id: notificationId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Remove notification from UI
                const item = document.querySelector(`[data-id="${notificationId}"]`);
                if (item) {
                    item.remove();
                }
                updateNotificationCount();
                
                // Check if no notifications left
                const remainingItems = document.querySelectorAll('.notification-item:not(.no-notifications)');
                if (remainingItems.length === 0) {
                    const list = document.getElementById('notificationList');
                    list.innerHTML = '<div class="notification-item no-notifications"><i class="fas fa-bell-slash"></i><span>No notifications yet</span></div>';
                }
            }
        });
    }
}

function clearAllNotifications() {
    if (confirm('Are you sure you want to delete all notifications?')) {
        // Get all notification IDs
        const items = document.querySelectorAll('.notification-item[data-id]');
        const notificationIds = Array.from(items).map(item => item.getAttribute('data-id'));
        
        if (notificationIds.length === 0) {
            return;
        }
        
        // Delete all notifications
        Promise.all(notificationIds.map(id => 
            fetch('../notifications/api.php', {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    notification_id: id
                })
            })
        ))
        .then(responses => Promise.all(responses.map(r => r.json())))
        .then(results => {
            // Clear the notification list
            const list = document.getElementById('notificationList');
            list.innerHTML = '<div class="notification-item no-notifications"><i class="fas fa-bell-slash"></i><span>No notifications yet</span></div>';
            updateNotificationCount();
        });
    }
}

function updateNotificationCount() {
    fetch('../notifications/api.php?action=count')
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const badge = document.querySelector('.notification-badge');
            if (data.unreadCount > 0) {
                if (!badge) {
                    const bell = document.querySelector('.notification-bell');
                    const newBadge = document.createElement('span');
                    newBadge.className = 'notification-badge';
                    newBadge.textContent = data.unreadCount;
                    bell.appendChild(newBadge);
                } else {
                    badge.textContent = data.unreadCount;
                }
            } else {
                if (badge) {
                    badge.remove();
                }
            }
        }
    });
}

// Close dropdown when clicking outside
document.addEventListener('click', function(event) {
    const dropdown = document.querySelector('.notification-dropdown');
    const panel = document.getElementById('notificationPanel');
    
    if (!dropdown.contains(event.target) && panel.classList.contains('show')) {
        panel.classList.remove('show');
    }
});

// Auto-refresh notifications every 30 seconds
setInterval(updateNotificationCount, 30000);
</script>

<?php
// Helper function for time ago
function timeAgo($datetime) {
    $time = time() - strtotime($datetime);
    
    if ($time < 60) return 'just now';
    if ($time < 3600) return floor($time/60) . ' minutes ago';
    if ($time < 86400) return floor($time/3600) . ' hours ago';
    if ($time < 2592000) return floor($time/86400) . ' days ago';
    if ($time < 31536000) return floor($time/2592000) . ' months ago';
    
    return floor($time/31536000) . ' years ago';
}
?>
