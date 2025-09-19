<?php
// C:\xampp1\htdocs\Innovista-final\Innovista-main\customer\my_profile.php

require_once '../public/session.php';
require_once '../handlers/flash_message.php';

// --- User-specific authentication function ---
if (!function_exists('protectPage')) {
    function protectPage(string $requiredRole): void {
        if (!isUserLoggedIn()) {
            header("Location: ../public/login.php");
            exit();
        }
        if (getUserRole() !== $requiredRole && getUserRole() !== 'admin') { 
            set_flash_message('error', 'Access denied. You do not have permission to view this page.');
            header("Location: ../public/index.php");
            exit();
        }
    }
}
protectPage('customer');

$pageTitle = 'My Profile';
require_once '../includes/user_dashboard_header.php';
require_once '../config/Database.php';

$customer_id = getUserId();
$database = new Database();
$conn = $database->getConnection();

// Debug: Log customer ID and session data
error_log("Profile Page Debug - Customer ID: " . $customer_id);
error_log("Profile Page Debug - Session data: " . print_r($_SESSION, true));

$currentUser = null;
$message = '';
$status_type = '';

// Fetch current user data (including profile_image_path for display)
try {
    // Force fresh data fetch if coming from update
    if (isset($_GET['updated']) && $_GET['updated'] == '1') {
        // Clear any potential caching
        $conn->query("SET SESSION query_cache_type = OFF");
        // Clear session profile image path to force fresh fetch
        unset($_SESSION['profile_image_path']);
        error_log("Profile Page Debug - Cleared session cache for fresh data fetch");
    }
    
    $stmt = $conn->prepare("SELECT id, name, email, phone, address, bio, profile_image_path FROM users WHERE id = :id");
    $stmt->bindParam(':id', $customer_id, PDO::PARAM_INT);
    $stmt->execute();
    $currentUser = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Debug: Log the fetched data
    error_log("Profile Page Debug - Fetched user data: " . print_r($currentUser, true));
    
    if ($currentUser && !empty($currentUser['profile_image_path'])) {
        error_log("Profile Page Debug - Profile image path from DB: " . $currentUser['profile_image_path']);
    } else {
        error_log("Profile Page Debug - No profile image path in DB");
    }
    
    // Additional debug: Show what we got
    if ($currentUser) {
        error_log("Profile Page Debug - User found: " . $currentUser['name']);
        error_log("Profile Page Debug - Profile image path: " . ($currentUser['profile_image_path'] ?? 'NULL'));
    } else {
        error_log("Profile Page Debug - No user data found!");
    }
    
    // Additional debug: Check if user exists at all
    if (!$currentUser) {
        error_log("Profile Page Debug - No user found with ID: " . $customer_id);
        
        // Try to find any user with this ID
        $debug_stmt = $conn->prepare("SELECT COUNT(*) as count FROM users WHERE id = :id");
        $debug_stmt->bindParam(':id', $customer_id, PDO::PARAM_INT);
        $debug_stmt->execute();
        $count = $debug_stmt->fetchColumn();
        error_log("Profile Page Debug - User count in database: " . $count);
    }

    if (!$currentUser) {
        set_flash_message('error', 'Your user account could not be found. Please contact support.');
        header("Location: ../public/logout.php");
        exit();
    }
} catch (PDOException $e) {
    error_log("Error fetching user profile data: " . $e->getMessage());
    $message = "Error loading your profile. Please try again.";
    $status_type = "error";
}

// Display messages that came from handlers
if (isset($_GET['status']) && isset($_GET['message'])) {
    $message = htmlspecialchars($_GET['message']);
    $status_type = htmlspecialchars($_GET['status']);
}

?>

<?php display_flash_message(); ?>

<h2>Manage My Profile</h2>
<p>Update your personal information and change your password.</p>

