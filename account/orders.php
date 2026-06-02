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

// Get or set merchant_id
if (!isset($_SESSION['merchant_id'])) {
    // Try to find merchant for this user
    $user_id = $_SESSION['user_id'];
    $merchant_sql = "SELECT merchant_id FROM merchants WHERE user_id = ? LIMIT 1";
    $stmt = mysqli_prepare($conn, $merchant_sql);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($row = mysqli_fetch_assoc($result)) {
        $_SESSION['merchant_id'] = $row['merchant_id'];
    } else {
        header("Location: ../merchant/getStarted.php");
        exit();
    }
}

$merchant_id = $_SESSION['merchant_id'];

// Get merchant details for sidebar
$merchant_sql = "SELECT m.*, u.first_name, u.last_name 
                 FROM merchants m 
                 JOIN users u ON m.user_id = u.id 
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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orders Management - BeU Delivery</title>
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
        
        /* Responsive */
        @media (max-width: 992px) {
            .sidebar {
                width: 70px;
            }
            
            .sidebar .nav-text,
            .store-info,
            .sidebar-header h4 {
                display: none;
            }
            
            .main-content {
                margin-left: 70px;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-wrapper">
        <?php include "includes/sidebar_only.php"; ?>
        
        <!-- Main Content -->
        <div class="main-content">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2>Orders Management</h2>
                    <p class="text-muted mb-0">Manage and track your orders</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="text-center py-5">
                    <i class="bi bi-bag-check display-4 text-muted mb-3"></i>
                    <h5>Orders Management</h5>
                    <p class="text-muted">Your orders will appear here when customers place them.</p>
                    <p><strong>Debug Info:</strong></p>
                    <p>Merchant ID: <?php echo $merchant_id; ?></p>
                    <p>Store: <?php echo htmlspecialchars($merchant['store_name']); ?></p>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>