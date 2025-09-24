
<?php
// Define the page title for this specific page
$pageTitle = 'Our Services'; 
// Include the master header, which also starts the session
include 'header.php'; 

// Define the target URL based on login status. This makes the code cleaner.
$targetUrl = isUserLoggedIn() ? './serviceprovider.php' : './login.php';
// JS helper: expose login state to handle "Get Service" links uniformly
$isLoggedInFlag = isUserLoggedIn() ? 'true' : 'false';
?>

<!-- =========================================
     SERVICES HERO SECTION
     ========================================= -->
<section class="services-hero">
    <div class="hero-slider">
        <div class="hero-slide active" style="background-image: url('assets/images/images/service/head1.jpg');"></div>
        <div class="hero-slide" style="background-image: url('assets/images/images/service/head2.jpg');"></div>
        <div class="hero-slide" style="background-image: url('assets/images/images/service/head3.jpg');"></div>
    </div>
    <div class="hero-overlay"></div>
    <div class="hero-content">
        <h1>Our Premium Services</h1>
        <p>Transform your space with our expert interior design, painting, and restoration services in the Northern Province.</p>
        <div class="hero-stats">
            <div class="stat">
                <span class="stat-number">50+</span>
                <span class="stat-label">Expert Providers</span>
            </div>
            <div class="stat">
                <span class="stat-number">4.8 ★</span>
                <span class="stat-label">Customer Rating</span>
            </div>
            <div class="stat">
                <span class="stat-number">500+</span>
                <span class="stat-label">Projects Completed</span>
            </div>
        </div>
    </div>
</section>

<!-- =========================================
     MAIN SERVICES LAYOUT
     ========================================= -->
