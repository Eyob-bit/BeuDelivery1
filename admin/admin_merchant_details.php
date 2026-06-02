<?php
// admin_merchant_details.php - MERCHANT DETAIL VIEW
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Session already started in admin_auth.php
require_once "admin_auth.php";
include "../includes/db.php";

if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("Error: No merchant ID provided. <a href='admin_merchants.php'>Go back</a>");
}

$merchant_id = mysqli_real_escape_string($conn, $_GET['id']);
$admin_id = $_SESSION['user_id'];

// Get merchant data - SIMPLIFIED without merchant_reviews
$merchant_sql = "SELECT 
    m.*,
    u.first_name,
    u.last_name,
    u.email,
    u.phone,
    u.created_at as user_created,
    md.store_phone,
    md.cuisine_types,
    md.launch_date,
    md.store_hours,
    mp.plan_type,
    mp.delivery_fee_percentage,
    mp.pickup_fee_percentage
    FROM merchants m
    JOIN users u ON m.user_id = u.id
    LEFT JOIN merchant_details md ON m.merchant_id = md.merchant_id
    LEFT JOIN merchant_plans mp ON m.merchant_id = mp.merchant_id
    WHERE m.merchant_id = '$merchant_id'";

$merchant_result = mysqli_query($conn, $merchant_sql);

// Check for query errors
if (!$merchant_result) {
    die("Query Error: " . mysqli_error($conn) . "<br>Query: " . $merchant_sql);
}

if (mysqli_num_rows($merchant_result) == 0) {
    die("Error: Merchant ID $merchant_id not found. <a href='admin_merchants.php'>Go back</a>");
}

$merchant = mysqli_fetch_assoc($merchant_result);

// Get review info separately if it exists
$review_sql = "SELECT * FROM merchant_reviews WHERE merchant_id = '$merchant_id'";
$review_result = mysqli_query($conn, $review_sql);
if ($review_result && mysqli_num_rows($review_result) > 0) {
    $review = mysqli_fetch_assoc($review_result);
    $merchant['review_status'] = $review['status'] ?? null;
    $merchant['reviewed_at'] = $review['reviewed_at'] ?? null;
    $merchant['reviewed_by'] = $review['reviewed_by'] ?? null;
} else {
    $merchant['review_status'] = null;
    $merchant['reviewed_at'] = null;
    $merchant['reviewed_by'] = null;
}

// Get banking info separately to avoid column name conflicts
$banking_sql = "SELECT * FROM merchant_banking WHERE merchant_id = '$merchant_id'";
$banking_result = mysqli_query($conn, $banking_sql);
if (!$banking_result) {
    die("Banking query error: " . mysqli_error($conn));
}
$banking = $banking_result ? mysqli_fetch_assoc($banking_result) : null;

// Get tax info separately
$tax_sql = "SELECT * FROM merchant_tax_info WHERE merchant_id = '$merchant_id'";
$tax_result = mysqli_query($conn, $tax_sql);
if (!$tax_result) {
    die("Tax query error: " . mysqli_error($conn));
}
$tax_info = $tax_result ? mysqli_fetch_assoc($tax_result) : null;

// Get store hours
$hours_sql = "SELECT * FROM store_hours WHERE merchant_id = '$merchant_id' ORDER BY day_of_week";
$hours_result = mysqli_query($conn, $hours_sql);
$store_hours = [];
if ($hours_result) {
    while ($hour = mysqli_fetch_assoc($hours_result)) {
        $store_hours[] = $hour;
    }
}

// Get documents
$docs_sql = "SELECT * FROM merchant_documents WHERE merchant_id = '$merchant_id'";
$docs_result = mysqli_query($conn, $docs_sql);
$documents = [];
if ($docs_result) {
    while ($doc = mysqli_fetch_assoc($docs_result)) {
        $documents[] = $doc;
    }
}

// Get restaurants (if table exists)
$restaurants_sql = "SELECT * FROM restaurants WHERE owner_id = '{$merchant['user_id']}'";
$restaurants_result = mysqli_query($conn, $restaurants_sql);
$restaurants = [];
if ($restaurants_result) {
    while ($rest = mysqli_fetch_assoc($restaurants_result)) {
        $restaurants[] = $rest;
    }
}

