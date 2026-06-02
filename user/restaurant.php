<?php
session_start();
include "../includes/db.php";
include "../auth/auth_check.php";

$merchant_id = $_GET['id'] ?? 0;
if (!$merchant_id) {
    header("Location: home.php");
    exit();
}

// Get merchant details
$merchant_sql = "SELECT m.*, md.*, ds.* 
                 FROM merchants m
                 LEFT JOIN merchant_details md ON m.merchant_id = md.merchant_id
                 LEFT JOIN delivery_settings ds ON m.merchant_id = ds.merchant_id
                 WHERE m.merchant_id = ? AND m.status = 'active'";
$stmt = mysqli_prepare($conn, $merchant_sql);
mysqli_stmt_bind_param($stmt, "i", $merchant_id);
mysqli_stmt_execute($stmt);
$merchant_result = mysqli_stmt_get_result($stmt);
$merchant = mysqli_fetch_assoc($merchant_result);

if (!$merchant) {
    header("Location: home.php");
    exit();
}

// Get menu categories and items
$categories_sql = "SELECT mc.* 
                   FROM menu_categories mc 
                   WHERE mc.merchant_id = ? AND mc.is_active = 1 
                   ORDER BY mc.display_order";
$stmt = mysqli_prepare($conn, $categories_sql);
mysqli_stmt_bind_param($stmt, "i", $merchant_id);
mysqli_stmt_execute($stmt);
$categories_result = mysqli_stmt_get_result($stmt);

// Get all menu items
$items_sql = "SELECT mi.*, mc.category_name 
              FROM menu_items mi
              LEFT JOIN menu_categories mc ON mi.category_id = mc.category_id
              WHERE mi.merchant_id = ? AND mi.is_available = 1 
              ORDER BY mc.display_order, mi.display_order";
$stmt = mysqli_prepare($conn, $items_sql);
mysqli_stmt_bind_param($stmt, "i", $merchant_id);
mysqli_stmt_execute($stmt);
$items_result = mysqli_stmt_get_result($stmt);

