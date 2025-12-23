<?php
include "../auth/auth_check.php";
include "../auth/admin_guard.php";
include "../includes/db.php";

$res = mysqli_query($conn, "
    SELECT restaurants.name, SUM(orders.total) AS revenue
    FROM orders
    JOIN restaurants ON orders.restaurant_id = restaurants.id
    GROUP BY restaurants.id
");
?>

<h2>💰 Revenue Report</h2>

<?php while ($r = mysqli_fetch_assoc($res)): ?>
    <div>
        <?= $r['name'] ?> → <b><?= $r['revenue'] ?> ETB</b>
    </div>
<?php endwhile; ?>
