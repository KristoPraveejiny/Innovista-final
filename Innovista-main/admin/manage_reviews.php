<?php
// admin/manage_reviews.php
require_once 'admin_header.php';
require_once '../config/Database.php';

// Initialize database connection
$db = new Database();
$conn = $db->getConnection();

// Handle review deletion
if (isset($_POST['delete_review']) && isset($_POST['review_id'])) {
    $review_id = filter_input(INPUT_POST, 'review_id', FILTER_VALIDATE_INT);
    
    if ($review_id) {
        try {
            $delete_stmt = $conn->prepare("DELETE FROM reviews WHERE id = :review_id");
            $delete_stmt->bindParam(':review_id', $review_id, PDO::PARAM_INT);
            
            if ($delete_stmt->execute()) {
                $success_message = "Review deleted successfully.";
            } else {
                $error_message = "Failed to delete review.";
            }
        } catch (PDOException $e) {
            $error_message = "Database error: " . $e->getMessage();
        }
    } else {
        $error_message = "Invalid review ID.";
    }
}

// Fetch all reviews with customer and provider information
try {
    $reviews_stmt = $conn->prepare("
        SELECT 
            r.id, 
            r.quotation_id, 
            r.rating, 
            r.comment, 
            r.created_at,
            customer.name as customer_name,
            customer.email as customer_email,
            provider.name as provider_name,
            provider.email as provider_email,
            COALESCE(cq.project_description, q.project_description, 'Project') as project_description
        FROM reviews r
        JOIN users customer ON r.customer_id = customer.id
        JOIN users provider ON r.provider_id = provider.id
        LEFT JOIN custom_quotations cq ON r.quotation_id = cq.id
        LEFT JOIN quotations q ON r.quotation_id = q.id
        ORDER BY r.created_at DESC
    ");
    $reviews_stmt->execute();
    $reviews = $reviews_stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $error_message = "Failed to fetch reviews: " . $e->getMessage();
    $reviews = [];
}
?>

<div class="reviews-management">
    <!-- Page Header -->
    <div class="page-header">
        <h1><i class="fas fa-star"></i> Customer Reviews Management</h1>
        <p>Monitor and manage all customer reviews across your platform</p>
    </div>
    
    <!-- Success/Error Messages -->
    <?php if (isset($success_message)): ?>
        <div class="alert success">
            <i class="fas fa-check-circle"></i>
            <span><?php echo htmlspecialchars($success_message); ?></span>
        </div>
    <?php endif; ?>
    
    <?php if (isset($error_message)): ?>
        <div class="alert error">
            <i class="fas fa-exclamation-triangle"></i>
            <span><?php echo htmlspecialchars($error_message); ?></span>
        </div>
    <?php endif; ?>

    <!-- Statistics Cards -->
    <div class="stats-grid">
        <div class="stat-card total">
            <div class="stat-icon">
                <i class="fas fa-comments"></i>
            </div>
            <div class="stat-content">
                <div class="stat-number"><?php echo count($reviews); ?></div>
                <div class="stat-label">Total Reviews</div>
            </div>
        </div>
        
        <div class="stat-card average">
            <div class="stat-icon">
                <i class="fas fa-star"></i>
            </div>
            <div class="stat-content">
                <?php 
                $total_rating = 0;
                $review_count = count($reviews);
                if ($review_count > 0) {
                    foreach ($reviews as $review) {
                        $total_rating += $review['rating'];
                    }
                    $average_rating = number_format($total_rating / $review_count, 1);
                } else {
                    $average_rating = 0;
                }
                ?>
                <div class="stat-number"><?php echo $average_rating; ?>/5</div>
                <div class="stat-label">Average Rating</div>
            </div>
        </div>
        
        <div class="stat-card five-star">
            <div class="stat-icon">
                <i class="fas fa-trophy"></i>
            </div>
            <div class="stat-content">
                <?php
                $five_star_count = 0;
                foreach ($reviews as $review) {
                    if ($review['rating'] == 5) $five_star_count++;
                }
                ?>
                <div class="stat-number"><?php echo $five_star_count; ?></div>
                <div class="stat-label">5-Star Reviews</div>
            </div>
        </div>
        
        <div class="stat-card satisfaction">
            <div class="stat-icon">
                <i class="fas fa-percentage"></i>
            </div>
            <div class="stat-content">
                <?php
                $satisfaction_rate = $review_count > 0 ? round(($five_star_count / $review_count) * 100) : 0;
                ?>
                <div class="stat-number"><?php echo $satisfaction_rate; ?>%</div>
                <div class="stat-label">Satisfaction Rate</div>
            </div>
        </div>
    </div>

    <!-- Reviews Section -->
    <div class="reviews-section">
        <div class="section-header">
            <h2> All Customer Reviews</h2>
            <span class="review-count"><?php echo count($reviews); ?> Reviews Found</span>
        </div>

        <?php if (empty($reviews)): ?>
            <div class="empty-state">
                <i class="fas fa-star" style="font-size: 4rem; color: #ddd;"></i>
                <h3>No Reviews Found</h3>
                <p>No customer reviews have been submitted yet.</p>
            </div>
        <?php else: ?>
            <div class="reviews-grid">
                <?php foreach ($reviews as $review): ?>
                    <div class="review-card">
                        <div class="review-header">
                            <div class="customer-info">
                                <div class="customer-avatar">
                                    <?php echo strtoupper(substr($review['customer_name'], 0, 1)); ?>
                                </div>
                                <div class="customer-details">
                                    <h4><?php echo htmlspecialchars($review['customer_name']); ?></h4>
                                    <p><?php echo htmlspecialchars($review['customer_email']); ?></p>
                                </div>
                            </div>
                            <div class="rating-display">
                                <div class="stars">
                                    <?php for($i = 1; $i <= 5; $i++): ?>
                                        <i class="fas fa-star <?php echo $i <= $review['rating'] ? 'filled' : 'empty'; ?>"></i>
                                    <?php endfor; ?>
                                </div>
                                <span class="rating-text"><?php echo $review['rating']; ?>/5</span>
                            </div>
                        </div>
                        
                        <div class="review-content">
                            <p class="review-comment">"<?php echo htmlspecialchars($review['comment']); ?>"</p>
                        </div>
                        
                        <div class="review-meta">
                            <div class="meta-item">
                                <i class="fas fa-user-tie"></i>
                                <span><strong>Provider:</strong> <?php echo htmlspecialchars($review['provider_name']); ?></span>
                            </div>
                            <div class="meta-item">
                                <i class="fas fa-project-diagram"></i>
                                <span><strong>Project:</strong> <?php echo htmlspecialchars($review['project_description']); ?></span>
                            </div>
                            <div class="meta-item">
                                <i class="fas fa-calendar"></i>
                                <span><strong>Date:</strong> <?php echo date('M j, Y g:i A', strtotime($review['created_at'])); ?></span>
                            </div>
                        </div>
                        
                        <div class="review-actions">
                            <form method="POST" onsubmit="return confirm('Are you sure you want to delete this review? This action cannot be undone.');">
                                <input type="hidden" name="review_id" value="<?php echo $review['id']; ?>">
                                <button type="submit" name="delete_review" class="delete-btn">
                                    <i class="fas fa-trash"></i> Delete Review
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'admin_footer.php'; ?>

<style>
/* Reviews Management Beautiful Styles */
.reviews-management {
    max-width: 1400px;
    margin: 0 auto;
    padding: 20px;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}

/* Page Header */
.page-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 40px 30px;
    border-radius: 16px;
    margin-bottom: 30px;
    text-align: center;
    box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
}

