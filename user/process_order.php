<?php
session_start();
include "../includes/db.php";
include "includes/auth_check.php";

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

$user_id = $_SESSION['user_id'];

// Get form data
$delivery_address = trim($_POST['delivery_address'] ?? '');
$delivery_instructions = trim($_POST['delivery_instructions'] ?? '');
$payment_method = $_POST['payment_method'] ?? 'cash';
$order_type = $_POST['order_type'] ?? 'delivery';
$address_id = intval($_POST['address_id'] ?? 0);

// Validate inputs
if (empty($delivery_address)) {
    echo json_encode(['success' => false, 'message' => 'Delivery address is required']);
    exit();
}

if (!in_array($payment_method, ['cash', 'card', 'mobile_money', 'wallet'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid payment method']);
    exit();
}

// Get cart items
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

// Get cart items - Updated for multi-store
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

$cart_items = [];
$stores = [];
$subtotal = 0;

while ($item = mysqli_fetch_assoc($cart_result)) {
    $cart_items[] = $item;
    $subtotal += $item['price'] * $item['quantity'];
    
    if (!isset($stores[$item['merchant_id']])) {
        $stores[$item['merchant_id']] = [
            'store_name' => $item['store_name'],
            'items' => []
        ];
    }
    $stores[$item['merchant_id']]['items'][] = $item;
}

if (empty($cart_items)) {
    echo json_encode(['success' => false, 'message' => 'Your cart is empty']);
    exit();
}

// Calculate delivery fees for all stores
$total_delivery_fee = 0;
foreach ($stores as $merchant_id => $store_data) {
    $delivery_sql = "SELECT delivery_fee, min_order_amount FROM delivery_settings WHERE merchant_id = ?";
    $stmt = mysqli_prepare($conn, $delivery_sql);
    mysqli_stmt_bind_param($stmt, "i", $merchant_id);
    mysqli_stmt_execute($stmt);
    $delivery_result = mysqli_stmt_get_result($stmt);
    $delivery_data = mysqli_fetch_assoc($delivery_result);

    $delivery_fee = $order_type === 'delivery' ? floatval($delivery_data['delivery_fee'] ?? 2.99) : 0;
    $stores[$merchant_id]['delivery_fee'] = $delivery_fee;
    $total_delivery_fee += $delivery_fee;
    
    // Check minimum order for each store
    $min_order_amount = floatval($delivery_data['min_order_amount'] ?? 0);
    $store_subtotal = array_sum(array_map(function($item) {
        return $item['price'] * $item['quantity'];
    }, $store_data['items']));
    
    if ($store_subtotal < $min_order_amount) {
        echo json_encode([
            'success' => false, 
            'message' => "Minimum order amount for {$store_data['store_name']} is $" . number_format($min_order_amount, 2)
        ]);
        exit();
    }
}

// Calculate totals
$tax_rate = 0.08;
$tax = $subtotal * $tax_rate;
$total = $subtotal + $total_delivery_fee + $tax;

// Generate order number
$order_number = 'ORD-' . strtoupper(uniqid());

// Calculate estimated delivery time (30-45 minutes from now)
$estimated_minutes = rand(30, 45);
$estimated_delivery_time = date('Y-m-d H:i:s', strtotime("+$estimated_minutes minutes"));

// Start transaction
mysqli_begin_transaction($conn);

try {
    $created_orders = [];
    
    // Create separate orders for each store
    foreach ($stores as $merchant_id => $store_data) {
        // Generate order number for this store
        $order_number = 'ORD-' . strtoupper(uniqid());
        
        // Calculate store-specific totals
        $store_subtotal = array_sum(array_map(function($item) {
            return $item['price'] * $item['quantity'];
        }, $store_data['items']));
        
        $store_delivery_fee = $store_data['delivery_fee'];
        $store_tax = $store_subtotal * $tax_rate;
        $store_total = $store_subtotal + $store_delivery_fee + $store_tax;
        
        // Calculate estimated delivery time (30-45 minutes from now)
        $estimated_minutes = rand(30, 45);
        $estimated_delivery_time = date('Y-m-d H:i:s', strtotime("+$estimated_minutes minutes"));
        
        // Create order for this store
        $order_sql = "INSERT INTO orders (
                        order_number, user_id, merchant_id, delivery_address_id, delivery_address,
                        delivery_instructions, subtotal, delivery_fee, tax, total,
                        status, payment_method, payment_status, order_type, estimated_delivery_time
                      ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?, 'pending', ?, ?)";
        
        $stmt = mysqli_prepare($conn, $order_sql);
        $address_id_param = $address_id > 0 ? $address_id : null;
        mysqli_stmt_bind_param($stmt, "siiissdddssss", 
            $order_number, $user_id, $merchant_id, $address_id_param, $delivery_address,
            $delivery_instructions, $store_subtotal, $store_delivery_fee, $store_tax, $store_total,
            $payment_method, $order_type, $estimated_delivery_time
        );
        
        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception('Failed to create order for ' . $store_data['store_name']);
        }
        
        $order_id = mysqli_insert_id($conn);
        $created_orders[] = [
            'order_id' => $order_id,
            'order_number' => $order_number,
            'store_name' => $store_data['store_name'],
            'merchant_id' => $merchant_id
        ];
        
        // Add order items for this store
        $item_sql = "INSERT INTO order_items (order_id, menu_item_id, item_name, quantity, price, subtotal, special_instructions)
                     VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $item_sql);
        
        foreach ($store_data['items'] as $item) {
            $item_subtotal = $item['price'] * $item['quantity'];
            mysqli_stmt_bind_param($stmt, "iisidds",
                $order_id, $item['menu_item_id'], $item['name'],
                $item['quantity'], $item['price'], $item_subtotal, $item['special_instructions']
            );
            
            if (!mysqli_stmt_execute($stmt)) {
                throw new Exception('Failed to add order items for ' . $store_data['store_name']);
            }
        }
        
        // Add initial tracking entry
        $tracking_sql = "INSERT INTO order_tracking (order_id, status, message, created_by)
                         VALUES (?, 'pending', 'Order placed successfully', ?)";
        $stmt = mysqli_prepare($conn, $tracking_sql);
        mysqli_stmt_bind_param($stmt, "ii", $order_id, $user_id);
        mysqli_stmt_execute($stmt);
        
        // Create merchant earnings record
        $merchant_sql = "SELECT commission_rate FROM merchants WHERE merchant_id = ?";
        $stmt = mysqli_prepare($conn, $merchant_sql);
        mysqli_stmt_bind_param($stmt, "i", $merchant_id);
        mysqli_stmt_execute($stmt);
        $merchant_result = mysqli_stmt_get_result($stmt);
        $merchant_data = mysqli_fetch_assoc($merchant_result);
        $commission_rate = floatval($merchant_data['commission_rate'] ?? 15.00);
        
        $commission_amount = ($store_subtotal * $commission_rate) / 100;
        $net_amount = $store_subtotal - $commission_amount;
        
        $earnings_sql = "INSERT INTO merchant_earnings (merchant_id, order_id, order_amount, commission_rate, commission_amount, net_amount)
                         VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $earnings_sql);
        mysqli_stmt_bind_param($stmt, "iidddd", $merchant_id, $order_id, $store_subtotal, $commission_rate, $commission_amount, $net_amount);
        mysqli_stmt_execute($stmt);
        
        // Create transaction record
        $transaction_sql = "INSERT INTO transactions (order_id, user_id, merchant_id, amount, transaction_type, payment_method, status, reference_number)
                            VALUES (?, ?, ?, ?, 'payment', ?, 'pending', ?)";
        $stmt = mysqli_prepare($conn, $transaction_sql);
        $reference = 'TXN-' . strtoupper(uniqid());
        mysqli_stmt_bind_param($stmt, "iiidss", $order_id, $user_id, $merchant_id, $store_total, $payment_method, $reference);
        mysqli_stmt_execute($stmt);
        
        // Update merchant total orders
        $update_merchant = "UPDATE merchants SET total_orders = total_orders + 1 WHERE merchant_id = ?";
        $stmt = mysqli_prepare($conn, $update_merchant);
        mysqli_stmt_bind_param($stmt, "i", $merchant_id);
        mysqli_stmt_execute($stmt);
        
        // Create notification for user
        $notif_sql = "INSERT INTO notifications (user_id, title, message, type, related_id)
                      VALUES (?, 'Order Placed', ?, 'order', ?)";
        $stmt = mysqli_prepare($conn, $notif_sql);
        $notif_message = "Your order #$order_number from {$store_data['store_name']} has been placed successfully!";
        mysqli_stmt_bind_param($stmt, "isi", $user_id, $notif_message, $order_id);
        mysqli_stmt_execute($stmt);
    }
    
    // Clear cart
    $clear_cart = "DELETE FROM cart_items WHERE user_id = ?";
    $stmt = mysqli_prepare($conn, $clear_cart);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    
    // Commit transaction
    mysqli_commit($conn);
    
    // Return success with order information
    if (count($created_orders) === 1) {
        // Single store order
        echo json_encode([
            'success' => true,
            'message' => 'Order placed successfully!',
            'order_id' => $created_orders[0]['order_id'],
            'order_number' => $created_orders[0]['order_number'],
            'redirect' => 'order_confirmation.php?id=' . $created_orders[0]['order_id']
        ]);
    } else {
        // Multi-store orders
        echo json_encode([
            'success' => true,
            'message' => count($created_orders) . ' orders placed successfully!',
            'orders' => $created_orders,
            'redirect' => 'multi_order_confirmation.php?orders=' . implode(',', array_column($created_orders, 'order_id'))
        ]);
    }
    
} catch (Exception $e) {
    // Rollback on error
    mysqli_rollback($conn);
    
    echo json_encode([
        'success' => false,
        'message' => 'Failed to process order: ' . $e->getMessage()
    ]);
}

mysqli_close($conn);
?>
