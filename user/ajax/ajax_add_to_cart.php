<?php
session_start();
include "../../includes/db.php";
include "../includes/auth_check.php";

header('Content-Type: application/json');

$item_id = intval($_POST['id'] ?? 0);
$action = $_POST['action'] ?? 'add';
$quantity = intval($_POST['quantity'] ?? 1);
$special_instructions = trim($_POST['special_instructions'] ?? '');

if (!$item_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid item']);
    exit();
}

$user_id = $_SESSION['user_id'];

// Get item details with merchant info
$sql = "SELECT mi.*, m.merchant_id, m.store_name, m.status as merchant_status 
        FROM menu_items mi
        JOIN merchants m ON mi.merchant_id = m.merchant_id
        WHERE mi.id = ? AND mi.is_available = 1 AND m.status = 'active'";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $item_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$item = mysqli_fetch_assoc($result);

if (!$item) {
    echo json_encode(['success' => false, 'message' => 'Item not found or unavailable']);
    exit();
}

$merchant_id = $item['merchant_id'];

// Multi-store cart protection removed - allow items from all restaurants

// Handle action
if ($action === 'add') {
    // Check if item already in cart
    $check_existing = "SELECT cart_id, quantity FROM cart_items WHERE user_id = ? AND menu_item_id = ?";
    $stmt = mysqli_prepare($conn, $check_existing);
    mysqli_stmt_bind_param($stmt, "ii", $user_id, $item_id);
    mysqli_stmt_execute($stmt);
    $existing = mysqli_stmt_get_result($stmt);
    
    if ($row = mysqli_fetch_assoc($existing)) {
        // Update existing item
        $new_quantity = $row['quantity'] + $quantity;
        $update_sql = "UPDATE cart_items SET quantity = ?, special_instructions = ?, updated_at = NOW() 
                       WHERE cart_id = ?";
        $stmt = mysqli_prepare($conn, $update_sql);
        mysqli_stmt_bind_param($stmt, "isi", $new_quantity, $special_instructions, $row['cart_id']);
        mysqli_stmt_execute($stmt);
    } else {
        // Insert new item
        $insert_sql = "INSERT INTO cart_items (user_id, menu_item_id, quantity, special_instructions) 
                       VALUES (?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $insert_sql);
        mysqli_stmt_bind_param($stmt, "iiis", $user_id, $item_id, $quantity, $special_instructions);
        mysqli_stmt_execute($stmt);
    }
    $message = 'Item added to cart successfully!';
    
} elseif ($action === 'remove') {
    $delete_sql = "DELETE FROM cart_items WHERE user_id = ? AND menu_item_id = ?";
    $stmt = mysqli_prepare($conn, $delete_sql);
    mysqli_stmt_bind_param($stmt, "ii", $user_id, $item_id);
    mysqli_stmt_execute($stmt);
    $message = 'Item removed from cart';
    
} elseif ($action === 'update') {
    if ($quantity > 0) {
        $update_sql = "UPDATE cart_items SET quantity = ?, updated_at = NOW() 
                       WHERE user_id = ? AND menu_item_id = ?";
        $stmt = mysqli_prepare($conn, $update_sql);
        mysqli_stmt_bind_param($stmt, "iii", $quantity, $user_id, $item_id);
        mysqli_stmt_execute($stmt);
    } else {
        $delete_sql = "DELETE FROM cart_items WHERE user_id = ? AND menu_item_id = ?";
        $stmt = mysqli_prepare($conn, $delete_sql);
        mysqli_stmt_bind_param($stmt, "ii", $user_id, $item_id);
        mysqli_stmt_execute($stmt);
    }
    $message = 'Cart updated';
}

// Get updated cart summary
$cart_sql = "SELECT 
                ci.cart_id,
                ci.menu_item_id,
                ci.quantity,
                ci.special_instructions,
                mi.name,
                mi.price,
                mi.image,
                m.store_name,
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

while ($cart_item = mysqli_fetch_assoc($cart_result)) {
    $cart_count += $cart_item['quantity'];
    $cart_total += $cart_item['subtotal'];
    $items[] = [
        'id' => $cart_item['menu_item_id'],
        'name' => $cart_item['name'],
        'price' => floatval($cart_item['price']),
        'quantity' => intval($cart_item['quantity']),
        'subtotal' => floatval($cart_item['subtotal']),
        'image' => $cart_item['image'],
        'store_name' => $cart_item['store_name'],
        'special_instructions' => $cart_item['special_instructions']
    ];
}

echo json_encode([
    'success' => true,
    'cart_count' => $cart_count,
    'cart_total' => $cart_total,
    'cart_total_formatted' => number_format($cart_total, 2),
    'items' => $items,
    'message' => $message
]);