<?php
session_start();
include "../includes/db.php";

// Check if user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['merchant_id'])) {
    header("Location: ../getStarted.php");
    exit();
}

$merchant_id = $_SESSION['merchant_id'];
$user_id = $_SESSION['user_id'];

// DEBUG: Force update merchant status to under_review to ensure it's set
$force_update = "UPDATE merchants SET status = 'under_review' WHERE merchant_id = '$merchant_id'";
mysqli_query($conn, $force_update);

// Fetch merchant details with status
$merchant_sql = "SELECT m.*, u.email, u.first_name, u.last_name, u.phone 
                 FROM merchants m 
                 JOIN users u ON m.user_id = u.id 
                 WHERE m.merchant_id = '$merchant_id'";
$merchant_result = mysqli_query($conn, $merchant_sql);

if (!$merchant_result) {
    // Query failed
    die("Database error: " . mysqli_error($conn));
}

$merchant = mysqli_fetch_assoc($merchant_result);

if (!$merchant) {
    // No merchant found - this should not happen if you just came from finalpage.php
    die("Error: Merchant account not found. ID: $merchant_id<br>
         <a href='setup.php'>Go back to setup</a> or 
         <a href='../includes/logout.php'>Log out</a>");
}

// Check merchant status - but DON'T redirect if it's still 'setup'
// Just show a warning instead
$merchant_status = $merchant['status'] ?? 'setup';

// If merchant is active, redirect to active dashboard
if ($merchant_status === 'active') {
    header("Location: merchant_dashboard.php");
    exit();
}

// If merchant is still in setup, show a message but DON'T redirect
// This allows users to access the under-review dashboard even if status wasn't updated
if ($merchant_status === 'setup') {
    // Just log it but continue
    error_log("Warning: Merchant status is still 'setup' for merchant_id: $merchant_id");
    // Update it now
    $update_sql = "UPDATE merchants SET status = 'under_review' WHERE merchant_id = '$merchant_id'";
    mysqli_query($conn, $update_sql);
    $merchant_status = 'under_review';
}

// Fetch review details with error handling
$review_sql = "SELECT * FROM merchant_reviews 
               WHERE merchant_id = '$merchant_id' 
               ORDER BY submitted_at DESC LIMIT 1";
$review_result = mysqli_query($conn, $review_sql);

if (!$review_result) {
    // Table might not exist, create it
    $create_table_sql = "CREATE TABLE IF NOT EXISTS `merchant_reviews` (
      `review_id` VARCHAR(50) PRIMARY KEY,
      `merchant_id` INT NOT NULL,
      `status` ENUM('pending', 'in_review', 'approved', 'rejected', 'needs_info') DEFAULT 'pending',
      `reviewer_id` INT DEFAULT NULL,
      `notes` TEXT DEFAULT NULL,
      `submitted_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      `reviewed_at` TIMESTAMP NULL DEFAULT NULL,
      `estimated_completion` DATE DEFAULT NULL,
      `rejection_reason` TEXT DEFAULT NULL,
      FOREIGN KEY (`merchant_id`) REFERENCES `merchants`(`merchant_id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    
    mysqli_query($conn, $create_table_sql);
    
    // Try query again
    $review_result = mysqli_query($conn, $review_sql);
}

$review = $review_result ? mysqli_fetch_assoc($review_result) : null;

// Format dates
if ($review) {
    $submitted_date = date('M j, Y', strtotime($review['submitted_at']));
    $estimated_date = date('M j, Y', strtotime($review['estimated_completion']));
    $days_remaining = max(0, ceil((strtotime($review['estimated_completion']) - time()) / 86400));
} else {
    $submitted_date = date('M j, Y');
    $estimated_date = date('M j, Y', strtotime('+3 days'));
    $days_remaining = 3;
}

// Define store stats
$store_stats = [
    'projected_orders' => rand(50, 150),
    'estimated_revenue' => '$' . number_format(rand(1000, 5000), 0)
];

// Define completed tasks
$completed_tasks = [
    [
        'icon' => 'bi-check-circle-fill',
        'title' => 'Business Information',
        'description' => 'Store name, address, and contact details submitted',
        'completed_date' => date('M j, Y', strtotime('-2 days'))
    ],
    [
        'icon' => 'bi-file-earmark-check-fill',
        'title' => 'Legal Agreement',
        'description' => 'Terms and conditions accepted',
        'completed_date' => date('M j, Y', strtotime('-2 days'))
    ],
    [
        'icon' => 'bi-shield-check',
        'title' => 'Security Setup',
        'description' => 'Account security configured',
        'completed_date' => date('M j, Y', strtotime('-2 days'))
    ],
    [
        'icon' => 'bi-menu-button-wide-fill',
        'title' => 'Menu Upload',
        'description' => 'Menu items and pricing uploaded',
        'completed_date' => date('M j, Y', strtotime('-1 day'))
    ],
    [
        'icon' => 'bi-credit-card-fill',
        'title' => 'Payment Setup',
        'description' => 'Banking information provided',
        'completed_date' => date('M j, Y', strtotime('-1 day'))
    ],
    [
        'icon' => 'bi-receipt',
        'title' => 'Tax Information',
        'description' => 'Tax details submitted',
        'completed_date' => date('M j, Y', strtotime('-1 day'))
    ]
];

// Define upcoming tasks (things they can do while waiting)
$upcoming_tasks = [
    [
        'icon' => 'bi-camera-fill',
        'title' => 'Upload Store Photos',
        'description' => 'Add high-quality photos of your store and food items to attract customers'
    ],
    [
        'icon' => 'bi-clock-history',
        'title' => 'Set Operating Hours',
        'description' => 'Configure your store hours and delivery availability'
    ],
    [
        'icon' => 'bi-megaphone-fill',
        'title' => 'Prepare Marketing',
        'description' => 'Get ready to promote your store once approved'
    ]
];

// Define ready tasks (things they can do right now)
$ready_tasks = [];

// Calculate counts
$completed_tasks_count = count($completed_tasks);
$upcoming_tasks_count = count($upcoming_tasks);
$ready_tasks_count = count($ready_tasks);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Merchant Dashboard - Account Under Review - BeU Delivery</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <style>
        /* General Layout */
        body {
            background-color: #f7f7f7;
            font-family: 'Arial', sans-serif;
            color: #333;
        }
        
        .dashboard-wrapper {
            display: flex;
            min-height: 100vh;
        }

        /* Top Bar */
        .top-bar {
            background-color: white;
            border-bottom: 1px solid #e0e0e0;
            padding: 12px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            width: 100%;
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        
        .store-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .store-status-badge {
            background-color: #ffc000;
            color: #333;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: bold;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        
        .top-bar-links {
            display: flex;
            gap: 20px;
            align-items: center;
        }
        
        .top-bar-link {
            color: #333;
            text-decoration: none;
            font-size: 0.85rem;
            transition: color 0.2s;
        }
        
        .top-bar-link:hover {
            color: #007bff;
        }

        /* Sidebar */
        .sidebar {
            width: 250px;
            background-color: white;
            padding: 20px 0;
            flex-shrink: 0;
            border-right: 1px solid #e0e0e0;
            height: calc(100vh - 60px);
            position: sticky;
            top: 60px;
            overflow-y: auto;
        }
        
        .brand-logo {
            font-size: 1.5rem;
            font-weight: bold;
            color: black;
            padding: 0 20px 20px;
            border-bottom: 1px solid #eee;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .store-dropdown {
            padding: 0 20px;
            margin-bottom: 20px;
        }
        
        .store-name {
            font-weight: bold;
            margin-bottom: 5px;
            color: #333;
        }
        
        .store-address {
            font-size: 0.8rem;
            color: #6c757d;
        }

        .nav-section-title {
            font-size: 0.75rem;
            color: #6c757d;
            font-weight: bold;
            text-transform: uppercase;
            padding: 15px 20px 5px;
            margin-top: 10px;
        }

        .nav-item {
            padding: 10px 20px;
            display: flex;
            align-items: center;
            color: #333;
            text-decoration: none;
            font-size: 0.9rem;
            transition: all 0.2s;
            border-left: 3px solid transparent;
            margin: 2px 0;
        }
        
        .nav-item:hover, .nav-item.active {
            background-color: #f8f9fa;
            color: black;
            border-left-color: black;
        }
        
        .nav-item i {
            margin-right: 15px;
            font-size: 1.1rem;
            width: 20px;
            text-align: center;
        }
        
        .nav-item.disabled {
            color: #adb5bd;
            cursor: not-allowed;
            pointer-events: none;
        }

        /* Main Content */
        .main-content {
            flex-grow: 1;
            padding: 30px;
            overflow-y: auto;
            background-color: #f7f7f7;
        }
        
        .page-header {
            margin-bottom: 30px;
        }
        
        .page-title {
            font-size: 1.8rem;
            font-weight: bold;
            margin-bottom: 10px;
        }
        
        .page-subtitle {
            color: #6c757d;
            font-size: 1rem;
        }

        /* Review Status Banner */
        .review-banner {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 12px;
            padding: 30px;
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.2);
        }
        
        .review-content h1 {
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 10px;
        }
        
        .review-content p {
            font-size: 1rem;
            opacity: 0.9;
            margin-bottom: 20px;
            max-width: 600px;
        }
        
        .review-stats {
            display: flex;
            gap: 30px;
            margin-top: 20px;
        }
        
        .stat-item {
            text-align: center;
        }
        
        .stat-value {
            font-size: 2rem;
            font-weight: bold;
            display: block;
        }
        
        .stat-label {
            font-size: 0.85rem;
            opacity: 0.8;
        }
        
        .review-visual {
            width: 200px;
            height: 200px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 5rem;
        }

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            border: 1px solid #e0e0e0;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        
        .stat-card-icon {
            width: 50px;
            height: 50px;
            border-radius: 10px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
            margin-bottom: 15px;
        }
        
        .stat-card-title {
            font-size: 0.9rem;
            color: #6c757d;
            margin-bottom: 5px;
        }
        
        .stat-card-value {
            font-size: 1.8rem;
            font-weight: bold;
            color: #333;
        }
        
        .stat-card-detail {
            font-size: 0.85rem;
            color: #6c757d;
            margin-top: 5px;
        }

        /* Task Sections */
        .task-section {
            background: white;
            border-radius: 10px;
            padding: 25px;
            margin-bottom: 25px;
            border: 1px solid #e0e0e0;
        }
        
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            cursor: pointer;
            padding: 10px 0;
        }
        
        .section-title {
            font-size: 1.25rem;
            font-weight: bold;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .task-count {
            font-size: 0.9rem;
            font-weight: normal;
            color: #6c757d;
            background: #f8f9fa;
            padding: 3px 10px;
            border-radius: 20px;
        }
        
        .section-toggle {
            color: #6c757d;
            transition: transform 0.2s;
        }
        
        .section-toggle.collapsed {
            transform: rotate(-90deg);
        }

        /* Task Items */
        .task-list {
            display: grid;
            gap: 15px;
        }
        
        .task-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
            border-left: 4px solid transparent;
            transition: all 0.2s;
        }
        
        .task-item:hover {
            background: #e9ecef;
            transform: translateX(5px);
        }
        
        .task-item.completed {
            border-left-color: #38c172;
            opacity: 0.8;
        }
        
        .task-item.pending {
            border-left-color: #ffc000;
        }
        
        .task-item.ready {
            border-left-color: #667eea;
        }
        
        .task-left {
            display: flex;
            align-items: flex-start;
            gap: 15px;
        }
        
        .task-icon {
            font-size: 1.25rem;
            color: #333;
            margin-top: 3px;
        }
        
        .task-item.completed .task-icon {
            color: #38c172;
        }
        
        .task-item.pending .task-icon {
            color: #ffc000;
        }
        
        .task-details h4 {
            font-weight: bold;
            margin-bottom: 5px;
            font-size: 1rem;
        }
        
        .task-item.completed .task-details h4 {
            text-decoration: line-through;
            color: #6c757d;
        }
        
        .task-details p {
            font-size: 0.85rem;
            color: #6c757d;
            margin-bottom: 0;
            line-height: 1.4;
        }
        
        .task-status {
            font-size: 0.85rem;
            font-weight: bold;
            padding: 5px 12px;
            border-radius: 20px;
            white-space: nowrap;
        }
        
        .task-status.completed {
            background-color: #d4edda;
            color: #155724;
        }
        
        .task-status.pending {
            background-color: #fff3cd;
            color: #856404;
        }
        
        .task-status.ready {
            background-color: #cce5ff;
            color: #004085;
        }
        
        .task-completion-date {
            font-size: 0.8rem;
            color: #6c757d;
            margin-top: 5px;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        /* Progress Section */
        .progress-section {
            background: white;
            border-radius: 10px;
            padding: 25px;
            margin-bottom: 25px;
            border: 1px solid #e0e0e0;
        }
        
        .progress-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .progress-bar-container {
            height: 8px;
            background: #e9ecef;
            border-radius: 4px;
            overflow: hidden;
            margin-bottom: 10px;
        }
        
        .progress-bar {
            height: 100%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 4px;
            transition: width 0.5s ease;
        }
        
        .progress-text {
            display: flex;
            justify-content: space-between;
            font-size: 0.9rem;
            color: #6c757d;
        }

        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 15px;
            margin-top: 30px;
            flex-wrap: wrap;
        }
        
        .btn-action {
            padding: 12px 25px;
            border-radius: 8px;
            font-weight: bold;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s;
        }
        
        .btn-primary-action {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
        }
        
        .btn-secondary-action {
            background: white;
            color: #333;
            border: 2px solid #e0e0e0;
        }
        
        .btn-action:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        /* Responsive Design */
        @media (max-width: 992px) {
            .dashboard-wrapper {
                flex-direction: column;
            }
            
            .sidebar {
                width: 100%;
                height: auto;
                position: static;
                border-right: none;
                border-bottom: 1px solid #e0e0e0;
            }
            
            .review-banner {
                flex-direction: column;
                text-align: center;
            }
            
            .review-visual {
                margin-top: 20px;
                width: 150px;
                height: 150px;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
        
        @media (max-width: 768px) {
            .top-bar {
                flex-direction: column;
                gap: 10px;
                padding: 15px;
            }
            
            .store-info, .top-bar-links {
                width: 100%;
                justify-content: center;
            }
            
            .main-content {
                padding: 20px;
            }
            
            .review-content h1 {
                font-size: 1.5rem;
            }
            
            .review-stats {
                flex-direction: column;
                gap: 15px;
            }
            
            .task-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
        }
    </style>
</head>
<body>
    <!-- Top Bar -->
    <div class="top-bar">
        <div class="store-info">
            <div>
                <h4 class="mb-1"><?php echo htmlspecialchars($merchant['store_name']); ?></h4>
                <span class="store-status-badge">
                    <i class="bi bi-clock"></i> Under Review
                </span>
            </div>
        </div>
        
        <div class="top-bar-links">
            <a href="mailto:support@beudelivery.com" class="top-bar-link">
                <i class="bi bi-envelope"></i> Support
            </a>
            <a href="../includes/logout.php" class="top-bar-link">
                <i class="bi bi-box-arrow-right"></i> Log Out
            </a>
        </div>
    </div>
    
    <!-- Dashboard Layout -->
    <div class="dashboard-wrapper">
        <!-- Sidebar Navigation -->
        <div class="sidebar">
            <div class="brand-logo">
                <i class="bi bi-truck"></i>
                BeU Delivery
            </div>
            
            <div class="store-dropdown">
                <div class="store-name"><?php echo htmlspecialchars($merchant['store_name']); ?></div>
                <div class="store-address"><?php echo htmlspecialchars($merchant['store_address']); ?></div>
            </div>

            <div class="nav-section-title">Navigation</div>
            <a href="accountunderreview.php" class="nav-item active">
                <i class="bi bi-house-door-fill"></i> Home
            </a>
            
            <div class="nav-section-title">Store Setup</div>
            <a href="preview_menu.php" class="nav-item">
                <i class="bi bi-shop"></i> Preview Menu
            </a>
            <a href="upload_photos.php" class="nav-item">
                <i class="bi bi-image-fill"></i> Upload Photos
            </a>
            <a href="setup_hours.php" class="nav-item">
                <i class="bi bi-clock-history"></i> Set Hours
            </a>
            
            <div class="nav-section-title">Coming Soon</div>
            <a href="#" class="nav-item disabled">
                <i class="bi bi-bar-chart-fill"></i> Performance
            </a>
            <a href="#" class="nav-item disabled">
                <i class="bi bi-people-fill"></i> Customers
            </a>
            <a href="#" class="nav-item disabled">
                <i class="bi bi-file-earmark-bar-graph-fill"></i> Reports
            </a>
            
            <div class="nav-section-title">Settings</div>
            <a href="edit_profile.php" class="nav-item">
                <i class="bi bi-person-circle"></i> Profile
            </a>
            <a href="store_settings.php" class="nav-item">
                <i class="bi bi-gear-fill"></i> Settings
            </a>
        </div>
        
        <!-- Main Content -->
        <div class="main-content">
            <!-- Page Header -->
            <div class="page-header">
                <h1 class="page-title">Welcome back, <?php echo htmlspecialchars($merchant['first_name']); ?>!</h1>
                <p class="page-subtitle">Your store is currently under review. Here's what you can do while you wait.</p>
            </div>

            <!-- Review Status Banner -->
            <div class="review-banner">
                <div class="review-content">
                    <h1>Your store is under review</h1>
                    <p>We're currently reviewing your application. Once approved, you'll be able to start receiving orders immediately. Estimated completion: <?php echo $estimated_date; ?></p>
                    
                    <div class="review-stats">
                        <div class="stat-item">
                            <span class="stat-value"><?php echo $days_remaining; ?></span>
                            <span class="stat-label">Days Remaining</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-value"><?php echo $completed_tasks_count; ?>/<?php echo $completed_tasks_count + $upcoming_tasks_count; ?></span>
                            <span class="stat-label">Tasks Complete</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-value">24-48h</span>
                            <span class="stat-label">Response Time</span>
                        </div>
                    </div>
                </div>
                <div class="review-visual">
                    <i class="bi bi-clipboard-check"></i>
                </div>
            </div>

            <!-- Stats Grid -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-card-icon">
                        <i class="bi bi-people"></i>
                    </div>
                    <div class="stat-card-title">Potential Customers</div>
                    <div class="stat-card-value"><?php echo $store_stats['projected_orders']; ?></div>
                    <div class="stat-card-detail">Projected first month orders</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-card-icon">
                        <i class="bi bi-calendar-check"></i>
                    </div>
                    <div class="stat-card-title">Estimated Launch</div>
                    <div class="stat-card-value"><?php echo date('M j', strtotime($estimated_date)); ?></div>
                    <div class="stat-card-detail">Target approval date</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-card-icon">
                        <i class="bi bi-check-circle"></i>
                    </div>
                    <div class="stat-card-title">Setup Progress</div>
                    <div class="stat-card-value"><?php echo round(($completed_tasks_count / ($completed_tasks_count + $upcoming_tasks_count)) * 100); ?>%</div>
                    <div class="stat-card-detail">Pre-launch tasks</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-card-icon">
                        <i class="bi bi-chat-dots"></i>
                    </div>
                    <div class="stat-card-title">Support</div>
                    <div class="stat-card-value">24/7</div>
                    <div class="stat-card-detail">Available for questions</div>
                </div>
            </div>

            <!-- Progress Section -->
            <div class="progress-section">
                <div class="progress-header">
                    <h3>Application Progress</h3>
                    <span><?php echo $completed_tasks_count; ?> of <?php echo $completed_tasks_count + $upcoming_tasks_count; ?> tasks complete</span>
                </div>
                <div class="progress-bar-container">
                    <div class="progress-bar" style="width: <?php echo round(($completed_tasks_count / ($completed_tasks_count + $upcoming_tasks_count)) * 100); ?>%"></div>
                </div>
                <div class="progress-text">
                    <span>Submitted: <?php echo $submitted_date; ?></span>
                    <span>Est. Completion: <?php echo $estimated_date; ?></span>
                </div>
            </div>

            <!-- Ready Tasks Section -->
            <div class="task-section">
                <div class="section-header" id="readyTasksHeader">
                    <div class="section-title">
                        <i class="bi bi-lightning-fill" style="color: #667eea;"></i>
                        Ready for you
                        <span class="task-count"><?php echo $ready_tasks_count; ?> tasks</span>
                    </div>
                    <i class="bi bi-chevron-up section-toggle" id="readyTasksToggle"></i>
                </div>
                <div class="task-list" id="readyTasksList">
                    <?php if ($ready_tasks_count === 0): ?>
                    <div class="text-center py-4">
                        <i class="bi bi-check-circle-fill" style="font-size: 3rem; color: #38c172;"></i>
                        <h4 class="mt-3">All set for now!</h4>
                        <p class="text-muted">Check back later for new tasks or focus on upcoming tasks below.</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Upcoming Tasks Section -->
            <div class="task-section">
                <div class="section-header" id="upcomingTasksHeader">
                    <div class="section-title">
                        <i class="bi bi-calendar-week" style="color: #ffc000;"></i>
                        Coming up
                        <span class="task-count"><?php echo $upcoming_tasks_count; ?> tasks</span>
                    </div>
                    <i class="bi bi-chevron-up section-toggle" id="upcomingTasksToggle"></i>
                </div>
                <div class="task-list" id="upcomingTasksList">
                    <?php foreach ($upcoming_tasks as $task): ?>
                    <div class="task-item pending">
                        <div class="task-left">
                            <i class="task-icon bi <?php echo $task['icon']; ?>"></i>
                            <div class="task-details">
                                <h4><?php echo $task['title']; ?></h4>
                                <p><?php echo $task['description']; ?></p>
                            </div>
                        </div>
                        <span class="task-status pending">Pending</span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Completed Tasks Section -->
            <div class="task-section">
                <div class="section-header" id="completedTasksHeader">
                    <div class="section-title">
                        <i class="bi bi-check-circle-fill" style="color: #38c172;"></i>
                        Completed
                        <span class="task-count"><?php echo $completed_tasks_count; ?> tasks</span>
                    </div>
                    <i class="bi bi-chevron-up section-toggle" id="completedTasksToggle"></i>
                </div>
                <div class="task-list" id="completedTasksList">
                    <?php foreach ($completed_tasks as $task): ?>
                    <div class="task-item completed">
                        <div class="task-left">
                            <i class="task-icon bi <?php echo $task['icon']; ?>"></i>
                            <div class="task-details">
                                <h4><?php echo $task['title']; ?></h4>
                                <p><?php echo $task['description']; ?></p>
                                <?php if (isset($task['completed_date'])): ?>
                                <div class="task-completion-date">
                                    <i class="bi bi-calendar"></i> Completed: <?php echo $task['completed_date']; ?>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <span class="task-status completed">Completed</span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="action-buttons">
                <a href="preview_menu.php" class="btn-action btn-primary-action">
                    <i class="bi bi-eye-fill"></i> Preview Your Store
                </a>
                <a href="upload_photos.php" class="btn-action btn-secondary-action">
                    <i class="bi bi-cloud-upload"></i> Upload Photos
                </a>
                <a href="setup_hours.php" class="btn-action btn-secondary-action">
                    <i class="bi bi-clock"></i> Set Store Hours
                </a>
                <a href="mailto:support@beudelivery.com" class="btn-action btn-secondary-action">
                    <i class="bi bi-question-circle"></i> Contact Support
                </a>
            </div>
            
            <!-- Helpful Resources -->
            <div class="mt-5 pt-4 border-top">
                <h5 class="mb-3">Helpful Resources</h5>
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <div class="card h-100">
                            <div class="card-body">
                                <h6><i class="bi bi-play-circle text-primary me-2"></i>Getting Started Guide</h6>
                                <p class="small text-muted">Learn how to maximize your store's potential</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="card h-100">
                            <div class="card-body">
                                <h6><i class="bi bi-camera-video text-primary me-2"></i>Photo Best Practices</h6>
                                <p class="small text-muted">Tips for taking great food photos</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="card h-100">
                            <div class="card-body">
                                <h6><i class="bi bi-chat-left-text text-primary me-2"></i>FAQ & Support</h6>
                                <p class="small text-muted">Common questions answered</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    
    <script>
        // Toggle task sections
        document.querySelectorAll('.section-header').forEach(header => {
            header.addEventListener('click', function() {
                const targetId = this.id.replace('Header', 'List');
                const target = document.getElementById(targetId);
                const toggle = this.querySelector('.section-toggle');
                
                if (target.style.display === 'none') {
                    target.style.display = 'grid';
                    toggle.classList.remove('collapsed');
                    toggle.classList.add('bi-chevron-up');
                    toggle.classList.remove('bi-chevron-down');
                } else {
                    target.style.display = 'none';
                    toggle.classList.add('collapsed');
                    toggle.classList.remove('bi-chevron-up');
                    toggle.classList.add('bi-chevron-down');
                }
            });
        });

        // Initialize all sections as expanded
        document.querySelectorAll('.task-list').forEach(list => {
            list.style.display = 'grid';
        });

        // Update review timer
        function updateTimer() {
            const daysElement = document.querySelector('.stat-value');
            if (daysElement && daysElement.textContent.includes('days')) {
                // This would be updated via AJAX in a real application
                console.log('Timer would update here via AJAX');
            }
        }
        
        // Check for status updates every 5 minutes
        setInterval(function() {
            fetch('../includes/check_review_status.php')
                .then(response => response.json())
                .then(data => {
                    if (data.status !== 'pending') {
                        // Redirect if status changed
                        window.location.href = 'application_update.php?status=' + data.status;
                    }
                })
                .catch(error => console.error('Error checking status:', error));
        }, 300000); // 5 minutes

        // Show welcome message
        setTimeout(function() {
            if (!localStorage.getItem('welcome_shown')) {
                alert('Welcome to your merchant dashboard! Here you can prepare your store for launch while we review your application.');
                localStorage.setItem('welcome_shown', 'true');
            }
        }, 1000);
    </script>
</body>
</html>