<?php
$pageTitle = 'Find a Professional';
include 'header.php'; 
require_once __DIR__ . '/../config/Database.php';
require_once '../public/session.php'; // Required for getImageSrc() and authentication helpers

// Ensure the user is authenticated (if necessary for features on this page)
// This page is publicly viewable, but specific actions like "Book Consultation" will redirect if not logged in.

// Get selected service from query string, e.g., ?service=Interior%20Design
$selectedService = isset($_GET['service']) ? filter_input(INPUT_GET, 'service', FILTER_SANITIZE_FULL_SPECIAL_CHARS) : '';
$selectedSubcategory = isset($_GET['subcategory']) ? filter_input(INPUT_GET, 'subcategory', FILTER_SANITIZE_FULL_SPECIAL_CHARS) : '';

$database = new Database();
$db = $database->getConnection();

$providers = [];
$debug = []; // For debugging messages

try {
    $query = "
        SELECT 
            u.id AS provider_id, 
            u.name AS provider_name, 
            u.email AS provider_email, 
            u.phone AS provider_phone, 
            u.address AS provider_address, 
            u.profile_image_path,
            s.main_service, 
            s.subcategories,
            -- Fetch portfolio images from portfolio_items, grouped
            (SELECT GROUP_CONCAT(pi.image_path) 
             FROM portfolio_items pi 
             WHERE pi.provider_id = u.id ORDER BY pi.created_at DESC) AS portfolio_images_list
        FROM users u
        JOIN service s ON u.id = s.provider_id
        WHERE u.role = 'provider' 
        AND u.provider_status = 'approved'
    ";
    $params = [];

    // Filter by selected main service
    if ($selectedService) {
        $selectedServiceNoSpace = strtolower(str_replace([' ', '-', '_'], '', $selectedService));
        $query .= " AND FIND_IN_SET(:service_filter, LOWER(REPLACE(s.main_service, ' ', '')))";
        $params[':service_filter'] = $selectedServiceNoSpace;
    }

    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $allProviders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Now, do client-side filtering for subcategories if needed, and process images
    $providers = [];
    foreach ($allProviders as $prov) {
        // Resolve profile image path
        $prov['profile_image_path_full'] = getImageSrc($prov['profile_image_path']);

        // Process portfolio images
        $raw_portfolio_images = $prov['portfolio_images_list'] ? explode(',', $prov['portfolio_images_list']) : [];
        $full_portfolio_images = [];
        foreach ($raw_portfolio_images as $img_path) {
            $full_portfolio_images[] = getImageSrc(trim($img_path));
        }
        $prov['portfolio_images_display'] = $full_portfolio_images; // Store for rendering

        // Subcategory filtering (if a subcategory is specifically selected)
        if ($selectedSubcategory) {
            $subcats = array_map('trim', explode(',', $prov['subcategories']));
            $subcatFound = false;
            $selectedSubcategoryNoSpace = strtolower(str_replace([' ', '-', '_'], '', $selectedSubcategory));

            foreach ($subcats as $subcat) {
                $subcatNoSpace = strtolower(str_replace([' ', '-', '_'], '', $subcat));
                // Check if the provider's subcategory (without main service prefix) matches the selected subcategory
                // Example: subcategory "Interior Design - Space Planning" vs selected "Space Planning"
                // This logic might need fine-tuning depending on how granular your subcategories are stored/matched.
                if (strpos($subcatNoSpace, $selectedSubcategoryNoSpace) !== false) {
                    $subcatFound = true;
                    break;
                }
            }
            if ($subcatFound) {
                $providers[] = $prov;
            }
        } else {
            // If no specific subcategory selected, add all providers for the main service
            $providers[] = $prov;
        }
    }
} catch (PDOException $e) {
    error_log("Database error in serviceprovider.php: " . $e->getMessage());
    $debug[] = 'Database Error: ' . htmlspecialchars($e->getMessage());
} catch (Exception $e) {
    error_log("General error in serviceprovider.php: " . $e->getMessage());
    $debug[] = 'General Error: ' . htmlspecialchars($e->getMessage());
}

?>

<header class="provider-page-header">
    <div class="container">
        <h1>Find Your Perfect Professional</h1>
        <p>Browse our list of verified experts to find the right match for your project.</p>
        <?php if (!empty($debug)): // Display debug messages if any ?>
            <div style="background:#fff8e1; color:#b45309; padding:1rem; margin:1rem 0; border-radius:6px;">
                <strong>Debug Info:</strong><br>
                <?php foreach ($debug as $msg) echo $msg . '<br>'; ?>
            </div>
        <?php endif; ?>
    </div>
