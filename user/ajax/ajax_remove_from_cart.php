<?php
session_start();
include "../../includes/db.php";
include "../includes/auth_check.php";

header('Content-Type: application/json');

$item_id = intval($_POST['id'] ?? 0);

if (!$item_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid item']);
    exit();
}

$user_id = $_SESSION['user_id'];

// Delete item from cart
$delete_sql = "DELETE FROM cart_items WHERE user_id = ? AND menu_item_id = ?";
$stmt = mysqli_prepare($conn, $delete_sql);
mysqli_stmt_bind_param($stmt, "ii", $user_id, $item_id);

if (mysqli_stmt_execute($stmt)) {
    // Get updated cart count
    $count_sql = "SELECT SUM(quantity) as total FROM cart_items WHERE user_id = ?";
    $stmt = mysqli_prepare($conn, $count_sql);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $count_data = mysqli_fetch_assoc($result);
    
    echo json_encode([
        'success' => true, 
        'message' => 'Item removed from cart',
        'cart_count' => intval($count_data['total'] ?? 0)
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to remove item']);
}
?>
