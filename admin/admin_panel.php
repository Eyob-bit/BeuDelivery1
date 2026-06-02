<?php
// admin_panel.php - ADMIN DASHBOARD
session_start();
require_once "admin_auth.php";
include "../includes/db.php";

// Get dashboard stats
// Total merchants
$merchants_sql = "SELECT 
    COUNT(*) as total,
    COUNT(CASE WHEN status = 'under_review' THEN 1 END) as pending,
    COUNT(CASE WHEN status = 'active' THEN 1 END) as active,
    COUNT(CASE WHEN status = 'inactive' THEN 1 END) as inactive
    FROM merchants";
$merchants_result = mysqli_query($conn, $merchants_sql);
$merchants_stats = mysqli_fetch_assoc($merchants_result);

// Total orders
$orders_sql = "SELECT 
    COUNT(*) as total,
    COUNT(CASE WHEN DATE(created_at) = CURDATE() THEN 1 END) as today,
    SUM(CASE WHEN DATE(created_at) = CURDATE() THEN total ELSE 0 END) as today_revenue,
    SUM(total) as total_revenue
    FROM orders";
$orders_result = mysqli_query($conn, $orders_sql);
$orders_stats = mysqli_fetch_assoc($orders_result);

// Total users
$users_sql = "SELECT 
    COUNT(*) as total,
    COUNT(CASE WHEN DATE(created_at) = CURDATE() THEN 1 END) as today
    FROM users";
$users_result = mysqli_query($conn, $users_sql);
$users_stats = mysqli_fetch_assoc($users_result);

// Recent pending merchants
$recent_merchants_sql = "SELECT 
    m.merchant_id,
    m.store_name,
    m.store_address,
    m.status,
    m.created_at,
    u.first_name,
    u.last_name,
    u.email,
    DATEDIFF(CURDATE(), m.created_at) as days_ago
    FROM merchants m
    JOIN users u ON m.user_id = u.id
    WHERE m.status = 'under_review'
    ORDER BY m.created_at DESC
    LIMIT 5";
$recent_merchants = mysqli_query($conn, $recent_merchants_sql);

// Recent orders
$recent_orders_sql = "SELECT 
    o.id,
    o.total,
    o.status,
    o.created_at,
    u.first_name,
    u.last_name,
    r.name as restaurant_name
    FROM orders o
    JOIN users u ON o.user_id = u.id
    JOIN restaurants r ON o.restaurant_id = r.id
    ORDER BY o.created_at DESC
    LIMIT 5";
