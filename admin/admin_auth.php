<?php
// admin_auth.php - ADMIN AUTHENTICATION CHECK
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    header("Location: ../login.php");
    exit();
}

// Check if user has admin role
if (!isset($_SESSION['user_roles']) || !is_array($_SESSION['user_roles']) || !in_array('admin', $_SESSION['user_roles'])) {
    // Not an admin
    header("Location: ../index.php");
    exit();
}

// Include database connection
include "../includes/db.php";

// Fetch admin user details from database
$admin_id = $_SESSION['user_id'];
$admin_sql = "SELECT u.* FROM users u WHERE u.id = '$admin_id' LIMIT 1";
$admin_result = mysqli_query($conn, $admin_sql);

if (mysqli_num_rows($admin_result) == 0) {
    // User doesn't exist
    session_destroy();
    header("Location: ../login.php");
    exit();
}

$admin_user = mysqli_fetch_assoc($admin_result);
?>