<?php
session_start();
include "../includes/db.php";

$user_id = $_SESSION['user_id'];
$restaurant_id = intval($_POST['restaurant_id']);
$name = trim($_POST['name']);
$price = floatval($_POST['price']);

// ownership check
$check = mysqli_query($conn, "
    SELECT * FROM restaurants
    WHERE id='$restaurant_id' AND owner_id='$user_id'
");

if (mysqli_num_rows($check) == 0) {
    die("Unauthorized");
}

mysqli_query($conn, "
    INSERT INTO menu_items (restaurant_id, name, price)
    VALUES ('$restaurant_id', '$name', '$price')
");

header("Location: menu.php?restaurant_id=$restaurant_id");
exit;
