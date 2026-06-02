<?php
// sidebar.php
// This file contains the sidebar HTML that can be included in any merchant page

// Check if user is logged in as merchant
if (!isset($_SESSION['user_id']) || !isset($_SESSION['merchant_id'])) {
    header("Location: ../getStarted.php");
    exit();
}

// Fetch merchant info for sidebar
include "../includes/db.php";
$merchant_id = $_SESSION['merchant_id'];

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
    header("Location: ../getStarted.php");
    exit();
}

// Get current page for active state highlighting
$current_page = basename($_SERVER['PHP_SELF']);
?>

<!-- Sidebar HTML -->
<div class="sidebar">
    <div class="text-center mb-4 px-3">
        <h4 class="mb-0">BEU</h4>
        <small class="text-muted">Merchant Portal</small>
    </div>
    
    <div class="store-info">
        <h6 class="mb-1"><?php echo htmlspecialchars($merchant['store_name']); ?></h6>
        <small class="badge bg-success">
            <i class="bi bi-check-circle"></i> <?php echo ucfirst($merchant['status']); ?>
        </small>
    </div>
    
    <nav class="nav flex-column">
        <a href="merchant_dashboard.php" class="nav-link <?php echo $current_page == 'merchant_dashboard.php' ? 'active' : ''; ?>">
            <i class="bi bi-speedometer2"></i> Dashboard
        </a>
        <a href="orders.php" class="nav-link <?php echo $current_page == 'orders.php' ? 'active' : ''; ?>">
            <i class="bi bi-bag-check"></i> Orders
        </a>
        <a href="menu_manager.php" class="nav-link <?php echo $current_page == 'menu_manager.php' ? 'active' : ''; ?>">
            <i class="bi bi-menu-button-wide"></i> Menu Manager
        </a>
        <a href="reports.php" class="nav-link <?php echo $current_page == 'reports.php' ? 'active' : ''; ?>">
            <i class="bi bi-bar-chart"></i> Reports
        </a>
        <a href="settings.php" class="nav-link <?php echo $current_page == 'settings.php' ? 'active' : ''; ?>">
            <i class="bi bi-gear"></i> Settings
        </a>
        
        <hr class="border-secondary mx-3 my-3">
        
        <a href="customer_feedback.php" class="nav-link <?php echo $current_page == 'customer_feedback.php' ? 'active' : ''; ?>">
            <i class="bi bi-chat-dots"></i> Customer Feedback
        </a>
        <a href="../auth/logout.php" class="nav-link">
            <i class="bi bi-box-arrow-right"></i> Logout
        </a>
    </nav>
</div>