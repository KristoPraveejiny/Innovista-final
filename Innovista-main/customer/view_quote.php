<?php
// C:\xampp1\htdocs\Innovista-final\Innovista-main\customer\view_quote.php

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

// Get the quotation ID and type from URL parameters
$quotation_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$quote_type = filter_input(INPUT_GET, 'type', FILTER_SANITIZE_FULL_SPECIAL_CHARS); // 'original' or 'custom'

if (!$quotation_id || empty($quote_type)) {
    set_flash_message('error', 'Invalid Quotation ID or type.');
    header('Location: my_projects.php');
    exit();
}

$pageTitle = 'View Quotation';
require_once '../includes/user_dashboard_header.php';
require_once '../config/Database.php';

$db = (new Database())->getConnection();
$loggedInUserId = getUserId(); // Current customer's ID

$quote = null;
$is_custom_quote = false; // Flag to determine if it's a provider's custom quote

if ($quote_type === 'custom') {
    // Fetch custom quotation details and join original request for service_type and subcategory
    $stmt = $db->prepare('
        SELECT 
            cq.id, cq.quotation_id AS original_request_id, cq.provider_id, cq.customer_id, 
            cq.amount, cq.advance, cq.start_date, cq.end_date, cq.validity, cq.provider_notes, 
            cq.photos AS custom_quote_photos, cq.status, cq.created_at, cq.project_description,
            orig.service_type, orig.subcategory,
            prov.name as provider_name, prov.email as provider_email,
            cust.name as customer_name, cust.email as customer_email
        FROM custom_quotations cq 
        JOIN quotations orig ON cq.quotation_id = orig.id
        JOIN users prov ON cq.provider_id = prov.id 
        JOIN users cust ON cq.customer_id = cust.id
        WHERE cq.id = :id AND (cq.customer_id = :customer_id OR :user_role = "admin")
    ');
    $stmt->bindParam(':id', $quotation_id, PDO::PARAM_INT);
    $stmt->bindParam(':customer_id', $loggedInUserId, PDO::PARAM_INT);
$user_role = getUserRole();
$stmt->bindParam(':user_role', $user_role);    $stmt->execute();
    $quote = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($quote) {
        $is_custom_quote = true;
    }
} else { // quote_type is 'original'
    // Fetch original quotation request from `quotations`
    // Ensure this customer is the owner or admin
    $stmt = $db->prepare('
        SELECT 
            q.id, q.customer_id, q.provider_id, q.service_type, q.subcategory, q.project_description, 
            q.status, q.created_at, q.photos AS request_photos,
            prov.name as provider_name, prov.email as provider_email,
            cust.name as customer_name, cust.email as customer_email
        FROM quotations q 
        LEFT JOIN users prov ON q.provider_id = prov.id  -- Use LEFT JOIN as provider_id can be NULL
        JOIN users cust ON q.customer_id = cust.id
        WHERE q.id = :id AND (q.customer_id = :customer_id OR :user_role = "admin")
    ');
    $stmt->bindParam(':id', $quotation_id, PDO::PARAM_INT);
    $stmt->bindParam(':customer_id', $loggedInUserId, PDO::PARAM_INT);
$user_role = getUserRole();
$stmt->bindParam(':user_role', $user_role);    $stmt->execute();
    $quote = $stmt->fetch(PDO::FETCH_ASSOC);
    // If original quote found, provider_name might be null if not assigned
    if ($quote && $quote['provider_name'] === null) {
        $quote['provider_name'] = 'Not Assigned Yet';
    }
}


if (!$quote) {
    set_flash_message('error', 'Quotation not found or you do not have access.');
    header('Location: my_projects.php');
    exit();
}

// Prepare data for the booking modal
$bookingModalData = [];
if ($is_custom_quote) {
    $bookingModalData = [
        'customer_id' => $quote['customer_id'],
        'customer_name' => $quote['customer_name'],
        'customer_email' => $quote['customer_email'],
        'provider_id' => $quote['provider_id'],
        'provider_name' => $quote['provider_name'],
        'project_description' => $quote['project_description'], // Use cq.project_description
        'quotation_id' => $quote['id'], // ID of the custom_quotation
        'amount' => $quote['advance'], // Initial payment is the advance amount
        'booking_date' => $quote['start_date'] // Use the proposed start date as booking date
    ];
}

?>

<?php display_flash_message(); ?>

<h2>Review Quotation</h2>
<p>Please review the details of the quotation below and take action.</p>

<div class="content-card">
    <h3>Quotation Details</h3>
    <div class="details-list">
        <p><strong>Provider:</strong> <?php echo htmlspecialchars($quote['provider_name']); ?></p>
    <p><strong>Service Type:</strong> <?php echo htmlspecialchars($quote['service_type'] ?? 'N/A'); ?></p>
    <p><strong>Subcategory:</strong> <?php echo htmlspecialchars($quote['subcategory'] ?? 'N/A'); ?></p>
        <p><strong>Project Description:</strong> <?php echo htmlspecialchars($is_custom_quote ? $quote['project_description'] : $quote['project_description']); ?></p>
        <p><strong>Status:</strong> <span class="status-badge status-<?php 
            $status_lower = strtolower($quote['status']);
            if ($status_lower === 'awaiting quote') echo 'pending';
            elseif ($status_lower === 'sent' || $status_lower === 'pending') echo 'yellow'; // Awaiting customer action
            elseif ($status_lower === 'approved') echo 'approved'; // Customer has approved custom quote
            elseif ($status_lower === 'rejected' || $status_lower === 'declined') echo 'rejected';
            elseif ($status_lower === 'awaiting provider assignment') echo 'info'; // New status for unassigned
            else echo 'info';
        ?>"><?php 
            // FIX: Assign result of str_replace to a variable first
            $status_text_for_display = str_replace('_', ' ', $quote['status']);
            echo htmlspecialchars(ucwords($status_text_for_display)); 
        ?></span></p>
        
        <?php if ($is_custom_quote): ?>
            <p><strong>Quoted Price:</strong> <span style="font-size: 1.5rem; font-weight: 600; color: var(--primary-color);">Rs <?php echo number_format($quote['amount'], 2); ?></span></p>
            <p><strong>Advance (25%):</strong> Rs <?php echo number_format($quote['advance'], 2); ?></p>
            <p><strong>Proposed Timeline:</strong> <?php echo htmlspecialchars($quote['start_date']); ?> to <?php echo htmlspecialchars($quote['end_date']); ?></p>
            <p><strong>Validity Period:</strong> <?php echo htmlspecialchars($quote['validity']); ?> days</p>
            
            <?php if (!empty($quote['custom_quote_photos'])): ?>
                <div class="quotation-images mt-3"><strong>Provider Images:</strong><br>
                    <?php 
                    $photos = explode(',', $quote['custom_quote_photos']);
                    foreach ($photos as $img_path): 
                        if (!empty(trim($img_path))): ?>
                            <img src="<?php echo getImageSrc(trim($img_path)); ?>" alt="Quote Image" style="max-width:100px; height:auto; margin:5px; border-radius:6px; object-fit: cover;">
                        <?php endif;
                    endforeach; ?>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <p><strong>Quoted Price:</strong> <span style="font-size: 1.5rem; font-weight: 600; color: var(--primary-color);">-</span></p>
            <p><strong>Advance (25%):</strong> -</p>
            <p><strong>Proposed Timeline:</strong> -</p>
            <p><strong>Validity Period:</strong> -</p>
            <?php if (!empty($quote['request_photos'])): ?>
                <div class="quotation-images mt-3"><strong>Your Request Images:</strong><br>
                    <?php 
                    $photos = explode(',', $quote['request_photos']);
                    foreach ($photos as $img_path): 
                        if (!empty(trim($img_path))): ?>
                            <img src="<?php echo getImageSrc(trim($img_path)); ?>" alt="Request Image" style="max-width:100px; height:auto; margin:5px; border-radius:6px; object-fit: cover;">
                        <?php endif;
                    endforeach; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <h3 style="margin-top: 2rem;">Provider Notes</h3>
    <p><?php echo $is_custom_quote ? nl2br(htmlspecialchars($quote['provider_notes'])) : 'N/A'; ?></p>

    <div class="action-buttons" style="margin-top: 2rem; border-top: 1px solid var(--border-color); padding-top: 1.5rem; display: flex; gap: 16px;">
        <?php if ($is_custom_quote && ($quote['status'] === 'sent' || $quote['status'] === 'pending')): // Only allow action if it's a custom quote awaiting response ?>
            <button type="button" class="btn-submit" id="btnConfirmBooking" style="background-color: var(--status-active); flex:1;">Confirm Booking & Pay Advance</button>
            <form action="../handlers/handle_quote_action.php" method="POST" style="flex:1;">
                <input type="hidden" name="action" value="decline">
                <input type="hidden" name="quotation_id" value="<?php echo htmlspecialchars($quotation_id); ?>">
                <input type="hidden" name="quote_type" value="custom">
                <button type="submit" name="submit_decline" class="btn-submit" style="background-color: var(--status-rejected); flex:1;" onclick="return confirm('Are you sure you want to decline this quotation?');">Decline Quotation</button>
            </form>
        <?php elseif ($quote['status'] === 'Awaiting Provider Assignment'): ?>
            <p class="text-info" style="flex:1; text-align: center;">Your request is awaiting provider assignment.</p>
        <?php elseif ($quote['status'] === 'Awaiting Quote'): ?>
            <p class="text-info" style="flex:1; text-align: center;">Your request is awaiting a quote from the provider.</p>
        <?php elseif ($is_custom_quote && $quote['status'] === 'approved'): ?>
            <p class="text-info" style="flex:1; text-align: center;">This quotation has been approved and a project initiated.</p>
        <?php elseif ($is_custom_quote && ($quote['status'] === 'declined' || $quote['status'] === 'rejected')): ?>
            <p class="text-info" style="flex:1; text-align: center;">This quotation has been declined.</p>
        <?php else: // Other statuses or original request with no custom quote yet ?>
            <p class="text-info" style="flex:1; text-align: center;">No direct action available at this stage.</p>
        <?php endif; ?>
    </div>
</div>

<!-- Modal container for iframe -->
<div id="bookingModalContainer" style="display:none; position:fixed; top:0; left:0; width:100vw; height:100vh; background:rgba(0,0,0,0.6); z-index:9999; justify-content:center; align-items:center;">
    <div style="position:relative; width:420px; max-width:95vw; margin:auto;">
        <iframe id="bookingModalIframe" style="width:100%; height:600px; border:none; border-radius:18px; background:#fff;"></iframe>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var confirmBtn = document.getElementById('btnConfirmBooking');
    var modalContainer = document.getElementById('bookingModalContainer');
    var iframe = document.getElementById('bookingModalIframe');

    // Prepare data to pass to the modal iframe
    const bookingData = <?php echo json_encode($bookingModalData); ?>;

    if (confirmBtn && modalContainer && iframe && Object.keys(bookingData).length > 0) {
        confirmBtn.addEventListener('click', function() {
            // Construct query string
            const queryString = new URLSearchParams(bookingData).toString();
            iframe.src = "booking-modal.php?" + queryString;
            modalContainer.style.display = 'flex';
        });
        // Close modal by clicking outside iframe
        modalContainer.addEventListener('click', function(e) {
            if (e.target === modalContainer) {
                modalContainer.style.display = 'none';
            }
        });
    }

    // Listener for messages FROM the iframe (e.g., to close itself)
    window.addEventListener('message', function(event) {
        // Ensure the message is from a trusted origin in production
        // if (event.origin !== "http://your-trusted-domain.com") return; 

        if (event.data === 'closeBookingModal') {
            var modalContainer = document.getElementById('bookingModalContainer');
            if (modalContainer) {
                modalContainer.style.display = 'none';
                // Optionally, refresh parent page or relevant section after booking
                location.reload(); 
            }
        }
    }, false);
});
</script>

<?php require_once '../includes/user_dashboard_footer.php'; ?>