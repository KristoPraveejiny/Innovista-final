<?php
$pageTitle = 'Find a Professional';
include 'header.php'; 
require_once __DIR__ . '/../config/Database.php';

// Get selected service from query string, e.g., ?service=Interior%20Design
$selectedService = isset($_GET['service']) ? $_GET['service'] : '';
$selectedSubcategory = isset($_GET['subcategory']) ? $_GET['subcategory'] : '';

$database = new Database();
$db = $database->getConnection();

$providers = [];
$debug = [];
if ($selectedService && $selectedSubcategory) {
    // Remove spaces and make lower case for matching in both filter and DB values
    $selectedServiceNoSpace = strtolower(str_replace([' ', '-', '_'], '', $selectedService));
    $selectedSubcategoryNoSpace = strtolower(str_replace([' ', '-', '_'], '', $selectedSubcategory));
    // Get all providers with the selected main service
    $stmt = $db->prepare('SELECT * FROM service WHERE FIND_IN_SET(:service, LOWER(REPLACE(main_service, " ", "")))');
    $stmt->bindParam(':service', $selectedServiceNoSpace);
    $stmt->execute();
    $allProviders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    // Filter providers to only those who have a subcategory matching both main service and subcategory
    $providers = [];
    foreach ($allProviders as $prov) {
        $subcats = explode(',', $prov['subcategories']);
        foreach ($subcats as $subcat) {
            $subcatNoSpace = strtolower(str_replace([' ', '-', '_'], '', $subcat));
            if (strpos($subcatNoSpace, $selectedServiceNoSpace) !== false && strpos($subcatNoSpace, $selectedSubcategoryNoSpace) !== false) {
                $providers[] = $prov;
                break;
            }
        }
    }
} elseif ($selectedService) {
    $selectedServiceNoSpace = strtolower(str_replace([' ', '-', '_'], '', $selectedService));
    $stmt = $db->prepare('SELECT * FROM service WHERE FIND_IN_SET(:service, LOWER(REPLACE(main_service, " ", "")))');
    $stmt->bindParam(':service', $selectedServiceNoSpace);
    $stmt->execute();
    $providers = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    // If no service selected, show nothing
    $providers = [];
}

// Debug: Show processed filter values
if ($selectedService) {
    $debug[] = 'Selected Service (raw): ' . htmlspecialchars($selectedService);
    $debug[] = 'Selected Service (processed): ' . htmlspecialchars($selectedServiceNoSpace ?? '');
}
if ($selectedSubcategory) {
    $debug[] = 'Selected Subcategory (raw): ' . htmlspecialchars($selectedSubcategory);
    $debug[] = 'Selected Subcategory (processed): ' . htmlspecialchars($selectedSubcategoryNoSpace ?? '');
}

// Debug: Show all main_service and subcategories in DB
$allRows = $db->query('SELECT provider_id, provider_name, main_service, subcategories FROM service')->fetchAll(PDO::FETCH_ASSOC);
if ($allRows) {
    $debug[] = '<strong>All Providers in DB:</strong>';
    foreach ($allRows as $row) {
        $debug[] = 'ID: ' . $row['provider_id'] . ' | Name: ' . htmlspecialchars($row['provider_name']) . ' | main_service: ' . htmlspecialchars($row['main_service']) . ' | subcategories: ' . htmlspecialchars($row['subcategories']);
    }
}
?>


<header class="provider-page-header">
    <div class="container">
        <h1>Find Your Perfect Professional</h1>
        <p>Browse our list of verified experts to find the right match for your project.</p>
    </div>
</header>