<main class="services-layout container page-section">
    <aside class="service-sidebar">
        <h3 class="sidebar-title">Service Categories</h3>
        <button class="service-tab active" data-service="interior-design"><i class="fas fa-couch"></i> Interior Design</button>
        <button class="service-tab" data-service="painting"><i class="fas fa-paint-roller"></i> Professional Painting</button>
        <button class="service-tab" data-service="restoration"><i class="fas fa-hammer"></i> Restoration</button>
    </aside>
  
    <section class="services-content-area">
        <!-- Interior Design Card (Initially visible) -->
        <div class="service-card active" data-service="interior-design">
            <div class="service-header-box">
                <h3>Interior Design</h3>
                <p>From concept to completion, we create spaces that are both beautiful and functional. Choose individual services or our all-inclusive package.</p>
            </div>
            <div class="subcategory-grid">
                <div class="subcategory-card"><img src="assets/images/images/service/ceiling.jpg" alt="Ceiling & Lighting"><h4>Ceiling & Lighting</h4><p>Modern ceiling and lighting solutions.</p><a href="serviceprovider.php?service=Interior%20Design&subcategory=Ceiling%20%26%20Lighting" class="get-service-link">Get Service →</a></div>
                <div class="subcategory-card"><img src="assets/images/images/service/interior 1.jpg" alt="Space Planning"><h4>Space Planning</h4><p>Efficient and functional layouts.</p><a href="serviceprovider.php?service=Interior%20Design&subcategory=Space%20Planning" class="get-service-link">Get Service →</a></div>
                <div class="subcategory-card"><img src="assets/images/images/service/kitchen.avif" alt="Modular Kitchen"><h4>Modular Kitchen</h4><p>Smart and stylish kitchen designs.</p><a href="serviceprovider.php?service=Interior%20Design&subcategory=Modular%20Kitchen" class="get-service-link">Get Service →</a></div>
                <div class="subcategory-card"><img src="assets/images/images/service/bathroom.jpg" alt="Bathroom Design"><h4>Bathroom Design</h4><p>Elegant and relaxing bathroom spaces.</p><a href="serviceprovider.php?service=Interior%20Design&subcategory=Bathroom%20Design" class="get-service-link">Get Service →</a></div>
                <div class="subcategory-card"><img src="assets/images/images/service/wood.jpg" alt="Carpentry"><h4>Carpentry & Woodwork</h4><p>Custom woodwork with fine finishes.</p><a href="serviceprovider.php?service=Interior%20Design&subcategory=Carpentry%20%26%20Woodwork" class="get-service-link">Get Service →</a></div>
                <div class="subcategory-card"><img src="assets/images/images/service/furniture.jpg" alt="Furniture Design"><h4>Furniture Design</h4><p>Unique and comfortable furnishings.</p><a href="serviceprovider.php?service=Interior%20Design&subcategory=Furniture%20Design" class="get-service-link">Get Service →</a></div>
            </div>
            <div class="package-section">
                <h4>Complete Interior Package</h4>
                <p>Get all our interior design expertise bundled at a discounted rate for a seamless transformation.</p>
                <a href="serviceprovider.php?service=Interior%20Design" class="btn btn-primary btn-get-package" id="getAllServicesBtn">Get All Interior Design Services</a>
            </div>
        </div>

        <!-- Painting Card (Initially hidden) -->
        <div class="service-card" data-service="painting">
            <div class="service-header-box">
                <h3>Professional Painting</h3>
                <p>Expert painting for residential and commercial spaces, ensuring a flawless finish that lasts.</p>
            </div>
            <div class="subcategory-grid">
                <div class="subcategory-card"><img src="assets/images/images/service/interiorpaint.jpg" alt="Interior Painting"><h4>Interior Painting</h4><p>Beautiful and durable indoor finishes.</p><a href="serviceprovider.php?service=Painting&subcategory=Interior%20Painting" class="get-service-link">Get Service →</a></div>
                <div class="subcategory-card"><img src="assets/images/images/service/exterior.jpg" alt="Exterior Painting"><h4>Exterior Painting</h4><p>Weatherproof and lasting exterior color.</p><a href="serviceprovider.php?service=Painting&subcategory=Exterior%20Painting" class="get-service-link">Get Service →</a></div>
                <div class="subcategory-card"><img src="assets/images/images/service/water.jpg" alt="Water Proofing"><h4>Water & Damp Proofing</h4><p>Protect your walls from moisture damage.</p><a href="serviceprovider.php?service=Painting&subcategory=Water%20%26%20Damp%20Proofing" class="get-service-link">Get Service →</a></div>
                <div class="subcategory-card"><img src="assets/images/images/service/commercial.webp" alt="Commercial Painting"><h4>Commercial Painting</h4><p>Professional finishes for your business.</p><a href="serviceprovider.php?service=Painting&subcategory=Commercial%20Painting" class="get-service-link">Get Service →</a></div>
                <div class="subcategory-card"><img src="assets/images/images/service/art.jpg" alt="Wall Art & Murals"><h4>Wall Art & Murals</h4><p>Creative custom designs and murals.</p><a href="serviceprovider.php?service=Painting&subcategory=Wall%20Art%20%26%20Murals" class="get-service-link">Get Service →</a></div>
                <div class="subcategory-card"><img src="assets/images/images/service/paintconsul.jpg" alt="Color Consultation"><h4>Color Consultation</h4><p>Expert advice on picking the perfect palette.</p><a href="serviceprovider.php?service=Painting&subcategory=Color%20Consultation" class="get-service-link">Get Service →</a></div>
            </div>
            <div class="package-section">
                <h4>Complete Painting Package</h4>
                <p>Bundle interior, exterior, and waterproofing services for a comprehensive painting solution.</p>
                <a href="serviceprovider.php?service=Painting" class="btn btn-primary btn-get-package" id="getAllServicesBtn">Get All Painting Services</a>
            </div>
        </div>
      
        <!-- Restoration Card (Initially hidden) -->
        <div class="service-card" data-service="restoration">
            <div class="service-header-box">
                <h3>Restoration Services</h3>
                <p>Breathe new life into your property. We restore and preserve the beauty of your spaces and furniture.</p>
            </div>
            <div class="subcategory-grid">
                <div class="subcategory-card"><img src="assets/images/images/service/wallreno.jpg" alt="Wall Repairs"><h4>Wall Repairs & Plastering</h4><p>Seamless wall and ceiling restoration.</p><a href="serviceprovider.php?service=Restoration&subcategory=Wall%20Repairs%20%26%20Plastering" class="get-service-link">Get Service →</a></div>
                <div class="subcategory-card"><img src="assets/images/images/service/floorreno.jpg" alt="Floor Restoration"><h4>Floor Restoration</h4><p>Refinishing and repairs for all floor types.</p><a href="serviceprovider.php?service=Restoration&subcategory=Floor%20Restoration" class="get-service-link">Get Service →</a></div>
                <div class="subcategory-card"><img src="assets/images/images/service/doorreno.jpg" alt="Carpentry Repairs"><h4>Door & Window Repairs</h4><p>Expert restoration of wooden fixtures.</p><a href="serviceprovider.php?service=Restoration&subcategory=Door%20%26%20Window%20Repairs" class="get-service-link">Get Service →</a></div>
                <div class="subcategory-card"><img src="assets/images/images/service/spacereno.jpg" alt="Old Space Transformation"><h4>Old Space Transformation</h4><p>Modernize your interiors completely.</p><a href="serviceprovider.php?service=Restoration&subcategory=Old%20Space%20Transformation" class="get-service-link">Get Service →</a></div>
                <div class="subcategory-card"><img src="assets/images/images/service/furniture.jpg" alt="Furniture Restoration"><h4>Furniture Restoration</h4><p>Restore antique and modern furniture.</p><a href="serviceprovider.php?service=Restoration&subcategory=Furniture%20Restoration" class="get-service-link">Get Service →</a></div>
                <div class="subcategory-card"><img src="assets/images/images/service/roomreno.jpg" alt="Building Renovation"><h4>Full Building Renovation</h4><p>Complete structural and aesthetic renewal.</p><a href="serviceprovider.php?service=Restoration&subcategory=Full%20Building%20Renovation" class="get-service-link">Get Service →</a></div>
            </div>
            <div class="package-section">
                <h4>Complete Restoration Package</h4>
                <p>A full-service solution to completely renovate and restore your property from top to bottom.</p>
                <a href="serviceprovider.php?service=Restoration" class="btn btn-primary btn-get-package" id="getAllServicesBtn">Get All Restoration Services</a>
            </div>
        </div>
    </section>
</main>