// Handle approval/rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['approve'])) {
        $comments = mysqli_real_escape_string($conn, $_POST['admin_comments'] ?? 'Approved');
        
        mysqli_begin_transaction($conn);
        try {
            // Update merchant status
            $update_status = mysqli_query($conn, "UPDATE merchants SET status = 'active' WHERE merchant_id = '$merchant_id'");
            if (!$update_status) {
                throw new Exception("Failed to update merchant status: " . mysqli_error($conn));
            }
            
            // Update or create review - use review_id instead of id
            $review_check = mysqli_query($conn, "SELECT review_id FROM merchant_reviews WHERE merchant_id = '$merchant_id'");
            if (!$review_check) {
                throw new Exception("Failed to check reviews: " . mysqli_error($conn));
            }
            
            if (mysqli_num_rows($review_check) > 0) {
                $update_review = mysqli_query($conn, "UPDATE merchant_reviews SET 
                    status = 'approved',
                    admin_comments = '$comments',
                    reviewed_at = NOW(),
                    reviewed_by = '$admin_id'
                    WHERE merchant_id = '$merchant_id'");
                if (!$update_review) {
                    throw new Exception("Failed to update review: " . mysqli_error($conn));
                }
            } else {
                $insert_review = mysqli_query($conn, "INSERT INTO merchant_reviews 
                    (merchant_id, user_id, order_id, rating, review_text, status, admin_comments, reviewed_at, reviewed_by) 
                    VALUES ('$merchant_id', '$admin_id', NULL, 5, 'Admin approved', 'approved', '$comments', NOW(), '$admin_id')");
                if (!$insert_review) {
                    throw new Exception("Failed to insert review: " . mysqli_error($conn));
                }
            }
            
            mysqli_commit($conn);
            header("Location: admin_merchant_details.php?id=$merchant_id&success=approved");
            exit();
        } catch (Exception $e) {
            mysqli_rollback($conn);
            $error = "Error: " . $e->getMessage();
        }
    } elseif (isset($_POST['reject'])) {
        $reason = mysqli_real_escape_string($conn, $_POST['rejection_reason'] ?? 'Rejected');
        
        mysqli_begin_transaction($conn);
        try {
            // Update merchant status
            $update_status = mysqli_query($conn, "UPDATE merchants SET status = 'inactive' WHERE merchant_id = '$merchant_id'");
            if (!$update_status) {
                throw new Exception("Failed to update merchant status: " . mysqli_error($conn));
            }
            
            // Update or create review - use review_id instead of id
            $review_check = mysqli_query($conn, "SELECT review_id FROM merchant_reviews WHERE merchant_id = '$merchant_id'");
            if (!$review_check) {
                throw new Exception("Failed to check reviews: " . mysqli_error($conn));
            }
            
            if (mysqli_num_rows($review_check) > 0) {
                $update_review = mysqli_query($conn, "UPDATE merchant_reviews SET 
                    status = 'rejected',
                    admin_comments = '$reason',
                    reviewed_at = NOW(),
                    reviewed_by = '$admin_id'
                    WHERE merchant_id = '$merchant_id'");
                if (!$update_review) {
                    throw new Exception("Failed to update review: " . mysqli_error($conn));
                }
            } else {
                $insert_review = mysqli_query($conn, "INSERT INTO merchant_reviews 
                    (merchant_id, user_id, order_id, rating, review_text, status, admin_comments, reviewed_at, reviewed_by) 
                    VALUES ('$merchant_id', '$admin_id', NULL, 1, 'Admin rejected', 'rejected', '$reason', NOW(), '$admin_id')");
                if (!$insert_review) {
                    throw new Exception("Failed to insert review: " . mysqli_error($conn));
                }
            }
            
            mysqli_commit($conn);
            header("Location: admin_merchant_details.php?id=$merchant_id&success=rejected");
            exit();
        } catch (Exception $e) {
            mysqli_rollback($conn);
            $error = "Error: " . $e->getMessage();
        }
    }
}

// Get reviewer name
$reviewer_name = 'Not reviewed yet';
if (!empty($merchant['reviewed_by'])) {
    $reviewer_sql = "SELECT first_name, last_name FROM users WHERE id = '{$merchant['reviewed_by']}'";
    $reviewer_result = mysqli_query($conn, $reviewer_sql);
    if ($reviewer_result && mysqli_num_rows($reviewer_result) > 0) {
        $reviewer = mysqli_fetch_assoc($reviewer_result);
        $reviewer_name = $reviewer['first_name'] . ' ' . $reviewer['last_name'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Merchant Details - Admin Panel</title>
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
        
        .header-card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        }
        
        .detail-card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        }
        
        .section-title {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 20px;
            color: #333;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .info-row {
            display: flex;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #f5f5f5;
        }
        
        .info-label {
            font-weight: 600;
            color: #666;
            min-width: 200px;
        }
        
        .info-value {
            color: #333;
            flex: 1;
        }
        
        .status-badge {
            padding: 8px 20px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
        }
        
        .badge-review { background: #fff3cd; color: #856404; }
        .badge-active { background: #d4edda; color: #155724; }
        .badge-inactive { background: #f8d7da; color: #721c24; }
        .badge-setup { background: #d1ecf1; color: #0c5460; }
        
        .action-buttons {
            display: flex;
            gap: 10px;
            margin-top: 30px;
        }
        
        .document-item {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 10px;
        }
        
        .hours-item {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #f0f0f0;
        }
        
        /* Sidebar styles from admin_sidebar.php */
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
</head>
<body>
    <!-- Sidebar -->
    <?php include "admin_sidebar.php"; ?>
    
    <!-- Main Content -->
    <div class="main-content">
        <!-- Header -->
        <div class="header-card">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="mb-1">Merchant Details</h2>
                    <p class="text-muted mb-0">Merchant ID: #<?php echo $merchant_id; ?></p>
                </div>
                <div class="d-flex gap-2">
                    <a href="admin_merchants.php" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left"></i> Back
                    </a>
                    <span class="status-badge badge-<?php 
                        if ($merchant['status'] == 'under_review') echo 'review';
                        elseif ($merchant['status'] == 'active') echo 'active';
                        elseif ($merchant['status'] == 'inactive') echo 'inactive';
                        else echo 'setup';
                    ?>">
                        <?php echo strtoupper(str_replace('_', ' ', $merchant['status'])); ?>
                    </span>
                </div>
            </div>
            
            <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-<?php echo $_GET['success'] == 'approved' ? 'success' : 'danger'; ?> alert-dismissible fade show mt-3">
                <?php if ($_GET['success'] == 'approved'): ?>
                <i class="bi bi-check-circle"></i> Merchant approved successfully!
                <?php else: ?>
                <i class="bi bi-x-circle"></i> Merchant application rejected.
                <?php endif; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>
            
            <?php if (isset($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show mt-3">
                <?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>
        </div>
        
        <div class="row">
            <!-- Left Column -->
            <div class="col-md-8">
                <!-- Store Information -->
                <div class="detail-card">
                    <h4 class="section-title">
                        <i class="bi bi-shop me-2"></i>Store Information
                    </h4>
                    
                    <div class="info-row">
                        <div class="info-label">Store Name:</div>
                        <div class="info-value">
                            <strong><?php echo htmlspecialchars($merchant['store_name']); ?></strong>
                            <?php if (!empty($merchant['brand_name'])): ?>
                            <br><small class="text-muted">Brand: <?php echo htmlspecialchars($merchant['brand_name']); ?></small>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="info-row">
                        <div class="info-label">Business Type:</div>
                        <div class="info-value">
                            <?php echo htmlspecialchars($merchant['business_type'] ?? 'N/A'); ?>
                        </div>
                    </div>
                    
                    <div class="info-row">
                        <div class="info-label">Store Address:</div>
                        <div class="info-value">
                            <?php echo nl2br(htmlspecialchars($merchant['store_address'])); ?>
                            <?php if (!empty($merchant['floor_suite'])): ?>
                            <br><small class="text-muted">Floor/Suite: <?php echo htmlspecialchars($merchant['floor_suite']); ?></small>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="info-row">
                        <div class="info-label">Store Phone:</div>
                        <div class="info-value">
                            <?php echo htmlspecialchars($merchant['store_phone'] ?? $merchant['mobile_phone'] ?? 'N/A'); ?>
                        </div>
                    </div>
                    
                    <?php if (!empty($merchant['social_media_website'])): ?>
                    <div class="info-row">
                        <div class="info-label">Website/Social:</div>
                        <div class="info-value">
                            <a href="<?php echo htmlspecialchars($merchant['social_media_website']); ?>" target="_blank">
                                <?php echo htmlspecialchars($merchant['social_media_website']); ?>
                            </a>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($merchant['cuisine_types'])): ?>
                    <div class="info-row">
                        <div class="info-label">Cuisine Types:</div>
                        <div class="info-value">
                            <?php 
                            $cuisines = json_decode($merchant['cuisine_types'], true);
                            if (is_array($cuisines)) {
                                echo implode(', ', array_map('htmlspecialchars', $cuisines));
                            } else {
                                echo htmlspecialchars($merchant['cuisine_types']);
                            }
                            ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($merchant['launch_date'])): ?>
                    <div class="info-row">
                        <div class="info-label">Launch Date:</div>
                        <div class="info-value">
                            <?php echo date('F j, Y', strtotime($merchant['launch_date'])); ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- Store Photos/Images -->
                <div class="detail-card">
                    <h4 class="section-title">
                        <i class="bi bi-images me-2"></i>Store Photos & Images
                    </h4>
                    
                    <div class="row">
                        <?php if (!empty($merchant['logo'])): ?>
                        <div class="col-md-4 mb-3">
                            <div class="text-center">
                                <p class="fw-bold mb-2">Logo</p>
                                <img src="../<?php echo htmlspecialchars($merchant['logo']); ?>" 
                                     class="img-fluid rounded border" 
                                     style="max-height: 200px; object-fit: cover;"
                                     alt="Store Logo">
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($merchant['featured_image'])): ?>
                        <div class="col-md-4 mb-3">
                            <div class="text-center">
                                <p class="fw-bold mb-2">Featured Image</p>
                                <img src="../<?php echo htmlspecialchars($merchant['featured_image']); ?>" 
                                     class="img-fluid rounded border" 
                                     style="max-height: 200px; object-fit: cover;"
                                     alt="Featured Image">
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($merchant['cover_image'])): ?>
                        <div class="col-md-4 mb-3">
                            <div class="text-center">
                                <p class="fw-bold mb-2">Cover Image</p>
                                <img src="../<?php echo htmlspecialchars($merchant['cover_image']); ?>" 
                                     class="img-fluid rounded border" 
                                     style="max-height: 200px; object-fit: cover;"
                                     alt="Cover Image">
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (empty($merchant['logo']) && empty($merchant['featured_image']) && empty($merchant['cover_image'])): ?>
                        <div class="col-12">
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle"></i> No store images uploaded yet.
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Menu Photos -->
                    <?php
                    $menu_photos_sql = "SELECT * FROM merchant_documents WHERE merchant_id = '$merchant_id' AND document_type IN ('menu_photo', 'menu_pdf') ORDER BY uploaded_at DESC";
                    $menu_photos_result = mysqli_query($conn, $menu_photos_sql);
                    $menu_photos = [];
                    while ($photo = mysqli_fetch_assoc($menu_photos_result)) {
                        $menu_photos[] = $photo;
                    }
                    ?>
                    
                    <?php if (!empty($menu_photos)): ?>
                    <hr class="my-4">
                    <h5 class="mb-3"><i class="bi bi-menu-button-wide me-2"></i>Menu Photos</h5>
                    <div class="row">
                        <?php foreach ($menu_photos as $photo): 
                            // Fix path - document_path is stored as "uploads/menusmenu_X_Y.png"
                            // but actual location is "merchant/uploads/menusmenu_X_Y.png"
                            $photo_path = $photo['document_path'];
                            if (strpos($photo_path, 'uploads/') === 0) {
                                $photo_path = '../merchant/' . $photo_path;
                            } else {
                                $photo_path = '../' . $photo_path;
                            }
                        ?>
                        <div class="col-md-3 mb-3">
                            <div class="text-center">
                                <?php if ($photo['document_type'] == 'menu_photo'): ?>
                                <a href="<?php echo htmlspecialchars($photo_path); ?>" target="_blank">
                                    <img src="<?php echo htmlspecialchars($photo_path); ?>" 
                                         class="img-fluid rounded border" 
                                         style="max-height: 150px; object-fit: cover; cursor: pointer;"
                                         alt="Menu Photo"
                                         onerror="this.parentElement.innerHTML='<div class=\'alert alert-warning\'>Image not found</div>'">
                                </a>
                                <?php else: ?>
                                <a href="<?php echo htmlspecialchars($photo_path); ?>" target="_blank" class="btn btn-outline-primary">
                                    <i class="bi bi-file-pdf"></i> View PDF
                                </a>
                                <?php endif; ?>
                                <p class="small text-muted mt-1">
                                    <?php echo date('M j, Y', strtotime($photo['uploaded_at'])); ?>
                                </p>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- Owner Information -->
                <div class="detail-card">
                    <h4 class="section-title">
                        <i class="bi bi-person me-2"></i>Owner Information
                    </h4>
                    
                    <div class="info-row">
                        <div class="info-label">Full Name:</div>
                        <div class="info-value">
                            <?php echo htmlspecialchars($merchant['first_name'] . ' ' . $merchant['last_name']); ?>
                        </div>
                    </div>
                    
                    <div class="info-row">
                        <div class="info-label">Email Address:</div>
                        <div class="info-value">
                            <?php echo htmlspecialchars($merchant['email']); ?>
                        </div>
                    </div>
                    
                    <div class="info-row">
                        <div class="info-label">Phone Number:</div>
                        <div class="info-value">
                            <?php echo htmlspecialchars($merchant['phone']); ?>
                        </div>
                    </div>
                    
                    <div class="info-row">
                        <div class="info-label">Account Created:</div>
                        <div class="info-value">
                            <?php echo date('F j, Y, h:i A', strtotime($merchant['user_created'])); ?>
                        </div>
                    </div>
                </div>
                
                <!-- Banking Information -->
                <div class="detail-card">
                    <h4 class="section-title">
                        <i class="bi bi-bank me-2"></i>Banking Information
                    </h4>
                    
                    <?php if (!empty($banking)): ?>
                    <div class="info-row">
                        <div class="info-label">Account Holder Name:</div>
                        <div class="info-value">
                            <strong><?php echo htmlspecialchars($banking['account_holder_name']); ?></strong>
                            <?php if ($banking['is_verified'] || $banking['verified']): ?>
                            <span class="badge bg-success ms-2"><i class="bi bi-check-circle"></i> Verified</span>
                            <?php else: ?>
                            <span class="badge bg-warning ms-2"><i class="bi bi-exclamation-circle"></i> Not Verified</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="info-row">
                        <div class="info-label">Bank Name:</div>
                        <div class="info-value">
                            <?php echo htmlspecialchars($banking['bank_name']); ?>
                        </div>
                    </div>
                    
                    <div class="info-row">
                        <div class="info-label">Account Number:</div>
                        <div class="info-value">
                            <code>****<?php echo substr($banking['account_number'], -4); ?></code>
                            <button class="btn btn-sm btn-outline-secondary ms-2" onclick="showFullAccount()">
                                <i class="bi bi-eye"></i> Show Full
                            </button>
                            <span id="fullAccount" style="display: none;">
                                <code><?php echo htmlspecialchars($banking['account_number']); ?></code>
                            </span>
                        </div>
                    </div>
                    
                    <div class="info-row">
                        <div class="info-label">Routing Number:</div>
                        <div class="info-value">
                            <code><?php echo htmlspecialchars($banking['routing_number'] ?? 'N/A'); ?></code>
                        </div>
                    </div>
                    
                    <div class="info-row">
                        <div class="info-label">Account Type:</div>
                        <div class="info-value">
                            <span class="badge bg-info"><?php echo strtoupper($banking['account_type'] ?? 'N/A'); ?></span>
                        </div>
                    </div>
                    
                    <?php if (!empty($banking['business_legal_entity_name'])): ?>
                    <div class="info-row">
                        <div class="info-label">Business Legal Name:</div>
                        <div class="info-value">
                            <?php echo htmlspecialchars($banking['business_legal_entity_name']); ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($banking['company_mailing_address'])): ?>
                    <div class="info-row">
                        <div class="info-label">Mailing Address:</div>
                        <div class="info-value">
                            <?php echo nl2br(htmlspecialchars($banking['company_mailing_address'])); ?>
                            <?php if (!empty($banking['city']) || !empty($banking['state']) || !empty($banking['postal_code'])): ?>
                            <br>
                            <?php echo htmlspecialchars($banking['city'] ?? ''); ?>, 
                            <?php echo htmlspecialchars($banking['state'] ?? ''); ?> 
                            <?php echo htmlspecialchars($banking['postal_code'] ?? ''); ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($banking['verified_at'])): ?>
                    <div class="info-row">
                        <div class="info-label">Verified At:</div>
                        <div class="info-value">
                            <?php echo date('F j, Y, h:i A', strtotime($banking['verified_at'])); ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    <?php else: ?>
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle"></i> No banking information provided yet.
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- Tax Information -->
                <div class="detail-card">
                    <h4 class="section-title">
                        <i class="bi bi-receipt me-2"></i>Tax Information
                    </h4>
                    
                    <?php if (!empty($tax_info)): ?>
                    <div class="info-row">
                        <div class="info-label">Tax Classification:</div>
                        <div class="info-value">
                            <strong><?php echo htmlspecialchars($tax_info['tax_classification'] ?? 'N/A'); ?></strong>
                            <?php if ($tax_info['is_verified'] || $tax_info['verified']): ?>
                            <span class="badge bg-success ms-2"><i class="bi bi-check-circle"></i> Verified</span>
                            <?php else: ?>
                            <span class="badge bg-warning ms-2"><i class="bi bi-exclamation-circle"></i> Not Verified</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="info-row">
                        <div class="info-label">Business Type:</div>
                        <div class="info-value">
                            <span class="badge bg-info"><?php echo strtoupper(str_replace('_', ' ', $tax_info['business_type'] ?? 'N/A')); ?></span>
                        </div>
                    </div>
                    
                    <div class="info-row">
                        <div class="info-label">Full Name (Tax):</div>
                        <div class="info-value">
                            <?php echo htmlspecialchars($tax_info['full_name'] ?? 'N/A'); ?>
                        </div>
                    </div>
                    
                    <?php if (!empty($tax_info['business_name'])): ?>
                    <div class="info-row">
                        <div class="info-label">Business Name:</div>
                        <div class="info-value">
                            <?php echo htmlspecialchars($tax_info['business_name']); ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($tax_info['ssn'])): ?>
                    <div class="info-row">
                        <div class="info-label">SSN:</div>
                        <div class="info-value">
                            <code>***-**-<?php echo htmlspecialchars($tax_info['ssn_last_four'] ?? '****'); ?></code>
                            <button class="btn btn-sm btn-outline-secondary ms-2" onclick="showFullSSN()">
                                <i class="bi bi-eye"></i> Show Full
                            </button>
                            <span id="fullSSN" style="display: none;">
                                <code><?php echo htmlspecialchars($tax_info['ssn']); ?></code>
                            </span>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($tax_info['ein'])): ?>
                    <div class="info-row">
                        <div class="info-label">EIN:</div>
                        <div class="info-value">
                            <code>**-***<?php echo htmlspecialchars($tax_info['ein_last_four'] ?? '****'); ?></code>
                            <button class="btn btn-sm btn-outline-secondary ms-2" onclick="showFullEIN()">
                                <i class="bi bi-eye"></i> Show Full
                            </button>
                            <span id="fullEIN" style="display: none;">
                                <code><?php echo htmlspecialchars($tax_info['ein']); ?></code>
                            </span>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($tax_info['tax_identification_number'])): ?>
                    <div class="info-row">
                        <div class="info-label">Tax ID Number:</div>
                        <div class="info-value">
                            <code><?php echo htmlspecialchars($tax_info['tax_identification_number']); ?></code>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($tax_info['vat_number'])): ?>
                    <div class="info-row">
                        <div class="info-label">VAT Number:</div>
                        <div class="info-value">
                            <code><?php echo htmlspecialchars($tax_info['vat_number']); ?></code>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($tax_info['registration_number'])): ?>
                    <div class="info-row">
                        <div class="info-label">Registration Number:</div>
                        <div class="info-value">
                            <code><?php echo htmlspecialchars($tax_info['registration_number']); ?></code>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($tax_info['address'])): ?>
                    <div class="info-row">
                        <div class="info-label">Tax Address:</div>
                        <div class="info-value">
                            <?php echo nl2br(htmlspecialchars($tax_info['address'])); ?>
                            <?php if (!empty($tax_info['city']) || !empty($tax_info['state']) || !empty($tax_info['postal_code'])): ?>
                            <br>
                            <?php echo htmlspecialchars($tax_info['city'] ?? ''); ?>, 
                            <?php echo htmlspecialchars($tax_info['state'] ?? ''); ?> 
                            <?php echo htmlspecialchars($tax_info['postal_code'] ?? ''); ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($tax_info['verified_at'])): ?>
                    <div class="info-row">
                        <div class="info-label">Verified At:</div>
                        <div class="info-value">
                            <?php echo date('F j, Y, h:i A', strtotime($tax_info['verified_at'])); ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    <?php else: ?>
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle"></i> No tax information provided yet.
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- Documents -->
                <?php if (!empty($documents)): ?>
                <div class="detail-card">
                    <h4 class="section-title">
                        <i class="bi bi-files me-2"></i>Uploaded Documents
                    </h4>
                    
                    <?php foreach ($documents as $doc): ?>
                    <div class="document-item">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <strong><?php echo htmlspecialchars($doc['document_type']); ?></strong>
                                <div class="small text-muted">
                                    Uploaded: <?php echo date('M j, Y', strtotime($doc['uploaded_at'])); ?>
                                </div>
                            </div>
                            <a href="../<?php echo htmlspecialchars($doc['document_path']); ?>" 
                               target="_blank" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-eye"></i> View
                            </a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Right Column -->
            <div class="col-md-4">
                <!-- Review Information -->
                <div class="detail-card">
                    <h4 class="section-title">
                        <i class="bi bi-clipboard-check me-2"></i>Review Information
                    </h4>
                    
                    <div class="info-row">
                        <div class="info-label">Review Status:</div>
                        <div class="info-value">
                            <span class="badge bg-<?php 
                                if ($merchant['review_status'] == 'approved') echo 'success';
                                elseif ($merchant['review_status'] == 'rejected') echo 'danger';
                                else echo 'warning';
                            ?>">
                                <?php echo strtoupper($merchant['review_status'] ?? 'pending'); ?>
                            </span>
                            <?php if (!empty($merchant['verification_score'])): ?>
                            <br><small>Score: <?php echo $merchant['verification_score']; ?>/100</small>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <?php if (!empty($merchant['reviewed_at'])): ?>
                    <div class="info-row">
                        <div class="info-label">Reviewed At:</div>
                        <div class="info-value">
                            <?php echo date('F j, Y, h:i A', strtotime($merchant['reviewed_at'])); ?>
                        </div>
                    </div>
                    
                    <div class="info-row">
                        <div class="info-label">Reviewed By:</div>
                        <div class="info-value">
                            <?php echo htmlspecialchars($reviewer_name); ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($merchant['admin_comments'])): ?>
                    <div class="info-row">
                        <div class="info-label">Admin Comments:</div>
                        <div class="info-value">
                            <div class="bg-light p-3 rounded">
                                <?php echo nl2br(htmlspecialchars($merchant['admin_comments'])); ?>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- Quick Actions -->
                <div class="detail-card">
                    <h4 class="section-title">
                        <i class="bi bi-lightning me-2"></i>Quick Actions
                    </h4>
                    
                    <div class="action-buttons">
                        <?php if ($merchant['status'] == 'under_review'): ?>
                        <!-- Approve Form -->
                        <button type="button" class="btn btn-success btn-lg w-100" data-bs-toggle="modal" data-bs-target="#approveModal">
                            <i class="bi bi-check-circle"></i> Approve
                        </button>
                        
                        <!-- Reject Button -->
                        <button type="button" class="btn btn-danger btn-lg w-100" data-bs-toggle="modal" data-bs-target="#rejectModal">
                            <i class="bi bi-x-circle"></i> Reject
                        </button>
                        
                        <!-- Request Corrections Button -->
                        <a href="request_corrections.php?id=<?php echo $merchant_id; ?>" class="btn btn-warning btn-lg w-100">
                            <i class="bi bi-exclamation-triangle"></i> Request Corrections
                        </a>
                        <?php endif; ?>
                        
                        <?php if ($merchant['status'] == 'active'): ?>
                        <a href="edit_merchant.php?id=<?php echo $merchant_id; ?>" class="btn btn-primary btn-lg w-100">
                            <i class="bi bi-pencil"></i> Edit Merchant
                        </a>
                        <?php endif; ?>
                        
                        <a href="mailto:<?php echo htmlspecialchars($merchant['email']); ?>" class="btn btn-outline-primary w-100">
                            <i class="bi bi-envelope"></i> Contact Owner
                        </a>
                    </div>
                </div>
                
                <!-- Store Hours -->
                <?php if (!empty($store_hours)): ?>
                <div class="detail-card">
                    <h4 class="section-title">
                        <i class="bi bi-clock me-2"></i>Store Hours
                    </h4>
                    
                    <?php 
                    $days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
                    foreach ($store_hours as $hour): 
                        $day_name = $days[$hour['day_of_week']] ?? "Day {$hour['day_of_week']}";
                    ?>
                    <div class="hours-item">
                        <span><?php echo $day_name; ?></span>
                        <span>
                            <?php if ($hour['is_closed']): ?>
                                <span class="text-danger">Closed</span>
                            <?php else: ?>
                                <?php echo date('g:i A', strtotime($hour['open_time'])); ?> - 
                                <?php echo date('g:i A', strtotime($hour['close_time'])); ?>
                            <?php endif; ?>
                        </span>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
                
                <!-- Plan Information -->
                <?php if (!empty($merchant['plan_type'])): ?>
                <div class="detail-card">
                    <h4 class="section-title">
                        <i class="bi bi-card-checklist me-2"></i>Plan Information
                    </h4>
                    
                    <div class="info-row">
                        <div class="info-label">Plan Type:</div>
                        <div class="info-value">
                            <span class="badge bg-info"><?php echo htmlspecialchars($merchant['plan_type']); ?></span>
                        </div>
                    </div>
                    
                    <div class="info-row">
                        <div class="info-label">Delivery Fee:</div>
                        <div class="info-value">
                            <?php echo $merchant['delivery_fee_percentage']; ?>%
                        </div>
                    </div>
                    
                    <div class="info-row">
                        <div class="info-label">Pickup Fee:</div>
                        <div class="info-value">
                            <?php echo $merchant['pickup_fee_percentage']; ?>%
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Approve Modal -->
    <div class="modal fade" id="approveModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title">Approve Merchant</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p>Are you sure you want to approve <strong><?php echo htmlspecialchars($merchant['store_name']); ?></strong>?</p>
                        <div class="mb-3">
                            <label class="form-label">Comments (optional):</label>
                            <textarea name="admin_comments" class="form-control" rows="3" placeholder="Add approval comments...">Approved</textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="approve" class="btn btn-success">Approve Merchant</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Reject Modal -->
    <div class="modal fade" id="rejectModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title">Reject Application</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p>Are you sure you want to reject <strong><?php echo htmlspecialchars($merchant['store_name']); ?></strong>?</p>
                        <div class="mb-3">
                            <label class="form-label">Reason for rejection:</label>
                            <textarea name="rejection_reason" class="form-control" rows="4" required placeholder="Please provide a reason..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="reject" class="btn btn-danger">Reject Application</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function showFullAccount() {
            document.getElementById('fullAccount').style.display = 'inline';
            event.target.style.display = 'none';
        }
        
        function showFullSSN() {
            document.getElementById('fullSSN').style.display = 'inline';
            event.target.style.display = 'none';
        }
        
        function showFullEIN() {
            document.getElementById('fullEIN').style.display = 'inline';
            event.target.style.display = 'none';
        }
    </script>
</body>
</html>