</header>

<main class="provider-listing-layout container page-section">
    <?php // Flash message display (now correctly included via session.php)
    if (function_exists('display_flash_message')) {
        echo '<div class="flash-message-container">';
        display_flash_message();
        echo '</div>';
    }
    ?>
    <!-- Filters Sidebar -->
    <aside class="provider-filters">
        <!-- ... (Your filter HTML goes here, it was not provided for full analysis) ... -->
        <!-- Example: You can dynamically generate service/subcategory filters here -->
        <h3>Filter by Service</h3>
        <nav class="service-filter-nav">
            <a href="serviceprovider.php" class="filter-item <?php echo empty($selectedService) ? 'active' : ''; ?>">All Services</a>
            <a href="serviceprovider.php?service=Interior%20Design" class="filter-item <?php echo $selectedService === 'Interior Design' ? 'active' : ''; ?>">Interior Design</a>
            <a href="serviceprovider.php?service=Painting" class="filter-item <?php echo $selectedService === 'Painting' ? 'active' : ''; ?>">Painting</a>
            <a href="serviceprovider.php?service=Restoration" class="filter-item <?php echo $selectedService === 'Restoration' ? 'active' : ''; ?>">Restoration</a>
        </nav>
        <?php if ($selectedService && empty($selectedSubcategory)): // If a main service is selected, show subcategory filters ?>
            <h3 style="margin-top: 1.5rem;">Subcategories for <?php echo htmlspecialchars($selectedService); ?></h3>
            <nav class="subcategory-filter-nav">
                <?php
                // This would ideally fetch subcategories from a lookup table or a distinct query
                // For now, listing some common ones based on your hardcoded services.php
                $possibleSubcategories = [];
                switch ($selectedService) {
                    case 'Interior Design': $possibleSubcategories = ["Ceiling & Lighting", "Space Planning", "Modular Kitchen"]; break;
                    case 'Painting': $possibleSubcategories = ["Interior Painting", "Exterior Painting", "Color Consultation"]; break;
                    case 'Restoration': $possibleSubcategories = ["Wall Repairs & Plastering", "Floor Restoration", "Furniture Restoration"]; break;
                }
                foreach($possibleSubcategories as $subcat):
                    $link = "serviceprovider.php?service=".urlencode($selectedService)."&subcategory=".urlencode($subcat);
                ?>
                    <a href="<?php echo htmlspecialchars($link); ?>" class="filter-item <?php echo $selectedSubcategory === $subcat ? 'active' : ''; ?>"><?php echo htmlspecialchars($subcat); ?></a>
                <?php endforeach; ?>
            </nav>
        <?php endif; ?>
    </aside>

    <!-- Provider List -->
    <section class="provider-list-container">
        <?php if (empty($providers)): ?>
            <div style="padding:2rem; text-align:center; color:#888; font-size:1.2rem; background:#f9f9f9; border-radius:8px;">No professionals found for this service and subcategory.</div>
        <?php else: ?>
            <?php foreach ($providers as $provider): ?>
            <div class="provider-card-list">
                <div class="provider-profile-image">
                    <img src="<?php echo htmlspecialchars($provider['profile_image_path_full'] ?? 'assets/images/default-avatar.jpg'); ?>" alt="<?php echo htmlspecialchars($provider['provider_name']); ?>" style="width:100px; height:100px; object-fit:cover; border-radius:50%; border:2px solid #eee;">
                </div>
                <div class="provider-details">
                    <h3 class="provider-name">
                        <?php echo htmlspecialchars($provider['provider_name']); ?>
                        <i class="fas fa-check-circle verified-badge" title="Verified Provider"></i>
                    </h3>
                    <div class="service-tags-list">
                        <?php
                        // Show all main services for this provider
                        $mainServices = array_map('trim', explode(',', $provider['main_service']));
                        foreach ($mainServices as $ms) {
                            $isSelected = ($selectedService && strtolower(str_replace(' ', '', $ms)) === strtolower(str_replace(' ', '', $selectedService)));
                            echo '<span class="service-tag-item" style="'.($isSelected ? 'background:#e0f7fa;color:#00796b;font-weight:600;' : '').'">'.htmlspecialchars($ms).'</span>';
                        }
                        // Show all subcategories for this provider (after internal filtering)
                        $subcats = array_map('trim', explode(',', $provider['subcategories']));
                        foreach ($subcats as $subcat) {
                            if (!empty($subcat)) { // Ensure subcategory is not empty
                                $isSelectedSub = ($selectedSubcategory && strtolower(str_replace(' ', '', $subcat)) === strtolower(str_replace(' ', '', $selectedSubcategory)));
                                echo '<span class="service-tag-item" style="background:#fffde7;color:#b45309;'.($isSelectedSub ? 'font-weight:600;' : '').'">'.htmlspecialchars($subcat).'</span>';
                            }
                        }
                        ?>
                    </div>
                    <div class="provider-contact-details" style="margin-top:1rem;">
                        <strong>Email:</strong> <?php echo htmlspecialchars($provider['provider_email']); ?><br>
                        <strong>Phone:</strong> <?php echo htmlspecialchars($provider['provider_phone']); ?><br>
                        <strong>Address:</strong> <?php echo htmlspecialchars($provider['provider_address']); ?><br>
                    </div>
                    <?php if (!empty($provider['portfolio_images_display'])): ?>
                        <div class="provider-portfolio-gallery" style="margin-top:1rem;">
                            <strong>Portfolio:</strong><br>
                            <?php 
                            foreach ($provider['portfolio_images_display'] as $photo_url): ?>
                                <img src="<?php echo htmlspecialchars($photo_url); ?>" alt="Portfolio" style="max-width:80px;max-height:80px;margin:4px;border-radius:6px;border:1px solid #eee;">
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="provider-actions">
                    <button type="button" class="btn btn-primary btn-book-consultation" data-provider-id="<?php echo htmlspecialchars($provider['provider_id']); ?>" data-provider-name="<?php echo htmlspecialchars($provider['provider_name']); ?>" data-service-type="<?php echo htmlspecialchars($selectedService); ?>" data-subcategory="<?php echo htmlspecialchars($selectedSubcategory); ?>">Book Consultation</button>
                    <form class="quote-request-form" data-provider-id="<?php echo htmlspecialchars($provider['provider_id']); ?>" data-provider-name="<?php echo htmlspecialchars($provider['provider_name']); ?>" data-service-type="<?php echo htmlspecialchars($selectedService); ?>" data-subcategory="<?php echo htmlspecialchars($selectedSubcategory); ?>" data-project-description="Request for <?php echo htmlspecialchars($selectedService); ?><?php echo isset($selectedSubcategory) && !empty($selectedSubcategory) ? ' - ' . htmlspecialchars($selectedSubcategory) : ''; ?>" style="display:inline;">
                        <button type="button" class="btn btn-secondary btn-request-quote">Request a Quote</button>
                    </form>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </section>

