
<?php
require_once '../config/session.php';
protectPage('provider');
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
$pageTitle = 'My Profile';
require_once '../provider/provider_header.php';
require_once '../config/Database.php';
$provider_id = $_SESSION['user_id'] ?? 0;
$db = (new Database())->getConnection();
$stmt = $db->prepare('SELECT * FROM service WHERE provider_id = :provider_id LIMIT 1');
$stmt->bindParam(':provider_id', $provider_id);
$stmt->execute();
$provider = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$provider) {
    $provider = [
        'provider_name' => '',
        'provider_email' => '',
        'provider_phone' => '',
        'provider_address' => '',
        'provider_bio' => '',
        'main_service' => '',
        'subcategories' => '',
        'portfolio' => ''
    ];
}
?>


<!-- Flash message for profile update -->
<?php if (function_exists('display_flash_message')): ?>
<div class="flash-message-container">
    <?php display_flash_message(); ?>
</div>
<?php endif; ?>


<div class="profile-page-wrapper">
    <h2 style="margin-bottom: 0.5rem;">Business Profile</h2>
    <p style="margin-bottom: 2rem; color: #666;">Keep your information up to date to attract more clients.</p>


    <form action="update_profile.php" method="POST" class="profile-form content-card business-info-form" style="margin-bottom: 2.5rem;">
        <h3 class="section-title">Business Information</h3>
        <div class="form-grid-pro" style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem 2.5rem; align-items: start;">
            <div class="form-group-pro">
                <label for="company_name">Full Name / Company Name <span class="required">*</span></label>
                <input type="text" id="company_name" name="company_name" value="<?php echo htmlspecialchars($provider['provider_name'] ?? ''); ?>" required autocomplete="organization" placeholder="e.g. Daniel Company or John Doe">
            </div>
            <div class="form-group-pro">
                <label for="email">Email</label>
                <div class="readonly-field" style="padding: 12px 16px; background-color: #f8f9fa; border: 1px solid #e9ecef; border-radius: 8px; color: #495057; font-weight: 500;">
                    <i class="fas fa-envelope" style="margin-right: 8px; color: #6c757d;"></i>
                    <?php echo htmlspecialchars($provider['provider_email'] ?? 'No email set'); ?>
                </div>
            </div>
            <div class="form-group-pro">
                <label for="phone">Phone Number</label>
                <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($provider['provider_phone'] ?? ''); ?>" autocomplete="tel" placeholder="e.g. 07XXXXXXXX">
            </div>
            <div class="form-group-pro">
                <label for="provider_address">Address</label>
                <textarea id="provider_address" name="provider_address" rows="2" placeholder="e.g. 123 Main St, City, Country"><?php echo htmlspecialchars($provider['provider_address'] ?? ''); ?></textarea>
            </div>
            <div class="form-group-pro" style="grid-column: 1 / -1;">
                <label for="provider_bio">Bio / Description</label>
                <textarea id="provider_bio" name="provider_bio" rows="4" placeholder="Describe your business, experience, or specialties..." style="min-height: 80px;"><?php echo htmlspecialchars($provider['provider_bio'] ?? ''); ?></textarea>
            </div>
        </div>
        <button type="submit" name="update_details" class="btn-submit-pro">Save Business Info</button>
    </form>



    <div class="services-section-pro">
        <h3 class="services-title-pro">My Services</h3>
        <p class="services-description-pro">Select the services you offer to help customers find you easily.</p>
        
        <form action="update_profile.php" method="POST" class="services-form-pro">
            <div class="services-main-pro">
                <label for="providerService" class="services-label-pro">Main Services</label>
                <div class="service-options-pro">
                    <label class="service-option-pro">
                        <input type="checkbox" name="providerService[]" value="Interior Design" <?php echo (strpos($provider['main_service'], 'Interior Design') !== false) ? 'checked' : ''; ?>>
                        <span class="service-icon-pro"><i class="fas fa-couch"></i></span>
                        <span class="service-text-pro">Interior Design</span>
                    </label>
                    <label class="service-option-pro">
                        <input type="checkbox" name="providerService[]" value="Painting" <?php echo (strpos($provider['main_service'], 'Painting') !== false) ? 'checked' : ''; ?>>
                        <span class="service-icon-pro"><i class="fas fa-paint-roller"></i></span>
                        <span class="service-text-pro">Painting</span>
                    </label>
                    <label class="service-option-pro">
                        <input type="checkbox" name="providerService[]" value="Restoration" <?php echo (strpos($provider['main_service'], 'Restoration') !== false) ? 'checked' : ''; ?>>
                        <span class="service-icon-pro"><i class="fas fa-hammer"></i></span>
                        <span class="service-text-pro">Restoration</span>
                    </label>
                </div>
            </div>
            
            <div class="subcategories-section-pro" id="providerSubcategories" style="display:none;">
                <label class="subcategories-label-pro">Specializations</label>
                <p class="subcategories-hint-pro">Select all specializations that apply to your services</p>
                <div id="subcategoryCheckboxes" class="subcategory-grid-pro"></div>
            </div>
            
            <button type="submit" name="update_services" class="btn-save-services-pro">
                <i class="fas fa-save"></i>
                Save Services
            </button>
        </form>
    </div>


    <script>
    // Subcategory options for each main service
    const subcategories = {
        "Interior Design": [
            "Ceiling & Lighting",
            "Space Planning",
            "Modular Kitchen",
            "Bathroom Design",
            "Carpentry & Woodwork",
            "Furniture Design"
        ],
        "Painting": [
            "Interior Painting",
            "Exterior Painting",
            "Water & Damp Proofing",
            "Commercial Painting",
            "Wall Art & Murals",
            "Color Consultation"
        ],
        "Restoration": [
            "Wall Repairs & Plastering",
            "Floor Restoration",
            "Door & Window Repairs",
            "Old Space Transformation",
            "Furniture Restoration",
            "Full Building Renovation"
        ]
    };
    
    document.addEventListener('DOMContentLoaded', function() {
        const serviceCheckboxes = document.querySelectorAll('input[name="providerService[]"]');
        const subcatContainer = document.getElementById('providerSubcategories');
        const subcatCheckboxes = document.getElementById('subcategoryCheckboxes');
        
        function updateSubcategories() {
            const selected = Array.from(serviceCheckboxes)
                .filter(cb => cb.checked)
                .map(cb => cb.value);
            
            subcatCheckboxes.innerHTML = '';
            
            if (selected.length > 0) {
                subcatContainer.style.display = 'block';
                
                selected.forEach(function(service) {
                    if (subcategories[service]) {
                        // Create section header for this service
                        const serviceHeader = document.createElement('div');
                        serviceHeader.className = 'service-category-header';
                        serviceHeader.textContent = service + ' Specializations:';
                        subcatCheckboxes.appendChild(serviceHeader);
                        
                        // Create checkboxes for each subcategory
                        subcategories[service].forEach(function(subcat) {
                            const id = 'subcat_' + service.replace(/\s+/g, '_') + '_' + subcat.replace(/\s+/g, '_');
                            const checkboxLabel = document.createElement('label');
                            checkboxLabel.className = 'subcategory-option';
                            
                            const checkbox = document.createElement('input');
                            checkbox.type = 'checkbox';
                            checkbox.name = 'providerSubcategories[]';
                            checkbox.value = service + ' - ' + subcat;
                            checkbox.id = id;
                            
                            // Pre-check if already selected
                            <?php if (!empty($provider['subcategories'])): ?>
                            if (<?php echo json_encode(explode(',', $provider['subcategories'])); ?>.includes(service + ' - ' + subcat)) {
                                checkbox.checked = true;
                            }
                            <?php endif; ?>
                            
                            checkboxLabel.appendChild(checkbox);
                            checkboxLabel.appendChild(document.createTextNode(' ' + subcat));
                            subcatCheckboxes.appendChild(checkboxLabel);
                        });
                    }
                });
            } else {
                subcatContainer.style.display = 'none';
                subcatCheckboxes.innerHTML = '';
            }
        }
        
        // Add event listeners to all service checkboxes
        serviceCheckboxes.forEach(function(checkbox) {
            checkbox.addEventListener('change', updateSubcategories);
        });
        
        // Initial call to show subcategories if services are already selected
        updateSubcategories();
    });
    </script>

    <!-- Portfolio section removed -->
    </div>


    <form action="../handlers/handle_update_password.php" method="POST" class="content-card password-form-pro">
        <h3 class="password-title-pro">Change Password</h3>
        <div class="password-grid-pro">
            <div class="password-group-pro">
                <label for="current_password">Current Password <span class="required">*</span></label>
                <input type="password" id="current_password" name="current_password" required autocomplete="current-password" placeholder="Enter current password">
            </div>
            <div class="password-group-pro">
                <label for="new_password">New Password <span class="required">*</span></label>
                <input type="password" id="new_password" name="new_password" required autocomplete="new-password" placeholder="Enter new password">
            </div>
            <div class="password-group-pro" style="grid-column: 1 / -1;">
                <label for="confirm_password">Confirm New Password <span class="required">*</span></label>
                <input type="password" id="confirm_password" name="confirm_password" required autocomplete="new-password" placeholder="Re-enter new password">
            </div>
        </div>
        <button type="submit" name="update_password" class="btn-password-pro">Update Password</button>
    </form>

    <link rel="stylesheet" href="../public/assets/css/my_profile.css">
</div>

<?php require_once '../includes/user_dashboard_footer.php'; ?>
