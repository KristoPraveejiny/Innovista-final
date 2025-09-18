<?php
require_once '../config/session.php';
require_once '../config/Database.php';
protectPage('provider');

// Get project ID from URL
$project_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

// Debug: Log the project ID
error_log("UpdateProgress Debug - Project ID: " . ($project_id ?: 'NULL'));

if (!$project_id) {
    error_log("UpdateProgress Debug - No project ID, redirecting to my_projects.php");
    header('Location: my_projects.php');
    exit();
}

// Get provider ID
$provider_id = $_SESSION['user_id'];

// Fetch project details
try {
    $db = (new Database())->getConnection();
    
    // Fetch project details with customer and provider info
    $stmt = $db->prepare("
        SELECT 
            p.id as project_id,
            p.status,
            p.start_date,
            p.end_date,
            cq.project_description,
            cq.amount,
            cq.advance,
            u.name as customer_name,
            u.email as customer_email,
            cq.id as custom_quotation_id
        FROM projects p
        JOIN custom_quotations cq ON p.quotation_id = cq.id
        JOIN users u ON cq.customer_id = u.id
        WHERE p.id = :project_id AND cq.provider_id = :provider_id
    ");
    $stmt->execute([':project_id' => $project_id, ':provider_id' => $provider_id]);
    $project_data = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$project_data) {
        error_log("UpdateProgress Debug - Project not found for ID: $project_id, Provider ID: $provider_id");
        header('Location: my_projects.php');
        exit();
    }
    
    // Fetch project updates (communication timeline)
    $stmt = $db->prepare("
        SELECT 
            pu.id,
            pu.update_text,
            pu.image_path,
            pu.created_at,
            u.name as poster_name,
            u.role as poster_role
        FROM project_updates pu
        JOIN users u ON pu.user_id = u.id
        WHERE pu.project_id = :project_id
        ORDER BY pu.created_at ASC
    ");
    $stmt->execute([':project_id' => $project_id]);
    $project_updates = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    error_log("Error fetching project details: " . $e->getMessage());
    header('Location: my_projects.php');
    exit();
}

// Include header after all validation is complete
$pageTitle = 'Update Progress';
require_once '../provider/provider_header.php';
?>

<div class="project-details-grid">
    <!-- Left Column: Project Timeline & Updates -->
    <div class="timeline-section">
        <h3>Project Timeline & Updates</h3>
        <div class="content-card">
            <?php if (empty($project_updates)): ?>
                <div class="no-updates">
                    <div class="timeline-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="timeline-content">
                        <h4>No updates yet.</h4>
                        <p>Your project timeline will appear here as the work progresses.</p>
                    </div>
                </div>
            <?php else: ?>
                <div class="timeline">
                    <?php foreach ($project_updates as $update): ?>
                        <div class="timeline-item">
                            <div class="timeline-marker"></div>
                            <div class="timeline-content">
                                <div class="update-header">
                                    <span class="poster-name"><?php echo htmlspecialchars($update['poster_name']); ?></span>
                                    <span class="poster-role">(<?php echo ucfirst($update['poster_role']); ?>)</span>
                                    <span class="update-time"><?php echo date('M d, Y g:i A', strtotime($update['created_at'])); ?></span>
                                </div>
                                <div class="update-text">
                                    <?php echo nl2br(htmlspecialchars($update['update_text'])); ?>
                                </div>
                                <?php if ($update['image_path']): ?>
                                    <div class="update-image">
                                        <img src="../<?php echo htmlspecialchars($update['image_path']); ?>" alt="Project update image" style="max-width: 100%; height: auto; border-radius: 8px; margin-top: 10px;">
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Right Column: Project Details & Communication -->
    <div class="project-details-section">
        <h3>Project Details</h3>
        <div class="content-card">
            <div class="project-info">
                <div class="info-row">
                    <span class="info-label">Customer:</span>
                    <span class="info-value customer-link"><?php echo htmlspecialchars($project_data['customer_name']); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Project:</span>
                    <span class="info-value"><?php echo htmlspecialchars($project_data['project_description']); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Status:</span>
                    <span class="status-badge status-<?php echo $project_data['status']; ?>">
                        <?php 
                        $status_display = [
                            'awaiting_advance' => 'Awaiting Advance',
                            'in_progress' => 'In Progress',
                            'awaiting_final_payment' => 'Awaiting Final Payment',
                            'completed' => 'Completed',
                            'disputed' => 'Disputed'
                        ];
                        echo $status_display[$project_data['status']] ?? ucfirst(str_replace('_', ' ', $project_data['status']));
                        ?>
                    </span>
                </div>
                <div class="info-row">
                    <span class="info-label">Quoted Cost:</span>
                    <span class="info-value">Rs <?php echo number_format($project_data['amount'], 2); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Advance Paid:</span>
                    <span class="info-value">Rs <?php echo number_format($project_data['advance'], 2); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Proposed Start:</span>
                    <span class="info-value"><?php echo date('d M Y', strtotime($project_data['start_date'])); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Proposed End:</span>
                    <span class="info-value"><?php echo date('d M Y', strtotime($project_data['end_date'])); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Remaining Payment:</span>
                    <span class="info-value remaining-payment">Rs <?php echo number_format($project_data['amount'] - $project_data['advance'], 2); ?></span>
                </div>
            </div>
        </div>
 
        <h3 style="margin-top: 2rem;">Update Project Status</h3>
        <div class="content-card">
            <form action="../handlers/handle_project_status_update.php" method="POST" class="status-form">
                <input type="hidden" name="project_id" value="<?php echo htmlspecialchars($project_data['project_id']); ?>">
                <div class="form-group">
                    <label for="status">Current Status:</label>
                    <select name="status" id="status" class="status-select">
                        <option value="in_progress" <?php echo $project_data['status'] === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                        <option value="awaiting_final_payment" <?php echo $project_data['status'] === 'awaiting_final_payment' ? 'selected' : ''; ?>>Awaiting Final Payment</option>
                        <option value="completed" <?php echo $project_data['status'] === 'completed' ? 'selected' : ''; ?>>Completed</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="status_message">Status Update Message (Optional):</label>
                    <textarea name="status_message" placeholder="Add a message about the status change..." rows="3"></textarea>
                </div>
                <button type="submit" class="btn-submit">Update Status</button>
            </form>
        </div>

        <h3 style="margin-top: 2rem;">Communicate with Customer</h3>
        <div class="content-card">
            <form action="../handlers/handle_project_communication.php" method="POST" class="communication-form">
                <input type="hidden" name="project_id" value="<?php echo htmlspecialchars($project_data['project_id']); ?>">
                <div class="form-group">
                    <textarea name="message" placeholder="Send a message or update to your customer..." rows="4" required></textarea>
                </div>
                <button type="submit" class="btn-submit">Send Message</button>
            </form>
        </div>
    </div>
</div>

<style>
.project-details-grid {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 2rem;
    margin-top: 2rem;
}

@media (max-width: 992px) {
    .project-details-grid {
        grid-template-columns: 1fr;
    }
}

.timeline-section h3,
.project-details-section h3 {
    color: #2d3748;
    margin-bottom: 1rem;
    font-size: 1.25rem;
    font-weight: 600;
}

.no-updates {
    display: flex;
    align-items: center;
    padding: 2rem;
    text-align: center;
    flex-direction: column;
}

.timeline-icon {
    width: 60px;
    height: 60px;
    background-color: #e2e8f0;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 1rem;
}

.timeline-icon i {
    font-size: 1.5rem;
    color: #718096;
}

.timeline-content h4 {
    margin: 0 0 0.5rem 0;
    color: #2d3748;
    font-weight: 600;
}

.timeline-content p {
    margin: 0;
    color: #718096;
    font-size: 0.9rem;
}

.timeline {
    padding: 1rem 0;
}

.timeline-item {
    display: flex;
    margin-bottom: 1.5rem;
    position: relative;
}

.timeline-marker {
    width: 12px;
    height: 12px;
    background-color: #0d9488;
    border-radius: 50%;
    margin-right: 1rem;
    margin-top: 0.5rem;
    flex-shrink: 0;
}

.timeline-item:not(:last-child)::after {
    content: '';
    position: absolute;
    left: 5px;
    top: 24px;
    width: 2px;
    height: calc(100% + 0.5rem);
    background-color: #e2e8f0;
}

.timeline-content {
    flex: 1;
}

.update-header {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 0.5rem;
    flex-wrap: wrap;
}

.poster-name {
    font-weight: 600;
    color: #2d3748;
}

.poster-role {
    color: #718096;
    font-size: 0.9rem;
}

.update-time {
    color: #a0aec0;
    font-size: 0.8rem;
    margin-left: auto;
}

.update-text {
    color: #4a5568;
    line-height: 1.5;
    margin-bottom: 0.5rem;
}

.update-image img {
    border: 1px solid #e2e8f0;
}

.project-info {
    padding: 1rem 0;
}

.info-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.75rem 0;
    border-bottom: 1px solid #f7fafc;
}

