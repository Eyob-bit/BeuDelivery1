<?php
include "../auth/auth_check.php";
include "../auth/admin_guard.php";
include "../includes/db.php";

$res = mysqli_query($conn, "
    SELECT orders.*, users.email, restaurants.name AS restaurant
    FROM orders
    JOIN users ON orders.user_id = users.id
    JOIN restaurants ON orders.restaurant_id = restaurants.id
    ORDER BY orders.created_at DESC
");
?>

<h2>All Orders</h2>

<?php while ($o = mysqli_fetch_assoc($res)): ?>
    <div>
        User: <?= $o['email'] ?><br>
        Restaurant: <?= $o['restaurant'] ?><br>
        Total: <?= $o['total'] ?> ETB<br>
        Status: <?= $o['status'] ?><br>
        <?= $o['created_at'] ?>
    </div>
    <hr>
<?php endwhile; ?>
