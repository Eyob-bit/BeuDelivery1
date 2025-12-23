<?php
session_start();
include "../includes/db.php";

$user_id = $_SESSION['user_id'] ?? null;

// get owner restaurant
$ownRes = mysqli_query($conn, "
    SELECT id FROM restaurants WHERE owner_id='$user_id'
");
$ownRestaurant = mysqli_fetch_assoc($ownRes);
$own_restaurant_id = $ownRestaurant['id'] ?? null;

// get all menus
$menus = mysqli_query($conn, "
    SELECT menu_items.*, restaurants.owner_id
    FROM menu_items
    JOIN restaurants ON menu_items.restaurant_id = restaurants.id
");
?>

<h2>Menus</h2>

<?php while ($m = mysqli_fetch_assoc($menus)): ?>
    <div>
        <b><?= $m['name'] ?></b> - <?= $m['price'] ?> ETB

        <?php if ($m['owner_id'] == $user_id): ?>
            <!-- ONLY OWNER CAN MANAGE -->
            <a href="edit_menu.php?id=<?= $m['id'] ?>">✏️ Edit</a>
            <a href="delete_menu.php?id=<?= $m['id'] ?>">🗑 Delete</a>
        <?php endif; ?>
    </div>
<?php endwhile; ?>
