<?php
// Try different paths to find Database.php
if (file_exists('../config/Database.php')) {
    require_once '../config/Database.php';
} elseif (file_exists('config/Database.php')) {
    require_once 'config/Database.php';
} elseif (file_exists(__DIR__ . '/../config/Database.php')) {
    require_once __DIR__ . '/../config/Database.php';
} else {
    throw new Exception('Database.php file not found');
}

class Product {
    private $conn;
    private $table_name = "products";

    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Get all products
     * @param string $service_type Optional filter by service type
     * @param string $category Optional filter by category
     * @return array Array of products
     */
    public function getAllProducts($service_type = null, $category = null) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE status = 'active'";
        $params = [];

        if ($service_type) {
            $query .= " AND service_type = :service_type";
            $params[':service_type'] = $service_type;
        }

        if ($category && $category !== 'all') {
            $query .= " AND category = :category";
            $params[':category'] = $category;
        }

        $query .= " ORDER BY created_at DESC";

        $stmt = $this->conn->prepare($query);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get product by ID
     * @param int $id Product ID
     * @return array|false Product data or false if not found
     */
    public function getProductById($id) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE id = :id AND status = 'active'";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }
        return false;
    }

    /**
     * Get products by service type
     * @param string $service_type Service type (interior-design, painting, restoration)
     * @return array Array of products
     */
    public function getProductsByServiceType($service_type) {
        return $this->getAllProducts($service_type);
    }

    /**
     * Get products by category
     * @param string $category Category (furniture, lighting, bath, kitchen, restoration)
     * @return array Array of products
     */
    public function getProductsByCategory($category) {
        return $this->getAllProducts(null, $category);
    }

    /**
     * Search products by name or brand
     * @param string $search_term Search term
     * @return array Array of matching products
     */
    public function searchProducts($search_term) {
        $query = "SELECT * FROM " . $this->table_name . " 
                  WHERE status = 'active' 
                  AND (name LIKE :search_term OR brand LIKE :search_term OR description LIKE :search_term)
                  ORDER BY created_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $search_term = "%" . $search_term . "%";
        $stmt->bindParam(':search_term', $search_term);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get featured products (high rating)
     * @param int $limit Number of products to return
     * @return array Array of featured products
     */
    public function getFeaturedProducts($limit = 6) {
        $query = "SELECT * FROM " . $this->table_name . " 
                  WHERE status = 'active' AND rating >= 4.5
                  ORDER BY rating DESC, created_at DESC
                  LIMIT :limit";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get product categories
     * @return array Array of unique categories
     */
    public function getCategories() {
        $query = "SELECT DISTINCT category FROM " . $this->table_name . " WHERE status = 'active' ORDER BY category";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Get service types
     * @return array Array of unique service types
     */
    public function getServiceTypes() {
        $query = "SELECT DISTINCT service_type FROM " . $this->table_name . " WHERE status = 'active' ORDER BY service_type";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Add new product
     * @param array $product_data Product data
     * @return bool Success status
     */
    public function addProduct($product_data) {
        $query = "INSERT INTO " . $this->table_name . " 
                  (name, brand, description, price, image_url, category, service_type, rating, badge, badge_type, stock_quantity) 
                  VALUES (:name, :brand, :description, :price, :image_url, :category, :service_type, :rating, :badge, :badge_type, :stock_quantity)";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(':name', $product_data['name']);
        $stmt->bindParam(':brand', $product_data['brand']);
        $stmt->bindParam(':description', $product_data['description']);
        $stmt->bindParam(':price', $product_data['price']);
        $stmt->bindParam(':image_url', $product_data['image_url']);
        $stmt->bindParam(':category', $product_data['category']);
        $stmt->bindParam(':service_type', $product_data['service_type']);
        $stmt->bindParam(':rating', $product_data['rating']);
        $stmt->bindParam(':badge', $product_data['badge']);
        $stmt->bindParam(':badge_type', $product_data['badge_type']);
        $stmt->bindParam(':stock_quantity', $product_data['stock_quantity']);
        
        return $stmt->execute();
    }

    /**
     * Update product
     * @param int $id Product ID
     * @param array $product_data Product data
     * @return bool Success status
     */
    public function updateProduct($id, $product_data) {
        $query = "UPDATE " . $this->table_name . " 
                  SET name = :name, brand = :brand, description = :description, price = :price, 
                      image_url = :image_url, category = :category, service_type = :service_type, 
                      rating = :rating, badge = :badge, badge_type = :badge_type, stock_quantity = :stock_quantity
                  WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->bindParam(':name', $product_data['name']);
        $stmt->bindParam(':brand', $product_data['brand']);
        $stmt->bindParam(':description', $product_data['description']);
        $stmt->bindParam(':price', $product_data['price']);
        $stmt->bindParam(':image_url', $product_data['image_url']);
        $stmt->bindParam(':category', $product_data['category']);
        $stmt->bindParam(':service_type', $product_data['service_type']);
        $stmt->bindParam(':rating', $product_data['rating']);
        $stmt->bindParam(':badge', $product_data['badge']);
        $stmt->bindParam(':badge_type', $product_data['badge_type']);
        $stmt->bindParam(':stock_quantity', $product_data['stock_quantity']);
        
        return $stmt->execute();
    }

    /**
     * Delete product (soft delete)
     * @param int $id Product ID
     * @return bool Success status
     */
    public function deleteProduct($id) {
        $query = "UPDATE " . $this->table_name . " SET status = 'inactive' WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        
        return $stmt->execute();
    }

    /**
     * Format price for display
     * @param float $price Price value
     * @return string Formatted price
     */
    public static function formatPrice($price) {
        return 'Rs. ' . number_format($price, 0, '.', ',');
    }

    /**
     * Generate star rating HTML
     * @param float $rating Rating value
     * @return string HTML for star rating
     */
    public static function generateStarRating($rating) {
        $stars = '';
        $full_stars = floor($rating);
        $has_half_star = ($rating - $full_stars) >= 0.5;
        
        // Full stars
        for ($i = 0; $i < $full_stars; $i++) {
            $stars .= '<i class="fas fa-star"></i>';
        }
        
        // Half star
        if ($has_half_star) {
            $stars .= '<i class="fas fa-star-half-alt"></i>';
        }
        
        // Empty stars
        $empty_stars = 5 - $full_stars - ($has_half_star ? 1 : 0);
        for ($i = 0; $i < $empty_stars; $i++) {
            $stars .= '<i class="far fa-star"></i>';
        }
        
        return $stars . '<span>(' . $rating . ')</span>';
    }

    /**
     * Get correct image path based on context
     * @param string $image_url
     * @param string $context 'admin' or 'public'
     * @return string
     */
    public static function getImagePath($image_url, $context = 'public') {
        if (empty($image_url)) {
            return ($context === 'admin') ? '../public/assets/images/placeholder.jpg' : 'assets/images/placeholder.jpg';
        }
        
        // Handle external URLs (like Unsplash)
        if (filter_var($image_url, FILTER_VALIDATE_URL)) {
            return $image_url;
        }
        
        // Handle different path formats
        if ($context === 'admin') {
            // For admin context, we need to go up one level from admin/ directory
            if (str_starts_with($image_url, 'uploads/')) {
                return '../public/' . $image_url;
            } elseif (str_starts_with($image_url, 'assets/')) {
                return '../public/' . $image_url;
            } else {
                return '../' . $image_url;
            }
        } else {
            // For public context
            if (str_starts_with($image_url, 'uploads/')) {
                return $image_url;
            } elseif (str_starts_with($image_url, 'assets/')) {
                return $image_url;
            } else {
                return $image_url;
            }
        }
    }
}
?>
