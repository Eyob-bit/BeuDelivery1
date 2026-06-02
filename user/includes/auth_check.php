<?php
// auth_check.php - User authentication check

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    // Store the current URL to redirect back after login
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
    
    // Redirect to login page
    header("Location: ../login.php");
    exit();
}

// Check if user is a customer (not merchant or admin accessing user pages)
$user_type = $_SESSION['user_type'] ?? '';
if ($user_type === 'merchant' && basename($_SERVER['PHP_SELF']) !== 'home.php') {
    // If merchant tries to access user pages, redirect to merchant dashboard
    header("Location: ../merchant/merchant_dashboard.php");
    exit();
}

// Optional: Check if user account is verified
if (isset($_SESSION['is_verified']) && $_SESSION['is_verified'] === false) {
    // Redirect to verification page
    header("Location: verify_account.php");
    exit();
}

// Optional: Update last activity for session timeout
$_SESSION['LAST_ACTIVITY'] = time();
?>