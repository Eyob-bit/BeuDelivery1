<?php
session_start();
include "../includes/db.php";
include "includes/auth_check.php";

$order_id = intval($_GET['id'] ?? 0);
$user_id = $_SESSION['user_id'];

if (!$order_id) {
    header("Location: home.php");
    exit();
}

// Get order details
$order_sql = "SELECT 
                o.*,
                m.store_name,
                m.store_address,
                md.store_phone
              FROM orders o
              JOIN merchants m ON o.merchant_id = m.merchant_id
              LEFT JOIN merchant_details md ON m.merchant_id = md.merchant_id
              WHERE o.id = ? AND o.user_id = ?";
$stmt = mysqli_prepare($conn, $order_sql);
mysqli_stmt_bind_param($stmt, "ii", $order_id, $user_id);
mysqli_stmt_execute($stmt);
$order_result = mysqli_stmt_get_result($stmt);
$order = mysqli_fetch_assoc($order_result);

if (!$order) {
    header("Location: home.php");
    exit();
}

// Get order items
$items_sql = "SELECT * FROM order_items WHERE order_id = ?";
$stmt = mysqli_prepare($conn, $items_sql);
mysqli_stmt_bind_param($stmt, "i", $order_id);
mysqli_stmt_execute($stmt);
$items_result = mysqli_stmt_get_result($stmt);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation - BeU Delivery</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        .success-icon {
            font-size: 5rem;
            color: #28a745;
            animation: scaleIn 0.5s ease-in-out;
        }
        
        @keyframes scaleIn {
            0% { transform: scale(0); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }
        
        .order-card {
            border: 2px solid #28a745;
            border-radius: 15px;
        }
        
        .status-badge {
            padding: 8px 20px;
            border-radius: 20px;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.9rem;
        }
        
        .status-pending {
            background: #fff3cd;
            color: #856404;
        }
        
        .status-confirmed {
            background: #d4edda;
            color: #155724;
        }
        
        .timeline {
            position: relative;
            padding-left: 30px;
        }
        
        .timeline::before {
            content: '';
            position: absolute;
            left: 10px;
            top: 0;
            bottom: 0;
            width: 2px;
            background: #dee2e6;
        }
        
        .timeline-item {
            position: relative;
            padding-bottom: 20px;
        }
        
        .timeline-item::before {
            content: '';
            position: absolute;
            left: -24px;
            top: 5px;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: #dee2e6;
            border: 2px solid #fff;
        }
        
        .timeline-item.active::before {
            background: #28a745;
        }
    </style>
</head>
<body>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <!-- Success Message -->
                <div class="text-center mb-5">
                    <i class="bi bi-check-circle-fill success-icon"></i>
                    <h1 class="mt-4 mb-3">Order Placed Successfully!</h1>
                    <p class="lead text-muted">Thank you for your order. We'll notify you when it's on the way.</p>
                </div>
                
                <!-- Order Details Card -->
                <div class="card order-card mb-4">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <div>
                                <h4 class="mb-1">Order #<?php echo htmlspecialchars($order['order_number']); ?></h4>
                                <p class="text-muted mb-0">
                                    <i class="bi bi-clock me-1"></i>
                                    Placed on <?php echo date('M d, Y \a\t h:i A', strtotime($order['created_at'])); ?>
                                </p>
                            </div>
                            <span class="status-badge status-<?php echo $order['status']; ?>">
                                <?php echo ucfirst($order['status']); ?>
                            </span>
                        </div>
                        
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <h6 class="text-muted mb-2">Store</h6>
                                <p class="mb-1"><strong><?php echo htmlspecialchars($order['store_name']); ?></strong></p>
                                <p class="text-muted small mb-0">
                                    <i class="bi bi-telephone me-1"></i>
                                    <?php echo htmlspecialchars($order['store_phone'] ?? 'N/A'); ?>
                                </p>
                            </div>
                            <div class="col-md-6">
                                <h6 class="text-muted mb-2">Delivery Address</h6>
                                <p class="mb-0"><?php echo nl2br(htmlspecialchars($order['delivery_address'])); ?></p>
                                <?php if ($order['delivery_instructions']): ?>
                                <p class="text-muted small mb-0">
                                    <i class="bi bi-info-circle me-1"></i>
                                    <?php echo htmlspecialchars($order['delivery_instructions']); ?>
                                </p>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <h6 class="text-muted mb-2">Estimated Delivery</h6>
                                <p class="mb-0">
                                    <i class="bi bi-clock-history me-1"></i>
                                    <?php echo date('h:i A', strtotime($order['estimated_delivery_time'])); ?>
                                </p>
                            </div>
                            <div class="col-md-6">
                                <h6 class="text-muted mb-2">Payment Method</h6>
                                <p class="mb-0">
                                    <i class="bi bi-<?php echo $order['payment_method'] === 'cash' ? 'cash-coin' : 'credit-card'; ?> me-1"></i>
                                    <?php echo ucfirst(str_replace('_', ' ', $order['payment_method'])); ?>
                                    <span class="badge bg-<?php echo $order['payment_status'] === 'paid' ? 'success' : 'warning'; ?> ms-2">
                                        <?php echo ucfirst($order['payment_status']); ?>
                                    </span>
                                </p>
                            </div>
                        </div>
                        
                        <!-- Order Items -->
                        <h6 class="text-muted mb-3">Order Items</h6>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Item</th>
                                        <th class="text-center">Qty</th>
                                        <th class="text-end">Price</th>
                                        <th class="text-end">Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($item = mysqli_fetch_assoc($items_result)): ?>
                                    <tr>
                                        <td>
                                            <?php echo htmlspecialchars($item['item_name']); ?>
                                            <?php if ($item['special_instructions']): ?>
                                            <br><small class="text-muted"><?php echo htmlspecialchars($item['special_instructions']); ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center"><?php echo $item['quantity']; ?></td>
                                        <td class="text-end">$<?php echo number_format($item['price'], 2); ?></td>
                                        <td class="text-end">$<?php echo number_format($item['subtotal'], 2); ?></td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="3" class="text-end"><strong>Subtotal:</strong></td>
                                        <td class="text-end">$<?php echo number_format($order['subtotal'], 2); ?></td>
                                    </tr>
                                    <tr>
                                        <td colspan="3" class="text-end">Delivery Fee:</td>
                                        <td class="text-end">$<?php echo number_format($order['delivery_fee'], 2); ?></td>
                                    </tr>
                                    <tr>
                                        <td colspan="3" class="text-end">Tax:</td>
                                        <td class="text-end">$<?php echo number_format($order['tax'], 2); ?></td>
                                    </tr>
                                    <tr class="table-active">
                                        <td colspan="3" class="text-end"><strong>Total:</strong></td>
                                        <td class="text-end"><strong>$<?php echo number_format($order['total'], 2); ?></strong></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
                
                <!-- Order Tracking -->
                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="card-title mb-4">
                            <i class="bi bi-geo-alt me-2"></i>Track Your Order
                        </h5>
                        
                        <div class="timeline">
                            <div class="timeline-item active">
                                <strong>Order Placed</strong>
                                <p class="text-muted small mb-0">Your order has been received</p>
                            </div>
                            <div class="timeline-item <?php echo in_array($order['status'], ['confirmed', 'preparing', 'ready', 'picked_up', 'on_the_way', 'delivered']) ? 'active' : ''; ?>">
                                <strong>Confirmed</strong>
                                <p class="text-muted small mb-0">Restaurant confirmed your order</p>
                            </div>
                            <div class="timeline-item <?php echo in_array($order['status'], ['preparing', 'ready', 'picked_up', 'on_the_way', 'delivered']) ? 'active' : ''; ?>">
                                <strong>Preparing</strong>
                                <p class="text-muted small mb-0">Your food is being prepared</p>
                            </div>
                            <div class="timeline-item <?php echo in_array($order['status'], ['ready', 'picked_up', 'on_the_way', 'delivered']) ? 'active' : ''; ?>">
                                <strong>Ready for Pickup</strong>
                                <p class="text-muted small mb-0">Order is ready</p>
                            </div>
                            <div class="timeline-item <?php echo in_array($order['status'], ['on_the_way', 'delivered']) ? 'active' : ''; ?>">
                                <strong>On the Way</strong>
                                <p class="text-muted small mb-0">Driver is on the way</p>
                            </div>
                            <div class="timeline-item <?php echo $order['status'] === 'delivered' ? 'active' : ''; ?>">
                                <strong>Delivered</strong>
                                <p class="text-muted small mb-0">Order delivered successfully</p>
                            </div>
                        </div>
                        
                        <div class="mt-4">
                            <a href="orders.php" class="btn btn-outline-primary">
                                <i class="bi bi-list-ul me-2"></i>View All Orders
                            </a>
                            <button class="btn btn-primary" onclick="trackOrder()">
                                <i class="bi bi-geo-alt me-2"></i>Track Live
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Action Buttons -->
                <div class="text-center">
                    <a href="home.php" class="btn btn-lg btn-primary me-2">
                        <i class="bi bi-house me-2"></i>Back to Home
                    </a>
                    <a href="store.php?id=<?php echo $order['merchant_id']; ?>" class="btn btn-lg btn-outline-primary">
                        <i class="bi bi-arrow-repeat me-2"></i>Order Again
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    function trackOrder() {
        // Implement real-time tracking
        window.location.href = 'track_order.php?id=<?php echo $order_id; ?>';
    }
    
    // Auto-refresh order status every 30 seconds
    setInterval(function() {
        fetch('ajax/ajax_track_order.php?order_id=<?php echo $order_id; ?>')
            .then(response => response.json())
            .then(data => {
                if (data.success && data.order.status !== '<?php echo $order['status']; ?>') {
                    location.reload();
                }
            });
    }, 30000);
    </script>
</body>
</html>
