<?php
session_start();
include "../../includes/db.php";
include "../includes/auth_check.php";

header('Content-Type: application/json');

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
$subtotal = 0;
$merchant_id = null;
$store_name = null;

while ($item = mysqli_fetch_assoc($result)) {
    $subtotal += $item['subtotal'];
    $merchant_id = $item['merchant_id'];
    $store_name = $item['store_name'];
    
    $items[] = [
        'id' => $item['menu_item_id'],
        'name' => $item['name'],
        'price' => floatval($item['price']),
        'quantity' => intval($item['quantity']),
        'subtotal' => floatval($item['subtotal']),
        'image' => $item['image'],
        'store_name' => $item['store_name'],
        'special_instructions' => $item['special_instructions']
    ];
}

// Get delivery settings if we have items
$delivery_fee = 2.99;
$min_order_amount = 0;

if ($merchant_id) {
    $delivery_sql = "SELECT delivery_fee, min_order_amount FROM delivery_settings WHERE merchant_id = ?";
    $stmt = mysqli_prepare($conn, $delivery_sql);
    mysqli_stmt_bind_param($stmt, "i", $merchant_id);
    mysqli_stmt_execute($stmt);
    $delivery_result = mysqli_stmt_get_result($stmt);
    if ($delivery_data = mysqli_fetch_assoc($delivery_result)) {
        $delivery_fee = floatval($delivery_data['delivery_fee']);
        $min_order_amount = floatval($delivery_data['min_order_amount']);
    }
}

$tax_rate = 0.08;
$tax = $subtotal * $tax_rate;
$total = $subtotal + $delivery_fee + $tax;
$total_items = count($items);
$total_quantity = array_sum(array_column($items, 'quantity'));

echo json_encode([
    'success' => true,
    'items' => $items,
    'subtotal' => $subtotal,
    'delivery_fee' => $delivery_fee,
    'tax' => $tax,
    'total' => $total,
    'total_items' => $total_items,
    'total_quantity' => $total_quantity,
    'store_name' => $store_name,
    'merchant_id' => $merchant_id,
    'min_order_amount' => $min_order_amount,
    'meets_minimum' => $subtotal >= $min_order_amount
]);
?>
