<?php
session_start();
include "../includes/db.php";

// Check if user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['merchant_id'])) {
    header("Location: getStarted.php");
    exit();
}

$merchant_id = $_SESSION['merchant_id'];
$user_id = $_SESSION['user_id'];

// Update merchant status to "under_review"
$update_sql = "UPDATE merchants SET status = 'under_review', updated_at = NOW() WHERE merchant_id = '$merchant_id'";
mysqli_query($conn, $update_sql);

// Fetch merchant details
$merchant_sql = "SELECT m.*, u.email, u.first_name, u.last_name FROM merchants m 
                 JOIN users u ON m.user_id = u.id 
                 WHERE m.merchant_id = '$merchant_id'";
$merchant_result = mysqli_query($conn, $merchant_sql);
$merchant = mysqli_fetch_assoc($merchant_result);

if (!$merchant) {
    header("Location: setup.php");
    exit();
}

// Create a review record
$review_check_sql = "SELECT * FROM merchant_reviews WHERE merchant_id = '$merchant_id'";
$review_result = mysqli_query($conn, $review_check_sql);

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
    $review_result = mysqli_query($conn, $review_check_sql);
}

if ($review_result && mysqli_num_rows($review_result) == 0) {
    $review_id = 'REV' . date('Ymd') . str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);
    $insert_review_sql = "INSERT INTO merchant_reviews (review_id, merchant_id, status, submitted_at, estimated_completion) 
                         VALUES ('$review_id', '$merchant_id', 'pending', NOW(), DATE_ADD(NOW(), INTERVAL 3 DAY))";
    mysqli_query($conn, $insert_review_sql);
}

// Get review details
$review_sql = "SELECT * FROM merchant_reviews WHERE merchant_id = '$merchant_id' ORDER BY submitted_at DESC LIMIT 1";
$review_result = mysqli_query($conn, $review_sql);
$review = $review_result ? mysqli_fetch_assoc($review_result) : null;

