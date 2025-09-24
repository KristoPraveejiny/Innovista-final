<?php
// C:\xampp1\htdocs\Innovista-final\Innovista-main\customer\leave_review.php

require_once '../public/session.php';
require_once '../handlers/flash_message.php';

// --- User-specific authentication function ---
if (!function_exists('protectPage')) {
    function protectPage(string $requiredRole): void {
        if (!isUserLoggedIn()) {
            header("Location: ../public/login.php");
            exit();
        }
        if (getUserRole() !== $requiredRole && getUserRole() !== 'admin') { 
            set_flash_message('error', 'Access denied. You do not have permission to view this page.');
            header("Location: ../public/index.php");
            exit();
        }
    }
}
protectPage('customer');

$pageTitle = 'Leave Review';
require_once '../includes/user_dashboard_header.php';
require_once '../config/Database.php';

$db = (new Database())->getConnection();
$loggedInUserId = getUserId();

$provider_id = filter_input(INPUT_GET, 'provider_id', FILTER_VALIDATE_INT);
$custom_quotation_id = filter_input(INPUT_GET, 'custom_quotation_id', FILTER_VALIDATE_INT); // Associated project ID from custom_quotations

if (!$provider_id || !$custom_quotation_id) {
    set_flash_message('error', 'Invalid provider or project information for review.');
    header('Location: my_projects.php');
    exit();
}

$provider_name = 'N/A';
$project_description = 'N/A';
$has_reviewed = false;

// Fetch provider and project details for display
try {
    $stmt_provider = $db->prepare("SELECT name FROM users WHERE id = :provider_id AND role = 'provider'");
    $stmt_provider->bindParam(':provider_id', $provider_id, PDO::PARAM_INT);
    $stmt_provider->execute();
    $provider_data = $stmt_provider->fetch(PDO::FETCH_ASSOC);
    if ($provider_data) {
        $provider_name = htmlspecialchars($provider_data['name']);
    } else {
        set_flash_message('error', 'Provider not found.');
        header('Location: my_projects.php');
        exit();
    }

    $stmt_project_desc = $db->prepare("SELECT project_description FROM custom_quotations WHERE id = :cq_id AND customer_id = :customer_id");
    $stmt_project_desc->bindParam(':cq_id', $custom_quotation_id, PDO::PARAM_INT);
    $stmt_project_desc->bindParam(':customer_id', $loggedInUserId, PDO::PARAM_INT);
    $stmt_project_desc->execute();
    $project_desc_data = $stmt_project_desc->fetch(PDO::FETCH_ASSOC);
    if ($project_desc_data) {
        $project_description = htmlspecialchars($project_desc_data['project_description']);
    } else {
        set_flash_message('error', 'Project details not found or you do not have permission.');
        header('Location: my_projects.php');
        exit();
    }

    // Check if the customer has already reviewed this specific provider for this specific project
    $stmt_check_review = $db->prepare("SELECT id FROM reviews WHERE customer_id = :customer_id AND provider_id = :provider_id AND quotation_id = :quotation_id LIMIT 1");
    $stmt_check_review->bindParam(':customer_id', $loggedInUserId, PDO::PARAM_INT);
    $stmt_check_review->bindParam(':provider_id', $provider_id, PDO::PARAM_INT);
    $stmt_check_review->bindParam(':quotation_id', $custom_quotation_id, PDO::PARAM_INT);
    $stmt_check_review->execute();
    if ($stmt_check_review->fetch(PDO::FETCH_ASSOC)) {
        $has_reviewed = true;
        set_flash_message('info', 'You have already submitted a review for this provider on this project.');
    }

} catch (PDOException $e) {
    error_log("Leave Review page error: " . $e->getMessage());
    set_flash_message('error', 'Error loading review page. Please try again.');
    header('Location: my_projects.php');
    exit();
}
?>

<?php display_flash_message(); ?>

<h2>Leave a Review</h2>
<p>Share your experience with <strong><?php echo $provider_name; ?></strong> for the project: "<?php echo $project_description; ?>".</p>

