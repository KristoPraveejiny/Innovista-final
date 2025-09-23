<?php 
    $pageTitle = 'Login';
    require_once __DIR__ . '/../public/session.php'; // Correct path to session.php
    require_once __DIR__ . '/../handlers/flash_message.php'; // Include flash message functions

    // If user is already logged in, check their approval status before redirecting
    if (isUserLoggedIn()) {
        $userRole = getUserRole();
        if ($userRole === 'admin') {
            header("Location: ../admin/admin_dashboard.php");
        } elseif ($userRole === 'provider') {
            // Check provider approval status before redirecting
            require_once '../config/Database.php';
            $database = new Database();
            $conn = $database->getConnection();
            $stmt = $conn->prepare("SELECT provider_status FROM users WHERE id = :id AND role = 'provider'");
            $stmt->bindParam(':id', $_SESSION['user_id'], PDO::PARAM_INT);
            $stmt->execute();
            $provider_data = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($provider_data) {
                if ($provider_data['provider_status'] === 'approved') {
                    header("Location: ../provider/provider_dashboard.php");
                } elseif ($provider_data['provider_status'] === 'pending') {
                    // Show pending message and stay on login page
                    set_flash_message('info', 'Your provider account is pending approval. Please wait for an administrator to review it.');
                    // Stay on login page to show the message
                } else { // rejected or inactive
                    set_flash_message('error', 'Your provider account is currently inactive or rejected. Please contact support.');
                    // Stay on login page to show error
                }
            } else {
                // Provider not found, stay on login page
                set_flash_message('error', 'Account not found. Please try again.');
            }
        } else { // customer or unknown
            header("Location: ../customer/customer_dashboard.php");
        }
        exit();
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?> - Innovista</title>
    
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/login.css"> <!-- Your existing login styles -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="login-page-wrapper">
        <div class="login-container">
            <div class="login-form-side">
                <a href="index.php" class="home-link"><i class="fas fa-arrow-left"></i> Back to Home</a>
                
                <form id="loginForm" method="POST" action="../handlers/handle_login.php" autocomplete="off">
                    <h2 class="form-title">Welcome Back</h2>
                    
                    <!-- Container for server messages -->
                    <div class="flash-message-container">
                        <?php display_flash_message(); ?>
                    </div>

                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <div class="input-wrapper">
                            <i class="fas fa-envelope input-icon"></i>
                            <input type="email" id="email" name="email" placeholder="you@example.com" required value="<?php echo htmlspecialchars($_SESSION['login_data']['email'] ?? ''); unset($_SESSION['login_data']['email']); ?>">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="password">Password</label>
                        <div class="input-wrapper">
                            <i class="fas fa-lock input-icon"></i>
                            <input type="password" id="password" name="password" placeholder="••••••••" required>
                        </div>
                    </div>
                    <div class="form-actions">
                        <a href="#" class="forgot-password-link">Forgot Password?</a>
                    </div>
                    <button type="submit" class="btn btn-primary login-btn">Login</button>
                    
                    <div class="form-footer">
                        Don't have an account? <a href="signup.php">Sign up</a>
                    </div>
                </form>
            </div>
            
            <div class="login-welcome-side">
                <div class="welcome-overlay">
                    <h1 class="welcome-title">Innovista</h1>
                    <p class="welcome-subtitle">Sign in to access your account and continue transforming spaces.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Provider Approval Status Popup Modal -->
    <div id="approvalModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <div class="modal-icon" id="modalIcon">
                    <i class="fas fa-clock"></i>
                </div>
                <h3 id="modalTitle">Account Status</h3>
            </div>
            <div class="modal-body">
                <p id="modalMessage">Your account status information will be displayed here.</p>
                
                <div class="info-box" id="infoBox" style="display: none;">
                    <h4><i class="fas fa-info-circle"></i> What happens next?</h4>
                    <ul>
                        <li>Our admin team will review your registration details</li>
                        <li>We'll verify your service information and credentials</li>
                        <li>You'll receive an email notification once approved</li>
                        <li>Once approved, you can access your provider dashboard</li>
                    </ul>
                </div>
                
                <div class="contact-info">
                    <h4><i class="fas fa-headset"></i> Need Help?</h4>
                    <p><strong>Email:</strong> support@innovista.com</p>
                    <p><strong>Phone:</strong> 077 123-4567</p>
                </div>
            </div>
            <div class="modal-footer">
                <button onclick="closeApprovalModal()" class="btn btn-primary">OK, I Understand</button>
            </div>
        </div>
    </div>

    <style>
        .modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 10000;
            backdrop-filter: blur(5px);
        }

        .modal-content {
            background: white;
            border-radius: 20px;
            padding: 40px;
            max-width: 600px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
            text-align: center;
            box-shadow: 0 20px 40px rgba(0,0,0,0.3);
            animation: modalSlideIn 0.3s ease-out;
        }

        @keyframes modalSlideIn {
            from {
                opacity: 0;
                transform: translateY(-50px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .modal-header {
            margin-bottom: 20px;
        }

        .modal-icon {
            font-size: 60px;
            margin-bottom: 15px;
        }

        .modal-icon.pending {
            color: #f39c12;
        }

        .modal-icon.error {
            color: #e74c3c;
        }

        .modal-header h3 {
            color: #2c3e50;
            margin: 0;
            font-size: 24px;
        }

        .modal-body {
            text-align: left;
            margin-bottom: 30px;
        }

        .modal-body p {
            color: #7f8c8d;
            line-height: 1.6;
            margin-bottom: 15px;
        }

        .info-box {
            background: #f8f9fa;
            border-left: 4px solid #0d9488;
            padding: 20px;
            margin: 20px 0;
            border-radius: 8px;
        }

        .info-box h4 {
            color: #0d9488;
            margin-bottom: 10px;
            font-size: 18px;
        }

        .info-box ul {
            margin: 0;
            padding-left: 20px;
            color: #495057;
        }

        .info-box li {
            margin-bottom: 8px;
        }

        .contact-info {
            background: #e8f4f8;
            padding: 20px;
            border-radius: 10px;
            border: 1px solid #b8e6e6;
            margin-top: 20px;
        }

        .contact-info h4 {
            color: #0d9488;
            margin-bottom: 10px;
        }

        .contact-info p {
            color: #495057;
            margin: 5px 0;
        }

        .modal-footer {
            text-align: center;
        }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background: linear-gradient(45deg, #0d9488, #14b8a6);
            color: white;
        }

        .btn-primary:hover {
            background: linear-gradient(45deg, #0f766e, #0d9488);
            transform: translateY(-2px);
        }
    </style>

    <script>
        function closeApprovalModal() {
            document.getElementById('approvalModal').style.display = 'none';
        }

        function showApprovalModal(type, message) {
            const modal = document.getElementById('approvalModal');
            const icon = document.getElementById('modalIcon');
            const title = document.getElementById('modalTitle');
            const messageEl = document.getElementById('modalMessage');
            const infoBox = document.getElementById('infoBox');

            if (type === 'pending') {
                icon.innerHTML = '<i class="fas fa-clock"></i>';
                icon.className = 'modal-icon pending';
                title.textContent = 'Account Pending Approval';
                messageEl.textContent = message;
                infoBox.style.display = 'block';
            } else if (type === 'error') {
                icon.innerHTML = '<i class="fas fa-exclamation-triangle"></i>';
                icon.className = 'modal-icon error';
                title.textContent = 'Account Access Denied';
                messageEl.textContent = message;
                infoBox.style.display = 'none';
            }

            modal.style.display = 'flex';
        }

        // Check for flash messages and show appropriate modal
        document.addEventListener('DOMContentLoaded', function() {
            const flashMessages = document.querySelectorAll('.alert');
            
            flashMessages.forEach(function(message) {
                const messageText = message.textContent.toLowerCase();
                
                if (messageText.includes('pending approval')) {
                    message.style.display = 'none';
                    showApprovalModal('pending', message.textContent);
                } else if (messageText.includes('inactive') || messageText.includes('rejected') || messageText.includes('not found')) {
                    message.style.display = 'none';
                    showApprovalModal('error', message.textContent);
                }
            });
        });
    </script>
</body>
</html>