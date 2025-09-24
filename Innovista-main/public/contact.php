<?php
// Define the page title for this specific page
$pageTitle = 'Contact Us'; 
// Include the master header
include 'header.php'; 
?>

<!-- =========================================
     CONTACT HERO HEADER
     ========================================= -->
<section class="contact-hero">
    <div class="container">
        <h1>Get in Touch</h1>
        <p>We're here to help! Whether you have a question about our services or need support, please reach out.</p>
    </div>
</section>

<!-- =========================================
     MAIN CONTACT CONTENT
     ========================================= -->
<main class="contact-main-content page-section">
    <div class="container">
        <div class="contact-layout">
            <!-- Left Side: Contact Form -->
            <div class="contact-form-wrapper">
                <h2 class="form-title">Send Us a Message</h2>
                <form id="contactForm" action="../handlers/handle_contact.php" method="POST" novalidate>
                    <div class="form-group">
                        <label for="name">Your Name</label>
                        <input type="text" id="name" name="name" placeholder="Enter your full name" required>
                        <div class="error" id="nameError"></div>
                    </div>
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" placeholder="you@example.com" required>
                        <div class="error" id="emailError"></div>
                    </div>
                    <div class="form-group">
                        <label for="subject">Subject</label>
                        <input type="text" id="subject" name="subject" placeholder="e.g., Question about Interior Design" required>
                        <div class="error" id="subjectError"></div>
                    </div>
                    <div class="form-group">
                        <label for="message">Message</label>
                        <textarea id="message" name="message" rows="5" placeholder="Enter your message here..." required></textarea>
                        <div class="error" id="messageError"></div>
                    </div>
                    <div id="formMessage" class="form-message"></div>
                    <button type="submit" class="btn btn-primary btn-submit" id="submitBtn">
                        <span class="btn-text">Send Message</span>
                        <span class="btn-loading" style="display: none;">
                            <i class="fas fa-spinner fa-spin"></i> Sending...
                        </span>
                    </button>
                </form>
            </div>

            <!-- Right Side: Contact Information -->
            <div class="contact-info-wrapper">
                <h2 class="info-title">Contact Information</h2>
                <p class="info-intro">Feel free to contact us through any of the following methods. We look forward to hearing from you.</p>
                <div class="info-item">
                    <i class="fas fa-map-marker-alt"></i>
                    <div>
                        <h3>Our Office</h3>
                        <p>25, KKS Road, Jaffna, Sri Lanka</p>
                    </div>
                </div>
                <div class="info-item">
                    <i class="fas fa-phone"></i>
                    <div>
                        <h3>Phone</h3>
                        <p>(+94) 77 442 2448</p>
                    </div>
                </div>
                <div class="info-item">
                    <i class="fas fa-envelope"></i>
                    <div>
                        <h3>Email</h3>
                        <p>info@innovista.com</p>
                    </div>
                </div>
                <div class="info-item">
                    <i class="fas fa-clock"></i>
                    <div>
                        <h3>Business Hours</h3>
                        <p>Monday - Friday: 9:00 AM - 6:00 PM</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Google Maps Section -->
        <div class="map-section">
            <iframe 
                src="https://www.google.com/maps?q=Jaffna+Northern+Province+Sri+Lanka&output=embed" 
                width="100%" 
                height="450" 
                style="border:0;" 
                allowfullscreen="" 
                loading="lazy" 
                referrerpolicy="no-referrer-when-downgrade">
            </iframe>
        </div>
    </div>
</main>

