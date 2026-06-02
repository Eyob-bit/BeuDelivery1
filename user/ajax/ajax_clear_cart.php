<?php
session_start();
include "../../includes/db.php";
include "../includes/auth_check.php";

header('Content-Type: application/json');

$user_id = $_SESSION['user_id'];

// Clear all cart items for user
$delete_sql = "DELETE FROM cart_items WHERE user_id = ?";
$stmt = mysqli_prepare($conn, $delete_sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);

if (mysqli_stmt_execute($stmt)) {
    echo json_encode([
        'success' => true,
        'message' => 'Cart cleared successfully'
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to clear cart'
    ]);
}
?>
