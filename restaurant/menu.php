<?php
session_start();
include "../includes/db.php";

$user_id = $_SESSION['user_id'];
$menu_id = $_GET['id'];

$check = mysqli_query($conn, "
    SELECT menu_items.*, restaurants.owner_id
    FROM menu_items
    JOIN restaurants ON menu_items.restaurant_id = restaurants.id
    WHERE menu_items.id='$menu_id'
");

$menu = mysqli_fetch_assoc($check);

if (!$menu || $menu['owner_id'] != $user_id) {
    die("Access denied");
}
?>

<body>
    <h2>Menu Management</h2>

<form action="add_menu_item.php" method="POST">
    <input type="hidden" name="restaurant_id" value="<?= $restaurant_id ?>">
    <input type="text" name="name" placeholder="Food name" required>
    <input type="number" step="0.01" name="price" placeholder="Price" required>
    <button>Add Item</button>
</form>

<hr>
    <div id="cartPopup" style="
    position: fixed;
    bottom: -100px;
    left: 0;
    width: 100%;
    background: #000;
    color: #fff;
    padding: 15px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    transition: bottom 0.3s;
">
    <span id="cartText">Item added</span>
    <a href="cart.php" style="color:#0f0;">View Cart</a>
</div>

</body>


<?php while ($m = mysqli_fetch_assoc($menu)): ?>
    <div>
        <?= htmlspecialchars($m['name']) ?> - $<?= $m['price'] ?>
        <?= $m['is_available'] ? "🟢" : "🔴" ?>
    </div>
<?php endwhile; ?>
