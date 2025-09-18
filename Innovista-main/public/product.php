<?php
// Define the page title for this specific page
$pageTitle = 'Shop Products'; 
// Include the master header, which also starts the session
include 'header.php'; 
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
                <a href="#" class="nav-item active" data-service="interior-design"><i class="fas fa-home"></i> Interior Design</a>
                <a href="#" class="nav-item" data-service="painting"><i class="fas fa-paint-brush"></i> Painting</a>
                <a href="#" class="nav-item" data-service="restoration"><i class="fas fa-tools"></i> Restoration</a>
            </nav>
        </div>
        <div class="sidebar-block" id="category-filter-block">
            <h3 class="sidebar-title">Browse by Category</h3>
            <div class="category-list">
                <a href="#" class="category-item active" data-category="all">All Products</a>
                <a href="#" class="category-item" data-category="furniture">Furniture</a>
                <a href="#" class="category-item" data-category="lighting">Lighting</a>
                <a href="#" class="category-item" data-category="bath">Bathroom</a>
                <a href="#" class="category-item" data-category="kitchen">Kitchen</a>
            </div>
        </div>
    </aside>

    <!-- Main Product Area -->
    <div class="product-area">
        <!-- Interior Design Section -->
        <section class="product-section active" id="interior-design-section">
            <div class="section-header">
                <h2>Interior Design Collection</h2>
                <p>Premium furnishings and materials curated for sophisticated living spaces.</p>
            </div>
            <div class="product-grid">
                <!-- Furniture -->
                <div class="product-item" data-category="furniture">
                    <div class="product-image"><img src="assets/images/images/modern-living-room-sofa.jpg" alt="Sofa"><div class="product-badge premium">Premium</div></div>
                    <div class="product-details"><p class="brand-name">Pottery Barn</p><h4>Buchanan Upholstered Sofa</h4><div class="product-rating"><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><span>(4.8)</span></div><div class="price-section"><span class="price">Rs. 1,19,900</span><button class="btn-add-cart"><i class="fas fa-shopping-cart"></i></button></div></div>
                </div>
                <!-- Lighting -->
                <div class="product-item" data-category="lighting">
                    <div class="product-image"><img src="assets/images/images/modern-table-lamp.jpg" alt="Lamp"><div class="product-badge">Modern</div></div>
                    <div class="product-details"><p class="brand-name">LightLux</p><h4>Modern Table Lamp</h4><div class="product-rating"><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star-half-alt"></i><span>(4.7)</span></div><div class="price-section"><span class="price">Rs. 8,500</span><button class="btn-add-cart"><i class="fas fa-shopping-cart"></i></button></div></div>
                </div>
                <!-- Bathroom -->
                <div class="product-item" data-category="bath">
                    <div class="product-image"><img src="assets/images/images/bathroom 11.webp" alt="Bathroom Sink"><div class="product-badge">Elegant</div></div>
                    <div class="product-details"><p class="brand-name">AquaLux</p><h4>Elegant Vanity Sink</h4><div class="product-rating"><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><span>(4.9)</span></div><div class="price-section"><span class="price">Rs. 26,500</span><button class="btn-add-cart"><i class="fas fa-shopping-cart"></i></button></div></div>
                </div>
                <!-- Kitchen -->
                 <div class="product-item" data-category="kitchen">
                    <div class="product-image"><img src="assets/images/images/kitchen-cabinet-1.jpg" alt="Kitchen Cabinets"><div class="product-badge">Modern</div></div>
                    <div class="product-details"><p class="brand-name">KitchenCraft</p><h4>Modern Cabinet Set</h4><div class="product-rating"><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="far fa-star"></i><span>(4.2)</span></div><div class="price-section"><span class="price">Rs. 85,000</span><button class="btn-add-cart"><i class="fas fa-shopping-cart"></i></button></div></div>
                </div>
                 <!-- More Furniture -->
                <div class="product-item" data-category="furniture">
                    <div class="product-image"><img src="assets/images/images/contemporary-bed-frame.jpg" alt="Bed Frame"><div class="product-badge">Popular</div></div>
                    <div class="product-details"><p class="brand-name">BedLux</p><h4>Contemporary Bed Frame</h4><div class="product-rating"><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star-half-alt"></i><span>(4.6)</span></div><div class="price-section"><span class="price">Rs. 28,500</span><button class="btn-add-cart"><i class="fas fa-shopping-cart"></i></button></div></div>
                </div>
                <!-- More Lighting -->
                <div class="product-item" data-category="lighting">
                    <div class="product-image"><img src="assets/images/images/luxury-ceiling-light.jpg" alt="Ceiling Light"><div class="product-badge premium">Luxury</div></div>
                    <div class="product-details"><p class="brand-name">LightLux</p><h4>Luxury Ceiling Fixture</h4><div class="product-rating"><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><span>(4.9)</span></div><div class="price-section"><span class="price">Rs. 28,500</span><button class="btn-add-cart"><i class="fas fa-shopping-cart"></i></button></div></div>
                </div>
            </div>
        </section>

        <!-- Painting Section -->
        <section class="product-section" id="painting-section">
            <div class="section-header">
                <h2>Painting Brands</h2>
                <p>Select a brand to get started, then choose your project type and color.</p>
            </div>
            <div id="painting-brand-grid" class="brand-grid">
                <!-- Brand cards will be rendered by JS -->
            </div>
            <div id="painting-color-panel" class="color-panel" style="display:none;">
                <button id="back-to-brands" class="btn btn-secondary" style="margin-bottom:1rem;">&larr; Back to Brands</button>
                <div id="paint-type-select-block" class="paint-type-select-block" style="margin-bottom:1.5rem; display:none;">
                    <label for="paintTypeSelect" style="font-weight:600; margin-right:8px;">Paint Type:</label>
                    <select id="paintTypeSelect" style="padding:6px 12px; border-radius:4px; border:1px solid #ccc;">
                        <option value="" selected disabled>Select paint type...</option>
                        <option value="Flat">Flat (Ceilings, Interior walls)</option>
                        <option value="Matte">Matte (Interior walls, Bedrooms)</option>
                        <option value="Eggshell">Eggshell (Interior walls)</option>
                        <option value="Satin">Satin (Bathrooms, Kitchens, Outdoor surfaces)</option>
                        <option value="Semi-gloss">Semi-gloss (Trim, Doors, Cabinets, Wood furniture)</option>
                        <option value="Gloss">Gloss (Doors, Trim, Furniture)</option>
                        <option value="High-gloss">High-gloss (Accent trim, Furniture)</option>
                        <option value="Pearl">Pearl (Decorative walls)</option>
                        <option value="Silk">Silk (Living rooms, Bedrooms)</option>
                        <option value="Velvet">Velvet (Feature walls)</option>
                        <option value="Enamel">Enamel (Metal, Wood, High-traffic areas)</option>
                        <option value="Acrylic">Acrylic (General purpose, Walls, Ceilings)</option>
                        <option value="Oil-based">Oil-based (Trim, Doors, Metal)</option>
                        <option value="Latex">Latex (Walls, Ceilings)</option>
                    </select>
                </div>
                <h3 id="color-panel-title"></h3>
                <div id="painting-color-grid" class="color-grid"></div>
            </div>
        </section>

        <!-- Restoration Section -->
        <section class="product-section" id="restoration-section">
             <div class="section-header">
                <h2>Restoration Materials</h2>
                <p>Everything you need to bring your treasured items back to life.</p>
            </div>
            <div class="product-grid">
                <!-- Restoration Product 1 -->
                <div class="product-item">
                    <div class="product-image"><img src="https://images.unsplash.com/photo-1595431658650-47759b855543?q=80&w=300" alt="Wood Polish"><div class="product-badge">Wood Care</div></div>
                    <div class="product-details"><p class="brand-name">RestorePro</p><h4>Premium Wood Polish</h4><div class="product-rating"><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><span>(4.9)</span></div><div class="price-section"><span class="price">Rs. 1,200</span><button class="btn-add-cart"><i class="fas fa-shopping-cart"></i></button></div></div>
                </div>
                <!-- Restoration Product 2 -->
                <div class="product-item">
                    <div class="product-image"><img src="https://images.unsplash.com/photo-1589939705384-5185137a7f0f?q=80&w=300" alt="Metal Cleaner"><div class="product-badge">Metal Care</div></div>
                    <div class="product-details"><p class="brand-name">MetalCare</p><h4>Metal Restoration Kit</h4><div class="product-rating"><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star-half-alt"></i><span>(4.5)</span></div><div class="price-section"><span class="price">Rs. 2,800</span><button class="btn-add-cart"><i class="fas fa-shopping-cart"></i></button></div></div>
                </div>
                <!-- Restoration Product 3 -->
                <div class="product-item">
                    <div class="product-image"><img src="https://images.unsplash.com/photo-1600585152220-0320f7f3a9d4?q=80&w=300" alt="Stone Sealer"><div class="product-badge">Stone Care</div></div>
                    <div class="product-details"><p class="brand-name">StoneGuard</p><h4>Stone Sealer & Protector</h4><div class="product-rating"><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="far fa-star"></i><span>(4.4)</span></div><div class="price-section"><span class="price">Rs. 3,500</span><button class="btn-add-cart"><i class="fas fa-shopping-cart"></i></button></div></div>
                </div>
                <!-- Restoration Product 4 -->
                <div class="product-item">
                    <div class="product-image"><img src="https://images.unsplash.com/photo-1556912173-356c3383a54b?q=80&w=300" alt="Restoration Tools"><div class="product-badge">Tools</div></div>
                    <div class="product-details"><p class="brand-name">ToolMaster</p><h4>Professional Tool Set</h4><div class="product-rating"><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><span>(4.8)</span></div><div class="price-section"><span class="price">Rs. 5,500</span><button class="btn-add-cart"><i class="fas fa-shopping-cart"></i></button></div></div>
                </div>
            </div>
        </section>
    </div>
</main>

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
<script src="assets/js/product-script.js"></script>
?>