.info-row:last-child {
    border-bottom: none;
}

.info-label {
    font-weight: 500;
    color: #4a5568;
}

.info-value {
    color: #2d3748;
    font-weight: 500;
}

.customer-link {
    color: #0d9488;
    text-decoration: none;
}

.customer-link:hover {
    text-decoration: underline;
}

.communication-form {
    padding: 1rem 0;
}

.form-group {
    margin-bottom: 1rem;
}

.form-group textarea {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    font-family: inherit;
    font-size: 0.9rem;
    resize: vertical;
    min-height: 100px;
}

.form-group textarea:focus {
    outline: none;
    border-color: #0d9488;
    box-shadow: 0 0 0 3px rgba(13, 148, 136, 0.1);
}

.btn-submit {
    background-color: #0d9488;
    color: white;
    border: none;
    padding: 0.75rem 1.5rem;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    transition: background-color 0.2s;
}

.btn-submit:hover {
    background-color: #0f766e;
}

.status-badge {
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 600;
    text-transform: uppercase;
}

.status-pending {
    background-color: #fef3c7;
    color: #d97706;
}

.status-awaiting_advance {
    background-color: #fef2f2;
    color: #dc2626;
}

.status-in_progress {
    background-color: #fef3c7;
    color: #d97706;
}

.status-awaiting_final_payment {
    background-color: #fef2f2;
    color: #dc2626;
}

