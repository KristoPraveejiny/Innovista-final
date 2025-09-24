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

require_once '../config/Database.php';

$db = (new Database())->getConnection();
$customer_id = getUserId();

// Get project ID and payment type from URL
$project_id = filter_input(INPUT_GET, 'project_id', FILTER_VALIDATE_INT);
$payment_type = filter_input(INPUT_GET, 'type', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

if (!$project_id) {
    set_flash_message('error', 'Invalid Project ID provided.');
    header('Location: my_projects.php');
    exit();
}

// Fetch project details
try {
    $stmt = $db->prepare("
        SELECT 
            p.id as project_id,
            p.status,
            cq.id as custom_quotation_id,
            cq.project_description,
            cq.amount,
            cq.advance,
            prov.name as provider_name,
            prov.email as provider_email
        FROM projects p
        JOIN custom_quotations cq ON p.quotation_id = cq.id
        JOIN users prov ON cq.provider_id = prov.id
        WHERE p.id = :project_id AND cq.customer_id = :customer_id
    ");
    $stmt->bindParam(':project_id', $project_id, PDO::PARAM_INT);
    $stmt->bindParam(':customer_id', $customer_id, PDO::PARAM_INT);
    $stmt->execute();
    $project_data = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$project_data) {
        set_flash_message('error', 'Project not found or you do not have permission to view it.');
        header('Location: my_projects.php');
        exit();
    }

    $remaining_balance = $project_data['amount'] - $project_data['advance'];
    
    if ($remaining_balance <= 0) {
        set_flash_message('info', 'This project is already fully paid.');
        header('Location: my_projects.php');
        exit();
    }

} catch (PDOException $e) {
    error_log("Payment Details Error: " . $e->getMessage());
    set_flash_message('error', 'Error loading project details. Please try again.');
    header('Location: my_projects.php');
    exit();
}

$pageTitle = 'Payment Details';
require_once '../includes/user_dashboard_header.php';
?>

<div class="dashboard-section">
    <h2>Payment Details</h2>
    <div class="content-card">
        <div class="project-summary">
            <h3>Project Information</h3>
            <div class="info-grid">
                <div class="info-item">
                    <label>Project:</label>
                    <span><?php echo htmlspecialchars($project_data['project_description']); ?></span>
                </div>
                <div class="info-item">
                    <label>Provider:</label>
                    <span><?php echo htmlspecialchars($project_data['provider_name']); ?></span>
                </div>
                <div class="info-item">
                    <label>Total Amount:</label>
                    <span>Rs <?php echo number_format($project_data['amount'], 2); ?></span>
                </div>
                <div class="info-item">
                    <label>Advance Paid:</label>
                    <span>Rs <?php echo number_format($project_data['advance'], 2); ?></span>
                </div>
                <div class="info-item highlight">
                    <label>Remaining Balance:</label>
                    <span class="remaining-amount">Rs <?php echo number_format($remaining_balance, 2); ?></span>
                </div>
            </div>
        </div>

        <div class="payment-section">
            <h3>Make Payment</h3>
            <div class="payment-form">
                <form action="../handlers/initiate_final_payment.php" method="POST" id="paymentForm">
                    <input type="hidden" name="project_id" value="<?php echo htmlspecialchars($project_data['project_id']); ?>">
                    <input type="hidden" name="custom_quotation_id" value="<?php echo htmlspecialchars($project_data['custom_quotation_id']); ?>">
                    <input type="hidden" name="amount" value="<?php echo $remaining_balance; ?>">
                    
                    <div class="form-group">
                        <label for="payment_method">Payment Method:</label>
                        <select name="payment_method" id="payment_method" required>
                            <option value="">Select Payment Method</option>
                            <option value="credit_card">Credit Card</option>
                            <option value="debit_card">Debit Card</option>
                            <option value="bank_transfer">Bank Transfer</option>
                            <option value="upi">UPI</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="card_number">Card Number:</label>
                        <input type="text" name="card_number" id="card_number" placeholder="1234 5678 9012 3456" maxlength="19" required>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="expiry_date">Expiry Date:</label>
                            <input type="text" name="expiry_date" id="expiry_date" placeholder="MM/YY" maxlength="5" required>
                        </div>
                        <div class="form-group">
                            <label for="cvv">CVV:</label>
                            <input type="text" name="cvv" id="cvv" placeholder="123" maxlength="3" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="cardholder_name">Cardholder Name:</label>
                        <input type="text" name="cardholder_name" id="cardholder_name" placeholder="John Doe" required>
                    </div>

                    <div class="payment-summary">
                        <div class="summary-row">
                            <span>Amount to Pay:</span>
                            <span class="amount">Rs <?php echo number_format($remaining_balance, 2); ?></span>
                        </div>
                    </div>

                    <button type="submit" class="btn-submit payment-btn">
                        <i class="fas fa-credit-card"></i>
                        Pay Rs <?php echo number_format($remaining_balance, 2); ?>
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
.project-summary {
    margin-bottom: 2rem;
}

.info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1rem;
    margin-top: 1rem;
}

