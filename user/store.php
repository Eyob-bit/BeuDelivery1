<?php
session_start();
include "../includes/db.php";
include "../auth/auth_check.php";

$merchant_id = $_GET['id'] ?? 0;
if (!$merchant_id) {
    header("Location: home.php");
    exit();
}

// Get merchant/store details with store image
$store_sql = "SELECT 
                m.*, 
                md.store_phone,
                md.cuisine_types,
                md.pickup_instructions,
                md.store_hours,
                ds.delivery_fee,
                ds.min_order_amount,
                ds.free_delivery_threshold,
                ds.estimated_delivery_time,
                ds.is_delivery_available,
                ds.is_pickup_available,
                (SELECT si.image_path FROM store_images si WHERE si.merchant_id = m.merchant_id ORDER BY si.display_order LIMIT 1) as store_image_path
              FROM merchants m
              LEFT JOIN merchant_details md ON m.merchant_id = md.merchant_id
              LEFT JOIN delivery_settings ds ON m.merchant_id = ds.merchant_id
              WHERE m.merchant_id = ? AND m.status = 'active'";
$stmt = mysqli_prepare($conn, $store_sql);
mysqli_stmt_bind_param($stmt, "i", $merchant_id);
mysqli_stmt_execute($stmt);
$store_result = mysqli_stmt_get_result($stmt);
$store = mysqli_fetch_assoc($store_result);

if (!$store) {
    header("Location: home.php");
    exit();
}

// Get menu categories
$categories_sql = "SELECT * FROM menu_categories 
                   WHERE merchant_id = ? AND is_active = 1 
                   ORDER BY display_order";
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

// Get cart count for this store
$cart = $_SESSION['cart'] ?? [];
$store_cart_items = [];
$store_cart_total = 0;

