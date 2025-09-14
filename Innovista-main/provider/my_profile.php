<?php
$pageTitle = 'My Profile';
require_once '../provider/provider_header.php';
require_once '../config/session.php';
protectPage('provider');
require_once '../config/Database.php';
$provider_id = $_SESSION['user_id'] ?? 0;
$db = (new Database())->getConnection();
$stmt = $db->prepare('SELECT * FROM service WHERE provider_id = :provider_id LIMIT 1');
$stmt->bindParam(':provider_id', $provider_id);
$stmt->execute();
$provider = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$provider) {
    $provider = [
        'provider_name' => '',
        'provider_email' => '',
        'provider_phone' => '',
        'provider_address' => '',
        'provider_bio' => '',
        'main_service' => '',
        'subcategories' => '',
        'portfolio' => ''
    ];
}
?>


<!-- Flash message for profile update -->



<div class="profile-page-wrapper">
    <h2 style="margin-bottom: 0.5rem;">Business Profile</h2>
    <p style="margin-bottom: 2rem; color: #666;">Keep your information up to date to attract more clients.</p>


    <form action="update_profile.php" method="POST" class="profile-form content-card business-info-form" style="margin-bottom: 2.5rem;">
        <h3 class="section-title">Business Information</h3>
        <div class="form-grid-pro" style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem 2.5rem; align-items: start;">
            <div class="form-group-pro">
                <label for="company_name">Full Name / Company Name <span class="required">*</span></label>
                <input type="text" id="company_name" name="company_name" value="<?php echo htmlspecialchars($provider['provider_name'] ?? ''); ?>" required autocomplete="organization" placeholder="e.g. Daniel Company or John Doe">
            </div>
            <div class="form-group-pro">
                <label for="email">Email <span class="required">*</span></label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($provider['provider_email'] ?? ''); ?>" required autocomplete="email" placeholder="e.g. daniel@email.com">
            </div>
            <div class="form-group-pro">
                <label for="phone">Phone Number</label>
                <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($provider['provider_phone'] ?? ''); ?>" autocomplete="tel" placeholder="e.g. 07XXXXXXXX">
            </div>
            <div class="form-group-pro">
                <label for="provider_address">Address</label>
                <textarea id="provider_address" name="provider_address" rows="2" placeholder="e.g. 123 Main St, City, Country"><?php echo htmlspecialchars($provider['provider_address'] ?? ''); ?></textarea>
            </div>
            <div class="form-group-pro" style="grid-column: 1 / -1;">
                <label for="provider_bio">Bio / Description</label>
                <textarea id="provider_bio" name="provider_bio" rows="4" placeholder="Describe your business, experience, or specialties..." style="min-height: 80px;"><?php echo htmlspecialchars($provider['provider_bio'] ?? ''); ?></textarea>
            </div>
        </div>
        <button type="submit" name="update_details" class="btn-submit-pro">Save Business Info</button>
    </form>

    <style>
    .profile-page-wrapper {
        width: 100%;
        max-width: 900px;
        margin: 0 auto;
        padding: 2rem 1.5rem 2rem 1.5rem;
        box-sizing: border-box;
    }
    .business-info-form {
        background: #fff;
        border-radius: 16px;
        box-shadow: 0 4px 24px rgba(0,0,0,0.07);
        padding: 2.5rem 2.5rem 2rem 2.5rem;
        margin-bottom: 2.5rem;
    }
    .section-title {
        font-size: 1.6rem;
        font-weight: 700;
        margin-bottom: 1.7rem;
        color: #18181b;
        letter-spacing: 0.2px;
        border-bottom: 1.5px solid #ececec;
        padding-bottom: 0.7rem;
    }
    .form-grid-pro {
        margin-bottom: 1.5rem;
    }
    .form-group-pro {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }
    .form-group-pro label {
        font-weight: 600;
        color: #22223b;
        font-size: 1.08rem;
        margin-bottom: 0.2rem;
    }
    .form-group-pro input,
    .form-group-pro textarea {
        border: 1.5px solid #d1d5db;
        border-radius: 8px;
        padding: 0.7rem 1rem;
        font-size: 1.05rem;
        background: #f8fafc;
        transition: border 0.2s;
        outline: none;
    }
    .form-group-pro input:focus,
    .form-group-pro textarea:focus {
        border-color: #0d9488;
        background: #fff;
    }
    .form-group-pro textarea {
        resize: vertical;
        min-height: 48px;
        max-height: 180px;
    }
    .btn-submit-pro {
        background: linear-gradient(90deg, #0d9488 0%, #14b8a6 100%);
        color: #fff;
        font-weight: 700;
        font-size: 1.08rem;
        border: none;
        border-radius: 8px;
        padding: 0.85rem 2.2rem;
        margin-top: 1.2rem;
        cursor: pointer;
        box-shadow: 0 2px 8px rgba(13,148,136,0.08);
        transition: background 0.2s, box-shadow 0.2s;
    }
    .btn-submit-pro:hover {
        background: linear-gradient(90deg, #14b8a6 0%, #0d9488 100%);
        box-shadow: 0 4px 16px rgba(13,148,136,0.13);
    }
    .required {
        color: #e11d48;
        font-size: 1.1em;
        margin-left: 2px;
    }
    @media (max-width: 1100px) {
        .profile-page-wrapper {
            max-width: 98vw;
            padding: 1rem 0.5rem 1rem 0.5rem;
        }
    }
    @media (max-width: 700px) {
        .form-grid-pro {
            grid-template-columns: 1fr !important;
            gap: 1.2rem 0 !important;
        }
        .business-info-form {
            padding: 1.2rem 0.7rem 1.2rem 0.7rem;
        }
        .profile-page-wrapper {
            max-width: 100vw;
            padding: 0.5rem 0.2rem 0.5rem 0.2rem;
        }
    }
    </style>


    <div class="content-card" style="margin-bottom: 2.5rem; padding: 2rem 2.5rem; box-shadow: 0 4px 16px rgba(0,0,0,0.06); border-radius: 14px;">
        <h3 style="margin-bottom: 1.5rem; font-size: 1.35rem; font-weight: 700; letter-spacing: 0.5px;">My Services</h3>
        <form action="update_profile.php" method="POST" class="form-section">
            <div class="form-group" style="margin-bottom: 1.5rem;">
                <label for="providerService" style="font-weight: 600; font-size: 1.05rem; margin-bottom: 0.5rem; display: block;">Service(s)</label>
                <select id="providerService" name="providerService[]" multiple style="min-width:240px; min-height: 90px; border-radius: 8px; border: 1.5px solid #d1d5db; padding: 0.5rem; font-size: 1rem;">
                    <option value="Interior Design" <?php echo (strpos($provider['main_service'], 'Interior Design') !== false) ? 'selected' : ''; ?>>Interior Design</option>
                    <option value="Painting" <?php echo (strpos($provider['main_service'], 'Painting') !== false) ? 'selected' : ''; ?>>Painting</option>
                    <option value="Restoration" <?php echo (strpos($provider['main_service'], 'Restoration') !== false) ? 'selected' : ''; ?>>Restoration</option>
                </select>
                
            </div>
            <div class="form-group" id="providerSubcategories" style="display:none; margin-bottom: 1.5rem;">
                <label style="font-weight: 600; font-size: 1.05rem; margin-bottom: 0.5rem; display: block;">Subcategories <span style="font-weight:400; color:#888;">(select all that apply):</span></label>
                <div id="subcategoryCheckboxes" style="margin-top: 0.5rem;"></div>
            </div>
            <button type="submit" name="update_services" class="btn-submit" style="margin-top: 0.5rem; padding: 0.6rem 1.5rem; font-size: 1rem; border-radius: 7px;">Save Services</button>
        </form>
    </div>

    <style>
    /* Modernize the multi-select and checkboxes */
    #providerService { background: #f8fafc; }
    #subcategoryCheckboxes label {
        margin-right: 18px;
        margin-bottom: 8px;
        font-size: 1rem;
        font-weight: 500;
        color: #222;
        cursor: pointer;
        user-select: none;
    }
    #subcategoryCheckboxes input[type="checkbox"] {
        accent-color: #0d9488;
        width: 18px;
        height: 18px;
        margin-right: 5px;
        vertical-align: middle;
    }
    #subcategoryCheckboxes div {
        font-weight: 700;
        margin-top: 0.7rem;
        margin-bottom: 0.3rem;
        color: #0d9488;
        font-size: 1.08rem;
    }
    </style>

    <script>
    // Subcategory options for each main service
    const subcategories = {
        "Interior Design": [
            "Ceiling & Lighting",
            "Space Planning",
            "Modular Kitchen",
            "Bathroom Design",
            "Carpentry & Woodwork",
            "Furniture Design"
        ],
        "Painting": [
            "Interior Painting",
            "Exterior Painting",
            "Water & Damp Proofing",
            "Commercial Painting",
            "Wall Art & Murals",
            "Color Consultation"
        ],
        "Restoration": [
            "Wall Repairs & Plastering",
            "Floor Restoration",
            "Door & Window Repairs",
            "Old Space Transformation",
            "Furniture Restoration",
            "Full Building Renovation"
        ]
    };
    document.addEventListener('DOMContentLoaded', function() {
        const providerService = document.getElementById('providerService');
        const subcatContainer = document.getElementById('providerSubcategories');
        const subcatCheckboxes = document.getElementById('subcategoryCheckboxes');
        function updateSubcategories() {
            const selected = Array.from(providerService.selectedOptions).map(opt => opt.value);
            subcatCheckboxes.innerHTML = '';
            if (selected.length > 0) {
                subcatContainer.style.display = 'block';
                selected.forEach(function(service) {
                    if (subcategories[service]) {
                        const label = document.createElement('div');
                        label.style.fontWeight = 'bold';
                        label.textContent = service + ' Subcategories:';
                        subcatCheckboxes.appendChild(label);
                        subcategories[service].forEach(function(subcat) {
                            const id = 'subcat_' + service.replace(/\s+/g, '_') + '_' + subcat.replace(/\s+/g, '_');
                            const checkboxLabel = document.createElement('label');
                            checkboxLabel.style.display = 'inline-block';
                            checkboxLabel.style.marginRight = '10px';
                            const checkbox = document.createElement('input');
                            checkbox.type = 'checkbox';
                            checkbox.name = 'providerSubcategories[]';
                            checkbox.value = service + ' - ' + subcat;
                            checkbox.id = id;
                            // Pre-check if already selected
                            <?php if (!empty($provider['subcategories'])): ?>
                            if (<?php echo json_encode(explode(',', $provider['subcategories'])); ?>.includes(service + ' - ' + subcat)) {
                                checkbox.checked = true;
                            }
                            <?php endif; ?>
                            checkboxLabel.appendChild(checkbox);
                            checkboxLabel.appendChild(document.createTextNode(' ' + subcat));
                            subcatCheckboxes.appendChild(checkboxLabel);
                        });
                    }
                });
            } else {
                subcatContainer.style.display = 'none';
                subcatCheckboxes.innerHTML = '';
            }
        }
        providerService.addEventListener('change', updateSubcategories);
        updateSubcategories(); // Initial call
    });
    </script>

    <div class="content-card portfolio-section-pro" style="margin-bottom: 2.5rem;">
        <h3 class="portfolio-title-pro">Portfolio</h3>
        <form action="upload_portfolio.php" method="POST" enctype="multipart/form-data" class="portfolio-upload-form-pro">
            <label for="portfolio_photos" class="portfolio-upload-label-pro">Upload New Portfolio Files</label>
            <input type="file" id="portfolio_photos" name="portfolio_photos[]" multiple class="portfolio-upload-input-pro">
            <button type="submit" class="btn-portfolio-upload-pro">Upload Portfolio</button>
        </form>
        <div class="portfolio-gallery-pro">
            <?php 
            $portfolio = isset($provider['portfolio']) && $provider['portfolio'] ? (is_array($provider['portfolio']) ? $provider['portfolio'] : explode(',', $provider['portfolio'])) : [];
            if (count($portfolio) > 0 && $portfolio[0] !== ''): 
                foreach($portfolio as $photo): ?>
                    <div class="portfolio-image-card-pro">
                        <img src="/INNOVISTA/Innovista-final/Innovista-main/public/assets/images/<?php echo htmlspecialchars($photo); ?>" alt="Portfolio work" class="portfolio-img-pro" onclick="showPortfolioModal(this.src)" />
                        <form class="delete-portfolio-form" method="POST" action="delete_portfolio.php" style="display:inline;">
                            <input type="hidden" name="photo" value="<?php echo htmlspecialchars($photo); ?>">
                            <button type="submit" class="delete-photo-btn-pro" title="Delete Photo"><i class="fas fa-trash"></i></button>
                        </form>
                    </div>
                <?php endforeach; 
            else: ?>
                <p class="portfolio-empty-pro">No portfolio images uploaded yet.</p>
            <?php endif; ?>
        </div>
        <!-- Modal for big image -->
        <div id="portfolioModal" class="portfolio-modal-pro">
            <span onclick="closePortfolioModal()" class="portfolio-modal-close-pro">&times;</span>
            <img id="portfolioModalImg" src="" alt="Portfolio Large" class="portfolio-modal-img-pro" />
        </div>
        <script>
        function showPortfolioModal(src) {
            document.getElementById('portfolioModalImg').src = src;
            document.getElementById('portfolioModal').style.display = 'flex';
        }
        function closePortfolioModal() {
            document.getElementById('portfolioModal').style.display = 'none';
        }
        document.addEventListener('DOMContentLoaded', function() {
            var modal = document.getElementById('portfolioModal');
            if(modal) {
                modal.addEventListener('click', function(e) {
                    if(e.target === modal) closePortfolioModal();
                });
            }
            // AJAX delete for portfolio images
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
        <style>
        .portfolio-section-pro {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 4px 24px rgba(0,0,0,0.07);
            padding: 2.5rem 2.5rem 2rem 2.5rem;
        }
        .portfolio-title-pro {
            font-size: 1.35rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
            color: #18181b;
            letter-spacing: 0.2px;
            border-bottom: 1.5px solid #ececec;
            padding-bottom: 0.7rem;
        }
        .portfolio-upload-form-pro {
            display: flex;
            align-items: flex-end;
            gap: 1.2rem;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
        }
        .portfolio-upload-label-pro {
            font-weight: 600;
            color: #22223b;
            font-size: 1.08rem;
        }
        .portfolio-upload-input-pro {
            border: 1.5px solid #d1d5db;
            border-radius: 8px;
            padding: 0.5rem 1rem;
            font-size: 1.05rem;
            background: #f8fafc;
            transition: border 0.2s;
            outline: none;
        }
        .btn-portfolio-upload-pro {
            background: linear-gradient(90deg, #0d9488 0%, #14b8a6 100%);
            color: #fff;
            font-weight: 700;
            font-size: 1.08rem;
            border: none;
            border-radius: 8px;
            padding: 0.7rem 2rem;
            cursor: pointer;
            box-shadow: 0 2px 8px rgba(13,148,136,0.08);
            transition: background 0.2s, box-shadow 0.2s;
        }
        .btn-portfolio-upload-pro:hover {
            background: linear-gradient(90deg, #14b8a6 0%, #0d9488 100%);
            box-shadow: 0 4px 16px rgba(13,148,136,0.13);
        }
        .portfolio-gallery-pro {
            display: flex;
            flex-wrap: wrap;
            gap: 1.5rem;
        }
        .portfolio-image-card-pro {
            background: #f8f9fa;
            border-radius: 14px;
            padding: 0.7rem;
            box-shadow: 0 2px 12px rgba(0,0,0,0.06);
            display: flex;
            flex-direction: column;
            align-items: center;
            width: 170px;
            transition: box-shadow 0.2s, transform 0.2s;
        }
        .portfolio-image-card-pro:hover {
            box-shadow: 0 6px 24px rgba(13,148,136,0.13);
            transform: translateY(-2px) scale(1.03);
        }
        .portfolio-img-pro {
            width: 150px;
            height: 150px;
            object-fit: cover;
            border-radius: 10px;
            display: block;
            cursor: pointer;
            box-shadow: 0 1px 4px rgba(0,0,0,0.04);
            margin-bottom: 0.7rem;
            transition: box-shadow 0.2s;
        }
        .portfolio-img-pro:hover {
            box-shadow: 0 4px 16px rgba(13,148,136,0.13);
        }
        .delete-photo-btn-pro {
            background: #fff;
            border: 1.5px solid #e11d48;
            color: #e11d48;
            border-radius: 6px;
            padding: 0.3rem 0.7rem;
            font-size: 1.1rem;
            cursor: pointer;
            margin-top: 0.2rem;
            transition: background 0.2s, color 0.2s;
        }
        .delete-photo-btn-pro:hover {
            background: #e11d48;
            color: #fff;
        }
        .portfolio-empty-pro {
            color: #888;
            font-size: 1rem;
            margin-top: 0.7rem;
        }
        .portfolio-modal-pro {
            display:none;position:fixed;z-index:9999;left:0;top:0;width:100vw;height:100vh;background:rgba(0,0,0,0.7);align-items:center;justify-content:center;
        }
        .portfolio-modal-close-pro {
            position:absolute;top:30px;right:50px;font-size:2.5rem;color:#fff;cursor:pointer;font-weight:bold;z-index:10001;
        }
        .portfolio-modal-img-pro {
            max-width:90vw;max-height:90vh;border-radius:16px;box-shadow:0 8px 32px rgba(0,0,0,0.25);background:#fff;z-index:10000;
        }
        @media (max-width: 700px) {
            .portfolio-section-pro {
                padding: 1.2rem 0.7rem 1.2rem 0.7rem;
            }
            .portfolio-gallery-pro {
                gap: 0.7rem;
            }
            .portfolio-image-card-pro {
                width: 100%;
                min-width: 0;
            }
            .portfolio-img-pro {
                width: 100%;
                height: 120px;
            }
        }
        </style>
    </div>
    </div>


    <form action="../handlers/handle_update_password.php" method="POST" class="content-card password-form-pro">
        <h3 class="password-title-pro">Change Password</h3>
        <div class="password-grid-pro">
            <div class="password-group-pro">
                <label for="current_password">Current Password <span class="required">*</span></label>
                <input type="password" id="current_password" name="current_password" required autocomplete="current-password" placeholder="Enter current password">
            </div>
            <div class="password-group-pro">
                <label for="new_password">New Password <span class="required">*</span></label>
                <input type="password" id="new_password" name="new_password" required autocomplete="new-password" placeholder="Enter new password">
            </div>
            <div class="password-group-pro" style="grid-column: 1 / -1;">
                <label for="confirm_password">Confirm New Password <span class="required">*</span></label>
                <input type="password" id="confirm_password" name="confirm_password" required autocomplete="new-password" placeholder="Re-enter new password">
            </div>
        </div>
        <button type="submit" name="update_password" class="btn-password-pro">Update Password</button>
    </form>

    <style>
    .password-form-pro {
        max-width: 600px;
        margin: 0 auto 2.5rem auto;
        background: #fff;
        border-radius: 16px;
        box-shadow: 0 4px 24px rgba(0,0,0,0.07);
        padding: 2.5rem 2.5rem 2rem 2.5rem;
    }
    .password-title-pro {
        font-size: 1.35rem;
        font-weight: 700;
        margin-bottom: 1.5rem;
        color: #18181b;
        letter-spacing: 0.2px;
        border-bottom: 1.5px solid #ececec;
        padding-bottom: 0.7rem;
    }
    .password-grid-pro {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1.5rem 2.5rem;
        margin-bottom: 1.5rem;
    }
    .password-group-pro {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }
    .password-group-pro label {
        font-weight: 600;
        color: #22223b;
        font-size: 1.08rem;
        margin-bottom: 0.2rem;
    }
    .password-group-pro input {
        border: 1.5px solid #d1d5db;
        border-radius: 8px;
        padding: 0.7rem 1rem;
        font-size: 1.05rem;
        background: #f8fafc;
        transition: border 0.2s;
        outline: none;
    }
    .password-group-pro input:focus {
        border-color: #0d9488;
        background: #fff;
    }
    .btn-password-pro {
        background: linear-gradient(90deg, #0d9488 0%, #14b8a6 100%);
        color: #fff;
        font-weight: 700;
        font-size: 1.08rem;
        border: none;
        border-radius: 8px;
        padding: 0.85rem 2.2rem;
        margin-top: 1.2rem;
        cursor: pointer;
        box-shadow: 0 2px 8px rgba(13,148,136,0.08);
        transition: background 0.2s, box-shadow 0.2s;
    }
    .btn-password-pro:hover {
        background: linear-gradient(90deg, #14b8a6 0%, #0d9488 100%);
        box-shadow: 0 4px 16px rgba(13,148,136,0.13);
    }
    @media (max-width: 700px) {
        .password-form-pro {
            padding: 1.2rem 0.7rem 1.2rem 0.7rem;
        }
        .password-grid-pro {
            grid-template-columns: 1fr !important;
            gap: 1.2rem 0 !important;
        }
    }
    </style>
</div>

<?php require_once '../includes/user_dashboard_footer.php'; ?>