<script>
// Ensure unauthenticated users clicking any "Get Service" link go to login
(function(){
    var isLoggedIn = <?php echo $isLoggedInFlag; ?>;
    if (!isLoggedIn) {
        document.addEventListener('DOMContentLoaded', function(){
            var links = document.querySelectorAll('.get-service-link');
            links.forEach(function(a){
                a.addEventListener('click', function(e){
                    e.preventDefault();
                    window.location.href = './signup.php';
                });
            });
        });
    }
})();
</script>

<!-- =========================================
     NEW: OUR COMMITMENT TO QUALITY SECTION
     ========================================= -->
<section class="commitment-section page-section">
    <div class="container">
        <h2 class="section-title">Our Commitment to Quality</h2>
        <div class="commitment-grid">
            <div class="commitment-item">
                <div class="commitment-icon"><i class="fas fa-tachometer-alt"></i></div>
                <h3>Performance</h3>
                <p>Our platform is optimized to load pages within 3 seconds, ensuring a fast and frustration-free experience for you.</p>
            </div>
            <div class="commitment-item">
                <div class="commitment-icon"><i class="fas fa-shield-alt"></i></div>
                <h3>Security</h3>
                <p>Your data is safe with us. All sensitive information, from passwords to payments, is encrypted to protect you.</p>
            </div>
            <div class="commitment-item">
                <div class="commitment-icon"><i class="fas fa-check-circle"></i></div>
                <h3>Reliability</h3>
                <p>We aim for 99% uptime with automatic backups, so Innovista is always available when you need it.</p>
            </div>
            <div class="commitment-item">
                <div class="commitment-icon"><i class="fas fa-handshake"></i></div>
                <h3>Transparency</h3>
                <p>From verified providers to clear invoices and a public feedback system, we build trust every step of the way.</p>
            </div>
        </div>
    </div>
</section>

<!-- =========================================
     NEW: PRODUCTS CTA SECTION
     ========================================= -->
<section class="products-cta-section">
    <div class="container">
        <h2>Don't Forget the Finishing Touches</h2>
        <p>In addition to our expert services, we offer a curated selection of high-quality products to complete your project.</p>
        <a href="./product.php" class="btn btn-primary">Shop Our Products</a>
    </div>
</section>

<!-- Service Information Modal -->
<div id="serviceModal" class="service-modal" style="display: none;">
    <div class="service-modal-content">
        <span class="close-service-modal">&times;</span>
        <h2 id="modalTitle">Service Information</h2>
        <div id="serviceContent">
            <!-- Content will be loaded here via AJAX -->
        </div>
    </div>
</div>

<!-- Quote Request Modal -->
<div id="quoteRequestModal" class="booking-modal" style="display:none;">
    <div class="booking-modal-content" style="max-width:500px;">
        <span class="close-modal-btn">&times;</span>
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

<!-- Booking Modal -->
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
        <!-- Payment Step moved inside modal content for proper overlay -->
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
                <button type="submit" class="btn btn-primary btn-confirm-booking">Pay $50 & Confirm Booking</button>
            </form>
            <a href="#" id="backToCalendar" class="back-to-calendar">← Back to Calendar</a>
        </div>
    </div>
</div>

<style>
.service-modal {
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 1rem;
}

.service-modal-content {
    background-color: #fff;
    padding: 2rem;
    border-radius: 12px;
    max-width: 95vw;
    width: 100%;
    max-height: 80vh;
    overflow-y: auto;
    position: relative;
    box-shadow: 0 10px 30px rgba(0,0,0,0.3);
}

.close-service-modal {
    position: absolute;
    top: 1rem;
    right: 1.5rem;
    font-size: 2rem;
    font-weight: bold;
    cursor: pointer;
    color: #666;
    transition: color 0.3s ease;
}

.close-service-modal:hover {
    color: #333;
}

.providers-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
    gap: 1.5rem;
    margin-top: 1rem;
    width: 100%;
}

.provider-card {
    background: #fff;
    border: 2px solid #20b2aa;
    border-radius: 12px;
    padding: 1.5rem;
    position: relative;
    box-shadow: 0 4px 12px rgba(32, 178, 170, 0.1);
    transition: all 0.3s ease;
}

.provider-card:hover {
    box-shadow: 0 8px 24px rgba(32, 178, 170, 0.2);
    transform: translateY(-2px);
}

.provider-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 1rem;
}

