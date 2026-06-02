<?php
// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

// Include database
require_once "../includes/db.php";

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

// Get search/filter parameters
$search = $_GET['search'] ?? '';
$category = $_GET['category'] ?? '';
$delivery_type = $_GET['delivery'] ?? '';
$sort = $_GET['sort'] ?? 'default';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 20; // Items per page
$offset = ($page - 1) * $limit;

// Build query
$where_conditions = ["m.status IN ('active', 'setup')"];
$params = [];
$param_types = "";

if (!empty($search)) {
    $where_conditions[] = "(m.store_name LIKE ? OR m.brand_name LIKE ? OR md.cuisine_types LIKE ? OR sc.name LIKE ?)";
    $search_term = "%" . $search . "%";
    array_push($params, $search_term, $search_term, $search_term, $search_term);
    $param_types .= "ssss";
}

if (!empty($category) && $category != 'all') {
    $where_conditions[] = "sc.name = ?";
    $params[] = $category;
    $param_types .= "s";
}

if (!empty($delivery_type)) {
    if ($delivery_type == 'delivery') {
        $where_conditions[] = "ds.is_delivery_available = 1";
    } elseif ($delivery_type == 'pickup') {
        $where_conditions[] = "ds.is_pickup_available = 1";
    }
}

$where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Order by
$order_by = "ORDER BY ";
switch ($sort) {
    case 'rating': 
        $order_by .= "m.rating DESC, m.review_count DESC"; 
        break;
    case 'delivery_time': 
        $order_by .= "ds.estimated_delivery_time ASC"; 
        break;
    case 'name': 
        $order_by .= "m.store_name ASC"; 
        break;
    case 'featured': 
        $order_by .= "m.is_featured DESC, m.created_at DESC"; 
        break;
    default: 
        $order_by .= "m.created_at DESC"; 
        break;
}

// Get total count for pagination
$count_sql = "SELECT COUNT(DISTINCT m.merchant_id) as total 
              FROM merchants m
              LEFT JOIN merchant_details md ON m.merchant_id = md.merchant_id
              LEFT JOIN delivery_settings ds ON m.merchant_id = ds.merchant_id
              LEFT JOIN store_categories sc ON m.category_id = sc.category_id
              $where_clause";
              
$stmt = mysqli_prepare($conn, $count_sql);
if (!empty($params)) {
    mysqli_stmt_bind_param($stmt, $param_types, ...$params);
}
mysqli_stmt_execute($stmt);
$count_result = mysqli_stmt_get_result($stmt);
$total_count = mysqli_fetch_assoc($count_result)['total'];
$total_pages = ceil($total_count / $limit);

// Get stores with all details including store images
$stores_sql = "SELECT 
                m.merchant_id,
                m.store_name,
                m.store_address,
                m.featured_image,
                m.store_type,
                m.is_featured,
                m.rating,
                m.review_count,
                sc.name as category_name,
                sc.icon as category_icon,
                md.cuisine_types,
                md.store_phone,
                ds.delivery_fee,
                ds.estimated_delivery_time,
                ds.is_delivery_available,
                ds.is_pickup_available,
                ds.min_order_amount,
                (SELECT COUNT(*) FROM menu_items mi WHERE mi.merchant_id = m.merchant_id) as menu_item_count,
                (SELECT si.image_path FROM store_images si WHERE si.merchant_id = m.merchant_id ORDER BY si.display_order LIMIT 1) as store_image_path
              FROM merchants m
              LEFT JOIN merchant_details md ON m.merchant_id = md.merchant_id
              LEFT JOIN delivery_settings ds ON m.merchant_id = ds.merchant_id
              LEFT JOIN store_categories sc ON m.category_id = sc.category_id
              $where_clause
              $order_by
              LIMIT ? OFFSET ?";
              
$params[] = $limit;
$params[] = $offset;
$param_types .= "ii";

$stmt = mysqli_prepare($conn, $stores_sql);
if (!empty($params)) {
    mysqli_stmt_bind_param($stmt, $param_types, ...$params);
}
mysqli_stmt_execute($stmt);
$stores_result = mysqli_stmt_get_result($stmt);

