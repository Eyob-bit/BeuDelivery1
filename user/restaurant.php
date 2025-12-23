<?php 
session_start();
include "../partials/navbar.php";
include "../auth/auth_check.php";
include "../includes/db.php";

$rid = (int)$_GET['id'];

$res = mysqli_query($conn, "SELECT * FROM restaurants WHERE id=$rid");
$restaurant = mysqli_fetch_assoc($res);

$menu = mysqli_query($conn, "
    SELECT * FROM menu_items WHERE restaurant_id=$rid
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= $restaurant['name'] ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .cart-popup {
    position: fixed;
    bottom: -120px;
    left: 50%;
    transform: translateX(-50%);
    width: 90%;
    max-width: 500px;
    background: #1c1c1c;
    color: #fff;
    padding: 15px 20px;
    border-radius: 16px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.4);
    display: flex;
    justify-content: space-between;
    align-items: center;
    transition: bottom 0.4s cubic-bezier(.25,1.4,.5,1);
    z-index: 9999;
}

.cart-popup.show {
    bottom: 20px;
}

.cart-popup-left {
    font-size: 14px;
    font-weight: 500;
}
    </style>
</head>

<body>

<div class="container mt-4">
    <h2><?= $restaurant['name'] ?></h2>

    <div class="row">
        <?php while ($m = mysqli_fetch_assoc($menu)): ?>
            <div class="col-md-4">
                <div class="card mb-3">
                    <div class="card-body">
                        <h5><?= $m['name'] ?></h5>
                        <p><?= $m['description'] ?></p>
                        <b><?= $m['price'] ?> ETB</b><br><br>

                        <!-- ✅ FIXED BUTTON -->
                        <button
                            type="button"
                            class="btn btn-success"
                            onclick="addToCart(event, <?= $m['id'] ?>)">
                            Add to Cart
                        </button>
                    </div>
                </div>
            </div>
        <?php endwhile; ?>
    </div>
</div>

<!-- ✅ CART POPUP -->
<div id="cartPopup" class="cart-popup">
    <div class="cart-popup-left">
        <span id="cartText">Item added</span>
    </div>
    <div class="cart-popup-right">
        <a href="cart.php" class="btn btn-light btn-sm">View Cart</a>
    </div>
</div>

<script>
let popupTimeout;

function addToCart(e, id) {
    e.preventDefault();

    fetch("ajax_add_to_cart.php", {
        method: "POST",
        headers: {
            "Content-Type": "application/x-www-form-urlencoded"
        },
        body: "id=" + id
    })
    .then(res => res.json())
    .then(data => {
        showCartPopup(data.name, data.count);
    });
}

function showCartPopup(name, count) {
    const popup = document.getElementById("cartPopup");
    const text = document.getElementById("cartText");

    text.innerText = `${name} added • ${count} item(s) in cart`;

    popup.classList.add("show");

    // Reset hide timer if user keeps adding
    clearTimeout(popupTimeout);
    popupTimeout = setTimeout(() => {
        popup.classList.remove("show");
    }, 3500);
}
</script>


</body>
</html>
