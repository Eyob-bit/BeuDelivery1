<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

include "../includes/db.php";

// Mock merchant data for now
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
    <title>Orders - BeU Delivery</title>
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
                <div class="store-name"><?php echo htmlspecialchars($merchant['store_name']); ?></div>
                <span class="store-status">
                    <i class="bi bi-check-circle me-1"></i> Active
                </span>
            </div>
            
            <nav class="nav flex-column">
                <a href="merchant_dashboard.php" class="nav-link">
                    <i class="bi bi-speedometer2"></i>
                    <span class="nav-text">Dashboard</span>
                </a>
                <a href="orders_simple.php" class="nav-link active">
                    <i class="bi bi-bag-check"></i>
                    <span class="nav-text">Orders</span>
                </a>
                <a href="menu_manager.php" class="nav-link">
                    <i class="bi bi-menu-button-wide"></i>
                    <span class="nav-text">Menu Manager</span>
                </a>
                <a href="reports_simple.php" class="nav-link">
                    <i class="bi bi-bar-chart"></i>
                    <span class="nav-text">Reports</span>
                </a>
                <a href="settings_simple.php" class="nav-link">
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
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2>Orders Management</h2>
                    <p class="text-muted mb-0">Manage and track your orders</p>
                </div>
            </div>
            
            <div class="stat-card">
                <h4>Orders</h4>
                <p>This is the orders page with the correct sidebar UI.</p>
                <p>The sidebar should show:</p>
                <ul>
                    <li>Black background</li>
                    <li>Store name with "Active" badge</li>
                    <li>Navigation menu with "Orders" highlighted</li>
                </ul>
                
                <div class="alert alert-info">
                    <i class="bi bi-info-circle me-2"></i>
                    This is a simplified version to test the UI. The full functionality will be added once the UI is working correctly.
                </div>
            </div>
        </div>
    </div>
</body>
</html>