<?php
// Define the page title for this specific page
$pageTitle = 'Shop Products'; 
// Include the master header, which also starts the session
include 'header.php'; 

// Include database and product class
require_once '../config/Database.php';
require_once '../classes/Product.php';

// Initialize database connection and product class
$database = new Database();
$conn = $database->getConnection();
$productClass = new Product($conn);

// Get filter parameters
$service_type = $_GET['service'] ?? 'interior-design';
$category = $_GET['category'] ?? 'all';

// Fetch products based on filters
$products = $productClass->getAllProducts($service_type, $category);
$categories = $productClass->getCategories();
$service_types = $productClass->getServiceTypes();
?>

<!-- =========================================
     PRODUCTS HERO SECTION
     ========================================= -->
<section class="products-hero">
    <div class="hero-content container">
        <h1>Shop Our Collection</h1>
        <p>Discover premium materials and furnishings for all your interior design, painting, and restoration needs.</p>
    </div>
</section>

<!-- =========================================
     MAIN CONTENT SECTION
     ========================================= -->
<main class="products-main-content container">
    <!-- Sidebar Navigation -->
    <aside class="sidebar">
        <div class="sidebar-block">
            <h3 class="sidebar-title">Service Type</h3>
            <nav class="service-nav">
                <a href="?service=interior-design&category=<?php echo $category; ?>" class="nav-item <?php echo $service_type === 'interior-design' ? 'active' : ''; ?>" data-service="interior-design"><i class="fas fa-home"></i> Interior Design</a>
                <a href="?service=painting&category=<?php echo $category; ?>" class="nav-item <?php echo $service_type === 'painting' ? 'active' : ''; ?>" data-service="painting"><i class="fas fa-paint-brush"></i> Painting</a>
                <a href="?service=restoration&category=<?php echo $category; ?>" class="nav-item <?php echo $service_type === 'restoration' ? 'active' : ''; ?>" data-service="restoration"><i class="fas fa-tools"></i> Restoration</a>
            </nav>
        </div>
        <div class="sidebar-block" id="category-filter-block">
            <h3 class="sidebar-title">Browse by Category</h3>
            <div class="category-list">
                <a href="?service=<?php echo $service_type; ?>&category=all" class="category-item <?php echo $category === 'all' ? 'active' : ''; ?>" data-category="all">All Products</a>
                <?php foreach ($categories as $cat): ?>
                    <a href="?service=<?php echo $service_type; ?>&category=<?php echo $cat; ?>" class="category-item <?php echo $category === $cat ? 'active' : ''; ?>" data-category="<?php echo $cat; ?>"><?php echo ucfirst($cat); ?></a>
                <?php endforeach; ?>
            </div>
        </div>
    </aside>

    <!-- Main Product Area -->
    <div class="product-area">
        <!-- Dynamic Product Section -->
        <section class="product-section active" id="product-section">
            <div class="section-header">
                <h2><?php echo ucfirst(str_replace('-', ' ', $service_type)); ?> Collection</h2>
                <p>
                    <?php 
                    switch($service_type) {
                        case 'interior-design':
                            echo 'Premium furnishings and materials curated for sophisticated living spaces.';
                            break;
                        case 'painting':
                            echo 'Select a brand to get started, then choose your project type and color.';
                            break;
                        case 'restoration':
                            echo 'Everything you need to bring your treasured items back to life.';
                            break;
                        default:
                            echo 'Discover premium materials and furnishings for all your needs.';
                    }
                    ?>
                </p>
            </div>
            
            <?php if ($service_type === 'painting'): ?>
                <!-- Painting Section - Show actual products from database -->
                <div class="product-grid">
                    <?php if (empty($products)): ?>
                        <div class="no-products">
                            <h3>No painting products found</h3>
                            <p>Try running the SQL query to add painting products, or check back later for new products.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($products as $product): ?>
                            <div class="product-item" data-category="<?php echo $product['category']; ?>" data-product-id="<?php echo $product['id']; ?>">
                                <div class="product-image">
                                    <img src="<?php echo htmlspecialchars($product['image_url']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" 
                                         onerror="this.src='assets/images/placeholder.jpg'">
                                    <?php if ($product['badge']): ?>
                                        <div class="product-badge <?php echo $product['badge_type'] ?? ''; ?>"><?php echo htmlspecialchars($product['badge']); ?></div>
                                    <?php endif; ?>
                                </div>
                                <div class="product-details">
                                    <p class="brand-name"><?php echo htmlspecialchars($product['brand']); ?></p>
                                    <h4><?php echo htmlspecialchars($product['name']); ?></h4>
                                    <div class="product-rating"><?php echo Product::generateStarRating($product['rating']); ?></div>
                                    <div class="price-section">
                                        <span class="price"><?php echo Product::formatPrice($product['price']); ?></span>
                                        <button class="btn-purchase" 
                                                data-product-id="<?php echo $product['id']; ?>"
                                                data-product-name="<?php echo htmlspecialchars($product['name']); ?>"
                                                data-product-price="<?php echo $product['price']; ?>"
                                                data-image-path="<?php echo Product::getImagePath($product['image_url']); ?>"
                                                title="Buy Now">
                                            <i class="fas fa-credit-card"></i> Buy Now
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <!-- Regular Product Grid -->
                <div class="product-grid">
                    <?php if (empty($products)): ?>
                        <div class="no-products">
                            <h3>No products found</h3>
                            <p>Try adjusting your filters or check back later for new products.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($products as $product): ?>
                            <div class="product-item" data-category="<?php echo $product['category']; ?>" data-product-id="<?php echo $product['id']; ?>">
                                <div class="product-image">
                                    <img src="<?php echo htmlspecialchars($product['image_url']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" 
                                         onerror="this.src='assets/images/placeholder.jpg'">
                                    <?php if ($product['badge']): ?>
                                        <div class="product-badge <?php echo $product['badge_type'] ?? ''; ?>"><?php echo htmlspecialchars($product['badge']); ?></div>
                                    <?php endif; ?>
                                </div>
                                <div class="product-details">
                                    <p class="brand-name"><?php echo htmlspecialchars($product['brand']); ?></p>
                                    <h4><?php echo htmlspecialchars($product['name']); ?></h4>
                                    <div class="product-rating"><?php echo Product::generateStarRating($product['rating']); ?></div>
                                    <div class="price-section">
                                        <span class="price"><?php echo Product::formatPrice($product['price']); ?></span>
                                        <button class="btn-purchase" 
                                                data-product-id="<?php echo $product['id']; ?>"
                                                data-product-name="<?php echo htmlspecialchars($product['name']); ?>"
                                                data-product-price="<?php echo $product['price']; ?>"
                                                data-image-path="<?php echo Product::getImagePath($product['image_url']); ?>"
                                                title="Buy Now">
                                            <i class="fas fa-credit-card"></i> Buy Now
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </section>

    </div>