.info-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.75rem;
    background-color: #f8f9fa;
    border-radius: 8px;
    border-left: 4px solid #007bff;
}

.info-item.highlight {
    background-color: #fff3cd;
    border-left-color: #ffc107;
}

.info-item label {
    font-weight: 600;
    color: #495057;
}

.remaining-amount {
    color: #dc2626;
    font-weight: 700;
    font-size: 1.2rem;
}

.payment-section {
    border-top: 2px solid #e9ecef;
    padding-top: 2rem;
}

.payment-form {
    max-width: 600px;
    margin: 0 auto;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
}

.payment-summary {
    background-color: #f8f9fa;
    padding: 1rem;
    border-radius: 8px;
    margin: 1.5rem 0;
}

.summary-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 1.1rem;
    font-weight: 600;
}

.amount {
    color: #dc2626;
    font-size: 1.3rem;
}

.payment-btn {
    width: 100%;
    padding: 1rem;
    font-size: 1.1rem;
    background: linear-gradient(135deg, #28a745, #20c997);
    border: none;
    color: white;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.3s ease;
}

.payment-btn:hover {
    background: linear-gradient(135deg, #218838, #1ea085);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(40, 167, 69, 0.3);
}

.payment-btn i {
    margin-right: 0.5rem;
}

/* Card validation visual feedback */
.payment-form input.valid {
    border-color: #28a745;
    box-shadow: 0 0 0 0.2rem rgba(40, 167, 69, 0.25);
}

.payment-form input.invalid {
    border-color: #dc3545;
    box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
}

/* Card type icons */
.payment-form input.card-visa {
    background-image: url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMzIiIGhlaWdodD0iMjAiIHZpZXdCb3g9IjAgMCAzMiAyMCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHJlY3Qgd2lkdGg9IjMyIiBoZWlnaHQ9IjIwIiByeD0iNCIgZmlsbD0iIzAwNTFBNSIvPgo8L3N2Zz4=');
    background-repeat: no-repeat;
    background-position: right 10px center;
    background-size: 32px 20px;
    padding-right: 50px;
}

.payment-form input.card-mastercard {
    background-image: url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMzIiIGhlaWdodD0iMjAiIHZpZXdCb3g9IjAgMCAzMiAyMCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPGNpcmNsZSBjeD0iMTIiIGN5PSIxMCIgcj0iOCIgZmlsbD0iI0VCMDAxQiIvPgo8Y2lyY2xlIGN4PSIyMCIgY3k9IjEwIiByPSI4IiBmaWxsPSIjRkY1RjAwIi8+Cjwvc3ZnPg==');
    background-repeat: no-repeat;
    background-position: right 10px center;
    background-size: 32px 20px;
    padding-right: 50px;
}

.payment-form input.card-amex {
    background-image: url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMzIiIGhlaWdodD0iMjAiIHZpZXdCb3g9IjAgMCAzMiAyMCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHJlY3Qgd2lkdGg9IjMyIiBoZWlnaHQ9IjIwIiByeD0iNCIgZmlsbD0iIzJFQjNGNCIvPgo8L3N2Zz4=');
    background-repeat: no-repeat;
    background-position: right 10px center;
    background-size: 32px 20px;
    padding-right: 50px;
}

/* Loading state */
.payment-btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.payment-btn:disabled:hover {
    transform: none;
    box-shadow: none;
}
</style>

<script>
// Luhn Algorithm for card validation
function validateCardNumber(cardNumber) {
    // Remove spaces and check if only digits
    cardNumber = cardNumber.replace(/\s/g, '');
    if (!/^\d+$/.test(cardNumber)) return false;
    
    // Must be between 13-19 digits
    if (cardNumber.length < 13 || cardNumber.length > 19) return false;
    
    // Apply Luhn algorithm
    let sum = 0;
    let isEven = false;
    
    for (let i = cardNumber.length - 1; i >= 0; i--) {
        let digit = parseInt(cardNumber.charAt(i));
        
        if (isEven) {
            digit *= 2;
            if (digit > 9) {
                digit = digit % 10 + 1;
            }
        }
        
        sum += digit;
        isEven = !isEven;
    }
    
    return (sum % 10) === 0;
}

// Detect card type
function getCardType(cardNumber) {
    cardNumber = cardNumber.replace(/\s/g, '');
    
    const cardTypes = {
        visa: /^4[0-9]{12}(?:[0-9]{3})?$/,
        mastercard: /^5[1-5][0-9]{14}$/,
        amex: /^3[47][0-9]{13}$/,
        discover: /^6(?:011|5[0-9]{2})[0-9]{12}$/,
        dinersclub: /^3[0689][0-9]{11}$/,
        jcb: /^(?:2131|1800|35\d{3})\d{11}$/
    };
    
    for (let type in cardTypes) {
        if (cardTypes[type].test(cardNumber)) {
            return type;
        }
    }
    return 'unknown';
}

// Update card type display
function updateCardType(cardNumber) {
    const cardType = getCardType(cardNumber);
    const cardInput = document.getElementById('card_number');
    
    // Remove existing card type classes
    cardInput.className = cardInput.className.replace(/card-\w+/g, '');
    
    if (cardType !== 'unknown') {
        cardInput.classList.add('card-' + cardType);
    }
    
    return cardType;
}

// Real-time card validation with visual feedback
document.getElementById('card_number').addEventListener('input', function(e) {
    let value = e.target.value.replace(/\s/g, '').replace(/[^0-9]/gi, '');
    
    // Format with spaces every 4 digits
    let formattedValue = value.match(/.{1,4}/g)?.join(' ') || value;
    e.target.value = formattedValue;
    
    // Update card type and validation
    const cardType = updateCardType(value);
    const isValid = validateCardNumber(value);
    
    // Visual feedback
    e.target.classList.remove('valid', 'invalid');
    if (value.length > 0) {
        if (isValid) {
            e.target.classList.add('valid');
        } else if (value.length >= 13) {
            e.target.classList.add('invalid');
        }
    }
});

// Validate expiry date
function validateExpiryDate(expiry) {
    if (!/^\d{2}\/\d{2}$/.test(expiry)) return false;
    
    const [month, year] = expiry.split('/').map(Number);
    const currentDate = new Date();
    const currentYear = currentDate.getFullYear() % 100;
    const currentMonth = currentDate.getMonth() + 1;
    
    if (month < 1 || month > 12) return false;
    if (year < currentYear) return false;
    if (year === currentYear && month < currentMonth) return false;
    
    return true;
}

// Format expiry date input with validation
document.getElementById('expiry_date').addEventListener('input', function(e) {
    let value = e.target.value.replace(/\D/g, '');
    if (value.length >= 2) {
        value = value.substring(0, 2) + '/' + value.substring(2, 4);
    }
    e.target.value = value;
    
    // Visual validation feedback
    e.target.classList.remove('valid', 'invalid');
    if (value.length === 5) {
        if (validateExpiryDate(value)) {
            e.target.classList.add('valid');
        } else {
            e.target.classList.add('invalid');
        }
    }
});

// Format CVV input
document.getElementById('cvv').addEventListener('input', function(e) {
    e.target.value = e.target.value.replace(/[^0-9]/g, '');
    
    // Visual validation
    const cvv = e.target.value;
    e.target.classList.remove('valid', 'invalid');
    if (cvv.length >= 3) {
        if (cvv.length === 3 || cvv.length === 4) {
            e.target.classList.add('valid');
        } else {
            e.target.classList.add('invalid');
        }
    }
});

// Enhanced form validation
document.getElementById('paymentForm').addEventListener('submit', function(e) {
    const cardNumber = document.getElementById('card_number').value.replace(/\s/g, '');
    const expiryDate = document.getElementById('expiry_date').value;
    const cvv = document.getElementById('cvv').value;
    const cardholderName = document.getElementById('cardholder_name').value.trim();
    
    let errors = [];
    
    // Validate cardholder name
    if (!cardholderName || cardholderName.length < 2) {
        errors.push('Please enter a valid cardholder name.');
    }
    
    // Validate card number
    if (!validateCardNumber(cardNumber)) {
        errors.push('Please enter a valid card number.');
    }
    
    // Validate expiry date
    if (!validateExpiryDate(expiryDate)) {
        errors.push('Please enter a valid expiry date (MM/YY).');
    }
    
    // Validate CVV
    if (cvv.length < 3 || cvv.length > 4) {
        errors.push('Please enter a valid CVV (3-4 digits).');
    }
    
    // Show errors if any
    if (errors.length > 0) {
        e.preventDefault();
        alert('Please correct the following errors:\n\n' + errors.join('\n'));
        return false;
    }
    
    // Show loading state
    const submitButton = e.target.querySelector('button[type="submit"]');
    submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing Payment...';
    submitButton.disabled = true;
});
</script>

<?php require_once '../includes/user_dashboard_footer.php'; ?>
