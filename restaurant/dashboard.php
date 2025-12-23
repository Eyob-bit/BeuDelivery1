<?php
session_start();
include "../includes/db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

$restaurants = mysqli_query($conn, "
    SELECT * FROM restaurants
    WHERE owner_id = '$user_id'
");
?>

<h1>Restaurant Dashboard</h1>

<a href="add_restaurant.php">➕ Add Restaurant</a>

<hr>

<?php if (mysqli_num_rows($restaurants) == 0): ?>
    <p>You have no restaurants yet.</p>
<?php endif; ?>

<?php while ($r = mysqli_fetch_assoc($restaurants)): ?>
    <div style="border:1px solid #ccc; padding:10px; margin-bottom:10px;">
        <h3><?= htmlspecialchars($r['name']) ?></h3>
        <p><?= htmlspecialchars($r['description']) ?></p>
        <p>Status:
            <?= $r['is_approved'] ? "✅ Approved" : "⏳ Pending approval" ?>
        </p>

        <a href="menu.php?restaurant_id=<?= $r['id'] ?>">
            Manage Menu
        </a>
    </div>
<?php endwhile; ?>
