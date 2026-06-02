<?php
session_start();
$page_title = "Sidebar Test";

// Mock data for testing
$merchant_id = 1;
$merchant = [
    'store_name' => 'Test Store',
    'first_name' => 'Test',
    'last_name' => 'User'
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sidebar Test - BeU Delivery</title>
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
        
        /* Sidebar Styles - Same as Dashboard */
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
            text-decoration: none;
            display: block;
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
                <div class="store-name">Test Store</div>
                <span class="store-status">
                    <i class="bi bi-check-circle me-1"></i> Active
                </span>
            </div>
            
            <nav class="nav flex-column">
                <a href="merchant_dashboard.php" class="nav-link">
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
                
                <a href="customer_feedback.php" class="nav-link active">
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
            <div class="stat-card">
                <h2>Sidebar Test Page</h2>
                <p>This page tests if the sidebar is working correctly.</p>
                <p>You should see:</p>
                <ul>
                    <li>Black sidebar on the left</li>
                    <li>"BEU Merchant Portal" header</li>
                    <li>"Test Store" with green "Active" badge</li>
                    <li>Navigation menu with icons</li>
                    <li>"Customer Feedback" should be highlighted (active)</li>
                </ul>
                
                <h3>Test Links:</h3>
                <p><a href="debug_merchant_login.php">Debug Merchant Login</a></p>
                <p><a href="menu_manager.php">Menu Manager</a></p>
                <p><a href="orders.php">Orders</a></p>
                <p><a href="reports.php">Reports</a></p>
                <p><a href="customer_feedback.php">Customer Feedback</a></p>
                <p><a href="settings.php">Settings</a></p>
            </div>
        </div>
    </div>
</body>
</html>