<div class="content-card">
    <h3>Personal Information</h3>
    <form action="../handlers/handle_update_profile.php" method="POST" class="form-section" enctype="multipart/form-data">
        <div class="profile-header-edit text-center mb-4">
            <?php 
            // Debug: Check if currentUser is set
            if (!$currentUser) {
                error_log("Profile Page Debug - currentUser is null!");
                $raw_image_path = 'assets/images/default-avatar.jpg';
            } else {
                $raw_image_path = $currentUser['profile_image_path'] ?? 'assets/images/default-avatar.jpg';
            }
            
            error_log("Profile Page Debug - Raw image path: " . $raw_image_path);
            $profile_img_src = getImageSrc($raw_image_path);
            error_log("Profile Page Debug - After getImageSrc: " . $profile_img_src);
            
            // Check if the file actually exists
            $file_path_to_check = '../' . $raw_image_path;
            if (str_starts_with($raw_image_path, 'uploads/')) {
                $file_path_to_check = '../' . $raw_image_path;
            } else {
                $file_path_to_check = '../public/' . $raw_image_path;
            }
            error_log("Profile Page Debug - Checking file existence at: " . $file_path_to_check);
            error_log("Profile Page Debug - File exists: " . (file_exists($file_path_to_check) ? 'YES' : 'NO'));
            
            $profile_img_src .= '?v=' . time(); // Add cache-busting
            error_log("Profile Page Debug - Final profile image source: " . $profile_img_src);
            ?>
            <?php if ($currentUser && !empty($currentUser['profile_image_path'])): ?>
                <img src="<?php echo htmlspecialchars($profile_img_src); ?>" 
                     alt="Profile Avatar" class="profile-avatar-lg mb-3"
                     onerror="this.src='../public/assets/images/default-avatar.jpg?v=<?php echo time(); ?>'">
            <?php else: ?>
                <div class="profile-avatar-lg mb-3" style="width: 150px; height: 150px; border-radius: 50%; background-color: #f0f0f0; display: flex; align-items: center; justify-content: center; margin: 0 auto; border: 3px solid #0d9488;">
                    <i class="fas fa-user" style="font-size: 60px; color: #666;"></i>
                </div>
            <?php endif; ?>
            <div class="form-group">
                <label for="profile_image" class="btn btn-secondary btn-sm" id="upload-label">Upload New Image</label>
                <input type="file" id="profile_image" name="profile_image" accept="image/*" style="display: none;">
                <small class="d-block text-muted mt-2">Max file size: 2MB. JPG, PNG, GIF. Leave blank to keep current.</small>
                <div id="file-info" style="margin-top: 10px; color: #666; font-size: 0.9em;"></div>
            </div>
        </div>

        <div class="form-grid">
            <div class="form-group">
                <label for="full_name">Full Name</label>
                <input type="text" id="full_name" name="full_name" value="<?php echo htmlspecialchars($currentUser['name'] ?? ''); ?>" required>
            </div>
            <div class="form-group">
                <label for="email">Email Address</label>
                <div class="readonly-field" style="padding: 12px 16px; background-color: #f8f9fa; border: 1px solid #e9ecef; border-radius: 8px; color: #495057; font-weight: 500;">
                    <i class="fas fa-envelope" style="margin-right: 8px; color: #6c757d;"></i>
                    <?php echo htmlspecialchars($currentUser['email'] ?? 'No email set'); ?>
                </div>
            </div>
        </div>
        <div class="form-group">
            <label for="phone">Phone Number</label>
            <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($currentUser['phone'] ?? ''); ?>">
        </div>
        <div class="form-group">
            <label for="address">Primary Address</label>
            <textarea id="address" name="address" rows="3"><?php echo htmlspecialchars($currentUser['address'] ?? ''); ?></textarea>
        </div>
        <div class="form-group">
            <label for="bio">Bio</label>
            <textarea id="bio" name="bio" rows="5" placeholder="Tell us a little about yourself..."><?php echo htmlspecialchars($currentUser['bio'] ?? ''); ?></textarea>
        </div>

        <button type="submit" name="update_details" class="btn-submit">Save Changes</button>
    </form>
</div>

<div class="content-card mt-4">
    <h3>Change Password</h3>
    <form action="../handlers/handle_update_password.php" method="POST" class="form-section">
        <div class="form-grid">
            <div class="form-group">
                <label for="current_password">Current Password</label>
                <input type="password" id="current_password" name="current_password" required>
            </div>
        </div>
        <div class="form-grid">
            <div class="form-group">
                <label for="new_password">New Password</label>
                <input type="password" id="new_password" name="new_password" required>
            </div>
            <div class="form-group">
                <label for="confirm_new_password">Confirm New Password</label>
                <input type="password" id="confirm_new_password" name="confirm_new_password" required>
            </div>
        </div>
        <button type="submit" name="update_password" class="btn-submit">Update Password</button>
    </form>
</div>

<?php require_once '../includes/user_dashboard_footer.php'; ?>

<!-- Add some basic styling to public/assets/css/dashboard.css or main.css -->
<style>
    /* These styles should be moved to your CSS file (e.g., public/assets/css/dashboard.css) */
    .profile-header-edit {
        text-align: center;
        margin-bottom: 2rem;
    }
    .profile-avatar-lg {
        width: 150px;
        height: 150px;
        border-radius: 50%;
        object-fit: cover;
        border: 3px solid var(--primary-color, #0d9488); /* Using CSS variable for primary color */
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        margin-bottom: 1rem;
    }
    .profile-header-edit .btn {
        margin-top: 0.5rem;
    }
</style>

<script>
// Handle profile image upload
document.addEventListener('DOMContentLoaded', function() {
    const fileInput = document.getElementById('profile_image');
    const profileImage = document.querySelector('.profile-avatar-lg');
    const uploadLabel = document.querySelector('label[for="profile_image"]');
    
    // Make the label clickable to trigger file input
    if (uploadLabel && fileInput) {
        uploadLabel.addEventListener('click', function(e) {
            e.preventDefault();
            fileInput.click();
        });
    }
    
    // Handle file selection and preview
    if (fileInput) {
        fileInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            const fileInfo = document.getElementById('file-info');
            
            if (file) {
                // Show file info
                fileInfo.innerHTML = `Selected: ${file.name} (${(file.size / 1024 / 1024).toFixed(2)} MB)`;
                
                // Validate file type
                const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
                if (!allowedTypes.includes(file.type)) {
                    fileInfo.innerHTML = '<span style="color: red;">Please select a valid image file (JPG, PNG, or GIF).</span>';
                    fileInput.value = '';
                    return;
                }
                
                // Validate file size (2MB = 2 * 1024 * 1024 bytes)
                if (file.size > 2 * 1024 * 1024) {
                    fileInfo.innerHTML = '<span style="color: red;">File size must be less than 2MB.</span>';
                    fileInput.value = '';
                    return;
                }
                
                // Show preview
                const reader = new FileReader();
                reader.onload = function(e) {
                    profileImage.src = e.target.result;
                    fileInfo.innerHTML = '<span style="color: green;">✓ Image ready for upload</span>';
                };
                reader.readAsDataURL(file);
            } else {
                fileInfo.innerHTML = '';
            }
        });
    }
});
</script>