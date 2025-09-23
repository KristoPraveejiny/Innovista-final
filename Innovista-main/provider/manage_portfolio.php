<?php
$pageTitle = 'Manage Portfolio';
require_once 'provider_header.php';
require_once '../config/session.php';
protectPage('provider');
require_once '../config/Database.php';
$provider_id = $_SESSION['user_id'] ?? 0;
$db = (new Database())->getConnection();
$stmt = $db->prepare('SELECT portfolio FROM service WHERE provider_id = :provider_id');
$stmt->bindParam(':provider_id', $provider_id);
$stmt->execute();
$row = $stmt->fetch(PDO::FETCH_ASSOC);
$portfolio = isset($row['portfolio']) ? ($row['portfolio'] ? explode(',', $row['portfolio']) : []) : [];
?>

<!-- Display flash messages -->
<?php if (function_exists('display_flash_message')): ?>
<div class="flash-message-container">
    <?php display_flash_message(); ?>
</div>
<?php endif; ?>

<section class="page-section">
    <div class="section-header">
        <h2 class="section-title">Manage My Portfolio</h2>
        <p>Showcase your best work to attract new clients. Upload high-quality images of completed projects.</p>
    </div>
    <div class="content-card" style="max-width: 600px; margin: 0 auto 2.5rem;">
        <h3 style="margin-bottom: 1.2rem;">Upload New Photos</h3>
        <form action="upload_portfolio.php" method="POST" enctype="multipart/form-data" class="form-section" style="display: flex; flex-direction: column; gap: 1.2rem;">
            <div class="form-group" style="display: flex; flex-direction: column; gap: 0.5rem;">
                <label for="portfolio_photos" style="font-weight: 600;">Select Images (you can select multiple)</label>
                <input type="file" id="portfolio_photos" name="portfolio_photos[]" multiple style="padding: 0.5rem; border-radius: 8px; border: 1px solid #d1d4ba; background: #f8f9fa;">
                <div id="drop-area" style="border: 2px dashed #8B9B93; border-radius: 8px; padding: 1.2rem; text-align: center; color: #8B9B93; background: #f8f9fa; cursor: pointer; transition: border-color 0.2s;">
                    <span style="font-size: 1.1rem;">Or drag & drop images here</span>
                </div>
            </div>
            <button type="submit" class="btn btn-primary" style="width: 180px; align-self: flex-end;">Upload Photos</button>
        </form>
    </div>

    <script>
        // Drag and drop for file input
        const dropArea = document.getElementById('drop-area');
        const fileInput = document.getElementById('portfolio_photos');
        dropArea.addEventListener('click', () => fileInput.click());
        dropArea.addEventListener('dragover', (e) => {
            e.preventDefault();
            dropArea.style.borderColor = '#576F72';
        });
        dropArea.addEventListener('dragleave', (e) => {
            e.preventDefault();
            dropArea.style.borderColor = '#8B9B93';
        });
        dropArea.addEventListener('drop', (e) => {
            e.preventDefault();
            dropArea.style.borderColor = '#8B9B93';
            fileInput.files = e.dataTransfer.files;
        });
    </script>
</section>

<div class="content-card">
    <h3>Current Gallery</h3>
    <div class="portfolio-gallery">
        <style>
        .portfolio-gallery img {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            margin-bottom: 0.5rem;
        }
        </style>
        <?php foreach($portfolio as $photo): ?>
            <div class="portfolio-image-card">
                <img src="../public/assets/images/<?php echo htmlspecialchars($photo); ?>" alt="Portfolio work">
                <form class="delete-portfolio-form" method="POST" action="delete_portfolio.php" style="display:inline;">
                    <input type="hidden" name="photo" value="<?php echo htmlspecialchars($photo); ?>">
                    <button type="submit" class="delete-photo-btn" title="Delete Photo"><i class="fas fa-trash"></i></button>
                </form>
            </div>
        <?php endforeach; ?>
    </div>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.delete-portfolio-form').forEach(function(form) {
                form.addEventListener('submit', function(e) {
                    e.preventDefault();
                    var formData = new FormData(form);
                    fetch('delete_portfolio.php', {
                        method: 'POST',
                        body: formData
                    }).then(function(resp) {
                        if (resp.redirected) {
                            window.location.href = resp.url;
                        } else {
                            window.location.reload();
                        }
                    });
                });
            });
        });
        </script>
</div>

<?php require_once 'provider_footer.php'; ?>