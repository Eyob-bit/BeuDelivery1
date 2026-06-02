<?php
session_start();
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['merchant_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit();
}

include "../../includes/db.php";
$merchant_id = $_SESSION['merchant_id'];

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);
$order_id = intval($input['order_id'] ?? 0);
$new_status = $input['status'] ?? '';

// Validate status
$valid_statuses = ['pending', 'preparing', 'ready', 'delivering', 'completed', 'cancelled'];
if (!in_array($new_status, $valid_statuses)) {
    echo json_encode(['success' => false, 'message' => 'Invalid status']);
    exit();
}

// Verify the order belongs to this merchant
$verify_sql = "SELECT COUNT(*) as count 
               FROM orders o
               JOIN order_items oi ON o.id = oi.order_id
               JOIN menu_items mi ON oi.menu_item_id = mi.id
               WHERE o.id = ? AND mi.merchant_id = ?";
$stmt = mysqli_prepare($conn, $verify_sql);
mysqli_stmt_bind_param($stmt, "ii", $order_id, $merchant_id);
mysqli_stmt_execute($stmt);
$verify_result = mysqli_stmt_get_result($stmt);
$verify = mysqli_fetch_assoc($verify_result);

if ($verify['count'] == 0) {
    echo json_encode(['success' => false, 'message' => 'Order not found or access denied']);
    exit();
}

// Update order status
$update_sql = "UPDATE orders SET status = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?";
$stmt = mysqli_prepare($conn, $update_sql);
mysqli_stmt_bind_param($stmt, "si", $new_status, $order_id);

if (mysqli_stmt_execute($stmt)) {
    echo json_encode(['success' => true, 'message' => 'Order status updated successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update order status']);
}
?>