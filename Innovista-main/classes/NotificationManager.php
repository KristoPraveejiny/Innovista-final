<?php
require_once '../config/Database.php';

class NotificationManager {
    private $db;
    private $table = 'notifications';

    public function __construct($db) {
        $this->db = $db;
    }

    /** Fetch all notifications for a user, newest first */
    public function getUserNotifications($userId) {
        $query = "SELECT n.*, u.name AS sender_name
                  FROM " . $this->table . " n
                  LEFT JOIN users u ON u.id = n.sender_id
                  WHERE n.user_id = :user_id
                  ORDER BY n.created_at DESC";

        $stmt = $this->db->prepare($query);
        $stmt->execute([':user_id' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Count unread notifications for a user */
    public function getUnreadCount($userId) {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM {$this->table} WHERE user_id = :user_id AND is_read = 0");
        $stmt->execute([':user_id' => $userId]);
        return (int)$stmt->fetchColumn();
    }

    /** Mark a single notification as read */
    public function markAsRead($notificationId, $userId) {
        $stmt = $this->db->prepare("UPDATE {$this->table} SET is_read = 1 WHERE id = :id AND user_id = :user_id");
        return $stmt->execute([':id' => $notificationId, ':user_id' => $userId]);
    }

    /** Mark all notifications as read for a user */
    public function markAllAsRead($userId) {
        $stmt = $this->db->prepare("UPDATE {$this->table} SET is_read = 1 WHERE user_id = :user_id");
        return $stmt->execute([':user_id' => $userId]);
    }

    /** Insert a notification */
    private function insertNotification($userId, $title, $message, $type, $relatedId, $senderId = null) {
        try {
            $stmt = $this->db->prepare(
                "INSERT INTO {$this->table} (user_id, sender_id, title, message, type, related_id, created_at)
                 VALUES (:user_id, :sender_id, :title, :message, :type, :related_id, NOW())"
            );
            return $stmt->execute([
                ':user_id' => $userId,
                ':sender_id' => $senderId,
                ':title' => $title,
                ':message' => $message,
                ':type' => $type,
                ':related_id' => $relatedId
            ]);
        } catch (Exception $e) {
            error_log("Notification insert failed: " . $e->getMessage());
            return false;
        }
    }

    /** Notify a single provider about a new quotation request */
    public function notifyNewQuotationRequestToProvider($quotationId, $customerId, $providerId, $projectTitle) {
        $stmt = $this->db->prepare("SELECT name FROM users WHERE id = :id");
        $stmt->execute([':id' => $customerId]);
        $customerName = $stmt->fetchColumn();

        $message = "New quotation request from {$customerName} for project: {$projectTitle}";
        return $this->insertNotification($providerId, 'New Quotation Request', $message, 'quotation_request', $quotationId, $customerId);
    }

    /** Notify all providers about a new quotation request */
    public function notifyNewQuotationRequestToAllProviders($quotationId, $customerId, $projectTitle) {
        $stmt = $this->db->prepare("SELECT name FROM users WHERE id = :id");
        $stmt->execute([':id' => $customerId]);
        $customerName = $stmt->fetchColumn();

        $providersStmt = $this->db->prepare("SELECT id FROM users WHERE user_role = 'provider'");
        $providersStmt->execute();
        $providers = $providersStmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($providers as $provider) {
            $this->insertNotification(
                $provider['id'],
                'New Quotation Request',
                "New quotation request from {$customerName} for project: {$projectTitle}",
                'quotation_request',
                $quotationId,
                $customerId
            );
        }
        return true;
    }

    /** Notify customer that provider submitted a quotation */
    public function notifyCustomerQuotationSubmitted($customerId, $providerId, $quotationId, $amount) {
        $stmt = $this->db->prepare("SELECT name FROM users WHERE id = :id");
        $stmt->execute([':id' => $providerId]);
        $providerName = $stmt->fetchColumn();

        $message = "{$providerName} has submitted a quotation for RM{$amount}";
        return $this->insertNotification($customerId, 'New Quotation Received', $message, 'quotation_submitted', $quotationId, $providerId);
    }

    /** Notify provider that customer accepted the quotation */
    public function notifyQuotationAccepted($providerId, $customerId, $quotationId, $projectTitle) {
        $stmt = $this->db->prepare("SELECT name FROM users WHERE id = :id");
        $stmt->execute([':id' => $customerId]);
        $customerName = $stmt->fetchColumn();

        $message = "{$customerName} has accepted your quotation for project: {$projectTitle}";
        return $this->insertNotification($providerId, 'Quotation Accepted', $message, 'quotation_accepted', $quotationId, $customerId);
    }

    /** Notify provider that customer rejected the quotation */
    public function notifyQuotationRejected($providerId, $customerId, $quotationId, $projectTitle, $reason = '') {
        $stmt = $this->db->prepare("SELECT name FROM users WHERE id = :id");
        $stmt->execute([':id' => $customerId]);
        $customerName = $stmt->fetchColumn();

        $message = "{$customerName} has rejected your quotation for project: {$projectTitle}";
        if ($reason) $message .= ". Reason: {$reason}";

        return $this->insertNotification($providerId, 'Quotation Rejected', $message, 'quotation_rejected', $quotationId, $customerId);
    }
}
