<?php
session_start();
require_once "admin_auth.php";
include "../includes/db.php";

// Get filters
$role = isset($_GET['role']) ? mysqli_real_escape_string($conn, $_GET['role']) : 'all';
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Build where conditions
$where = [];
if ($role !== 'all') {
    $where[] = "r.name = '$role'";
}
if (!empty($search)) {
    $where[] = "(u.first_name LIKE '%$search%' OR u.last_name LIKE '%$search%' OR u.email LIKE '%$search%' OR u.phone LIKE '%$search%')";
}
$where_clause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

// Get counts for filter tabs
$count_sql = "SELECT 
    COUNT(DISTINCT CASE WHEN r.name = 'customer' THEN u.id END) as customer,
    COUNT(DISTINCT CASE WHEN r.name = 'merchant' THEN u.id END) as merchant,
    COUNT(DISTINCT CASE WHEN r.name = 'delivery' THEN u.id END) as delivery,
    COUNT(DISTINCT CASE WHEN r.name = 'admin' THEN u.id END) as admin,
    COUNT(DISTINCT u.id) as total
    FROM users u
    LEFT JOIN user_roles ur ON u.id = ur.user_id
    LEFT JOIN roles r ON ur.role_id = r.id";
$count_result = mysqli_query($conn, $count_sql);
$counts = mysqli_fetch_assoc($count_result) ?: array_fill_keys(['customer','merchant','delivery','admin','total'], 0);

// Get users with pagination
$users_sql = "SELECT 
    u.*,
    GROUP_CONCAT(DISTINCT r.name) as roles,
    COUNT(DISTINCT o.id) as order_count,
    COALESCE(SUM(o.total), 0) as total_spent
    FROM users u
    LEFT JOIN user_roles ur ON u.id = ur.user_id
    LEFT JOIN roles r ON ur.role_id = r.id
    LEFT JOIN orders o ON u.id = o.user_id
    $where_clause
    GROUP BY u.id
    ORDER BY u.created_at DESC
    LIMIT $offset, $per_page";
$users_result = mysqli_query($conn, $users_sql);

// Get total count for pagination
$total_sql = "SELECT COUNT(DISTINCT u.id) as total 
    FROM users u
    LEFT JOIN user_roles ur ON u.id = ur.user_id
    LEFT JOIN roles r ON ur.role_id = r.id
    $where_clause";
