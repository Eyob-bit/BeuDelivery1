<?php
session_start();
require_once "admin_auth.php";
include "../includes/db.php";

// Get date range filter
$period = isset($_GET['period']) ? $_GET['period'] : 'today';

// Calculate date range
$today = date('Y-m-d');
$start_date = $today;
$end_date = $today;

switch ($period) {
    case 'today':
        $start_date = $today;
        break;
    case 'yesterday':
        $start_date = date('Y-m-d', strtotime('-1 day'));
        $end_date = $start_date;
        break;
    case 'week':
        $start_date = date('Y-m-d', strtotime('-7 days'));
        break;
    case 'month':
        $start_date = date('Y-m-d', strtotime('-30 days'));
        break;
    case 'year':
        $start_date = date('Y-m-d', strtotime('-365 days'));
        break;
}

// Revenue Statistics
$revenue_sql = "SELECT 
    COUNT(*) as total_orders,
    COALESCE(SUM(total), 0) as total_revenue,
    COALESCE(AVG(total), 0) as avg_order_value,
    COALESCE(SUM(CASE WHEN status = 'delivered' THEN total ELSE 0 END), 0) as completed_revenue,
    COALESCE(SUM(CASE WHEN status = 'cancelled' THEN total ELSE 0 END), 0) as cancelled_revenue
    FROM orders
    WHERE DATE(created_at) BETWEEN '$start_date' AND '$end_date'";
$revenue_result = mysqli_query($conn, $revenue_sql);
$revenue = mysqli_fetch_assoc($revenue_result);

// Orders by Status
$status_sql = "SELECT 
    status,
    COUNT(*) as count,
    COALESCE(SUM(total), 0) as revenue
    FROM orders
    WHERE DATE(created_at) BETWEEN '$start_date' AND '$end_date'
    GROUP BY status
    ORDER BY count DESC";
$status_result = mysqli_query($conn, $status_sql);

// Top Merchants
$merchants_sql = "SELECT 
    m.merchant_id,
    m.store_name,
    COUNT(o.id) as order_count,
    COALESCE(SUM(o.total), 0) as revenue,
    COALESCE(AVG(o.total), 0) as avg_order
    FROM merchants m
    LEFT JOIN orders o ON m.merchant_id = o.merchant_id 
        AND DATE(o.created_at) BETWEEN '$start_date' AND '$end_date'
    GROUP BY m.merchant_id
    HAVING order_count > 0
    ORDER BY revenue DESC
    LIMIT 10";
$merchants_result = mysqli_query($conn, $merchants_sql);

// Daily Revenue Chart Data
$daily_sql = "SELECT 
    DATE(created_at) as date,
    COUNT(*) as orders,
    COALESCE(SUM(total), 0) as revenue
    FROM orders
    WHERE DATE(created_at) BETWEEN '$start_date' AND '$end_date'
    GROUP BY DATE(created_at)
    ORDER BY date ASC";
$daily_result = mysqli_query($conn, $daily_sql);

$chart_dates = [];
$chart_revenue = [];
$chart_orders = [];

while ($row = mysqli_fetch_assoc($daily_result)) {
    $chart_dates[] = date('M j', strtotime($row['date']));
    $chart_revenue[] = (float)$row['revenue'];
    $chart_orders[] = (int)$row['orders'];
}

// User Growth
$users_sql = "SELECT 
    COUNT(*) as total_users,
    COUNT(CASE WHEN DATE(created_at) BETWEEN '$start_date' AND '$end_date' THEN 1 END) as new_users
    FROM users";
$users_result = mysqli_query($conn, $users_sql);
$users = mysqli_fetch_assoc($users_result);

// Popular Items
$items_sql = "SELECT 
    oi.item_name,
    COUNT(*) as order_count,
    SUM(oi.quantity) as total_quantity,
    COALESCE(SUM(oi.subtotal), 0) as revenue
    FROM order_items oi
    JOIN orders o ON oi.order_id = o.id
    WHERE DATE(o.created_at) BETWEEN '$start_date' AND '$end_date'
    GROUP BY oi.item_name
    ORDER BY total_quantity DESC
    LIMIT 10";
$items_result = mysqli_query($conn, $items_sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports & Analytics - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
        
        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            transition: transform 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: white;
        }
        
        .chart-card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        }
        
        .period-selector .btn {
            border-radius: 20px;
            margin-right: 10px;
        }
    </style>
