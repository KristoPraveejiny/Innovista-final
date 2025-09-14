<?php 
$pageTitle = 'My Projects';
require_once '../provider/provider_header.php';
require_once '../config/session.php';
protectPage('provider'); 

// --- DUMMY DATA ---
$active_projects = [
    ['customer' => 'Alice Johnson', 'project' => 'Living Room Renovation', 'status' => 'In Progress', 'link' => 'update_project.php?id=1'],
];
$awaiting_payment = [
    ['customer' => 'Bob Williams', 'project' => 'Antique Chair Refurbishing', 'status' => 'Awaiting Final Payment', 'link' => 'update_project.php?id=2']
];
$completed_projects = [
    ['customer' => 'Charlie Brown', 'project' => 'Kitchen Backsplash', 'status' => 'Completed', 'link' => 'view_project_history.php?id=3']
];
?>

<h2>My Projects</h2>
<p>Track your active jobs, manage payments, and view your completed work history.</p>

<div class="dashboard-section">
    <h3>Active Projects</h3>
    <div class="content-card">
        <div class="table-wrapper">
            <table>
                <thead><tr><th>Customer</th><th>Project</th><th>Status</th><th>Action</th></tr></thead>
                <tbody>
                    <?php if (!empty($active_projects)): foreach ($active_projects as $project): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($project['customer']); ?></td>
                        <td><?php echo htmlspecialchars($project['project']); ?></td>
                        <td><span class="status-badge status-pending"><?php echo htmlspecialchars($project['status']); ?></span></td>
                        <td><a href="<?php echo $project['link']; ?>" class="btn-view">Update Progress</a></td>
                    </tr>
                    <?php endforeach; else: ?>
                        <tr><td colspan="4" style="text-align: center;">You have no active projects.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="dashboard-section">
    <h3>Awaiting Payment</h3>
    <div class="content-card">
        <div class="table-wrapper">
            <table>
                 <thead><tr><th>Customer</th><th>Project</th><th>Status</th><th>Action</th></tr></thead>
                <tbody>
                    <?php if (!empty($awaiting_payment)): foreach ($awaiting_payment as $project): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($project['customer']); ?></td>
                        <td><?php echo htmlspecialchars($project['project']); ?></td>
                        <td><span class="status-badge status-rejected"><?php echo htmlspecialchars($project['status']); ?></span></td>
                        <td><a href="<?php echo $project['link']; ?>" class="btn-view">View & Remind</a></td>
                    </tr>
                    <?php endforeach; else: ?>
                        <tr><td colspan="4" style="text-align: center;">No projects are awaiting final payment.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="dashboard-section">
    <h3>Completed Projects</h3>
    <div class="content-card">
        <div class="table-wrapper">
            <table>
                <thead><tr><th>Customer</th><th>Project</th><th>Status</th><th>Action</th></tr></thead>
                <tbody>
                    <?php if (!empty($completed_projects)): foreach ($completed_projects as $project): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($project['customer']); ?></td>
                        <td><?php echo htmlspecialchars($project['project']); ?></td>
                        <td><span class="status-badge status-approved"><?php echo htmlspecialchars($project['status']); ?></span></td>
                        <td><a href="<?php echo $project['link']; ?>" class="btn-view">View History</a></td>
                    </tr>
                    <?php endforeach; else: ?>
                        <tr><td colspan="4" style="text-align: center;">You have no completed projects.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once '../includes/user_dashboard_footer.php'; ?>