<?php
// Show flash message if present
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (isset($_SESSION['flash_message'])) {
    $msg = $_SESSION['flash_message'];
    if (is_array($msg) && isset($msg['message'])) {
        echo '<div style="background:#e0ffe0; color:#2d7a2d; padding:1rem; margin:1rem 0; border-radius:6px; text-align:center; font-size:1.1rem;">'.htmlspecialchars($msg['message']).'</div>';
    } else {
        echo '<div style="background:#e0ffe0; color:#2d7a2d; padding:1rem; margin:1rem 0; border-radius:6px; text-align:center; font-size:1.1rem;">'.htmlspecialchars($msg).'</div>';
    }
    unset($_SESSION['flash_message']);
}
?>
<main class="provider-listing-layout container page-section">
    <!-- Filters Sidebar -->
    <aside class="provider-filters">
        <!-- ... (Your filter HTML is correct) ... -->
    </aside>

    <!-- Provider List -->
    
    <section class="provider-list-container">
        <?php if (empty($providers)): ?>
            <div style="padding:2rem; text-align:center; color:#888; font-size:1.2rem;">No providers found for this service.</div>
        <?php else: ?>
            <?php foreach ($providers as $provider): ?>
            <div class="provider-card-list">
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
                            // Highlight if matches selected service
                            $isSelected = ($selectedService && strtolower(str_replace(' ', '', $ms)) === strtolower(str_replace(' ', '', $selectedService)));
                            echo '<span class="service-tag-item" style="'.($isSelected ? 'background:#e0f7fa;color:#00796b;font-weight:600;' : '').'">'.htmlspecialchars($ms).'</span>';
                        }

                        // Show relevant subcategories for the selected service
                        $subcats = array_map('trim', explode(',', $provider['subcategories']));
                        foreach ($subcats as $subcat) {
                            // If a service is selected, only show subcategories that start with or contain the selected service
                            if ($selectedService) {
                                // Remove spaces and compare lowercased
                                $msNoSpace = strtolower(str_replace(' ', '', $selectedService));
                                $subcatNoSpace = strtolower(str_replace(' ', '', $subcat));
                                if (strpos($subcatNoSpace, $msNoSpace) === false) continue;
                            }
                            // If a subcategory is selected, only show matching subcategory
                            if ($selectedSubcategory) {
                                $subcatNameOnly = strtolower(str_replace(' ', '', $subcat));
                                $selectedSubcatNoSpace = strtolower(str_replace(' ', '', $selectedSubcategory));
                                if (strpos($subcatNameOnly, $selectedSubcatNoSpace) === false) continue;
                            }
                            echo '<span class="service-tag-item" style="background:#fffde7;color:#b45309;">'.htmlspecialchars($subcat).'</span>';
                        }
                        ?>
                    </div>
                    <div class="provider-contact-details" style="margin-top:1rem;">
                        <strong>Email:</strong> <?php echo htmlspecialchars($provider['provider_email']); ?><br>
                        <strong>Phone:</strong> <?php echo htmlspecialchars($provider['provider_phone']); ?><br>
                        <strong>Address:</strong> <?php echo htmlspecialchars($provider['provider_address']); ?><br>
                    </div>
                    <?php if (!empty($provider['portfolio'])): ?>
                        <div class="provider-portfolio-gallery" style="margin-top:1rem;">
                            <strong>Portfolio:</strong><br>
                            <?php 
                            $portfolio = explode(',', $provider['portfolio']);
                            foreach ($portfolio as $photo): ?>
                                <img src="../public/assets/images/<?php echo htmlspecialchars(trim($photo)); ?>" alt="Portfolio" style="max-width:80px;max-height:80px;margin:4px;border-radius:6px;border:1px solid #eee;">
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="provider-actions">
                    <button type="button" class="btn btn-primary btn-book-consultation">Book Consultation</button>
                    <form class="quote-request-form" data-provider-id="<?php echo htmlspecialchars($provider['provider_id']); ?>" data-service-type="<?php echo htmlspecialchars($selectedService); ?>" data-subcategory="<?php echo isset($_GET['subcategory']) ? htmlspecialchars($_GET['subcategory']) : ''; ?>" data-project-description="Request for <?php echo htmlspecialchars($selectedService); ?><?php echo isset($_GET['subcategory']) ? ' - ' . htmlspecialchars($_GET['subcategory']) : ''; ?>" style="display:inline;">
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
        <span class="close-modal-btn">×</span>
        <h2 style="margin-bottom:1rem;">Request a Quotation</h2>
        <form id="quotePreviewForm">
            <div class="form-group">
                <label for="previewProjectDescription"><strong>Project Description</strong></label>
                <textarea id="previewProjectDescription" rows="5" style="width:100%;padding:0.5rem;" required placeholder="Please describe your project in detail. Include room dimensions, desired style, and any specific requirements."></textarea>
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



<!-- Move booking modal to end of body to avoid nesting/overlap issues -->
<?php ob_start(); ?>
<div id="bookingModal" class="booking-modal" style="display:none;">
    <div class="booking-modal-content">
        <span class="close-modal-btn">×</span>
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
        <div id="paymentStep" style="display:none;">
            <h3>Confirm & Pay Consultation Fee</h3>
            <p>A $50 fee is required to confirm your booking. This will be credited towards your project.</p>
            <form action="../handlers/handle_consultation_booking.php" method="POST" class="payment-form" id="consultation-payment-form">
                <!-- Hidden fields for booking data -->
                <input type="hidden" id="booking-provider-id" name="provider_id" value="">
                <input type="hidden" id="booking-service-id" name="service_id" value="1">
                <input type="hidden" id="booking-date" name="booking_date" value="">
                <input type="hidden" id="booking-time" name="booking_time" value="">
                <input type="hidden" name="payment_method" value="card">
                
                <div class="form-group">
                    <label for="cardholder-name">Cardholder Name</label>
                    <input type="text" id="cardholder-name" name="cardholder_name" placeholder="John M. Doe" required>
                </div>
                <div class="form-group">
                    <label for="card-number">Card Number <span id="card-type-indicator" style="display: none; color: #28a745; font-weight: bold; margin-left: 10px;"></span></label>
                    <input type="text" id="card-number" name="card_number" placeholder="•••• •••• •••• ••••" maxlength="19" pattern="[0-9\s]{13,19}" title="Enter a valid card number" required>
                    <div id="card-number-error" class="error-message" style="color: #dc3545; font-size: 12px; display: none; margin-top: 5px; font-weight: 500;"></div>
                </div>
                <div class="card-details">
                    <div class="form-group">
                        <label for="expiry-date">Expiry</label>
                        <input type="text" id="expiry-date" name="card_expiry" placeholder="MM / YY" required>
                    </div>
                    <div class="form-group">
                        <label for="cvc">CVC</label>
                        <input type="text" id="cvc" name="card_cvc" placeholder="CVC" required>
                    </div>
                     <div class="form-group">
                        <label for="zip">ZIP</label>
                        <input type="text" id="zip" name="zip_code" placeholder="ZIP Code" required>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary btn-confirm-booking">Pay Rs500 & Confirm Booking</button>
            </form>
            <a href="#" id="backToCalendar" class="back-to-calendar">← Back to Calendar</a>
        </div>
    </div>
</div>
<?php $bookingModalHtml = ob_get_clean(); ?>
<style>
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
</style>
<script src="assets/js/serviceprovider.js"></script>
<script src="assets/js/service-provider.js"></script>
<?php 
// Output the booking modal at the end of the body
echo $bookingModalHtml;
// Include the footer, which now should also link to our new script
include 'footer.php'; 
?>