<!-- JavaScript for Contact Form -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('contactForm');
    const submitBtn = document.getElementById('submitBtn');
    const btnText = submitBtn.querySelector('.btn-text');
    const btnLoading = submitBtn.querySelector('.btn-loading');
    const formMessage = document.getElementById('formMessage');

    // Form validation
    function validateField(field, errorElement, validationFn, errorMessage) {
        const value = field.value.trim();
        if (!validationFn(value)) {
            errorElement.textContent = errorMessage;
            field.classList.add('error');
            return false;
        } else {
            errorElement.textContent = '';
            field.classList.remove('error');
            return true;
        }
    }

    function validateEmail(email) {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    }

    // Real-time validation
    const nameField = document.getElementById('name');
    const emailField = document.getElementById('email');
    const subjectField = document.getElementById('subject');
    const messageField = document.getElementById('message');

    nameField.addEventListener('blur', function() {
        validateField(this, document.getElementById('nameError'), 
            val => val.length >= 2, 'Name must be at least 2 characters long');
    });

    emailField.addEventListener('blur', function() {
        validateField(this, document.getElementById('emailError'), 
            validateEmail, 'Please enter a valid email address');
    });

    subjectField.addEventListener('blur', function() {
        validateField(this, document.getElementById('subjectError'), 
            val => val.length >= 3, 'Subject must be at least 3 characters long');
    });

    messageField.addEventListener('blur', function() {
        validateField(this, document.getElementById('messageError'), 
            val => val.length >= 10, 'Message must be at least 10 characters long');
    });

    // Form submission
    form.addEventListener('submit', function(e) {
        e.preventDefault();

        // Validate all fields
        const isNameValid = validateField(nameField, document.getElementById('nameError'), 
            val => val.length >= 2, 'Name must be at least 2 characters long');
        
        const isEmailValid = validateField(emailField, document.getElementById('emailError'), 
            validateEmail, 'Please enter a valid email address');
        
        const isSubjectValid = validateField(subjectField, document.getElementById('subjectError'), 
            val => val.length >= 3, 'Subject must be at least 3 characters long');
        
        const isMessageValid = validateField(messageField, document.getElementById('messageError'), 
            val => val.length >= 10, 'Message must be at least 10 characters long');

        if (!isNameValid || !isEmailValid || !isSubjectValid || !isMessageValid) {
            showMessage('Please fix the errors above.', 'error');
            return;
        }

        // Show loading state
        submitBtn.disabled = true;
        btnText.style.display = 'none';
        btnLoading.style.display = 'inline-block';
        formMessage.textContent = '';

        // Prepare form data
        const formData = new FormData(form);

        // Send AJAX request
        fetch(form.action, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                showMessage(data.message, 'success');
                form.reset(); // Clear form
                // Clear any error classes
                document.querySelectorAll('.error').forEach(el => el.classList.remove('error'));
                document.querySelectorAll('.error').forEach(el => el.textContent = '');
            } else {
                showMessage(data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showMessage('Sorry, there was an error sending your message. Please try again.', 'error');
        })
        .finally(() => {
            // Reset button state
            submitBtn.disabled = false;
            btnText.style.display = 'inline-block';
            btnLoading.style.display = 'none';
        });
    });

    function showMessage(message, type) {
        formMessage.textContent = message;
        formMessage.className = `form-message ${type}`;
        formMessage.scrollIntoView({ behavior: 'smooth', block: 'center' });
        
        // Auto hide success messages after 5 seconds
        if (type === 'success') {
            setTimeout(() => {
                formMessage.textContent = '';
                formMessage.className = 'form-message';
            }, 5000);
        }
    }
});
</script>

<style>
/* Contact Form Validation Styles */
.form-group input.error,
.form-group textarea.error {
    border-color: #dc3545;
    box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
}

.form-group .error {
    color: #dc3545;
    font-size: 0.875rem;
    margin-top: 0.25rem;
    display: block;
}

.form-message {
    padding: 0.75rem 1rem;
    margin-bottom: 1rem;
    border-radius: 4px;
    font-weight: 500;
}

.form-message.success {
    background-color: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.form-message.error {
    background-color: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

.btn-loading {
    color: #007bff;
}

.btn-submit:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}
</style>

<?php 
// Include the master footer
include 'footer.php'; 
?>