<?php
require_once '../config/session.php';
require_once '../config/Database.php';
protectPage('provider');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $quotation_id = intval($_POST['quotation_id'] ?? 0);
    $amount = floatval($_POST['amount'] ?? 0);
    $advance = floatval($_POST['advance'] ?? 0);
    $start_date = $_POST['start_date'] ?? '';
    $end_date = $_POST['end_date'] ?? '';
    $validity = intval($_POST['validity'] ?? 0);
    $provider_notes = $_POST['provider_notes'] ?? '';

    $db = (new Database())->getConnection();
    // Fetch provider_id and customer_id from quotations table
    $stmt = $db->prepare('SELECT provider_id, customer_id, photos FROM quotations WHERE id = :id');
    $stmt->bindParam(':id', $quotation_id);
    $stmt->execute();
    $base = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$base) {
        echo '<div style="color:red;">Quotation not found.</div>';
        exit();
    }
    $provider_id = $base['provider_id'];
    $customer_id = $base['customer_id'];
    $photos = $base['photos'];

    $stmt = $db->prepare('INSERT INTO custom_quotations (quotation_id, provider_id, customer_id, amount, advance, start_date, end_date, validity, provider_notes, photos, status) VALUES (:quotation_id, :provider_id, :customer_id, :amount, :advance, :start_date, :end_date, :validity, :provider_notes, :photos, "sent")');
    $stmt->bindParam(':quotation_id', $quotation_id);
    $stmt->bindParam(':provider_id', $provider_id);
    $stmt->bindParam(':customer_id', $customer_id);
    $stmt->bindParam(':amount', $amount);
    $stmt->bindParam(':advance', $advance);
    $stmt->bindParam(':start_date', $start_date);
    $stmt->bindParam(':end_date', $end_date);
    $stmt->bindParam(':validity', $validity);
    $stmt->bindParam(':provider_notes', $provider_notes);
    $stmt->bindParam(':photos', $photos);
    $stmt->execute();

    // Update the status of the original quotation to 'quote_sent'
    $stmt = $db->prepare('UPDATE quotations SET status = "quote_sent" WHERE id = :id');
    $stmt->bindParam(':id', $quotation_id);
    $stmt->execute();

    // Send notification to customer about new quotation
    try {
        require_once '../notifications/NotificationManager.php';
        $notificationManager = new NotificationManager($db);
        
        // Get quotation details for notification
        $stmt = $db->prepare('SELECT service_type, project_description FROM quotations WHERE id = ?');
        $stmt->execute([$quotation_id]);
        $quotationDetails = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $notificationManager->notifyNewQuotationReceived($customer_id, [
            'id' => $quotation_id,
            'amount' => $amount,
            'service_type' => $quotationDetails['service_type'] ?? 'Service',
            'provider_name' => $_SESSION['user_name'] ?? 'Provider'
        ]);
    } catch (Exception $e) {
        // Log error but don't stop the process
        error_log("Failed to send notification to customer: " . $e->getMessage());
    }

    // Redirect to customer dashboard with a success message
    header('Location: ../customer/customer_dashboard.php?quote_sent=1&id=' . $quotation_id);
    exit();
} else {
    echo '<div style="color:red;">Invalid request.</div>';
}
