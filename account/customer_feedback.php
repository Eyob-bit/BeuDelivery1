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

// Handle merchant response submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['respond_to_feedback'])) {
    $feedback_id = $_POST['feedback_id'];
    $response = $_POST['merchant_response'];
    
    $update_sql = "UPDATE customer_feedback SET merchant_response = ?, responded_at = NOW() WHERE id = ? AND merchant_id = ?";
    $stmt = mysqli_prepare($conn, $update_sql);
    mysqli_stmt_bind_param($stmt, "sii", $response, $feedback_id, $merchant_id);
    
    if (mysqli_stmt_execute($stmt)) {
        $success_message = "Response submitted successfully!";
    } else {
        $error_message = "Error submitting response: " . mysqli_error($conn);
    }
}

// Get filter parameters
$rating_filter = $_GET['rating'] ?? '';
$type_filter = $_GET['type'] ?? '';
$sort_by = $_GET['sort'] ?? 'newest';

// Build query with filters
$where_conditions = ["merchant_id = ?"];
$params = [$merchant_id];
$param_types = "i";

if ($rating_filter) {
    $where_conditions[] = "rating = ?";
    $params[] = $rating_filter;
    $param_types .= "i";
}

if ($type_filter) {
    $where_conditions[] = "feedback_type = ?";
    $params[] = $type_filter;
    $param_types .= "s";
}

$where_clause = implode(" AND ", $where_conditions);

// Set order by
$order_by = match($sort_by) {
    'oldest' => 'created_at ASC',
    'rating_high' => 'rating DESC, created_at DESC',
    'rating_low' => 'rating ASC, created_at DESC',
    default => 'created_at DESC'
};

// Get feedback with pagination
$page = $_GET['page'] ?? 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

$feedback_sql = "SELECT * FROM customer_feedback WHERE $where_clause ORDER BY $order_by LIMIT $per_page OFFSET $offset";
$stmt = mysqli_prepare($conn, $feedback_sql);
mysqli_stmt_bind_param($stmt, $param_types, ...$params);
mysqli_stmt_execute($stmt);
$feedback_result = mysqli_stmt_get_result($stmt);

// Get total count for pagination
$count_sql = "SELECT COUNT(*) as total FROM customer_feedback WHERE $where_clause";
$count_stmt = mysqli_prepare($conn, $count_sql);
mysqli_stmt_bind_param($count_stmt, $param_types, ...$params);
mysqli_stmt_execute($count_stmt);
$count_result = mysqli_stmt_get_result($count_stmt);
$total_feedback = mysqli_fetch_assoc($count_result)['total'];
$total_pages = ceil($total_feedback / $per_page);

// Get feedback statistics
$stats_sql = "SELECT 
    COUNT(*) as total_feedback,
    AVG(rating) as avg_rating,
    COUNT(CASE WHEN rating = 5 THEN 1 END) as five_star,
    COUNT(CASE WHEN rating = 4 THEN 1 END) as four_star,
    COUNT(CASE WHEN rating = 3 THEN 1 END) as three_star,
    COUNT(CASE WHEN rating = 2 THEN 1 END) as two_star,
    COUNT(CASE WHEN rating = 1 THEN 1 END) as one_star,
    COUNT(CASE WHEN merchant_response IS NOT NULL THEN 1 END) as responded
FROM customer_feedback WHERE merchant_id = ?";
$stats_stmt = mysqli_prepare($conn, $stats_sql);
mysqli_stmt_bind_param($stats_stmt, "i", $merchant_id);
mysqli_stmt_execute($stats_stmt);
$stats_result = mysqli_stmt_get_result($stats_stmt);
$stats = mysqli_fetch_assoc($stats_result);

