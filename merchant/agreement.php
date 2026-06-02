<?php
session_start();
include "../includes/db.php";

// Check if user came from chooseplan.php
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

// Fetch plan details
$plan_sql = "SELECT * FROM merchant_plans WHERE merchant_id = '$merchant_id'";
$plan_result = mysqli_query($conn, $plan_sql);
$plan = mysqli_fetch_assoc($plan_result);

if (!$merchant || !$plan) {
    header("Location: chooseplan.php");
    exit();
}

// Handle agreement acceptance
$error = "";
$success = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_agreement'])) {
    $agreement_accepted = isset($_POST['agreementCheck']) ? 1 : 0;
    
    if (!$agreement_accepted) {
        $error = "You must accept the merchant agreement terms to continue.";
    } else {
        // Update agreement status in database
        $current_time = date('Y-m-d H:i:s');
        $update_sql = "UPDATE merchant_plans SET 
                       agreed_to_terms = 1,
                       terms_agreed_at = '$current_time'
                       WHERE merchant_id = '$merchant_id'";
        
        if (mysqli_query($conn, $update_sql)) {
            $success = "Agreement accepted! Redirecting to setup...";
            header("Location: setup.php");
        } else {
            $error = "Failed to save agreement: " . mysqli_error($conn);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Review Agreement Summary - Uber Eats</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <style>
        /* General body and container styling */
        body {
            background-color: white;
            font-family: Arial, sans-serif;
            color: #333;
        }

        .page-content {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }

        /* Step indicator styling */
        .step-indicator {
            display: flex;
            justify-content: center;
            margin-bottom: 40px;
        }

        .step-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            position: relative;
            width: 33%;
            text-align: center;
        }

        .step-item:not(:last-child)::after {
            content: '';
            position: absolute;
            top: 15px;
            right: -50%;
            width: 100%;
            height: 2px;
            background-color: #ccc;
            z-index: 1;
        }

        /* Step 1 line is black (completed) */
        .step-item:nth-child(1)::after {
            background-color: black;
        }

        .step-icon {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background-color: #ccc;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            z-index: 2;
        }
        
        .step-item:nth-child(1) .step-icon {
            background-color: black;
        }
        
        .step-label {
            margin-top: 5px;
            font-size: 0.9rem;
            font-weight: 500;
        }

        .step-item:nth-child(1) .step-label {
            font-weight: bold;
        }

        .step-est {
            font-size: 0.75rem;
            color: #6c757d;
        }
        
        /* Agreement Content Styling */
        .agreement-summary {
            max-width: 500px;
            margin: 0 auto;
        }

        .back-arrow {
            font-size: 1.5rem;
            color: #333;
            text-decoration: none;
            margin-bottom: 15px;
            display: block;
        }

        .plan-box {
            background-color: #fff9f0; /* Light yellow background */
            border-left: 5px solid #ffcc00;
            padding: 20px;
            border-radius: 4px;
            margin-bottom: 30px;
            position: relative;
        }

        .plan-box .plan-title {
            font-size: 1.25rem;
            font-weight: bold;
        }

        .plan-box .plan-subtitle {
            font-size: 0.9rem;
            color: #6c757d;
            margin-bottom: 15px;
        }

        .plan-box .commission {
            float: right;
            text-align: right;
            font-weight: bold;
        }
        
        .plan-box .commission-rate {
            font-size: 1.25rem;
            line-height: 1;
        }
        
        .plan-box .commission-label {
            font-size: 0.8rem;
            font-weight: normal;
        }

        .plan-box .icon-pin {
            position: absolute;
            top: 10px;
            right: 10px;
            color: #ffcc00;
            font-size: 1.5rem;
        }

        /* Benefit List Styling */
        .benefit-list h5 {
            font-weight: bold;
            margin-top: 30px;
            margin-bottom: 15px;
        }

        .benefit-item {
            display: flex;
            align-items: flex-start;
            padding: 8px 0;
            font-size: 0.95rem;
        }

        .benefit-item .icon {
            font-size: 1.1rem;
            margin-right: 10px;
            line-height: 1.5;
        }

        .icon-check {
            color: #38c172; /* Green */
        }

        .icon-x {
            color: #dc3545; /* Red */
        }
        
        /* Final agreement section */
        .device-rental-fee {
            color: #38c172;
            font-weight: bold;
        }
        
        .agreement-text {
            font-size: 0.8rem;
            color: #6c757d;
            line-height: 1.5;
            margin-top: 15px;
        }

        .btn-submit {
            background-color: black;
            color: white;
            padding: 12px 30px;
            font-weight: bold;
            border-radius: 6px;
            float: right;
            margin-top: 20px;
            border: none;
            cursor: pointer;
            transition: background-color 0.2s;
        }
        
        .btn-submit:hover {
            background-color: #333;
        }
        
        .btn-submit:disabled {
            background-color: #ccc;
            cursor: not-allowed;
        }
        
        /* Messages */
        .alert {
            padding: 12px 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 0.95rem;
        }
        
        .alert-danger {
            background-color: rgba(220, 53, 69, 0.1);
            color: #dc3545;
            border: 1px solid rgba(220, 53, 69, 0.2);
        }
        
        .alert-success {
            background-color: rgba(40, 167, 69, 0.1);
            color: #28a745;
            border: 1px solid rgba(40, 167, 69, 0.2);
        }
        
        @media (max-width: 768px) {
            .page-content {
                padding: 15px;
            }
            
            .agreement-summary {
                max-width: 100%;
            }
            
            .plan-box .commission {
                float: none;
                text-align: left;
                margin-top: 10px;
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
        <hr>

        <div class="step-indicator">
            <div class="step-item">
                <div class="step-icon">✓</div>
                <div class="step-label fw-bold">Choose plan</div>
                <div class="step-est">Est 3-5 min</div>
            </div>
            <div class="step-item">
                <div class="step-icon">2</div>
                <div class="step-label">Set up store</div>
                <div class="step-est">Est 10-15 min</div>
            </div>
            <div class="step-item">
                <div class="step-icon">3</div>
                <div class="step-label">Wait for review</div>
                <div class="step-est">Est 1-3 days</div>
            </div>
        </div>

        <!-- Messages -->
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>

        <div class="agreement-summary">
            <a href="chooseplan.php" class="back-arrow">&larr;</a>
            
            <h1 class="h4 fw-bold mb-2">Review agreement summary</h1>
            <p class="mb-4 text-muted small">There's no long-term commitment to join Uber Eats.</p>

            <div class="plan-box clearfix">
                <span class="icon-pin float-end">📌</span>
                <div class="commission">
                    <div class="commission-rate"><?php echo $plan['delivery_fee_percentage']; ?>%</div>
                    <div class="commission-label">for Delivery orders</div>
                    <div class="commission-rate"><?php echo $plan['pickup_fee_percentage']; ?>%</div>
                    <div class="commission-label">for Pickup orders</div>
                </div>
                <div class="plan-title"><?php echo $plan['plan_type']; ?></div>
                <div class="plan-subtitle">
                    <?php 
                    if ($plan['plan_type'] == 'Lite') {
                        echo 'Keep Costs Low';
                    } elseif ($plan['plan_type'] == 'Plus') {
                        echo 'Grow Your Sales';
                    } else {
                        echo 'Maximize Your Sales';
                    }
                    ?>
                </div>
                <p class="small">
                    <?php 
                    if ($plan['plan_type'] == 'Lite') {
                        echo 'Sell to customers who already know you';
                    } elseif ($plan['plan_type'] == 'Plus') {
                        echo 'Get discovered by new customers';
                    } else {
                        echo 'Stand out to new customers';
                    }
                    ?>
                </p>
            </div>
            
            <!-- Benefits for Lite Plan -->
            <div class="benefit-list">
                <h5>Attract customers</h5>
                <div class="benefit-item">
                    <span class="icon icon-check">✓</span>
                    <div><strong>Keep Costs Low:</strong> Customers must search for you, and will see a higher delivery fee</div>
                </div>
                <div class="benefit-item">
                    <span class="icon icon-x">x</span>
                    <div><strong>Uber One:</strong> Not Included</div>
                </div>
                <div class="benefit-item">
                    <span class="icon icon-x">x</span>
                    <div><strong>Sales Guarantee:</strong> Not Included</div>
                </div>
                <div class="benefit-item">
                    <span class="icon icon-x">x</span>
                    <div><strong>Advertising Benefit:</strong> Not Included</div>
                </div>
            </div>

            <!-- Device Rental -->
            <div class="benefit-list">
                <h5>Device rental</h5>
                <div class="benefit-item">
                    <span class="icon <?php echo $plan['device_rental'] ? 'icon-check' : 'icon-x'; ?>">
                        <?php echo $plan['device_rental'] ? '✓' : 'x'; ?>
                    </span>
                    <div>
                        <strong>Data and wifi tablet - $6.99 per week</strong><br>
                        <span class="text-muted small">Rental fees will be automatically deducted from your earnings every week. Additional taxes may apply.</span>
                    </div>
                </div>
            </div>

            <!-- Other Perks -->
            <div class="benefit-list">
                <h5>Other perks</h5>
                <div class="benefit-item">
                    <span class="icon icon-check">✓</span>
                    <div>Reliable order processing</div>
                </div>
                <div class="benefit-item">
                    <span class="icon icon-check">✓</span>
                    <div>Zero credit card fees</div>
                </div>
                <div class="benefit-item">
                    <span class="icon icon-check">✓</span>
                    <div>Performance data and insights</div>
                </div>
                <div class="benefit-item">
                    <span class="icon icon-check">✓</span>
                    <div>24/7 support</div>
                </div>
            </div>

            <hr class="mt-4">

            <!-- Agreement Form -->
            <form method="POST" action="" id="agreementForm">
                <input type="hidden" name="submit_agreement" value="1">
                
                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" value="1" id="agreementCheck" name="agreementCheck" 
                           <?php echo ($plan['agreed_to_terms'] ?? 0) ? 'checked disabled' : ''; ?>>
                    <label class="form-check-label fw-bold" for="agreementCheck">
                        I've reviewed and accept the merchant agreement terms. <a href="#" class="text-dark">View Agreement</a>
                    </label>
                </div>
                
                <p class="agreement-text">
                    *Uber Eats reserves the right to modify plan benefits and service levels as needed, including but not limited to instances where maximum allowable fees are applicable in the Merchant's location or due to other applicable regulation. **If** Merchant is located in a local or state jurisdiction(s) that sets forth maximum allowable per-order fees for the provision of the services described herein or implement other applicable regulation that may impact such benefits or service, Merchant agrees that Portier may adjust services provided to Merchant to a level based on the maximum allowable per-order fee or applicable regulation in such jurisdiction(s). Such service limitations may include reducing or eliminating Premium and/or Plus plan benefits or adjusting any plan benefits impacted, such as 1) suspending monthly sponsored listings credits and minimum order fee credits; 2) Increased Delivery Fee ($) charged to Merchants' Customers; 3) Services related to Merchant's discoverability in the Uber Eats app; 4) Uber One member benefits may not apply to Customers orders fulfilled by Merchants. Other services or benefits may be impacted.
                </p>

                <button type="submit" class="btn btn-submit" id="submitBtn" 
                        <?php echo ($plan['agreed_to_terms'] ?? 0) ? 'disabled' : ''; ?>>
                    <?php echo ($plan['agreed_to_terms'] ?? 0) ? 'Already Submitted' : 'Submit'; ?>
                </button>
            </form>

        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    
    <script>
        // Enable/disable submit button based on checkbox
        const agreementCheck = document.getElementById('agreementCheck');
        const submitBtn = document.getElementById('submitBtn');
        
        // If already agreed, disable everything
        if (submitBtn.disabled) {
            agreementCheck.disabled = true;
        } else {
            // Enable/disable based on checkbox
            agreementCheck.addEventListener('change', function() {
                submitBtn.disabled = !this.checked;
            });
            
            // Initialize button state
            submitBtn.disabled = !agreementCheck.checked;
        }
        
        // Form validation
        document.getElementById('agreementForm').addEventListener('submit', function(e) {
            if (!agreementCheck.checked) {
                alert('You must accept the merchant agreement terms to continue.');
                e.preventDefault();
                return false;
            }
            return true;
        });
        
        // Prevent form resubmission on page refresh
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }
    </script>
</body>
</html>