</head>
<body>
    <?php include "admin_sidebar.php"; ?>
    
    <div class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2>Reports & Analytics</h2>
                <p class="text-muted mb-0">Performance insights and statistics</p>
            </div>
            <div class="period-selector">
                <a href="?period=today" class="btn btn-<?php echo $period === 'today' ? 'primary' : 'outline-primary'; ?>">Today</a>
                <a href="?period=yesterday" class="btn btn-<?php echo $period === 'yesterday' ? 'primary' : 'outline-primary'; ?>">Yesterday</a>
                <a href="?period=week" class="btn btn-<?php echo $period === 'week' ? 'primary' : 'outline-primary'; ?>">7 Days</a>
                <a href="?period=month" class="btn btn-<?php echo $period === 'month' ? 'primary' : 'outline-primary'; ?>">30 Days</a>
                <a href="?period=year" class="btn btn-<?php echo $period === 'year' ? 'primary' : 'outline-primary'; ?>">Year</a>
            </div>
        </div>
        
        <!-- Revenue Statistics -->
        <div class="row">
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="d-flex align-items-center mb-3">
                        <div class="stat-icon" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                            <i class="bi bi-currency-dollar"></i>
                        </div>
                        <div class="ms-3">
                            <h6 class="text-muted mb-0">Total Revenue</h6>
                            <h3 class="mb-0">$<?php echo number_format($revenue['total_revenue'], 2); ?></h3>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="d-flex align-items-center mb-3">
                        <div class="stat-icon" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                            <i class="bi bi-bag-check"></i>
                        </div>
                        <div class="ms-3">
                            <h6 class="text-muted mb-0">Total Orders</h6>
                            <h3 class="mb-0"><?php echo number_format($revenue['total_orders']); ?></h3>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="d-flex align-items-center mb-3">
                        <div class="stat-icon" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                            <i class="bi bi-graph-up"></i>
                        </div>
                        <div class="ms-3">
                            <h6 class="text-muted mb-0">Avg Order Value</h6>
                            <h3 class="mb-0">$<?php echo number_format($revenue['avg_order_value'], 2); ?></h3>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="d-flex align-items-center mb-3">
                        <div class="stat-icon" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);">
                            <i class="bi bi-people"></i>
                        </div>
                        <div class="ms-3">
                            <h6 class="text-muted mb-0">New Users</h6>
                            <h3 class="mb-0"><?php echo number_format($users['new_users']); ?></h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Revenue Chart -->
        <div class="row">
            <div class="col-md-8">
                <div class="chart-card">
                    <h5 class="mb-4">Revenue Trend</h5>
                    <canvas id="revenueChart" height="80"></canvas>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="chart-card">
                    <h5 class="mb-4">Orders by Status</h5>
                    <canvas id="statusChart"></canvas>
                </div>
            </div>
        </div>
        
        <!-- Top Merchants -->
        <div class="row">
            <div class="col-md-6">
                <div class="chart-card">
                    <h5 class="mb-4">Top Performing Merchants</h5>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Store</th>
                                    <th class="text-center">Orders</th>
                                    <th class="text-end">Revenue</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                mysqli_data_seek($merchants_result, 0);
                                while ($merchant = mysqli_fetch_assoc($merchants_result)): 
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($merchant['store_name']); ?></td>
                                    <td class="text-center"><?php echo $merchant['order_count']; ?></td>
                                    <td class="text-end"><strong>$<?php echo number_format($merchant['revenue'], 2); ?></strong></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="chart-card">
                    <h5 class="mb-4">Popular Items</h5>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Item</th>
                                    <th class="text-center">Sold</th>
                                    <th class="text-end">Revenue</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($item = mysqli_fetch_assoc($items_result)): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($item['item_name']); ?></td>
                                    <td class="text-center"><?php echo $item['total_quantity']; ?></td>
                                    <td class="text-end"><strong>$<?php echo number_format($item['revenue'], 2); ?></strong></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Revenue Chart
        const revenueCtx = document.getElementById('revenueChart').getContext('2d');
        new Chart(revenueCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($chart_dates); ?>,
                datasets: [{
                    label: 'Revenue ($)',
                    data: <?php echo json_encode($chart_revenue); ?>,
                    borderColor: '#667eea',
                    backgroundColor: 'rgba(102, 126, 234, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '$' + value.toFixed(0);
                            }
                        }
                    }
                }
            }
        });
        
        // Status Chart
        const statusCtx = document.getElementById('statusChart').getContext('2d');
        <?php
        mysqli_data_seek($status_result, 0);
        $status_labels = [];
        $status_data = [];
        while ($status = mysqli_fetch_assoc($status_result)) {
            $status_labels[] = ucfirst(str_replace('_', ' ', $status['status']));
            $status_data[] = (int)$status['count'];
        }
        ?>
        new Chart(statusCtx, {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode($status_labels); ?>,
                datasets: [{
                    data: <?php echo json_encode($status_data); ?>,
                    backgroundColor: [
                        '#ffc107',
                        '#0dcaf0',
                        '#0d6efd',
                        '#198754',
                        '#20c997',
                        '#28a745',
                        '#dc3545'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    </script>
</body>
</html>
