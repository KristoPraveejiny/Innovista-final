<?php
require_once 'admin_header.php';
require_once '../config/Database.php';
require_once '../classes/Product.php';
require_once '../classes/NotificationManager.php';

// Initialize database connection and product class
$database = new Database();
$conn = $database->getConnection();
$productClass = new Product($conn);
$notificationManager = new NotificationManager($conn);

// Handle actions
$action = $_GET['action'] ?? 'list';
$message = '';
$message_type = '';

// Handle success messages from redirects
if (isset($_GET['message']) && isset($_GET['type'])) {
    switch ($_GET['message']) {
        case 'updated':
            $message = 'Product updated successfully!';
            break;
        case 'added':
            $message = 'Product added successfully! All customers have been notified about the new product.';
            break;
        case 'deleted':
            $message = 'Product deleted successfully!';
            break;
        default:
            $message = '';
    }
    $message_type = $_GET['type'];
}

// Handle image upload
function handleImageUpload($file, $product_id = null) {
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        return false;
    }
    
    $upload_dir = '../public/uploads/products/';
    if (!is_dir($upload_dir)) {
        if (!mkdir($upload_dir, 0777, true)) {
            return false;
        }
    }
    
    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    
    if (!in_array($file_extension, $allowed_extensions)) {
        return false;
    }
    
    $filename = ($product_id ? 'product_' . $product_id : 'product_' . time()) . '_' . uniqid() . '.' . $file_extension;
    $file_path = $upload_dir . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $file_path)) {
        return 'uploads/products/' . $filename;
    }
    
    return false;
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        // Handle image upload first
        $image_url = $_POST['image_url'] ?? ''; // Keep existing image if no new upload
        
        // Only process new image upload if a file was actually uploaded
        if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === UPLOAD_ERR_OK && !empty($_FILES['product_image']['name'])) {
            $uploaded_image = handleImageUpload($_FILES['product_image'], $_POST['product_id'] ?? null);
            if ($uploaded_image) {
                $image_url = $uploaded_image;
            } else {
                // If image upload failed, keep the existing image
                $image_url = $_POST['image_url'] ?? '';
            }
        }
        
        switch ($_POST['action']) {
            case 'add':
                $product_data = [
                    'name' => $_POST['name'],
                    'brand' => $_POST['brand'],
                    'description' => $_POST['description'],
                    'price' => $_POST['price'],
                    'image_url' => $image_url,
                    'category' => $_POST['category'],
                    'service_type' => $_POST['service_type'],
                    'rating' => $_POST['rating'],
                    'badge' => $_POST['badge'],
                    'badge_type' => $_POST['badge_type'],
                    'stock_quantity' => $_POST['stock_quantity']
                ];
                
                if ($productClass->addProduct($product_data)) {
                    // Get the newly added product ID for notifications
                    $newProductId = $conn->lastInsertId();
                    
                    // Send notifications to all customers about the new product
                    $notificationCount = $notificationManager->notifyAllCustomersAboutNewProduct(
                        $newProductId,
                        $product_data['name'],
                        $product_data['category'],
                        $product_data['price']
                    );
                    
                    // Redirect to product list after successful add
                    header('Location: manage_products.php?message=added&type=success');
                    exit();
                } else {
                    $message = 'Failed to add product.';
                    $message_type = 'error';
                }
                break;
                
            case 'update':
                $product_id = $_POST['product_id'];
                $product_data = [
                    'name' => $_POST['name'],
                    'brand' => $_POST['brand'],
                    'description' => $_POST['description'],
                    'price' => $_POST['price'],
                    'image_url' => $image_url,
                    'category' => $_POST['category'],
                    'service_type' => $_POST['service_type'],
                    'rating' => $_POST['rating'],
                    'badge' => $_POST['badge'],
                    'badge_type' => $_POST['badge_type'],
                    'stock_quantity' => $_POST['stock_quantity']
                ];
                
                if ($productClass->updateProduct($product_id, $product_data)) {
                    // Redirect to product list after successful update
                    header('Location: manage_products.php?message=updated&type=success');
                    exit();
                } else {
                    $message = 'Failed to update product.';
                    $message_type = 'error';
                }
                break;
        }
    }
}

// Handle delete action
if (isset($_GET['delete'])) {
    $product_id = $_GET['delete'];
    if ($productClass->deleteProduct($product_id)) {
        // Redirect to product list after successful delete
        header('Location: manage_products.php?message=deleted&type=success');
        exit();
    } else {
        $message = 'Failed to delete product.';
        $message_type = 'error';
    }
}

