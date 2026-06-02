<?php
// logout.php
// Start session
session_start();

// Store some session data for redirect messages
$user_email = $_SESSION['user_email'] ?? '';
$user_type = $_SESSION['user_type'] ?? '';

// Destroy all session data
session_unset(); // Remove all session variables
session_destroy(); // Destroy the session
session_write_close(); // Write session data and end session

// Also destroy the session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Determine redirect URL based on user type
$redirect_url = "../index.php";

// Set logout message in URL
$message = urlencode("You have been logged out successfully.");
$redirect_url .= "?logout=success&message=" . $message;

// Redirect to appropriate page
header("Location: " . $redirect_url);
exit();
?>