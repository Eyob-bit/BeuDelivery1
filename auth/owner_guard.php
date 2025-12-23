<?php
include "../includes/db.php";

$user_id = $_SESSION['user_id'];

$res = mysqli_query($conn, "
    SELECT id FROM restaurants WHERE owner_id='$user_id'
");

if (mysqli_num_rows($res) == 0) {
    die("Access denied: Not a restaurant owner");
}

$restaurant = mysqli_fetch_assoc($res);
$restaurant_id = $restaurant['id'];