foreach ($cart as $item_id => $quantity) {
    // Check if item belongs to this store
    $check_sql = "SELECT merchant_id, price FROM menu_items WHERE id = ?";
    $stmt = mysqli_prepare($conn, $check_sql);
    mysqli_stmt_bind_param($stmt, "i", $item_id);
    mysqli_stmt_execute($stmt);
    $check_result = mysqli_stmt_get_result($stmt);
    $item_check = mysqli_fetch_assoc($check_result);
    
    if ($item_check && $item_check['merchant_id'] == $merchant_id) {
        $store_cart_items[$item_id] = $quantity;
        $store_cart_total += $item_check['price'] * $quantity;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($store['store_name']); ?> - BeU Delivery</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        .store-header {
            position: relative;
            height: 300px;
            background: linear-gradient(rgba(0,0,0,0.5), rgba(0,0,0,0.5))<?php 
            if (!empty($store['store_image_path'])) {
                echo ', url(\'../' . $store['store_image_path'] . '\')';
            } elseif (!empty($store['featured_image'])) {
                echo ', url(\'../uploads/merchants/featured/' . $store['featured_image'] . '\')';
            } else {
                echo ', url(\'../public/images/store-default.jpg\')';
            }
            ?>;
            background-size: cover;
            background-position: center;
            color: white;
            display: flex;
            align-items: flex-end;
            padding: 30px 0;
            margin-bottom: 30px;
        }
        
        .menu-item-card {
            border: 1px solid #e0e0e0;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 15px;
            transition: all 0.3s;
            background: white;
        }
        
        .menu-item-card:hover {
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transform: translateY(-2px);
        }
        
        .add-to-cart-btn {
            background: #00B26A;
            color: white;
            border: none;
            padding: 8px 20px;
            border-radius: 5px;
            cursor: pointer;
            transition: background 0.3s;
            font-size: 14px;
        }
        
        .add-to-cart-btn:hover {
            background: #009956;
        }
        
        .cart-summary {
            position: sticky;
            top: 20px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            padding: 20px;
        }
        
        .cart-summary-item {
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }
        
        .store-info-box {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        @media (max-width: 768px) {
            .store-header {
                height: 200px;
                padding: 20px 0;
            }
        }
    </style>
</head>
<body>
    <?php include "../partials/navbar.php"; ?>
    
    <!-- Store Header -->
    <div class="store-header">
        <div class="container">
            <h1 class="display-5 fw-bold mb-2"><?php echo htmlspecialchars($store['store_name']); ?></h1>
            
            <?php 
            // Parse cuisine types
            $cuisines = [];
            if (!empty($store['cuisine_types'])) {
                $cuisines = json_decode($store['cuisine_types'], true);
                if (is_array($cuisines) && isset($cuisines[0])) {
                    $cuisines = json_decode($cuisines[0], true);
                }
            }
            if (!empty($cuisines) && is_array($cuisines)): ?>
            <p class="lead mb-3">
                <i class="bi bi-tag me-1"></i>
                <?php echo htmlspecialchars(implode(', ', $cuisines)); ?>
            </p>
            <?php endif; ?>
            
            <div class="d-flex flex-wrap gap-3">
                <?php if ($store['is_delivery_available']): ?>
                <span class="badge bg-success fs-6">
                    <i class="bi bi-truck me-1"></i> Delivery: $<?php echo number_format($store['delivery_fee'] ?? 2.99, 2); ?>
                </span>
                <?php endif; ?>
                
                <?php if ($store['is_pickup_available']): ?>
                <span class="badge bg-info fs-6">
                    <i class="bi bi-bag me-1"></i> Pickup Available
                </span>
                <?php endif; ?>
                
                <span class="badge bg-secondary fs-6">
                    <i class="bi bi-clock me-1"></i> <?php echo $store['estimated_delivery_time'] ?? '30-45'; ?> min
                </span>
                
                <?php if ($store['min_order_amount'] > 0): ?>
                <span class="badge bg-warning fs-6">
                    <i class="bi bi-currency-dollar me-1"></i> Min: $<?php echo number_format($store['min_order_amount'], 2); ?>
                </span>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="container">
        <div class="row">
            <!-- Menu Section -->
            <div class="col-lg-8">
                <?php if (empty($menu_by_category)): ?>
                <div class="text-center py-5">
                    <i class="bi bi-menu-button-wide display-1 text-muted"></i>
                    <h3 class="mt-4">Menu Coming Soon</h3>
                    <p class="text-muted">This store is setting up their menu</p>
                </div>
                <?php else: ?>
                    <?php foreach ($menu_by_category as $category_name => $items): ?>
                    <div class="mb-5">
                        <h2 class="mb-4 border-bottom pb-2"><?php echo htmlspecialchars($category_name); ?></h2>
                        
                        <?php foreach ($items as $item): ?>
                        <div class="menu-item-card">
                            <div class="row align-items-center">
                                <div class="col-md-8">
                                    <h5 class="mb-1"><?php echo htmlspecialchars($item['name']); ?></h5>
                                    <p class="text-muted mb-2"><?php echo htmlspecialchars($item['description'] ?? ''); ?></p>
                                    <h5 class="text-primary mb-0">$<?php echo number_format($item['price'], 2); ?></h5>
                                </div>
                                <div class="col-md-4 text-end">
                                    <div class="d-flex align-items-center justify-content-end">
                                        <button class="btn btn-sm btn-outline-secondary me-2" 
                                                onclick="updateCart(<?php echo $item['id']; ?>, 'decrease')">
                                            <i class="bi bi-dash"></i>
                                        </button>
                                        
                                        <span class="mx-2" id="itemQty-<?php echo $item['id']; ?>">
                                            <?php echo $store_cart_items[$item['id']] ?? 0; ?>
                                        </span>
                                        
                                        <button class="btn btn-sm btn-outline-secondary me-2" 
                                                onclick="updateCart(<?php echo $item['id']; ?>, 'increase')">
                                            <i class="bi bi-plus"></i>
                                        </button>
                                        
                                        <button class="add-to-cart-btn" 
                                                onclick="addToCart(<?php echo $item['id']; ?>, '<?php echo addslashes($item['name']); ?>', <?php echo $item['price']; ?>)">
                                            Add
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <!-- Sidebar -->
            <div class="col-lg-4">
                <!-- Store Info -->
                <div class="store-info-box">
                    <h5><i class="bi bi-info-circle me-2"></i> Store Info</h5>
                    <p class="mb-1"><strong>Address:</strong> <?php echo htmlspecialchars($store['store_address']); ?></p>
                    <p class="mb-1"><strong>Phone:</strong> <?php echo htmlspecialchars($store['store_phone'] ?? $store['mobile_phone']); ?></p>
                    
                    <?php if (!empty($store['pickup_instructions'])): ?>
                    <div class="mt-3">
                        <strong><i class="bi bi-box-arrow-in-down me-1"></i> Pickup Instructions:</strong>
                        <p class="mb-0"><?php echo htmlspecialchars($store['pickup_instructions']); ?></p>
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- Cart Summary -->
                <div class="cart-summary">
                    <h5 class="mb-4">Your Order</h5>
                    
                    <div id="orderItems">
                        <?php if (empty($store_cart_items)): ?>
                        <p class="text-muted">Add items to your order</p>
                        <?php else: ?>
                            <?php 
                            $item_count = 0;
                            foreach ($store_cart_items as $item_id => $quantity):
                                $item_sql = "SELECT name, price FROM menu_items WHERE id = ?";
                                $stmt = mysqli_prepare($conn, $item_sql);
                                mysqli_stmt_bind_param($stmt, "i", $item_id);
                                mysqli_stmt_execute($stmt);
                                $item_result = mysqli_stmt_get_result($stmt);
                                $item = mysqli_fetch_assoc($item_result);
                                
                                if ($item):
                                    $item_count += $quantity;
                            ?>
                            <div class="cart-summary-item">
                                <div class="d-flex justify-content-between">
                                    <span><?php echo htmlspecialchars($item['name']); ?> × <?php echo $quantity; ?></span>
                                    <span>$<?php echo number_format($item['price'] * $quantity, 2); ?></span>
                                </div>
                            </div>
                            <?php 
                                endif;
                            endforeach; 
                            ?>
                        <?php endif; ?>
                    </div>
                    
                    <?php if (!empty($store_cart_items)): 
                        $delivery_fee = $store['delivery_fee'] ?? 2.99;
                        $subtotal = $store_cart_total;
                        $tax = $subtotal * 0.08;
                        $total = $subtotal + $delivery_fee + $tax;
                    ?>
                    <div class="mt-3">
                        <div class="d-flex justify-content-between mb-1">
                            <span>Subtotal</span>
                            <span>$<?php echo number_format($subtotal, 2); ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-1">
                            <span>Delivery</span>
                            <span>$<?php echo number_format($delivery_fee, 2); ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-3">
                            <span>Tax (8%)</span>
                            <span>$<?php echo number_format($tax, 2); ?></span>
                        </div>
                        <div class="d-flex justify-content-between fw-bold border-top pt-2">
                            <span>Total</span>
                            <span>$<?php echo number_format($total, 2); ?></span>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <div class="mt-4">
                        <a href="cart.php" class="btn btn-primary w-100 mb-2" 
                           <?php echo empty($store_cart_items) ? 'disabled' : ''; ?>>
                            <i class="bi bi-cart3 me-2"></i> View Cart (<?php echo $item_count ?? 0; ?>)
                        </a>
                        
                        <?php if (!empty($store_cart_items) && $store['min_order_amount'] > 0 && $subtotal < $store['min_order_amount']): ?>
                        <div class="alert alert-warning mt-2">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            Minimum order: $<?php echo number_format($store['min_order_amount'], 2); ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Add item to cart
    function addToCart(itemId, itemName, itemPrice) {
        console.log('Adding to cart:', itemId, itemName, itemPrice);
        
        fetch('ajax/ajax_add_to_cart.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'id=' + itemId + '&action=add&quantity=1'
        })
        .then(response => response.json())
        .then(data => {
            console.log('Add to cart response:', data);
            
            if (data.success) {
                showNotification(itemName + ' added to cart!', 'success');
                // Update quantity display
                updateItemQuantity(itemId);
                // Update cart summary without page reload
                updateCartSummary();
            } else {
                console.error('Add to cart failed:', data.message);
                showNotification(data.message || 'Failed to add item to cart', 'danger');
            }
        })
        .catch(error => {
            console.error('Network error:', error);
            showNotification('Network error occurred. Please try again.', 'danger');
        });
    }
    
    // Update cart via AJAX
    function updateCart(itemId, action) {
        console.log('Updating cart:', itemId, action);
        
        fetch('ajax/ajax_update_cart.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'id=' + itemId + '&action=' + action
        })
        .then(response => response.json())
        .then(data => {
            console.log('Update cart response:', data);
            
            if (data.success) {
                // Update item quantity display
                const qtyElement = document.getElementById('itemQty-' + itemId);
                if (qtyElement) {
                    qtyElement.textContent = data.new_quantity || 0;
                }
                
                // Update cart summary without page reload
                updateCartSummary();
                
                if (data.new_quantity === 0) {
                    showNotification('Item removed from cart', 'info');
                } else {
                    showNotification('Cart updated', 'success');
                }
            } else {
                console.error('Update cart failed:', data.message);
                showNotification(data.message || 'Failed to update cart', 'danger');
            }
        })
        .catch(error => {
            console.error('Update cart error:', error);
            showNotification('Network error occurred while updating cart', 'danger');
        });
    }
    
    // Update item quantity display
    function updateItemQuantity(itemId) {
        fetch('ajax/ajax_get_cart.php')
            .then(response => response.json())
            .then(data => {
                if (data.success && data.items) {
                    const qtyElement = document.getElementById('itemQty-' + itemId);
                    if (qtyElement) {
                        const cartItem = data.items.find(item => item.id == itemId);
                        qtyElement.textContent = cartItem ? cartItem.quantity : 0;
                    }
                }
            })
            .catch(error => {
                console.error('Error updating quantity:', error);
            });
    }
    
    // Update cart summary without page reload
    function updateCartSummary() {
        fetch('ajax/ajax_get_cart_summary.php')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update cart summary section
                    updateCartDisplay(data);
                    
                    // Update view cart button
                    const viewCartBtn = document.querySelector('a[href="cart.php"]');
                    if (viewCartBtn) {
                        viewCartBtn.innerHTML = `<i class="bi bi-cart3 me-2"></i> View Cart (${data.cart_count || 0})`;
                        if (data.cart_count > 0) {
                            viewCartBtn.classList.remove('disabled');
                            viewCartBtn.removeAttribute('disabled');
                        } else {
                            viewCartBtn.classList.add('disabled');
                            viewCartBtn.setAttribute('disabled', 'true');
                        }
                    }
                }
            })
            .catch(error => {
                console.error('Error updating cart summary:', error);
            });
    }
    
    // Update cart display
    function updateCartDisplay(cartData) {
        const orderItemsDiv = document.getElementById('orderItems');
        if (!orderItemsDiv) return;
        
        if (!cartData.items || cartData.items.length === 0) {
            orderItemsDiv.innerHTML = '<p class="text-muted">Add items to your order</p>';
            return;
        }
        
        let html = '';
        cartData.items.forEach(item => {
            html += `
                <div class="cart-summary-item">
                    <div class="d-flex justify-content-between">
                        <span>${item.name} × ${item.quantity}</span>
                        <span>$${item.subtotal.toFixed(2)}</span>
                    </div>
                </div>
            `;
        });
        
        // Add totals
        const deliveryFee = 2.99;
        const tax = cartData.cart_total * 0.08;
        const total = cartData.cart_total + deliveryFee + tax;
        
        html += `
            <div class="mt-3">
                <div class="d-flex justify-content-between mb-1">
                    <span>Subtotal</span>
                    <span>$${cartData.cart_total.toFixed(2)}</span>
                </div>
                <div class="d-flex justify-content-between mb-1">
                    <span>Delivery</span>
                    <span>$${deliveryFee.toFixed(2)}</span>
                </div>
                <div class="d-flex justify-content-between mb-3">
                    <span>Tax (8%)</span>
                    <span>$${tax.toFixed(2)}</span>
                </div>
                <div class="d-flex justify-content-between fw-bold border-top pt-2">
                    <span>Total</span>
                    <span>$${total.toFixed(2)}</span>
                </div>
            </div>
        `;
        
        orderItemsDiv.innerHTML = html;
    }
    
    // Show notification
    function showNotification(message, type) {
        const notification = document.createElement('div');
        notification.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
        notification.style.top = '20px';
        notification.style.right = '20px';
        notification.style.zIndex = '9999';
        notification.style.minWidth = '300px';
        notification.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        document.body.appendChild(notification);
        
        setTimeout(() => {
            if (notification.parentNode) {
                notification.remove();
            }
        }, 3000);
    }
    
    // Load cart summary on page load
    document.addEventListener('DOMContentLoaded', function() {
        updateCartSummary();
    });
    </script>
</body>
</html>