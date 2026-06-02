<?php
/**
 * Get Order Details - AJAX Endpoint
 * Returns complete order information including items
 */

session_start();
require_once "admin_auth.php";
include "../includes/db.php";

header('Content-Type: application/json');

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid order ID']);
    exit();
}

$order_id = (int)$_GET['id'];

// Get order details
$order_sql = "SELECT 
    o.*,
    CONCAT(u.first_name, ' ', u.last_name) as customer_name,
    u.email as customer_email,
    u.phone as customer_phone,
    m.store_name,
    m.store_address
    FROM orders o
    LEFT JOIN users u ON o.user_id = u.id
    LEFT JOIN merchants m ON o.merchant_id = m.merchant_id
    WHERE o.id = $order_id";

$order_result = mysqli_query($conn, $order_sql);

if (!$order_result || mysqli_num_rows($order_result) == 0) {
    echo json_encode(['success' => false, 'message' => 'Order not found']);
    exit();
}

$order = mysqli_fetch_assoc($order_result);

// Get order items
$items_sql = "SELECT 
    oi.*,
    mi.name as menu_item_name,
    mi.image as menu_item_image
    FROM order_items oi
    LEFT JOIN menu_items mi ON oi.menu_item_id = mi.id
    WHERE oi.order_id = $order_id";

$items_result = mysqli_query($conn, $items_sql);
$items = [];

while ($item = mysqli_fetch_assoc($items_result)) {
    $items[] = $item;
}

$order['items'] = $items;

echo json_encode([
    'success' => true,
    'order' => $order
]);