// Get all products
$products = $productClass->getAllProducts();
$categories = $productClass->getCategories();
$service_types = $productClass->getServiceTypes();

// Get product for editing
$edit_product = null;
if (isset($_GET['edit'])) {
    $edit_product = $productClass->getProductById($_GET['edit']);
}
?>

<div class="main-content">
    <div class="content-header">
        <h1>Manage Products</h1>
        <p>Add, edit, and manage products for the store</p>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-<?php echo $message_type; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <div class="dashboard-section">
        <div class="section-header">
            <h2>Products Overview</h2>
            <div class="header-actions">
                <button class="btn btn-primary btn-icon" onclick="toggleAddForm()">
                    <i class="fas fa-plus"></i>
                    <span>Add New Product</span>
                </button>
            </div>
        </div>

        <!-- Add/Edit Product Form -->
        <div id="product-form" class="form-container" style="display: <?php echo $edit_product ? 'block' : 'none'; ?>;">
            <h3><?php echo $edit_product ? 'Edit Product' : 'Add New Product'; ?></h3>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="<?php echo $edit_product ? 'update' : 'add'; ?>">
                <?php if ($edit_product): ?>
                    <input type="hidden" name="product_id" value="<?php echo $edit_product['id']; ?>">
                <?php endif; ?>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="name">Product Name *</label>
                        <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($edit_product['name'] ?? ''); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="brand">Brand *</label>
                        <input type="text" id="brand" name="brand" value="<?php echo htmlspecialchars($edit_product['brand'] ?? ''); ?>" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" rows="3"><?php echo htmlspecialchars($edit_product['description'] ?? ''); ?></textarea>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="price">Price *</label>
                        <input type="number" id="price" name="price" step="0.01" value="<?php echo $edit_product['price'] ?? ''; ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="rating">Rating</label>
                        <input type="number" id="rating" name="rating" step="0.1" min="0" max="5" value="<?php echo $edit_product['rating'] ?? '0'; ?>">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="category">Category *</label>
                        <select id="category" name="category" required>
                            <option value="">Select Category</option>
                            <option value="furniture" <?php echo ($edit_product['category'] ?? '') === 'furniture' ? 'selected' : ''; ?>>Furniture</option>
                            <option value="lighting" <?php echo ($edit_product['category'] ?? '') === 'lighting' ? 'selected' : ''; ?>>Lighting</option>
                            <option value="bath" <?php echo ($edit_product['category'] ?? '') === 'bath' ? 'selected' : ''; ?>>Bathroom</option>
                            <option value="kitchen" <?php echo ($edit_product['category'] ?? '') === 'kitchen' ? 'selected' : ''; ?>>Kitchen</option>
                            <option value="paint" <?php echo ($edit_product['category'] ?? '') === 'paint' ? 'selected' : ''; ?>>Paint</option>
                            <option value="restoration" <?php echo ($edit_product['category'] ?? '') === 'restoration' ? 'selected' : ''; ?>>Restoration</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="service_type">Service Type *</label>
                        <select id="service_type" name="service_type" required>
                            <option value="">Select Service Type</option>
                            <option value="interior-design" <?php echo ($edit_product['service_type'] ?? '') === 'interior-design' ? 'selected' : ''; ?>>Interior Design</option>
                            <option value="painting" <?php echo ($edit_product['service_type'] ?? '') === 'painting' ? 'selected' : ''; ?>>Painting</option>
                            <option value="restoration" <?php echo ($edit_product['service_type'] ?? '') === 'restoration' ? 'selected' : ''; ?>>Restoration</option>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="product_image">Product Image *</label>
                        <input type="file" id="product_image" name="product_image" accept="image/*" onchange="previewImage(this)">
                        <div id="image-preview" class="image-preview">
                            <?php if ($edit_product && $edit_product['image_url']): ?>
                                <img src="<?php echo Product::getImagePath($edit_product['image_url'], 'admin'); ?>" alt="Current image" style="max-width: 200px; max-height: 150px; border-radius: 8px;">
                            <?php endif; ?>
                        </div>
                        <input type="hidden" id="image_url" name="image_url" value="<?php echo htmlspecialchars($edit_product['image_url'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label for="stock_quantity">Stock Quantity</label>
                        <input type="number" id="stock_quantity" name="stock_quantity" min="0" value="<?php echo $edit_product['stock_quantity'] ?? '0'; ?>">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="badge">Badge</label>
                        <input type="text" id="badge" name="badge" value="<?php echo htmlspecialchars($edit_product['badge'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label for="badge_type">Badge Type</label>
                        <select id="badge_type" name="badge_type">
                            <option value="">Select Badge Type</option>
                            <option value="premium" <?php echo ($edit_product['badge_type'] ?? '') === 'premium' ? 'selected' : ''; ?>>Premium</option>
                            <option value="modern" <?php echo ($edit_product['badge_type'] ?? '') === 'modern' ? 'selected' : ''; ?>>Modern</option>
                            <option value="elegant" <?php echo ($edit_product['badge_type'] ?? '') === 'elegant' ? 'selected' : ''; ?>>Elegant</option>
                            <option value="popular" <?php echo ($edit_product['badge_type'] ?? '') === 'popular' ? 'selected' : ''; ?>>Popular</option>
                            <option value="luxury" <?php echo ($edit_product['badge_type'] ?? '') === 'luxury' ? 'selected' : ''; ?>>Luxury</option>
                            <option value="wood-care" <?php echo ($edit_product['badge_type'] ?? '') === 'wood-care' ? 'selected' : ''; ?>>Wood Care</option>
                            <option value="metal-care" <?php echo ($edit_product['badge_type'] ?? '') === 'metal-care' ? 'selected' : ''; ?>>Metal Care</option>
                            <option value="stone-care" <?php echo ($edit_product['badge_type'] ?? '') === 'stone-care' ? 'selected' : ''; ?>>Stone Care</option>
                            <option value="tools" <?php echo ($edit_product['badge_type'] ?? '') === 'tools' ? 'selected' : ''; ?>>Tools</option>
                        </select>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary btn-icon">
                        <i class="fas fa-save"></i>
                        <span><?php echo $edit_product ? 'Update Product' : 'Add Product'; ?></span>
                    </button>
                    <button type="button" class="btn btn-secondary btn-icon" onclick="toggleAddForm()">
                        <i class="fas fa-times"></i>
                        <span>Cancel</span>
                    </button>
                </div>
            </form>
        </div>

        <!-- Products List -->
        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Image</th>
                        <th>Name</th>
                        <th>Brand</th>
                        <th>Category</th>
                        <th>Service Type</th>
                        <th>Price</th>
                        <th>Rating</th>
                        <th>Stock</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($products)): ?>
                        <tr>
                            <td colspan="10" class="text-center">No products found</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($products as $product): ?>
                            <tr>
                                <td>
                                    <img src="<?php echo Product::getImagePath($product['image_url'], 'admin'); ?>" 
                                         alt="<?php echo htmlspecialchars($product['name']); ?>" 
                                         class="product-thumbnail"
                                         onerror="this.src='../public/assets/images/placeholder.jpg'">
                                </td>
                                <td><?php echo htmlspecialchars($product['name']); ?></td>
                                <td><?php echo htmlspecialchars($product['brand']); ?></td>
                                <td><?php echo ucfirst($product['category']); ?></td>
                                <td><?php echo ucfirst(str_replace('-', ' ', $product['service_type'])); ?></td>
                                <td><?php echo Product::formatPrice($product['price']); ?></td>
                                <td>
                                    <div class="rating">
                                        <?php echo Product::generateStarRating($product['rating']); ?>
                                    </div>
                                </td>
                                <td><?php echo $product['stock_quantity']; ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo $product['status']; ?>">
                                        <?php echo ucfirst($product['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="?edit=<?php echo $product['id']; ?>" class="btn btn-sm btn-primary btn-icon" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="?delete=<?php echo $product['id']; ?>" 
                                           class="btn btn-sm btn-danger btn-icon" 
                                           title="Delete"
                                           onclick="return confirm('Are you sure you want to delete this product?')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
/* Product Management Styles */
.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    padding-bottom: 15px;
    border-bottom: 2px solid #e9ecef;
}

.header-actions {
    display: flex;
    gap: 10px;
}

.btn-icon {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 16px;
    border-radius: 8px;
    font-weight: 500;
    text-decoration: none;
    transition: all 0.3s ease;
    border: none;
    cursor: pointer;
}

.btn-icon:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.btn-primary {
    background: linear-gradient(45deg, #0d9488, #14b8a6);
    color: white;
}

.btn-secondary {
    background: linear-gradient(45deg, #6c757d, #868e96);
    color: white;
}

.btn-danger {
    background: linear-gradient(45deg, #dc3545, #e74c3c);
    color: white;
}

.product-thumbnail {
    width: 60px;
    height: 60px;
    object-fit: cover;
    border-radius: 8px;
    border: 2px solid #e9ecef;
}

.rating {
    font-size: 12px;
    color: #f39c12;
}

.status-badge {
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.status-active {
    background-color: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.status-inactive {
    background-color: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

.action-buttons {
    display: flex;
    gap: 8px;
}

.btn-sm {
    padding: 8px 12px;
    font-size: 12px;
    border-radius: 6px;
}

.form-container {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    padding: 30px;
    border-radius: 12px;
    margin-bottom: 30px;
    border: 1px solid #dee2e6;
    box-shadow: 0 4px 6px rgba(0,0,0,0.05);
}

.form-container h3 {
    color: #2c3e50;
    margin-bottom: 25px;
    font-size: 24px;
    font-weight: 600;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 25px;
    margin-bottom: 20px;
}

.form-group {
    display: flex;
    flex-direction: column;
}

.form-group label {
    margin-bottom: 8px;
    font-weight: 600;
    color: #495057;
    font-size: 14px;
}

.form-group input,
.form-group select,
.form-group textarea {
    padding: 12px 16px;
    border: 2px solid #e9ecef;
    border-radius: 8px;
    font-size: 14px;
    transition: all 0.3s ease;
    background: white;
}

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
    border-color: #0d9488;
    box-shadow: 0 0 0 3px rgba(13, 148, 136, 0.1);
    outline: none;
}

.image-preview {
    margin-top: 10px;
    padding: 15px;
    border: 2px dashed #dee2e6;
    border-radius: 8px;
    text-align: center;
    background: #f8f9fa;
}

.image-preview img {
    max-width: 200px;
    max-height: 150px;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.form-actions {
    display: flex;
    gap: 15px;
    margin-top: 30px;
    padding-top: 20px;
    border-top: 1px solid #dee2e6;
}

.table-container {
    background: white;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 4px 6px rgba(0,0,0,0.05);
    border: 1px solid #dee2e6;
}

.data-table {
    width: 100%;
    border-collapse: collapse;
}

.data-table th {
    background: linear-gradient(135deg, #0d9488, #14b8a6);
    color: white;
    padding: 15px 12px;
    text-align: left;
    font-weight: 600;
    font-size: 14px;
}

.data-table td {
    padding: 15px 12px;
    border-bottom: 1px solid #e9ecef;
    vertical-align: middle;
}

.data-table tr:hover {
    background-color: #f8f9fa;
}

.text-center {
    text-align: center;
    color: #6c757d;
    font-style: italic;
}

/* Responsive Design */
@media (max-width: 768px) {
    .form-row {
        grid-template-columns: 1fr;
        gap: 15px;
    }
    
    .header-actions {
        flex-direction: column;
        gap: 8px;
    }
    
    .action-buttons {
        flex-direction: column;
        gap: 5px;
    }
}
</style>

<script>
function toggleAddForm() {
    const form = document.getElementById('product-form');
    if (form.style.display === 'none') {
        form.style.display = 'block';
        // Clear form if not editing
        if (!form.querySelector('input[name="product_id"]')) {
            form.reset();
            document.getElementById('image-preview').innerHTML = '';
        }
    } else {
        form.style.display = 'none';
        // Redirect to clear edit parameters
        if (window.location.search.includes('edit=')) {
            window.location.href = 'manage_products.php';
        }
    }
}

function previewImage(input) {
    const preview = document.getElementById('image-preview');
    const imageUrlInput = document.getElementById('image_url');
    
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        
        reader.onload = function(e) {
            preview.innerHTML = '<img src="' + e.target.result + '" alt="Preview" style="max-width: 200px; max-height: 150px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">';
        };
        
        reader.readAsDataURL(input.files[0]);
    } else {
        preview.innerHTML = '';
    }
}

// Show form if editing
<?php if ($edit_product): ?>
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('product-form').style.display = 'block';
});
<?php endif; ?>

// Add smooth animations
document.addEventListener('DOMContentLoaded', function() {
    // Add loading states to buttons
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function() {
            const submitBtn = form.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i><span>Processing...</span>';
                submitBtn.disabled = true;
            }
        });
    });
});
</script>

<?php require_once 'admin_footer.php'; ?>
