<?php
session_start();
$page_title = "Dashboard";
include "includes/merchant_header.php";

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// First, get merchant_id from merchants table
include "../includes/db.php";
$merchant_check_sql = "SELECT merchant_id FROM merchants WHERE user_id = ? LIMIT 1";
$stmt = mysqli_prepare($conn, $merchant_check_sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$merchant_check_result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($merchant_check_result) == 0) {
    // No merchant record found - redirect to merchant signup
    header("Location: ../merchant/getStarted.php");
    exit();
}

$merchant_data = mysqli_fetch_assoc($merchant_check_result);
$merchant_id = $merchant_data['merchant_id'];
$_SESSION['merchant_id'] = $merchant_id;

// Fetch merchant details with all related data
$merchant_sql = "SELECT 
                    m.*, 
                    u.first_name, 
                    u.last_name,
                    u.email,
                    u.phone,
                    md.store_phone,
                    md.cuisine_types,
                    md.store_hours
                 FROM merchants m 
                 JOIN users u ON m.user_id = u.id 
                 LEFT JOIN merchant_details md ON m.merchant_id = md.merchant_id
                 WHERE m.merchant_id = ?";
$stmt = mysqli_prepare($conn, $merchant_sql);
mysqli_stmt_bind_param($stmt, "i", $merchant_id);
mysqli_stmt_execute($stmt);
$merchant_result = mysqli_stmt_get_result($stmt);
$merchant = mysqli_fetch_assoc($merchant_result);

if (!$merchant) {
    header("Location: ../merchant/getStarted.php");
    exit();
}

// Check merchant status
if ($merchant['status'] !== 'active') {
    switch ($merchant['status']) {
        case 'under_review':
            header("Location: accountunderreview.php");
            break;
        case 'setup':
            header("Location: ../merchant/setup.php");
            break;
        case 'inactive':
        case 'suspended':
            header("Location: account_disabled.php");
            break;
        default:
            header("Location: ../merchant/getStarted.php");
    }
    exit();
}

// Include sidebar
include "includes/sidebar.php";

// Get today's stats
$today = date('Y-m-d');
$orders_today_sql = "SELECT 
                        COUNT(DISTINCT o.id) as total_orders, 
                        COALESCE(SUM(o.total), 0) as total_sales,
                        COALESCE(AVG(o.total), 0) as avg_order_value
                     FROM orders o
                     JOIN order_items oi ON o.id = oi.order_id
                     JOIN menu_items mi ON oi.menu_item_id = mi.id
                     WHERE mi.merchant_id = ? 
                     AND DATE(o.created_at) = ?";
$stmt = mysqli_prepare($conn, $orders_today_sql);
mysqli_stmt_bind_param($stmt, "is", $merchant_id, $today);
mysqli_stmt_execute($stmt);
$today_result = mysqli_stmt_get_result($stmt);
$today_stats = mysqli_fetch_assoc($today_result);

// Get pending orders (orders with items from this merchant)
$pending_orders_sql = "SELECT DISTINCT 
                            o.id,
                            o.order_number,
                            o.total,
                            o.status,
                            o.created_at,
                            u.first_name,
                            u.last_name,
                            u.phone
                       FROM orders o
                       JOIN order_items oi ON o.id = oi.order_id
                       JOIN menu_items mi ON oi.menu_item_id = mi.id
                       JOIN users u ON o.user_id = u.id
                       WHERE mi.merchant_id = ? 
                       AND o.status IN ('pending', 'preparing')
                       ORDER BY o.created_at DESC 
                       LIMIT 10";
$stmt = mysqli_prepare($conn, $pending_orders_sql);
mysqli_stmt_bind_param($stmt, "i", $merchant_id);
mysqli_stmt_execute($stmt);
$pending_orders_result = mysqli_stmt_get_result($stmt);

