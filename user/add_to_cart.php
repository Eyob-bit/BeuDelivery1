<?php
session_start();

$id = $_GET['id'];

$_SESSION['cart'][$id] = ($_SESSION['cart'][$id] ?? 0) + 1;

header("Location: cart.php");
exit;

