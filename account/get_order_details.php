<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['merchant_id'])) {
    echo '<div class="alert alert-danger">Not authenticated</div>';
    exit();
}

include "../includes/db.php";
$merchant_id = $_SESSION['merchant_id'];
$order_id = intval($_GET['order_id'] ?? 0);

// Get order details
$order_sql = "SELECT DISTINCT o.*, u.first_name, u.last_name, u.phone
              FROM orders o
              JOIN order_items oi ON o.id = oi.order_id
              JOIN menu_items mi ON oi.menu_item_id = mi.id
              JOIN users u ON o.user_id = u.id
              WHERE o.id = ? AND mi.merchant_id = ?";
$stmt = mysqli_prepare($conn, $order_sql);
mysqli_stmt_bind_param($stmt, "ii", $order_id, $merchant_id);
mysqli_stmt_execute($stmt);
$order_result = mysqli_stmt_get_result($stmt);
$order = mysqli_fetch_assoc($order_result);

if (!$order) {
    echo '<div class="alert alert-danger">Order not found</div>';
    exit();
}

// Get order items
$items_sql = "SELECT oi.*, mi.name as item_name
              FROM order_items oi
              JOIN menu_items mi ON oi.menu_item_id = mi.id
              WHERE oi.order_id = ? AND mi.merchant_id = ?";
$stmt = mysqli_prepare($conn, $items_sql);
mysqli_stmt_bind_param($stmt, "ii", $order_id, $merchant_id);
mysqli_stmt_execute($stmt);
$items_result = mysqli_stmt_get_result($stmt);
?>

<div class="order-details">
    <div class="row mb-3">
        <div class="col-md-6">
            <h6>Order Information</h6>
            <p><strong>Order ID:</strong> #<?php echo $order['order_number'] ?? $order['id']; ?></p>
            <p><strong>Status:</strong> 
                <span class="badge bg-<?php 
                    switch($order['status']) {
                        case 'pending': echo 'warning'; break;
                        case 'preparing': echo 'info'; break;
                        case 'ready': echo 'primary'; break;
                        case 'delivering': echo 'secondary'; break;
                        case 'completed': echo 'success'; break;
                        case 'cancelled': echo 'danger'; break;
                        default: echo 'secondary';
                    }
                ?>"><?php echo ucfirst($order['status']); ?></span>
            </p>
            <p><strong>Order Date:</strong> <?php echo date('M j, Y h:i A', strtotime($order['created_at'])); ?></p>
            <p><strong>Order Type:</strong> <?php echo ucfirst($order['order_type'] ?? 'delivery'); ?></p>
        </div>
        <div class="col-md-6">
            <h6>Customer Information</h6>
            <p><strong>Name:</strong> <?php echo htmlspecialchars($order['first_name'] . ' ' . $order['last_name']); ?></p>
            <p><strong>Phone:</strong> <?php echo htmlspecialchars($order['phone']); ?></p>
            <?php if ($order['delivery_address']): ?>
            <p><strong>Address:</strong><br><?php echo nl2br(htmlspecialchars($order['delivery_address'])); ?></p>
            <?php endif; ?>
            <?php if ($order['delivery_instructions']): ?>
            <p><strong>Instructions:</strong><br><?php echo nl2br(htmlspecialchars($order['delivery_instructions'])); ?></p>
            <?php endif; ?>
        </div>
    </div>
    
    <h6>Order Items</h6>
    <div class="table-responsive">
        <table class="table table-sm">
            <thead>
                <tr>
                    <th>Item</th>
                    <th>Quantity</th>
                    <th>Price</th>
                    <th>Subtotal</th>
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
                    <td><?php echo $item['quantity']; ?></td>
                    <td>$<?php echo number_format($item['price'], 2); ?></td>
                    <td>$<?php echo number_format($item['subtotal'], 2); ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
            <tfoot>
                <tr>
                    <th colspan="3">Subtotal</th>
                    <th>$<?php echo number_format($order['subtotal'], 2); ?></th>
                </tr>
                <?php if ($order['delivery_fee'] > 0): ?>
                <tr>
                    <th colspan="3">Delivery Fee</th>
                    <th>$<?php echo number_format($order['delivery_fee'], 2); ?></th>
                </tr>
                <?php endif; ?>
                <?php if ($order['tax'] > 0): ?>
                <tr>
                    <th colspan="3">Tax</th>
                    <th>$<?php echo number_format($order['tax'], 2); ?></th>
                </tr>
                <?php endif; ?>
                <tr class="table-primary">
                    <th colspan="3">Total</th>
                    <th>$<?php echo number_format($order['total'], 2); ?></th>
                </tr>
            </tfoot>
        </table>
    </div>
    
    <div class="mt-3">
        <h6>Update Status</h6>
        <div class="btn-group" role="group">
            <button class="btn btn-outline-warning btn-sm" onclick="updateStatus('pending')">Pending</button>
            <button class="btn btn-outline-info btn-sm" onclick="updateStatus('preparing')">Preparing</button>
            <button class="btn btn-outline-primary btn-sm" onclick="updateStatus('ready')">Ready</button>
            <button class="btn btn-outline-secondary btn-sm" onclick="updateStatus('delivering')">Delivering</button>
            <button class="btn btn-outline-success btn-sm" onclick="updateStatus('completed')">Completed</button>
        </div>
    </div>
</div>

<script>
function updateStatus(status) {
    fetch('ajax/ajax_update_order_status.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({order_id: <?php echo $order_id; ?>, status: status})
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Order status updated!');
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    });
}
</script>