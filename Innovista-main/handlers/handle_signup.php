<?php
// C:\xampp1\htdocs\Innovista-final\Innovista-main\handlers\handle_signup.php

// Ensure session and helper functions are loaded
require_once '../public/session.php'; // Defines isUserLoggedIn, getUserRole, getUserId, getImageSrc
require_once '../config/Database.php';
require_once '../classes/User.php';
require_once '../classes/Service.php'; // Assuming you have a Service class for provider services
require_once '../handlers/flash_message.php'; // Assuming this file exists and defines set_flash_message

// If a user is already logged in, redirect them away from signup
if (isUserLoggedIn()) {
        $userRole = getUserRole();
        if ($userRole === 'admin') {
            header("Location: ../admin/admin_dashboard.php");
        } elseif ($userRole === 'provider') {
            // Corrected path: from handlers/ to provider/
            header("Location: ../provider/provider_dashboard.php");
        } else { // customer or unknown
            // Corrected path: from handlers/ to customer/
            header("Location: ../customer/customer_dashboard.php");
        }
        exit();
    }

// Check if the form was submitted using POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../public/signup.php');
    exit();
}

// Check if OTP verification is required and completed
$otpVerified = filter_input(INPUT_POST, 'otpVerified', FILTER_SANITIZE_STRING);
if ($otpVerified !== 'true') {
    // This should not happen with the new modal flow, but keep as safety check
    set_flash_message('error', 'Please verify your email address with OTP before completing registration.');
    header('Location: ../public/signup.php');
    exit();
}

// Store submitted data in session for re-filling form on error
$_SESSION['signup_data'] = $_POST;
// Clear subcategories from session if it's an array for proper re-encoding later
// This line ensures the array is properly maintained for JavaScript processing


// Get the user type ('customer' or 'provider')
$userType = $_POST['userType'] ?? 'customer';

