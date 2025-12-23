<?php
session_start();
include "../includes/db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
?>

<h2>Add Your Restaurant</h2>

<form action="save_restaurant.php" method="POST">
    <input type="text" name="name" placeholder="Restaurant Name" required><br><br>
    <textarea name="description" placeholder="Description"></textarea><br><br>
    <button type="submit">Submit for Approval</button>
</form>