.page-header h1 {
    margin: 0 0 10px 0;
    font-size: 2.5rem;
    font-weight: 700;
    text-shadow: 0 2px 4px rgba(0,0,0,0.2);
}

.page-header p {
    margin: 0;
    font-size: 1.2rem;
    opacity: 0.9;
    font-weight: 300;
}

/* Alert Messages */
.alert {
    display: flex;
    align-items: center;
    padding: 16px 20px;
    border-radius: 12px;
    margin-bottom: 20px;
    font-weight: 500;
    gap: 12px;
}

.alert.success {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.alert.error {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

/* Statistics Grid */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 24px;
    margin-bottom: 40px;
}

.stat-card {
    background: white;
    border-radius: 16px;
    padding: 30px;
    box-shadow: 0 8px 32px rgba(0,0,0,0.1);
    display: flex;
    align-items: center;
    gap: 20px;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    border: 1px solid #f0f0f0;
}

.stat-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 40px rgba(0,0,0,0.15);
}

.stat-icon {
    width: 80px;
    height: 80px;
    border-radius: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2rem;
    color: white;
    flex-shrink: 0;
}

.stat-card.total .stat-icon { background: linear-gradient(135deg, #667eea, #764ba2); }
.stat-card.average .stat-icon { background: linear-gradient(135deg, #f093fb, #f5576c); }
.stat-card.five-star .stat-icon { background: linear-gradient(135deg, #4facfe, #00f2fe); }
.stat-card.satisfaction .stat-icon { background: linear-gradient(135deg, #43e97b, #38f9d7); }

.stat-content {
    flex: 1;
}

.stat-number {
    font-size: 2.5rem;
    font-weight: 700;
    color: #2d3748;
    line-height: 1;
    margin-bottom: 8px;
}

.stat-label {
    color: #718096;
    font-size: 1rem;
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

/* Reviews Section */
.reviews-section {
    background: white;
    border-radius: 16px;
    padding: 30px;
    box-shadow: 0 8px 32px rgba(0,0,0,0.1);
    border: 1px solid #f0f0f0;
}

.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
    padding-bottom: 20px;
    border-bottom: 2px solid #f7fafc;
}

.section-header h2 {
    margin: 0;
    font-size: 1.8rem;
    font-weight: 600;
    color: #2d3748;
    display: flex;
    align-items: center;
    gap: 12px;
}

.review-count {
    background: #e2e8f0;
    color: #4a5568;
    padding: 8px 16px;
    border-radius: 20px;
    font-size: 0.9rem;
    font-weight: 500;
}

/* Reviews Grid */
.reviews-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(500px, 1fr));
    gap: 24px;
}

.review-card {
    background: #fafafa;
    border: 1px solid #e2e8f0;
    border-radius: 16px;
    padding: 24px;
    transition: all 0.3s ease;
    position: relative;
}

.review-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 12px 32px rgba(0,0,0,0.12);
    border-color: #cbd5e0;
}

/* Review Header */
.review-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 20px;
}

.customer-info {
    display: flex;
    align-items: center;
    gap: 16px;
    flex: 1;
}

.customer-avatar {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
    font-weight: 700;
    text-transform: uppercase;
    flex-shrink: 0;
}

.customer-details h4 {
    margin: 0 0 4px 0;
    font-size: 1.1rem;
    font-weight: 600;
    color: #2d3748;
}

.customer-details p {
    margin: 0;
    color: #718096;
    font-size: 0.9rem;
}

.rating-display {
    text-align: right;
}

.stars {
    margin-bottom: 4px;
}

.stars i {
    font-size: 1.1rem;
    margin: 0 1px;
}

.stars .filled {
    color: #fbbf24;
}

.stars .empty {
    color: #e5e7eb;
}

.rating-text {
    font-size: 0.9rem;
    color: #4a5568;
    font-weight: 600;
}

/* Review Content */
.review-content {
    margin: 20px 0;
}

.review-comment {
    background: white;
    border: 1px solid #e2e8f0;
    border-left: 4px solid #fbbf24;
    border-radius: 12px;
    padding: 20px;
    margin: 0;
    font-style: italic;
    color: #4a5568;
    line-height: 1.7;
    font-size: 1rem;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
}

/* Review Meta */
.review-meta {
    margin: 20px 0;
}

.meta-item {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 8px;
    color: #4a5568;
    font-size: 0.9rem;
}

.meta-item i {
    color: #718096;
    width: 16px;
    text-align: center;
}

.meta-item strong {
    color: #2d3748;
}

/* Review Actions */
.review-actions {
    margin-top: 20px;
    padding-top: 20px;
    border-top: 1px solid #e2e8f0;
    text-align: right;
}

.delete-btn {
    background: linear-gradient(135deg, #fc8181, #f56565);
    color: white;
    border: none;
    padding: 12px 24px;
    border-radius: 10px;
    font-size: 0.9rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.delete-btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 6px 20px rgba(252, 129, 129, 0.4);
    background: linear-gradient(135deg, #f56565, #e53e3e);
}

/* Empty State */
.empty-state {
    text-align: center;
    padding: 60px 20px;
    color: #718096;
}

.empty-state i {
    display: block;
    margin-bottom: 20px;
    font-size: 4rem;
    color: #cbd5e0;
}

.empty-state h3 {
    margin: 0 0 12px 0;
    color: #4a5568;
    font-size: 1.5rem;
    font-weight: 600;
}

.empty-state p {
    margin: 0;
    font-size: 1rem;
    line-height: 1.6;
}

/* Responsive Design */
@media (max-width: 1200px) {
    .reviews-grid {
        grid-template-columns: repeat(auto-fill, minmax(450px, 1fr));
    }
}

@media (max-width: 768px) {
    .reviews-management {
        padding: 15px;
    }
    
    .page-header {
        padding: 30px 20px;
        margin-bottom: 20px;
    }
    
    .page-header h1 {
        font-size: 2rem;
    }
    
    .stats-grid {
        grid-template-columns: 1fr;
        gap: 16px;
        margin-bottom: 30px;
    }
    
    .stat-card {
        padding: 20px;
    }
    
    .stat-icon {
        width: 60px;
        height: 60px;
        font-size: 1.5rem;
    }
    
    .stat-number {
        font-size: 2rem;
    }
    
    .reviews-grid {
        grid-template-columns: 1fr;
        gap: 16px;
    }
    
    .review-card {
        padding: 20px;
    }
    
    .review-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 16px;
    }
    
    .rating-display {
        text-align: left;
        align-self: flex-start;
    }
    
    .section-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 16px;
    }
    
    .review-count {
        align-self: flex-start;
    }
}

@media (max-width: 480px) {
    .page-header h1 {
        font-size: 1.8rem;
    }
    
    .page-header p {
        font-size: 1rem;
    }
    
    .customer-info {
        gap: 12px;
    }
    
    .customer-avatar {
        width: 40px;
        height: 40px;
        font-size: 1rem;
    }
    
    .review-comment {
        padding: 16px;
        font-size: 0.95rem;
    }
}
</style>