// Group items by category
$menu_by_category = [];
while ($item = mysqli_fetch_assoc($items_result)) {
    $category = $item['category_name'] ?? 'Uncategorized';
    if (!isset($menu_by_category[$category])) {
        $menu_by_category[$category] = [];
    }
    $menu_by_category[$category][] = $item;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($merchant['store_name']); ?> - BeU Delivery</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        .restaurant-header {
            background: linear-gradient(rgba(0,0,0,0.5), rgba(0,0,0,0.5)), 
                        url('../uploads/merchants/featured/<?php echo $merchant['featured_image'] ?? ''; ?>');
            background-size: cover;
            background-position: center;
            color: white;
            padding: 60px 0;
            margin-bottom: 30px;
        }
        
        .menu-category {
            margin-bottom: 40px;
        }
        
        .menu-item-card {
            border: 1px solid #e0e0e0;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 15px;
            transition: all 0.3s;
        }
        
        .menu-item-card:hover {
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transform: translateY(-2px);
        }
        
        .item-image {
            width: 100px;
            height: 100px;
            border-radius: 8px;
            object-fit: cover;
        }
        
        .add-to-cart-btn {
            background: #00B26A;
            color: white;
            border: none;
            padding: 8px 20px;
            border-radius: 5px;
            cursor: pointer;
            transition: background 0.3s;
        }
        
        .add-to-cart-btn:hover {
            background: #009956;
        }
        
        .sticky-info {
            position: sticky;
            top: 20px;
        }
    </style>
</head>
<body>
    
    <!-- Restaurant Header -->
    <div class="restaurant-header">
        <div class="container">
            <h1 class="display-5 fw-bold"><?php echo htmlspecialchars($merchant['store_name']); ?></h1>
            <p class="lead">
                <?php 
                $cuisines = json_decode($merchant['cuisine_types'] ?? '[]', true);
                if (is_array($cuisines) && isset($cuisines[0])) {
                    $cuisines = json_decode($cuisines[0], true);
                    echo htmlspecialchars(implode(', ', $cuisines));
                }
                ?>
            </p>
            <div class="d-flex gap-4">
                <span><i class="bi bi-clock"></i> <?php echo $merchant['estimated_delivery_time'] ?? '30-45'; ?> min</span>
                <span><i class="bi bi-truck"></i> $<?php echo number_format($merchant['delivery_fee'] ?? 2.99, 2); ?> delivery</span>
                <span><i class="bi bi-star-fill"></i> 4.5 (500+)</span>
            </div>
        </div>
    </div>
    
    <div class="container">
        <div class="row">
            <!-- Menu Section -->
            <div class="col-lg-8">
                <?php foreach ($menu_by_category as $category_name => $items): ?>
                <div class="menu-category">
                    <h3 class="mb-4"><?php echo htmlspecialchars($category_name); ?></h3>
                    
                    <?php foreach ($items as $item): ?>
                    <div class="menu-item-card">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <h5 class="mb-1"><?php echo htmlspecialchars($item['name']); ?></h5>
                                <p class="text-muted mb-2"><?php echo htmlspecialchars($item['description'] ?? ''); ?></p>
                                <h5 class="text-primary mb-0">$<?php echo number_format($item['price'], 2); ?></h5>
                            </div>
                            <div class="col-md-4 text-end">
                                <?php if (!empty($item['image'])): ?>
                                <img src="../uploads/menu_items/<?php echo $item['image']; ?>" 
                                     alt="<?php echo htmlspecialchars($item['name']); ?>" 
                                     class="item-image mb-2">
                                <?php endif; ?>
                                <button class="add-to-cart-btn" 
                                        onclick="addToCart(<?php echo $item['id']; ?>, '<?php echo addslashes($item['name']); ?>', <?php echo $item['price']; ?>)">
                                    <i class="bi bi-plus-lg me-1"></i> Add
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Restaurant Info Sidebar -->
            <div class="col-lg-4">
                <div class="sticky-info">
                    <div class="card mb-4">
                        <div class="card-body">
                            <h5 class="card-title">Restaurant Info</h5>
                            <p><i class="bi bi-geo-alt me-2"></i> <?php echo htmlspecialchars($merchant['store_address']); ?></p>
                            <p><i class="bi bi-telephone me-2"></i> <?php echo htmlspecialchars($merchant['store_phone'] ?? $merchant['mobile_phone']); ?></p>
                            
                            <h6 class="mt-4">Store Hours</h6>
                            <?php
                            $hours_sql = "SELECT * FROM store_hours WHERE merchant_id = ? ORDER BY day_of_week";
                            $stmt = mysqli_prepare($conn, $hours_sql);
                            mysqli_stmt_bind_param($stmt, "i", $merchant_id);
                            mysqli_stmt_execute($stmt);
                            $hours_result = mysqli_stmt_get_result($stmt);
                            
                            $days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
                            while ($hour = mysqli_fetch_assoc($hours_result)):
                            ?>
                            <div class="d-flex justify-content-between">
                                <span><?php echo $days[$hour['day_of_week']]; ?></span>
                                <span>
                                    <?php if ($hour['is_closed']): ?>
                                        Closed
                                    <?php else: ?>
                                        <?php echo date('g:i A', strtotime($hour['open_time'])); ?> - 
                                        <?php echo date('g:i A', strtotime($hour['close_time'])); ?>
                                    <?php endif; ?>
                                </span>
                            </div>
                            <?php endwhile; ?>
                        </div>
                    </div>
                    
                    <!-- Order Summary Card -->
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Your Order</h5>
                            <div id="orderSummary">
                                <p class="text-muted">Add items to see your order</p>
                            </div>
                            <button class="btn btn-primary w-100 mt-3" onclick="viewCart()" id="viewCartBtn" disabled>
                                View Cart (<span id="cartItemCount">0</span>)
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    let cart = JSON.parse(localStorage.getItem('cart_' + <?php echo $merchant_id; ?>)) || {};
    
    function addToCart(itemId, itemName, itemPrice) {
        if (!cart[itemId]) {
            cart[itemId] = {
                name: itemName,
                price: itemPrice,
                quantity: 0
            };
        }
        cart[itemId].quantity++;
        
        // Save to localStorage
        localStorage.setItem('cart_' + <?php echo $merchant_id; ?>, JSON.stringify(cart));
        
        // Update UI
        updateOrderSummary();
        
        // Also add to session cart via AJAX
        fetch('ajax_add_to_cart.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'id=' + itemId
        });
        
        // Show success message
        showNotification(itemName + ' added to cart!', 'success');
    }
    
    function updateOrderSummary() {
        const orderSummary = document.getElementById('orderSummary');
        const viewCartBtn = document.getElementById('viewCartBtn');
        const cartItemCount = document.getElementById('cartItemCount');
        
        let totalItems = 0;
        let totalPrice = 0;
        let html = '';
        
        for (const itemId in cart) {
            const item = cart[itemId];
            totalItems += item.quantity;
            totalPrice += item.price * item.quantity;
            
            html += `
                <div class="d-flex justify-content-between mb-2">
                    <span>${item.name} × ${item.quantity}</span>
                    <span>$${(item.price * item.quantity).toFixed(2)}</span>
                </div>
            `;
        }
        
        if (totalItems > 0) {
            html += `
                <hr>
                <div class="d-flex justify-content-between fw-bold">
                    <span>Total</span>
                    <span>$${totalPrice.toFixed(2)}</span>
                </div>
            `;
            viewCartBtn.disabled = false;
        } else {
            html = '<p class="text-muted">Add items to see your order</p>';
            viewCartBtn.disabled = true;
        }
        
        orderSummary.innerHTML = html;
        cartItemCount.textContent = totalItems;
    }
    
    function viewCart() {
        // Redirect to cart page or show modal
        window.location.href = 'cart.php?merchant_id=<?php echo $merchant_id; ?>';
    }
    
    function showNotification(message, type) {
        // Create notification element
        const notification = document.createElement('div');
        notification.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
        notification.style.top = '20px';
        notification.style.right = '20px';
        notification.style.zIndex = '9999';
        notification.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        document.body.appendChild(notification);
        
        // Auto remove after 3 seconds
        setTimeout(() => {
            notification.remove();
        }, 3000);
    }
    
    // Initialize on page load
    updateOrderSummary();
    </script>
</body>
</html>