// Get menu items count
$menu_count_sql = "SELECT COUNT(*) as total_items FROM menu_items WHERE merchant_id = ?";
$stmt = mysqli_prepare($conn, $menu_count_sql);
mysqli_stmt_bind_param($stmt, "i", $merchant_id);
mysqli_stmt_execute($stmt);
$menu_result = mysqli_stmt_get_result($stmt);
$menu_stats = mysqli_fetch_assoc($menu_result);

// Get store rating
$rating_sql = "SELECT 
                COALESCE(AVG(rating), 0) as avg_rating,
                COUNT(*) as total_reviews
               FROM merchant_reviews 
               WHERE merchant_id = ? 
               AND status = 'approved'";
$stmt = mysqli_prepare($conn, $rating_sql);
mysqli_stmt_bind_param($stmt, "i", $merchant_id);
mysqli_stmt_execute($stmt);
$rating_result = mysqli_stmt_get_result($stmt);
$rating_stats = mysqli_fetch_assoc($rating_result);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Merchant Dashboard - BeU Delivery</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        :root {
            --primary-color: #000000;
            --secondary-color: #ffffff;
            --accent-color: #f5f5f5;
            --border-color: #e0e0e0;
            --text-color: #333333;
            --success-color: #28a745;
        }
        
        body {
            background-color: var(--accent-color);
            color: var(--text-color);
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
        }
        
        .dashboard-wrapper {
            display: flex;
            min-height: 100vh;
        }
        
        /* Sidebar Styles */
        .sidebar {
            width: 250px;
            background: var(--primary-color);
            color: var(--secondary-color);
            padding: 0;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
        }
        
        .sidebar-header {
            padding: 25px 20px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .store-info {
            padding: 20px;
            background: rgba(255,255,255,0.05);
            margin: 20px;
            border-radius: 8px;
        }
        
        .store-name {
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .store-status {
            font-size: 12px;
            padding: 3px 10px;
            background: var(--success-color);
            color: white;
            border-radius: 15px;
            display: inline-block;
        }
        
        .nav-link {
            color: rgba(255,255,255,0.8);
            padding: 15px 25px;
            border-left: 3px solid transparent;
            transition: all 0.3s;
            font-weight: 500;
        }
        
        .nav-link:hover,
        .nav-link.active {
            color: var(--secondary-color);
            background: rgba(255,255,255,0.05);
            border-left-color: var(--secondary-color);
        }
        
        .nav-link i {
            width: 20px;
            margin-right: 10px;
            text-align: center;
        }
        
        .nav-divider {
            border-color: rgba(255,255,255,0.1);
            margin: 10px 25px;
        }
        
        /* Main Content */
        .main-content {
            flex: 1;
            margin-left: 250px;
            padding: 30px;
            min-height: 100vh;
        }
        
        .header-bar {
            background: var(--secondary-color);
            padding: 20px 30px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            margin-bottom: 30px;
            border: 1px solid var(--border-color);
        }
        
        .welcome-text h1 {
            font-weight: 700;
            font-size: 1.8rem;
            margin-bottom: 5px;
        }
        
        .welcome-text p {
            color: #666;
            margin: 0;
        }
        
        .quick-stats {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }
        
        .stat-btn {
            background: var(--secondary-color);
            border: 1px solid var(--border-color);
            padding: 12px 25px;
            border-radius: 8px;
            font-weight: 600;
            color: var(--text-color);
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .stat-btn:hover {
            background: var(--primary-color);
            color: var(--secondary-color);
            border-color: var(--primary-color);
        }
        
        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }
        
        .stat-card {
            background: var(--secondary-color);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 25px;
            transition: all 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            border-color: var(--primary-color);
        }
        
        .stat-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .stat-icon {
            width: 50px;
            height: 50px;
            background: var(--accent-color);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: var(--primary-color);
        }
        
        .stat-trend {
            font-size: 14px;
            padding: 4px 10px;
            border-radius: 15px;
            background: var(--accent-color);
        }
        
        .trend-up { color: var(--success-color); }
        
        .stat-value {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 10px;
            color: var(--primary-color);
        }
        
        .stat-label {
            color: #666;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        /* Orders Section */
        .section-title {
            font-weight: 700;
            font-size: 1.4rem;
            margin-bottom: 25px;
            color: var(--primary-color);
        }
        
        .orders-container {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 30px;
            margin-bottom: 40px;
        }
        
        .orders-card,
        .activity-card,
        .quick-actions-card {
            background: var(--secondary-color);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            overflow: hidden;
        }
        
        .card-header {
            padding: 20px 25px;
            border-bottom: 1px solid var(--border-color);
            background: var(--accent-color);
        }
        
        .card-header h5 {
            margin: 0;
            font-weight: 600;
        }
        
        .card-body {
            padding: 25px;
        }
        
        .order-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 0;
            border-bottom: 1px solid var(--border-color);
        }
        
        .order-item:last-child {
            border-bottom: none;
        }
        
        .order-info h6 {
            margin: 0;
            font-weight: 600;
            color: var(--primary-color);
        }
        
        .order-details {
            color: #666;
            font-size: 14px;
            margin: 5px 0;
        }
        
        .order-status {
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .status-pending { 
            background: #fff3cd; 
            color: #856404; 
            border: 1px solid #ffeaa7;
        }
        
        .status-preparing { 
            background: #d4edda; 
            color: #155724; 
            border: 1px solid #c3e6cb;
        }
        
        .status-completed { 
            background: #d1ecf1; 
            color: #0c5460; 
            border: 1px solid #bee5eb;
        }
        
        .order-actions .btn {
            padding: 5px 15px;
            font-size: 14px;
        }
        
        /* Activity List */
        .activity-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .activity-item {
            display: flex;
            align-items: flex-start;
            padding: 12px 0;
            border-bottom: 1px solid var(--border-color);
        }
        
        .activity-item:last-child {
            border-bottom: none;
        }
        
        .activity-icon {
            width: 36px;
            height: 36px;
            background: var(--accent-color);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            flex-shrink: 0;
        }
        
        .activity-content {
            flex: 1;
        }
        
        .activity-title {
            font-weight: 600;
            margin-bottom: 3px;
        }
        
        .activity-time {
            color: #666;
            font-size: 12px;
        }
        
        /* Quick Actions */
        .action-grid {
            display: grid;
            gap: 15px;
        }
        
        .action-btn {
            background: var(--secondary-color);
            border: 2px solid var(--border-color);
            border-radius: 10px;
            padding: 15px;
            display: flex;
            align-items: center;
            gap: 15px;
            transition: all 0.3s;
            color: var(--text-color);
            text-decoration: none;
        }
        
        .action-btn:hover {
            background: var(--primary-color);
            color: var(--secondary-color);
            border-color: var(--primary-color);
            transform: translateX(5px);
        }
        
        .action-icon {
            width: 40px;
            height: 40px;
            background: var(--accent-color);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
        }
        
        .action-btn:hover .action-icon {
            background: rgba(255,255,255,0.1);
        }
        
        /* Responsive */
        @media (max-width: 992px) {
            .sidebar {
                width: 70px;
                overflow: visible;
            }
            
            .sidebar .nav-text,
            .store-info,
            .sidebar-header h4 {
                display: none;
            }
            
            .sidebar-header {
                padding: 20px 15px;
                text-align: center;
            }
            
            .nav-link {
                padding: 15px;
                text-align: center;
                border-left: none;
                border-right: 3px solid transparent;
            }
            
            .nav-link:hover,
            .nav-link.active {
                border-left: none;
                border-right-color: var(--secondary-color);
            }
            
            .nav-link i {
                margin-right: 0;
                font-size: 20px;
            }
            
            .main-content {
                margin-left: 70px;
            }
            
            .orders-container {
                grid-template-columns: 1fr;
            }
        }
        
        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .header-bar {
                flex-direction: column;
                gap: 20px;
            }
            
            .quick-stats {
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-wrapper">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">
                <h4 class="mb-0">BEU</h4>
                <small class="text-muted">Merchant Portal</small>
            </div>
            
            <div class="store-info">
                <div class="store-name"><?php echo htmlspecialchars($merchant['store_name']); ?></div>
                <span class="store-status">
                    <i class="bi bi-check-circle me-1"></i> Active
                </span>
            </div>
            
            <nav class="nav flex-column">
                <a href="merchant_dashboard.php" class="nav-link active">
                    <i class="bi bi-speedometer2"></i>
                    <span class="nav-text">Dashboard</span>
                </a>
                <a href="orders.php" class="nav-link">
                    <i class="bi bi-bag-check"></i>
                    <span class="nav-text">Orders</span>
                </a>
                <a href="menu_manager.php" class="nav-link">
                    <i class="bi bi-menu-button-wide"></i>
                    <span class="nav-text">Menu Manager</span>
                </a>
                <a href="reports.php" class="nav-link">
                    <i class="bi bi-bar-chart"></i>
                    <span class="nav-text">Reports</span>
                </a>
                <a href="settings.php" class="nav-link">
                    <i class="bi bi-gear"></i>
                    <span class="nav-text">Settings</span>
                </a>
                
                <hr class="nav-divider">
                
                <a href="customer_feedback.php" class="nav-link">
                    <i class="bi bi-chat-dots"></i>
                    <span class="nav-text">Customer Feedback</span>
                </a>
                <a href="../auth/logout.php" class="nav-link">
                    <i class="bi bi-box-arrow-right"></i>
                    <span class="nav-text">Logout</span>
                </a>
            </nav>
        </div>
        
        <!-- Main Content -->
        <div class="main-content">
            <!-- Header -->
            <div class="header-bar d-flex justify-content-between align-items-center">
                <div class="welcome-text">
                    <h1>Welcome back, <?php echo htmlspecialchars($merchant['first_name']); ?>!</h1>
                    <p>Here's what's happening with your store today</p>
                </div>
                <div class="quick-stats">
                    <button class="stat-btn">
                        <i class="bi bi-clock"></i>
                        <span>Live Orders</span>
                    </button>
                    <button class="stat-btn">
                        <i class="bi bi-bell"></i>
                        <span>Notifications</span>
                    </button>
                    <a href="menu_manager.php" class="stat-btn">
                        <i class="bi bi-plus-circle"></i>
                        <span>Add Item</span>
                    </a>
                </div>
            </div>
            
            <!-- Stats Overview -->
            <div class="stats-grid">
                <!-- Today's Orders -->
                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-icon">
                            <i class="bi bi-bag-check"></i>
                        </div>
                        <span class="stat-trend trend-up">+12%</span>
                    </div>
                    <div class="stat-value"><?php echo $today_stats['total_orders'] ?? 0; ?></div>
                    <div class="stat-label">Today's Orders</div>
                </div>
                
                <!-- Today's Sales -->
                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-icon">
                            <i class="bi bi-currency-dollar"></i>
                        </div>
                        <span class="stat-trend trend-up">+8%</span>
                    </div>
                    <div class="stat-value">$<?php echo number_format($today_stats['total_sales'] ?? 0, 2); ?></div>
                    <div class="stat-label">Today's Sales</div>
                </div>
                
                <!-- Store Rating -->
                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-icon">
                            <i class="bi bi-star"></i>
                        </div>
                        <span class="stat-trend trend-up">+0.2</span>
                    </div>
                    <div class="stat-value"><?php echo number_format($rating_stats['avg_rating'] ?? 0, 1); ?></div>
                    <div class="stat-label">Store Rating (<?php echo $rating_stats['total_reviews'] ?? 0; ?> reviews)</div>
                </div>
            </div>
            
            <!-- Orders & Activity -->
            <div class="orders-container">
                <!-- Pending Orders -->
                <div class="orders-card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5>Pending Orders</h5>
                        <a href="orders.php?status=pending" class="btn btn-sm btn-outline-dark">View All</a>
                    </div>
                    <div class="card-body">
                        <?php if (mysqli_num_rows($pending_orders_result) > 0): ?>
                            <?php while($order = mysqli_fetch_assoc($pending_orders_result)): ?>
                            <div class="order-item">
                                <div class="order-info">
                                    <h6>Order #<?php echo $order['order_number'] ?? $order['id']; ?></h6>
                                    <div class="order-details">
                                        <?php echo htmlspecialchars($order['first_name'] . ' ' . $order['last_name']); ?> • 
                                        $<?php echo number_format($order['total'], 2); ?>
                                    </div>
                                    <small><?php echo date('h:i A', strtotime($order['created_at'])); ?></small>
                                </div>
                                <div class="order-actions">
                                    <span class="order-status status-<?php echo $order['status']; ?>">
                                        <?php echo ucfirst($order['status']); ?>
                                    </span>
                                    <a href="orders.php?action=view&id=<?php echo $order['id']; ?>" 
                                       class="btn btn-sm btn-outline-dark ms-2">View</a>
                                </div>
                            </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <i class="bi bi-bag-x display-4 text-muted mb-3"></i>
                                <h6>No pending orders</h6>
                                <p class="text-muted">New orders will appear here</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Activity & Quick Actions -->
                <div style="display: grid; gap: 30px;">
                    <!-- Recent Activity -->
                    <div class="activity-card">
                        <div class="card-header">
                            <h5>Recent Activity</h5>
                        </div>
                        <div class="card-body">
                            <ul class="activity-list">
                                <li class="activity-item">
                                    <div class="activity-icon">
                                        <i class="bi bi-bag-check text-success"></i>
                                    </div>
                                    <div class="activity-content">
                                        <div class="activity-title">New order received</div>
                                        <div class="activity-time">Just now</div>
                                    </div>
                                </li>
                                <li class="activity-item">
                                    <div class="activity-icon">
                                        <i class="bi bi-star text-warning"></i>
                                    </div>
                                    <div class="activity-content">
                                        <div class="activity-title">New review received</div>
                                        <div class="activity-time">10 minutes ago</div>
                                    </div>
                                </li>
                                <li class="activity-item">
                                    <div class="activity-icon">
                                        <i class="bi bi-person-plus text-info"></i>
                                    </div>
                                    <div class="activity-content">
                                        <div class="activity-title">New customer registered</div>
                                        <div class="activity-time">1 hour ago</div>
                                    </div>
                                </li>
                            </ul>
                        </div>
                    </div>
                    
                    <!-- Quick Actions -->
                    <div class="quick-actions-card">
                        <div class="card-header">
                            <h5>Quick Actions</h5>
                        </div>
                        <div class="card-body">
                            <div class="action-grid">
                                <a href="menu_manager.php" class="action-btn">
                                    <div class="action-icon">
                                        <i class="bi bi-plus-lg"></i>
                                    </div>
                                    <div>
                                        <div class="fw-bold">Add Menu Item</div>
                                        <small class="text-muted">Add new items to your menu</small>
                                    </div>
                                </a>
                                
                                <a href="settings.php" class="action-btn">
                                    <div class="action-icon">
                                        <i class="bi bi-clock"></i>
                                    </div>
                                    <div>
                                        <div class="fw-bold">Update Store Hours</div>
                                        <small class="text-muted">Set your business hours</small>
                                    </div>
                                </a>
                                
                                <a href="reports.php" class="action-btn">
                                    <div class="action-icon">
                                        <i class="bi bi-printer"></i>
                                    </div>
                                    <div>
                                        <div class="fw-bold">Print Report</div>
                                        <small class="text-muted">Generate today's sales report</small>
                                    </div>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Store Information -->
            <div class="section-title">Store Information</div>
            <div class="row">
                <div class="col-md-6">
                    <div class="stat-card">
                        <h6 class="mb-3"><i class="bi bi-info-circle me-2"></i> Basic Info</h6>
                        <div class="mb-2">
                            <strong>Store Name:</strong> <?php echo htmlspecialchars($merchant['store_name']); ?>
                        </div>
                        <div class="mb-2">
                            <strong>Address:</strong> <?php echo htmlspecialchars($merchant['store_address']); ?>
                        </div>
                        <div class="mb-2">
                            <strong>Phone:</strong> <?php echo htmlspecialchars($merchant['store_phone'] ?? $merchant['phone']); ?>
                        </div>
                        <div class="mb-2">
                            <strong>Menu Items:</strong> <?php echo $menu_stats['total_items'] ?? 0; ?> items
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="stat-card">
                        <h6 class="mb-3"><i class="bi bi-truck me-2"></i> Delivery Settings</h6>
                        <div class="mb-2">
                            <strong>Delivery Fee:</strong> 
                            $<?php echo number_format($merchant['delivery_fee'] ?? 0, 2); ?>
                        </div>
                        <div class="mb-2">
                            <strong>Minimum Order:</strong> 
                            $<?php echo number_format($merchant['min_order_amount'] ?? 0, 2); ?>
                        </div>
                        <div class="mb-2">
                            <strong>Delivery Time:</strong> 
                            <?php echo $merchant['estimated_delivery_time'] ?? '30-45'; ?> minutes
                        </div>
                        <div class="mb-2">
                            <strong>Services:</strong> 
                            <?php echo ($merchant['is_delivery_available'] ?? 0) ? 'Delivery' : ''; ?>
                            <?php echo ($merchant['is_pickup_available'] ?? 0) ? ', Pickup' : ''; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Auto-refresh orders every 60 seconds
    setInterval(function() {
        fetch('includes/check_new_orders.php?merchant_id=<?php echo $merchant_id; ?>')
            .then(response => response.json())
            .then(data => {
                if(data.has_new_orders) {
                    // Play notification sound
                    const audio = new Audio('../public/notification.mp3');
                    audio.play().catch(e => console.log('Audio error:', e));
                    
                    // Show notification
                    showNotification('New order received!', 'success');
                }
            })
            .catch(error => console.error('Error checking orders:', error));
    }, 60000);
    
    // Live orders counter
    function updateLiveCounter() {
        const counter = document.querySelector('.stat-btn:nth-child(1) span');
        if (counter) {
            fetch('includes/get_live_orders.php?merchant_id=<?php echo $merchant_id; ?>')
                .then(response => response.json())
                .then(data => {
                    if(data.count > 0) {
                        counter.textContent = `Live Orders (${data.count})`;
                    }
                });
        }
    }
    
    // Update counter every 30 seconds
    setInterval(updateLiveCounter, 30000);
    
    function showNotification(message, type) {
        // Create notification element
        const notification = document.createElement('div');
        notification.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
        notification.style.top = '20px';
        notification.style.right = '20px';
        notification.style.zIndex = '1050';
        notification.style.minWidth = '300px';
        notification.innerHTML = `
            <div class="d-flex align-items-center">
                <i class="bi bi-bell fs-4 me-3"></i>
                <div>
                    <strong>${message}</strong><br>
                    <small>Click to view order</small>
                </div>
                <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
            </div>
        `;
        
        // Add click handler
        notification.addEventListener('click', function() {
            window.location.href = 'orders.php?status=pending';
        });
        
        document.body.appendChild(notification);
        
        // Auto remove after 10 seconds
        setTimeout(() => {
            if (notification.parentNode) {
                notification.remove();
            }
        }, 10000);
    }
    
    // Initialize
    document.addEventListener('DOMContentLoaded', function() {
        updateLiveCounter();
    });
    </script>
</body>
</html>