</main>

<style>
.no-products {
    text-align: center;
    padding: 60px 20px;
    color: #666;
}

.no-products h3 {
    color: #333;
    margin-bottom: 10px;
}

.no-products p {
    font-size: 16px;
}
</style>

<!-- =========================================
     MODALS AND CART
     ========================================= -->
<div id="productModal" class="modal-wrapper">
    <div class="modal-overlay"></div>
    <div class="modal-content">
        <button class="modal-close-btn">×</button>
        <div class="modal-body">
            <div class="modal-image"><img id="modalImage" src="" alt="Product Image"></div>
            <div class="modal-details">
                <p class="modal-brand" id="modalBrand"></p>
                <h2 id="modalTitle"></h2>
                <div id="modalPrice" class="modal-price"></div>
                <p id="modalDescription" class="modal-description"></p>
                <div class="modal-options">
                    <div class="form-group"><label for="modalColor">Color:</label><select id="modalColor"></select></div>
                    <div class="form-group"><label for="modalQuantity">Quantity:</label><input type="number" id="modalQuantity" min="1" value="1"></div>
                </div>
                <button class="btn btn-primary btn-add-cart-modal">Add to Cart</button>
            </div>
        </div>
    </div>
</div>
<div id="cartSidebar" class="cart-sidebar">
    <div class="cart-header">
        <h3>Your Cart</h3>
        <button class="cart-close-btn">×</button>
    </div>
    <div class="cart-items">
        <p class="cart-empty-message">Your cart is empty.</p>
    </div>
    <div class="cart-footer">
        <div class="cart-total"><span>Subtotal:</span><span id="cartSubtotal">Rs. 0</span></div>
        <a href="<?php echo isUserLoggedIn() ? 'checkout.php' : 'login.php'; ?>" class="btn btn-primary btn-checkout">Proceed to Checkout</a>
    </div>
</div>

<?php 
include 'footer.php'; 
?>
<script src="assets/js/product-script.js"></script>?>