<div class="content-card">
    <?php if ($has_reviewed): ?>
        <p class="text-center text-info">You have already submitted a review for this provider on this project.</p>
        <div class="action-buttons mt-4" style="justify-content: center;">
            <a href="view_project_history.php?id=<?php echo htmlspecialchars($custom_quotation_id); ?>" class="btn-link">Back to Project History</a>
            <a href="my_projects.php" class="btn-link">Back to My Projects</a>
        </div>
    <?php else: ?>
        <form action="../handlers/handle_submit_review.php" method="POST" class="form-section">
            <input type="hidden" name="provider_id" value="<?php echo htmlspecialchars($provider_id); ?>">
            <input type="hidden" name="custom_quotation_id" value="<?php echo htmlspecialchars($custom_quotation_id); ?>">
            <input type="hidden" name="customer_id" value="<?php echo htmlspecialchars($loggedInUserId); ?>">

            <div class="form-group">
                <label for="rating">Your Rating</label>
                <div class="rating-stars">
                    <!-- Star icons for rating selection -->
                    <i class="fas fa-star" data-rating="1"></i>
                    <i class="fas fa-star" data-rating="2"></i>
                    <i class="fas fa-star" data-rating="3"></i>
                    <i class="fas fa-star" data-rating="4"></i>
                    <i class="fas fa-star" data-rating="5"></i>
                    <input type="hidden" id="rating" name="rating" value="" required>
                    <small class="d-block text-muted mt-2">Click on a star to rate.</small>
                </div>
            </div>

            <div class="form-group mt-3">
                <label for="review_text">Your Review</label>
                <textarea id="review_text" name="review_text" rows="7" placeholder="Describe your experience with the provider..." required></textarea>
            </div>

            <div class="action-buttons mt-4">
                <button type="submit" class="btn-submit">Submit Review</button>
                <a href="view_project_history.php?id=<?php echo htmlspecialchars($custom_quotation_id); ?>" class="btn-link">Cancel</a>
            </div>
        </form>
    <?php endif; ?>
</div>

<?php require_once '../includes/user_dashboard_footer.php'; ?>

<!-- Inline JavaScript for star rating functionality (should be moved to assets/js/main.js or similar) -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const stars = document.querySelectorAll('.rating-stars .fa-star');
    const ratingInput = document.getElementById('rating');

    stars.forEach(star => {
        star.addEventListener('click', function() {
            const ratingValue = this.getAttribute('data-rating');
            ratingInput.value = ratingValue; // Set hidden input value

            // Update star appearance
            stars.forEach(s => {
                if (parseInt(s.getAttribute('data-rating')) <= ratingValue) {
                    s.classList.add('selected');
                } else {
                    s.classList.remove('selected');
                }
            });
        });

        // Hover effect for stars
        star.addEventListener('mouseover', function() {
            const hoverValue = this.getAttribute('data-rating');
            stars.forEach(s => {
                if (parseInt(s.getAttribute('data-rating')) <= hoverValue) {
                    s.classList.add('hovered');
                } else {
                    s.classList.remove('hovered');
                }
            });
        });
        star.addEventListener('mouseout', function() {
            stars.forEach(s => s.classList.remove('hovered'));
        });
    });

    // Restore selected stars if form was submitted with error and rating was set
    if (ratingInput.value) {
        const initialRating = parseInt(ratingInput.value);
        stars.forEach(s => {
            if (parseInt(s.getAttribute('data-rating')) <= initialRating) {
                s.classList.add('selected');
            }
        });
    }
});
</script>

<!-- Add custom styling for star rating to public/assets/css/dashboard.css or main.css -->
<style>
/* For leave_review.php */
.rating-stars {
    display: flex;
    justify-content: flex-start; /* Align stars to the left */
    gap: 5px;
    font-size: 1.8rem;
    color: #ccc; /* Default grey for unselected stars */
    cursor: pointer;
    margin-top: 0.5rem;
}
.rating-stars .fa-star {
    transition: color 0.2s ease;
}
.rating-stars .fa-star.selected,
.rating-stars .fa-star.hovered {
    color: #f1c40f; /* Yellow for selected/hovered stars */
}
/* Ensure the .d-block and .text-muted styles exist from Bootstrap-like utilities if you use them */
</style>