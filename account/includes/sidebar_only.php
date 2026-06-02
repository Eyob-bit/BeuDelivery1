<?php
// sidebar_only.php - Just the sidebar HTML without full document structure
$current_page = basename($_SERVER['PHP_SELF']);

// Get merchant details if not already loaded
if (!isset($merchant)) {
    $merchant_sql = "SELECT m.*, u.first_name, u.last_name 
                     FROM merchants m 
                     JOIN users u ON m.user_id = u.id 
                     WHERE m.merchant_id = ?";
    $stmt = mysqli_prepare($conn, $merchant_sql);
    mysqli_stmt_bind_param($stmt, "i", $merchant_id);
    mysqli_stmt_execute($stmt);
    $merchant_result = mysqli_stmt_get_result($stmt);
    $merchant = mysqli_fetch_assoc($merchant_result);
}
?>

<!-- Sidebar -->
<div class="sidebar">
    <div class="sidebar-header">
        <h4 class="mb-0">BEU</h4>
        <small class="text-muted">Merchant Portal</small>
    </div>
    
    <div class="store-info">
        <div class="store-name"><?php echo htmlspecialchars($merchant['store_name'] ?? 'Store'); ?></div>
        <span class="store-status">
            <i class="bi bi-check-circle me-1"></i> Active
        </span>
    </div>
    
    <nav class="nav flex-column">
        <a href="merchant_dashboard.php" class="nav-link <?php echo $current_page == 'merchant_dashboard.php' ? 'active' : ''; ?>">
            <i class="bi bi-speedometer2"></i>
            <span class="nav-text">Dashboard</span>
        </a>
        <a href="orders.php" class="nav-link <?php echo $current_page == 'orders.php' ? 'active' : ''; ?>">
            <i class="bi bi-bag-check"></i>
            <span class="nav-text">Orders</span>
        </a>
        <a href="menu_manager.php" class="nav-link <?php echo $current_page == 'menu_manager.php' ? 'active' : ''; ?>">
            <i class="bi bi-menu-button-wide"></i>
            <span class="nav-text">Menu Manager</span>
        </a>
        <a href="reports.php" class="nav-link <?php echo $current_page == 'reports.php' ? 'active' : ''; ?>">
            <i class="bi bi-bar-chart"></i>
            <span class="nav-text">Reports</span>
        </a>
        <a href="settings.php" class="nav-link <?php echo $current_page == 'settings.php' ? 'active' : ''; ?>">
            <i class="bi bi-gear"></i>
            <span class="nav-text">Settings</span>
        </a>
        
        <hr class="nav-divider">
        
        <a href="customer_feedback.php" class="nav-link <?php echo $current_page == 'customer_feedback.php' ? 'active' : ''; ?>">
            <i class="bi bi-chat-dots"></i>
            <span class="nav-text">Customer Feedback</span>
        </a>
        <a href="../auth/logout.php" class="nav-link">
            <i class="bi bi-box-arrow-right"></i>
            <span class="nav-text">Logout</span>
        </a>
    </nav>
</div>