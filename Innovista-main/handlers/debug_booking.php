<?php
// Debug version of consultation booking handler
require_once '../config/Database.php';
require_once '../public/session.php';

header('Content-Type: application/json');

try {
    // Step 1: Check user login
    if (!isUserLoggedIn()) {
        echo json_encode(['success' => false, 'message' => 'Not logged in', 'step' => 1]);
        exit();
    }
    
    $loggedInUserId = getUserId();
    
    // Step 2: Check database connection
    $database = new Database();
    $conn = $database->getConnection();
    
    // Step 3: Test table access
    $test_stmt = $conn->prepare("SELECT COUNT(*) FROM book_consultation LIMIT 1");
    $test_stmt->execute();
    $count = $test_stmt->fetchColumn();
    
    // Step 4: Get POST data
    $provider_id = $_POST['provider_id'] ?? null;
    $service_id = $_POST['service_id'] ?? 1;
    $booking_date = $_POST['booking_date'] ?? null;
    $booking_time = $_POST['booking_time'] ?? null;
    $amount = 500;
    
    // Step 5: Log received data
    error_log("Received data - provider_id: $provider_id, service_id: $service_id, date: $booking_date, time: $booking_time, user: $loggedInUserId");
    
    if (!$provider_id || !$booking_date || !$booking_time) {
        echo json_encode([
            'success' => false, 
            'message' => 'Missing data',
            'step' => 5,
            'data' => [
                'provider_id' => $provider_id,
                'booking_date' => $booking_date,
                'booking_time' => $booking_time
            ]
        ]);
        exit();
    }
    
    // Step 6: Convert time format if needed
    if (preg_match('/^(\d{1,2}):(\d{2})\s*(AM|PM)$/i', $booking_time, $matches)) {
        $hour = (int)$matches[1];
        $minute = $matches[2];
        $ampm = strtoupper($matches[3]);
        
        if ($ampm === 'AM') {
            if ($hour === 12) $hour = 0;
        } else {
            if ($hour !== 12) $hour += 12;
        }
        
        $booking_time = sprintf('%02d:%s', $hour, $minute);
    }
    
    // Step 7: Test insert
    $conn->beginTransaction();
    
    $stmt = $conn->prepare("
        INSERT INTO book_consultation 
        (customer_id, provider_id, service_id, date, time, amount_paid) 
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    
    $result = $stmt->execute([$loggedInUserId, $provider_id, $service_id, $booking_date, $booking_time, $amount]);
    
    if ($result) {
        $conn->commit();
        echo json_encode([
            'success' => true, 
            'message' => 'Booking successful!',
            'data' => [
                'customer_id' => $loggedInUserId,
                'provider_id' => $provider_id,
                'service_id' => $service_id,
                'date' => $booking_date,
                'time' => $booking_time,
                'amount' => $amount
            ]
        ]);
    } else {
        $conn->rollBack();
        $errorInfo = $stmt->errorInfo();
        echo json_encode([
            'success' => false, 
            'message' => 'Insert failed',
            'step' => 7,
            'error' => $errorInfo
        ]);
    }
    
} catch (PDOException $e) {
    if (isset($conn)) $conn->rollBack();
    echo json_encode([
        'success' => false, 
        'message' => 'Database error: ' . $e->getMessage(),
        'code' => $e->getCode(),
        'step' => 'PDO Exception'
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false, 
        'message' => 'General error: ' . $e->getMessage(),
        'step' => 'General Exception'
    ]);
}
?>