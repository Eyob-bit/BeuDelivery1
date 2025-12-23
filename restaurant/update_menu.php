<?php
session_start();
include "../includes/db.php";

$user_id = $_SESSION['user_id'];

$res = mysqli_query($conn, "
    SELECT id FROM restaurants WHERE owner_id='$user_id'
");

$restaurant = mysqli_fetch_assoc($res);
$restaurant_id = $restaurant['id'];

$name = $_POST['name'];
$desc = $_POST['description'];
$price = $_POST['price'];

mysqli_query($conn, "
    INSERT INTO menu_items (restaurant_id, name, description, price)
    VALUES ('$restaurant_id', '$name', '$desc', '$price')
");

header("Location: dashboard.php");
exit;