// Get all categories for filter
$categories_sql = "SELECT * FROM store_categories WHERE is_active = 1 ORDER BY name";
$categories_result = mysqli_query($conn, $categories_sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BeU Delivery - Find Stores & Order Online</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        :root {
            --primary-color: #000000;
            --secondary-color: #ffffff;
            --accent-color: #f5f5f5;
            --text-color: #333333;
            --border-color: #e0e0e0;
        }
        
        body {
            background-color: var(--secondary-color);
            color: var(--text-color);
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
        }
        
        .navbar {
            background-color: var(--primary-color) !important;
            border-bottom: 1px solid var(--border-color);
        }
        
        .navbar-brand, .nav-link {
            color: var(--secondary-color) !important;
        }
        
        .nav-link:hover {
            color: #cccccc !important;
        }
        
        .hero-section {
            background: linear-gradient(rgba(0,0,0,0.9), rgba(0,0,0,0.8)), 
                        url('../public/images/hero-bg.jpg');
            background-size: cover;
            background-position: center;
            color: var(--secondary-color);
            padding: 100px 0 80px;
            margin-bottom: 40px;
        }
        
        .hero-title {
            font-weight: 800;
            font-size: 3.5rem;
            margin-bottom: 1rem;
            text-transform: uppercase;
            letter-spacing: -0.5px;
        }
        
        .search-container {
            max-width: 800px;
            margin: 0 auto;
        }
        
        .search-box {
            background: var(--secondary-color);
            border-radius: 50px;
            padding: 5px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
        }
        
        .search-box input {
            border: none;
            padding: 20px 25px;
            font-size: 1.1rem;
            border-radius: 50px;
        }
        
        .search-box input:focus {
            outline: none;
            box-shadow: none;
        }
        
        .search-btn {
            background: var(--primary-color);
            color: var(--secondary-color);
            border: none;
            border-radius: 50px;
            padding: 15px 35px;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .search-btn:hover {
            background: #333333;
        }
        
        .category-filter {
            background: var(--accent-color);
            padding: 20px 0;
            margin-bottom: 30px;
            border-bottom: 1px solid var(--border-color);
        }
        
        .category-btn {
            background: transparent;
            border: 2px solid var(--border-color);
            color: var(--text-color);
            padding: 10px 20px;
            border-radius: 50px;
            margin: 0 5px 10px;
            transition: all 0.3s;
            font-weight: 500;
        }
        
        .category-btn:hover,
        .category-btn.active {
            background: var(--primary-color);
            color: var(--secondary-color);
            border-color: var(--primary-color);
        }
        
        .store-card {
            background: var(--secondary-color);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            overflow: hidden;
            transition: all 0.3s;
            margin-bottom: 25px;
            height: 100%;
        }
        
        .store-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            border-color: var(--primary-color);
        }
        
        .store-image {
            height: 200px;
            object-fit: cover;
            width: 100%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .store-image[src*="store-default"] {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 48px;
            color: white;
        }
        
        .store-image[src*="store-default"]::before {
            content: '🍽️';
        }
        
        .store-badge {
            position: absolute;
            top: 15px;
            left: 15px;
            background: var(--primary-color);
            color: var(--secondary-color);
            padding: 5px 12px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .featured-badge {
            background: #ff6b6b;
        }
        
        .store-info {
            padding: 20px;
        }
        
        .store-title {
            font-weight: 700;
            font-size: 1.25rem;
            margin-bottom: 5px;
            color: var(--primary-color);
        }
        
        .store-category {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 10px;
        }
        
        .store-rating {
            display: inline-flex;
            align-items: center;
            background: var(--accent-color);
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 0.9rem;
            margin-bottom: 10px;
        }
        
        .store-rating .stars {
            color: #ffc107;
            margin-right: 5px;
        }
        
        .store-details {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid var(--border-color);
        }
        
        .detail-item {
            display: flex;
            align-items: center;
            margin-bottom: 8px;
            font-size: 0.9rem;
            color: #666;
        }
        
        .detail-item i {
            width: 20px;
            margin-right: 8px;
            color: var(--primary-color);
        }
        
        .view-store-btn {
            background: var(--primary-color);
            color: var(--secondary-color);
            border: none;
            border-radius: 8px;
            padding: 12px;
            width: 100%;
            font-weight: 600;
            transition: all 0.3s;
            margin-top: 15px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .view-store-btn:hover {
            background: #333333;
            color: var(--secondary-color);
        }
        
        .pagination-container {
            margin-top: 50px;
        }
        
        .page-link {
            color: var(--primary-color);
            border: 1px solid var(--border-color);
            padding: 10px 18px;
            margin: 0 5px;
            border-radius: 8px;
        }
        
        .page-link:hover {
            background: var(--primary-color);
            color: var(--secondary-color);
            border-color: var(--primary-color);
        }
        
        .page-item.active .page-link {
            background: var(--primary-color);
            color: var(--secondary-color);
            border-color: var(--primary-color);
        }
        
        .results-info {
            color: #666;
            font-size: 0.95rem;
        }
        
        .sort-dropdown .btn {
            border: 1px solid var(--border-color);
            color: var(--text-color);
            background: var(--secondary-color);
        }
        
        .empty-state {
            text-align: center;
            padding: 80px 20px;
        }
        
        .empty-state i {
            font-size: 4rem;
            color: #ddd;
            margin-bottom: 20px;
        }
        
        .footer {
            background: var(--primary-color);
            color: var(--secondary-color);
            padding: 60px 0 30px;
            margin-top: 80px;
        }
        
        .footer a {
            color: #cccccc;
            text-decoration: none;
            transition: color 0.3s;
        }
        
        .footer a:hover {
            color: var(--secondary-color);
        }
        
        .copyright {
            border-top: 1px solid #333;
            padding-top: 20px;
            margin-top: 40px;
            color: #888;
            font-size: 0.9rem;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .hero-title {
                font-size: 2.5rem;
            }
            
            .search-box input {
                padding: 15px 20px;
            }
            
            .store-image {
                height: 180px;
            }
            
            .category-btn {
                padding: 8px 15px;
                font-size: 0.9rem;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark sticky-top">
        <div class="container">
            <a class="navbar-brand fw-bold" href="home.php">
                <i class="bi bi-basket3 me-2"></i> BEU DELIVERY
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="home.php">
                            <i class="bi bi-house-door me-1"></i> Home
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="orders.php">
                            <i class="bi bi-receipt me-1"></i> Orders
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="cart.php">
                            <i class="bi bi-cart3 me-1"></i> Cart
                        </a>
                    </li>
                </ul>
                
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle me-1"></i> 
                            <?php 
                            echo isset($_SESSION['user_name']) ? 
                                htmlspecialchars(explode(' ', $_SESSION['user_name'])[0]) : 'Account';
                            ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="profile.php">
                                <i class="bi bi-person me-2"></i> Profile
                            </a></li>
                            <li><a class="dropdown-item" href="orders.php">
                                <i class="bi bi-receipt me-2"></i> My Orders
                            </a></li>
                            <li><a class="dropdown-item" href="favorites.php">
                                <i class="bi bi-heart me-2"></i> Favorites
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="../merchant/getStarted.php">
                                <i class="bi bi-shop me-2"></i> Add Your Restaurant
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="../auth/logout.php">
                                <i class="bi bi-box-arrow-right me-2"></i> Logout
                            </a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    
    <!-- Hero Section with Search -->
    <div class="hero-section">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8 text-center">
                    <h1 class="hero-title">Discover & Order</h1>
                    <p class="lead mb-5">Find the best stores in your city and get it delivered to your doorstep</p>
                    
                    <!-- Search Form -->
                    <form method="GET" action="" class="search-container">
                        <div class="search-box">
                            <div class="input-group">
                                <input type="text" class="form-control" name="search" 
                                       placeholder="Search for stores, cuisine, or items..." 
                                       value="<?php echo htmlspecialchars($search); ?>">
                                <button class="btn search-btn" type="submit">
                                    <i class="bi bi-search me-2"></i> Search
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Become a Merchant Banner -->
    <div class="container my-4">
        <div class="alert alert-dark d-flex align-items-center justify-content-between" style="border-radius: 12px; border: 2px solid #000;">
            <div class="d-flex align-items-center">
                <i class="bi bi-shop" style="font-size: 2rem; margin-right: 15px;"></i>
                <div>
                    <h5 class="mb-1">Own a Restaurant?</h5>
                    <p class="mb-0">Partner with us and reach thousands of customers</p>
                </div>
            </div>
            <a href="../merchant/getStarted.php" class="btn btn-light btn-lg">
                <i class="bi bi-plus-circle me-2"></i> Add Your Restaurant
            </a>
        </div>
    </div>
    
    <!-- Category Filter -->
    <div class="category-filter">
        <div class="container">
            <div class="d-flex flex-wrap justify-content-center">
                <a href="?<?php echo buildQueryString(['category' => 'all']); ?>" 
                   class="category-btn <?php echo (empty($category) || $category == 'all') ? 'active' : ''; ?>">
                    <i class="bi bi-grid-3x3-gap me-2"></i> All Stores
                </a>
                
                <?php while($cat = mysqli_fetch_assoc($categories_result)): ?>
                <a href="?<?php echo buildQueryString(['category' => $cat['name']]); ?>" 
                   class="category-btn <?php echo ($category == $cat['name']) ? 'active' : ''; ?>">
                    <i class="bi <?php echo htmlspecialchars($cat['icon']); ?> me-2"></i>
                    <?php echo htmlspecialchars($cat['name']); ?>
                </a>
                <?php endwhile; ?>
            </div>
        </div>
    </div>
    
    <!-- Main Content -->
    <div class="container">
        <!-- Results Header -->
        <div class="row mb-4 align-items-center">
            <div class="col-md-6">
                <h3 class="mb-0">Available Stores</h3>
                <p class="results-info mb-0">
                    Showing <?php echo ($total_count > 0) ? (($page-1)*$limit + 1) : 0; ?> - 
                    <?php echo min($page*$limit, $total_count); ?> of <?php echo $total_count; ?> stores
                </p>
            </div>
            <div class="col-md-6 text-md-end">
                <div class="sort-dropdown d-inline-block">
                    <div class="btn-group">
                        <button type="button" class="btn dropdown-toggle" data-bs-toggle="dropdown">
                            Sort by: 
                            <?php 
                            $sort_labels = [
                                'default' => 'Newest',
                                'rating' => 'Rating',
                                'delivery_time' => 'Delivery Time',
                                'name' => 'Name A-Z',
                                'featured' => 'Featured'
                            ];
                            echo $sort_labels[$sort] ?? 'Newest';
                            ?>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="?<?php echo buildQueryString(['sort' => 'default']); ?>">Newest</a></li>
                            <li><a class="dropdown-item" href="?<?php echo buildQueryString(['sort' => 'rating']); ?>">Rating</a></li>
                            <li><a class="dropdown-item" href="?<?php echo buildQueryString(['sort' => 'delivery_time']); ?>">Delivery Time</a></li>
                            <li><a class="dropdown-item" href="?<?php echo buildQueryString(['sort' => 'name']); ?>">Name A-Z</a></li>
                            <li><a class="dropdown-item" href="?<?php echo buildQueryString(['sort' => 'featured']); ?>">Featured</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Stores Grid -->
        <?php if (mysqli_num_rows($stores_result) > 0): ?>
            <div class="row">
                <?php while($store = mysqli_fetch_assoc($stores_result)): 
                    // Parse cuisine types
                    $cuisine_list = [];
                    if (!empty($store['cuisine_types'])) {
                        $cuisine_data = json_decode($store['cuisine_types'], true);
                        if (is_array($cuisine_data) && isset($cuisine_data[0])) {
                            $cuisine_list = json_decode($cuisine_data[0], true);
                        }
                    }
                    
                    // Get store image from store_images table
                    $store_image = null;
                    
                    if (!empty($store['store_image_path'])) {
                        // Use the image from store_images table
                        $store_image = '../' . $store['store_image_path'];
                        
                        // Verify file exists using absolute path
                        $absolute_path = __DIR__ . '/../' . $store['store_image_path'];
                        if (!file_exists($absolute_path)) {
                            $store_image = null;
                        }
                    }
                    
                    // If no store image, try featured_image from merchants table
                    if (!$store_image && !empty($store['featured_image'])) {
                        $possible_paths = [
                            'account/uploads/store_images/' . $store['featured_image'],
                            'merchant/uploads/' . $store['featured_image'],
                            'uploads/merchants/' . $store['featured_image']
                        ];
                        
                        foreach ($possible_paths as $path) {
                            $absolute_path = __DIR__ . '/../' . $path;
                            if (file_exists($absolute_path)) {
                                $store_image = '../' . $path;
                                break;
                            }
                        }
                    }
                    
                    // If still no image, use default placeholder
                    if (!$store_image) {
                        $store_image = '../public/images/store-default.jpg';
                    }
                ?>
                <div class="col-lg-4 col-md-6">
                    <div class="store-card">
                        <!-- Store Image -->
                        <div style="position: relative;">
                            <img src="<?php echo $store_image; ?>" 
                                 alt="<?php echo htmlspecialchars($store['store_name']); ?>" 
                                 class="store-image" 
                                 onerror="this.src='../public/images/store-default.jpg'">
                            
                            <?php if ($store['is_featured']): ?>
                            <span class="store-badge featured-badge">
                                <i class="bi bi-star-fill me-1"></i> Featured
                            </span>
                            <?php endif; ?>
                            
                            <?php if ($store['category_name']): ?>
                            <span class="store-badge" style="top: 45px;">
                                <i class="bi <?php echo htmlspecialchars($store['category_icon']); ?> me-1"></i>
                                <?php echo htmlspecialchars($store['category_name']); ?>
                            </span>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Store Info -->
                        <div class="store-info">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h4 class="store-title"><?php echo htmlspecialchars($store['store_name']); ?></h4>
                                    <div class="store-category">
                                        <?php if (!empty($cuisine_list) && is_array($cuisine_list)): ?>
                                        <?php echo htmlspecialchars(implode(', ', array_slice($cuisine_list, 0, 2))); ?>
                                        <?php if (count($cuisine_list) > 2): ?>...<?php endif; ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <?php if ($store['rating'] > 0): ?>
                                <div class="store-rating">
                                    <span class="stars">
                                        <i class="bi bi-star-fill"></i>
                                        <?php echo number_format($store['rating'], 1); ?>
                                    </span>
                                    <small class="text-muted">(<?php echo $store['review_count']; ?>)</small>
                                </div>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Store Details -->
                            <div class="store-details">
                                <div class="detail-item">
                                    <i class="bi bi-geo-alt"></i>
                                    <span><?php echo htmlspecialchars($store['store_address']); ?></span>
                                </div>
                                
                                <?php if ($store['estimated_delivery_time']): ?>
                                <div class="detail-item">
                                    <i class="bi bi-clock"></i>
                                    <span><?php echo $store['estimated_delivery_time']; ?> min delivery</span>
                                </div>
                                <?php endif; ?>
                                
                                <?php if ($store['delivery_fee']): ?>
                                <div class="detail-item">
                                    <i class="bi bi-truck"></i>
                                    <span>
                                        <?php if ($store['delivery_fee'] == 0): ?>
                                        Free delivery
                                        <?php else: ?>
                                        $<?php echo number_format($store['delivery_fee'], 2); ?> delivery
                                        <?php endif; ?>
                                    </span>
                                </div>
                                <?php endif; ?>
                                
                                <?php if ($store['menu_item_count'] > 0): ?>
                                <div class="detail-item">
                                    <i class="bi bi-menu-button-wide"></i>
                                    <span><?php echo $store['menu_item_count']; ?> menu items</span>
                                </div>
                                <?php endif; ?>
                            </div>
                            
                            <!-- View Store Button -->
                            <a href="store.php?id=<?php echo $store['merchant_id']; ?>" class="btn view-store-btn">
                                <i class="bi bi-shop me-2"></i> Browse Menu
                            </a>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
            
            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
            <div class="pagination-container">
                <nav aria-label="Page navigation">
                    <ul class="pagination justify-content-center">
                        <?php if ($page > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?<?php echo buildQueryString(['page' => $page - 1]); ?>">
                                <i class="bi bi-chevron-left"></i> Previous
                            </a>
                        </li>
                        <?php endif; ?>
                        
                        <?php 
                        // Show page numbers
                        $start_page = max(1, $page - 2);
                        $end_page = min($total_pages, $page + 2);
                        
                        for ($i = $start_page; $i <= $end_page; $i++): 
                        ?>
                        <li class="page-item <?php echo ($i == $page) ? 'active' : ''; ?>">
                            <a class="page-link" href="?<?php echo buildQueryString(['page' => $i]); ?>">
                                <?php echo $i; ?>
                            </a>
                        </li>
                        <?php endfor; ?>
                        
                        <?php if ($page < $total_pages): ?>
                        <li class="page-item">
                            <a class="page-link" href="?<?php echo buildQueryString(['page' => $page + 1]); ?>">
                                Next <i class="bi bi-chevron-right"></i>
                            </a>
                        </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            </div>
            <?php endif; ?>
            
        <?php else: ?>
            <!-- Empty State -->
            <div class="empty-state">
                <i class="bi bi-shop"></i>
                <h3 class="mt-4">No stores found</h3>
                <p class="text-muted mb-4">
                    <?php if (!empty($search)): ?>
                    No stores match your search "<?php echo htmlspecialchars($search); ?>"
                    <?php else: ?>
                    No stores are currently available. Check back soon!
                    <?php endif; ?>
                </p>
                <?php if (!empty($search)): ?>
                <a href="home.php" class="btn btn-dark">
                    <i class="bi bi-arrow-left me-2"></i> Clear Search
                </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="row">
                <div class="col-lg-4 mb-4">
                    <h4 class="mb-4">BEU DELIVERY</h4>
                    <p>Your trusted partner for food and goods delivery. Fast, reliable, and convenient.</p>
                </div>
                
                <div class="col-lg-2 col-md-4 mb-4">
                    <h5 class="mb-4">Quick Links</h5>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="home.php">Home</a></li>
                        <li class="mb-2"><a href="orders.php">My Orders</a></li>
                        <li class="mb-2"><a href="cart.php">Cart</a></li>
                        <li class="mb-2"><a href="profile.php">Profile</a></li>
                    </ul>
                </div>
                
                <div class="col-lg-3 col-md-4 mb-4">
                    <h5 class="mb-4">For Stores</h5>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="../merchant/getStarted.php">Add Your Store</a></li>
                        <li class="mb-2"><a href="#">Merchant Portal</a></li>
                        <li class="mb-2"><a href="#">Partner Program</a></li>
                    </ul>
                </div>
                
                <div class="col-lg-3 col-md-4 mb-4">
                    <h5 class="mb-4">Contact Us</h5>
                    <ul class="list-unstyled">
                        <li class="mb-2">
                            <i class="bi bi-telephone me-2"></i> 1-800-BEU-FOOD
                        </li>
                        <li class="mb-2">
                            <i class="bi bi-envelope me-2"></i> support@beudelivery.com
                        </li>
                        <li class="mb-2">
                            <i class="bi bi-geo-alt me-2"></i> Addis Ababa, Ethiopia
                        </li>
                    </ul>
                </div>
            </div>
            
            <div class="copyright text-center">
                <p class="mb-0">&copy; <?php echo date('Y'); ?> BeU Delivery. All rights reserved.</p>
            </div>
        </div>
    </footer>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Live search functionality
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.querySelector('input[name="search"]');
        let searchTimeout;
        
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(function() {
                if (searchInput.value.length >= 2 || searchInput.value.length === 0) {
                    // Submit form
                    const form = searchInput.closest('form');
                    const urlParams = new URLSearchParams(window.location.search);
                    urlParams.set('search', searchInput.value);
                    urlParams.set('page', '1'); // Reset to first page on search
                    window.location.href = 'home.php?' + urlParams.toString();
                }
            }, 500);
        });

        // Add to the existing script
const searchForm = document.querySelector('form[method="GET"]');
searchForm.addEventListener('submit', function(e) {
    e.preventDefault();
    const searchInput = this.querySelector('input[name="search"]');
    const urlParams = new URLSearchParams(window.location.search);
    urlParams.set('search', searchInput.value);
    urlParams.set('page', '1');
    window.location.href = 'home.php?' + urlParams.toString();
});
        
        // Category filter animation
        const categoryBtns = document.querySelectorAll('.category-btn');
        categoryBtns.forEach(btn => {
            btn.addEventListener('click', function(e) {
                categoryBtns.forEach(b => b.classList.remove('active'));
                this.classList.add('active');
            });
        });
    });
    
    // Helper function to build query string (defined in PHP)
    function buildQueryString(newParams) {
        // This function is implemented in PHP below
        return '';
    }
    </script>
</body>
</html>
<?php
// Helper function to build query string while preserving existing parameters
function buildQueryString($newParams = []) {
    $params = $_GET;
    
    // Remove page parameter when changing filters
    if (isset($newParams['category']) || isset($newParams['delivery']) || isset($newParams['sort'])) {
        unset($params['page']);
    }
    
    // Merge new parameters
    $params = array_merge($params, $newParams);
    
    // Remove empty parameters
    $params = array_filter($params);
    
    return http_build_query($params);
}
?>