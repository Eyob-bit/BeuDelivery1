<?php
session_start();
require_once "admin_auth.php";
include "../includes/db.php";

// Get filters
$status = isset($_GET['status']) ? mysqli_real_escape_string($conn, $_GET['status']) : 'all';
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Build where conditions
$where = [];
if ($status !== 'all') {
    $where[] = "m.status = '$status'";
}
if (!empty($search)) {
    $where[] = "(m.store_name LIKE '%$search%' OR m.store_address LIKE '%$search%')";
}
$where_clause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

// Get counts for filter tabs
$count_sql = "SELECT 
    COUNT(CASE WHEN status = 'active' THEN 1 END) as active,
    COUNT(CASE WHEN status = 'under_review' THEN 1 END) as under_review,
    COUNT(CASE WHEN status = 'inactive' THEN 1 END) as inactive,
    COUNT(*) as total
    FROM merchants";
$count_result = mysqli_query($conn, $count_sql);
$counts = mysqli_fetch_assoc($count_result) ?: array_fill_keys(['active','under_review','inactive','total'], 0);

// Get restaurants with pagination
$restaurants_sql = "SELECT 
    m.*,
    u.first_name,
    u.last_name,
    u.email,
    u.phone,
    COUNT(DISTINCT o.id) as total_orders,
    COALESCE(SUM(o.total), 0) as total_revenue,
    COUNT(DISTINCT mi.id) as menu_items_count
    FROM merchants m
    LEFT JOIN users u ON m.user_id = u.id
    LEFT JOIN orders o ON m.merchant_id = o.merchant_id
    LEFT JOIN menu_items mi ON m.merchant_id = mi.merchant_id
    $where_clause
    GROUP BY m.merchant_id
    ORDER BY m.created_at DESC
    LIMIT $offset, $per_page";
$restaurants_result = mysqli_query($conn, $restaurants_sql);

