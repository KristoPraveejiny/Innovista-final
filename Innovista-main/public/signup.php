<?php
    $pageTitle = 'Sign Up'; 
    require_once __DIR__ . '/../config/session.php';
    // No need for header.php here as it's a standalone page, but session is needed.

    // If a user is already logged in, redirect them away from signup
    if (isUserLoggedIn()) {
        $userRole = getUserRole();
        if ($userRole === 'admin') {
            header("Location: ../admin/admin_dashboard.php");
        } elseif ($userRole === 'provider') {
            header("Location: provider_dashboard.php");
        } else { // customer or unknown
            header("Location: customer_dashboard.php");
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
    <link rel="stylesheet" href="assets/css/signup.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* OTP Modal Styles */
        .otp-modal {
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

        .otp-modal-content {
            background: white;
            border-radius: 20px;
            padding: 40px;
            max-width: 500px;
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
                transform: translateY(-50px) scale(0.9);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        .otp-modal-header {
            margin-bottom: 30px;
        }

        .verification-icon {
            font-size: 3rem;
            color: #0d9488;
            margin-bottom: 15px;
        }

        .verification-icon i {
            background: linear-gradient(135deg, #0d9488, #14b8a6);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .otp-modal-header h3 {
            font-size: 1.8rem;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 10px;
        }

        .otp-modal-header p {
            color: #6b7280;
            margin-bottom: 20px;
        }

        .email-display {
            background: #f3f4f6;
            padding: 12px 20px;
            border-radius: 10px;
            font-weight: 600;
            color: #374151;
            display: inline-block;
        }

        .otp-inputs {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-bottom: 20px;
        }

        .otp-inputs input {
            width: 50px;
            height: 50px;
            text-align: center;
            font-size: 1.5rem;
            font-weight: 600;
            border: 2px solid #e5e7eb;
            border-radius: 10px;
            transition: all 0.3s ease;
        }

        .otp-inputs input:focus {
            border-color: #0d9488;
            box-shadow: 0 0 0 3px rgba(13, 148, 136, 0.1);
            outline: none;
        }

        .otp-timer {
            color: #dc2626;
            font-weight: 600;
            margin-bottom: 20px;
        }

        .otp-actions {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .btn-verify {
            background: linear-gradient(135deg, #0d9488, #14b8a6);
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 10px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-verify:hover:not(:disabled) {
            background: linear-gradient(135deg, #0f766e, #0d9488);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(13, 148, 136, 0.3);
        }

        .btn-verify:disabled {
            background: #9ca3af;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        .btn-resend {
            background: none;
            border: 2px solid #0d9488;
            color: #0d9488;
            padding: 12px 30px;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-resend:hover:not(:disabled) {
            background: #0d9488;
            color: white;
        }

        .btn-resend:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        @media (max-width: 768px) {
            .otp-modal-content {
                padding: 30px 20px;
                margin: 20px;
            }
            
            .otp-inputs input {
                width: 40px;
                height: 40px;
                font-size: 1.2rem;
            }
        }
    </style>
</head>
<body>
    <div class="signup-page-wrapper">
        <div class="signup-container">
            <div class="signup-form-side">
                <a href="index.php" class="home-link"><i class="fas fa-arrow-left"></i> Back to Home</a>
                <h2 class="form-title">Create Your Account</h2>

                <!-- Container for server messages -->
                <div class="flash-message-container">
                     <?php // This function should be defined in a utils/flash_message.php or similar ?>
                     <?php // Assuming display_flash_message() exists and handles output ?>
                     <?php if (function_exists('display_flash_message')) display_flash_message(); ?>
                </div>
                
                <form id="signupForm" method="POST" action="../handlers/handle_signup.php" autocomplete="off" enctype="multipart/form-data">
                    <input type="hidden" id="otpVerified" name="otpVerified" value="false">
                    <div class="user-type-group">
                        <button type="button" class="user-type-btn active" data-type="customer">I'm a Customer</button>
                        <button type="button" class="user-type-btn" data-type="provider">I'm a Provider</button>
                        <input type="hidden" id="userType" name="userType" value="customer">
                    </div>

                    <!-- Customer Fields -->
                    <div id="customerFields" class="form-fields active">
                        <div class="form-group">
                            <label for="name">Full Name</label>
                            <input type="text" id="name" name="name" placeholder="Enter your full name" value="<?php echo htmlspecialchars($_SESSION['signup_data']['name'] ?? ''); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="email">Email Address</label>
                            <input type="email" id="email" name="email" placeholder="you@example.com" value="<?php echo htmlspecialchars($_SESSION['signup_data']['email'] ?? ''); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="customerPhone">Phone Number</label>
                            <input type="tel" id="customerPhone" name="customerPhone" placeholder="Enter phone number" pattern="^[0-9]{10,15}$" maxlength="15" value="<?php echo htmlspecialchars($_SESSION['signup_data']['customerPhone'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label for="customerAddress">Address</label>
                            <input type="text" id="customerAddress" name="customerAddress" placeholder="Enter address" value="<?php echo htmlspecialchars($_SESSION['signup_data']['customerAddress'] ?? ''); ?>">
                        </div>
                    </div>

                    <!-- Provider Fields -->
                    <div id="providerFields" class="form-fields">
                         <div class="form-group">
                            <label for="providerFullname">Full Name / Company Name</label>
                            <input type="text" id="providerFullname" name="providerFullname" placeholder="Your professional name" value="<?php echo htmlspecialchars($_SESSION['signup_data']['providerFullname'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label for="providerEmail">Business Email</label>
                            <input type="email" id="providerEmail" name="providerEmail" placeholder="contact@yourbusiness.com" value="<?php echo htmlspecialchars($_SESSION['signup_data']['providerEmail'] ?? ''); ?>">
                        </div>
                           <div class="form-group">
                               <label for="provider_bio">Bio / Description</label>
                               <textarea id="provider_bio" name="provider_bio" rows="4" placeholder="Describe your business, experience, or specialties"><?php echo htmlspecialchars($_SESSION['signup_data']['provider_bio'] ?? ''); ?></textarea>
                           </div>
                        <div class="form-group">
                            <label for="providerPhone">Phone Number</label>
                            <input type="tel" id="providerPhone" name="providerPhone" placeholder="Enter phone number" pattern="^[0-9]{10}$" maxlength="10" value="<?php echo htmlspecialchars($_SESSION['signup_data']['providerPhone'] ?? ''); ?>">
                            <small style="color: #888;">Must be 10 digits.</small>
                        </div>
                        <div class="form-group">
                            <label for="providerAddress">Address</label>
                            <input type="text" id="providerAddress" name="providerAddress" placeholder="Enter address" value="<?php echo htmlspecialchars($_SESSION['signup_data']['providerAddress'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="providerCV">Upload Portfolio/Credentials (PDF, DOC, JPG, PNG) (Optional)</label>
                            <input type="file" id="providerCV" name="providerCV" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
                            <small style="color: #888;">This is optional during signup, can be added later.</small>
                        </div>
                        <div class="form-group">
                            <label for="providerService">Main Service (select one)</label>
                            <div class="service-options">
                                <label class="service-option">
                                    <input type="radio" name="providerService" value="Interior Design" id="service_interior">
                                    <span class="service-radio"></span>
                                    <span class="service-label">Interior Design</span>
                                </label>
                                <label class="service-option">
                                    <input type="radio" name="providerService" value="Painting" id="service_painting">
                                    <span class="service-radio"></span>
                                    <span class="service-label">Painting</span>
                                </label>
                                <label class="service-option">
                                    <input type="radio" name="providerService" value="Restoration" id="service_restoration">
                                    <span class="service-radio"></span>
                                    <span class="service-label">Restoration</span>
                                </label>
                            </div>
                        </div>
                        <div class="form-group" id="providerSubcategories" style="display:none;">
                            <label>Subcategories (select all that apply):</label>
                            <div id="subcategoryCheckboxes" class="subcategory-grid"></div>
                        </div>
                    </div>
                    
                    <!-- Common Fields -->
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" placeholder="Min. 8 characters" required>
                    </div>
                     <div class="form-group">
                        <label for="confirm_password">Confirm Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" placeholder="Re-enter your password" required>
                    </div>
                    
                    <button type="submit" class="btn btn-primary signup-btn">Create Account</button>
                    
                    <p class="terms-text">
                        By creating an account, you agree to our <a href="#">Terms of Service</a>.
                    </p>
                </form>

                <!-- OTP Verification Modal -->
                <div id="otpModal" class="otp-modal" style="display: none;">
                    <div class="otp-modal-content">
                        <div class="otp-modal-header">
                            <div class="verification-icon">
                                <i class="fas fa-shield-alt"></i>
                            </div>
                            <h3>Verify Your Email</h3>
                            <p>We've sent a 6-digit verification code to your email address</p>
                            <div class="email-display">
                                <i class="fas fa-envelope"></i> <span id="otpEmail"></span>
                            </div>
                        </div>
                        
                        <div class="otp-modal-body">
                            <div class="otp-inputs" id="otpInputs">
                                <!-- OTP inputs will be generated by JavaScript -->
                            </div>
                            
                            <div class="otp-timer">
                                Time remaining: <span id="timeLeft">10:00</span>
                            </div>
                            
                            <div class="otp-actions">
                                <button type="button" class="btn-verify" id="verifyBtn" disabled>
                                    <i class="fas fa-check"></i> Verify Email
                                </button>
                                <button type="button" class="btn-resend" id="resendBtn" disabled>
                                    <i class="fas fa-redo"></i> Resend OTP (<span id="resendTimer">60</span>s)
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="signup-welcome-side">
                <div class="welcome-overlay">
                    <h1 class="welcome-title">Join Innovista</h1>
                    <p class="welcome-subtitle">The #1 platform for connecting clients with trusted design and restoration professionals.</p>
                    <div class="welcome-login-link">
                        Already have an account? <a href="./login.php">Log in</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- This script is for the customer/provider toggle button -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const subcategories = {
            "Interior Design": [
                "Ceiling & Lighting", "Space Planning", "Modular Kitchen", 
                "Bathroom Design", "Carpentry & Woodwork", "Furniture Design"
            ],
            "Painting": [
                "Interior Painting", "Exterior Painting", "Water & Damp Proofing", 
                "Commercial Painting", "Wall Art & Murals", "Color Consultation"
            ],
            "Restoration": [
                "Wall Repairs & Plastering", "Floor Restoration", "Door & Window Repairs", 
                "Old Space Transformation", "Furniture Restoration", "Full Building Renovation"
            ]
        };

        const userTypeButtons = document.querySelectorAll('.user-type-btn');
        const userTypeInput = document.getElementById('userType');
        const customerFields = document.getElementById('customerFields');
        const providerFields = document.getElementById('providerFields');

        // Customer fields
        const customerName = document.getElementById('name');
        const customerEmail = document.getElementById('email');
        const customerPhone = document.getElementById('customerPhone');
        const customerAddress = document.getElementById('customerAddress');

        // Provider fields
        const providerFullname = document.getElementById('providerFullname');
        const providerEmail = document.getElementById('providerEmail');
        const providerBio = document.getElementById('provider_bio');
        const providerPhone = document.getElementById('providerPhone');
        const providerAddress = document.getElementById('providerAddress');
        const providerCV = document.getElementById('providerCV');
        const providerServiceRadios = document.querySelectorAll('input[name="providerService"]');
        const subcatContainer = document.getElementById('providerSubcategories');
        const subcatCheckboxes = document.getElementById('subcategoryCheckboxes');

        // Common fields
        const password = document.getElementById('password');
        const confirmPassword = document.getElementById('confirm_password');


        function setRequiredAttributes(type) {
            // Reset all required attributes first
            [customerName, customerEmail, customerPhone, customerAddress, 
             providerFullname, providerEmail, providerBio, providerPhone, providerAddress, 
             providerCV].forEach(field => {
                if (field) field.removeAttribute('required');
            });
            Array.from(subcatCheckboxes.querySelectorAll('input[type="checkbox"]')).forEach(cb => cb.removeAttribute('required'));

            // Set common required fields
            password.required = true;
            confirmPassword.required = true;

            if (type === 'customer') {
                customerName.required = true;
                customerEmail.required = true;
                // customerPhone and customerAddress are optional
            } else { // provider
                providerFullname.required = true;
                providerEmail.required = true;
                providerPhone.required = true;
                // At least one service must be selected
                providerServiceRadios.forEach(radio => radio.required = true);
                // providerBio, providerAddress, providerCV are optional
            }
        }

        function toggleUserTypeFields(type) {
            if (type === 'customer') {
                customerFields.classList.add('active');
                providerFields.classList.remove('active');
            } else { // provider
                providerFields.classList.add('active');
                customerFields.classList.remove('active');
                // Manually trigger change for providerService to load subcategories if any are pre-selected
                updateSubcategories();
            }
            setRequiredAttributes(type);
        }

        userTypeButtons.forEach(button => {
            button.addEventListener('click', function() {
                userTypeButtons.forEach(btn => btn.classList.remove('active'));
                this.classList.add('active');
                const type = this.getAttribute('data-type');
                userTypeInput.value = type;
                toggleUserTypeFields(type);
            });
        });

        function updateSubcategories() {
            const selected = Array.from(providerServiceRadios)
                .filter(radio => radio.checked)
                .map(radio => radio.value);
            
            subcatCheckboxes.innerHTML = ''; // Clear previous checkboxes

            if (selected.length > 0) {
                subcatContainer.style.display = 'block';
                selected.forEach(function(service) {
                    if (subcategories[service]) {
                        const serviceGroup = document.createElement('div');
                        serviceGroup.className = 'subcategory-group';
                        
                        const label = document.createElement('div');
                        label.className = 'subcategory-group-title';
                        label.textContent = service + ' Subcategories:';
                        serviceGroup.appendChild(label);
                        
                        const subcatGrid = document.createElement('div');
                        subcatGrid.className = 'subcategory-options';
                        
                        subcategories[service].forEach(function(subcat) {
                            const id = 'subcat_' + service.replace(/\s+/g, '_') + '_' + subcat.replace(/\s+/g, '_');
                            const checkboxLabel = document.createElement('label');
                            checkboxLabel.className = 'subcategory-option';
                            
                            const checkbox = document.createElement('input');
                            checkbox.type = 'checkbox';
                            checkbox.name = 'providerSubcategories[]';
                            checkbox.value = service + ' - ' + subcat;
                            checkbox.id = id;
                            
                            // Pre-select if previously submitted (for error re-display)
                            const prevSubcategories = <?php echo json_encode($_SESSION['signup_data']['providerSubcategories'] ?? []); ?>;
                            if (prevSubcategories.includes(service + ' - ' + subcat)) {
                                checkbox.checked = true;
                            }

                            checkboxLabel.appendChild(checkbox);
                            checkboxLabel.appendChild(document.createTextNode(' ' + subcat));
                            subcatGrid.appendChild(checkboxLabel);
                        });
                        
                        serviceGroup.appendChild(subcatGrid);
                        subcatCheckboxes.appendChild(serviceGroup);
                    }
                });
            } else {
                subcatContainer.style.display = 'none';
            }
        }

        // Add event listeners to all service radio buttons
        providerServiceRadios.forEach(function(radio) {
            radio.addEventListener('change', updateSubcategories);
        });

        // Initialize state based on pre-selected user type (e.g., from error re-display)
        const initialUserType = userTypeInput.value;
        toggleUserTypeFields(initialUserType);
        userTypeButtons.forEach(btn => {
            if (btn.getAttribute('data-type') === initialUserType) {
                btn.classList.add('active');
            } else {
                btn.classList.remove('active');
            }
        });

        // Password matching validation and OTP verification
        const signupForm = document.getElementById('signupForm');
        const otpModal = document.getElementById('otpModal');
        const otpEmail = document.getElementById('otpEmail');
        const otpInputs = document.getElementById('otpInputs');
        const verifyBtn = document.getElementById('verifyBtn');
        const resendBtn = document.getElementById('resendBtn');
        const timeLeft = document.getElementById('timeLeft');
        const resendTimer = document.getElementById('resendTimer');
        
        let timeRemaining = 600; // 10 minutes
        let resendTimeRemaining = 60; // 1 minute
        let timerInterval;
        let resendInterval;
        let currentFormData = {};
        
        signupForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Basic validation
            if (password.value !== confirmPassword.value) {
                alert('Passwords do not match!');
                confirmPassword.focus();
                return;
            }
            
            // Get email based on user type
            const userType = userTypeInput.value;
            const email = userType === 'customer' ? customerEmail.value : providerEmail.value;
            
            if (!email) {
                alert('Please enter your email address first.');
                return;
            }
            
            // Validate email format
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                alert('Please enter a valid email address.');
                return;
            }
            
            // Store form data
            const formData = new FormData(signupForm);
            currentFormData = {};
            formData.forEach((value, key) => {
                currentFormData[key] = value;
            });
            
            // Send OTP request
            sendOtpRequest(email, userType);
        });
        
        // Function to send OTP request
        function sendOtpRequest(email, userType) {
            const submitBtn = document.querySelector('.signup-btn');
            const originalText = submitBtn.innerHTML;
            
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending OTP...';
            
            fetch('../handlers/handle_signup_otp.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=send_otp&email=' + encodeURIComponent(email) + '&userType=' + encodeURIComponent(userType)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Show OTP modal
                    otpEmail.textContent = email;
                    showOtpModal();
                    setupOtpInputs();
                    startTimers();
                } else {
                    alert(data.message);
                }
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred. Please try again.');
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            });
        }
        
        // Show OTP modal
        function showOtpModal() {
            otpModal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }
        
        // Hide OTP modal
        function hideOtpModal() {
            otpModal.style.display = 'none';
            document.body.style.overflow = 'auto';
        }
        
        // Setup OTP input fields
        function setupOtpInputs() {
            otpInputs.innerHTML = '';
            for (let i = 0; i < 6; i++) {
                const input = document.createElement('input');
                input.type = 'text';
                input.maxLength = 1;
                input.addEventListener('input', function(e) {
                    // Only allow numbers
                    e.target.value = e.target.value.replace(/[^0-9]/g, '');
                    
                    if (e.target.value && i < 5) {
                        otpInputs.children[i + 1].focus();
                    }
                    checkOtpFilled();
                });
                input.addEventListener('keydown', function(e) {
                    if (e.key === 'Backspace' && !e.target.value && i > 0) {
                        otpInputs.children[i - 1].focus();
                    }
                });
                otpInputs.appendChild(input);
            }
            otpInputs.children[0].focus();
        }
        
        // Check if all OTP fields are filled
        function checkOtpFilled() {
            const otp = Array.from(otpInputs.children).map(inp => inp.value).join('');
            verifyBtn.disabled = otp.length !== 6;
        }
        
        // Start timers
        function startTimers() {
            timeRemaining = 600;
            resendTimeRemaining = 60;
            
            timerInterval = setInterval(updateTimer, 1000);
            resendInterval = setInterval(updateResendTimer, 1000);
        }
        
        // Update timer
        function updateTimer() {
            const mins = Math.floor(timeRemaining / 60);
            const secs = timeRemaining % 60;
            timeLeft.textContent = `${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
            timeRemaining--;
            
            if (timeRemaining < 0) {
                clearInterval(timerInterval);
                alert('OTP has expired. Please request a new one.');
                hideOtpModal();
            }
        }
        
        // Update resend timer
        function updateResendTimer() {
            resendTimer.textContent = resendTimeRemaining;
            resendTimeRemaining--;
            
            if (resendTimeRemaining < 0) {
                resendBtn.disabled = false;
                resendBtn.innerHTML = '<i class="fas fa-redo"></i> Resend OTP';
                clearInterval(resendInterval);
            }
        }
        
        // Verify OTP
        verifyBtn.addEventListener('click', function() {
            const otp = Array.from(otpInputs.children).map(inp => inp.value).join('');
            if (otp.length !== 6) {
                alert('Please enter a valid 6-digit OTP.');
                return;
            }
            
            verifyBtn.disabled = true;
            verifyBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Verifying...';
            
            fetch('../handlers/handle_signup_otp.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=verify_otp&otp=' + otp + '&email=' + encodeURIComponent(otpEmail.textContent)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // OTP verified, now complete registration
                    completeRegistration();
                } else {
                    alert(data.message);
                    verifyBtn.disabled = false;
                    verifyBtn.innerHTML = '<i class="fas fa-check"></i> Verify Email';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred. Please try again.');
                verifyBtn.disabled = false;
                verifyBtn.innerHTML = '<i class="fas fa-check"></i> Verify Email';
            });
        });
        
        // Resend OTP
        resendBtn.addEventListener('click', function() {
            if (resendBtn.disabled) return;
            
            resendBtn.disabled = true;
            resendBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';
            
            const userType = currentFormData.userType;
            const email = userType === 'customer' ? currentFormData.email : currentFormData.providerEmail;
            
            fetch('../handlers/handle_signup_otp.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=resend_otp&email=' + encodeURIComponent(email) + '&userType=' + encodeURIComponent(userType)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('New OTP sent to your email address.');
                    resendTimeRemaining = 60;
                    resendInterval = setInterval(updateResendTimer, 1000);
                    setupOtpInputs();
                } else {
                    alert(data.message);
                }
                resendBtn.disabled = false;
                resendBtn.innerHTML = '<i class="fas fa-redo"></i> Resend OTP (<span id="resendTimer">60</span>s)';
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred. Please try again.');
                resendBtn.disabled = false;
                resendBtn.innerHTML = '<i class="fas fa-redo"></i> Resend OTP';
            });
        });
        
        // Complete registration
        function completeRegistration() {
            verifyBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creating Account...';
            
            // Send form data to complete registration
            const formData = new FormData();
            Object.keys(currentFormData).forEach(key => {
                formData.append(key, currentFormData[key]);
            });
            formData.append('otpVerified', 'true');
            
            fetch('../handlers/handle_signup.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (response.ok) {
                    // Registration successful, redirect to login
                    alert('Account created successfully! Redirecting to login...');
                    window.location.href = 'login.php';
                } else {
                    throw new Error('Registration failed');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Registration failed. Please try again.');
                verifyBtn.disabled = false;
                verifyBtn.innerHTML = '<i class="fas fa-check"></i> Verify Email';
            });
        }
        
        // Close modal when clicking outside
        otpModal.addEventListener('click', function(e) {
            if (e.target === otpModal) {
                hideOtpModal();
            }
        });
    });
    </script>
</body>
</html>