$page_title = "Customer Feedback";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - BeU Delivery</title>
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
            --warning-color: #ffc107;
            --danger-color: #dc3545;
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
            margin-bottom: 20px;
        }
        
        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            border-color: var(--primary-color);
        }
        
        .feedback-card {
            background: var(--secondary-color);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            transition: all 0.3s;
        }
        
        .feedback-card:hover {
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .rating-stars {
            color: var(--warning-color);
        }
        
        .rating-bar {
            height: 8px;
            background-color: var(--accent-color);
            border-radius: 4px;
            overflow: hidden;
        }
        
        .rating-fill {
            height: 100%;
            background-color: var(--warning-color);
            transition: width 0.3s;
        }
        
        .feedback-type-badge {
            font-size: 12px;
            padding: 4px 10px;
            border-radius: 15px;
            font-weight: 500;
        }
        
        .type-food { background-color: #e3f2fd; color: #1976d2; }
        .type-service { background-color: #f3e5f5; color: #7b1fa2; }
        .type-delivery { background-color: #e8f5e8; color: #388e3c; }
        .type-order { background-color: #fff3e0; color: #f57c00; }
        .type-general { background-color: #f5f5f5; color: #616161; }
        
        .response-form {
            background-color: var(--accent-color);
            border-radius: 8px;
            padding: 15px;
            margin-top: 15px;
        }
        
        .merchant-response {
            background-color: #e8f5e8;
            border-left: 4px solid var(--success-color);
            padding: 15px;
            margin-top: 15px;
            border-radius: 0 8px 8px 0;
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
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2><i class="bi bi-chat-dots me-2"></i>Customer Feedback</h2>
                    <p class="text-muted mb-0">View and respond to customer reviews and feedback</p>
                </div>
            </div>
            
            <?php if (isset($success_message)): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <i class="bi bi-check-circle me-2"></i><?php echo $success_message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <i class="bi bi-exclamation-triangle me-2"></i><?php echo $error_message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <!-- Statistics Cards -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="stat-card text-center">
                        <div class="stat-icon mx-auto mb-3">
                            <i class="bi bi-chat-dots"></i>
                        </div>
                        <div class="stat-value"><?php echo number_format($stats['total_feedback']); ?></div>
                        <div class="stat-label">Total Reviews</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card text-center">
                        <div class="stat-icon mx-auto mb-3">
                            <i class="bi bi-star-fill text-warning"></i>
                        </div>
                        <div class="stat-value"><?php echo number_format($stats['avg_rating'], 1); ?></div>
                        <div class="stat-label">Average Rating</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card text-center">
                        <div class="stat-icon mx-auto mb-3">
                            <i class="bi bi-reply"></i>
                        </div>
                        <div class="stat-value"><?php echo number_format($stats['responded']); ?></div>
                        <div class="stat-label">Responses Sent</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card text-center">
                        <div class="stat-icon mx-auto mb-3">
                            <i class="bi bi-percent"></i>
                        </div>
                        <div class="stat-value"><?php echo $stats['total_feedback'] > 0 ? number_format(($stats['responded'] / $stats['total_feedback']) * 100, 1) : 0; ?>%</div>
                        <div class="stat-label">Response Rate</div>
                    </div>
                </div>
            </div>
            
            <!-- Rating Breakdown -->
            <div class="stat-card mb-4">
                <h5 class="mb-3">Rating Breakdown</h5>
                <div class="row">
                    <?php for ($i = 5; $i >= 1; $i--): ?>
                    <div class="col-md-2 mb-3">
                        <div class="d-flex align-items-center mb-2">
                            <span class="me-2"><?php echo $i; ?> <i class="bi bi-star-fill text-warning"></i></span>
                            <div class="rating-bar flex-grow-1 me-2">
                                <div class="rating-fill" style="width: <?php echo $stats['total_feedback'] > 0 ? ($stats[strtolower(number_to_words($i)) . '_star'] / $stats['total_feedback']) * 100 : 0; ?>%"></div>
                            </div>
                            <small class="text-muted"><?php echo $stats[strtolower(number_to_words($i)) . '_star']; ?></small>
                        </div>
                    </div>
                    <?php endfor; ?>
                </div>
            </div>
            
            <!-- Filters -->
            <div class="stat-card mb-4">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <form method="GET" class="row g-3">
                            <div class="col-md-3">
                                <select name="rating" class="form-select">
                                    <option value="">All Ratings</option>
                                    <option value="5" <?php echo $rating_filter == '5' ? 'selected' : ''; ?>>5 Stars</option>
                                    <option value="4" <?php echo $rating_filter == '4' ? 'selected' : ''; ?>>4 Stars</option>
                                    <option value="3" <?php echo $rating_filter == '3' ? 'selected' : ''; ?>>3 Stars</option>
                                    <option value="2" <?php echo $rating_filter == '2' ? 'selected' : ''; ?>>2 Stars</option>
                                    <option value="1" <?php echo $rating_filter == '1' ? 'selected' : ''; ?>>1 Star</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <select name="type" class="form-select">
                                    <option value="">All Types</option>
                                    <option value="food" <?php echo $type_filter == 'food' ? 'selected' : ''; ?>>Food</option>
                                    <option value="service" <?php echo $type_filter == 'service' ? 'selected' : ''; ?>>Service</option>
                                    <option value="delivery" <?php echo $type_filter == 'delivery' ? 'selected' : ''; ?>>Delivery</option>
                                    <option value="order" <?php echo $type_filter == 'order' ? 'selected' : ''; ?>>Order</option>
                                    <option value="general" <?php echo $type_filter == 'general' ? 'selected' : ''; ?>>General</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <select name="sort" class="form-select">
                                    <option value="newest" <?php echo $sort_by == 'newest' ? 'selected' : ''; ?>>Newest First</option>
                                    <option value="oldest" <?php echo $sort_by == 'oldest' ? 'selected' : ''; ?>>Oldest First</option>
                                    <option value="rating_high" <?php echo $sort_by == 'rating_high' ? 'selected' : ''; ?>>Highest Rating</option>
                                    <option value="rating_low" <?php echo $sort_by == 'rating_low' ? 'selected' : ''; ?>>Lowest Rating</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="bi bi-funnel me-1"></i> Filter
                                </button>
                            </div>
                        </form>
                    </div>
                    <div class="col-md-4 text-end">
                        <span class="text-muted">Showing <?php echo mysqli_num_rows($feedback_result); ?> of <?php echo $total_feedback; ?> reviews</span>
                    </div>
                </div>
            </div>
            
            <!-- Feedback List -->
            <?php if (mysqli_num_rows($feedback_result) > 0): ?>
                <?php while ($feedback = mysqli_fetch_assoc($feedback_result)): ?>
                <div class="feedback-card">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <h6 class="mb-1"><?php echo htmlspecialchars($feedback['customer_name']); ?></h6>
                            <div class="rating-stars mb-2">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <i class="bi bi-star<?php echo $i <= $feedback['rating'] ? '-fill' : ''; ?>"></i>
                                <?php endfor; ?>
                                <span class="ms-2 text-muted"><?php echo $feedback['rating']; ?>/5</span>
                            </div>
                            <span class="feedback-type-badge type-<?php echo $feedback['feedback_type']; ?>">
                                <?php echo ucfirst($feedback['feedback_type']); ?>
                            </span>
                        </div>
                        <div class="text-end">
                            <small class="text-muted">
                                <i class="bi bi-calendar me-1"></i>
                                <?php echo date('M j, Y', strtotime($feedback['created_at'])); ?>
                            </small>
                            <?php if ($feedback['order_id']): ?>
                                <br><small class="text-muted">Order #<?php echo $feedback['order_id']; ?></small>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <p class="mb-0"><?php echo nl2br(htmlspecialchars($feedback['feedback_text'])); ?></p>
                    </div>
                    
                    <?php if ($feedback['merchant_response']): ?>
                        <div class="merchant-response">
                            <div class="d-flex align-items-center mb-2">
                                <i class="bi bi-reply me-2"></i>
                                <strong>Your Response</strong>
                                <small class="text-muted ms-auto">
                                    <?php echo date('M j, Y g:i A', strtotime($feedback['responded_at'])); ?>
                                </small>
                            </div>
                            <p class="mb-0"><?php echo nl2br(htmlspecialchars($feedback['merchant_response'])); ?></p>
                        </div>
                    <?php else: ?>
                        <div class="response-form">
                            <form method="POST" class="mb-0">
                                <input type="hidden" name="feedback_id" value="<?php echo $feedback['id']; ?>">
                                <div class="mb-3">
                                    <label class="form-label">
                                        <i class="bi bi-reply me-1"></i>Respond to this feedback
                                    </label>
                                    <textarea name="merchant_response" class="form-control" rows="3" 
                                              placeholder="Thank the customer and address their feedback..." required></textarea>
                                </div>
                                <button type="submit" name="respond_to_feedback" class="btn btn-primary btn-sm">
                                    <i class="bi bi-send me-1"></i>Send Response
                                </button>
                            </form>
                        </div>
                    <?php endif; ?>
                </div>
                <?php endwhile; ?>
                
                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                <nav aria-label="Feedback pagination">
                    <ul class="pagination justify-content-center">
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page - 1; ?>&rating=<?php echo $rating_filter; ?>&type=<?php echo $type_filter; ?>&sort=<?php echo $sort_by; ?>">
                                    <i class="bi bi-chevron-left"></i>
                                </a>
                            </li>
                        <?php endif; ?>
                        
                        <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                            <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?>&rating=<?php echo $rating_filter; ?>&type=<?php echo $type_filter; ?>&sort=<?php echo $sort_by; ?>">
                                    <?php echo $i; ?>
                                </a>
                            </li>
                        <?php endfor; ?>
                        
                        <?php if ($page < $total_pages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page + 1; ?>&rating=<?php echo $rating_filter; ?>&type=<?php echo $type_filter; ?>&sort=<?php echo $sort_by; ?>">
                                    <i class="bi bi-chevron-right"></i>
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
                <?php endif; ?>
                
            <?php else: ?>
                <div class="stat-card text-center py-5">
                    <i class="bi bi-chat-dots display-4 text-muted mb-3"></i>
                    <h5>No Customer Feedback Yet</h5>
                    <p class="text-muted mb-0">Customer reviews and feedback will appear here once you start receiving orders.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
// Helper function to convert numbers to words for rating breakdown
function number_to_words($number) {
    $words = ['', 'one', 'two', 'three', 'four', 'five'];
    return $words[$number] ?? '';
}
?>