<!-- Quote Request Modal -->
<div id="quoteRequestModal" class="booking-modal" style="display:none;">
    <div class="booking-modal-content" style="max-width:500px;">
        <span class="close-modal-btn">&times;</span>
        <h2 style="margin-bottom:1rem;">Request a Quotation</h2>
        <form id="quotePreviewForm">
            <div class="form-group">
                <label for="previewProjectDescription"><strong>Project Description</strong></label>
                <textarea id="previewProjectDescription" rows="5" style="width:100%;padding:0.5rem;" required placeholder="Please describe your project in detail. Include room dimensions, desired style, and any specific requirements." data-provider-id="" data-service-type="" data-subcategory=""></textarea> <!-- Data attributes will be set by JS -->
            </div>
            <div class="form-group">
                <label><strong>Upload Photos (Optional)</strong></label>
                <input type="file" id="previewUploadPhotos" multiple>
                <div id="previewFileList" style="margin-top:0.5rem;"></div>
            </div>
            <button type="submit" class="btn btn-primary" id="submitQuoteBtn" style="margin-top:1rem;">Submit Request</button>
        </form>
    </div>
</div>
</main>

<!-- Booking Modal (for consultation) -->
<div id="bookingModal" class="booking-modal" style="display:none;">
    <div class="booking-modal-content">
        <span class="close-modal-btn">&times;</span>
        <div id="calendarStep">
            <div style="display:flex;justify-content:center;align-items:center;margin-bottom:8px;gap:12px;">
                <button id="prevMonthBtn" style="background:#e5e7eb;border:none;border-radius:6px;padding:6px 12px;cursor:pointer;font-weight:600;">&#8592;</button>
                <span id="calendarMonthTitle" style="font-weight:600;font-size:1.1rem;min-width:120px;text-align:center;"></span>
                <button id="nextMonthBtn" style="background:#e5e7eb;border:none;border-radius:6px;padding:6px 12px;cursor:pointer;font-weight:600;">&#8594;</button>
            </div>
            <div id="calendar-container"></div>
            <div id="time-slots-section" class="time-slots-section" style="display:none;">
                <div class="times-label">Available Times</div>
                <div id="time-slots-list" class="time-slots-list"></div>
            </div>
        </div>
        <!-- Payment Step for Consultation Fee -->
        <div id="paymentStep" style="display:none;">
            <h3>Confirm & Pay Consultation Fee</h3>
            <p>A Rs. 500 fee is required to confirm your booking. This will be credited towards your project.</p>
            <form action="#" class="payment-form">
                <div class="form-group">
                    <label for="cardholder-name">Cardholder Name</label>
                    <input type="text" id="cardholder-name" placeholder="John M. Doe" required>
                </div>
                <div class="form-group">
                    <label for="card-number">Card Number</label>
                    <input type="text" id="card-number" placeholder="•••• •••• •••• ••••" required>
                </div>
                <div class="card-details">
                    <div class="form-group">
                        <label for="expiry-date">Expiry</label>
                        <input type="text" id="expiry-date" placeholder="MM / YY" required>
                    </div>
                    <div class="form-group">
                        <label for="cvc">CVC</label>
                        <input type="text" id="cvc" placeholder="CVC" required>
                    </div>
                     <div class="form-group">
                        <label for="zip">ZIP</label>
                        <input type="text" id="zip" placeholder="ZIP Code" required>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary btn-confirm-booking">Pay Rs. 500 & Confirm Booking</button>
            </form>
            <a href="#" id="backToCalendar" class="back-to-calendar">← Back to Calendar</a>
        </div>
    </div>
