<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
include "../includes/db.php";

if (!isset($_SESSION['user_id'])) {
    die("Not logged in");
}

$user_id = $_SESSION['user_id'];
$name = trim($_POST['name'] ?? '');
$desc = trim($_POST['description'] ?? '');

if ($name === '') {
    die("Restaurant name required");
}

/* 1️⃣ INSERT RESTAURANT */
$insertRestaurant = mysqli_query($conn, "
    INSERT INTO restaurants (owner_id, name, description)
    VALUES ('$user_id', '$name', '$desc')
");

if (!$insertRestaurant) {
    die("RESTAURANT INSERT FAILED: " . mysqli_error($conn));
}

/* 2️⃣ GET restaurant_owner ROLE ID */
$roleRes = mysqli_query($conn, "
    SELECT id FROM roles WHERE name = 'restaurant_owner'
");

if (!$roleRes) {
    die("ROLE QUERY FAILED: " . mysqli_error($conn));
}

if (mysqli_num_rows($roleRes) == 0) {
    die("restaurant_owner role does not exist in roles table");
}

$roleRow = mysqli_fetch_assoc($roleRes);
$role_id = $roleRow['id'];

/* 3️⃣ CHECK IF USER ALREADY HAS ROLE */
$checkRole = mysqli_query($conn, "
    SELECT * FROM user_roles
    WHERE user_id = '$user_id' AND role_id = '$role_id'
");

if (!$checkRole) {
    die("ROLE CHECK FAILED: " . mysqli_error($conn));
}

/* 4️⃣ ASSIGN ROLE IF NOT EXISTS */
if (mysqli_num_rows($checkRole) == 0) {

    $assignRole = mysqli_query($conn, "
        INSERT INTO user_roles (user_id, role_id)
        VALUES ('$user_id', '$role_id')
    ");

    if (!$assignRole) {
        die("ROLE INSERT FAILED: " . mysqli_error($conn));
    }
}

/* 5️⃣ REDIRECT */
header("Location: ../restaurant/dashboard.php");
exit;
