<?php
session_start();
require_once "admin_auth.php";
include "../includes/db.php";

// Get filters
$status = isset($_GET['status']) ? mysqli_real_escape_string($conn, $_GET['status']) : 'all';
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Build where conditions
$where = [];
if ($status !== 'all') {
    $where[] = "o.status = '$status'";
}
if (!empty($search)) {
    $where[] = "(o.id LIKE '%$search%' OR u.first_name LIKE '%$search%' OR u.last_name LIKE '%$search%' OR m.store_name LIKE '%$search%')";
}
$where_clause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

// Get counts for filter tabs
$count_sql = "SELECT 
    COUNT(CASE WHEN o.status = 'pending' THEN 1 END) as pending,
    COUNT(CASE WHEN o.status = 'confirmed' THEN 1 END) as confirmed,
    COUNT(CASE WHEN o.status = 'preparing' THEN 1 END) as preparing,
    COUNT(CASE WHEN o.status = 'ready' THEN 1 END) as ready,
    COUNT(CASE WHEN o.status = 'out_for_delivery' THEN 1 END) as out_for_delivery,
    COUNT(CASE WHEN o.status = 'delivered' THEN 1 END) as delivered,
    COUNT(CASE WHEN o.status = 'cancelled' THEN 1 END) as cancelled,
    COUNT(*) as total
    FROM orders o";
$count_result = mysqli_query($conn, $count_sql);
$counts = mysqli_fetch_assoc($count_result) ?: array_fill_keys(['pending','confirmed','preparing','ready','out_for_delivery','delivered','cancelled','total'], 0);

// Get orders with pagination
$orders_sql = "SELECT 
    o.*,
    u.first_name as customer_first,
    u.last_name as customer_last,
    u.email as customer_email,
    u.phone as customer_phone,
    m.store_name,
    m.store_address,
    CONCAT(ua.street_address, ', ', ua.city) as delivery_address
    FROM orders o
    LEFT JOIN users u ON o.user_id = u.id
    LEFT JOIN merchants m ON o.merchant_id = m.merchant_id
    LEFT JOIN user_addresses ua ON o.delivery_address_id = ua.id
    $where_clause
    ORDER BY o.created_at DESC
    LIMIT $offset, $per_page";
$orders_result = mysqli_query($conn, $orders_sql);

