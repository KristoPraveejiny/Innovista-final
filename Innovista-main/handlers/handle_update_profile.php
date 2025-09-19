<?php
// C:\xampp1\htdocs\Innovista-final\Innovista-main\handlers\handle_update_profile.php

require_once '../public/session.php';
require_once '../handlers/flash_message.php';
require_once '../config/Database.php';

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../public/index.php');
    exit();
}

// Debug: Log all incoming data
error_log("Profile Update Debug - REQUEST_METHOD: " . $_SERVER['REQUEST_METHOD']);
error_log("Profile Update Debug - Content-Type: " . ($_SERVER['CONTENT_TYPE'] ?? 'Not set'));
error_log("Profile Update Debug - POST data: " . print_r($_POST, true));
error_log("Profile Update Debug - FILES data: " . print_r($_FILES, true));

// Ensure user is logged in
if (!isUserLoggedIn()) {
    set_flash_message('error', 'You must be logged in to update your profile.');
    header('Location: ../public/login.php');
    exit();
}

$user_id = getUserId();
$name = filter_input(INPUT_POST, 'full_name', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
// Email is now read-only, so we don't process it from POST data
$phone = filter_input(INPUT_POST, 'phone', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
$address = filter_input(INPUT_POST, 'address', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
$bio = filter_input(INPUT_POST, 'bio', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

// Input Validation (email validation removed since it's read-only)
if (empty($name)) {
    set_flash_message('error', 'Full Name is required.');
    header('Location: ../customer/my_profile.php');
    exit();
}

$database = new Database();
$conn = $database->getConnection();

// Get current profile image path first
$current_image_stmt = $conn->prepare("SELECT profile_image_path FROM users WHERE id = :id");
$current_image_stmt->bindParam(':id', $user_id, PDO::PARAM_INT);
$current_image_stmt->execute();
$current_image_row = $current_image_stmt->fetch(PDO::FETCH_ASSOC);
$new_profile_image_path = $current_image_row['profile_image_path'] ?? null; // Keep current image if no new upload

try {
    $conn->beginTransaction();

    // 1. Get current user's profile_image_path from DB to handle deletion if needed
    $stmt_current_image = $conn->prepare("SELECT profile_image_path FROM users WHERE id = :id");
    $stmt_current_image->bindParam(':id', $user_id, PDO::PARAM_INT);
    $stmt_current_image->execute();
    $current_image_path = $stmt_current_image->fetchColumn();
    $new_profile_image_path = $current_image_path; // Assume current path unless new file uploaded

    // 2. Handle Profile Image Upload
    // Debug: Log file upload information
    error_log("Profile Upload Debug - Files array: " . print_r($_FILES, true));
    error_log("Profile Upload Debug - POST array: " . print_r($_POST, true));
    
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../uploads/user_profiles/'; // Relative to handlers/ - dedicated folder for user profiles
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
            error_log("Profile Upload Debug - Created upload directory: " . $upload_dir);
        }
        
        // Check if directory is writable
        if (!is_writable($upload_dir)) {
            error_log("Profile Upload Debug - ERROR: Upload directory is not writable: " . $upload_dir);
            set_flash_message('error', 'Upload directory is not writable. Please contact administrator.');
            header('Location: ../customer/my_profile.php');
            exit();
        } else {
            error_log("Profile Upload Debug - Upload directory is writable: " . $upload_dir);
        }
        $file_tmp = $_FILES['profile_image']['tmp_name'];
        $file_name = $_FILES['profile_image']['name'];
        $file_size = $_FILES['profile_image']['size'];
        $file_error = $_FILES['profile_image']['error'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $allowed_ext = ['jpg', 'jpeg', 'png', 'gif'];
        
        error_log("Profile Upload Debug - File details:");
        error_log("  - Original name: " . $file_name);
        error_log("  - Temp file: " . $file_tmp);
        error_log("  - File size: " . $file_size . " bytes");
        error_log("  - File error: " . $file_error);
        error_log("  - File extension: " . $file_ext);
        error_log("  - Temp file exists: " . (file_exists($file_tmp) ? 'YES' : 'NO'));

        if (in_array($file_ext, $allowed_ext)) {
            $new_file_name = 'profile_' . $user_id . '_' . uniqid() . '.' . $file_ext;
            $destination_path = $upload_dir . $new_file_name;
            $public_image_path_for_db = 'uploads/user_profiles/' . $new_file_name; // Path stored in DB (relative to public/)

            error_log("Profile Upload Debug - Attempting to move file:");
            error_log("  - From: " . $file_tmp);
            error_log("  - To: " . $destination_path);
            error_log("  - Destination directory exists: " . (is_dir(dirname($destination_path)) ? 'YES' : 'NO'));
            error_log("  - Destination directory writable: " . (is_writable(dirname($destination_path)) ? 'YES' : 'NO'));
            
            if (move_uploaded_file($file_tmp, $destination_path)) {
                error_log("Profile Upload Debug - move_uploaded_file() returned TRUE");
                
                // Verify file was actually moved
                if (file_exists($destination_path)) {
                    error_log("Profile Upload Debug - File confirmed to exist at: " . $destination_path);
                    error_log("Profile Upload Debug - File size: " . filesize($destination_path) . " bytes");
                } else {
                    error_log("Profile Upload Debug - ERROR: File does not exist after move_uploaded_file!");
                }
                
                // Delete old image if it was a local upload (not URL or default)
                if ($current_image_path &&
                    !filter_var($current_image_path, FILTER_VALIDATE_URL) &&
                    $current_image_path !== 'assets/images/default-avatar.jpg' &&
                    file_exists('../public/' . $current_image_path)) { // Path relative to handlers/
                    unlink('../public/' . $current_image_path);
                    error_log("Profile Upload Debug - Deleted old image: " . $current_image_path);
                }
                $new_profile_image_path = $public_image_path_for_db;
                error_log("Profile Upload Debug - File uploaded successfully to: " . $destination_path);
                error_log("Profile Upload Debug - New profile image path: " . $new_profile_image_path);
            } else {
                error_log("Profile Upload Debug - move_uploaded_file() returned FALSE");
                error_log("Profile Upload Debug - Error details:");
                error_log("  - Source file exists: " . (file_exists($file_tmp) ? 'YES' : 'NO'));
                error_log("  - Destination directory exists: " . (is_dir(dirname($destination_path)) ? 'YES' : 'NO'));
                error_log("  - Destination directory writable: " . (is_writable(dirname($destination_path)) ? 'YES' : 'NO'));
                error_log("  - Destination file already exists: " . (file_exists($destination_path) ? 'YES' : 'NO'));
                
                set_flash_message('error', 'Failed to upload new profile image. Check server logs for details.');
                header('Location: ../customer/my_profile.php');
                exit();
            }
        } else {
            set_flash_message('error', 'Invalid image file type. Only JPG, JPEG, PNG, GIF allowed.');
            header('Location: ../customer/my_profile.php');
            exit();
        }
    }

    // 3. Update user data (email is now read-only)
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
        // Update with new image path
        $stmt_update = $conn->prepare("
            UPDATE users SET 
                name = :name, 
                phone = :phone, 
                address = :address, 
                bio = :bio, 
                profile_image_path = :profile_image_path
            WHERE id = :id
        ");
        $stmt_update->bindParam(':profile_image_path', $new_profile_image_path);
    } else {
        // Update without changing image path
        $stmt_update = $conn->prepare("
            UPDATE users SET 
                name = :name, 
                phone = :phone, 
                address = :address, 
                bio = :bio
            WHERE id = :id
        ");
    }
    
    $stmt_update->bindParam(':name', $name);
    $stmt_update->bindParam(':phone', $phone);
    $stmt_update->bindParam(':address', $address);
    $stmt_update->bindParam(':bio', $bio);
    $stmt_update->bindParam(':id', $user_id, PDO::PARAM_INT);
    $stmt_update->execute();
    
    error_log("Profile Update Debug - Database updated with profile_image_path: " . $new_profile_image_path);

    // 4. Update session data if it was changed
    if ($_SESSION['user_name'] !== $name) {
        $_SESSION['user_name'] = $name;
    }
    
    // Update session with profile image path only if new image was uploaded
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
        $_SESSION['profile_image_path'] = $new_profile_image_path;
        error_log("Profile Update Debug - Updated session with new image path: " . $new_profile_image_path);
    }

    $conn->commit();
    set_flash_message('success', 'Profile updated successfully!');
    
    // Redirect back to profile page to show updated image
    header('Location: ../customer/my_profile.php?updated=1');
    exit();

} catch (PDOException $e) {
    $conn->rollBack();
    // Check for duplicate email error specifically
    if ($e->getCode() == '23000' && str_contains($e->getMessage(), 'email')) {
        set_flash_message('error', 'This email is already registered to another account.');
    } else {
        error_log("Update Profile PDO Exception: " . $e->getMessage());
        set_flash_message('error', 'A database error occurred while updating your profile. Please try again.');
    }
    header('Location: ../customer/my_profile.php');
    exit();
} catch (Exception $e) {
    $conn->rollBack();
    error_log("Update Profile General Exception: " . $e->getMessage());
    set_flash_message('error', 'An unexpected error occurred. Please try again later.');
    header('Location: ../customer/my_profile.php');
    exit();
}