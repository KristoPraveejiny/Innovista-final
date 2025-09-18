<?php
require_once '../config/session.php';
require_once '../config/Database.php';

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

// Ensure user is logged in
if (!isUserLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit();
}

$user_id = getUserId();
$project_id = filter_input(INPUT_POST, 'project_id', FILTER_VALIDATE_INT);

if (!$project_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Project ID required']);
    exit();
}

$database = new Database();
$conn = $database->getConnection();
if (!$conn) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

try {
    // Mark all project-related notifications as read for this user
    $stmt = $conn->prepare("
        UPDATE notifications 
        SET is_read = 1, read_at = NOW() 
        WHERE user_id = :user_id 
        AND related_id = :project_id 
        AND related_type = 'project' 
        AND is_read = 0
    ");
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->bindParam(':project_id', $project_id, PDO::PARAM_INT);
    $stmt->execute();
    
    $affected_rows = $stmt->rowCount();
    
    echo json_encode([
        'success' => true, 
        'message' => 'Notifications marked as read',
        'affected_rows' => $affected_rows
    ]);
    
} catch (Exception $e) {
    error_log("Mark notifications read error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
?>
