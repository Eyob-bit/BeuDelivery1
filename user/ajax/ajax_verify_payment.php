<?php
session_start();
include "../../includes/db.php";
include "../../includes/payment_gateway.php";
include "../includes/auth_check.php";

header('Content-Type: application/json');

$order_id = intval($_GET['order_id'] ?? 0);
$reference = $_GET['reference'] ?? '';

if (!$order_id || empty($reference)) {
    echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
    exit();
}

$user_id = $_SESSION['user_id'];

// Verify order belongs to user
$check_sql = "SELECT id FROM orders WHERE id = ? AND user_id = ?";
$stmt = mysqli_prepare($conn, $check_sql);
mysqli_stmt_bind_param($stmt, "ii", $order_id, $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (!mysqli_fetch_assoc($result)) {
    echo json_encode(['success' => false, 'message' => 'Order not found']);
    exit();
}

// Verify payment
$payment_gateway = new PaymentGateway($conn);
$result = $payment_gateway->verifyPayment($order_id, $reference);

echo json_encode($result);
?>
