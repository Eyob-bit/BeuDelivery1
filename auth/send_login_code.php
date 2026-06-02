<?php
session_start();
include "../includes/db.php";

// Get email from session
$email = $_SESSION['login_email'] ?? '';

if (empty($email)) {
    header("Location: login.php");
    exit();
}

// Generate 6-digit code
$code = sprintf("%06d", mt_rand(0, 999999));

// Store in database with UTC time
$expires_utc = gmdate("Y-m-d H:i:s", strtotime("+10 minutes"));

// Clear any existing codes
mysqli_query($conn, "DELETE FROM email_verifications WHERE email='$email'");

// Insert new code
$sql = "INSERT INTO email_verifications (email, code, expires_at) VALUES ('$email', '$code', '$expires_utc')";
mysqli_query($conn, $sql);

// Store in session
$_SESSION['login_verification_code'] = $code;
$_SESSION['code_generated_at'] = time();
$_SESSION['resend_allowed_after'] = time() + 60;

// Redirect to verification page
header("Location: verify_login.php");
exit();
?>