// Get total count for pagination
$total_sql = "SELECT COUNT(*) as total FROM merchants m $where_clause";
$total_result = mysqli_query($conn, $total_sql);
$total_row = mysqli_fetch_assoc($total_result);
$total_restaurants = $total_row['total'];
$total_pages = ceil($total_restaurants / $per_page);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restaurants Management - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
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
        
        .header-card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        }
        
        .filter-tabs .nav-link {
            border-radius: 20px;
            padding: 8px 20px;
            margin-right: 10px;
            color: #6c757d;
            border: 1px solid #dee2e6;
        }
        
        .filter-tabs .nav-link.active {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }
        
        .restaurants-table {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        }
        
        .restaurant-logo {
            width: 50px;
            height: 50px;
            border-radius: 8px;
            object-fit: cover;
            background: #f0f0f0;
        }
        
        .status-badge {
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .badge-active { background: #d4edda; color: #155724; }
        .badge-under_review { background: #fff3cd; color: #856404; }
        .badge-inactive { background: #f8d7da; color: #721c24; }
    </style>
</head>
<body>
    <?php include "admin_sidebar.php"; ?>
    
    <div class="main-content">
        <div class="header-card">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="mb-1">Restaurants Management</h2>
                    <p class="text-muted mb-0">Total: <?php echo $counts['total']; ?> restaurants</p>
                </div>
                <div>
                    <button class="btn btn-primary" onclick="window.location.reload()">
                        <i class="bi bi-arrow-clockwise"></i> Refresh
                    </button>
                </div>
            </div>
            
            <!-- Filter Tabs -->
            <div class="filter-tabs mb-4">
                <ul class="nav nav-pills">
                    <li class="nav-item">
                        <a class="nav-link <?php echo $status === 'all' ? 'active' : ''; ?>" 
                           href="?status=all&search=<?php echo urlencode($search); ?>">
                            All (<?php echo $counts['total']; ?>)
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $status === 'active' ? 'active' : ''; ?>" 
                           href="?status=active&search=<?php echo urlencode($search); ?>">
                            Active (<?php echo $counts['active']; ?>)
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $status === 'under_review' ? 'active' : ''; ?>" 
                           href="?status=under_review&search=<?php echo urlencode($search); ?>">
                            Under Review (<?php echo $counts['under_review']; ?>)
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $status === 'inactive' ? 'active' : ''; ?>" 
                           href="?status=inactive&search=<?php echo urlencode($search); ?>">
                            Inactive (<?php echo $counts['inactive']; ?>)
                        </a>
                    </li>
                </ul>
            </div>
            
            <!-- Search Bar -->
            <form method="GET" class="mb-4">
                <div class="row">
                    <div class="col-md-8">
                        <input type="hidden" name="status" value="<?php echo $status; ?>">
                        <div class="input-group">
                            <input type="text" name="search" class="form-control" 
                                   placeholder="Search by restaurant name or address..." 
                                   value="<?php echo htmlspecialchars($search); ?>">
                            <button class="btn btn-outline-primary" type="submit">
                                <i class="bi bi-search"></i> Search
                            </button>
                            <?php if (!empty($search)): ?>
                            <a href="?status=<?php echo $status; ?>" class="btn btn-outline-secondary">
                                <i class="bi bi-x-circle"></i> Clear
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </form>
        </div>
        
        <!-- Restaurants Table -->
        <div class="restaurants-table">
            <?php if ($restaurants_result && mysqli_num_rows($restaurants_result) > 0): ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Restaurant</th>
                            <th>Owner</th>
                            <th>Menu Items</th>
                            <th>Orders</th>
                            <th>Revenue</th>
                            <th>Rating</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($restaurant = mysqli_fetch_assoc($restaurants_result)): 
                            $status_class = [
                                'active' => 'active',
                                'under_review' => 'under_review',
                                'inactive' => 'inactive'
                            ][$restaurant['status']] ?? 'inactive';
                        ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <?php if ($restaurant['logo']): ?>
                                    <img src="../<?php echo htmlspecialchars($restaurant['logo']); ?>" 
                                         class="restaurant-logo me-3" alt="Logo">
                                    <?php else: ?>
                                    <div class="restaurant-logo me-3 d-flex align-items-center justify-content-center">
                                        <i class="bi bi-shop"></i>
                                    </div>
                                    <?php endif; ?>
                                    <div>
                                        <strong><?php echo htmlspecialchars($restaurant['store_name']); ?></strong>
                                        <br><small class="text-muted"><?php echo htmlspecialchars(substr($restaurant['store_address'], 0, 40)); ?>...</small>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <?php echo htmlspecialchars($restaurant['first_name'] . ' ' . $restaurant['last_name']); ?>
                                <br><small class="text-muted"><?php echo htmlspecialchars($restaurant['phone']); ?></small>
                            </td>
                            <td class="text-center">
                                <strong><?php echo $restaurant['menu_items_count']; ?></strong>
                            </td>
                            <td class="text-center">
                                <?php echo $restaurant['total_orders']; ?>
                            </td>
                            <td>
                                <strong>$<?php echo number_format($restaurant['total_revenue'], 2); ?></strong>
                            </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-star-fill text-warning me-1"></i>
                                    <strong><?php echo number_format($restaurant['rating'], 1); ?></strong>
                                    <small class="text-muted ms-1">(<?php echo $restaurant['review_count']; ?>)</small>
                                </div>
                            </td>
                            <td>
                                <span class="status-badge badge-<?php echo $status_class; ?>">
                                    <?php echo strtoupper(str_replace('_', ' ', $restaurant['status'])); ?>
                                </span>
                            </td>
                            <td>
                                <a href="admin_merchant_details.php?id=<?php echo $restaurant['merchant_id']; ?>" 
                                   class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-eye"></i> View
                                </a>
                                <button class="btn btn-sm btn-outline-secondary" 
                                        onclick="viewMenu(<?php echo $restaurant['merchant_id']; ?>)">
                                    <i class="bi bi-menu-button-wide"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
            <nav aria-label="Page navigation" class="mt-4">
                <ul class="pagination justify-content-center">
                    <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?status=<?php echo $status; ?>&search=<?php echo urlencode($search); ?>&page=<?php echo $page - 1; ?>">
                            <i class="bi bi-chevron-left"></i>
                        </a>
                    </li>
                    
                    <?php
                    $start_page = max(1, $page - 2);
                    $end_page = min($total_pages, $start_page + 4);
                    
                    for ($i = $start_page; $i <= $end_page; $i++): ?>
                    <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                        <a class="page-link" href="?status=<?php echo $status; ?>&search=<?php echo urlencode($search); ?>&page=<?php echo $i; ?>">
                            <?php echo $i; ?>
                        </a>
                    </li>
                    <?php endfor; ?>
                    
                    <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?status=<?php echo $status; ?>&search=<?php echo urlencode($search); ?>&page=<?php echo $page + 1; ?>">
                            <i class="bi bi-chevron-right"></i>
                        </a>
                    </li>
                </ul>
            </nav>
            <?php endif; ?>
            
            <?php else: ?>
            <div class="text-center py-5">
                <i class="bi bi-shop" style="font-size: 48px; color: #6c757d;"></i>
                <h5 class="mt-3">No restaurants found</h5>
                <?php if (!empty($search)): ?>
                <p class="text-muted">No results for "<?php echo htmlspecialchars($search); ?>"</p>
                <a href="?status=<?php echo $status; ?>" class="btn btn-primary">Clear Search</a>
                <?php else: ?>
                <p class="text-muted">No restaurants have been registered yet.</p>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function viewMenu(merchantId) {
            alert('Menu view for merchant #' + merchantId + ' - Coming soon!');
        }
    </script>
</body>
</html>
