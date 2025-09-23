<?php
/**
 * Notification Manager Class
 * 
 * Handles all notification operations for the Innovista platform
 * 
 * @author Innovista Development Team
 * @version 1.0
 * @since 2025-01-13
 */

require_once __DIR__ . '/../config/Database.php';

class NotificationManager {
    private $conn;
    private $table = 'notifications';

    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Create a new notification
     */
    public function createNotification($userId, $title, $message, $type = 'general', $priority = 'medium', $actionUrl = null, $relatedId = null, $relatedType = null) {
        $query = "INSERT INTO " . $this->table . " 
                 (user_id, title, message, type, priority, action_url, related_id, related_type) 
                 VALUES (:userId, :title, :message, :type, :priority, :actionUrl, :relatedId, :relatedType)";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
        $stmt->bindParam(':title', $title, PDO::PARAM_STR);
        $stmt->bindParam(':message', $message, PDO::PARAM_STR);
        $stmt->bindParam(':type', $type, PDO::PARAM_STR);
        $stmt->bindParam(':priority', $priority, PDO::PARAM_STR);
        $stmt->bindParam(':actionUrl', $actionUrl, PDO::PARAM_STR);
        $stmt->bindParam(':relatedId', $relatedId, PDO::PARAM_INT);
        $stmt->bindParam(':relatedType', $relatedType, PDO::PARAM_STR);
        
        return $stmt->execute();
    }