.provider-name {
    color: #333;
    font-size: 1.4rem;
    font-weight: 700;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.verified-badge {
    color: #4caf50;
    font-size: 1.2rem;
}

.provider-actions {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
    align-items: flex-end;
}

.btn-book-consultation {
    background: #20b2aa;
    color: white;
    border: none;
    padding: 0.75rem 1.5rem;
    border-radius: 8px;
    font-weight: 600;
    font-size: 0.9rem;
    cursor: pointer;
    transition: all 0.3s ease;
    min-width: 140px;
}

.btn-book-consultation:hover {
    background: #1a9b94;
    transform: translateY(-1px);
}

.btn-request-quote {
    background: #f5f5f5;
    color: #333;
    border: none;
    padding: 0.6rem 1.2rem;
    border-radius: 8px;
    font-weight: 600;
    font-size: 0.85rem;
    cursor: pointer;
    transition: all 0.3s ease;
    min-width: 120px;
}

.btn-request-quote:hover {
    background: #e0e0e0;
    transform: translateY(-1px);
}

.service-tags {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    margin-bottom: 1rem;
}

.service-tag {
    padding: 0.3rem 0.8rem;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 500;
}

.service-tag.primary {
    background: #e0f7fa;
    color: #00796b;
}

.service-tag.secondary {
    background: #f5f5f5;
    color: #666;
}

.service-tag.highlight {
    background: #fff8e1;
    color: #f57c00;
}

.contact-details {
    margin-bottom: 1rem;
}

.contact-details p {
    margin: 0.3rem 0;
    color: #666;
    font-size: 0.9rem;
}

.portfolio-section {
    margin-top: 1rem;
}

.portfolio-label {
    font-weight: 600;
    color: #333;
    margin-bottom: 0.5rem;
}

.portfolio-image {
    width: 80px;
    height: 60px;
    border-radius: 6px;
    object-fit: cover;
    border: 1px solid #e0e0e0;
}

.loading {
    text-align: center;
    padding: 3rem;
    color: #666;
}

.loading i {
    font-size: 2rem;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.error {
    background: #ffebee;
    color: #c62828;
    padding: 1rem;
    border-radius: 8px;
    text-align: center;
}

@media (max-width: 768px) {
    .service-modal {
        padding: 1rem;
    }
    
    .service-modal-content {
        padding: 1.5rem;
    }
    
    .providers-grid {
        grid-template-columns: 1fr;
        gap: 1rem;
    }
    
    .provider-card {
        padding: 1rem;
    }
    
    .provider-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 1rem;
    }
    
    .provider-actions {
        align-items: stretch;
        width: 100%;
    }
    
    .btn-book-consultation,
    .btn-request-quote {
        width: 100%;
    }
}

/* Booking Modal Styles */
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
  /* overflow-y: visible;  */
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

   
    

document.addEventListener('DOMContentLoaded', function() {
    // Modal and button variables (must be defined before any function uses them)
    const modal = document.getElementById('serviceModal');
    const closeModal = document.querySelector('.close-service-modal');
    const modalTitle = document.getElementById('modalTitle');
    const serviceContent = document.getElementById('serviceContent');
    const getAllServicesBtn = document.getElementById('getAllServicesBtn');
    const getAllServicesBtnPainting = document.getElementById('getAllServicesBtnPainting');
    const getAllServicesBtnRestoration = document.getElementById('getAllServicesBtnRestoration');

    // Add event listeners to sidebar service category tabs
    const serviceTabs = document.querySelectorAll('.service-tab');
    serviceTabs.forEach(function(tab) {
        tab.addEventListener('click', function(e) {
            e.preventDefault(); // Prevent default button behavior
            // Remove active class from all tabs
            serviceTabs.forEach(t => t.classList.remove('active'));
            // Add active class to clicked tab
            tab.classList.add('active');
            // Determine category
            let category = '';
            if (tab.dataset.service === 'interior-design') {
                category = 'Interior Design';
            } else if (tab.dataset.service === 'painting') {
                category = 'Painting';
            } else if (tab.dataset.service === 'restoration') {
                category = 'Restoration';
            }
            if (category) {
                showServicesByCategory(category);
                if (modal) modal.style.display = 'flex';
            }
        });
    });
}); 
 // Close modal when clicking X
    closeModal.addEventListener('click', function() {
        modal.style.display = 'none';
    });
    

    // Close modal when clicking outside
    window.addEventListener('click', function(event) {
        if (event.target === modal) {
            modal.style.display = 'none';
        }
    });

    // Function to show services by category
    function showServicesByCategory(category) {
        modalTitle.textContent = `${category} Services`;
        serviceContent.innerHTML = '<div class="loading"><i class="fas fa-spinner"></i><br>Loading services...</div>';
        modal.style.display = 'flex';
        
        fetch(`get_services.php?action=get_services_by_category&category=${encodeURIComponent(category)}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    if (data.services && data.services.length > 0) {
                        displayServices(data.services);
                    } else {
                        serviceContent.innerHTML = `<div class="error">No ${category} services found. Please check if providers are available for this category.</div>`;
                    }
                } else {
                    serviceContent.innerHTML = '<div class="error">Error: ' + data.message + '</div>';
                }
            })
            .catch(error => {
                serviceContent.innerHTML = '<div class="error">Error loading services. Please try again.</div>';
                console.error('Error:', error);
            });
    }

    Get All Services button click handlers
    getAllServicesBtn.addEventListener('click', function() {
        showServicesByCategory('Interior Design');
    });
    
    getAllServicesBtnPainting.addEventListener('click', function() {
        showServicesByCategory('Painting');
    });
    
    getAllServicesBtnRestoration.addEventListener('click', function() {
        showServicesByCategory('Restoration');
    });
    

    function displayServices(services) {
        if (!services || services.length === 0) {
            serviceContent.innerHTML = '<div class="error">No services found.</div>';
            return;
        }

        let html = '<div class="providers-grid">';
        services.forEach(provider => {
            // Parse main services and subcategories
            const mainServices = provider.main_service.split(',').map(s => s.trim());
            const subcategories = provider.subcategories.split(',').map(s => s.trim());
            
            // Get first portfolio image if available
            const portfolioImages = provider.portfolio ? provider.portfolio.split(',').map(s => s.trim()) : [];
            const firstImage = portfolioImages.length > 0 ? portfolioImages[0] : '';
            
            // Filter services based on current category
            const currentCategory = modalTitle.textContent.replace(' Services', '');
            let filteredMainServices = mainServices;
            let filteredSubcategories = subcategories;
            
            // If showing Painting services, filter to show only Painting main service and painting subcategories
            if (currentCategory === 'Painting') {
                filteredMainServices = mainServices.filter(service => 
                    service.toLowerCase().includes('painting')
                );
                filteredSubcategories = subcategories.filter(sub => 
                    sub.toLowerCase().includes('painting') || 
                    sub.toLowerCase().includes('paint') ||
                    sub.toLowerCase().includes('color') ||
                    sub.toLowerCase().includes('mural') ||
                    sub.toLowerCase().includes('waterproof') ||
                    sub.toLowerCase().includes('damp')
                );
            }
            // If showing Interior Design services, filter to show only Interior Design main service and interior subcategories
            else if (currentCategory === 'Interior Design') {
                filteredMainServices = mainServices.filter(service => 
                    service.toLowerCase().includes('interior') || service.toLowerCase().includes('design')
                );
                filteredSubcategories = subcategories.filter(sub => 
                    sub.toLowerCase().includes('interior design') ||
                    sub.toLowerCase().includes('interior -') ||
                    sub.toLowerCase().includes('ceiling & lighting') ||
                    sub.toLowerCase().includes('space planning') ||
                    sub.toLowerCase().includes('modular kitchen') ||
                    sub.toLowerCase().includes('bathroom design') ||
                    sub.toLowerCase().includes('carpentry & woodwork') ||
                    sub.toLowerCase().includes('furniture design')
                );
            }
            // If showing Restoration services, filter to show only Restoration main service and restoration subcategories
            else if (currentCategory === 'Restoration') {
                filteredMainServices = mainServices.filter(service => 
                    service.toLowerCase().includes('restoration') || service.toLowerCase().includes('renovation')
                );
                filteredSubcategories = subcategories.filter(sub => 
                    sub.toLowerCase().includes('restoration') || 
                    sub.toLowerCase().includes('renovation') ||
                    sub.toLowerCase().includes('repair') ||
                    sub.toLowerCase().includes('plastering') ||
                    sub.toLowerCase().includes('floor') ||
                    sub.toLowerCase().includes('door') ||
                    sub.toLowerCase().includes('window') ||
                    sub.toLowerCase().includes('building') ||
                    sub.toLowerCase().includes('transformation')
                );
            }
            
            // Only show the card if there are relevant services for this category
            if (filteredMainServices.length > 0 || filteredSubcategories.length > 0) {
                html += `
                    <div class="provider-card">
                        <div class="provider-header">
                            <div>
                                <h3 class="provider-name">
                                    ${provider.provider_name}
                                    <i class="fas fa-check-circle verified-badge" title="Verified Provider"></i>
                                </h3>
                            </div>
                            <div class="provider-actions">
                                <button class="btn-book-consultation" data-provider-id="${provider.provider_id || ''}" data-provider-name="${provider.provider_name}">Book Consultation</button>
                                <button class="btn-request-quote" data-provider-id="${provider.provider_id || ''}" data-provider-name="${provider.provider_name}" data-service-type="${filteredMainServices[0] || ''}">Request a Quote</button>
                            </div>
                        </div>
                        
                        <div class="service-tags">
                            ${filteredMainServices.map((service, index) => 
                                `<span class="service-tag ${index === 0 ? 'primary' : 'secondary'}">${service}</span>`
                            ).join('')}
                            ${filteredSubcategories.map(sub => 
                                `<span class="service-tag highlight">${sub}</span>`
                            ).join('')}
                        </div>

                        <div class="contact-details">
                            <p><strong>Email:</strong> ${provider.provider_email || 'Not provided'}</p>
                            <p><strong>Phone:</strong> ${provider.provider_phone || 'Not provided'}</p>
                            <p><strong>Address:</strong> ${provider.provider_address || 'Not provided'}</p>
                        </div>
                        
                        ${(firstImage && firstImage !== '${firstImage}') ? `
                            <div class="portfolio-section">
                                <div class="portfolio-label">Portfolio:</div>
                                <img src="assets/images/${firstImage}" alt="Portfolio" class="portfolio-image" onerror="this.style.display='none'">
                            </div>
                        ` : ''}
                    </div>
                `;
            }
        });
        html += '</div>';
        
        serviceContent.innerHTML = html;
        
        // Add event listeners to the newly created buttons
        addButtonEventListeners();

        // Store selected main service and subcategory globally for quote modal
        window.selectedServiceType = provider.main_service || '';
        window.selectedSubcategory = '';

        // If a subcategory is selected (from the UI), set it
        if (typeof filteredSubcategories !== 'undefined' && filteredSubcategories.length === 1) {
            window.selectedSubcategory = filteredSubcategories[0];
        } else if (typeof filteredSubcategories !== 'undefined' && filteredSubcategories.length > 1) {
            window.selectedSubcategory = 'All';
        }
    }
    
    // Function to add event listeners to modal buttons
    function addButtonEventListeners() {
        // Book Consultation buttons
        const bookConsultationBtns = document.querySelectorAll('#serviceModal .btn-book-consultation');
        bookConsultationBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                const providerId = this.getAttribute('data-provider-id');
                if (providerId && providerId !== '') {
                    // Close the service modal
                    modal.style.display = 'none';
                    // Find the matching button in the main DOM and trigger its click
                    const globalBtn = document.querySelector('.provider-actions .btn-book-consultation[data-provider-id="' + providerId + '"]');
                    if (globalBtn) {
                        globalBtn.click();
                    } else {
                        // fallback: directly call the booking modal logic
                        let currentYear = new Date().getFullYear();
                        let currentMonth = new Date().getMonth();
                        var calendarStep = document.getElementById('calendarStep');
                        var paymentStep = document.getElementById('paymentStep');
                        var timeSlotsSection = document.getElementById('time-slots-section');
                        var bookingModal = document.getElementById('bookingModal');
                        if (calendarStep) calendarStep.style.display = '';
                        if (paymentStep) paymentStep.style.display = 'none';
                        if (timeSlotsSection) timeSlotsSection.style.display = 'none';
                        if (bookingModal) {
                            bookingModal.classList.add('active');
                            bookingModal.style.display = 'block';
                        }
                        if (typeof renderRealTimeCalendar === 'function') {
                            renderRealTimeCalendar(providerId, currentYear, currentMonth);
                        }
                        if (typeof updateMonthTitle === 'function') {
                            updateMonthTitle(currentYear, currentMonth);
                        }
                        document.getElementById('prevMonthBtn').onclick = function() {
                            currentMonth--;
                            if (currentMonth < 0) { currentMonth = 11; currentYear--; }
                            renderRealTimeCalendar(providerId, currentYear, currentMonth);
                            updateMonthTitle(currentYear, currentMonth);
                        };
                        document.getElementById('nextMonthBtn').onclick = function() {
                            currentMonth++;
                            if (currentMonth > 11) { currentMonth = 0; currentYear++; }
                            renderRealTimeCalendar(providerId, currentYear, currentMonth);
                            updateMonthTitle(currentYear, currentMonth);
                        };
                    }
                } else {
                    alert('Provider information not available. Provider ID: ' + providerId);
                }
            });
        });
        
        // Request Quote buttons
        const requestQuoteBtns = document.querySelectorAll('#serviceModal .btn-request-quote');
        requestQuoteBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                const providerId = this.getAttribute('data-provider-id');
                const providerName = this.getAttribute('data-provider-name');
                // Only use the main service the customer is viewing/selecting
                let serviceType = '';
                if (this.hasAttribute('data-service-type')) {
                    serviceType = this.getAttribute('data-service-type');
                } else if (typeof selectedServiceTab !== 'undefined') {
                    serviceType = selectedServiceTab;
                }
                window.selectedServiceType = serviceType;
                console.log('Request Quote clicked - Provider ID:', providerId, 'Provider Name:', providerName, 'Service Type:', serviceType);
                if (providerId && providerId !== '') {
                    // Close the service modal
                    modal.style.display = 'none';
                    // Open quote request modal
                    openQuoteModal(providerId, providerName, serviceType);
                } else {
                    alert('Provider information not available. Provider ID: ' + providerId);
                }
            });
        });
    }
    
    // Function to open booking modal
        // REMOVE booking and quote modal openers from services page
        // These modals should only be triggered from the provider list page, not here.
        const bookingModal = document.getElementById('bookingModal');
        const calendarStep = document.getElementById('calendarStep');
        const paymentStep = document.getElementById('paymentStep');
        const timeSlotsSection = document.getElementById('time-slots-section');
        
        // Reset modal content
        calendarStep.style.display = 'block';
        paymentStep.style.display = 'none';
        timeSlotsSection.style.display = 'none';
        
        // Show modal
        bookingModal.style.display = 'flex';
        
        // Add close functionality
        const bookingCloseBtn = bookingModal.querySelector('.close-modal-btn');
        bookingCloseBtn.onclick = function() {
            bookingModal.style.display = 'none';
        };
        
        // Close when clicking outside
        window.onclick = function(event) {
            if (event.target === bookingModal) {
                bookingModal.style.display = 'none';
            }
        };
        
        // For now, show a simple message since we don't have the full calendar functionality
        
    // Function to open quote request modal
    // REMOVE booking and quote modal openers from services page
    // These modals should only be triggered from the provider list page, not here.
        const quoteModal = document.getElementById('quoteRequestModal');
        
        // Show modal
        quoteModal.style.display = 'flex';
        
        // Add close functionality
        const quoteCloseBtn = quoteModal.querySelector('.close-modal-btn');
        quoteCloseBtn.onclick = function() {
            quoteModal.style.display = 'none';
        };
        
        // Close when clicking outside
        window.onclick = function(event) {
            if (event.target === quoteModal) {
                quoteModal.style.display = 'none';
            }
        };
        
        // Handle form submission
        const quoteForm = document.getElementById('quotePreviewForm');
        quoteForm.onsubmit = function(e) {
            e.preventDefault();
            const projectDescription = document.getElementById('previewProjectDescription').value;
            const files = document.getElementById('previewUploadPhotos').files;
            // Get selected main service and subcategory from modal context (assume these are set globally or in the modal)
            let selectedService = window.selectedServiceType || '';
            let selectedSubcategory = window.selectedSubcategory || '';
            // Prepare form data
            const formData = new FormData();
            formData.append('provider_id', providerId);
            formData.append('provider_name', providerName);
            formData.append('service_type', selectedService);
            formData.append('subcategory', selectedSubcategory);
            formData.append('project_description', projectDescription);
            // Add files
            for (let i = 0; i < files.length; i++) {
                formData.append('photos[]', files[i]);
            }
            fetch('handlers/handle_quote_request.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Quote request submitted successfully!');
                    quoteModal.style.display = 'none';
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                alert('Error submitting quote request. Please try again.');
                console.error('Error:', error);
            });
        };
    
</script>

<script>
// Modal and button variables (global scope)
const modal = document.getElementById('serviceModal');
const closeModal = document.querySelector('.close-service-modal');
const modalTitle = document.getElementById('modalTitle');
const serviceContent = document.getElementById('serviceContent');
const getAllServicesBtn = document.getElementById('getAllServicesBtn');
const getAllServicesBtnPainting = document.getElementById('getAllServicesBtnPainting');
const getAllServicesBtnRestoration = document.getElementById('getAllServicesBtnRestoration');



document.addEventListener('DOMContentLoaded', function() {
    // Tab switching for static service cards (not modal)
    const serviceTabs = document.querySelectorAll('.service-tab');
    const serviceCards = document.querySelectorAll('.service-card');
    serviceTabs.forEach(function(tab) {
        tab.addEventListener('click', function(e) {
            e.preventDefault();
            // Remove active from all tabs and cards
            serviceTabs.forEach(t => t.classList.remove('active'));
            serviceCards.forEach(card => card.classList.remove('active'));
            // Add active to clicked tab and matching card
            tab.classList.add('active');
            const service = tab.getAttribute('data-service');
            const card = document.querySelector('.service-card[data-service="' + service + '"]');
            if (card) card.classList.add('active');
        });
    });

    // Get All Services buttons for Painting and Restoration (robust event handler)
    if (getAllServicesBtnPainting) {
        getAllServicesBtnPainting.addEventListener('click', function(e) {
            e.preventDefault();
            // Remove active from all tabs and cards
            serviceTabs.forEach(function(t) { t.classList.remove('active'); });
            serviceCards.forEach(function(card) { card.classList.remove('active'); });
            // Add active to Painting tab and card
            var tab = document.querySelector('.service-tab[data-service="painting"]');
            var card = document.querySelector('.service-card[data-service="painting"]');
            if (tab) tab.classList.add('active');
            if (card) card.classList.add('active');
        });
    }
    if (getAllServicesBtnRestoration) {
        getAllServicesBtnRestoration.addEventListener('click', function(e) {
            e.preventDefault();
            // Remove active from all tabs and cards
            serviceTabs.forEach(function(t) { t.classList.remove('active'); });
            serviceCards.forEach(function(card) { card.classList.remove('active'); });
            // Add active to Restoration tab and card
            var tab = document.querySelector('.service-tab[data-service="restoration"]');
            var card = document.querySelector('.service-card[data-service="restoration"]');
            if (tab) tab.classList.add('active');
            if (card) card.classList.add('active');
        });
    }
});

// Function to show services by category
function showServicesByCategory(category) {
    modalTitle.textContent = `${category} Services`;
    serviceContent.innerHTML = '<div class="loading"><i class="fas fa-spinner"></i><br>Loading services...</div>';
    modal.style.display = 'flex';
    fetch(`get_services.php?action=get_services_by_category&category=${encodeURIComponent(category)}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                if (data.services && data.services.length > 0) {
                    displayServices(data.services);
                } else {
                    serviceContent.innerHTML = `<div class="error">No ${category} services found. Please check if providers are available for this category.</div>`;
                }
            } else {
                serviceContent.innerHTML = '<div class="error">Error: ' + data.message + '</div>';
            }
        })
        .catch(error => {
            serviceContent.innerHTML = '<div class="error">Error loading services. Please try again.</div>';
            console.error('Error:', error);
        });
}