// Common fields
$password = $_POST['password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';

// User-specific fields
$name = '';
$email = '';
$phone = '';
$address = '';
$bio = ''; // Only for providers
$portfolio_text = ''; // Only for providers, for external link/description
$main_services_array = []; // Array for provider's main services
$subcategories_array = []; // Array for provider's subcategories

// Default profile image path
$profile_image_path = 'assets/images/default-avatar.jpg';

if ($userType === 'provider') {
    $name = $_POST['providerFullname'] ?? '';
    $email = $_POST['providerEmail'] ?? '';
    $phone = $_POST['providerPhone'] ?? '';
    $address = $_POST['providerAddress'] ?? '';
    $bio = $_POST['provider_bio'] ?? '';
    // Always treat providerService as an array, even if only one is selected (radio)
    $main_services_array = [];
    if (isset($_POST['providerService']) && $_POST['providerService'] !== '') {
        $main_services_array[] = $_POST['providerService'];
    }
    $subcategories_array = $_POST['providerSubcategories'] ?? [];

    // Handle optional provider CV/Portfolio file upload (for credentials, not main profile image)
    // This file's path won't go into users.portfolio, but could go into a separate table or users.credentials_file_path
    $credentials_file_path = NULL; // Assuming a new column or a different table for this.
    if (isset($_FILES['providerCV']) && $_FILES['providerCV']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../public/uploads/credentials/'; // Create this folder: public/uploads/credentials
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        $file_tmp = $_FILES['providerCV']['tmp_name'];
        $file_ext = strtolower(pathinfo($_FILES['providerCV']['name'], PATHINFO_EXTENSION));
        $allowed_ext = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'];

        if (in_array($file_ext, $allowed_ext)) {
            $new_file_name = 'provider_cv_' . uniqid() . '.' . $file_ext;
            $destination_path = $upload_dir . $new_file_name;
            if (move_uploaded_file($file_tmp, $destination_path)) {
                $credentials_file_path = 'uploads/credentials/' . $new_file_name; // Path relative to public/ for DB
            } else {
                set_flash_message('error', 'Failed to upload credentials file.');
                header('Location: ../public/signup.php');
                exit();
            }
        } else {
            set_flash_message('error', 'Invalid credentials file type. Only PDF, DOC, DOCX, JPG, JPEG, PNG allowed.');
            header('Location: ../public/signup.php');
            exit();
        }
    }
    // If provider is also submitting a portfolio *text/URL*
    $portfolio_text = $_POST['providerPortfolioLink'] ?? ''; // Assuming you add a field for this

} else { // customer
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $phone = $_POST['customerPhone'] ?? '';
    $address = $_POST['customerAddress'] ?? '';
}

// --- Validation ---
if (empty($name) || empty($email) || empty($password) || empty($confirm_password)) {
    set_flash_message('error', 'Please fill in all required fields.');
    header('Location: ../public/signup.php');
    exit();
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    set_flash_message('error', 'Invalid email format.');
    header('Location: ../public/signup.php');
    exit();
}

if (strlen($password) < 8) {
    set_flash_message('error', 'Password must be at least 8 characters long.');
    header('Location: ../public/signup.php');
    exit();
}

if ($password !== $confirm_password) {
    set_flash_message('error', 'Passwords do not match.');
    header('Location: ../public/signup.php');
    exit();
}

// Specific validation for provider fields
if ($userType === 'provider') {
    if (empty($main_services_array)) {
        set_flash_message('error', 'Please select at least one main service.');
        header('Location: ../public/signup.php');
        exit();
    }
    if (!preg_match('/^[0-9]{10}$/', $phone)) { // Assuming 10 digits for providers
        set_flash_message('error', 'Provider phone number must be exactly 10 digits.');
        header('Location: ../public/signup.php');
        exit();
    }
}


// --- Registration Logic ---
$database = new Database();
$conn = $database->getConnection(); // $conn is now the PDO object
$userClass = new User($conn); // Renamed $user to $userClass to avoid conflict with $user variable after login

try {
    $conn->beginTransaction(); // Start a transaction

    $registeredUser = $userClass->register(
        $name, $email, $password, $userType, 
        $phone, $address, $bio, 
        $portfolio_text, // This is for users.portfolio (text/URL)
        $profile_image_path // This is for users.profile_image_path (default or uploaded)
    );

    if (is_string($registeredUser)) { // It's an error message
        $conn->rollBack();
        set_flash_message('error', $registeredUser);
        header('Location: ../public/signup.php');
        exit();
    } elseif ($registeredUser === false) { // Database error
        $conn->rollBack();
        set_flash_message('error', 'Registration failed due to a database error. Please try again.');
        header('Location: ../public/signup.php');
        exit();
    }

    // If registration was successful, $registeredUser now contains the new user's data (including ID)
    $newUserId = $registeredUser['id'];

    // If provider, insert service details into the 'service' table
    if ($userType === 'provider') {
        $main_services_str = implode(',', $main_services_array);
        $subcategories_str = implode(',', $subcategories_array);

        $serviceClass = new Service($conn); // Assuming a Service class exists
        $serviceResult = $serviceClass->create(
            $newUserId,
            $name,
            $email,
            $main_services_str,
            $subcategories_str,
            $phone,
            $address,
            $portfolio_text, // This is for the service.portfolio (text/URL)
            $bio
        );

        if (!$serviceResult) {
            $conn->rollBack();
            set_flash_message('error', 'Registration successful, but failed to record provider services. Please contact support.');
            header('Location: ../public/signup.php');
            exit();
        }
    }
    
    // Clear signup data and OTP from session after successful registration
    unset($_SESSION['signup_data']);
    unset($_SESSION['signup_otp']);

    $conn->commit(); // Commit the transaction
    set_flash_message('success', 'Registration successful! You can now log in.');
    header('Location: ../public/login.php');
    exit();

} catch (PDOException $e) {
    $conn->rollBack(); // Rollback on any PDO exception
    error_log("Signup PDO Exception: " . $e->getMessage());
    set_flash_message('error', 'A system error occurred during registration. Please try again later.');
    header('Location: ../public/signup.php');
    exit();
}

// Catch-all for any other unexpected errors
catch (Exception $e) {
    $conn->rollBack();
    error_log("Signup General Exception: " . $e->getMessage());
    set_flash_message('error', 'An unexpected error occurred during registration. Please try again later.');
    header('Location: ../public/signup.php');
    exit();
}