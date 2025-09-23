<?php
// Try different paths to find Database.php
if (file_exists('../config/Database.php')) {
    require_once '../config/Database.php';
} elseif (file_exists('config/Database.php')) {
    require_once 'config/Database.php';
} elseif (file_exists(__DIR__ . '/../config/Database.php')) {
    require_once __DIR__ . '/../config/Database.php';
} else {
    throw new Exception('Database.php file not found');
}

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
    private function insertNotification($userId, $title, $message, $type, $relatedId, $priority = 'medium', $actionUrl = null, $relatedType = null) {
        try {
            $stmt = $this->db->prepare(
                "INSERT INTO {$this->table} (user_id, title, message, type, priority, action_url, related_id, related_type, created_at)
                 VALUES (:user_id, :title, :message, :type, :priority, :action_url, :related_id, :related_type, NOW())"
            );
            return $stmt->execute([
                ':user_id' => $userId,
                ':title' => $title,
                ':message' => $message,
                ':type' => $type,
                ':priority' => $priority,
                ':action_url' => $actionUrl,
                ':related_id' => $relatedId,
                ':related_type' => $relatedType
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
        return $this->insertNotification($providerId, 'New Quotation Request', $message, 'quotation', $quotationId, 'high', null, 'quotation');
    }

    /** Notify all providers about a new quotation request */
    public function notifyNewQuotationRequestToAllProviders($quotationId, $customerId, $projectTitle) {
        $stmt = $this->db->prepare("SELECT name FROM users WHERE id = :id");
        $stmt->execute([':id' => $customerId]);
        $customerName = $stmt->fetchColumn();

        $providersStmt = $this->db->prepare("SELECT id FROM users WHERE role = 'provider'");
        $providersStmt->execute();
        $providers = $providersStmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($providers as $provider) {
            $this->insertNotification(
                $provider['id'],
                'New Quotation Request',
                "New quotation request from {$customerName} for project: {$projectTitle}",
                'quotation',
                $quotationId,
                'high',
                null,
                'quotation'
            );
        }
        return true;
    }

    /** Notify customer that provider submitted a quotation */
    public function notifyCustomerQuotationSubmitted($customerId, $providerId, $quotationId, $amount) {
        $stmt = $this->db->prepare("SELECT name FROM users WHERE id = :id");
        $stmt->execute([':id' => $providerId]);
        $providerName = $stmt->fetchColumn();

        $message = "{$providerName} has submitted a quotation for Rs{$amount}";
        return $this->insertNotification($customerId, 'New Quotation Received', $message, 'quotation', $quotationId, 'high', null, 'quotation');
    }

    /** Notify provider that customer accepted the quotation */
    public function notifyQuotationAccepted($providerId, $customerId, $quotationId, $projectTitle) {
        $stmt = $this->db->prepare("SELECT name FROM users WHERE id = :id");
        $stmt->execute([':id' => $customerId]);
        $customerName = $stmt->fetchColumn();

        $message = "{$customerName} has accepted your quotation for project: {$projectTitle}";
        return $this->insertNotification($providerId, 'Quotation Accepted', $message, 'quotation', $quotationId, 'high', null, 'quotation');
    }

    /** Notify provider that customer rejected the quotation */
    public function notifyQuotationRejected($providerId, $customerId, $quotationId, $projectTitle, $reason = '') {
        $stmt = $this->db->prepare("SELECT name FROM users WHERE id = :id");
        $stmt->execute([':id' => $customerId]);
        $customerName = $stmt->fetchColumn();

        $message = "{$customerName} has rejected your quotation for project: {$projectTitle}";
        if ($reason) $message .= ". Reason: {$reason}";

        return $this->insertNotification($providerId, 'Quotation Rejected', $message, 'quotation', $quotationId, 'medium', null, 'quotation');
    }

    /** Notify all customers about a new product */
    public function notifyAllCustomersAboutNewProduct($productId, $productName, $productCategory, $productPrice) {
        try {
            // Get all customers
            $customersStmt = $this->db->prepare("SELECT id FROM users WHERE role = 'customer' AND status = 'active'");
            $customersStmt->execute();
            $customers = $customersStmt->fetchAll(PDO::FETCH_ASSOC);

            $successCount = 0;
            $formattedPrice = 'Rs. ' . number_format($productPrice, 0, '.', ',');
            $actionUrl = "../public/product.php?service=interior-design&category={$productCategory}";
            
            foreach ($customers as $customer) {
                $title = 'New Product Available!';
                $message = "Check out our new {$productCategory} product: {$productName} - {$formattedPrice}";
                
                if ($this->insertNotification(
                    $customer['id'], 
                    $title, 
                    $message, 
                    'general', 
                    $productId,
                    'medium',
                    $actionUrl,
                    'product'
                )) {
                    $successCount++;
                }
            }
            
            error_log("Product notification sent to {$successCount} customers for product: {$productName}");
            return $successCount;
            
        } catch (Exception $e) {
            error_log("Failed to notify customers about new product: " . $e->getMessage());
            return false;
        }
    }
}
