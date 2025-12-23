<?php
session_start();
require_once "../includes/db.php";

$user_id = $_SESSION['user_id'];
$cart = $_SESSION['cart'] ?? [];

if (!$cart) {
    header("Location: cart.php");
    exit;
}

mysqli_query($conn, "
    INSERT INTO orders (user_id, status)
    VALUES ($user_id, 'pending')
");

$order_id = mysqli_insert_id($conn);

foreach ($cart as $item_id => $qty) {
    mysqli_query($conn, "
        INSERT INTO order_items (order_id, menu_item_id, quantity)
        VALUES ($order_id, $item_id, $qty)
    ");
}

unset($_SESSION['cart']);
header("Location: orders.php");
exit;
