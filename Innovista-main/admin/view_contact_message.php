<?php
// view_contact_message.php
require_once 'admin_header.php'; // Ensures admin is logged in
require_once '../config/Database.php';

$db = new Database();
$conn = $db->getConnection();

$contact_id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);
$message_data = null;

if (!$contact_id || !is_numeric($contact_id)) {
    header("Location: manage_contacts.php?status=error&message=Invalid message ID.");
    exit();
}

$stmt = $conn->prepare("SELECT id, name, email, subject, message, created_at, is_read FROM contacts WHERE id = :id");
$stmt->bindParam(':id', $contact_id, PDO::PARAM_INT);
$stmt->execute();
$message_data = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$message_data) {
    header("Location: manage_contacts.php?status=error&message=Message not found.");
    exit();
}

// Mark message as read if it's unread
if ($message_data['is_read'] == 0) {
    $update_stmt = $conn->prepare("UPDATE contacts SET is_read = 1 WHERE id = :id");
    $update_stmt->bindParam(':id', $contact_id, PDO::PARAM_INT);
    $update_stmt->execute();
    $message_data['is_read'] = 1; // Update local variable
}
?>

<h2>Contact Message Details</h2>

<div class="content-card">
    <div class="detail-item">
        <strong>From:</strong> <?php echo htmlspecialchars($message_data['name']); ?>
    </div>
    <div class="detail-item">
        <strong>Email:</strong> <?php echo htmlspecialchars($message_data['email']); ?>
    </div>
    <div class="detail-item">
        <strong>Subject:</strong> <?php echo htmlspecialchars($message_data['subject']); ?>
    </div>
    <div class="detail-item">
        <strong>Received:</strong> <?php echo date('d M Y, H:i', strtotime($message_data['created_at'])); ?>
    </div>
    <div class="detail-item">
        <strong>Message:</strong>
        <p><?php echo nl2br(htmlspecialchars($message_data['message'])); ?></p>
    </div>

    <div class="action-buttons mt-3">
        <a href="manage_contacts.php" class="btn-link">Back to Messages</a>
        <a href="manage_contacts.php?id=<?php echo $message_data['id']; ?>&action=delete" class="btn-link delete" onclick="return confirm('Are you sure you want to delete this message?');">Delete Message</a>
    </div>
</div>

<?php require_once 'admin_footer.php'; ?>