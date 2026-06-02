<?php
session_start();
include "../../includes/db.php";
include "../includes/auth_check.php";

header('Content-Type: application/json');

$item_id = intval($_POST['id'] ?? 0);
$action = $_POST['action'] ?? '';
$quantity = intval($_POST['quantity'] ?? 0);

if (!$item_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid item']);
    exit();
}

$user_id = $_SESSION['user_id'];

// Check if item exists in cart
$check_sql = "SELECT cart_id, quantity FROM cart_items WHERE user_id = ? AND menu_item_id = ?";
$stmt = mysqli_prepare($conn, $check_sql);
mysqli_stmt_bind_param($stmt, "ii", $user_id, $item_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$cart_item = mysqli_fetch_assoc($result);

if (!$cart_item) {
    echo json_encode(['success' => false, 'message' => 'Item not in cart']);
    exit();
}

$new_quantity = $cart_item['quantity'];

// Update quantity based on action
if ($action === 'increase') {
    $new_quantity++;
} elseif ($action === 'decrease') {
    $new_quantity--;
} elseif ($action === 'set' && $quantity > 0) {
    $new_quantity = $quantity;
}

// Update or delete
if ($new_quantity > 0) {
    $update_sql = "UPDATE cart_items SET quantity = ?, updated_at = NOW() WHERE cart_id = ?";
    $stmt = mysqli_prepare($conn, $update_sql);
    mysqli_stmt_bind_param($stmt, "ii", $new_quantity, $cart_item['cart_id']);
    mysqli_stmt_execute($stmt);
    $message = 'Cart updated';
} else {
    $delete_sql = "DELETE FROM cart_items WHERE cart_id = ?";
    $stmt = mysqli_prepare($conn, $delete_sql);
    mysqli_stmt_bind_param($stmt, "i", $cart_item['cart_id']);
    mysqli_stmt_execute($stmt);
    $message = 'Item removed from cart';
}

// Get updated cart summary
$summary_sql = "SELECT 
                    SUM(ci.quantity) as total_quantity,
                    SUM(mi.price * ci.quantity) as cart_total
                FROM cart_items ci
                JOIN menu_items mi ON ci.menu_item_id = mi.id
                WHERE ci.user_id = ?";
$stmt = mysqli_prepare($conn, $summary_sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$summary_result = mysqli_stmt_get_result($stmt);
$summary = mysqli_fetch_assoc($summary_result);

echo json_encode([
    'success' => true,
    'message' => $message,
    'new_quantity' => $new_quantity,
    'cart_count' => intval($summary['total_quantity'] ?? 0),
    'cart_total' => floatval($summary['cart_total'] ?? 0),
    'cart_total_formatted' => number_format($summary['cart_total'] ?? 0, 2)
]);
?>
