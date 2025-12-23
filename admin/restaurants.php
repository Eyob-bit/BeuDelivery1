<?php
include "../auth/auth_check.php";
include "../auth/admin_guard.php";
include "../includes/db.php";

$res = mysqli_query($conn, "
    SELECT restaurants.*, users.email
    FROM restaurants
    JOIN users ON restaurants.owner_id = users.id
");
?>

<h2>All Restaurants</h2>

<?php while ($r = mysqli_fetch_assoc($res)): ?>
    <div>
        <b><?= $r['name'] ?></b><br>
        Owner: <?= $r['email'] ?><br>
        <?= $r['description'] ?>
    </div>
    <hr>
<?php endwhile; ?>