// Format dates
if ($review) {
    $submitted_date = date('M j, Y', strtotime($review['submitted_at']));
    $estimated_date = date('M j, Y', strtotime($review['estimated_completion']));
} else {
    $submitted_date = date('M j, Y');
    $estimated_date = date('M j, Y', strtotime('+3 days'));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account in review - BeU Delivery</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <style>
        /* General body styling */
        body {
            background-color: white;
            font-family: Arial, sans-serif;
            color: #333;
        }

        .page-content {
            max-width: 900px;
            margin: 0 auto;
            padding: 20px;
        }

        /* -----------------------
           Step Indicator Styling (All completed)
           ----------------------- */
        .step-indicator {
            display: flex;
            justify-content: center;
            margin-bottom: 60px;
            margin-top: 20px;
        }

        .step-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            position: relative;
            width: 30%;
            text-align: center;
        }

        /* Connecting Lines (All black/completed) */
        .step-item:not(:last-child)::after {
            content: '';
            position: absolute;
            top: 15px;
            right: -50%;
            width: 100%;
            height: 2px;
            background-color: black;
            z-index: 1;
        }

        .step-icon {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background-color: black; /* All steps are completed/active */
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
            z-index: 2;
        }
        
        .step-label {
            margin-top: 8px;
            font-size: 0.85rem;
            font-weight: bold; /* All completed steps are bold */
            color: #333;
        }

        .step-est {
            font-size: 0.75rem;
            color: #6c757d;
            margin-top: 2px;
        }
        
        /* -----------------------
           Review Content Styling 
           ----------------------- */
        .review-container {
            max-width: 450px;
            margin: 0 auto;
            text-align: center;
        }

        .review-icon-container {
            margin-bottom: 30px;
            width: 100px; 
            height: 100px;
            margin: 0 auto 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: transparent;
        }
        
        /* CSS for the specific icon image (clipboard with checkmarks) */
        .clipboard-icon {
            position: relative;
            width: 100px;
            height: 100px;
            border: 2px solid #ffc000;
            border-radius: 10px;
            background-color: white;
            box-shadow: 0 0 10px rgba(0,0,0,0.05);
            display: inline-block;
        }
        
        .clipboard-header {
            width: 40px;
            height: 10px;
            background-color: #ffc000;
            border-radius: 0 0 5px 5px;
            position: absolute;
            top: -10px;
            left: 50%;
            transform: translateX(-50%);
        }
        
        .clipboard-check-line {
            width: 60px;
            height: 4px;
            background-color: #ffc000;
            border-radius: 2px;
            margin: 15px auto;
        }
        
        .clipboard-check-line:nth-child(3) {
            opacity: 0.7;
            width: 50px;
        }
        
        .clipboard-check-line:nth-child(4) {
            opacity: 0.5;
            width: 70px;
        }
        
        /* The Actual Title and Description */
        .review-title {
            font-size: 1.8rem;
            font-weight: bold;
            margin-bottom: 15px;
        }

        .review-description {
            font-size: 0.95rem;
            color: #6c757d;
            line-height: 1.5;
            margin-bottom: 40px;
        }

        /* Review Details */
        .review-details {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 30px;
            text-align: left;
        }
        
        .review-detail-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        
        .review-detail-item:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }
        
        .detail-label {
            font-weight: 500;
            color: #6c757d;
        }
        
        .detail-value {
            font-weight: bold;
            color: #333;
        }
        
        /* Status Badge */
        .status-badge {
            background-color: #ffc000;
            color: #333;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: bold;
        }

        /* Buttons */
        .btn-go-manager {
            background-color: white;
            color: black;
            font-weight: bold;
            padding: 12px 30px;
            border: 1px solid #ddd;
            border-radius: 8px;
            width: 100%;
            text-decoration: none;
            display: block;
            transition: all 0.2s;
        }

        .btn-go-manager:hover {
            border-color: black;
            background-color: #f8f9fa;
        }
        
        /* Notification Box */
        .notification-box {
            background-color: #e7f3ff;
            border: 1px solid #b6d4fe;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 25px;
            text-align: left;
            font-size: 0.9rem;
        }
        
        .notification-box i {
            color: #0d6efd;
            margin-right: 8px;
        }
        
        /* Next Steps */
        .next-steps {
            margin-top: 40px;
            text-align: left;
        }
        
        .next-steps-title {
            font-weight: bold;
            margin-bottom: 15px;
            font-size: 1.1rem;
        }
        
        .next-step-item {
            display: flex;
            align-items: flex-start;
            margin-bottom: 15px;
        }
        
        .next-step-icon {
            background-color: #f8f9fa;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 12px;
            flex-shrink: 0;
            color: #6c757d;
        }
        
        .next-step-text {
            font-size: 0.9rem;
        }
        
        @media (max-width: 768px) {
            .step-indicator {
                flex-direction: column;
                align-items: center;
            }
            
            .step-item {
                width: 100%;
                margin-bottom: 20px;
            }
            
            .step-item::after {
                display: none;
            }
            
            .review-container {
                padding: 0 15px;
            }
        }
    </style>
