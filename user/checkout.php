<?php
session_start();
include "../includes/db.php";
include "includes/auth_check.php";

$user_id = $_SESSION['user_id'];

// Get cart items from database
$cart_sql = "SELECT 
                ci.menu_item_id,
                ci.quantity,
                ci.special_instructions,
                mi.name,
                mi.price,
                mi.merchant_id
             FROM cart_items ci
             JOIN menu_items mi ON ci.menu_item_id = mi.id
             WHERE ci.user_id = ?";
$stmt = mysqli_prepare($conn, $cart_sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$cart_result = mysqli_stmt_get_result($stmt);

// Get cart items from database - Updated for multi-store
$cart_sql = "SELECT 
                ci.menu_item_id,
                ci.quantity,
                ci.special_instructions,
                mi.name,
                mi.price,
                mi.merchant_id,
                m.store_name
             FROM cart_items ci
             JOIN menu_items mi ON ci.menu_item_id = mi.id
             JOIN merchants m ON mi.merchant_id = m.merchant_id
             WHERE ci.user_id = ?";
$stmt = mysqli_prepare($conn, $cart_sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$cart_result = mysqli_stmt_get_result($stmt);

$items = [];
$subtotal = 0;
$stores = [];

while ($item = mysqli_fetch_assoc($cart_result)) {
    $item_subtotal = $item['price'] * $item['quantity'];
    $subtotal += $item_subtotal;
    
    // Group by store
    if (!isset($stores[$item['merchant_id']])) {
        $stores[$item['merchant_id']] = [
            'store_name' => $item['store_name'],
            'items' => [],
            'subtotal' => 0
        ];
    }
    
    $stores[$item['merchant_id']]['items'][] = [
        'name' => $item['name'],
        'quantity' => $item['quantity'],
        'price' => $item['price'],
        'subtotal' => $item_subtotal,
        'special_instructions' => $item['special_instructions']
    ];
    
    $stores[$item['merchant_id']]['subtotal'] += $item_subtotal;
    
    $items[] = [
        'name' => $item['name'],
        'quantity' => $item['quantity'],
        'price' => $item['price'],
        'subtotal' => $item_subtotal,
        'store_name' => $item['store_name'],
        'merchant_id' => $item['merchant_id']
    ];
}

// Redirect if cart is empty
if (empty($items)) {
    header("Location: cart.php");
    exit();
}

// Get delivery settings for all stores
$total_delivery_fee = 0;
foreach ($stores as $merchant_id => $store_data) {
    $delivery_sql = "SELECT delivery_fee FROM delivery_settings WHERE merchant_id = ?";
    $stmt = mysqli_prepare($conn, $delivery_sql);
    mysqli_stmt_bind_param($stmt, "i", $merchant_id);
    mysqli_stmt_execute($stmt);
    $delivery_result = mysqli_stmt_get_result($stmt);
    $delivery_data = mysqli_fetch_assoc($delivery_result);
    
    $delivery_fee = floatval($delivery_data['delivery_fee'] ?? 2.99);
    $stores[$merchant_id]['delivery_fee'] = $delivery_fee;
    $total_delivery_fee += $delivery_fee;
}

$tax_rate = 0.08;
$tax = $subtotal * $tax_rate;
$total = $subtotal + $total_delivery_fee + $tax;

// Get user info
$user_sql = "SELECT * FROM users WHERE id = ?";
$stmt = mysqli_prepare($conn, $user_sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$user_result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($user_result);

// Get user addresses
$addresses_sql = "SELECT * FROM user_addresses WHERE user_id = ? ORDER BY is_default DESC, created_at DESC";
$stmt = mysqli_prepare($conn, $addresses_sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$addresses_result = mysqli_stmt_get_result($stmt);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - BeU Delivery</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body>
    <?php include "../partials/navbar.php"; ?>
    
    <div class="container py-5">
        <h1 class="mb-4">Checkout</h1>
        
        <div class="row">
            <!-- Delivery Details -->
            <div class="col-lg-8">
                <form method="POST" id="checkoutForm">
                    <div class="card mb-4">
                        <div class="card-body">
                            <h4 class="card-title mb-4">
                                <i class="bi bi-truck me-2"></i> Delivery Details
                            </h4>
                            
                            <?php if (mysqli_num_rows($addresses_result) > 0): ?>
                            <div class="mb-3">
                                <label class="form-label">Saved Addresses</label>
                                <select name="address_id" id="addressSelect" class="form-select">
                                    <option value="0">Enter new address</option>
                                    <?php while ($addr = mysqli_fetch_assoc($addresses_result)): ?>
                                    <option value="<?php echo $addr['address_id']; ?>" 
                                            data-address="<?php echo htmlspecialchars($addr['address_line1'] . ', ' . $addr['city']); ?>"
                                            data-instructions="<?php echo htmlspecialchars($addr['delivery_instructions']); ?>"
                                            <?php echo $addr['is_default'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($addr['address_type'] . ': ' . $addr['address_line1'] . ', ' . $addr['city']); ?>
                                    </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <?php endif; ?>
                            
                            <div class="mb-3">
                                <label class="form-label">Delivery Address *</label>
                                <textarea name="delivery_address" id="deliveryAddress" class="form-control" rows="3" required></textarea>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Delivery Instructions (Optional)</label>
                                <textarea name="delivery_instructions" id="deliveryInstructions" class="form-control" rows="2" 
                                          placeholder="e.g., Leave at door, call when arriving..."></textarea>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Phone Number *</label>
                                <input type="tel" name="phone" class="form-control" 
                                       value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" required>
                            </div>
                            
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="order_type" id="delivery" value="delivery" checked>
                                <label class="form-check-label" for="delivery">
                                    <i class="bi bi-truck me-1"></i> Delivery
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="order_type" id="pickup" value="pickup">
                                <label class="form-check-label" for="pickup">
                                    <i class="bi bi-bag-check me-1"></i> Pickup
                                </label>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Payment Method -->
                    <div class="card mb-4">
                        <div class="card-body">
                            <h4 class="card-title mb-4">
                                <i class="bi bi-credit-card me-2"></i> Payment Method
                            </h4>
                            
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="radio" name="payment_method" 
                                       id="cash" value="cash" checked>
                                <label class="form-check-label" for="cash">
                                    <i class="bi bi-cash-coin me-2"></i> Cash on Delivery
                                </label>
                            </div>
                            
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="radio" name="payment_method" 
                                       id="mobile_money" value="mobile_money">
                                <label class="form-check-label" for="mobile_money">
                                    <i class="bi bi-phone me-2"></i> Mobile Money (Telebirr/CBE Birr)
                                </label>
                            </div>
                            
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="radio" name="payment_method" 
                                       id="card" value="card">
                                <label class="form-check-label" for="card">
                                    <i class="bi bi-credit-card-2-front me-2"></i> Credit/Debit Card
                                </label>
                            </div>
                            
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle me-2"></i>
                                <small>All payments are secure and encrypted</small>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            
            <!-- Order Summary -->
            <div class="col-lg-4">
                <div class="card sticky-top" style="top: 20px;">
                    <div class="card-body">
                        <h4 class="card-title mb-4">Order Summary</h4>
                        
                        <?php if (count($stores) > 1): ?>
                        <div class="alert alert-info mb-3">
                            <i class="bi bi-info-circle me-2"></i>
                            <strong>Multi-Store Order</strong><br>
                            Items from <?php echo count($stores); ?> restaurants
                        </div>
                        
                        <?php foreach ($stores as $merchant_id => $store): ?>
                        <div class="mb-3">
                            <h6 class="text-primary mb-2">
                                <i class="bi bi-shop me-1"></i>
                                <?php echo htmlspecialchars($store['store_name']); ?>
                            </h6>
                            <?php foreach ($store['items'] as $item): ?>
                            <div class="d-flex justify-content-between mb-1">
                                <span class="small"><?php echo htmlspecialchars($item['name']); ?> × <?php echo $item['quantity']; ?></span>
                                <span class="small">$<?php echo number_format($item['subtotal'], 2); ?></span>
                            </div>
                            <?php endforeach; ?>
                            <div class="d-flex justify-content-between mb-1">
                                <span class="small"><strong>Store Subtotal:</strong></span>
                                <span class="small"><strong>$<?php echo number_format($store['subtotal'], 2); ?></strong></span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span class="small">Delivery Fee:</span>
                                <span class="small">$<?php echo number_format($store['delivery_fee'], 2); ?></span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        
                        <?php else: ?>
                        <?php foreach ($items as $item): ?>
                        <div class="d-flex justify-content-between mb-2">
                            <span><?php echo htmlspecialchars($item['name']); ?> × <?php echo $item['quantity']; ?></span>
                            <span>$<?php echo number_format($item['subtotal'], 2); ?></span>
                        </div>
                        <?php endforeach; ?>
                        <?php endif; ?>
                        
                        <hr>
                        
                        <div class="d-flex justify-content-between mb-2">
                            <span>Subtotal</span>
                            <span>$<?php echo number_format($subtotal, 2); ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-2 delivery-fee-row">
                            <span><?php echo count($stores) > 1 ? 'Total Delivery Fees' : 'Delivery Fee'; ?></span>
                            <span>$<?php echo number_format($total_delivery_fee, 2); ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-3">
                            <span>Tax (8%)</span>
                            <span>$<?php echo number_format($tax, 2); ?></span>
                        </div>
                        
                        <div class="d-flex justify-content-between fw-bold fs-5 mb-4">
                            <span>Total</span>
                            <span class="total-amount">$<?php echo number_format($total, 2); ?></span>
                        </div>
                        
                        <button type="submit" form="checkoutForm" class="btn btn-primary btn-lg w-100">
                            <i class="bi bi-lock me-2"></i> Place Order
                        </button>
                        
                        <a href="cart.php" class="btn btn-outline-secondary w-100 mt-2">
                            <i class="bi bi-arrow-left me-2"></i> Back to Cart
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Handle address selection
    document.getElementById('addressSelect')?.addEventListener('change', function() {
        const selected = this.options[this.selectedIndex];
        if (this.value !== '0') {
            document.getElementById('deliveryAddress').value = selected.dataset.address;
            document.getElementById('deliveryInstructions').value = selected.dataset.instructions || '';
        } else {
            document.getElementById('deliveryAddress').value = '';
            document.getElementById('deliveryInstructions').value = '';
        }
    });
    
    // Trigger initial load if default address exists
    if (document.getElementById('addressSelect')?.value !== '0') {
        document.getElementById('addressSelect').dispatchEvent(new Event('change'));
    }
    
    // Handle order type change
    document.querySelectorAll('input[name="order_type"]').forEach(radio => {
        radio.addEventListener('change', function() {
            const deliveryFeeRow = document.querySelector('.delivery-fee-row');
            if (this.value === 'pickup') {
                deliveryFeeRow.innerHTML = '<span>Delivery Fee</span><span class="text-success">FREE (Pickup)</span>';
            } else {
                deliveryFeeRow.innerHTML = '<span>Delivery Fee</span><span>$<?php echo number_format($delivery_fee, 2); ?></span>';
            }
            // Recalculate total
            updateTotal();
        });
    });
    
    function updateTotal() {
        const orderType = document.querySelector('input[name="order_type"]:checked').value;
        const subtotal = <?php echo $subtotal; ?>;
        const deliveryFee = orderType === 'pickup' ? 0 : <?php echo $total_delivery_fee; ?>;
        const tax = <?php echo $tax; ?>;
        const total = subtotal + deliveryFee + tax;
        
        document.querySelector('.total-amount').textContent = '$' + total.toFixed(2);
    }
    
    // Handle form submission
    document.getElementById('checkoutForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const submitBtn = this.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Processing...';
        
        fetch('process_order.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // If payment requires action (card/mobile money)
                if (data.requires_payment) {
                    // Redirect to payment page or show payment modal
                    window.location.href = data.redirect;
                } else {
                    // Direct to confirmation
                    window.location.href = data.redirect;
                }
            } else {
                alert(data.message || 'Failed to place order. Please try again.');
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred. Please try again.');
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        });
    });
    </script>
</body>
</html>