$total_result = mysqli_query($conn, $total_sql);
$total_row = mysqli_fetch_assoc($total_result);
$total_users = $total_row['total'];
$total_pages = ceil($total_users / $per_page);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Users Management - Admin Panel</title>
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
        
        .users-table {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        }
        
        .role-badge {
            padding: 4px 12px;
            border-radius: 15px;
            font-size: 11px;
            font-weight: 600;
            margin-right: 5px;
        }
        
        .badge-customer { background: #e7f1ff; color: #0a58ca; }
        .badge-merchant { background: #d1e7dd; color: #0f5132; }
        .badge-delivery { background: #cff4fc; color: #055160; }
        .badge-admin { background: #f8d7da; color: #721c24; }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <?php include "admin_sidebar.php"; ?>
    
    <div class="main-content">
        <div class="header-card">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="mb-1">Users Management</h2>
                    <p class="text-muted mb-0">Total: <?php echo $counts['total']; ?> users</p>
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
                        <a class="nav-link <?php echo $role === 'all' ? 'active' : ''; ?>" 
                           href="?role=all&search=<?php echo urlencode($search); ?>">
                            All (<?php echo $counts['total']; ?>)
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $role === 'customer' ? 'active' : ''; ?>" 
                           href="?role=customer&search=<?php echo urlencode($search); ?>">
                            Customers (<?php echo $counts['customer']; ?>)
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $role === 'merchant' ? 'active' : ''; ?>" 
                           href="?role=merchant&search=<?php echo urlencode($search); ?>">
                            Merchants (<?php echo $counts['merchant']; ?>)
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $role === 'delivery' ? 'active' : ''; ?>" 
                           href="?role=delivery&search=<?php echo urlencode($search); ?>">
                            Delivery (<?php echo $counts['delivery']; ?>)
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $role === 'admin' ? 'active' : ''; ?>" 
                           href="?role=admin&search=<?php echo urlencode($search); ?>">
                            Admins (<?php echo $counts['admin']; ?>)
                        </a>
                    </li>
                </ul>
            </div>
            
            <!-- Search Bar -->
            <form method="GET" class="mb-4">
                <div class="row">
                    <div class="col-md-8">
                        <input type="hidden" name="role" value="<?php echo $role; ?>">
                        <div class="input-group">
                            <input type="text" name="search" class="form-control" 
                                   placeholder="Search by name, email, or phone..." 
                                   value="<?php echo htmlspecialchars($search); ?>">
                            <button class="btn btn-outline-primary" type="submit">
                                <i class="bi bi-search"></i> Search
                            </button>
                            <?php if (!empty($search)): ?>
                            <a href="?role=<?php echo $role; ?>" class="btn btn-outline-secondary">
                                <i class="bi bi-x-circle"></i> Clear
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </form>
        </div>
        
        <!-- Users Table -->
        <div class="users-table">
            <?php if ($users_result && mysqli_num_rows($users_result) > 0): ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Contact</th>
                            <th>Roles</th>
                            <th>Orders</th>
                            <th>Total Spent</th>
                            <th>Status</th>
                            <th>Joined</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($user = mysqli_fetch_assoc($users_result)): 
                            $initials = strtoupper(substr($user['first_name'] ?? 'U', 0, 1) . substr($user['last_name'] ?? 'U', 0, 1));
                            $roles_array = $user['roles'] ? explode(',', $user['roles']) : ['customer'];
                        ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="user-avatar me-3"><?php echo $initials; ?></div>
                                    <div>
                                        <strong><?php echo htmlspecialchars(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')); ?></strong>
                                        <br><small class="text-muted">ID: <?php echo $user['id']; ?></small>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <?php if ($user['email']): ?>
                                <div><i class="bi bi-envelope"></i> <?php echo htmlspecialchars($user['email']); ?></div>
                                <?php endif; ?>
                                <?php if ($user['phone']): ?>
                                <div><i class="bi bi-phone"></i> <?php echo htmlspecialchars($user['phone']); ?></div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php foreach ($roles_array as $r): ?>
                                <span class="role-badge badge-<?php echo trim($r); ?>">
                                    <?php echo strtoupper(trim($r)); ?>
                                </span>
                                <?php endforeach; ?>
                            </td>
                            <td><?php echo $user['order_count']; ?></td>
                            <td><strong>$<?php echo number_format($user['total_spent'], 2); ?></strong></td>
                            <td>
                                <?php if ($user['is_active']): ?>
                                <span class="badge bg-success">Active</span>
                                <?php else: ?>
                                <span class="badge bg-danger">Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php echo date('M j, Y', strtotime($user['created_at'])); ?>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-outline-primary" onclick="viewUser(<?php echo $user['id']; ?>)">
                                    <i class="bi bi-eye"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-<?php echo $user['is_active'] ? 'danger' : 'success'; ?>" 
                                        onclick="toggleUserStatus(<?php echo $user['id']; ?>, <?php echo $user['is_active'] ? 0 : 1; ?>)">
                                    <i class="bi bi-<?php echo $user['is_active'] ? 'x-circle' : 'check-circle'; ?>"></i>
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
                        <a class="page-link" href="?role=<?php echo $role; ?>&search=<?php echo urlencode($search); ?>&page=<?php echo $page - 1; ?>">
                            <i class="bi bi-chevron-left"></i>
                        </a>
                    </li>
                    
                    <?php
                    $start_page = max(1, $page - 2);
                    $end_page = min($total_pages, $start_page + 4);
                    
                    for ($i = $start_page; $i <= $end_page; $i++): ?>
                    <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                        <a class="page-link" href="?role=<?php echo $role; ?>&search=<?php echo urlencode($search); ?>&page=<?php echo $i; ?>">
                            <?php echo $i; ?>
                        </a>
                    </li>
                    <?php endfor; ?>
                    
                    <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?role=<?php echo $role; ?>&search=<?php echo urlencode($search); ?>&page=<?php echo $page + 1; ?>">
                            <i class="bi bi-chevron-right"></i>
                        </a>
                    </li>
                </ul>
            </nav>
            <?php endif; ?>
            
            <?php else: ?>
            <div class="text-center py-5">
                <i class="bi bi-people" style="font-size: 48px; color: #6c757d;"></i>
                <h5 class="mt-3">No users found</h5>
                <?php if (!empty($search)): ?>
                <p class="text-muted">No results for "<?php echo htmlspecialchars($search); ?>"</p>
                <a href="?role=<?php echo $role; ?>" class="btn btn-primary">Clear Search</a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function viewUser(userId) {
            alert('User details for ID ' + userId + ' - Coming soon!');
        }
        
        function toggleUserStatus(userId, newStatus) {
            if (confirm('Are you sure you want to ' + (newStatus ? 'activate' : 'deactivate') + ' this user?')) {
                fetch('toggle_user_status.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({user_id: userId, status: newStatus})
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                });
            }
        }
    </script>
</body>
</html>