</head>
<body>
    <div class="page-content">

        <div class="d-flex justify-content-between align-items-center py-3">
            <h2 class="h5 fw-bold m-0">BeU Delivery <span class="fw-normal fs-6 text-muted">for Merchants</span></h2>
            <div>
                <span class="text-dark small fw-bold">
                    <?php echo htmlspecialchars($merchant['store_name']); ?> - 
                    <?php echo htmlspecialchars($merchant['store_address']); ?>
                </span>
                <span class="ms-3"><a href="#" class="text-dark text-decoration-none small">Help</a></span>
                <span class="ms-3"><a href="../index.php" class="text-dark text-decoration-none small">Log out</a></span>
            </div>
        </div>
        
        <div class="step-indicator">
            <div class="step-item">
                <div class="step-icon"><i class="bi bi-check-lg"></i></div>
                <div class="step-label">Choose plan</div>
                <div class="step-est">Est 3-5 min</div>
            </div>
            <div class="step-item">
                <div class="step-icon"><i class="bi bi-check-lg"></i></div>
                <div class="step-label">Set up store</div>
                <div class="step-est">Est 10-15 min</div>
            </div>
            <div class="step-item">
                <div class="step-icon"><i class="bi bi-check-lg"></i></div>
                <div class="step-label">Wait for review</div>
                <div class="step-est">Est 1-3 days</div>
            </div>
        </div>

        <div class="review-container">
            
            <div class="review-icon-container">
                <div class="clipboard-icon">
                    <div class="clipboard-header"></div>
                    <div class="clipboard-check-line"></div>
                    <div class="clipboard-check-line"></div>
                    <div class="clipboard-check-line"></div>
                </div>
            </div>

            <h1 class="review-title">Account in review</h1>
            
            <div class="notification-box">
                <i class="bi bi-info-circle-fill"></i>
                <strong>Thank you for submitting your application!</strong> Our team is now reviewing your information to ensure everything meets our requirements.
            </div>
            
            <div class="review-details">
                <div class="review-detail-item">
                    <span class="detail-label">Status</span>
                    <span class="detail-value"><span class="status-badge">Under Review</span></span>
                </div>
                <div class="review-detail-item">
                    <span class="detail-label">Submitted on</span>
                    <span class="detail-value"><?php echo $submitted_date; ?></span>
                </div>
                <div class="review-detail-item">
                    <span class="detail-label">Estimated completion</span>
                    <span class="detail-value"><?php echo $estimated_date; ?></span>
                </div>
                <div class="review-detail-item">
                    <span class="detail-label">Application ID</span>
                    <span class="detail-value"><?php echo htmlspecialchars($merchant_id); ?></span>
                </div>
            </div>

            <p class="review-description">
                Great work! We've received your information and will get back to you as soon as possible (it may take up to a few days). In the meantime, check out the Merchant Portal, your home for managing your business on our platform.
            </p>

            <!-- FIXED LINK - Now points to the correct under-review dashboard -->
            <a href="../account/accountunderreview.php" class="btn-go-manager mb-3">
                Go to Merchant Portal
            </a>
            
            <a href="setup.php" class="btn-go-manager">
                <i class="bi bi-arrow-left me-2"></i>Back to Setup
            </a>
            
            <div class="next-steps">
                <div class="next-steps-title">What happens next?</div>
                
                <div class="next-step-item">
                    <div class="next-step-icon"><i class="bi bi-1-circle"></i></div>
                    <div class="next-step-text">
                        <strong>Verification:</strong> Our team will verify your business details, menu, and documentation.
                    </div>
                </div>
                
                <div class="next-step-item">
                    <div class="next-step-icon"><i class="bi bi-2-circle"></i></div>
                    <div class="next-step-text">
                        <strong>Background check:</strong> We'll conduct necessary background checks as per our policy.
                    </div>
                </div>
                
                <div class="next-step-item">
                    <div class="next-step-icon"><i class="bi bi-3-circle"></i></div>
                    <div class="next-step-text">
                        <strong>Approval notification:</strong> You'll receive an email notification once your account is approved.
                    </div>
                </div>
                
                <div class="next-step-item">
                    <div class="next-step-icon"><i class="bi bi-4-circle"></i></div>
                    <div class="next-step-text">
                        <strong>Get started:</strong> Once approved, you can start taking orders immediately!
                    </div>
                </div>
            </div>
            
            <div class="mt-4 small text-muted">
                <i class="bi bi-envelope me-1"></i>
                Questions? Contact our support team at <a href="mailto:support@beudelivery.com">support@beudelivery.com</a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    
    <script>
        // Save completion timestamp to localStorage
        localStorage.setItem('applicationSubmitted', new Date().toISOString());
        
        // Check for updates every hour
        setInterval(function() {
            // You can add AJAX call here to check for status updates
            console.log('Checking for application status updates...');
        }, 3600000); // Check every hour
        
        // Show notification about checking email
        setTimeout(function() {
            if (confirm("Don't forget to check your email for updates about your application! Would you like to check your email now?")) {
                window.open('mailto:', '_blank');
            }
        }, 30000); // Show after 30 seconds
    </script>
</body>
</html>