$recent_orders = mysqli_query($conn, $recent_orders_sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - BeU Delivery</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        :root {
            --primary: #000000;
            --secondary: #06C167;
            --light: #f8f9fa;
            --dark: #343a40;
            --success: #28a745;
            --warning: #ffc107;
            --danger: #dc3545;
            --info: #17a2b8;
        }
        
        body {
            background-color: #f5f7fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
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
        
        .sidebar-header {
            padding: 0 20px 20px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .admin-profile {
            background: rgba(255,255,255,0.1);
            border-radius: 10px;
            padding: 15px;
            margin: 15px;
        }
        
        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            border-left: 5px solid var(--primary);
            transition: transform 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 12px rgba(0,0,0,0.1);
        }
        
        .stat-card.pending { border-left-color: var(--warning); }
        .stat-card.active { border-left-color: var(--success); }
        .stat-card.revenue { border-left-color: var(--secondary); }
        .stat-card.users { border-left-color: var(--info); }
        
        .stat-number {
            font-size: 32px;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 5px;
        }
        
        .stat-label {
            font-size: 14px;
            color: #6c757d;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .section-card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        }
        
        .section-title {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 20px;
            color: var(--dark);
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .table th {
            background-color: #f8f9fa;
            font-weight: 600;
            color: var(--dark);
        }
        
        .badge-status {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .badge-pending { background: #fff3cd; color: #856404; }
        .badge-active { background: #d4edda; color: #155724; }
        .badge-inactive { background: #f8d7da; color: #721c24; }
        
        .nav-link {
            color: rgba(255,255,255,0.8);
            padding: 12px 20px;
            margin: 2px 0;
            border-radius: 8px;
            transition: all 0.3s;
        }
        
        .nav-link:hover, .nav-link.active {
            color: white;
            background: rgba(255,255,255,0.1);
        }
        
        .nav-link i {
            width: 20px;
            text-align: center;
            margin-right: 10px;
        }
        
        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                position: relative;
                height: auto;
            }
            
            .main-content {
                margin-left: 0;
            }
        }
        
        .empty-state {
            text-align: center;
            padding: 40px 20px;
        }
        
        .empty-state i {
            font-size: 48px;
            color: #6c757d;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <h3 class="mb-1">BeU Delivery</h3>
            <small class="text-muted">Admin Panel</small>
        </div>
        
        <div class="admin-profile">
            <div class="d-flex align-items-center">
                <?php
                // Get admin profile photo
                $admin_id = $_SESSION['user_id'];
                $admin_photo_sql = "SELECT profile_image FROM users WHERE id = $admin_id";
                $admin_photo_result = mysqli_query($conn, $admin_photo_sql);
                $admin_data = mysqli_fetch_assoc($admin_photo_result);
                $profile_image = $admin_data['profile_image'] ?? null;
                ?>
                
                <?php if ($profile_image && file_exists($profile_image)): ?>
                <img src="../<?php echo htmlspecialchars($profile_image); ?>" 
                     class="rounded-circle" 
                     style="width: 50px; height: 50px; object-fit: cover;"
                     alt="Admin Photo">
                <?php else: ?>
                <div class="rounded-circle bg-secondary d-flex align-items-center justify-content-center" 
                     style="width: 50px; height: 50px;">
                    <i class="bi bi-person-fill" style="font-size: 24px;"></i>
                </div>
                <?php endif; ?>
                
                <div class="ms-3">
                    <h6 class="mb-0"><?php echo htmlspecialchars($_SESSION['user_name']); ?></h6>
                    <small class="text-muted">Administrator</small>
                </div>
            </div>
        </div>
        
        <nav class="nav flex-column px-3">
            <a href="admin_panel.php" class="nav-link active">
                <i class="bi bi-speedometer2"></i> Dashboard
            </a>
            <a href="admin_merchants.php" class="nav-link">
                <i class="bi bi-shop"></i> All Merchants
            </a>
            <a href="orders.php" class="nav-link">
                <i class="bi bi-bag-check"></i> Orders
            </a>
            <a href="admin_users.php" class="nav-link">
                <i class="bi bi-people"></i> Users
            </a>
            <a href="admin_reports.php" class="nav-link">
                <i class="bi bi-bar-chart"></i> Reports
            </a>
            <a href="restaurants.php" class="nav-link">
                <i class="bi bi-building"></i> Restaurants
            </a>
            <div class="mt-4 pt-3 border-top border-secondary">
                <a href="admin_settings.php" class="nav-link">
                    <i class="bi bi-gear"></i> Settings
                </a>
                <a href="../auth/logout.php" class="nav-link text-danger">
                    <i class="bi bi-box-arrow-right"></i> Logout
                </a>
            </div>
        </nav>
                    <i class="bi bi-box-arrow-right"></i> Logout
                </a>
            </div>
        </nav>
    </div>
    
    <!-- Main Content -->
    <div class="main-content">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="mb-1">Dashboard</h2>
                <p class="text-muted mb-0">Welcome back, <?php echo htmlspecialchars($_SESSION['user_name']); ?>!</p>
            </div>
            <div class="text-muted">
                <i class="bi bi-calendar3"></i> <?php echo date('l, F j, Y'); ?>
            </div>
        </div>
        
        <!-- Stats Cards -->
        <div class="row">
            <div class="col-xl-3 col-md-6">
                <div class="stat-card pending">
                    <div class="stat-number"><?php echo $merchants_stats['pending'] ?? 0; ?></div>
                    <div class="stat-label">Pending Review</div>
                    <?php if (($merchants_stats['pending'] ?? 0) > 0): ?>
                    <div class="stat-label text-warning">
                        <i class="bi bi-exclamation-circle-fill"></i> Needs attention
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="col-xl-3 col-md-6">
                <div class="stat-card active">
                    <div class="stat-number"><?php echo $orders_stats['today'] ?? 0; ?></div>
                    <div class="stat-label">Today's Orders</div>
                    <div class="stat-label">
                        $<?php echo number_format($orders_stats['today_revenue'] ?? 0, 2); ?>
                    </div>
                </div>
            </div>
            
            <div class="col-xl-3 col-md-6">
                <div class="stat-card revenue">
                    <div class="stat-number">$<?php echo number_format($orders_stats['total_revenue'] ?? 0, 2); ?></div>
                    <div class="stat-label">Total Revenue</div>
                    <div class="stat-label">
                        <?php echo $orders_stats['total'] ?? 0; ?> orders
                    </div>
                </div>
            </div>
            
            <div class="col-xl-3 col-md-6">
                <div class="stat-card users">
                    <div class="stat-number"><?php echo $users_stats['today'] ?? 0; ?></div>
                    <div class="stat-label">New Users Today</div>
                    <div class="stat-label">
                        <?php echo $users_stats['total'] ?? 0; ?> total users
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Recent Activity -->
        <div class="row">
            <!-- Pending Merchants -->
            <div class="col-lg-6">
                <div class="section-card">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="section-title mb-0">Pending Merchant Applications</h5>
                        <a href="admin_merchants.php?status=under_review" class="btn btn-sm btn-outline-primary">
                            View All
                        </a>
                    </div>
                    
                    <?php if (mysqli_num_rows($recent_merchants) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Store Name</th>
                                    <th>Owner</th>
                                    <th>Days Ago</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($merchant = mysqli_fetch_assoc($recent_merchants)): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($merchant['store_name']); ?></strong>
                                    </td>
                                    <td>
                                        <?php echo htmlspecialchars($merchant['first_name'] . ' ' . $merchant['last_name']); ?>
                                        <br>
                                        <small class="text-muted"><?php echo htmlspecialchars($merchant['email']); ?></small>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php echo ($merchant['days_ago'] ?? 0) > 3 ? 'danger' : 'warning'; ?>">
                                            <?php echo $merchant['days_ago'] ?? 0; ?> days
                                        </span>
                                    </td>
                                    <td>
                                        <a href="admin_merchant_details.php?id=<?php echo $merchant['merchant_id']; ?>" 
                                           class="btn btn-sm btn-primary">
                                            <i class="bi bi-eye"></i> Review
                                        </a>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="empty-state">
                        <i class="bi bi-check-circle-fill text-success"></i>
                        <h5 class="mt-3">No pending applications</h5>
                        <p class="text-muted">All merchant applications have been reviewed.</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Recent Orders -->
            <div class="col-lg-6">
                <div class="section-card">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="section-title mb-0">Recent Orders</h5>
                        <a href="admin_orders.php" class="btn btn-sm btn-outline-primary">
                            View All
                        </a>
                    </div>
                    
                    <?php if (mysqli_num_rows($recent_orders) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Order ID</th>
                                    <th>Customer</th>
                                    <th>Restaurant</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($order = mysqli_fetch_assoc($recent_orders)): 
                                    $status_classes = [
                                        'pending' => 'warning',
                                        'preparing' => 'primary',
                                        'delivered' => 'success',
                                        'cancelled' => 'danger'
                                    ];
                                ?>
                                <tr>
                                    <td>
                                        <strong>#<?php echo $order['id']; ?></strong>
                                        <br>
                                        <small class="text-muted"><?php echo date('H:i', strtotime($order['created_at'])); ?></small>
                                    </td>
                                    <td><?php echo htmlspecialchars($order['first_name'] . ' ' . $order['last_name']); ?></td>
                                    <td><?php echo htmlspecialchars($order['restaurant_name']); ?></td>
                                    <td>$<?php echo number_format($order['total'], 2); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo $status_classes[$order['status']] ?? 'secondary'; ?>">
                                            <?php echo ucfirst($order['status']); ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="empty-state">
                        <i class="bi bi-bag-x text-muted"></i>
                        <h5 class="mt-3">No recent orders</h5>
                        <p class="text-muted">No orders have been placed yet.</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Quick Stats -->
        <div class="row">
            <div class="col-12">
                <div class="section-card">
                    <h5 class="section-title">Quick Statistics</h5>
                    <div class="row">
                        <div class="col-md-3 text-center">
                            <div class="py-3">
                                <div class="display-4 fw-bold"><?php echo $merchants_stats['total'] ?? 0; ?></div>
                                <div class="text-muted">Total Merchants</div>
                            </div>
                        </div>
                        <div class="col-md-3 text-center">
                            <div class="py-3">
                                <div class="display-4 fw-bold"><?php echo $orders_stats['total'] ?? 0; ?></div>
                                <div class="text-muted">Total Orders</div>
                            </div>
                        </div>
                        <div class="col-md-3 text-center">
                            <div class="py-3">
                                <div class="display-4 fw-bold">$<?php echo number_format($orders_stats['total_revenue'] ?? 0, 2); ?></div>
                                <div class="text-muted">Total Revenue</div>
                            </div>
                        </div>
                        <div class="col-md-3 text-center">
                            <div class="py-3">
                                <div class="display-4 fw-bold"><?php echo $users_stats['total'] ?? 0; ?></div>
                                <div class="text-muted">Total Users</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>