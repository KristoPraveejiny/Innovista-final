<?php
// handle_contact.php - Contact form submission handler
require_once '../config/Database.php';

// Set JSON response header
header('Content-Type: application/json');

// Check if request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
    exit();
}

try {
    // Validate and sanitize input data
    $name = trim(filter_input(INPUT_POST, 'name', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
    $email = trim(filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL));
    $subject = trim(filter_input(INPUT_POST, 'subject', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
    $message = trim(filter_input(INPUT_POST, 'message', FILTER_SANITIZE_FULL_SPECIAL_CHARS));

    // Validation
    $errors = [];

    if (empty($name) || strlen($name) < 2) {
        $errors[] = 'Name must be at least 2 characters long';
    }

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please provide a valid email address';
    }

    if (empty($subject) || strlen($subject) < 3) {
        $errors[] = 'Subject must be at least 3 characters long';
    }

    if (empty($message) || strlen($message) < 10) {
        $errors[] = 'Message must be at least 10 characters long';
    }

    // If there are validation errors, return them
    if (!empty($errors)) {
        echo json_encode(['status' => 'error', 'message' => implode(', ', $errors)]);
        exit();
    }

    // Connect to database
    $db = new Database();
    $conn = $db->getConnection();

    // Create contacts table if it doesn't exist
    $createTableSQL = "
        CREATE TABLE IF NOT EXISTS contacts (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            email VARCHAR(150) NOT NULL,
            subject VARCHAR(200) NOT NULL,
            message TEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            is_read TINYINT(1) DEFAULT 0,
            INDEX idx_created_at (created_at),
            INDEX idx_is_read (is_read)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ";
    
    $conn->exec($createTableSQL);

    // Insert contact message into database
    $stmt = $conn->prepare("
        INSERT INTO contacts (name, email, subject, message, created_at, is_read) 
        VALUES (:name, :email, :subject, :message, NOW(), 0)
    ");

    $stmt->bindParam(':name', $name, PDO::PARAM_STR);
    $stmt->bindParam(':email', $email, PDO::PARAM_STR);
    $stmt->bindParam(':subject', $subject, PDO::PARAM_STR);
    $stmt->bindParam(':message', $message, PDO::PARAM_STR);

    if ($stmt->execute()) {
        echo json_encode([
            'status' => 'success', 
            'message' => 'Thank you for your message! We will get back to you soon.'
        ]);
    } else {
        echo json_encode([
            'status' => 'error', 
            'message' => 'Sorry, there was an error sending your message. Please try again.'
        ]);
    }

} catch (PDOException $e) {
    error_log("Contact form error: " . $e->getMessage());
    echo json_encode([
        'status' => 'error', 
        'message' => 'Sorry, there was a database error. Please try again later.'
    ]);
} catch (Exception $e) {
    error_log("Contact form error: " . $e->getMessage());
    echo json_encode([
        'status' => 'error', 
        'message' => 'Sorry, there was an unexpected error. Please try again.'
    ]);
}
?>