</div>

<style>
/* This CSS was provided by you previously, likely for modals. It is included here. */
.booking-modal {
    display: flex;
    align-items: center;
    justify-content: center;
    position: fixed;
    top: 0; left: 0; right: 0; bottom: 0;
    z-index: 1000;
    background: rgba(0,0,0,0.18);
}
.booking-modal-content {
    padding: 2rem 2.5rem 2rem 2.5rem;
    border-radius: 1.5rem;
    background: #fff;
    box-shadow: 0 4px 32px rgba(30,182,233,0.08);
    min-width: 340px;
    max-width: 540px;
    margin: 0 auto;
    max-height: 90vh;
    overflow-y: auto;
}
.close-modal-btn {
    position: absolute;
    top: 1rem;
    right: 1.5rem;
    font-size: 2rem;
    font-weight: bold;
    cursor: pointer;
    color: #666;
    transition: color 0.3s ease;
}
.close-modal-btn:hover {
    color: #333;
}
.calendar-date-cell.selected {
    box-shadow: 0 0 0 2px #1eb6e9;
    background: #1eb6e9 !important;
    color: #fff !important;
}
.time-slots-section {
    margin-top: 1.5rem;
    padding: 1rem 0 0 0;
    border-top: 1px solid #e5e7eb;
    text-align: center;
    background: #fff;
    position: sticky;
    bottom: 0;
    z-index: 2;
    overflow: visible;
}
.times-label {
    font-weight: 700;
    color: #1eb6e9;
    margin-bottom: 0.75rem;
    font-size: 1.08rem;
    letter-spacing: 0.5px;
}
.time-slots-list {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    align-items: center;
    gap: 18px 24px;
    padding-bottom: 1.2rem;
    margin-top: 1.2rem;
    width: 100%;
    box-sizing: border-box;
}
.time-slot-btn {
    min-width: 150px;
    height: 54px;
    text-align: center;
    box-sizing: border-box;
    font-size: 1.18rem;
    font-weight: 700;
    border: 2.5px solid #1eb6e9;
    background: #f0faff;
    color: #1eb6e9;
    border-radius: 10px;
    transition: all 0.18s;
    box-shadow: 0 2px 8px #e0e7ff33;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 18px;
    margin-bottom: 0;
}
.time-slot-btn {
    background: #f5f6fa;
    color: #1eb6e9;
    border: 1.5px solid #1eb6e9;
    border-radius: 8px;
    padding: 12px 28px;
    font-size: 1.08rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.18s;
    outline: none;
    box-shadow: 0 2px 8px #e0e7ff33;
}
.time-slot-btn.selected,
.time-slot-btn:active {
    background: #1eb6e9;
    color: #fff;
    border-color: #1eb6e9;
}
.time-slot-btn:hover {
    background: #e0f7ff;
    color: #1eb6e9;
}
@media (max-width: 500px) {
    .booking-modal-content { min-width: 0; max-width: 98vw; padding: 1rem 0.5rem; }
    .time-slot-btn { padding: 8px 10px; font-size: 0.98rem; }
    .time-slots-list { gap: 8px; }
}

