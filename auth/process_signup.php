<?php
session_start();
ob_start();
include "../includes/db.php";

// Check if all required session data exists
if (!isset($_SESSION['signup_email']) || 
    !isset($_SESSION['first_name']) || 
    !isset($_SESSION['last_name'])) {
    header("Location: signup.php");
    exit();
}

// Check if terms were agreed
if (!isset($_POST['agree_terms']) || $_POST['agree_terms'] != '1') {
    header("Location: terms_accept.php");
    exit();
}

// Collect all user data
$email = $_SESSION['signup_email'];
$first_name = $_SESSION['first_name'];
$last_name = $_SESSION['last_name'];
$phone = $_SESSION['phone'] ?? null;
$agreed_to_terms = 1;
$is_verified = 1; // Since they verified via email/code
$password_hash = NULL;
$user_type = "customer";


// Check if email already exists
$check_email = mysqli_query($conn, "SELECT id FROM users WHERE email = '$email'");
if (mysqli_num_rows($check_email) > 0) {
    // Email exists, redirect to login
    header("Location: login.html?error=email_exists");
    exit();
}



$current_time = date('Y-m-d H:i:s');

$insert_query = "INSERT INTO users (`email`, `first_name`, `last_name`, `phone`, `is_verified`, `created_at`, `last_login`, `agreed_to_terms`, 
                 `password_hash`, `user_type`, `updated_at`) 
                 VALUES ('$email', '$first_name', '$last_name', " . ($phone ? "'$phone'" : "NULL") . ", $is_verified, '$current_time', '$current_time', $agreed_to_terms,
                 NULL, '$user_type', '$current_time')";

if (mysqli_query($conn, $insert_query)) {
    // Get the new user ID
    $user_id = mysqli_insert_id($conn);
    
    // Set session variables for logged in user
    $_SESSION['user_id'] = $user_id;
    $_SESSION['user_email'] = $email;
    $_SESSION['user_name'] = $first_name . ' ' . $last_name;
    $_SESSION['logged_in'] = true;
    
    // Clear verification code from database
    mysqli_query($conn, "DELETE FROM email_verifications WHERE email='$email'");
    
    // Clear session signup data
    unset($_SESSION['signup_email']);
    unset($_SESSION['dev_code']);
    unset($_SESSION['first_name']);
    unset($_SESSION['last_name']);
    unset($_SESSION['phone']);
    
    // After setting all session variables, add:
$_SESSION['just_signed_up'] = true;

// Then redirect:
header("Location: success.php");
exit();
} else {
    // Error occurred
    echo "Error: " . mysqli_error($conn);
    // header("Location: signup.php?error=registration_failed");
    // exit();
}
?>