// Get total count for pagination
$total_sql = "SELECT COUNT(*) as total FROM orders o $where_clause";
$total_result = mysqli_query($conn, $total_sql);
$total_row = mysqli_fetch_assoc($total_result);
$total_orders = $total_row['total'];
$total_pages = ceil($total_orders / $per_page);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orders Management - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        :root {
            --primary: #000000;
            --secondary: #06C167;
        }
        
        body {
            background-color: #f5f7fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .sidebar {
            width: 260px;
            background: var(--primary);
            color: white;
            position: fixed;
            height: 100vh;
            padding-top: 20px;
            overflow-y: auto;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
            z-index: 1000;
        }
        
        .main-content {
            margin-left: 260px;
            padding: 20px;
            min-height: 100vh;
        }
        
        .header-card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        }
        
        .filter-tabs .nav-link {
            border-radius: 20px;
            padding: 8px 20px;
            margin-right: 10px;
            color: #6c757d;
            border: 1px solid #dee2e6;
        }
        
        .filter-tabs .nav-link.active {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }
        
        .orders-table {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        }
        
        .status-badge {
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .badge-pending { background: #fff3cd; color: #856404; }
        .badge-confirmed { background: #cfe2ff; color: #084298; }
        .badge-preparing { background: #e7f1ff; color: #0a58ca; }
        .badge-ready { background: #d1e7dd; color: #0f5132; }
        .badge-out_for_delivery { background: #cff4fc; color: #055160; }
        .badge-delivered { background: #d4edda; color: #155724; }
        .badge-cancelled { background: #f8d7da; color: #721c24; }
    </style>
</head>
<body>
    <?php include "admin_sidebar.php"; ?>
    
    <div class="main-content">
        <div class="header-card">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="mb-1">Orders Management</h2>
                    <p class="text-muted mb-0">Total: <?php echo $counts['total']; ?> orders</p>
                </div>
                <div>
                    <button class="btn btn-primary" onclick="window.location.reload()">
                        <i class="bi bi-arrow-clockwise"></i> Refresh
                    </button>
                </div>
            </div>
            
            <!-- Filter Tabs -->
            <div class="filter-tabs mb-4">
                <ul class="nav nav-pills">
                    <li class="nav-item">
                        <a class="nav-link <?php echo $status === 'all' ? 'active' : ''; ?>" 
                           href="?status=all&search=<?php echo urlencode($search); ?>">
                            All (<?php echo $counts['total']; ?>)
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $status === 'pending' ? 'active' : ''; ?>" 
                           href="?status=pending&search=<?php echo urlencode($search); ?>">
                            Pending (<?php echo $counts['pending']; ?>)
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $status === 'preparing' ? 'active' : ''; ?>" 
                           href="?status=preparing&search=<?php echo urlencode($search); ?>">
                            Preparing (<?php echo $counts['preparing']; ?>)
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $status === 'out_for_delivery' ? 'active' : ''; ?>" 
                           href="?status=out_for_delivery&search=<?php echo urlencode($search); ?>">
                            Out for Delivery (<?php echo $counts['out_for_delivery']; ?>)
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $status === 'delivered' ? 'active' : ''; ?>" 
                           href="?status=delivered&search=<?php echo urlencode($search); ?>">
                            Delivered (<?php echo $counts['delivered']; ?>)
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $status === 'cancelled' ? 'active' : ''; ?>" 
                           href="?status=cancelled&search=<?php echo urlencode($search); ?>">
                            Cancelled (<?php echo $counts['cancelled']; ?>)
                        </a>
                    </li>
                </ul>
            </div>
            
            <!-- Search Bar -->
            <form method="GET" class="mb-4">
                <div class="row">
                    <div class="col-md-8">
                        <input type="hidden" name="status" value="<?php echo $status; ?>">
                        <div class="input-group">
                            <input type="text" name="search" class="form-control" 
                                   placeholder="Search by order ID, customer name, or store..." 
                                   value="<?php echo htmlspecialchars($search); ?>">
                            <button class="btn btn-outline-primary" type="submit">
                                <i class="bi bi-search"></i> Search
                            </button>
                            <?php if (!empty($search)): ?>
                            <a href="?status=<?php echo $status; ?>" class="btn btn-outline-secondary">
                                <i class="bi bi-x-circle"></i> Clear
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </form>
        </div>
        
        <!-- Orders Table -->
        <div class="orders-table">
            <?php if ($orders_result && mysqli_num_rows($orders_result) > 0): ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Customer</th>
                            <th>Store</th>
                            <th>Items</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($order = mysqli_fetch_assoc($orders_result)): 
                            $status_class = [
                                'pending' => 'pending',
                                'confirmed' => 'confirmed',
                                'preparing' => 'preparing',
                                'ready' => 'ready',
                                'out_for_delivery' => 'out_for_delivery',
                                'delivered' => 'delivered',
                                'cancelled' => 'cancelled'
                            ][$order['status']] ?? 'pending';
                        ?>
                        <tr>
                            <td>
                                <strong>#<?php echo $order['id']; ?></strong>
                            </td>
                            <td>
                                <?php echo htmlspecialchars($order['customer_first'] . ' ' . $order['customer_last']); ?>
                                <br><small class="text-muted"><?php echo htmlspecialchars($order['customer_phone']); ?></small>
                            </td>
                            <td>
                                <?php echo htmlspecialchars($order['store_name']); ?>
                            </td>
                            <td>
                                <?php 
                                $items_sql = "SELECT COUNT(*) as count FROM order_items WHERE order_id = {$order['id']}";
                                $items_result = mysqli_query($conn, $items_sql);
                                $items = mysqli_fetch_assoc($items_result);
                                echo $items['count'] . ' items';
                                ?>
                            </td>
                            <td>
                                <strong>$<?php echo number_format($order['total'], 2); ?></strong>
                            </td>
                            <td>
                                <span class="status-badge badge-<?php echo $status_class; ?>">
                                    <?php echo strtoupper(str_replace('_', ' ', $order['status'])); ?>
                                </span>
                            </td>
                            <td>
                                <?php echo date('M j, Y', strtotime($order['created_at'])); ?>
                                <br><small class="text-muted"><?php echo date('g:i A', strtotime($order['created_at'])); ?></small>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-outline-primary" onclick="viewOrder(<?php echo $order['id']; ?>)">
                                    <i class="bi bi-eye"></i> View
                                </button>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
            <nav aria-label="Page navigation" class="mt-4">
                <ul class="pagination justify-content-center">
                    <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?status=<?php echo $status; ?>&search=<?php echo urlencode($search); ?>&page=<?php echo $page - 1; ?>">
                            <i class="bi bi-chevron-left"></i>
                        </a>
                    </li>
                    
                    <?php
                    $start_page = max(1, $page - 2);
                    $end_page = min($total_pages, $start_page + 4);
                    
                    for ($i = $start_page; $i <= $end_page; $i++): ?>
                    <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                        <a class="page-link" href="?status=<?php echo $status; ?>&search=<?php echo urlencode($search); ?>&page=<?php echo $i; ?>">
                            <?php echo $i; ?>
                        </a>
                    </li>
                    <?php endfor; ?>
                    
                    <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?status=<?php echo $status; ?>&search=<?php echo urlencode($search); ?>&page=<?php echo $page + 1; ?>">
                            <i class="bi bi-chevron-right"></i>
                        </a>
                    </li>
                </ul>
            </nav>
            <?php endif; ?>
            
            <?php else: ?>
            <div class="text-center py-5">
                <i class="bi bi-bag-x" style="font-size: 48px; color: #6c757d;"></i>
                <h5 class="mt-3">No orders found</h5>
                <?php if (!empty($search)): ?>
                <p class="text-muted">No results for "<?php echo htmlspecialchars($search); ?>"</p>
                <a href="?status=<?php echo $status; ?>" class="btn btn-primary">Clear Search</a>
                <?php else: ?>
                <p class="text-muted">No orders have been placed yet.</p>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Order Details Modal -->
    <div class="modal fade" id="orderDetailsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Order Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="orderDetailsContent">
                    <div class="text-center py-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function viewOrder(orderId) {
            const modal = new bootstrap.Modal(document.getElementById('orderDetailsModal'));
            modal.show();
            
            // Fetch order details
            fetch('get_order_details.php?id=' + orderId)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        displayOrderDetails(data.order);
                    } else {
                        document.getElementById('orderDetailsContent').innerHTML = 
                            '<div class="alert alert-danger">Error loading order details</div>';
                    }
                })
                .catch(error => {
                    document.getElementById('orderDetailsContent').innerHTML = 
                        '<div class="alert alert-danger">Error: ' + error.message + '</div>';
                });
        }
        
        function displayOrderDetails(order) {
            const statusBadges = {
                'pending': 'warning',
                'confirmed': 'info',
                'preparing': 'primary',
                'ready': 'success',
                'out_for_delivery': 'info',
                'delivered': 'success',
                'cancelled': 'danger'
            };
            
            let itemsHtml = '';
            order.items.forEach(item => {
                itemsHtml += `
                    <tr>
                        <td>${item.item_name}</td>
                        <td class="text-center">${item.quantity}</td>
                        <td class="text-end">$${parseFloat(item.price).toFixed(2)}</td>
                        <td class="text-end"><strong>$${parseFloat(item.subtotal).toFixed(2)}</strong></td>
                    </tr>
                `;
            });
            
            const html = `
                <div class="row mb-4">
                    <div class="col-md-6">
                        <h6 class="text-muted mb-2">Order Information</h6>
                        <p class="mb-1"><strong>Order #:</strong> ${order.order_number}</p>
                        <p class="mb-1"><strong>Date:</strong> ${new Date(order.created_at).toLocaleString()}</p>
                        <p class="mb-1"><strong>Status:</strong> 
                            <span class="badge bg-${statusBadges[order.status] || 'secondary'}">
                                ${order.status.toUpperCase().replace('_', ' ')}
                            </span>
                        </p>
                        <p class="mb-1"><strong>Payment:</strong> ${order.payment_method.toUpperCase()}</p>
                        <p class="mb-1"><strong>Type:</strong> ${order.order_type.toUpperCase()}</p>
                    </div>
                    <div class="col-md-6">
                        <h6 class="text-muted mb-2">Customer Information</h6>
                        <p class="mb-1"><strong>Name:</strong> ${order.customer_name}</p>
                        <p class="mb-1"><strong>Phone:</strong> ${order.customer_phone || 'N/A'}</p>
                        <p class="mb-1"><strong>Email:</strong> ${order.customer_email || 'N/A'}</p>
                    </div>
                </div>
                
                <div class="row mb-4">
                    <div class="col-md-6">
                        <h6 class="text-muted mb-2">Store Information</h6>
                        <p class="mb-1"><strong>${order.store_name}</strong></p>
                        <p class="mb-1 text-muted">${order.store_address}</p>
                    </div>
                    <div class="col-md-6">
                        <h6 class="text-muted mb-2">Delivery Address</h6>
                        <p class="mb-1">${order.delivery_address}</p>
                        ${order.delivery_instructions ? `<p class="mb-1 text-muted"><em>${order.delivery_instructions}</em></p>` : ''}
                    </div>
                </div>
                
                <h6 class="text-muted mb-3">Order Items</h6>
                <div class="table-responsive mb-4">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Item</th>
                                <th class="text-center">Qty</th>
                                <th class="text-end">Price</th>
                                <th class="text-end">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${itemsHtml}
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="3" class="text-end"><strong>Subtotal:</strong></td>
                                <td class="text-end">$${parseFloat(order.subtotal).toFixed(2)}</td>
                            </tr>
                            <tr>
                                <td colspan="3" class="text-end"><strong>Delivery Fee:</strong></td>
                                <td class="text-end">$${parseFloat(order.delivery_fee).toFixed(2)}</td>
                            </tr>
                            <tr>
                                <td colspan="3" class="text-end"><strong>Tax:</strong></td>
                                <td class="text-end">$${parseFloat(order.tax).toFixed(2)}</td>
                            </tr>
                            ${order.discount > 0 ? `
                            <tr>
                                <td colspan="3" class="text-end"><strong>Discount:</strong></td>
                                <td class="text-end text-success">-$${parseFloat(order.discount).toFixed(2)}</td>
                            </tr>
                            ` : ''}
                            <tr class="table-active">
                                <td colspan="3" class="text-end"><strong>Total:</strong></td>
                                <td class="text-end"><strong>$${parseFloat(order.total).toFixed(2)}</strong></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            `;
            
            document.getElementById('orderDetailsContent').innerHTML = html;
        }
    </script>
</body>
</html>