    /**
     * Get notifications for a specific user
     */
    public function getNotifications($userId, $limit = 20, $offset = 0, $type = null) {
        $query = "SELECT * FROM " . $this->table . " 
                 WHERE user_id = :userId";
        
        if ($type) {
            $query .= " AND type = :type";
        }
        
        $query .= " ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        
        if ($type) {
            $stmt->bindParam(':type', $type, PDO::PARAM_STR);
        }
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get unread notification count for a user
     */
    public function getUnreadCount($userId) {
        $query = "SELECT COUNT(*) as count FROM " . $this->table . " 
                 WHERE user_id = :userId AND is_read = 0";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    }

    /**
     * Mark a specific notification as read
     */
    public function markAsRead($notificationId, $userId) {
        $query = "UPDATE " . $this->table . " 
                 SET is_read = 1, read_at = NOW() 
                 WHERE id = :notificationId AND user_id = :userId";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':notificationId', $notificationId, PDO::PARAM_INT);
        $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
        
        return $stmt->execute();
    }

    /**
     * Mark all notifications as read for a user
     */
    public function markAllAsRead($userId) {
        $query = "UPDATE " . $this->table . " 
                 SET is_read = 1, read_at = NOW() 
                 WHERE user_id = :userId AND is_read = 0";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
        
        return $stmt->execute();
    }

    /**
     * Delete a notification
     */
    public function deleteNotification($notificationId, $userId) {
        $query = "DELETE FROM " . $this->table . " 
                 WHERE id = :notificationId AND user_id = :userId";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':notificationId', $notificationId, PDO::PARAM_INT);
        $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
        
        return $stmt->execute();
    }

    /**
     * Create notification for new quotation request
     */
    public function notifyNewQuotationRequest($providerId, $quotationData) {
        $title = "New Quotation Request";
        $message = "You have received a new quotation request for " . $quotationData['service_type'] . " services.";
        $actionUrl = "../provider/manage_quotations.php";
        
        return $this->createNotification(
            $providerId,
            $title,
            $message,
            'quotation',
            'high',
            $actionUrl,
            $quotationData['id'],
            'quotation'
        );
    }

    /**
     * Create notification for quotation approval
     */
    public function notifyQuotationApproved($customerId, $quotationData) {
        $title = "Quotation Approved";
        $message = "Your quotation for \"" . $quotationData['project_description'] . "\" has been approved by the customer.";
        $actionUrl = "../customer/view_quote.php?id=" . $quotationData['id'];
        
        return $this->createNotification(
            $customerId,
            $title,
            $message,
            'quotation',
            'high',
            $actionUrl,
            $quotationData['id'],
            'quotation'
        );
    }

    /**
     * Create notification for new quotation received by customer
     */
    public function notifyNewQuotationReceived($customerId, $quotationData) {
        $title = "New Quotation Received";
        $message = "You have received a new quotation from " . $quotationData['provider_name'] . " for " . $quotationData['service_type'] . " services. Amount: $" . number_format($quotationData['amount'], 2);
        $actionUrl = "../customer/view_quote.php?id=" . $quotationData['id'];
        
        return $this->createNotification(
            $customerId,
            $title,
            $message,
            'quotation',
            'high',
            $actionUrl,
            $quotationData['id'],
            'quotation'
        );
    }

    /**
     * Create notification for payment received
     */
    public function notifyPaymentReceived($userId, $paymentData) {
        $title = "Payment Received";
        $message = "Payment of Rs " . number_format($paymentData['amount'], 2) . " has been received for your project.";
        $actionUrl = "../provider/view_transactions.php";
        
        return $this->createNotification(
            $userId,
            $title,
            $message,
            'payment',
            'high',
            $actionUrl,
            $paymentData['id'],
            'payment'
        );
    }

    /**
     * Create notification for project updates
     */
    public function notifyProjectUpdate($userId, $projectData) {
        $title = "Project Update";
        $message = "Your project \"" . $projectData['project_description'] . "\" has been updated.";
        $actionUrl = "../customer/track_project.php?id=" . $projectData['id'];
        
        return $this->createNotification(
            $userId,
            $title,
            $message,
            'project',
            'medium',
            $actionUrl,
            $projectData['id'],
            'project'
        );
    }

    /**
     * Get notification icon based on type
     */
    public function getNotificationIcon($type) {
        $icons = [
            'quotation' => 'fas fa-file-invoice-dollar',
            'payment' => 'fas fa-credit-card',
            'project' => 'fas fa-tasks',
            'dispute' => 'fas fa-gavel',
            'system' => 'fas fa-cog',
            'general' => 'fas fa-bell'
        ];
        
        return $icons[$type] ?? $icons['general'];
    }

    /**
     * Format time ago
     */
    public function timeAgo($datetime) {
        $time = time() - strtotime($datetime);
        
        if ($time < 60) return 'just now';
        if ($time < 3600) return floor($time/60) . ' minutes ago';
        if ($time < 86400) return floor($time/3600) . ' hours ago';
        if ($time < 2592000) return floor($time/86400) . ' days ago';
        if ($time < 31536000) return floor($time/2592000) . ' months ago';
        
        return floor($time/31536000) . ' years ago';
    }

    /**
     * Create notification for quotation accepted by customer
     */
    public function notifyQuotationAccepted($providerId, $customerId, $quotationId, $serviceType) {
        $title = "Quotation Accepted";
        $message = "Your quotation for " . $serviceType . " services has been accepted by the customer.";
        $actionUrl = "../provider/manage_quotations.php";
        
        return $this->createNotification(
            $providerId,
            $title,
            $message,
            'quotation',
            'high',
            $actionUrl,
            $quotationId,
            'quotation'
        );
    }

    /**
     * Create notification for quotation rejected by customer
     */
    public function notifyQuotationRejected($providerId, $customerId, $quotationId, $serviceType, $reason = '') {
        $title = "Quotation Declined";
        $message = "Your quotation for " . $serviceType . " services has been declined by the customer.";
        if (!empty($reason)) {
            $message .= " Reason: " . $reason;
        }
        $actionUrl = "../provider/manage_quotations.php";
        
        return $this->createNotification(
            $providerId,
            $title,
            $message,
            'quotation',
            'medium',
            $actionUrl,
            $quotationId,
            'quotation'
        );
    }
}
?>
