<?php
include "../auth/auth_check.php";
include "../auth/admin_guard.php";
include "../includes/db.php";
?>

<h2>👑 Admin Dashboard</h2>

<ul>
    <li><a href="restaurants.php">🍽 Restaurants</a></li>
    <li><a href="orders.php">📦 Orders</a></li>
    <li><a href="revenue.php">💰 Revenue</a></li>
</ul>
