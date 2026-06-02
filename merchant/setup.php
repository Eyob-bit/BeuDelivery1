<?php
session_start();
include "../includes/db.php";

// Check if user is logged in and completed agreement
if (!isset($_SESSION['user_id']) || !isset($_SESSION['merchant_id'])) {
    header("Location: getStarted.php");
    exit();
}

$merchant_id = $_SESSION['merchant_id'];
$user_id = $_SESSION['user_id'];

// Fetch merchant details
$merchant_sql = "SELECT m.*, u.email FROM merchants m 
                 JOIN users u ON m.user_id = u.id 
                 WHERE m.merchant_id = '$merchant_id'";
$merchant_result = mysqli_query($conn, $merchant_sql);
$merchant = mysqli_fetch_assoc($merchant_result);

// Fetch plan details to check if agreement was accepted
$plan_sql = "SELECT * FROM merchant_plans WHERE merchant_id = '$merchant_id'";
$plan_result = mysqli_query($conn, $plan_sql);
$plan = mysqli_fetch_assoc($plan_result);

// Check if agreement was accepted
if (!$plan || $plan['agreed_to_terms'] != 1) {
    header("Location: agreement.php");
    exit();
}

// Check completed tasks
$completed_tasks = [
    'security' => false,
    'store_details' => false,
    'menu' => false,
    'payment' => false,
    'tax' => false
];

// Check if password is set (security task)
$user_sql = "SELECT password_hash FROM users WHERE id = '$user_id'";
$user_result = mysqli_query($conn, $user_sql);
$user = mysqli_fetch_assoc($user_result);
$completed_tasks['security'] = !empty($user['password_hash']);

// Check store details
$details_sql = "SELECT * FROM merchant_details WHERE merchant_id = '$merchant_id'";
$details_result = mysqli_query($conn, $details_sql);
$completed_tasks['store_details'] = mysqli_num_rows($details_result) > 0;

// Check menu upload
$menu_sql = "SELECT * FROM merchant_documents WHERE merchant_id = '$merchant_id' AND document_type IN ('menu_pdf', 'menu_photo', 'menu_link')";
$menu_result = mysqli_query($conn, $menu_sql);
$completed_tasks['menu'] = mysqli_num_rows($menu_result) > 0;

// Check banking info
$banking_sql = "SELECT * FROM merchant_banking WHERE merchant_id = '$merchant_id'";
$banking_result = mysqli_query($conn, $banking_sql);
$completed_tasks['payment'] = mysqli_num_rows($banking_result) > 0;

// Check tax info
$tax_sql = "SELECT * FROM merchant_tax_info WHERE merchant_id = '$merchant_id'";
$tax_result = mysqli_query($conn, $tax_sql);
$completed_tasks['tax'] = mysqli_num_rows($tax_result) > 0;

// Count completed tasks
$total_tasks = count($completed_tasks);
$completed_count = array_sum(array_values($completed_tasks));
$progress_percentage = $total_tasks > 0 ? round(($completed_count / $total_tasks) * 100) : 0;

