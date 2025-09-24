<?php
    // Set the specific title for this page
    $pageTitle = 'Home'; 
    
    // Include the master header. This will also start the session and include helper functions.
    include 'header.php';

    // --- ESTABLISH DATABASE CONNECTION FOR THIS PAGE ---
    require_once '../config/Database.php';
    $db = new Database();
    $conn = $db->getConnection();
    // ---------------------------------------------------

    // --- Fetch Dynamic Settings for the Homepage ---
    // --- Fetch Dynamic Achievements/Stats ---
    // 1. Projects Completed
    $projectsCompleted = 0;
    try {
        $stmt = $conn->prepare("SELECT COUNT(*) FROM project WHERE status = 'completed'");
        $stmt->execute();
        $projectsCompleted = (int)$stmt->fetchColumn();
    } catch (PDOException $e) { $projectsCompleted = 0; }

    // 2. Happy Clients (active customers)
    $happyClients = 0;
    try {
        $stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE role = 'customer' AND status = 'active'");
        $stmt->execute();
        $happyClients = (int)$stmt->fetchColumn();
    } catch (PDOException $e) { $happyClients = 0; }

    // 3. Verified Professionals (active providers)
    $verifiedProfessionals = 0;
    try {
        $stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE role = 'provider' AND status = 'active'");
        $stmt->execute();
        $verifiedProfessionals = (int)$stmt->fetchColumn();
    } catch (PDOException $e) { $verifiedProfessionals = 0; }
    $settings = [];
    try {
        $stmt_settings = $conn->prepare("SELECT setting_key, setting_value FROM settings");
        $stmt_settings->execute();
        while ($row = $stmt_settings->fetch(PDO::FETCH_ASSOC)) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
    } catch (PDOException $e) {
        error_log("Database error fetching settings: " . $e->getMessage());
        // Fallback: Provide empty defaults to prevent further errors if DB connection failed
        $settings = array_fill_keys([
            'homepage_hero_h1', 'homepage_hero_p', 'homepage_how_it_works_title',
            'homepage_services_title', 'homepage_products_title', 'homepage_products_description',
            'homepage_why_choose_us_title', 'homepage_testimonials_title', 'homepage_our_work_title',
            'homepage_our_work_description', 'homepage_faq_title', 'homepage_cta_title',
            'homepage_cta_description'
        ], ''); // Fill with empty strings as fallbacks
    }

    // Default values if settings are not found in DB or error occurred
    $settings['homepage_hero_h1'] = $settings['homepage_hero_h1'] ?? 'Transforming Spaces, Restoring Dreams';
    $settings['homepage_hero_p'] = $settings['homepage_hero_p'] ?? 'Your one-stop platform for interior design, painting, and restoration services in the Northern Province';
    $settings['homepage_how_it_works_title'] = $settings['homepage_how_it_works_title'] ?? 'How It Works';
    $settings['homepage_services_title'] = $settings['homepage_services_title'] ?? 'Our Core Services';
    $settings['homepage_products_title'] = $settings['homepage_products_title'] ?? 'Complete Your Project';
    $settings['homepage_products_description'] = $settings['homepage_products_description'] ?? 'Find high-quality products from trusted brands, all in one place. From paints to furniture, get everything you need for your project delivered.';
    $settings['homepage_why_choose_us_title'] = $settings['homepage_why_choose_us_title'] ?? 'Why Choose Innovista?';
    $settings['homepage_testimonials_title'] = $settings['homepage_testimonials_title'] ?? 'What Our Clients Say';
    $settings['homepage_our_work_title'] = $settings['homepage_our_work_title'] ?? 'Our Recent Work';
    $settings['homepage_our_work_description'] = $settings['homepage_our_work_description'] ?? 'A glimpse into the spaces we\'ve transformed.';
    $settings['homepage_faq_title'] = $settings['homepage_faq_title'] ?? 'Frequently Asked Questions';
    $settings['homepage_cta_title'] = $settings['homepage_cta_title'] ?? 'Ready to Start Your Next Project?';
    $settings['homepage_cta_description'] = $settings['homepage_cta_description'] ?? 'Whether you\'re looking to transform your home or grow your service business, the Innovista community is here for you. Join today for a seamless, transparent, and trustworthy experience.';


    // --- Dynamic Link Logic ---
    $userLoggedIn = isUserLoggedIn();
    $userRole = getUserRole();
    $loggedInUserId = getUserId(); 

    // Determine target page for generic "Explore Services" / "Find a Professional" links
    $servicesLink = 'login.php';
    if ($userLoggedIn) {
        if ($userRole === 'admin') {
            $servicesLink = '../admin/admin_dashboard.php';
        } else {
            $servicesLink = 'services.php';
        }
    }

    // Determine target page for "Join as a Provider" link
    $joinProviderLink = 'signup.php';
    if ($userLoggedIn) {
        if ($userRole === 'provider') {
            $joinProviderLink = '../provider/provider_dashboard.php';
        } elseif ($userRole === 'customer') {
            $joinProviderLink = '../customer/customer_dashboard.php';
        } elseif ($userRole === 'admin') {
            $joinProviderLink = '../admin/admin_dashboard.php'; 
        }
    }

    // --- Fetch Dynamic Data for Sections ---

    // 1. Fetch Featured Professionals (Top 3 approved providers)
    $featuredProfessionals = [];
    try {
        $stmt_providers = $conn->prepare("
            SELECT id, name, bio, profile_image_path
            FROM users
            WHERE role = 'provider' AND provider_status = 'approved'
            ORDER BY created_at DESC LIMIT 3
        ");
        $stmt_providers->execute();
        $featuredProfessionals = $stmt_providers->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Database error fetching featured professionals: " . $e->getMessage());
    }

    // 2. Fetch Recent Work / Portfolio Images from service table
    $recentServicePortfolios = [];
    try {
        $stmt_service = $conn->prepare("
            SELECT provider_name, main_service, subcategories, portfolio
            FROM service
            WHERE portfolio IS NOT NULL AND TRIM(portfolio) != ''
            ORDER BY created_at DESC LIMIT 6
        ");
        $stmt_service->execute();
        $recentServicePortfolios = $stmt_service->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Database error fetching service portfolios: " . $e->getMessage());
    }

    // 3. Fetch Products (hardcoded for now, as no 'products' table in schema)
    /*
    $featuredProducts = [];
    try {
        $stmt_products = $conn->prepare("SELECT id, name, category, price, image_path FROM products ORDER BY created_at DESC LIMIT 3");
        $stmt_products->execute();
        $featuredProducts = $stmt_products->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Database error fetching featured products: " . $e->getMessage());
    }
    */

    // 4. Testimonials
    $testimonials = [];
    try {
        $stmt_testimonials = $conn->prepare("
            SELECT r.comment, r.rating, c.name AS customer_name
            FROM reviews r
            JOIN users c ON r.customer_id = c.id
            ORDER BY r.created_at DESC LIMIT 3
        ");
        $stmt_testimonials->execute();
        $testimonials = $stmt_testimonials->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Database error fetching testimonials: " . $e->getMessage());
    }

?>
<main>
    <!-- =========================================
     HERO SLIDER SECTION
     ========================================= -->
    <section class="hero">
        <div class="hero-slider">
            <div class="hero-slide active" style="background-image: url('assets/images/hero/hero1.jpg');"></div>
            <div class="hero-slide" style="background-image: url('assets/images/hero/hero2.jpg');"></div>
            <div class="hero-slide" style="background-image: url('assets/images/hero/hero3.jpg');"></div>
        </div>
        <div class="hero-overlay"></div>
        <div class="hero-content container">
            <h1 style="color: white;"><?php echo htmlspecialchars($settings['homepage_hero_h1']); ?></h1>
            <p><?php echo htmlspecialchars($settings['homepage_hero_p']); ?></p>
            <a href="<?php echo htmlspecialchars($servicesLink); ?>" class="btn btn-primary">Explore Services</a>
        </div>
        <div class="slider-dots">
            <span class="dot active" data-slide="0"></span>
            <span class="dot" data-slide="1"></span>
            <span class="dot" data-slide="2"></span>
        </div>
    </section>
        
    <!-- =========================================
     HOW IT WORKS SECTION
     ========================================= -->
    <section class="how-it-works page-section">
        <div class="container">
            <h2 class="section-title"><?php echo htmlspecialchars($settings['homepage_how_it_works_title']); ?></h2>
            <div class="steps">
                <div class="step">
                    <div class="step-icon">1</div>
                    <h3>Discover</h3>
                    <p>Browse portfolios and services from top-rated local professionals.</p>
                </div>
                <div class="step">
                    <div class="step-icon">2</div>
                    <h3>Book & Quote</h3>
                    <p>Schedule appointments, get transparent quotes, and manage bookings.</p>
                </div>
                <div class="step">
                    <div class="step-icon">3</div>
                    <h3>Track & Complete</h3>
                    <p>Monitor your project's progress from start to finish with live updates.</p>
                </div>
            </div>
        </div>
    </section>
     
    <!-- =========================================
     OUR SERVICES SECTION
     ========================================= -->
    <section class="services-section page-section">
        <div class="container">
            <h2 class="section-title"><?php echo htmlspecialchars($settings['homepage_services_title']); ?></h2>
            <div class="services-grid">
                <!-- Interior Design Card -->
                <div class="service-card">
                    <img src="assets/images/service_interior.jpg" alt="Interior design of a modern living room">
                    <div class="service-card-content">
                        <h3>Interior Design</h3>
                        <p>From concept to completion, we bring your vision to life. Our experts create beautiful, functional spaces tailored to your lifestyle.</p>
                        <a href="<?php echo htmlspecialchars($servicesLink); ?>" class="btn btn-secondary">Learn More</a>
                    </div>
                </div>
                <!-- Painting Card -->
                <div class="service-card">
                    <img src="assets/images/service_paint2.jpg" alt="Professional painter working on a wall">
                    <div class="service-card-content">
                        <h3>Professional Painting</h3>
                        <p>A fresh coat of paint can redefine a room. Our professionals deliver flawless, lasting finishes for both interior and exterior projects.</p>
                        <a href="<?php echo htmlspecialchars($servicesLink); ?>" class="btn btn-secondary">Learn More</a>
                    </div>
                </div>
                <!-- Restoration Card -->
                <div class="service-card">
                    <img src="assets/images/service_restoration.jpg" alt="Restored antique wooden furniture">
                    <div class="service-card-content">
                        <h3>Restoration</h3>
                        <p>We breathe new life into your cherished spaces and furniture. Our restoration services preserve the beauty and integrity of your property.</p>
                        <a href="<?php echo htmlspecialchars($servicesLink); ?>" class="btn btn-secondary">Learn More</a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- =========================================
     SHOP OUR PRODUCTS SECTION
     ========================================= -->
    <section class="products-section page-section">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title"><?php echo htmlspecialchars($settings['homepage_products_title']); ?></h2>
                <p><?php echo htmlspecialchars($settings['homepage_products_description']); ?></p>
            </div>
            <div class="products-grid">
                <?php
                // For now, keeping hardcoded examples:
                ?>
                <!-- Product 1 (Hardcoded, but could be dynamic from 'products' table) -->
                <div class="product-card">
                    <img src="assets/images/port10.jpg" alt="Premium Interior Paint">
                    <div class="product-info">
                        <h4>Premium Interior Paint</h4>
                        <p class="product-category">Painting Supplies</p>
                        <div class="product-price">Rs 10000</div>
                        <a href="<?php echo htmlspecialchars($servicesLink); ?>" class="btn btn-secondary">View Product</a>
                    </div>
                </div>
                <!-- Product 2 -->
                <div class="product-card">
                    <img src="assets/images/port2.jpg" alt="Modern Sofa">
                    <div class="product-info">
                        <h4>Modern Velvet Sofa</h4>
                        <p class="product-category">Furniture</p>
                        <div class="product-price">Rs100000</div>
                        <a href="<?php echo htmlspecialchars($servicesLink); ?>" class="btn btn-secondary">View Product</a>
                    </div>
                </div>
                <!-- Product 3 -->
                <div class="product-card">
                    <img src="assets/images/port3.jpg" alt="Pendant Lights">
                    <div class="product-info">
                        <h4>Elegant Pendant Lights</h4>
                        <p class="product-category">Lighting</p>
                        <div class="product-price">Rs100000</div>
                        <a href="<?php echo htmlspecialchars($servicesLink); ?>" class="btn btn-secondary">View Product</a>
                    </div>
                </div>
            </div>
        </div>
    </section>
        
    <!-- =========================================
     WHY CHOOSE US SECTION
     ========================================= -->
    <section class="why-choose-us page-section">
        <div class="container">
            <h2 class="section-title"><?php echo htmlspecialchars($settings['homepage_why_choose_us_title']); ?></h2>
            <div class="features-grid">
                <div class="feature-item">
                    <i class="fas fa-user-shield"></i>
                    <h3>Vetted Professionals</h3>
                    <p>Every service provider is carefully verified for quality, experience, and reliability, ensuring you work with only the best.</p>
                </div>
                <div class="feature-item">
                    <i class="fas fa-file-invoice-dollar"></i>
                    <h3>Transparent Pricing</h3>
                    <p>Receive clear, upfront quotations with no hidden fees. You approve the final cost before any work begins.</p>
                </div>
                <div class="feature-item">
                    <i class="fas fa-tasks"></i>
                    <h3>Live Project Tracking</h3>
                    <p>Stay informed from start to finish. Follow your project's progress in real-time with stage updates directly from your dashboard.</p>
                </div>
                <div class="feature-item">
                    <i class="fas fa-credit-card"></i>
                    <h3>Secure Payments</h3>
                    <p>Our integrated payment system is fully encrypted. Manage all transactions easily and safely within the Innovista platform.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- =========================================
     ACHIEVEMENTS / STATS SECTION (Could be dynamic from DB counts)
     ========================================= -->
    <section class="achievements">
        <div class="container achievements-grid">
            <div class="achievement-item">
                <h3 class="counter" data-goal="<?php echo $projectsCompleted; ?>"><?php echo $projectsCompleted; ?></h3>
                <p>Projects Completed</p>
            </div>
            <div class="achievement-item">
                <h3 class="counter" data-goal="<?php echo $happyClients; ?>"><?php echo $happyClients; ?></h3>
                <p>Happy Clients</p>
            </div>
            <div class="achievement-item">
                <h3 class="counter" data-goal="<?php echo $verifiedProfessionals; ?>"><?php echo $verifiedProfessionals; ?></h3>
                <p>Verified Professionals</p>
            </div>
        </div>
    </section>

    <!-- =========================================
     FEATURED PROVIDERS SECTION
     ========================================= -->
    <section class="featured-providers page-section">
        <div class="container">
            <h2 class="section-title">Meet Our Top Professionals</h2>
            <div class="provider-cards">
                <?php if (!empty($featuredProfessionals)): ?>
                    <?php foreach ($featuredProfessionals as $provider): ?>
                        <div class="provider-card">
                            <img src="<?php echo getImageSrc($provider['profile_image_path'] ?? 'assets/images/default-avatar.jpg'); ?>" 
                                 alt="<?php echo htmlspecialchars($provider['name']); ?> Profile">
                            <div class="provider-info">
                                <h4><?php echo htmlspecialchars($provider['name']); ?></h4>
                                <p><?php echo htmlspecialchars(substr($provider['bio'] ?? 'Specialist', 0, 50)) . (strlen($provider['bio'] ?? '') > 50 ? '...' : ''); ?></p>
                                <a href="../public/signup.php?php echo htmlspecialchars($provider['id']); ?>" class="btn btn-link">View Proifile</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-center text-light">No featured professionals found at the moment.</p>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- =========================================
     TESTIMONIALS SECTION
     ========================================= -->
    <section class="testimonials page-section">
        <div class="container">
            <h2 style="color: white;" class="section-title"><?php echo htmlspecialchars($settings['homepage_testimonials_title']); ?></h2>
            <div class="testimonial-slider">
                <div class="testimonial-cards">
                    <?php if (!empty($testimonials)): ?>
                        <?php foreach ($testimonials as $testimonial): ?>
                            <div class="testimonial-card">
                                "<?php echo htmlspecialchars($testimonial['comment']); ?>"
                                <br><span class="author">- <?php echo htmlspecialchars($testimonial['customer_name']); ?></span>
                                <?php if (isset($testimonial['rating'])): ?>
                                    <div class="testimonial-rating">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <span class="fa fa-star<?php echo ($i <= (int)$testimonial['rating']) ? ' checked' : ''; ?>"></span>
                                        <?php endfor; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-center text-light">No testimonials available at the moment.</p>
                    <?php endif; ?>
<style>
.testimonial-rating .fa-star {
    color: #ccc;
}
.testimonial-rating .fa-star.checked {
    color: #f1c40f;
}
</style>
                </div>
            </div>
        </div>
    </section>
<!-- =========================================
     PORTFOLIO / OUR WORK SECTION
     ========================================= -->
<section class="portfolio-section page-section">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title"><?php echo htmlspecialchars($settings['homepage_our_work_title']); ?></h2>
            <p><?php echo htmlspecialchars($settings['homepage_our_work_description']); ?></p>
        </div>
        <div class="portfolio-grid homepage-grid">
            <?php if (!empty($recentServicePortfolios)): ?>
                <?php foreach ($recentServicePortfolios as $service): ?>
                    <?php 
                        $portfolioImages = array_filter(array_map('trim', explode(',', $service['portfolio'])));
                        $mainService = $service['main_service'] ?? '';
                        $providerName = $service['provider_name'] ?? '';
                        $subcategories = $service['subcategories'] ?? '';
                        $desc = $mainService;
                    ?>
                    <?php foreach ($portfolioImages as $img): ?>
                        <div class="portfolio-item">
                            <img src="assets/images/<?php echo htmlspecialchars($img); ?>" alt="<?php echo htmlspecialchars($providerName); ?> Portfolio">
                            <div class="portfolio-overlay">
                                <div class="portfolio-content">
                                    <h3><?php echo htmlspecialchars($providerName); ?></h3>
                                    <p><?php echo htmlspecialchars($desc); ?></p>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="text-center">No recent work to display.</p>
            <?php endif; ?>
        </div>
    </div>
</section>

    <!-- =========================================
     FAQ SECTION
     ========================================= -->
    <section class="faq-section page-section">
        <div class="container">
            <h2 class="section-title"><?php echo htmlspecialchars($settings['homepage_faq_title']); ?></h2>
            <div class="faq-accordion">
                <div class="faq-item">
                    <button class="faq-question">
                        <span>How do I book a service?</span>
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    <div class="faq-answer">
                        <p>Booking is simple! Just browse our services, select a professional you like, view their availability on the real-time calendar, and choose a time that works for you. You'll receive an instant email confirmation.</p>
                    </div>
                </div>
                <div class="faq-item">
                    <button class="faq-question">
                        <span>Are the service providers verified?</span>
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    <div class="faq-answer">
                        <p>Absolutely. Every service provider on Innovista goes through a verification process where we check their experience, past work, and reliability. We only partner with trusted, high-quality professionals to ensure your peace of mind.</p>
                    </div>
                </div>
                <div class="faq-item">
                    <button class="faq-question">
                        <span>How does payment work?</span>
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    <div class="faq-answer">
                        <p>Our system is designed for transparency and security. Based on the quotation you approve, you make a 25% advance payment to secure your booking. The remaining balance is only due after the project is completed to your satisfaction. All transactions are handled securely through our platform.</p>
                    </div>
                </div>
                <div class="faq-item">
                    <button class="faq-question">
                        <span>Can I track my project's progress?</span>
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    <div class="faq-answer">
                        <p>Yes! Our Live Project Tracking feature allows you to monitor your project's progress through your customer dashboard. Service providers upload stage-wise updates and photos, so you're always in the loop from start to finish.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- =========================================
     JOIN OUR COMMUNITY (CTA) SECTION
     ========================================= -->
    <section class="cta-section">
        <div class="container">
            <div class="cta-content">
                <h2><?php echo htmlspecialchars($settings['homepage_cta_title']); ?></h2>
                <p><?php echo htmlspecialchars($settings['homepage_cta_description']); ?></p>
                <div class="cta-buttons">
                    <a href="<?php echo htmlspecialchars($servicesLink); ?>" class="btn btn-primary">Find a Professional</a>
                    <a href="<?php echo htmlspecialchars($joinProviderLink); ?>" class="btn btn-secondary">Join as a Provider</a>
                </div>
            </div>
        </div>
    </section>

</main>   
<?php include './footer.php'; ?>