/* Custom CSS for provider listing layout */
.provider-listing-layout {
    display: grid;
    grid-template-columns: 1fr 3fr; /* Sidebar for filters, main area for providers */
    gap: 2rem;
}

@media (max-width: 992px) {
    .provider-listing-layout {
        grid-template-columns: 1fr; /* Stack vertically on smaller screens */
    }
    .provider-filters {
        padding: 1rem;
        background-color: #f9f9f9;
        border-radius: 8px;
        box-shadow: 0 2px 5px rgba(0,0,0,0.05);
    }
}

.provider-filters h3 {
    margin-bottom: 1rem;
    color: #333;
    font-size: 1.2rem;
}

.service-filter-nav, .subcategory-filter-nav {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    margin-bottom: 1.5rem;
}

.filter-item {
    display: block;
    padding: 0.75rem 1rem;
    background-color: #eef2f5;
    border-radius: 6px;
    color: #555;
    text-decoration: none;
    transition: background-color 0.2s, color 0.2s;
}

.filter-item:hover {
    background-color: #e0e5ea;
    color: #333;
}

.filter-item.active {
    background-color: #0d9488; /* Innovista primary color */
    color: white;
    font-weight: 600;
}

.provider-list-container {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.provider-card-list {
    background-color: #fff;
    padding: 1.5rem;
    border-radius: 8px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.08);
    display: flex;
    gap: 1.5rem;
    align-items: flex-start;
}

@media (max-width: 768px) {
    .provider-card-list {
        flex-direction: column;
        align-items: center;
        text-align: center;
    }
    .provider-profile-image {
        margin-bottom: 1rem;
    }
    .provider-details, .provider-actions {
        width: 100%;
        align-items: center;
    }
    .service-tags-list {
        justify-content: center;
    }
}

.provider-profile-image img {
    width: 120px;
    height: 120px;
    object-fit: cover;
    border-radius: 50%;
    border: 3px solid #0d9488;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.provider-details {
    flex-grow: 1;
}

.provider-name {
    font-size: 1.5rem;
    margin-top: 0;
    margin-bottom: 0.5rem;
    color: #333;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.verified-badge {
    color: #28a745; /* Green checkmark */
    font-size: 1rem;
}

.service-tags-list {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    margin-bottom: 1rem;
}

.service-tag-item {
    background-color: #f0f0f0;
    padding: 0.4rem 0.8rem;
    border-radius: 20px;
    font-size: 0.85rem;
    color: #555;
    white-space: nowrap;
}

.provider-contact-details {
    font-size: 0.95rem;
    color: #666;
    margin-bottom: 1rem;
}

.provider-portfolio-gallery {
    margin-top: 1rem;
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
}

.provider-actions {
    display: flex;
    flex-direction: column;
    gap: 0.8rem;
    margin-left: 1rem;
    flex-shrink: 0; /* Prevent actions from shrinking */
}

.btn-book-consultation, .btn-request-quote {
    padding: 0.75rem 1.25rem;
    border-radius: 6px;
    font-weight: 600;
    cursor: pointer;
    text-align: center;
    text-decoration: none;
    white-space: nowrap;
    min-width: 160px;
}

.btn-book-consultation {
    background-color: #0d9488;
    color: white;
    border: none;
}
.btn-book-consultation:hover {
    background-color: #0a756b;
}

.btn-request-quote {
    background-color: #f0f0f0;
    color: #333;
    border: 1px solid #ccc;
}
.btn-request-quote:hover {
    background-color: #e0e0e0;
}
</style>

<script src="assets/js/serviceprovider.js"></script>
<?php 
// Output the booking modal at the end of the body
// This modal is still embedded in public/serviceprovider.php
// Its functionality relies on assets/js/serviceprovider.js
// If this was originally intended to be in the footer, you can move it.
// For now, it stays here.
?>
<?php 
// This ob_start() / ob_get_clean() block should encompass the entire modal HTML 
// if you want to output it from PHP at the end of the body.
// If it's literally at the end of the file as an include, then the ob_start/clean is not needed.
// Given your original file structure, the modal HTML is directly in the file.
?>
<!-- The actual modal HTML is embedded directly in this file as per your original structure -->

<?php 
// Include the footer.
include 'footer.php'; 
?>