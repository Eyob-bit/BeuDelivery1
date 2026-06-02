<?php
// admin_merchants.php - MERCHANTS LISTING
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
    $where[] = "(m.store_name LIKE '%$search%' OR u.first_name LIKE '%$search%' OR u.last_name LIKE '%$search%' OR u.email LIKE '%$search%')";
}
$where_clause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

// Get counts for filter tabs
$count_sql = "SELECT 
    COUNT(CASE WHEN m.status = 'under_review' THEN 1 END) as pending,
    COUNT(CASE WHEN m.status = 'active' THEN 1 END) as active,
    COUNT(CASE WHEN m.status = 'inactive' THEN 1 END) as inactive,
    COUNT(*) as total
    FROM merchants m
    JOIN users u ON m.user_id = u.id";
$count_result = mysqli_query($conn, $count_sql);
$counts = mysqli_fetch_assoc($count_result);

// Get merchants with pagination - SIMPLIFIED QUERY
$merchants_sql = "SELECT 
    m.merchant_id,
    m.store_name,
    m.brand_name,
    m.business_type,
    m.store_address,
    m.status,
    m.created_at,
    m.mobile_phone,
    u.first_name,
    u.last_name,
    u.email
    FROM merchants m
    JOIN users u ON m.user_id = u.id
    $where_clause
    ORDER BY m.created_at DESC
    LIMIT $offset, $per_page";
$merchants_result = mysqli_query($conn, $merchants_sql);

// Check for query errors
if (!$merchants_result) {
    error_log("Merchants query failed: " . mysqli_error($conn));
    error_log("Query was: " . $merchants_sql);
    die("Query Error: " . mysqli_error($conn)); // Show error on page
}

// Store all merchants data
$all_merchants = [];
if ($merchants_result && mysqli_num_rows($merchants_result) > 0) {
    while ($row = mysqli_fetch_assoc($merchants_result)) {
        // Get review status separately
        $review_sql = "SELECT status, verification_score FROM merchant_reviews WHERE merchant_id = " . $row['merchant_id'];
        $review_result = mysqli_query($conn, $review_sql);
        if ($review_result && mysqli_num_rows($review_result) > 0) {
            $review = mysqli_fetch_assoc($review_result);
            $row['review_status'] = $review['status'];
            $row['verification_score'] = $review['verification_score'];
        } else {
            $row['review_status'] = null;
            $row['verification_score'] = null;
        }
        $all_merchants[] = $row;
    }
    error_log("Successfully fetched " . count($all_merchants) . " merchants");
} else {
    error_log("Query returned 0 rows or failed");
}

// DEBUG: Check if merchants were loaded
error_log("Admin Merchants Page - Total merchants loaded: " . count($all_merchants));
if (empty($all_merchants)) {
    error_log("Admin Merchants Page - WARNING: No merchants in array!");
    error_log("Admin Merchants Page - Query error: " . mysqli_error($conn));
}

// Get total count for pagination
$total_sql = "SELECT COUNT(*) as total FROM merchants m JOIN users u ON m.user_id = u.id $where_clause";
$total_result = mysqli_query($conn, $total_sql);
$total_row = mysqli_fetch_assoc($total_result);
$total_merchants = $total_row['total'];
$total_pages = ceil($total_merchants / $per_page);

