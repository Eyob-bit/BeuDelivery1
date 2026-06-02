<?php
// admin_sidebar.php - Reusable sidebar for admin pages
$current_page = basename($_SERVER['PHP_SELF']);

// Get admin profile photo
$admin_id = $_SESSION['user_id'] ?? 0;
$admin_photo_sql = "SELECT profile_image FROM users WHERE id = $admin_id";
$admin_photo_result = mysqli_query($conn, $admin_photo_sql);
$admin_data = mysqli_fetch_assoc($admin_photo_result);
$profile_image = $admin_data['profile_image'] ?? null;
?>
<div class="sidebar">
    <div class="sidebar-header">
        <h3 class="mb-1">BeU Delivery</h3>
        <small class="text-muted">Admin Panel</small>
    </div>
    
    <div class="admin-profile">
        <div class="d-flex align-items-center">
            <?php if ($profile_image && file_exists('../' . $profile_image)): ?>
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
                <h6 class="mb-0"><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Admin'); ?></h6>
                <small class="text-muted">Administrator</small>
            </div>
        </div>
    </div>
    
    <nav class="nav flex-column px-3">
        <a href="admin_panel.php" class="nav-link <?php echo $current_page == 'admin_panel.php' ? 'active' : ''; ?>">
            <i class="bi bi-speedometer2"></i> Dashboard
        </a>
        <a href="admin_merchants.php" class="nav-link <?php echo $current_page == 'admin_merchants.php' || $current_page == 'admin_merchant_details.php' ? 'active' : ''; ?>">
            <i class="bi bi-shop"></i> All Merchants
        </a>
        <a href="orders.php" class="nav-link <?php echo $current_page == 'orders.php' ? 'active' : ''; ?>">
            <i class="bi bi-bag-check"></i> Orders
        </a>
        <a href="admin_users.php" class="nav-link <?php echo $current_page == 'admin_users.php' ? 'active' : ''; ?>">
            <i class="bi bi-people"></i> Users
        </a>
        <a href="admin_reports.php" class="nav-link <?php echo $current_page == 'admin_reports.php' ? 'active' : ''; ?>">
            <i class="bi bi-bar-chart"></i> Reports
        </a>
        <a href="restaurants.php" class="nav-link <?php echo $current_page == 'restaurants.php' ? 'active' : ''; ?>">
            <i class="bi bi-building"></i> Restaurants
        </a>
        <div class="mt-4 pt-3 border-top border-secondary">
            <a href="admin_settings.php" class="nav-link <?php echo $current_page == 'admin_settings.php' ? 'active' : ''; ?>">
                <i class="bi bi-gear"></i> Settings
            </a>
            <a href="../auth/logout.php" class="nav-link text-danger">
                <i class="bi bi-box-arrow-right"></i> Logout
            </a>
        </div>
    </nav>
</div>

<style>
.admin-profile {
    background: rgba(255,255,255,0.1);
    border-radius: 10px;
    padding: 15px;
    margin: 15px;
}

.nav-link {
    color: rgba(255,255,255,0.8);
    padding: 12px 20px;
    margin: 2px 0;
    border-radius: 8px;
    transition: all 0.3s;
    text-decoration: none;
    display: block;
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
</style>