// NEW: Auto-redirect to final page when all tasks are completed
if ($completed_count == $total_tasks && $total_tasks > 0) {
    // Check if we should auto-redirect (only after completing the last task)
    if (isset($_GET['completed']) && $_GET['completed'] == 'true') {
        header("Location: finalpage.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Set up and verify your store - Uber Eats</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <style>
        /* (Keep all existing CSS styles - they're fine) */
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
           Step Indicator Styling 
           ----------------------- */
        .step-indicator {
            display: flex;
            justify-content: center;
            margin-bottom: 40px;
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

        /* Connecting Lines */
        .step-item:not(:last-child)::after {
            content: '';
            position: absolute;
            top: 15px;
            right: -50%;
            width: 100%;
            height: 2px;
            background-color: #e0e0e0;
            z-index: 1;
        }

        /* Line 1 (Plan -> Setup) is black (completed) */
        .step-item:first-child::after {
             background-color: black;
        }

        .step-icon {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background-color: #e0e0e0;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
            z-index: 2;
        }
        
        /* Step 1: Completed (Black Check) */
        .step-item:nth-child(1) .step-icon {
            background-color: black;
            color: white;
        }

        /* Step 2: Active (Store Icon) */
        .step-item:nth-child(2) .step-icon {
            background-color: black; /* Active step background */
            color: white;
        }

        /* Step 3: Pending (Clock) - default gray */
        .step-item:nth-child(3) .step-icon {
            background-color: #e0e0e0;
            color: #666;
        }

        .step-label {
            margin-top: 8px;
            font-size: 0.85rem;
            font-weight: 500;
            color: #333;
        }

        .step-item:nth-child(1) .step-label,
        .step-item:nth-child(2) .step-label {
            font-weight: bold;
        }

        .step-est {
            font-size: 0.75rem;
            color: #6c757d;
            margin-bottom: 2px;
        }

        /* Progress Bar */
        .progress-container {
            margin-bottom: 30px;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 8px;
        }

        .progress-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }

        .progress-text {
            font-weight: bold;
            font-size: 1rem;
        }

        .progress-percentage {
            color: #28a745;
            font-weight: bold;
        }

        .progress-bar {
            height: 8px;
            background-color: #e9ecef;
            border-radius: 4px;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            background-color: #28a745;
            border-radius: 4px;
            transition: width 0.3s ease;
        }

        /* -----------------------
           Security Alert Box 
           ----------------------- */
        .security-alert {
            background-color: #D4142B; /* Specific Red from screenshot */
            color: white;
            border-radius: 8px;
            padding: 20px;
            display: flex;
            align-items: flex-start;
            margin-bottom: 30px;
        }

        .alert-icon {
            font-size: 2rem;
            margin-right: 15px;
            line-height: 1;
        }

        .alert-content h5 {
            font-size: 1rem;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .alert-content p {
            font-size: 0.9rem;
            margin-bottom: 0;
            opacity: 0.9;
        }

        /* -----------------------
           Task List Styling 
           ----------------------- */
        .page-title {
            font-weight: bold;
            margin-bottom: 25px;
        }

        .task-item {
            border-radius: 8px;
            padding: 20px 25px;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        /* Task variant: Urgent (Security) */
        .task-urgent {
            background-color: #FCF0EF; /* Light pink background */
        }

        /* Task variant: Submitted */
        .task-submitted {
            background-color: #d4edda; /* Light green background */
        }

        /* Task variant: Standard */
        .task-standard {
            background-color: #F6F6F6; /* Light gray background */
        }

        .task-left {
            display: flex;
            align-items: center;
        }

        .task-icon {
            font-size: 1.25rem;
            margin-right: 20px;
            width: 24px;
            text-align: center;
            color: #000;
        }

        .task-submitted .task-icon {
            color: #28a745;
        }

        .task-details h6 {
            font-weight: bold;
            margin-bottom: 3px;
            font-size: 1rem;
        }

        .task-details p {
            font-size: 0.85rem;
            color: #6c757d;
            margin-bottom: 0;
        }

        .btn-start {
            background-color: white;
            color: black;
            font-weight: bold;
            border: none;
            padding: 8px 20px;
            border-radius: 20px;
            font-size: 0.9rem;
            white-space: nowrap;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            transition: background-color 0.2s;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }

        .btn-start:hover {
            background-color: #f0f0f0;
            color: black;
            text-decoration: none;
        }

        .status-submitted {
            font-weight: bold;
            color: #28a745;
            display: flex;
            align-items: center;
            font-size: 0.9rem;
        }

        .status-submitted i {
            margin-right: 5px;
        }
        
        /* Auto-redirect notice */
        .auto-redirect-notice {
            background-color: #e7f3ff;
            border: 1px solid #b6d4fe;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
            text-align: center;
            font-size: 0.95rem;
        }
        
        .auto-redirect-notice i {
            color: #0d6efd;
            margin-right: 8px;
        }
        
        .countdown-text {
            font-weight: bold;
            color: #0d6efd;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .task-item {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .task-left {
                margin-bottom: 15px;
                width: 100%;
            }
            
            .btn-start, .status-submitted {
                align-self: flex-end;
            }
            
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
                <span class="ms-3"><a href="../index.html" class="text-dark text-decoration-none small">Log out</a></span>
            </div>
        </div>
        
        <div class="step-indicator">
            <div class="step-item">
                <div class="step-icon"><i class="bi bi-check-lg"></i></div>
                <div class="step-label">Choose plan</div>
                <div class="step-est">Est 3-5 min</div>
            </div>
            <div class="step-item">
                <div class="step-icon"><i class="bi bi-shop"></i></div>
                <div class="step-label">Set up store</div>
                <div class="step-est">Est 10-15 min</div>
            </div>
            <div class="step-item">
                <div class="step-icon"><i class="bi bi-clock"></i></div>
                <div class="step-label">Wait for review</div>
                <div class="step-est">Est 1-3 days</div>
            </div>
        </div>

        <?php if ($completed_count == $total_tasks && $total_tasks > 0): ?>
        <!-- Auto-redirect notice -->
        <div class="auto-redirect-notice" id="redirectNotice">
            <i class="bi bi-info-circle-fill"></i>
            All tasks completed! You will be redirected to the final review page in <span class="countdown-text" id="countdown">5</span> seconds.
            <br>
            <a href="finalpage.php" class="btn btn-primary btn-sm mt-2">Go Now</a>
        </div>
        <?php endif; ?>

        <!-- Progress Bar -->
        <div class="progress-container">
            <div class="progress-header">
                <div class="progress-text">Setup Progress</div>
                <div class="progress-percentage"><?php echo $progress_percentage; ?>%</div>
            </div>
            <div class="progress-bar">
                <div class="progress-fill" style="width: <?php echo $progress_percentage; ?>%;"></div>
            </div>
            <div class="text-end mt-2 small text-muted">
                <?php echo $completed_count; ?> of <?php echo $total_tasks; ?> tasks completed
            </div>
        </div>

        <?php if (!$completed_tasks['security']): ?>
        <div class="security-alert">
            <div class="alert-icon">
                <i class="bi bi-exclamation-circle-fill"></i>
            </div>
            <div class="alert-content">
                <h5>Complete mandatory security step</h5>
                <p>Scams are a growing threat across the industry. We're adding an additional layer of security to help protect your banking information and earnings. It takes about 30 seconds to complete.</p>
            </div>
        </div>
        <?php endif; ?>

        <h3 class="page-title">Set up and verify your store</h3>

        <!-- Security Task -->
        <div class="task-item <?php echo $completed_tasks['security'] ? 'task-submitted' : 'task-urgent'; ?>">
            <div class="task-left">
                <div class="task-icon"><i class="bi bi-shield-lock-fill"></i></div>
                <div class="task-details">
                    <h6>Set up security</h6>
                    <p>Add an additional layer of security to your account.</p>
                </div>
            </div>
            <?php if ($completed_tasks['security']): ?>
                <div class="status-submitted">
                    <i class="bi bi-check-lg"></i> Submitted
                </div>
            <?php else: ?>
                <a href="setupsecurity.php" class="btn-start">Start &rarr;</a>
            <?php endif; ?>
        </div>

        <!-- Store Details Task -->
        <div class="task-item <?php echo $completed_tasks['store_details'] ? 'task-submitted' : 'task-standard'; ?>">
            <div class="task-left">
                <div class="task-icon"><i class="bi bi-shop-window"></i></div>
                <div class="task-details">
                    <h6>Enter store details</h6>
                    <p>Tell us your store cuisine, phone number, pick up instructions, and preferred Uber Eats launch date.</p>
                </div>
            </div>
            <?php if ($completed_tasks['store_details']): ?>
                <div class="status-submitted">
                    <i class="bi bi-check-lg"></i> Submitted
                </div>
            <?php else: ?>
                <a href="enter_store_details.php" class="btn-start">Start &rarr;</a>
            <?php endif; ?>
        </div>

        <!-- Menu Upload Task -->
        <div class="task-item <?php echo $completed_tasks['menu'] ? 'task-submitted' : 'task-standard'; ?>">
            <div class="task-left">
                <div class="task-icon"><i class="bi bi-cup-straw"></i></div>
                <div class="task-details">
                    <h6>Upload Menu & Update Menu Hours</h6>
                    <p>Submit a photo, PDF, or link and we'll build your menu for you.</p>
                </div>
            </div>
            <?php if ($completed_tasks['menu']): ?>
                <div class="status-submitted">
                    <i class="bi bi-check-lg"></i> Submitted
                </div>
            <?php else: ?>
                <a href="uploadmenu.php" class="btn-start">Start &rarr;</a>
            <?php endif; ?>
        </div>

        <!-- Payment Setup Task -->
        <div class="task-item <?php echo $completed_tasks['payment'] ? 'task-submitted' : 'task-standard'; ?>">
            <div class="task-left">
                <div class="task-icon"><i class="bi bi-credit-card-fill"></i></div>
                <div class="task-details">
                    <h6>Set up payment</h6>
                    <p>Provide your banking info to make sure you get paid on time.</p>
                </div>
            </div>
            <?php if ($completed_tasks['payment']): ?>
                <div class="status-submitted">
                    <i class="bi bi-check-lg"></i> Submitted
                </div>
            <?php else: ?>
                <a href="setup_payment.php" class="btn-start">Start &rarr;</a>
            <?php endif; ?>
        </div>

        <!-- Tax Info Task -->
        <div class="task-item <?php echo $completed_tasks['tax'] ? 'task-submitted' : 'task-standard'; ?>">
            <div class="task-left">
                <div class="task-icon"><i class="bi bi-file-earmark-person-fill"></i></div>
                <div class="task-details">
                    <h6>Enter tax info</h6>
                    <p>Verify your business and be prepared for tax season.</p>
                </div>
            </div>
            <?php if ($completed_tasks['tax']): ?>
                <div class="status-submitted">
                    <i class="bi bi-check-lg"></i> Submitted
                </div>
            <?php else: ?>
                <a href="enter_tax_info.php" class="btn-start">Start &rarr;</a>
            <?php endif; ?>
        </div>

        <!-- Manual button (kept for compatibility) -->
        <?php if ($completed_count == $total_tasks && $total_tasks > 0): ?>
        <div class="text-center mt-4">
            <a href="finalpage.php" class="btn btn-success">
                <i class="bi bi-check-circle me-2"></i> Go to Final Review Page
            </a>
        </div>
        <?php endif; ?>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    
    <script>
        // Auto-redirect countdown when all tasks are completed
        <?php if ($completed_count == $total_tasks && $total_tasks > 0): ?>
        let countdown = 5; // seconds
        const countdownElement = document.getElementById('countdown');
        const redirectNotice = document.getElementById('redirectNotice');
        
        function updateCountdown() {
            if (countdown > 0) {
                countdownElement.textContent = countdown;
                countdown--;
                setTimeout(updateCountdown, 1000);
            } else {
                // Redirect to final page
                window.location.href = 'finalpage.php';
            }
        }
        
        // Start countdown
        setTimeout(updateCountdown, 1000);
        <?php endif; ?>
        
        // Auto-refresh page every 30 seconds to update progress
        setTimeout(function() {
            window.location.reload();
        }, 30000); // 30 seconds
    </script>
</body>
</html>