.status-completed {
    background-color: #f0fdf4;
    color: #16a34a;
}

.status-disputed {
    background-color: #fef2f2;
    color: #dc2626;
}

.remaining-payment {
    color: #dc2626;
    font-weight: 600;
}

.status-form {
    padding: 1rem 0;
}

.status-select {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    font-family: inherit;
    font-size: 0.9rem;
    background-color: white;
}

.status-select:focus {
    outline: none;
    border-color: #0d9488;
    box-shadow: 0 0 0 3px rgba(13, 148, 136, 0.1);
}

.form-group label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 500;
    color: #4a5568;
}
</style>

<script>
// Mark project notifications as read when page loads
document.addEventListener('DOMContentLoaded', function() {
    const projectId = <?php echo $project_id; ?>;
    
    // Mark notifications as read
    fetch('../handlers/mark_project_notifications_read.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'project_id=' + projectId
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            console.log('Project notifications marked as read');
            // Update the notification count in the header if it exists
            updateHeaderNotificationCount();
        }
    })
    .catch(error => {
        console.error('Error marking notifications as read:', error);
    });
});

// Function to update header notification count
function updateHeaderNotificationCount() {
    // This would update the main notification bell count
    // You can implement this based on your existing notification system
    const notificationBadge = document.querySelector('.notification-badge');
    if (notificationBadge) {
        // Decrease count by 1 or refresh the count
        const currentCount = parseInt(notificationBadge.textContent) || 0;
        if (currentCount > 0) {
            notificationBadge.textContent = currentCount - 1;
            if (currentCount - 1 === 0) {
                notificationBadge.style.display = 'none';
            }
        }
    }
}
</script>

<?php require_once '../includes/user_dashboard_footer.php'; ?>
