<?php
session_start();
include "../includes/db.php";
include "includes/auth_check.php";

$user_id = $_SESSION['user_id'];
$status_filter = $_GET['status'] ?? 'all';

// Build query
$where_conditions = ["o.user_id = ?"];
$params = [$user_id];
$param_types = "i";

if ($status_filter !== 'all') {
    $where_conditions[] = "o.status = ?";
    $params[] = $status_filter;
    $param_types .= "s";
}

$where_clause = implode(" AND ", $where_conditions);

// Get orders
$orders_sql = "SELECT 
                o.*,
                m.store_name,
                m.featured_image,
                COUNT(oi.id) as item_count,
                SUM(oi.quantity) as total_items
              FROM orders o
              LEFT JOIN merchants m ON o.merchant_id = m.merchant_id
              LEFT JOIN order_items oi ON o.id = oi.order_id
              WHERE $where_clause
              GROUP BY o.id
              ORDER BY o.created_at DESC";

$stmt = mysqli_prepare($conn, $orders_sql);
mysqli_stmt_bind_param($stmt, $param_types, ...$params);
mysqli_stmt_execute($stmt);
$orders_result = mysqli_stmt_get_result($stmt);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders - BeU Delivery</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        .orders-header {
            background: #f8f9fa;
            padding: 30px 0;
            margin-bottom: 30px;
        }
        
        .order-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            padding: 20px;
            margin-bottom: 20px;
            transition: all 0.3s;
        }
        
        .order-card:hover {
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        
        .store-image {
            width: 80px;
            height: 80px;
            border-radius: 8px;
            object-fit: cover;
        }
        
        .status-badge {
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 500;
        }
        
        .status-pending { background: #fff3cd; color: #856404; }
        .status-preparing { background: #d4edda; color: #155724; }
        .status-delivering { background: #cce5ff; color: #004085; }
        .status-completed { background: #d1ecf1; color: #0c5460; }
        .status-cancelled { background: #f8d7da; color: #721c24; }
        
        .order-tracking {
            position: relative;
            height: 4px;
            background: #e9ecef;
            border-radius: 2px;
            margin: 20px 0;
        }
        
        .tracking-progress {
            position: absolute;
            height: 100%;
            background: #00B26A;
            border-radius: 2px;
            transition: width 0.5s;
        }
        
        .tracking-dots {
            position: absolute;
            top: -8px;
            width: 100%;
            display: flex;
            justify-content: space-between;
        }
        
        .tracking-dot {
            width: 20px;
            height: 20px;
            background: white;
            border: 2px solid #e9ecef;
            border-radius: 50%;
        }
        
        .tracking-dot.active {
            background: #00B26A;
            border-color: #00B26A;
        }
        
        .filter-btn {
            padding: 8px 20px;
            margin-right: 10px;
            margin-bottom: 10px;
            background: white;
            border: 2px solid #e0e0e0;
            border-radius: 25px;
            color: var(--dark);
            font-weight: 500;
            transition: all 0.3s;
        }
        
        .filter-btn:hover,
        .filter-btn.active {
            background: #00B26A;
            color: white;
            border-color: #00B26A;
        }
    </style>
</head>
<body>
    <?php include "../partials/navbar.php"; ?>
    
    <!-- Orders Header -->
    <div class="orders-header">
        <div class="container">
            <h1 class="display-5 fw-bold mb-3">My Orders</h1>
            <p class="lead text-muted">Track and manage your food orders</p>
            
            <!-- Order Filters -->
            <div class="d-flex flex-wrap mt-4">
                <a href="?status=all" class="filter-btn <?php echo $status_filter == 'all' ? 'active' : ''; ?>">
                    All Orders
                </a>
                <a href="?status=pending" class="filter-btn <?php echo $status_filter == 'pending' ? 'active' : ''; ?>">
                    Pending
                </a>
                <a href="?status=preparing" class="filter-btn <?php echo $status_filter == 'preparing' ? 'active' : ''; ?>">
                    Preparing
                </a>
                <a href="?status=delivering" class="filter-btn <?php echo $status_filter == 'delivering' ? 'active' : ''; ?>">
                    Delivering
                </a>
                <a href="?status=completed" class="filter-btn <?php echo $status_filter == 'completed' ? 'active' : ''; ?>">
                    Completed
                </a>
            </div>
        </div>
    </div>
    
    <div class="container">
        <?php if (mysqli_num_rows($orders_result) > 0): ?>
            <?php while($order = mysqli_fetch_assoc($orders_result)): 
                // Calculate progress based on status
                $progress = 0;
                $active_dots = 0;
                
                switch ($order['status']) {
                    case 'pending':
                        $progress = 25;
                        $active_dots = 1;
                        break;
                    case 'preparing':
                        $progress = 50;
                        $active_dots = 2;
                        break;
                    case 'delivering':
                        $progress = 75;
                        $active_dots = 3;
                        break;
                    case 'completed':
                        $progress = 100;
                        $active_dots = 4;
                        break;
                    default:
                        $progress = 0;
                        $active_dots = 0;
                }
                
                // Get store image
                $store_image = !empty($order['featured_image']) ? 
                    '../uploads/merchants/featured/' . $order['featured_image'] : 
                    'https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?ixlib=rb-1.2.1&auto=format&fit=crop&w=200&q=80';
            ?>
            <div class="order-card">
                <div class="row align-items-center">
                    <!-- Store Info -->
                    <div class="col-md-3">
                        <div class="d-flex align-items-center">
                            <img src="<?php echo $store_image; ?>" alt="<?php echo htmlspecialchars($order['store_name']); ?>" 
                                 class="store-image me-3">
                            <div>
                                <h6 class="mb-1"><?php echo htmlspecialchars($order['store_name']); ?></h6>
                                <small class="text-muted">
                                    <?php echo date('M j, Y', strtotime($order['created_at'])); ?>
                                </small>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Order Details -->
                    <div class="col-md-3">
                        <div class="mb-1">
                            <strong>Order #<?php echo $order['id']; ?></strong>
                        </div>
                        <div class="text-muted">
                            <?php echo $order['item_count']; ?> items • $<?php echo number_format($order['total'], 2); ?>
                        </div>
                    </div>
                    
                    <!-- Status -->
                    <div class="col-md-2">
                        <span class="status-badge status-<?php echo $order['status']; ?>">
                            <?php echo ucfirst($order['status']); ?>
                        </span>
                    </div>
                    
                    <!-- Tracking -->
                    <div class="col-md-4">
                        <?php if ($order['status'] !== 'completed' && $order['status'] !== 'cancelled'): ?>
                        <div class="order-tracking">
                            <div class="tracking-progress" style="width: <?php echo $progress; ?>%"></div>
                            <div class="tracking-dots">
                                <?php for ($i = 1; $i <= 4; $i++): ?>
                                <div class="tracking-dot <?php echo $i <= $active_dots ? 'active' : ''; ?>"></div>
                                <?php endfor; ?>
                            </div>
                        </div>
                        <small class="text-muted">
                            <?php
                            switch ($order['status']) {
                                case 'pending': echo 'Order received'; break;
                                case 'preparing': echo 'Store preparing your order'; break;
                                case 'delivering': echo 'Driver on the way'; break;
                                default: echo 'Order processing';
                            }
                            ?>
                        </small>
                        <?php else: ?>
                        <div class="text-center">
                            <i class="bi bi-<?php echo $order['status'] == 'completed' ? 'check-circle text-success' : 'x-circle text-danger'; ?> display-6"></i>
                            <div class="mt-2">
                                <?php echo $order['status'] == 'completed' ? 'Delivered Successfully' : 'Order Cancelled'; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Action Buttons -->
                <div class="row mt-3">
                    <div class="col-12">
                        <div class="d-flex justify-content-end gap-2">
                            <a href="order_confirmation.php?id=<?php echo $order['id']; ?>" 
                               class="btn btn-outline-primary btn-sm">
                                <i class="bi bi-eye me-1"></i> View Details
                            </a>
                            
                            <?php if ($order['status'] === 'pending' || $order['status'] === 'preparing'): ?>
                            <button class="btn btn-outline-warning btn-sm" onclick="cancelOrder(<?php echo $order['id']; ?>)">
                                <i class="bi bi-x-circle me-1"></i> Cancel Order
                            </button>
                            <?php endif; ?>
                            
                            <?php if ($order['status'] === 'completed'): ?>
                            <button class="btn btn-outline-success btn-sm" onclick="reorder(<?php echo $order['id']; ?>)">
                                <i class="bi bi-arrow-repeat me-1"></i> Reorder
                            </button>
                            <button class="btn btn-outline-info btn-sm" onclick="rateOrder(<?php echo $order['id']; ?>)">
                                <i class="bi bi-star me-1"></i> Rate Order
                            </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="text-center py-5">
                <i class="bi bi-bag-x display-1 text-muted"></i>
                <h3 class="mt-4">No orders found</h3>
                <p class="text-muted">
                    <?php echo $status_filter !== 'all' ? 
                        'You have no ' . $status_filter . ' orders.' : 
                        'You haven\'t placed any orders yet.'; ?>
                </p>
                <a href="home.php" class="btn btn-primary">
                    <i class="bi bi-basket3 me-2"></i> Browse Stores
                </a>
            </div>
        <?php endif; ?>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    function cancelOrder(orderId) {
        if (confirm('Are you sure you want to cancel this order?')) {
            fetch('ajax_cancel_order.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'order_id=' + orderId
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Order cancelled successfully!');
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            });
        }
    }
    
    function reorder(orderId) {
        if (confirm('Add all items from this order to cart?')) {
            fetch('ajax_reorder.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'order_id=' + orderId
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Items added to cart!');
                    window.location.href = 'cart.php';
                } else {
                    alert('Error: ' + data.message);
                }
            });
        }
    }
    
    function rateOrder(orderId) {
        // Open rating modal or redirect to rating page
        window.location.href = 'rate_order.php?id=' + orderId;
    }
    </script>
</body>
</html>