// Function to display services in the modal
function displayServices(services) {
    if (!services || services.length === 0) {
        serviceContent.innerHTML = '<div class="error">No services found.</div>';
        return;
    }
    let html = '<div class="providers-grid">';
    services.forEach(provider => {
        const mainServices = provider.main_service.split(',').map(s => s.trim());
        const subcategories = provider.subcategories.split(',').map(s => s.trim());
        const portfolioImages = provider.portfolio ? provider.portfolio.split(',').map(s => s.trim()) : [];
        const firstImage = portfolioImages.length > 0 ? portfolioImages[0] : '';
        const currentCategory = modalTitle.textContent.replace(' Services', '');
        let filteredMainServices = mainServices;
        let filteredSubcategories = subcategories;
        if (currentCategory === 'Painting') {
            filteredMainServices = mainServices.filter(service => service.toLowerCase().includes('painting'));
            filteredSubcategories = subcategories.filter(sub =>
                sub.toLowerCase().includes('painting') ||
                sub.toLowerCase().includes('paint') ||
                sub.toLowerCase().includes('color') ||
                sub.toLowerCase().includes('mural') ||
                sub.toLowerCase().includes('waterproof') ||
                sub.toLowerCase().includes('damp')
            );
        } else if (currentCategory === 'Interior Design') {
            filteredMainServices = mainServices.filter(service =>
                service.toLowerCase().includes('interior') || service.toLowerCase().includes('design')
            );
            filteredSubcategories = subcategories.filter(sub =>
                sub.toLowerCase().includes('interior design') ||
                sub.toLowerCase().includes('interior -') ||
                sub.toLowerCase().includes('ceiling & lighting') ||
                sub.toLowerCase().includes('space planning') ||
                sub.toLowerCase().includes('modular kitchen') ||
                sub.toLowerCase().includes('bathroom design') ||
                sub.toLowerCase().includes('carpentry & woodwork') ||
                sub.toLowerCase().includes('furniture design')
            );
        } else if (currentCategory === 'Restoration') {
            filteredMainServices = mainServices.filter(service =>
                service.toLowerCase().includes('restoration') || service.toLowerCase().includes('renovation')
            );
            filteredSubcategories = subcategories.filter(sub =>
                sub.toLowerCase().includes('restoration') ||
                sub.toLowerCase().includes('renovation') ||
                sub.toLowerCase().includes('repair') ||
                sub.toLowerCase().includes('plastering') ||
                sub.toLowerCase().includes('floor') ||
                sub.toLowerCase().includes('door') ||
                sub.toLowerCase().includes('window') ||
                sub.toLowerCase().includes('building') ||
                sub.toLowerCase().includes('transformation')
            );
        }
        if (filteredMainServices.length > 0 || filteredSubcategories.length > 0) {
            html += `
                <div class="provider-card">
                    <div class="provider-header">
                        <div>
                            <h3 class="provider-name">
                                ${provider.provider_name}
                                <i class="fas fa-check-circle verified-badge" title="Verified Provider"></i>
                            </h3>
                        </div>
                        <div class="provider-actions">
                            <button class="btn-book-consultation" data-provider-id="${provider.provider_id || ''}" data-provider-name="${provider.provider_name}">Book Consultation</button>
                            <button class="btn-request-quote" data-provider-id="${provider.provider_id || ''}" data-provider-name="${provider.provider_name}" data-service-type="${filteredMainServices[0] || ''}">Request a Quote</button>
                        </div>
                    </div>
                    <div class="service-tags">
                        ${filteredMainServices.map((service, index) =>
                            `<span class="service-tag ${index === 0 ? 'primary' : 'secondary'}">${service}</span>`
                        ).join('')}
                        ${filteredSubcategories.map(sub =>
                            `<span class="service-tag highlight">${sub}</span>`
                        ).join('')}
                    </div>
                    <div class="contact-details">
                        <p><strong>Email:</strong> ${provider.provider_email || 'Not provided'}</p>
                        <p><strong>Phone:</strong> ${provider.provider_phone || 'Not provided'}</p>
                        <p><strong>Address:</strong> ${provider.provider_address || 'Not provided'}</p>
                    </div>
                    ${firstImage ? `
                        <div class="portfolio-section">
                            <div class="portfolio-label">Portfolio:</div>
                            <img src="../public/assets/images/${firstImage}" alt="Portfolio" class="portfolio-image" onerror="this.style.display='none'">
                        </div>
                    ` : ''}
                </div>
            `;
        }
    });
    html += '</div>';
    serviceContent.innerHTML = html;
    addButtonEventListeners();
}

// Function to add event listeners to modal buttons
function addButtonEventListeners() {
    // Book Consultation buttons
    const bookConsultationBtns = document.querySelectorAll('#serviceModal .btn-book-consultation');
    bookConsultationBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const providerId = this.getAttribute('data-provider-id');
            if (providerId && providerId !== '') {
                modal.style.display = 'none';
                // ...existing code for booking modal logic...
            } else {
                alert('Provider information not available. Provider ID: ' + providerId);
            }
        });
    });
    // Request Quote buttons
    const requestQuoteBtns = document.querySelectorAll('#serviceModal .btn-request-quote');
    requestQuoteBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const providerId = this.getAttribute('data-provider-id');
            const providerName = this.getAttribute('data-provider-name');
            let serviceType = '';
            if (this.hasAttribute('data-service-type')) {
                serviceType = this.getAttribute('data-service-type');
            }
            window.selectedServiceType = serviceType;
            if (providerId && providerId !== '') {
                modal.style.display = 'none';
                // ...existing code for quote modal logic...
            } else {
                alert('Provider information not available. Provider ID: ' + providerId);
            }
        });
    });
}
</script>

<script src="assets/js/serviceprovider.js"></script>
<?php 
include 'footer.php'; 
?>