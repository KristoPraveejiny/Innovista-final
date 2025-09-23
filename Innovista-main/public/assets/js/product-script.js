document.addEventListener('DOMContentLoaded', () => {
    // --- Hardcoded Painting Brands ---
    const hardcodedPaintingBrands = [
        {
            id: 'asianpaints',
            name: 'Asian Paints',
            image_path_full: 'assets/images/brands/asianpaints.png'
        },
        {
            id: 'berger',
            name: 'Berger Paints',
            image_path_full: 'assets/images/brands/berger.png'
        },
        {
            id: 'dulux',
            name: 'Dulux',
            image_path_full: 'assets/images/brands/dulux.png'
        }
        // Add more brands as needed
    ];
    // --- DOM Elements ---
    const serviceNavItems = document.querySelectorAll('.service-nav .nav-item');
    const categoryItems = document.querySelectorAll('.category-list .category-item');
    const productSections = document.querySelectorAll('.product-section');
    const categoryFilterBlock = document.getElementById('category-filter-block');

    // These IDs are added to public/product.php's HTML for filtering
    const interiorDesignGrid = document.getElementById('interior-design-grid'); 
    const paintingBrandGrid = document.getElementById('painting-brand-grid');
    const paintingToolsGrid = document.getElementById('painting-tools-grid');
    const restorationGrid = document.getElementById('restoration-grid'); // Added ID for restoration grid

    const paintingColorPanel = document.getElementById('painting-color-panel');
    const paintingColorGrid = document.getElementById('painting-color-grid');
    const colorPanelTitle = document.getElementById('color-panel-title');
    const backToBrandsBtn = document.getElementById('back-to-brands');
    const paintTypeSelect = document.getElementById('paintTypeSelect');
    const paintTypeSheen = document.getElementById('paintTypeSheen'); 

    const productModal = document.getElementById('productModal');
    const modalOverlay = productModal ? productModal.querySelector('.modal-overlay') : null;
    const modalCloseBtn = productModal ? productModal.querySelector('.modal-close-btn') : null;

    const cartSidebar = document.getElementById('cartSidebar');
    const cartCloseBtn = cartSidebar ? cartSidebar.querySelector('.cart-close-btn') : null;

    // --- State Variables ---
    let currentServiceType = 'interior-design'; // Default active service
    let currentCategory = 'all'; // Default active category for interior design
    let selectedPaintingBrandId = null;
    let selectedPaintingBrandName = '';
    
    // --- User Login Status (from PHP data attribute on body) ---
    const bodyElement = document.querySelector('body');
    const isUserLoggedIn = bodyElement ? bodyElement.dataset.loggedIn === 'true' : false;

    // --- Cart Management (Client-side) ---
    let cartItems = {}; // Stores client-side representation of the cart

    function updateCartUI() {
        const cartItemsContainer = document.querySelector('#cartSidebar .cart-items');
        const cartSubtotalEl = document.getElementById('cartSubtotal');
        let subtotal = 0;
        
        cartItemsContainer.innerHTML = ''; // Clear existing items

        if (Object.keys(cartItems).length === 0) {
            cartItemsContainer.innerHTML = '<p class="cart-empty-message">Your cart is empty.</p>';
            cartSubtotalEl.textContent = 'Rs. 0';
            return;
        }

        for (const cartItemId in cartItems) {
            const item = cartItems[cartItemId];
            const itemTotal = item.price * item.quantity;
            subtotal += itemTotal;

            const cartItemEl = document.createElement('div');
            cartItemEl.className = 'cart-item-entry'; // Add styling for this class in your CSS
            cartItemEl.innerHTML = `
                <img src="${item.image_path}" alt="${item.name}" class="cart-item-image">
                <div class="cart-item-details">
                    <h4>${item.name}</h4>
                    ${item.color ? `<p>Color: <span style="display:inline-block; width:15px; height:15px; background:${item.color}; border:1px solid #ccc; vertical-align:middle; border-radius:3px;"></span> ${item.color}</p>` : ''}
                    <div class="cart-item-qty-price">
                        <input type="number" class="cart-item-qty-input" data-cart-item-id="${cartItemId}" value="${item.quantity}" min="1">
                        <span>Rs. ${item.price.toFixed(2)} each</span>
                        <button class="cart-item-remove-btn" data-cart-item-id="${cartItemId}"><i class="fas fa-trash"></i></button>
                    </div>
                </div>
            `;
            cartItemsContainer.appendChild(cartItemEl);
        }
        cartSubtotalEl.textContent = `Rs. ${subtotal.toFixed(2)}`;

        // Add event listeners for quantity change and remove button
        cartItemsContainer.querySelectorAll('.cart-item-qty-input').forEach(input => {
            input.addEventListener('change', (e) => {
                const cartItemId = e.target.dataset.cartItemId;
                const newQuantity = parseInt(e.target.value);
                if (newQuantity > 0) {
                    const item = cartItems[cartItemId];
                    addToCart(item.db_product_id, item.name, item.price, newQuantity, item.image_path, item.color, true, cartItemId);
                } else {
                    removeFromCart(cartItemId);
                }
            });
        });

        cartItemsContainer.querySelectorAll('.cart-item-remove-btn').forEach(button => {
            button.addEventListener('click', (e) => {
                const cartItemId = e.target.closest('button').dataset.cartItemId;
                removeFromCart(cartItemId);
            });
        });
    }

    // Function to fetch current cart state from backend
    function fetchCart() {
        if (!isUserLoggedIn) return;
        fetch('../handlers/add_to_cart.php?action=get_cart') // Corrected path
            .then(response => {
                if (!response.ok) { throw new Error('Network response was not ok'); }
                return response.json();
            })
            .then(data => {
                if (data.success && data.cart) {
                    cartItems = data.cart; // Update client-side cart
                    updateCartUI();
                } else {
                    console.error('Failed to fetch cart:', data.message);
                }
            })
            .catch(error => console.error('Network error fetching cart:', error));
    }

    // Function to add/update item in cart via AJAX
    function addToCart(dbProductId, productName, productPrice, quantity, imagePath, color = '', isUpdate = false, cartItemId = null) {
        if (!isUserLoggedIn) {
            window.location.href = 'login.php';
            return;
        }
        const formData = new FormData();
        formData.append('action', 'add');
        formData.append('product_id', dbProductId); 
        formData.append('name', productName);
        formData.append('price', productPrice);
        formData.append('quantity', quantity);
        formData.append('image_path', imagePath);
        formData.append('color', color);
        formData.append('is_update', isUpdate ? 'true' : 'false');
        if (cartItemId) formData.append('cart_item_id', cartItemId);

        // --- IMPORTANT: Include CSRF token here if implemented (HIGHLY RECOMMENDED) ---
        // formData.append('csrf_token', 'YOUR_CSRF_TOKEN_FROM_PHP');

        fetch('../handlers/add_to_cart.php', { // Corrected path
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => {
            if (!response.ok) { throw new Error('Network response was not ok'); }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                // alert(data.message); 
                cartItems = data.cart; 
                updateCartUI();
                if (cartSidebar) cartSidebar.classList.add('active'); 
            } else {
                alert('Error adding to cart: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Network error adding to cart:', error);
            alert('Could not add to cart. Please try again.');
        });
    }

    // Function to remove item from cart via AJAX
    function removeFromCart(cartItemId) {
        if (!isUserLoggedIn) {
            window.location.href = 'login.php';
            return;
        }
        if (!confirm('Are you sure you want to remove this item from your cart?')) {
            return;
        }

        const formData = new FormData();
        formData.append('action', 'remove');
        formData.append('cart_item_id', cartItemId); 

        // --- IMPORTANT: Include CSRF token here if implemented ---
        // formData.append('csrf_token', 'YOUR_CSRF_TOKEN_FROM_PHP');

        fetch('../handlers/add_to_cart.php', { // Corrected path
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => {
            if (!response.ok) { throw new Error('Network response was not ok'); }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                // alert(data.message);
                cartItems = data.cart;
                updateCartUI();
            } else {
                alert('Error removing from cart: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Network error removing from cart:', error);
            alert('Could not remove from cart. Please try again.');
        });
    }

    // --- Hardcoded Painting Data (Matches your HTML) ---
    // These will be used to generate color cards dynamically within the Painting section
    const standardColors = [
        { name: 'Royal Blue', hex: '#2a3eb1', price: 1200 },
        { name: 'Sunshine Yellow', hex: '#ffe066', price: 1150 },
        { name: 'Classic White', hex: '#fff', price: 1100 },
        { name: 'Emerald Green', hex: '#50c878', price: 1050 },
        { name: 'Coral Red', hex: '#ff6f61', price: 1250 },
        { name: 'Ivory', hex: '#fffff0', price: 1000 },
        { name: 'Ocean Blue', hex: '#0077be', price: 1300 },
        { name: 'Peach', hex: '#ffdab9', price: 1200 },
        { name: 'Charcoal Grey', hex: '#36454f', price: 1350 },
        { name: 'Lavender', hex: '#b57edc', price: 1280 },
        { name: 'Mint Green', hex: '#98ff98', price: 1180 },
        { name: 'Rose Pink', hex: '#ff66cc', price: 1220 }
    ];
    // --- End hardcoded painting data ---


    // --- Rendering Functions ---
    // This function filters and displays products based on current service type and category
    function filterAndRenderProducts() {
        // Hide all product sections first
        productSections.forEach(section => section.classList.remove('active'));

        if (currentServiceType === 'interior-design') {
            document.getElementById('interior-design-section').classList.add('active');
            categoryFilterBlock.style.display = 'block';

            // Filter the products already present in HTML for Interior Design
            const allInteriorProducts = document.querySelectorAll('#interior-design-grid .product-item');
            allInteriorProducts.forEach(product => {
                const productCategory = product.dataset.category;
                if (currentCategory === 'all' || productCategory === currentCategory) {
                    product.style.display = 'block';
                } else {
                    product.style.display = 'none';
                }
            });

        } else if (currentServiceType === 'painting') {
            document.getElementById('painting-section').classList.add('active');
            categoryFilterBlock.style.display = 'none'; // Painting has its own brand/color filters
            renderPaintingBrandsGrid(); // Show painting brands first
            if (paintingToolsGrid) paintingToolsGrid.style.display = 'grid'; // Ensure tools grid is visible
        } else if (currentServiceType === 'restoration') {
            document.getElementById('restoration-section').classList.add('active');
            categoryFilterBlock.style.display = 'block';

            // Filter the products already present in HTML for Restoration
            const allRestorationProducts = document.querySelectorAll('#restoration-grid .product-item');
            allRestorationProducts.forEach(product => {
                const productCategory = product.dataset.category;
                if (currentCategory === 'all' || productCategory === currentCategory) {
                    product.style.display = 'block';
                } else {
                    product.style.display = 'none';
                }
            });
        }
    }


    function renderPaintingBrandsGrid() {
        if (!paintingBrandGrid) return;
        paintingBrandGrid.innerHTML = ''; // Clear any loading spinners

        if (hardcodedPaintingBrands.length === 0) {
            paintingBrandGrid.innerHTML = '<p class="text-center">No painting brands found.</p>';
            return;
        }
        hardcodedPaintingBrands.forEach((brand) => {
            const card = document.createElement('div');
            card.className = 'brand-card';
            card.setAttribute('data-brand-id', brand.id);
            card.setAttribute('data-brand-name', brand.name);
            card.innerHTML = `
                <img src="${brand.image_path_full}" alt="${brand.name}" class="brand-logo" />
                <div class="brand-title">${brand.name}</div>
            `;
            card.addEventListener('click', () => {
                selectedPaintingBrandId = brand.id;
                selectedPaintingBrandName = brand.name;
                
                paintingBrandGrid.style.display = 'none';
                paintingColorPanel.style.display = 'block';
                if (document.getElementById('paint-type-select-block')) { // Ensure element exists
                     document.getElementById('paint-type-select-block').style.display = 'block';
                     paintTypeSelect.selectedIndex = 0; // Reset paint type selection
                     if (paintTypeSheen) paintTypeSheen.textContent = '';
                }
                colorPanelTitle.textContent = `${brand.name} - Select Type & Color`;
                paintingColorGrid.innerHTML = ''; // Clear colors until type is selected
                
                // If a type is already selected (e.g., user navigated back), show colors
                if (paintTypeSelect && paintTypeSelect.value) {
                    showBrandColors(selectedPaintingBrandId, selectedPaintingBrandName, paintTypeSelect.value);
                }
            });
            paintingBrandGrid.appendChild(card);
        });
        paintingBrandGrid.style.display = 'grid';
        paintingColorPanel.style.display = 'none';
        if (document.getElementById('paint-type-select-block')) document.getElementById('paint-type-select-block').style.display = 'none';
    }

    function showBrandColors(brandId, brandName, paintType) {
        if (!paintingColorGrid) return;

        paintingColorGrid.innerHTML = '';
        colorPanelTitle.textContent = `${brandName} - ${paintType} Colors`;

        standardColors.forEach(color => {
            const colorCard = document.createElement('div');
            colorCard.className = 'color-card';
            const dynamicProductId = `paint-${brandId}-${color.hex.replace('#','')}-${paintType.replace(/\s/g,'')}`;
            colorCard.setAttribute('data-product-id', dynamicProductId); 
            colorCard.setAttribute('data-product-name', `${brandName} ${color.name} ${paintType} Paint`);
            colorCard.setAttribute('data-product-price', color.price);
            colorCard.setAttribute('data-image-path', 'assets/images/placeholder-paint.jpg'); // Placeholder image for paint
            colorCard.setAttribute('data-color', color.hex);

            colorCard.innerHTML = `
                <div class="color-swatch" style="background:${color.hex}"></div>
                <div class="color-info">
                    <div class="color-name">${color.name}</div>
                    <div class="color-price">Rs. ${color.price} / L</div>
                    <div class="color-liters">
                        <label>Liters: </label>
                        <select class="color-liters-select">
                            <option value="1">1L</option>
                            <option value="2">2L</option>
                            <option value="5">5L</option>
                            <option value="10">10L</option>
                        </select>
                    </div>
                </div>
                <button class="btn-add-cart painting-purchase-btn" title="Add to Cart"><i class="fas fa-shopping-cart"></i></button>
            `;
            paintingColorGrid.appendChild(colorCard);
        });

        // Add custom color option
        const customCard = document.createElement('div');
        customCard.className = 'color-card';
        customCard.setAttribute('data-product-id', `paint-${brandId}-custom`); 
        customCard.setAttribute('data-image-path', 'assets/images/placeholder-paint.jpg'); 
        
        customCard.innerHTML = `
            <div class="color-swatch" style="background:linear-gradient(135deg, #fff, #eee, #ccc, #000, #f00,#0f0,#00f,#ff0,#0ff,#f0f,#fa0,#0af)"></div>
            <div class="color-info custom-color-info" style="display:flex;flex-direction:column;gap:0.5rem;align-items:flex-start;">
                <div class="custom-color-label">Custom Color</div>
                <input type="color" class="custom-color-picker" value="#ffffff" style="width:40px;height:40px;">
                <input type="text" class="custom-color-hex" value="#FFFFFF" readonly>
                <input type="text" class="custom-color-name" placeholder="Custom Color Name" value="Custom White" readonly>
                <input type="number" class="custom-color-price" placeholder="Price/L" min="1" value="1100" readonly>
                <div style="display:flex;align-items:center;gap:8px;">
                    <label>Liters:</label>
                    <select class="color-liters-select">
                        <option value="1">1L</option>
                        <option value="2">2L</option>
                        <option value="5">5L</option>
                        <option value="10">10L</option>
                    </select>
                </div>
            </div>
            <button class="btn-add-cart painting-purchase-btn" title="Add to Cart"><i class="fas fa-shopping-cart"></i></button>
        `;
        paintingColorGrid.appendChild(customCard);
        
        // Custom color logic
        const colorInput = customCard.querySelector('.custom-color-picker');
        const priceInput = customCard.querySelector('.custom-color-price');
        const nameInput = customCard.querySelector('.custom-color-name');
        const hexInput = customCard.querySelector('.custom-color-hex');
        const litersSelect = customCard.querySelector('.color-liters-select');
        const customAddToCartBtn = customCard.querySelector('.btn-add-cart');

        function autoPrice(hex) {
            let sum = 0;
            for (let i = 0; i < hex.length; i++) sum += hex.charCodeAt(i);
            return 1000 + (sum % 501); // Price range 1000-1500
        }
        function autoColorName(hex) {
            const colorNames = {
                '#ffffff': 'White', '#000000': 'Black', '#ff0000': 'Red', '#00ff00': 'Green', '#0000ff': 'Blue',
                '#ffff00': 'Yellow', '#00ffff': 'Cyan', '#ff00ff': 'Magenta', '#b57edc': 'Lavender', '#98ff98': 'Mint', '#ff66cc': 'Rose',
                '#ffe066': 'Sunshine', '#2a3eb1': 'Royal Blue', '#50c878': 'Emerald', '#ff6f61': 'Coral', '#0077be': 'Ocean', '#ffdab9': 'Peach', '#36454f': 'Charcoal'
            };
            const currentPaintType = paintTypeSelect ? paintTypeSelect.value : '';
            return (colorNames[hex.toLowerCase()] || 'Custom ' + hex.toUpperCase()) + (currentPaintType ? ` ${currentPaintType} Paint` : ' Paint');
        }
        function updateCustomFields() {
            const currentHex = colorInput.value;
            priceInput.value = autoPrice(currentHex);
            nameInput.value = autoColorName(currentHex); 
            hexInput.value = currentHex.toUpperCase();

            const paintTypeSuffix = paintTypeSelect ? paintTypeSelect.value.replace(/\s/g,'') : '';
            customAddToCartBtn.dataset.productId = `paint-${brandId}-custom-${currentHex.replace('#','')}-${paintTypeSuffix}`; // More specific ID
            customAddToCartBtn.dataset.productName = nameInput.value;
            customAddToCartBtn.dataset.productPrice = priceInput.value;
            customAddToCartBtn.dataset.color = currentHex;
            customAddToCartBtn.dataset.quantity = litersSelect.value;
        }
        colorInput.addEventListener('input', updateCustomFields);
        litersSelect.addEventListener('change', updateCustomFields); 
        if (paintTypeSelect) {
            paintTypeSelect.addEventListener('change', updateCustomFields);
        }
        updateCustomFields(); // Set initial values
    }

    // --- Main Event Listeners ---
    // Service Type Navigation (Left Sidebar)
    serviceNavItems.forEach(item => {
        item.addEventListener('click', e => {
            e.preventDefault();
            const service = item.dataset.service;
            currentServiceType = service;
            
            serviceNavItems.forEach(i => i.classList.remove('active'));
            item.classList.add('active');

            // For dynamic content, just reload the page with the new service parameter
            const currentCategory = document.querySelector('.category-list .category-item.active')?.dataset.category || 'all';
            window.location.href = `?service=${service}&category=${currentCategory}`;
        });
    });

    // Category Filter Items (for Interior Design / Restoration)
    categoryItems.forEach(item => {
        item.addEventListener('click', e => {
            e.preventDefault();
            currentCategory = item.dataset.category;
            
            categoryItems.forEach(i => i.classList.remove('active'));
            item.classList.add('active');

            // For dynamic content, just reload the page with the new category parameter
            const currentService = document.querySelector('.service-nav .nav-item.active')?.dataset.service || 'interior-design';
            window.location.href = `?service=${currentService}&category=${currentCategory}`;
        });
    });

    // Back to Brands button (in Painting section)
    if (backToBrandsBtn) {
        backToBrandsBtn.addEventListener('click', () => {
            renderPaintingBrandsGrid();
            paintingColorPanel.style.display = 'none';
            if (document.getElementById('paint-type-select-block')) document.getElementById('paint-type-select-block').style.display = 'none';
            selectedPaintingBrandId = null;
            selectedPaintingBrandName = '';
        });
    }

    // Paint Type Selection listener
    if (paintTypeSelect) {
        paintTypeSelect.addEventListener('change', function() {
            const selectedPaintType = paintTypeSelect.value;
            if (paintTypeSheen) {
                paintTypeSheen.textContent = selectedPaintType ? `Paint Type Selected: ${selectedPaintType}` : '';
            }
            if (selectedPaintingBrandId && selectedPaintType) {
                showBrandColors(selectedPaintingBrandId, selectedPaintingBrandName, selectedPaintType);
            } else {
                paintingColorGrid.innerHTML = ''; // Clear if no brand/type selected
            }
        });
    }

    // --- Product Modal Functionality ---
    if (productModal) {
        document.body.addEventListener('click', (e) => { // Use event delegation
            const productImageTrigger = e.target.closest('.product-item .product-image');
            if (productImageTrigger) {
                if (!isUserLoggedIn) {
                    window.location.href = 'login.php';
                    return;
                }
                const productItem = productImageTrigger.closest('.product-item');
                // Extract data from the product item's dataset
                const productId = productItem.dataset.productId;
                const productName = productItem.querySelector('h4').textContent;
                const productBrand = productItem.querySelector('.brand-name').textContent;
                const productPrice = parseFloat(productItem.querySelector('.price').textContent.replace('Rs.', '').replace(',', '').trim());
                const productImage = productItem.querySelector('img').src;
                const productDescription = productItem.dataset.productDescription || 'No description available.'; // Get from data attribute
                
                // Get color options from the button's data attribute
                const addToCartButton = productItem.querySelector('.btn-add-cart');
                const productColorOptions = JSON.parse(addToCartButton.dataset.colorOptions || '[]');


                document.getElementById('modalImage').src = productImage;
                document.getElementById('modalBrand').textContent = productBrand;
                document.getElementById('modalTitle').textContent = productName;
                document.getElementById('modalTitle').dataset.productId = productId; // Set ID here
                document.getElementById('modalPrice').textContent = `Rs. ${productPrice.toLocaleString('en-IN')}`;
                document.getElementById('modalDescription').textContent = productDescription;

                // Populate color options dropdown
                const modalColorSelect = document.getElementById('modalColor');
                modalColorSelect.innerHTML = ''; // Clear previous options
                if (productColorOptions.length > 0) {
                    modalColorSelect.closest('.form-group').style.display = 'block';
                    productColorOptions.forEach(colorHex => {
                        const option = document.createElement('option');
                        option.value = colorHex;
                        option.textContent = colorHex; // Or a color name if you map hex to name
                        option.style.backgroundColor = colorHex;
                        option.style.color = getTextColorForBackground(colorHex);
                        modalColorSelect.appendChild(option);
                    });
                } else {
                    modalColorSelect.closest('.form-group').style.display = 'none'; // Hide if no color options
                }
                document.getElementById('modalQuantity').value = 1; // Reset quantity
                productModal.classList.add('active');
            }
        });

        const closeModal = () => productModal.classList.remove('active');
        if(modalOverlay) modalOverlay.addEventListener('click', closeModal);
        if(modalCloseBtn) modalCloseBtn.addEventListener('click', closeModal);

        // Helper to determine text color for background (for modal color options)
        function getTextColorForBackground(hexcolor) {
            const r = parseInt(hexcolor.substr(1, 2), 16);
            const g = parseInt(hexcolor.substr(3, 2), 16);
            const b = parseInt(hexcolor.substr(5, 2), 16);
            const y = ((r * 299) + (g * 587) + (b * 114)) / 1000;
            return (y >= 128) ? 'black' : 'white';
        }
    }

    // --- Cart Sidebar Functionality ---
    if(cartSidebar){
        if(cartCloseBtn) cartCloseBtn.addEventListener('click', () => cartSidebar.classList.remove('active'));
    }

    // --- Direct Purchase Event Listener ---
    document.body.addEventListener('click', function(e) {
        const purchaseButton = e.target.closest('.btn-purchase, .btn-add-cart-modal, .painting-purchase-btn');
        if (purchaseButton) {
            e.preventDefault();

            if (!isUserLoggedIn) {
                window.location.href = 'login.php';
                return;
            }

            // Handle direct purchase
            if (purchaseButton.classList.contains('btn-purchase')) {
                const productId = purchaseButton.dataset.productId;
                const productName = purchaseButton.dataset.productName;
                const productPrice = parseFloat(purchaseButton.dataset.productPrice);
                const imagePath = purchaseButton.dataset.imagePath;
                
                if (productId && productName && !isNaN(productPrice)) {
                    // Store product data in session for checkout
                    const productData = {
                        id: productId,
                        name: productName,
                        price: productPrice,
                        image_path: imagePath,
                        quantity: 1, // Default quantity, will be updated in checkout
                        color: ''
                    };
                    
                    // Send to checkout with product data
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = 'checkout.php';
                    
                    const productInput = document.createElement('input');
                    productInput.type = 'hidden';
                    productInput.name = 'product_data';
                    productInput.value = JSON.stringify(productData);
                    form.appendChild(productInput);
                    
                    document.body.appendChild(form);
                    form.submit();
                    return;
                } else {
                    alert('Could not get complete product information.');
                    return;
                }
            }

            let dbProductId, productName, productPrice, imagePath, color = '', quantity = 1;

            // Common attributes are expected on the button itself or a closest product-item
            dbProductId = addButton.dataset.productId;
            productName = addButton.dataset.productName;
            productPrice = parseFloat(addButton.dataset.productPrice);
            imagePath = addButton.dataset.imagePath; // Full path
            
            // Quantity and Color from modal or painting section
            if (addButton.closest('#productModal')) { // From product modal
                quantity = parseInt(document.getElementById('modalQuantity').value);
                color = document.getElementById('modalColor').value;
            } else if (addButton.closest('.color-card')) { // From painting color grid
                quantity = parseInt(addButton.closest('.color-card').querySelector('.color-liters-select').value);
                // Get color for painting items (standard or custom)
                if (addButton.closest('.custom-color-info')) { // Custom color
                    color = addButton.closest('.custom-color-info').querySelector('.custom-color-picker').value;
                } else { // Standard color swatch
                    const swatch = addButton.closest('.color-card').querySelector('.color-swatch');
                    color = window.getComputedStyle(swatch).backgroundColor; // Get computed RGB, or hex if set directly
                    color = rgbToHex(color); // Convert to HEX for consistency
                }
            } else { // From general product grid items (default quantity 1, no color unless set on button)
                 color = addButton.dataset.color || ''; // If product card button has a default color
            }

            if (dbProductId && productName && !isNaN(productPrice) && !isNaN(quantity) && quantity > 0) {
                addToCart(dbProductId, productName, productPrice, quantity, imagePath, color);
                if (productModal && productModal.classList.contains('active')) {
                    productModal.classList.remove('active'); // Close modal after adding
                }
            } else {
                alert('Could not get complete product information or quantity is invalid.');
            }
        }
    });

    // Helper to convert RGB to Hex (for painting colors)
    function rgbToHex(rgb) {
        if (!rgb || !rgb.startsWith('rgb')) return rgb;
        const parts = rgb.match(/^rgb\((\d+),\s*(\d+),\s*(\d+)\)$/);
        if (!parts) return rgb;
        function hex(x) {
            return ("0" + parseInt(x).toString(16)).slice(-2);
        }
        return "#" + hex(parts[1]) + hex(parts[2]) + hex(parts[3]);
    }


    // --- Initial Load Logic ---
    document.addEventListener('DOMContentLoaded', () => {
        // Fetch cart contents on page load
        fetchCart();

        // Render initial active service section based on `data-service="interior-design"` tab
        const initialServiceTab = document.querySelector('.service-nav .nav-item.active');
        if (initialServiceTab) {
            currentServiceType = initialServiceTab.dataset.service;
            currentCategory = document.querySelector('.category-list .category-item.active')?.dataset.category || 'all';
            filterAndRenderProducts(); // Initialize filtering and display
        }
    });

});