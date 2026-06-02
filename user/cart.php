<?php
session_start();
include "../includes/db.php";
include "includes/auth_check.php";

$user_id = $_SESSION['user_id'];

// Get cart items from database
$cart_sql = "SELECT 
                ci.cart_id,
                ci.menu_item_id,
                ci.quantity,
                ci.special_instructions,
                mi.name,
                mi.price,
                mi.image,
                m.store_name,
                m.merchant_id,
                (mi.price * ci.quantity) as subtotal
             FROM cart_items ci
             JOIN menu_items mi ON ci.menu_item_id = mi.id
             JOIN merchants m ON mi.merchant_id = m.merchant_id
             WHERE ci.user_id = ?
             ORDER BY ci.created_at DESC";
$stmt = mysqli_prepare($conn, $cart_sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$items = [];
$total = 0;
$stores = [];

while ($item = mysqli_fetch_assoc($result)) {
    $total += $item['subtotal'];
    
    // Group items by store
    if (!isset($stores[$item['merchant_id']])) {
        $stores[$item['merchant_id']] = [
            'store_name' => $item['store_name'],
            'merchant_id' => $item['merchant_id'],
            'items' => [],
            'subtotal' => 0
        ];
    }
    
    $stores[$item['merchant_id']]['items'][] = [
        'id' => $item['menu_item_id'],
        'name' => $item['name'],
        'price' => $item['price'],
        'quantity' => $item['quantity'],
        'subtotal' => $item['subtotal'],
        'image' => $item['image'],
        'special_instructions' => $item['special_instructions']
    ];
    
    $stores[$item['merchant_id']]['subtotal'] += $item['subtotal'];
    
    $items[] = [
        'id' => $item['menu_item_id'],
        'name' => $item['name'],
        'price' => $item['price'],
        'quantity' => $item['quantity'],
        'subtotal' => $item['subtotal'],
        'store_name' => $item['store_name'],
        'merchant_id' => $item['merchant_id'],
        'image' => $item['image'],
        'special_instructions' => $item['special_instructions']
    ];
}

// Calculate delivery fees for all stores
$total_delivery_fee = 0;
foreach ($stores as $merchant_id => $store_data) {
    $delivery_fee = 2.99; // Default delivery fee
    $delivery_sql = "SELECT delivery_fee FROM delivery_settings WHERE merchant_id = ?";
    $stmt = mysqli_prepare($conn, $delivery_sql);
    mysqli_stmt_bind_param($stmt, "i", $merchant_id);
    mysqli_stmt_execute($stmt);
    $delivery_result = mysqli_stmt_get_result($stmt);
    if ($delivery_data = mysqli_fetch_assoc($delivery_result)) {
        $delivery_fee = floatval($delivery_data['delivery_fee']);
    }
    $stores[$merchant_id]['delivery_fee'] = $delivery_fee;
    $total_delivery_fee += $delivery_fee;
}

// Calculate totals
$tax_rate = 0.08;
$tax = $total * $tax_rate;
$grand_total = $total + $total_delivery_fee + $tax;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Cart - BeU Delivery</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        .quantity-control {
            width: 120px;
        }
        
        .summary-card {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
        }
    </style>
</head>
<body>
    <?php include "../partials/navbar.php"; ?>
    
    <div class="container py-5">
        <h1 class="mb-4">🛒 Your Cart</h1>
        
        <?php if (empty($items)): ?>
        <div class="text-center py-5">
            <i class="bi bi-cart-x display-1 text-muted"></i>
            <h3 class="mt-4">Your cart is empty</h3>
            <p class="text-muted">Add some delicious items from our restaurants!</p>
            <a href="home.php" class="btn btn-primary">Browse Restaurants</a>
        </div>
        <?php else: ?>
        
        <div class="row">
            <!-- Cart Items -->
            <div class="col-lg-8">
                <?php foreach ($stores as $merchant_id => $store): ?>
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">
                            <i class="bi bi-shop me-2"></i>
                            <?php echo htmlspecialchars($store['store_name']); ?>
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php foreach ($store['items'] as $item): ?>
                        <div class="row align-items-center mb-3 <?php echo end($store['items']) !== $item ? 'border-bottom pb-3' : ''; ?>">
                            <div class="col-md-8">
                                <h5 class="mb-1"><?php echo htmlspecialchars($item['name']); ?></h5>
                                <?php if (!empty($item['special_instructions'])): ?>
                                <p class="text-muted small mb-1">
                                    <i class="bi bi-chat-left-text me-1"></i>
                                    <?php echo htmlspecialchars($item['special_instructions']); ?>
                                </p>
                                <?php endif; ?>
                                <h5 class="text-primary mb-0">$<?php echo number_format($item['price'], 2); ?></h5>
                            </div>
                            <div class="col-md-4">
                                <div class="d-flex align-items-center justify-content-end">
                                    <div class="input-group quantity-control">
                                        <button class="btn btn-outline-secondary" 
                                                onclick="updateQuantity(<?php echo $item['id']; ?>, 'decrease')">
                                            <i class="bi bi-dash"></i>
                                        </button>
                                        <input type="text" class="form-control text-center" 
                                               value="<?php echo $item['quantity']; ?>" readonly
                                               id="qty-<?php echo $item['id']; ?>">
                                        <button class="btn btn-outline-secondary" 
                                                onclick="updateQuantity(<?php echo $item['id']; ?>, 'increase')">
                                            <i class="bi bi-plus"></i>
                                        </button>
                                    </div>
                                    <button class="btn btn-link text-danger ms-3" 
                                            onclick="removeItem(<?php echo $item['id']; ?>)">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                                <div class="text-end mt-2">
                                    <strong>Subtotal: $<span id="subtotal-<?php echo $item['id']; ?>"><?php echo number_format($item['subtotal'], 2); ?></span></strong>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        
                        <div class="row mt-3">
                            <div class="col-md-8">
                                <strong><?php echo htmlspecialchars($store['store_name']); ?> Subtotal:</strong>
                            </div>
                            <div class="col-md-4 text-end">
                                <strong>$<?php echo number_format($store['subtotal'], 2); ?></strong>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-8">
                                <span>Delivery Fee:</span>
                            </div>
                            <div class="col-md-4 text-end">
                                <span>$<?php echo number_format($store['delivery_fee'], 2); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Order Summary -->
            <div class="col-lg-4">
                <div class="summary-card sticky-top" style="top: 20px;">
                    <h4 class="mb-4">Order Summary</h4>
                    
                    <?php if (count($stores) > 1): ?>
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i>
                        <strong>Multi-Store Order</strong><br>
                        Items from <?php echo count($stores); ?> restaurants
                    </div>
                    <?php endif; ?>
                    
                    <div class="d-flex justify-content-between mb-2">
                        <span>Subtotal</span>
                        <span id="cart-subtotal">$<?php echo number_format($total, 2); ?></span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Total Delivery Fees</span>
                        <span>$<?php echo number_format($total_delivery_fee, 2); ?></span>
                    </div>
                    <div class="d-flex justify-content-between mb-3">
                        <span>Tax (8%)</span>
                        <span id="cart-tax">$<?php echo number_format($tax, 2); ?></span>
                    </div>
                    
                    <hr>
                    
                    <div class="d-flex justify-content-between mb-4">
                        <h4>Total</h4>
                        <h4 id="cart-total">$<?php echo number_format($grand_total, 2); ?></h4>
                    </div>
                    
                    <button class="btn btn-primary btn-lg w-100 mb-3" onclick="checkout()">
                        <i class="bi bi-lock me-2"></i> Proceed to Checkout
                    </button>
                    
                    <a href="home.php" class="btn btn-outline-primary w-100">
                        <i class="bi bi-arrow-left me-2"></i> Continue Shopping
                    </a>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    function updateQuantity(itemId, action) {
        fetch('ajax/ajax_update_cart.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'id=' + itemId + '&action=' + action
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Update quantity display
                const qtyInput = document.getElementById('qty-' + itemId);
                if (qtyInput) {
                    qtyInput.value = data.new_quantity;
                }
                
                // Update item subtotal
                const subtotalSpan = document.getElementById('subtotal-' + itemId);
                if (subtotalSpan && data.new_quantity > 0) {
                    const price = parseFloat(subtotalSpan.textContent.replace('$', '')) / parseInt(qtyInput.value || 1);
                    const newSubtotal = price * data.new_quantity;
                    subtotalSpan.textContent = newSubtotal.toFixed(2);
                }
                
                // Update cart summary
                updateCartSummary();
                
                // Remove item row if quantity is 0
                if (data.new_quantity === 0) {
                    const itemRow = qtyInput.closest('.row');
                    if (itemRow) {
                        itemRow.remove();
                    }
                    showNotification('Item removed from cart', 'info');
                } else {
                    showNotification('Cart updated', 'success');
                }
            } else {
                alert(data.message || 'Failed to update cart');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Network error occurred');
        });
    }
    
    function removeItem(itemId) {
        if (confirm('Remove this item from cart?')) {
            fetch('ajax/ajax_remove_from_cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'id=' + itemId
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Remove item row
                    const qtyInput = document.getElementById('qty-' + itemId);
                    if (qtyInput) {
                        const itemRow = qtyInput.closest('.row');
                        if (itemRow) {
                            itemRow.remove();
                        }
                    }
                    
                    // Update cart summary
                    updateCartSummary();
                    showNotification('Item removed from cart', 'success');
                    
                    // Check if cart is empty and reload if needed
                    setTimeout(() => {
                        const remainingItems = document.querySelectorAll('[id^="qty-"]');
                        if (remainingItems.length === 0) {
                            location.reload();
                        }
                    }, 1000);
                } else {
                    alert(data.message || 'Failed to remove item');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Network error occurred');
            });
        }
    }
    
    function updateCartSummary() {
        fetch('ajax/ajax_get_cart_summary.php')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update subtotal
                    const subtotalElement = document.getElementById('cart-subtotal');
                    if (subtotalElement) {
                        subtotalElement.textContent = '$' + data.cart_total.toFixed(2);
                    }
                    
                    // Calculate and update tax
                    const tax = data.cart_total * 0.08;
                    const taxElement = document.getElementById('cart-tax');
                    if (taxElement) {
                        taxElement.textContent = '$' + tax.toFixed(2);
                    }
                    
                    // Calculate and update total (assuming delivery fees stay the same)
                    const deliveryFees = parseFloat(document.querySelector('.summary-card').textContent.match(/Total Delivery Fees\s*\$(\d+\.\d+)/)?.[1] || '0');
                    const total = data.cart_total + deliveryFees + tax;
                    const totalElement = document.getElementById('cart-total');
                    if (totalElement) {
                        totalElement.textContent = '$' + total.toFixed(2);
                    }
                }
            })
            .catch(error => {
                console.error('Error updating cart summary:', error);
            });
    }
    
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
    
    function checkout() {
        window.location.href = 'checkout.php';
    }
    </script>
</body>
</html>