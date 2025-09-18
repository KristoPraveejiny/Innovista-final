<?php
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

// Check if there's a pending payment
if (!isset($_SESSION['pending_final_payment'])) {
    set_flash_message('error', 'No pending payment found. Please start the payment process again.');
    header('Location: my_projects.php');
    exit();
}

$pending_payment = $_SESSION['pending_final_payment'];
$project_id = filter_input(INPUT_GET, 'project_id', FILTER_VALIDATE_INT);

// Verify project ID matches
if (!$project_id || $project_id != $pending_payment['project_id']) {
    unset($_SESSION['pending_final_payment']);
    set_flash_message('error', 'Invalid payment session. Please start the payment process again.');
    header('Location: my_projects.php');
    exit();
}

// Check if OTP has expired (10 minutes)
$otp_age = time() - $pending_payment['created_at'];
if ($otp_age > 600) { // 10 minutes
    unset($_SESSION['pending_final_payment']);
    set_flash_message('error', 'OTP has expired. Please start the payment process again.');
    header('Location: payment_details.php?project_id=' . $project_id);
    exit();
}

$pageTitle = 'Verify Payment OTP';
require_once '../includes/user_dashboard_header.php';
?>

<div class="dashboard-section">
    <h2>Verify Payment OTP</h2>
    <div class="content-card">
        <div class="otp-verification">
            <div class="verification-header">
                <div class="verification-icon">
                    <i class="fas fa-shield-alt"></i>
                </div>
                <h3>Payment Verification Required</h3>
                <p>We've sent a 6-digit OTP to your email address. Please enter it below to complete your payment.</p>
            </div>

            <div class="payment-summary">
                <h4>Payment Details</h4>
                <div class="summary-grid">
                    <div class="summary-item">
                        <span class="label">Amount:</span>
                        <span class="value">Rs <?php echo number_format($pending_payment['amount'], 2); ?></span>
                    </div>
                    <div class="summary-item">
                        <span class="label">Payment Method:</span>
                        <span class="value"><?php echo ucfirst(str_replace('_', ' ', $pending_payment['payment_method'])); ?></span>
                    </div>
                    <div class="summary-item">
                        <span class="label">Transaction ID:</span>
                        <span class="value"><?php echo htmlspecialchars($pending_payment['transaction_id']); ?></span>
                    </div>
                    <div class="summary-item">
                        <span class="label">Card Ending:</span>
                        <span class="value">**** **** **** <?php echo substr($pending_payment['card_number'], -4); ?></span>
                    </div>
                </div>
            </div>

            <form action="../handlers/verify_payment_otp.php" method="POST" id="otpForm">
                <input type="hidden" name="project_id" value="<?php echo htmlspecialchars($project_id); ?>">
                <input type="hidden" name="transaction_id" value="<?php echo htmlspecialchars($pending_payment['transaction_id']); ?>">
                
                <div class="otp-input-container">
                    <label for="otp">Enter 6-digit OTP:</label>
                    <div class="otp-inputs">
                        <input type="text" name="otp" id="otp" maxlength="6" pattern="[0-9]{6}" required autocomplete="off">
                    </div>
                </div>

                <div class="otp-timer">
                    <span id="timer">Time remaining: <span id="time-left">10:00</span></span>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn-submit verify-btn" id="verifyBtn">
                        <i class="fas fa-check"></i>
                        Verify & Complete Payment
                    </button>
                    <a href="payment_details.php?project_id=<?php echo $project_id; ?>" class="btn-cancel">
                        <i class="fas fa-arrow-left"></i>
                        Back to Payment
                    </a>
                </div>

                <div class="resend-section">
                    <p>Didn't receive the OTP?</p>
                    <button type="button" class="btn-resend" id="resendBtn" disabled>
                        Resend OTP (<span id="resend-timer">60</span>s)
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.otp-verification {
    max-width: 500px;
    margin: 0 auto;
    text-align: center;
}

.verification-header {
    margin-bottom: 2rem;
}

.verification-icon {
    font-size: 3rem;
    color: #007bff;
    margin-bottom: 1rem;
}

.verification-icon i {
    background: linear-gradient(135deg, #007bff, #0056b3);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.payment-summary {
    background-color: #f8f9fa;
    padding: 1.5rem;
    border-radius: 8px;
    margin-bottom: 2rem;
    text-align: left;
}

.summary-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
    margin-top: 1rem;
}

.summary-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.5rem 0;
    border-bottom: 1px solid #e9ecef;
}

.summary-item:last-child {
    border-bottom: none;
}

.summary-item .label {
    font-weight: 600;
    color: #495057;
}

.summary-item .value {
    color: #007bff;
    font-weight: 500;
}

.otp-input-container {
    margin-bottom: 1.5rem;
}

.otp-input-container label {
    display: block;
    margin-bottom: 1rem;
    font-weight: 600;
    color: #495057;
}

.otp-inputs {
    display: flex;
    justify-content: center;
    gap: 0.5rem;
}

