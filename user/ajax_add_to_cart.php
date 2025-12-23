<?php
session_start();
require_once "../includes/db.php";

$id = (int)$_POST['id'];

$_SESSION['cart'][$id] = ($_SESSION['cart'][$id] ?? 0) + 1;

$item = mysqli_fetch_assoc(
    mysqli_query($conn, "SELECT name FROM menu_items WHERE id=$id")
);

echo json_encode([
    'success' => true,
    'name' => $item['name'],
    'count' => array_sum($_SESSION['cart'])
]);
