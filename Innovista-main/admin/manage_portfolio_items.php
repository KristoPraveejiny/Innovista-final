<?php
// C:\xampp1\htdocs\Innovista-final\Innovista-main\admin\manage_portfolio_items.php
require_once 'admin_header.php';
require_once '../config/Database.php';
require_once '../public/session.php'; // For getImageSrc

$db = new Database();
$conn = $db->getConnection();

// Fetch all portfolio items for display
$portfolio_items = [];
// Fetch all providers and their portfolios from service table
$service_portfolios = [];
try {
    $stmt = $conn->prepare("SELECT s.provider_id, s.portfolio, s.main_service, u.email FROM service s JOIN users u ON s.provider_id = u.id ORDER BY s.provider_id DESC");
    $stmt->execute();
    $service_portfolios = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Database error fetching service portfolios: " . $e->getMessage());
}

// Fetch all providers for the dropdown
$providers = [];
try {
    $stmt_providers = $conn->prepare("SELECT id, name FROM users WHERE role = 'provider' AND provider_status = 'approved' ORDER BY name");
    $stmt_providers->execute();
    $providers = $stmt_providers->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Database error fetching providers: " . $e->getMessage());
}

?>

<h2>Manage Portfolio Items</h2>


<div class="content-card">
    
    <form action="manage_portfolio_items.php" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="action" value="add">
        <div class="content-card mt-4">
            <h3>Existing Portfolio Images</h3>
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>Image</th>
                            <th>Provider ID</th>
                            <th>Email</th>
                            <th>Service</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $hasImages = false;
                        if (!empty($service_portfolios)) {
                            foreach ($service_portfolios as $prov) {
                                if (!empty($prov['portfolio'])) {
                                    $portfolio = array_filter(array_map('trim', explode(',', $prov['portfolio'])));
                                    foreach ($portfolio as $img) {
                                        $hasImages = true;
                                        echo '<tr>';
                                        echo '<td><img src="../public/assets/images/' . htmlspecialchars($img) . '" alt="Portfolio" style="width:80px;height:50px;object-fit:cover;border-radius:4px;"></td>';
                                        echo '<td>' . htmlspecialchars($prov['provider_id']) . '</td>';
                                        echo '<td>' . htmlspecialchars($prov['email']) . '</td>';
                                        echo '<td>' . htmlspecialchars($prov['main_service']) . '</td>';
                                        echo '</tr>';
                                    }
                                }
                            }
                        }
                        if (!$hasImages) {
                            echo '<tr><td colspan="4" style="text-align:center;">No portfolio images found.</td></tr>';
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
<!-- The following block appears to be orphaned and not inside a foreach loop, so it should be removed or wrapped properly. 
If you want to display portfolio items, you need to fetch them and loop through them. 
For now, let's remove the orphaned endforeach and related code to fix the syntax error. -->

<?php require_once 'admin_footer.php'; ?>