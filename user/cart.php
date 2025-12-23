<?php 
include "../partials/navbar.php";
include "../auth/auth_check.php";
include "../includes/db.php";


$cart = $_SESSION['cart'] ?? [];
$total = 0;
?>

<h2>🛒 Your Cart</h2>

<?php if (!$cart): ?>
    <p>Cart is empty</p>
<?php else: ?>
    <?php foreach ($cart as $item_id => $qty): 
    $item = mysqli_fetch_assoc(
        mysqli_query($conn, "SELECT * FROM menu_items WHERE id=$item_id")
    );
    $subtotal = $item['price'] * $qty;
    $total += $subtotal;
?>
<div style="margin-bottom: 15px;">
    <b><?= $item['name'] ?></b><br>
    <?= $item['price'] ?> ETB × <?= $qty ?> = <?= $subtotal ?> ETB
    <br>

    <a href="update_cart.php?id=<?= $item_id ?>&action=decrease">➖</a>
    <a href="update_cart.php?id=<?= $item_id ?>&action=increase">➕</a>
</div>
        <?= $item['name'] ?> × <?= $qty ?> = <?= $subtotal ?> ETB<br>
    <?php endforeach; ?>

    <hr>
    <b>Total: <?= $total ?> ETB</b><br><br>
    <a href="place_order.php">✅ Place Order</a>
<?php endif; ?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
</head>
<body>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <div class="container mt-4">
  <h2>🛒 Cart</h2>

  <table class="table table-bordered">
    <tr>
      <th>Item</th>
      <th>Qty</th>
      <th>Subtotal</th>
    </tr>

    <?php foreach ($cart as $item_id => $qty): 
    $item = mysqli_fetch_assoc(
        mysqli_query($conn, "SELECT * FROM menu_items WHERE id=$item_id")
    );
    $subtotal = $item['price'] * $qty;
    $total += $subtotal;
?>
<div class="cart-item">
  <h4><?= $item['name'] ?></h4>
  <p><?= $item['price'] ?> ETB × <?= $qty ?></p>
  <strong><?= $subtotal ?> ETB</strong>
</div>
<?php endforeach; ?>

<h3>Total: <?= $total ?> ETB</h3>

<a href="place_order.php" class="btn btn-success">Place Order</a>

</div>

</body>
</html>
