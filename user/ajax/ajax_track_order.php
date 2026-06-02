<?php
session_start();
include "../../includes/db.php";
include "../includes/auth_check.php";

header('Content-Type: application/json');

$order_id = intval($_GET['order_id'] ?? 0);

if (!$order_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid order ID']);
    exit();
}

$user_id = $_SESSION['user_id'];

// Get order details
$order_sql = "SELECT 
                o.*,
                m.store_name,
                m.store_address as merchant_address,
                md.store_phone,
                u.first_name as driver_first_name,
                u.last_name as driver_last_name,
                u.phone as driver_phone
              FROM orders o
              JOIN merchants m ON o.merchant_id = m.merchant_id
              LEFT JOIN merchant_details md ON m.merchant_id = md.merchant_id
              LEFT JOIN users u ON o.driver_id = u.id
              WHERE o.id = ? AND o.user_id = ?";
$stmt = mysqli_prepare($conn, $order_sql);
mysqli_stmt_bind_param($stmt, "ii", $order_id, $user_id);
mysqli_stmt_execute($stmt);
$order_result = mysqli_stmt_get_result($stmt);
$order = mysqli_fetch_assoc($order_result);

if (!$order) {
    echo json_encode(['success' => false, 'message' => 'Order not found']);
    exit();
}

// Get order items
$items_sql = "SELECT * FROM order_items WHERE order_id = ?";
$stmt = mysqli_prepare($conn, $items_sql);
mysqli_stmt_bind_param($stmt, "i", $order_id);
mysqli_stmt_execute($stmt);
$items_result = mysqli_stmt_get_result($stmt);

$items = [];
while ($item = mysqli_fetch_assoc($items_result)) {
    $items[] = [
        'name' => $item['item_name'],
        'quantity' => intval($item['quantity']),
        'price' => floatval($item['price']),
        'subtotal' => floatval($item['subtotal']),
        'special_instructions' => $item['special_instructions']
    ];
}

// Get tracking history
$tracking_sql = "SELECT 
                    ot.*,
                    u.first_name,
                    u.last_name
                 FROM order_tracking ot
                 LEFT JOIN users u ON ot.created_by = u.id
                 WHERE ot.order_id = ?
                 ORDER BY ot.created_at ASC";
$stmt = mysqli_prepare($conn, $tracking_sql);
mysqli_stmt_bind_param($stmt, "i", $order_id);
mysqli_stmt_execute($stmt);
$tracking_result = mysqli_stmt_get_result($stmt);

$tracking = [];
while ($track = mysqli_fetch_assoc($tracking_result)) {
    $tracking[] = [
        'status' => $track['status'],
        'message' => $track['message'],
        'created_at' => $track['created_at'],
        'created_by' => $track['first_name'] ? $track['first_name'] . ' ' . $track['last_name'] : 'System',
        'latitude' => $track['latitude'],
        'longitude' => $track['longitude']
    ];
}

// Calculate progress percentage
$status_progress = [
    'pending' => 10,
    'confirmed' => 25,
    'preparing' => 50,
    'ready' => 65,
    'picked_up' => 75,
    'on_the_way' => 90,
    'delivered' => 100,
    'cancelled' => 0
];

$progress = $status_progress[$order['status']] ?? 0;

// Format response
echo json_encode([
    'success' => true,
    'order' => [
        'id' => intval($order['id']),
        'order_number' => $order['order_number'],
        'status' => $order['status'],
        'payment_status' => $order['payment_status'],
        'payment_method' => $order['payment_method'],
        'order_type' => $order['order_type'],
        'subtotal' => floatval($order['subtotal']),
        'delivery_fee' => floatval($order['delivery_fee']),
        'tax' => floatval($order['tax']),
        'total' => floatval($order['total']),
        'delivery_address' => $order['delivery_address'],
        'delivery_instructions' => $order['delivery_instructions'],
        'estimated_delivery_time' => $order['estimated_delivery_time'],
        'actual_delivery_time' => $order['actual_delivery_time'],
        'created_at' => $order['created_at'],
        'store_name' => $order['store_name'],
        'store_phone' => $order['store_phone'],
        'merchant_address' => $order['merchant_address'],
        'driver' => $order['driver_id'] ? [
            'name' => $order['driver_first_name'] . ' ' . $order['driver_last_name'],
            'phone' => $order['driver_phone']
        ] : null,
        'progress' => $progress
    ],
    'items' => $items,
    'tracking' => $tracking
]);
?>
