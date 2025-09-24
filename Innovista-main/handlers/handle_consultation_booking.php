<?php
// Handle consultation booking payment
require_once '../config/Database.php';
require_once '../public/session.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isUserLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'You must be logged in to book a consultation.']);
    exit();
}

$loggedInUserId = getUserId();

// Get the PDO connection object
$database = new Database();
$conn = $database->getConnection();

function sendJsonResponse(bool $success, string $message, array $data = []): void {
    echo json_encode(array_merge(['success' => $success, 'message' => $message], $data));
    exit();
}

try {
    $conn->beginTransaction();
    
    // Test database connection and table access
    try {
        $test_stmt = $conn->prepare("SELECT COUNT(*) FROM book_consultation LIMIT 1");
        $test_stmt->execute();
        error_log("Database table access test successful");
    } catch (PDOException $e) {
        error_log("Database table access test failed: " . $e->getMessage());
        sendJsonResponse(false, 'Database table access error: ' . $e->getMessage());
    }
    
    // Get POST data
    $provider_id = filter_input(INPUT_POST, 'provider_id', FILTER_VALIDATE_INT);
    $service_id = filter_input(INPUT_POST, 'service_id', FILTER_VALIDATE_INT) ?: 1; // Default to 1 if not provided
    $booking_date = filter_input(INPUT_POST, 'booking_date', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $booking_time = filter_input(INPUT_POST, 'booking_time', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $amount = 500; // Fixed consultation fee
    $payment_method = filter_input(INPUT_POST, 'payment_method', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $card_number = filter_input(INPUT_POST, 'card_number', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $card_expiry = filter_input(INPUT_POST, 'card_expiry', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $card_cvc = filter_input(INPUT_POST, 'card_cvc', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $cardholder_name = filter_input(INPUT_POST, 'cardholder_name', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $zip_code = filter_input(INPUT_POST, 'zip_code', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

    // Input validation
    if (!$provider_id || empty($booking_date) || empty($booking_time) || empty($payment_method) || 
        empty($card_number) || empty($card_expiry) || empty($card_cvc) || empty($cardholder_name) || empty($zip_code)) {
        
        // Log what data was received for debugging
        error_log("Missing booking data - provider_id: $provider_id, booking_date: $booking_date, booking_time: $booking_time, payment_method: $payment_method");
        sendJsonResponse(false, 'Missing required booking details.');
    }

    // Convert time from 12-hour format (11:00 AM) to 24-hour format (11:00)
    if (preg_match('/^(\d{1,2}):(\d{2})\s*(AM|PM)$/i', $booking_time, $matches)) {
        $hour = (int)$matches[1];
        $minute = $matches[2];
        $ampm = strtoupper($matches[3]);
        
        // Convert to 24-hour format
        if ($ampm === 'AM') {
            if ($hour === 12) {
                $hour = 0; // 12:00 AM becomes 00:00
            }
        } else { // PM
            if ($hour !== 12) {
                $hour += 12; // Add 12 hours for PM (except 12:00 PM stays 12:00)
            }
        }
        
        // Format as HH:MM
        $booking_time = sprintf('%02d:%s', $hour, $minute);
    }

    // Validate date and time formats
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $booking_date)) {
        sendJsonResponse(false, 'Invalid date format. Expected YYYY-MM-DD.');
    }
    
    // Now validate the converted 24-hour format
    if (!preg_match('/^\d{2}:\d{2}$/', $booking_time)) {
        sendJsonResponse(false, 'Invalid time format after conversion. Received: ' . $booking_time);
    }

    // Validate card number (basic validation - should have proper Luhn validation in real app)
    $clean_card_number = preg_replace('/\D/', '', $card_number);
    if (strlen($clean_card_number) < 13 || strlen($clean_card_number) > 16) {
        sendJsonResponse(false, 'Invalid card number format.');
    }

    // Check if provider exists
    try {
        $stmt_provider = $conn->prepare("SELECT id, email FROM users WHERE id = :provider_id AND role = 'provider'");
        $stmt_provider->bindParam(':provider_id', $provider_id, PDO::PARAM_INT);
        $stmt_provider->execute();
        $provider = $stmt_provider->fetch(PDO::FETCH_ASSOC);

        if (!$provider) {
            error_log("Provider not found: provider_id=$provider_id");
            sendJsonResponse(false, 'Invalid provider selected.');
        }
    } catch (PDOException $e) {
        error_log("Provider check error: " . $e->getMessage());
        sendJsonResponse(false, 'Error validating provider.');
    }

    // Simulate payment processing (in real app, integrate with payment gateway)
    $transaction_id = 'CONS-' . uniqid();
    $payment_status = 'success'; // Simulate success

    if ($payment_status === 'success') {
        // Insert consultation booking record (using your existing table structure)
        try {
            $stmt_booking = $conn->prepare("
                INSERT INTO book_consultation 
                (customer_id, provider_id, service_id, date, time, amount_paid) 
                VALUES (:customer_id, :provider_id, :service_id, :date, :time, :amount_paid)
            ");
            
            $stmt_booking->bindParam(':customer_id', $loggedInUserId, PDO::PARAM_INT);
            $stmt_booking->bindParam(':provider_id', $provider_id, PDO::PARAM_INT);
            $stmt_booking->bindParam(':service_id', $service_id, PDO::PARAM_INT);
            $stmt_booking->bindParam(':date', $booking_date);
            $stmt_booking->bindParam(':time', $booking_time);
            $stmt_booking->bindParam(':amount_paid', $amount);
            $stmt_booking->execute();
        } catch (PDOException $e) {
            error_log("Consultation booking insertion error: " . $e->getMessage());
            error_log("Data: customer_id=$loggedInUserId, provider_id=$provider_id, service_id=$service_id, date=$booking_date, time=$booking_time, amount=$amount");
            $conn->rollBack();
            sendJsonResponse(false, 'Failed to save booking: ' . $e->getMessage());
        }

        // Add amount to provider earnings (optional - don't fail booking if this fails)
        try {
            $stmt_check_earnings = $conn->prepare("SELECT book_consult_earnings, total_earnings FROM provider_earnings WHERE provider_id = :provider_id");
            $stmt_check_earnings->bindParam(':provider_id', $provider_id, PDO::PARAM_INT);
            $stmt_check_earnings->execute();
            $earnings = $stmt_check_earnings->fetch(PDO::FETCH_ASSOC);

            if ($earnings) {
                // Update existing earnings - add to book_consult_earnings
                $stmt_update_earnings = $conn->prepare("
                    UPDATE provider_earnings 
                    SET book_consult_earnings = book_consult_earnings + :amount
                    WHERE provider_id = :provider_id
                ");
                $stmt_update_earnings->bindParam(':provider_id', $provider_id, PDO::PARAM_INT);
                $stmt_update_earnings->bindParam(':amount', $amount);
                $stmt_update_earnings->execute();
            } else {
                // Insert new earnings record with consultation amount
                $stmt_insert_earnings = $conn->prepare("
                    INSERT INTO provider_earnings 
                    (provider_id, total_earnings, book_consult_earnings) 
                    VALUES (:provider_id, 0.00, :amount)
                ");
                $stmt_insert_earnings->bindParam(':provider_id', $provider_id, PDO::PARAM_INT);
                $stmt_insert_earnings->bindParam(':amount', $amount);
                $stmt_insert_earnings->execute();
            }
        } catch (PDOException $e) {
            // Log error but don't fail the booking
            error_log("Provider earnings update failed (non-critical): " . $e->getMessage());
        }

        // Try to record payment transaction with consultation amount (optional - don't fail booking if this fails)
        try {
            $stmt_payment = $conn->prepare("
                INSERT INTO payments 
                (quotation_id, amount, book_consult_amount, payment_type, transaction_id, payment_date) 
                VALUES (:quotation_id, 0.00, :book_consult_amount, 'consultation', :transaction_id, NOW())
            ");
            $stmt_payment->bindParam(':quotation_id', $loggedInUserId, PDO::PARAM_INT);
            $stmt_payment->bindParam(':book_consult_amount', $amount);
            $stmt_payment->bindParam(':transaction_id', $transaction_id);
            $stmt_payment->execute();
        } catch (PDOException $e) {
            // Log error but don't fail the booking
            error_log("Payment recording failed (non-critical): " . $e->getMessage());
        }

        $conn->commit();
        
        // Return success with redirect URL
        sendJsonResponse(true, 'Consultation booked successfully! Redirecting to services page...', [
            'redirect_url' => '../public/services.php',
            'transaction_id' => $transaction_id,
            'booking_date' => $booking_date,
            'booking_time' => $booking_time
        ]);

    } else {
        $conn->rollBack();
        sendJsonResponse(false, 'Payment failed. Please try again.');
    }

} catch (PDOException $e) {
    $conn->rollBack();
    error_log("Consultation Booking PDO Exception: " . $e->getMessage());
    sendJsonResponse(false, 'A database error occurred. Please try again later.');
} catch (Exception $e) {
    $conn->rollBack();
    error_log("Consultation Booking Exception: " . $e->getMessage());
    sendJsonResponse(false, 'An unexpected error occurred. Please try again later.');
}
?>