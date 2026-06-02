<?php
session_start();
include "../../includes/db.php";
include "../includes/auth_check.php";

header('Content-Type: application/json');

$user_id = $_SESSION['user_id'];

// Get cart items with details
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
$cart_result = mysqli_stmt_get_result($stmt);

$cart_count = 0;
$cart_total = 0;
$items = [];
$stores = [];

while ($cart_item = mysqli_fetch_assoc($cart_result)) {
    $cart_count += $cart_item['quantity'];
    $cart_total += $cart_item['subtotal'];
    
    $items[] = [
        'id' => intval($cart_item['menu_item_id']),
        'name' => $cart_item['name'],
        'price' => floatval($cart_item['price']),
        'quantity' => intval($cart_item['quantity']),
        'subtotal' => floatval($cart_item['subtotal']),
        'image' => $cart_item['image'],
        'store_name' => $cart_item['store_name'],
        'merchant_id' => intval($cart_item['merchant_id']),
        'special_instructions' => $cart_item['special_instructions']
    ];
    
    if (!isset($stores[$cart_item['merchant_id']])) {
        $stores[$cart_item['merchant_id']] = $cart_item['store_name'];
    }
}

echo json_encode([
    'success' => true,
    'cart_count' => $cart_count,
    'cart_total' => $cart_total,
    'cart_total_formatted' => number_format($cart_total, 2),
    'items' => $items,
    'stores' => $stores,
    'store_count' => count($stores)
]);
?>
