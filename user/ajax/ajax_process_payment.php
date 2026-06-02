<?php
session_start();
include "../../includes/db.php";
include "../../includes/payment_gateway.php";
include "../includes/auth_check.php";

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

$order_id = intval($_POST['order_id'] ?? 0);
$payment_method = $_POST['payment_method'] ?? '';

if (!$order_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid order ID']);
    exit();
}

$user_id = $_SESSION['user_id'];

// Get order details
$order_sql = "SELECT o.*, u.first_name, u.last_name, u.email, u.phone
              FROM orders o
              JOIN users u ON o.user_id = u.id
              WHERE o.id = ? AND o.user_id = ?";
$stmt = mysqli_prepare($conn, $order_sql);
mysqli_stmt_bind_param($stmt, "ii", $order_id, $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$order = mysqli_fetch_assoc($result);

if (!$order) {
    echo json_encode(['success' => false, 'message' => 'Order not found']);
    exit();
}

// Check if already paid
if ($order['payment_status'] === 'paid') {
    echo json_encode(['success' => false, 'message' => 'Order already paid']);
    exit();
}

// Prepare customer data
$customer_data = [
    'user_id' => $user_id,
    'first_name' => $order['first_name'],
    'last_name' => $order['last_name'],
    'email' => $order['email'],
    'phone' => $order['phone']
];

// Initialize payment gateway
$payment_gateway = new PaymentGateway($conn);
$result = $payment_gateway->initializePayment(
    $order_id,
    $order['total'],
    $payment_method,
    $customer_data
);

echo json_encode($result);
?>