.otp-inputs input {
    width: 60px;
    height: 60px;
    text-align: center;
    font-size: 1.5rem;
    font-weight: 600;
    border: 2px solid #e9ecef;
    border-radius: 8px;
    transition: all 0.3s ease;
}

.otp-inputs input:focus {
    border-color: #007bff;
    box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.1);
    outline: none;
}

.otp-timer {
    margin-bottom: 1.5rem;
    color: #dc3545;
    font-weight: 600;
}

.form-actions {
    display: flex;
    flex-direction: column;
    gap: 1rem;
    margin-bottom: 2rem;
}

.verify-btn {
    background: linear-gradient(135deg, #28a745, #20c997);
    color: white;
    border: none;
    padding: 1rem 2rem;
    border-radius: 8px;
    font-size: 1.1rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
}

.verify-btn:hover {
    background: linear-gradient(135deg, #218838, #1ea085);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(40, 167, 69, 0.3);
}

.verify-btn:disabled {
    background: #6c757d;
    cursor: not-allowed;
    transform: none;
    box-shadow: none;
}

.btn-cancel {
    color: #6c757d;
    text-decoration: none;
    padding: 0.75rem 1.5rem;
    border: 1px solid #6c757d;
    border-radius: 8px;
    transition: all 0.3s ease;
}

.btn-cancel:hover {
    background-color: #6c757d;
    color: white;
    text-decoration: none;
}

.resend-section {
    border-top: 1px solid #e9ecef;
    padding-top: 1rem;
}

.btn-resend {
    background: none;
    border: 1px solid #007bff;
    color: #007bff;
    padding: 0.5rem 1rem;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.3s ease;
}

.btn-resend:hover:not(:disabled) {
    background-color: #007bff;
    color: white;
}

.btn-resend:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

@media (max-width: 768px) {
    .summary-grid {
        grid-template-columns: 1fr;
    }
    
    .otp-inputs input {
        width: 50px;
        height: 50px;
        font-size: 1.2rem;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const otpInput = document.getElementById('otp');
    const verifyBtn = document.getElementById('verifyBtn');
    const resendBtn = document.getElementById('resendBtn');
    const timeLeft = document.getElementById('time-left');
    const resendTimer = document.getElementById('resend-timer');
    
    let timeRemaining = 600; // 10 minutes in seconds
    let resendTimeRemaining = 60; // 1 minute for resend
    
    // Format time as MM:SS
    function formatTime(seconds) {
        const mins = Math.floor(seconds / 60);
        const secs = seconds % 60;
        return `${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
    }
    
    // Update timer
    function updateTimer() {
        timeLeft.textContent = formatTime(timeRemaining);
        timeRemaining--;
        
        if (timeRemaining < 0) {
            alert('OTP has expired. Please start the payment process again.');
            window.location.href = 'my_projects.php';
            return;
        }
    }
    
    // Update resend timer
    function updateResendTimer() {
        resendTimer.textContent = resendTimeRemaining;
        resendTimeRemaining--;
        
        if (resendTimeRemaining < 0) {
            resendBtn.disabled = false;
            resendBtn.innerHTML = 'Resend OTP';
            return;
        }
    }
    
    // Start timers
    const timerInterval = setInterval(updateTimer, 1000);
    const resendInterval = setInterval(updateResendTimer, 1000);
    
    // OTP input formatting
    otpInput.addEventListener('input', function(e) {
        // Only allow numbers
        e.target.value = e.target.value.replace(/[^0-9]/g, '');
        
        // Enable/disable verify button
        verifyBtn.disabled = e.target.value.length !== 6;
    });
    
    // Form submission
    document.getElementById('otpForm').addEventListener('submit', function(e) {
        const otp = otpInput.value;
        if (otp.length !== 6) {
            e.preventDefault();
            alert('Please enter a valid 6-digit OTP.');
            return;
        }
        
        verifyBtn.disabled = true;
        verifyBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Verifying...';
    });
    
    // Resend OTP
    resendBtn.addEventListener('click', function() {
        if (resendBtn.disabled) return;
        
        resendBtn.disabled = true;
        resendBtn.innerHTML = 'Sending...';
        resendTimeRemaining = 60;
        
        fetch('../handlers/resend_payment_otp.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'project_id=' + <?php echo $project_id; ?>
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('OTP has been resent to your email.');
                resendBtn.innerHTML = 'Resend OTP (<span id="resend-timer">60</span>s)';
            } else {
                alert('Failed to resend OTP. Please try again.');
                resendBtn.disabled = false;
                resendBtn.innerHTML = 'Resend OTP';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred. Please try again.');
            resendBtn.disabled = false;
            resendBtn.innerHTML = 'Resend OTP';
        });
    });
    
    // Clean up timers when page unloads
    window.addEventListener('beforeunload', function() {
        clearInterval(timerInterval);
        clearInterval(resendInterval);
    });
});
</script>

<?php require_once '../includes/user_dashboard_footer.php'; ?>
