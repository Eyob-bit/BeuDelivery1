<?php 
include "../partials/navbar.php";
include "../auth/auth_check.php";
include "../includes/db.php";

$user_id = $_SESSION['user_id'];

$orders = mysqli_query($conn, "
    SELECT * FROM orders
    WHERE user_id=$user_id
    ORDER BY created_at DESC
");
?>

<h2>📦 My Orders</h2>

<?php while ($o = mysqli_fetch_assoc($orders)): ?>
<div class="order-card">
  <h4>Order #<?= $o['id'] ?></h4>
  <p>Status: <?= $o['status'] ?></p>
</div>
<?php endwhile; ?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

</head>
<body>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>