// Handle approval/rejection via AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_action'])) {
    $response = ['success' => false, 'message' => ''];
    $action = $_POST['action'];
    $merchant_id = $_POST['merchant_id'];
    $admin_id = $_SESSION['user_id'];
    
    mysqli_begin_transaction($conn);
    try {
        if ($action === 'approve') {
            $comments = mysqli_real_escape_string($conn, $_POST['comments'] ?? 'Approved');
            
            // Update merchant status
            mysqli_query($conn, "UPDATE merchants SET status = 'active' WHERE merchant_id = '$merchant_id'");
            
            // Update or create review
            $review_check = mysqli_query($conn, "SELECT id FROM merchant_reviews WHERE merchant_id = '$merchant_id'");
            if (mysqli_num_rows($review_check) > 0) {
                mysqli_query($conn, "UPDATE merchant_reviews SET 
                    status = 'approved',
                    admin_comments = '$comments',
                    reviewed_at = NOW(),
                    reviewed_by = '$admin_id'
                    WHERE merchant_id = '$merchant_id'");
            } else {
                mysqli_query($conn, "INSERT INTO merchant_reviews 
                    (merchant_id, status, admin_comments, reviewed_at, reviewed_by) 
                    VALUES ('$merchant_id', 'approved', '$comments', NOW(), '$admin_id')");
            }
            
            $response['success'] = true;
            $response['message'] = 'Merchant approved successfully!';
            
        } elseif ($action === 'reject') {
            $reason = mysqli_real_escape_string($conn, $_POST['reason'] ?? 'Rejected');
            
            // Update merchant status
            mysqli_query($conn, "UPDATE merchants SET status = 'inactive' WHERE merchant_id = '$merchant_id'");
            
            // Update or create review
            $review_check = mysqli_query($conn, "SELECT id FROM merchant_reviews WHERE merchant_id = '$merchant_id'");
            if (mysqli_num_rows($review_check) > 0) {
                mysqli_query($conn, "UPDATE merchant_reviews SET 
                    status = 'rejected',
                    admin_comments = '$reason',
                    reviewed_at = NOW(),
                    reviewed_by = '$admin_id'
                    WHERE merchant_id = '$merchant_id'");
            } else {
                mysqli_query($conn, "INSERT INTO merchant_reviews 
                    (merchant_id, status, admin_comments, reviewed_at, reviewed_by) 
                    VALUES ('$merchant_id', 'rejected', '$reason', NOW(), '$admin_id')");
            }
            
            $response['success'] = true;
            $response['message'] = 'Merchant application rejected.';
        }
        
        mysqli_commit($conn);
    } catch (Exception $e) {
        mysqli_rollback($conn);
        $response['message'] = 'Error: ' . $e->getMessage();
    }
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Merchants - Admin Panel</title>
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
        
        .merchant-table {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        }
        
        .status-badge {
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .badge-review { background: #fff3cd; color: #856404; }
        .badge-active { background: #d4edda; color: #155724; }
        .badge-inactive { background: #f8d7da; color: #721c24; }
        .badge-setup { background: #d1ecf1; color: #0c5460; }
        
        .action-btn {
            padding: 5px 12px;
            font-size: 12px;
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
        
        /* Modal Styles */
        .modal-xl {
            max-width: 1000px;
        }
        
        .detail-section {
            margin-bottom: 25px;
        }
        
        .detail-section h5 {
            color: var(--dark);
            border-bottom: 2px solid var(--primary);
            padding-bottom: 10px;
            margin-bottom: 15px;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
        }
        
        .info-item {
            display: flex;
            flex-direction: column;
        }
        
        .info-label {
            font-weight: 600;
            color: #666;
            font-size: 14px;
            margin-bottom: 5px;
        }
        
        .info-value {
            color: #333;
            font-size: 15px;
        }
        
        .modal-body {
            max-height: 70vh;
            overflow-y: auto;
        }
        
        .status-indicator {
            display: inline-block;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            margin-right: 8px;
        }
        
        .status-under_review { background-color: var(--warning); }
        .status-active { background-color: var(--success); }
        .status-inactive { background-color: var(--danger); }
        
        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                position: relative;
                height: auto;
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .info-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <!-- Include Sidebar -->
    <?php include "admin_sidebar.php"; ?>
    
    <!-- Main Content -->
    <div class="main-content">
        <!-- Header -->
        <div class="header-card">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="mb-1">Manage Merchants</h2>
                    <p class="text-muted mb-0">Total: <?php echo $counts['total']; ?> merchants registered</p>
                </div>
                <div>
                    <button class="btn btn-primary" onclick="window.location.href='admin_panel.php'">
                        <i class="bi bi-arrow-left"></i> Back to Dashboard
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
                        <a class="nav-link <?php echo $status === 'under_review' ? 'active' : ''; ?>" 
                           href="?status=under_review&search=<?php echo urlencode($search); ?>">
                            Under Review (<?php echo $counts['pending']; ?>)
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $status === 'active' ? 'active' : ''; ?>" 
                           href="?status=active&search=<?php echo urlencode($search); ?>">
                            Active (<?php echo $counts['active']; ?>)
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
                                   placeholder="Search by store name, owner name, or email..." 
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
        
        <!-- Merchants Table -->
        <div class="merchant-table">
            <?php if (!empty($all_merchants)): ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Store Name</th>
                            <th>Owner</th>
                            <th>Business Type</th>
                            <th>Contact</th>
                            <th>Status</th>
                            <th>Review Status</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $counter = $offset + 1;
                        foreach ($all_merchants as $merchant): 
                            $status_class = [
                                'under_review' => 'review',
                                'active' => 'active',
                                'inactive' => 'inactive',
                                'setup' => 'setup'
                            ][$merchant['status']] ?? 'secondary';
                        ?>
                        <tr>
                            <td><?php echo $counter++; ?></td>
                            <td>
                                <strong><?php echo htmlspecialchars($merchant['store_name']); ?></strong>
                                <?php if (!empty($merchant['brand_name'])): ?>
                                <br><small class="text-muted">Brand: <?php echo htmlspecialchars($merchant['brand_name']); ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php echo htmlspecialchars($merchant['first_name'] . ' ' . $merchant['last_name']); ?>
                                <br><small class="text-muted"><?php echo htmlspecialchars($merchant['email']); ?></small>
                            </td>
                            <td><?php echo htmlspecialchars($merchant['business_type'] ?? 'N/A'); ?></td>
                            <td>
                                <small class="text-muted"><?php echo htmlspecialchars($merchant['mobile_phone']); ?></small>
                            </td>
                            <td>
                                <span class="status-badge badge-<?php echo $status_class; ?>">
                                    <span class="status-indicator status-<?php echo $merchant['status']; ?>"></span>
                                    <?php echo strtoupper(str_replace('_', ' ', $merchant['status'])); ?>
                                </span>
                            </td>
                            <td>
                                <?php if (!empty($merchant['review_status'])): ?>
                                <span class="badge bg-<?php 
                                    if ($merchant['review_status'] == 'approved') echo 'success';
                                    elseif ($merchant['review_status'] == 'rejected') echo 'danger';
                                    else echo 'warning';
                                ?>">
                                    <?php echo ucfirst($merchant['review_status']); ?>
                                    <?php if ($merchant['verification_score']): ?>
                                    <br><small><?php echo $merchant['verification_score']; ?>/100</small>
                                    <?php endif; ?>
                                </span>
                                <?php else: ?>
                                <span class="badge bg-secondary">Not reviewed</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php echo date('M j, Y', strtotime($merchant['created_at'])); ?>
                                <br><small class="text-muted"><?php echo date('g:i A', strtotime($merchant['created_at'])); ?></small>
                            </td>
                            <td>
                                <div class="btn-group" role="group">
                                    <a href="admin_merchant_details.php?id=<?php echo $merchant['merchant_id']; ?>" 
                                       class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-eye"></i> View Details
                                    </a>
                                    <?php if ($merchant['status'] == 'under_review'): ?>
                                    <button type="button" class="btn btn-sm btn-outline-success action-btn quick-approve-btn"
                                            data-merchant-id="<?php echo $merchant['merchant_id']; ?>"
                                            data-store-name="<?php echo htmlspecialchars($merchant['store_name']); ?>">
                                        <i class="bi bi-check-circle"></i> Quick Approve
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
            <nav aria-label="Page navigation" class="mt-4">
                <ul class="pagination justify-content-center">
                    <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                        <a class="page-link" 
                           href="?status=<?php echo $status; ?>&search=<?php echo urlencode($search); ?>&page=<?php echo $page - 1; ?>">
                            <i class="bi bi-chevron-left"></i>
                        </a>
                    </li>
                    
                    <?php
                    $start_page = max(1, $page - 2);
                    $end_page = min($total_pages, $start_page + 4);
                    if ($end_page - $start_page < 4) {
                        $start_page = max(1, $end_page - 4);
                    }
                    
                    for ($i = $start_page; $i <= $end_page; $i++): ?>
                    <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                        <a class="page-link" 
                           href="?status=<?php echo $status; ?>&search=<?php echo urlencode($search); ?>&page=<?php echo $i; ?>">
                            <?php echo $i; ?>
                        </a>
                    </li>
                    <?php endfor; ?>
                    
                    <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                        <a class="page-link" 
                           href="?status=<?php echo $status; ?>&search=<?php echo urlencode($search); ?>&page=<?php echo $page + 1; ?>">
                            <i class="bi bi-chevron-right"></i>
                        </a>
                    </li>
                </ul>
                <p class="text-center text-muted mt-2">
                    Page <?php echo $page; ?> of <?php echo $total_pages; ?> 
                    | Showing <?php echo min($per_page, $total_merchants - $offset); ?> of <?php echo $total_merchants; ?> merchants
                </p>
            </nav>
            <?php endif; ?>
            
            <?php else: ?>
            <div class="empty-state">
                <i class="bi bi-shop-slash"></i>
                <h5 class="mt-3">No merchants found</h5>
                <?php if (!empty($search)): ?>
                <p class="text-muted">No results for "<?php echo htmlspecialchars($search); ?>"</p>
                <a href="?status=<?php echo $status; ?>" class="btn btn-primary">Clear Search</a>
                <?php elseif ($status !== 'all'): ?>
                <p class="text-muted">No merchants with status "<?php echo $status; ?>"</p>
                <a href="admin_merchants.php" class="btn btn-primary">View All Merchants</a>
                <?php else: ?>
                <p class="text-muted">No merchants have registered yet.</p>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- View Details Modal -->
    <div class="modal fade" id="viewDetailsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Merchant Details - <span id="modalStoreName"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="merchantDetailsContent">
                    <!-- Content will be loaded via AJAX -->
                    <div class="text-center py-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-3">Loading merchant details...</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <div id="actionButtons" style="display: none;">
                        <button type="button" class="btn btn-danger" id="rejectBtn" data-bs-toggle="modal" data-bs-target="#rejectModal">
                            <i class="bi bi-x-circle"></i> Reject
                        </button>
                        <button type="button" class="btn btn-success" id="approveBtn" data-bs-toggle="modal" data-bs-target="#approveModal">
                            <i class="bi bi-check-circle"></i> Approve
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Approve Modal -->
    <div class="modal fade" id="approveModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Approve Merchant</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="approveForm">
                    <div class="modal-body">
                        <p>Are you sure you want to approve <strong id="approveStoreName"></strong>?</p>
                        <div class="mb-3">
                            <label class="form-label">Comments (optional):</label>
                            <textarea name="comments" class="form-control" rows="3" placeholder="Add approval comments...">Approved</textarea>
                        </div>
                        <input type="hidden" name="merchant_id" id="approveMerchantId">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success">Approve Merchant</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Reject Modal -->
    <div class="modal fade" id="rejectModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Reject Application</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="rejectForm">
                    <div class="modal-body">
                        <p>Are you sure you want to reject <strong id="rejectStoreName"></strong>?</p>
                        <div class="mb-3">
                            <label class="form-label">Reason for rejection:</label>
                            <textarea name="reason" class="form-control" rows="4" required placeholder="Please provide a reason..."></textarea>
                        </div>
                        <input type="hidden" name="merchant_id" id="rejectMerchantId">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">Reject Application</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Quick Approve Modal -->
    <div class="modal fade" id="quickApproveModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Quick Approve</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="quickApproveForm">
                    <div class="modal-body">
                        <p>Approve <strong id="quickApproveStoreName"></strong> without viewing details?</p>
                        <div class="mb-3">
                            <label class="form-label">Comments (optional):</label>
                            <textarea name="comments" class="form-control" rows="2" placeholder="Add comments...">Approved</textarea>
                        </div>
                        <input type="hidden" name="merchant_id" id="quickApproveMerchantId">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success">Approve</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Action Functions for Merchant Management
async function approveMerchant(merchantId, storeName) {
    if (!confirm(`Are you sure you want to APPROVE "${storeName}"?\n\nThis will:\n1. Change status to "active"\n2. Move merchant to active dashboard\n3. Send approval email`)) {
        return;
    }
    
    try {
        // Show loading
        const modal = document.getElementById('viewDetailsModal');
        const footer = modal.querySelector('.modal-footer');
        const originalFooter = footer.innerHTML;
        footer.innerHTML = '<div class="text-center py-2"><div class="spinner-border spinner-border-sm text-primary"></div> Approving merchant...</div>';
        
        // Call API to approve merchant
        const response = await fetch('update_merchant_status.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=approve&merchant_id=${merchantId}`
        });
        
        const result = await response.json();
        
        if (result.success) {
            // Show success message
            footer.innerHTML = '<div class="alert alert-success mb-0">✅ Merchant approved successfully!</div>';
            
            // Refresh the page after 2 seconds
            setTimeout(() => {
                location.reload();
            }, 2000);
        } else {
            footer.innerHTML = `<div class="alert alert-danger mb-0">❌ Error: ${result.message}</div>`;
            setTimeout(() => {
                footer.innerHTML = originalFooter;
            }, 3000);
        }
        
    } catch (error) {
        console.error('Error approving merchant:', error);
        alert('Failed to approve merchant. Check console for details.');
    }
}

async function rejectMerchant(merchantId, storeName) {
    const reason = prompt(`Enter rejection reason for "${storeName}":\n\nExamples:\n- Incomplete documentation\n- Menu quality issues\n- Business verification failed\n- Other compliance issues`);
    
    if (reason === null) return; // User cancelled
    
    if (!reason.trim()) {
        alert('Rejection reason is required.');
        return;
    }
    
    if (!confirm(`REJECT "${storeName}"?\n\nReason: ${reason}\n\nThis will:\n1. Change status to "rejected"\n2. Notify the merchant\n3. Remove from active listings`)) {
        return;
    }
    
    try {
        // Show loading
        const modal = document.getElementById('viewDetailsModal');
        const footer = modal.querySelector('.modal-footer');
        const originalFooter = footer.innerHTML;
        footer.innerHTML = '<div class="text-center py-2"><div class="spinner-border spinner-border-sm text-primary"></div> Rejecting merchant...</div>';
        
        // Call API to reject merchant
        const response = await fetch('update_merchant_status.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=reject&merchant_id=${merchantId}&reason=${encodeURIComponent(reason)}`
        });
        
        const result = await response.json();
        
        if (result.success) {
            footer.innerHTML = '<div class="alert alert-warning mb-0">⚠️ Merchant rejected successfully!</div>';
            
            // Refresh the page after 2 seconds
            setTimeout(() => {
                location.reload();
            }, 2000);
        } else {
            footer.innerHTML = `<div class="alert alert-danger mb-0">❌ Error: ${result.message}</div>`;
            setTimeout(() => {
                footer.innerHTML = originalFooter;
            }, 3000);
        }
        
    } catch (error) {
        console.error('Error rejecting merchant:', error);
        alert('Failed to reject merchant. Check console for details.');
    }
}

async function disableMerchant(merchantId, storeName) {
    const reason = prompt(`Enter disable reason for "${storeName}":\n\nExamples:\n- Terms of service violation\n- Illegal activities suspected\n- Inappropriate content\n- Multiple customer complaints\n- Payment issues`);
    
    if (reason === null) return; // User cancelled
    
    if (!reason.trim()) {
        alert('Disable reason is required.');
        return;
    }
    
    if (!confirm(`DISABLE "${storeName}"?\n\nReason: ${reason}\n\nThis will:\n1. Change status to "inactive"\n2. Stop all orders\n3. Hide store from customers\n4. Notify the merchant`)) {
        return;
    }
    
    try {
        // Show loading
        const modal = document.getElementById('viewDetailsModal');
        const footer = modal.querySelector('.modal-footer');
        const originalFooter = footer.innerHTML;
        footer.innerHTML = '<div class="text-center py-2"><div class="spinner-border spinner-border-sm text-primary"></div> Disabling merchant...</div>';
        
        // Call API to disable merchant
        const response = await fetch('update_merchant_status.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=disable&merchant_id=${merchantId}&reason=${encodeURIComponent(reason)}`
        });
        
        const result = await response.json();
        
        if (result.success) {
            footer.innerHTML = '<div class="alert alert-danger mb-0">⛔ Merchant disabled successfully!</div>';
            
            // Refresh the page after 2 seconds
            setTimeout(() => {
                location.reload();
            }, 2000);
        } else {
            footer.innerHTML = `<div class="alert alert-danger mb-0">❌ Error: ${result.message}</div>`;
            setTimeout(() => {
                footer.innerHTML = originalFooter;
            }, 3000);
        }
        
    } catch (error) {
        console.error('Error disabling merchant:', error);
        alert('Failed to disable merchant. Check console for details.');
    }
}

// Enable merchant function
async function enableMerchant(merchantId, storeName) {
    const reason = prompt(`Enter reason for re-enabling "${storeName}":`);
    
    if (reason === null) return;
    
    try {
        const modal = document.getElementById('viewDetailsModal');
        const footer = modal.querySelector('.modal-footer');
        const originalFooter = footer.innerHTML;
        footer.innerHTML = '<div class="text-center py-2"><div class="spinner-border spinner-border-sm text-primary"></div> Enabling merchant...</div>';
        
        const response = await fetch('update_merchant_status.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=enable&merchant_id=${merchantId}&reason=${encodeURIComponent(reason)}`
        });
        
        const result = await response.json();
        
        if (result.success) {
            footer.innerHTML = '<div class="alert alert-success mb-0">✅ Merchant enabled successfully!</div>';
            
            setTimeout(() => {
                location.reload();
            }, 2000);
        } else {
            footer.innerHTML = `<div class="alert alert-danger mb-0">❌ Error: ${result.message}</div>`;
            setTimeout(() => {
                footer.innerHTML = originalFooter;
            }, 3000);
        }
        
    } catch (error) {
        console.error('Error enabling merchant:', error);
        alert('Failed to enable merchant.');
    }
}

// View button click handler - DISABLED (using direct links now)
/*
document.addEventListener('DOMContentLoaded', function() {
    console.log('Page loaded. Looking for view buttons...');
    
    // Add click event to ALL view buttons with class "view-details-btn"
    document.querySelectorAll('.view-details-btn').forEach(button => {
        button.addEventListener('click', function(event) {
            event.preventDefault(); // Prevent default behavior
            
            // Get merchant ID from data attribute
            const merchantId = this.getAttribute('data-merchant-id');
            const storeName = this.getAttribute('data-store-name');
            
            console.log('View button clicked!');
            console.log('Merchant ID:', merchantId);
            console.log('Store Name:', storeName);
            
            // Call function to show merchant details
            showMerchantDetails(merchantId, storeName);
        });
    });
    
    console.log('Found', document.querySelectorAll('.view-details-btn').length, 'view buttons');
*/
    
    // Quick approve buttons
    document.querySelectorAll('.quick-approve-btn').forEach(button => {
        button.addEventListener('click', function() {
            const merchantId = this.getAttribute('data-merchant-id');
            const storeName = this.getAttribute('data-store-name');
            
            if (confirm(`Quick approve "${storeName}" without viewing details?`)) {
                approveMerchant(merchantId, storeName);
            }
        });
    });
});

// Function to fetch and display merchant details in YOUR existing modal
async function showMerchantDetails(merchantId, storeName) {
    console.log('showMerchantDetails called for:', merchantId, storeName);
    
    try {
        // Show loading in your existing modal
        const modalContent = document.getElementById('merchantDetailsContent');
        modalContent.innerHTML = `
            <div class="text-center py-5">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-3">Loading ${storeName} details...</p>
            </div>
        `;
        
        // Set the store name in modal title
        document.getElementById('modalStoreName').textContent = storeName;
        
        // Show the existing modal
        const viewModal = new bootstrap.Modal(document.getElementById('viewDetailsModal'));
        viewModal.show();
        
        // Fetch merchant details from API
        console.log('Fetching from: get_merchant_details.php?merchant_id=' + merchantId);
        const response = await fetch('get_merchant_details.php?merchant_id=' + merchantId);
        const result = await response.json();
        
        console.log('API Response:', result);
        
        if (result.success) {
            // Populate the modal with data
            populateMerchantModal(result.data, merchantId);
        } else {
            modalContent.innerHTML = `
                <div class="alert alert-danger">
                    <h5>Error Loading Details</h5>
                    <p>${result.message}</p>
                </div>
            `;
        }
        
    } catch (error) {
        console.error('Error fetching merchant details:', error);
        document.getElementById('merchantDetailsContent').innerHTML = `
            <div class="alert alert-danger">
                <h5>Network Error</h5>
                <p>Failed to load merchant details. Please try again.</p>
            </div>
        `;
    }
}

// Function to populate YOUR existing modal with data
function populateMerchantModal(data, merchantId) {
    const modalContent = document.getElementById('merchantDetailsContent');
    
    // Status configuration
    const statusConfig = {
        'active': { color: 'success', text: 'Active', icon: 'bi-check-circle-fill' },
        'under_review': { color: 'warning', text: 'Under Review', icon: 'bi-clock-history' },
        'inactive': { color: 'danger', text: 'Disabled', icon: 'bi-slash-circle' },
        'rejected': { color: 'secondary', text: 'Rejected', icon: 'bi-x-circle-fill' },
        'setup': { color: 'info', text: 'Setup', icon: 'bi-gear' }
    };
    
    const status = data.merchant.status || 'setup';
    const statusInfo = statusConfig[status] || statusConfig.setup;
    
    // Create the content
    modalContent.innerHTML = `
        <div class="detail-section">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5>Store Information</h5>
                <span class="badge bg-${statusInfo.color}">
                    <i class="bi ${statusInfo.icon} me-1"></i> ${statusInfo.text}
                </span>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="info-grid mb-4">
                        <div class="info-item">
                            <span class="info-label">Store Name:</span>
                            <span class="info-value"><strong>${data.merchant.store_name}</strong></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Owner:</span>
                            <span class="info-value">${data.owner.first_name} ${data.owner.last_name}</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Email:</span>
                            <span class="info-value">${data.owner.email}</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Phone:</span>
                            <span class="info-value">${data.details.store_phone || data.merchant.mobile_phone}</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Business Type:</span>
                            <span class="info-value">${data.merchant.business_type}</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Address:</span>
                            <span class="info-value">${data.merchant.store_address}</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Created:</span>
                            <span class="info-value">${data.merchant.created_at}</span>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-body">
                            <h6><i class="bi bi-briefcase me-2"></i>Business Details</h6>
                            <div class="info-grid">
                                <div class="info-item">
                                    <span class="info-label">Cuisine Types:</span>
                                    <span class="info-value">${formatCuisineTypes(data.details.cuisine_types)}</span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Pickup Instructions:</span>
                                    <span class="info-value">${data.details.pickup_instructions || 'Not specified'}</span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Launch Date:</span>
                                    <span class="info-value">${data.details.launch_date || 'Not set'}</span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Store Hours:</span>
                                    <span class="info-value">${data.details.store_hours || 'Not specified'}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="detail-section">
            <h5><i class="bi bi-images me-2"></i>Menu Photos</h5>
            ${createPhotosHTML(data.menu_photos)}
        </div>
        
        <div class="detail-section">
            <h5><i class="bi bi-bank me-2"></i>Banking Information</h5>
            ${createBankingHTML(data.banking)}
        </div>
    `;
    
    // Show/hide action buttons based on status
    const actionButtons = document.getElementById('actionButtons');
    if (actionButtons) {
        actionButtons.style.display = 'block';
        
        // Set up action buttons
        document.getElementById('rejectBtn').onclick = function() {
            rejectMerchant(merchantId, data.merchant.store_name);
        };
        
        document.getElementById('approveBtn').onclick = function() {
            approveMerchant(merchantId, data.merchant.store_name);
        };
        
        // Add disable button if it doesn't exist
        if (!document.getElementById('disableBtn')) {
            const disableBtn = document.createElement('button');
            disableBtn.type = 'button';
            disableBtn.className = 'btn btn-warning';
            disableBtn.id = 'disableBtn';
            disableBtn.innerHTML = '<i class="bi bi-slash-circle"></i> Disable';
            disableBtn.onclick = function() {
                disableMerchant(merchantId, data.merchant.store_name);
            };
            actionButtons.appendChild(disableBtn);
        }
        
        // Show only relevant buttons based on status
        if (status === 'under_review') {
            document.getElementById('rejectBtn').style.display = 'inline-block';
            document.getElementById('approveBtn').style.display = 'inline-block';
            document.getElementById('disableBtn').style.display = 'none';
        } else if (status === 'active') {
            document.getElementById('rejectBtn').style.display = 'none';
            document.getElementById('approveBtn').style.display = 'none';
            document.getElementById('disableBtn').style.display = 'inline-block';
        } else {
            document.getElementById('rejectBtn').style.display = 'none';
            document.getElementById('approveBtn').style.display = 'none';
            document.getElementById('disableBtn').style.display = 'none';
        }
    }
}

// Helper function to format cuisine types
function formatCuisineTypes(cuisineTypes) {
    if (!cuisineTypes) return 'Not specified';
    
    try {
        let cuisines = cuisineTypes;
        if (typeof cuisineTypes === 'string') {
            cuisines = JSON.parse(cuisineTypes);
        }
        
        if (typeof cuisines === 'string') {
            cuisines = JSON.parse(cuisines);
        }
        
        if (Array.isArray(cuisines) && cuisines.length > 0) {
            return cuisines.map(c => `<span class="badge bg-info me-1">${c}</span>`).join('');
        }
    } catch (e) {
        console.warn('Error parsing cuisine types:', e);
    }
    
    return cuisineTypes;
}

// Helper function to create photos HTML
function createPhotosHTML(photos) {
    if (!photos || photos.length === 0) {
        return '<div class="alert alert-info">No menu photos uploaded</div>';
    }
    
    let html = '<div class="row">';
    photos.forEach((photo, index) => {
        let imagePath = photo.file_path || photo.file_url || '';
        let fullImageUrl = imagePath;
        
        if (imagePath) {
            if (!imagePath.startsWith('/BeU Delivery/merchant/')) {
                if (imagePath.startsWith('uploads/')) {
                    imagePath = imagePath.substring(8);
                }
                if (imagePath.startsWith('/')) {
                    imagePath = imagePath.substring(1);
                }
                fullImageUrl = '/BeU Delivery/merchant/uploads/' + imagePath;
            }
        }
        
        html += `
            <div class="col-md-4 mb-3">
                <div class="card">
                    <img src="${fullImageUrl}" 
                         class="card-img-top" 
                         alt="Menu Photo ${index + 1}"
                         style="height: 150px; object-fit: cover;"
                         onerror="this.onerror=null; this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMzAwIiBoZWlnaHQ9IjE1MCIgdmlld0JveD0iMCAwIDMwMCAxNTAiIGZpbGw9Im5vbmUiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PHJlY3Qgd2lkdGg9IjMwMCIgaGVpZ2h0PSIxNTAiIGZpbGw9IiNlZWVlZWUiLz48dGV4dCB4PSI1MCUiIHk9IjUwJSIgZG9taW5hbnQtYmFzZWxpbmU9Im1pZGRsZSIgdGV4dC1hbmNob3I9Im1pZGRsZSIgZm9udC1mYW1pbHk9IkFyaWFsIiBmb250LXNpemU9IjE0IiBmaWxsPSIjNjY2Ij5JbWFnZSBub3QgZm91bmQ8L3RleHQ+PC9zdmc+';">
                    <div class="card-body p-2">
                        <small class="text-muted">${photo.document_type || 'Menu Photo'}</small>
                    </div>
                </div>
            </div>
        `;
    });
    html += '</div>';
    return html;
}

// Helper function to create banking HTML
function createBankingHTML(banking) {
    if (!banking || !banking.account_holder_name) {
        return '<div class="alert alert-warning">No banking information available</div>';
    }
    
    return `
        <table class="table table-sm">
            <tr>
                <th width="40%">Account Holder:</th>
                <td>${banking.account_holder_name}</td>
            </tr>
            <tr>
                <th>Account Type:</th>
                <td>${banking.account_type}</td>
            </tr>
            <tr>
                <th>Business Name:</th>
                <td>${banking.business_legal_entity_name || 'Not specified'}</td>
            </tr>
            <tr>
                <th>Verified:</th>
                <td>
                    <span class="badge ${banking.verified ? 'bg-success' : 'bg-warning'}">
                        ${banking.verified ? 'Verified' : 'Pending Verification'}
                    </span>
                </td>
            </tr>
        </table>
    `;
}

// Debug: Log all view buttons on page
console.log('All view-details buttons:', document.querySelectorAll('.view-details-btn'));
</script>
</body>
</html>