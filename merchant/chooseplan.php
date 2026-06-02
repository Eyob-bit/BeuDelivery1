<?php
session_start();
include "../includes/db.php";

// Check if user came from getStarted.php
if (!isset($_SESSION['user_id']) || !isset($_SESSION['merchant_id'])) {
    header("Location: getStarted.php");
    exit();
}

// Get merchant info from session/database
$merchant_id = $_SESSION['merchant_id'];
$user_id = $_SESSION['user_id'];

// Fetch merchant details
$merchant_sql = "SELECT m.*, u.email FROM merchants m 
                 JOIN users u ON m.user_id = u.id 
                 WHERE m.merchant_id = '$merchant_id'";
$merchant_result = mysqli_query($conn, $merchant_sql);
$merchant = mysqli_fetch_assoc($merchant_result);

if (!$merchant) {
    header("Location: getStarted.php");
    exit();
}

// Handle plan selection
$error = "";
$success = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['plan_type'])) {
    $plan_type = mysqli_real_escape_string($conn, $_POST['plan_type']);
    
    // Update plan in database
    $update_sql = "UPDATE merchant_plans SET 
                   plan_type = '$plan_type',
                   agreed_to_terms = 0
                   WHERE merchant_id = '$merchant_id'";
    
    if (mysqli_query($conn, $update_sql)) {
        $_SESSION['selected_plan'] = $plan_type;
        $success = "Lite plan selected! Redirecting to agreement...";
        header("refresh:2;url=agreement.php");
    } else {
        $error = "Failed to save plan: " . mysqli_error($conn);
    }
}

// Get current plan
$plan_sql = "SELECT * FROM merchant_plans WHERE merchant_id = '$merchant_id'";
$plan_result = mysqli_query($conn, $plan_sql);
$current_plan = mysqli_fetch_assoc($plan_result);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Choose Plan - BeU Delivery for Merchants</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <style>
        /* General body styling */
        body {
            background-color: #f7f7f7;
            font-family: Arial, sans-serif;
        }

        /* Container for the whole page content */
        .page-content {
            max-width: 1200px;
            margin: auto;
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

        .step-item::after {
            content: '';
            position: absolute;
            top: 15px;
            left: 50%;
            width: 100%;
            height: 2px;
            background-color: #ccc;
            z-index: 1;
        }

        .step-item:last-child::after {
            display: none;
        }

        .step-icon {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background-color: black;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            z-index: 2; /* Keep icon on top of line */
        }

        .step-item:nth-child(2) .step-icon {
            background-color: #ccc;
        }
        
        .step-item:nth-child(3) .step-icon {
            background-color: #ccc;
        }

        .step-label {
            margin-top: 5px;
            font-size: 0.9rem;
            font-weight: bold;
        }
        
        .step-item:nth-child(2) .step-label,
        .step-item:nth-child(3) .step-label {
            font-weight: normal;
        }

        .step-est {
            font-size: 0.75rem;
            color: #6c757d;
        }

        /* Messages */
        .alert {
            padding: 12px 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 0.95rem;
        }
        
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        /* Delivery toggle styling */
        .delivery-toggle {
            display: flex;
            justify-content: center;
            margin-bottom: 40px;
            background-color: #e9ecef;
            border-radius: 8px;
            padding: 5px;
            width: fit-content;
            margin-left: auto;
            margin-right: auto;
        }

        .toggle-btn {
            padding: 8px 20px;
            border: none;
            background: transparent;
            font-weight: 600;
            color: #6c757d;
            border-radius: 6px;
            cursor: pointer;
        }

        .toggle-btn.active {
            background-color: white;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            color: black;
        }

        /* Card styling */
        .plan-card {
            border: 1px solid #ccc;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            height: 100%;
            display: flex;
            flex-direction: column;
            transition: all 0.3s;
        }

        .plan-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .plan-card.selected {
            border-color: black;
            border-width: 2px;
        }

        .plan-header {
            min-height: 100px;
            margin-bottom: 15px;
        }

        .plan-title {
            font-weight: bold;
            font-size: 1.5rem;
        }

        .plan-subtitle {
            font-size: 0.9rem;
            color: #6c757d;
        }

        .plan-star {
            font-size: 2.5rem;
            margin-bottom: 10px;
        }

        .plan-rate {
            font-size: 1.5rem;
            font-weight: bold;
            margin-top: 10px;
        }

        .rate-details {
            font-size: 0.9rem;
            color: #6c757d;
            margin-bottom: 20px;
        }

        .btn-choose {
            width: 100%;
            background-color: black;
            border-color: black;
            font-weight: bold;
            padding: 10px 0;
            margin-top: auto; /* Push button to the bottom */
            cursor: pointer;
            transition: all 0.2s;
            border: none;
            color: white;
            border-radius: 6px;
        }

        .btn-choose:hover {
            background-color: #333;
            border-color: #333;
        }
        
        /* Plan-specific highlights (border/color) */
        .plan-card.lite { border-top: 4px solid #f6b553; }
        .plan-card.plus { border-top: 4px solid #6c757d; }
        .plan-card.premium { border-top: 4px solid #38c172; }
        
        .plan-card.lite .plan-star { color: #f6b553; }
        .plan-card.plus .plan-star { color: #6c757d; }
        .plan-card.premium .plan-star { color: #38c172; }

        /* Comparison grid styling */
        .comparison-grid-header {
            font-weight: bold;
            padding: 15px;
            text-align: left;
        }

        .comparison-item {
            display: flex;
            align-items: center;
            border-bottom: 1px solid #eee;
            padding: 15px 0;
            min-height: 80px;
        }

        .comparison-label {
            font-weight: 500;
            font-size: 1rem;
        }

        .comparison-benefit {
            text-align: center;
            font-size: 0.9rem;
        }

        .comparison-benefit .icon {
            font-size: 1.2rem;
            margin-right: 5px;
        }

        .icon-check {
            color: #38c172; /* Green */
        }

        .icon-x {
            color: #dc3545; /* Red */
        }

        .info-icon {
            color: #6c757d;
            margin-left: 5px;
            cursor: pointer;
        }

        /* Benefits across all plans styling */
        .all-benefits {
            background-color: white;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 30px;
            margin-top: 40px;
        }

        .all-benefits-list {
            display: flex;
            justify-content: space-around;
            flex-wrap: wrap;
            margin-top: 20px;
        }

        .benefit-item {
            text-align: center;
            flex: 1;
            min-width: 150px;
            padding: 10px;
            font-size: 0.95rem;
            color: #343a40;
        }

        .benefit-item .icon {
            font-size: 2rem;
            margin-bottom: 5px;
            color: black;
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

        <!-- Messages -->
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>

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

        <div class="delivery-toggle">
            <button class="toggle-btn active">Use delivery people on Uber</button>
            <button class="toggle-btn">Use your own delivery staff</button>
        </div>

        <h3 class="text-center mb-4 fw-bold">Choose the plan that's right for your business*</h3>

        <!-- Plan Selection Form -->
        <form method="POST" action="" id="planForm">
            <input type="hidden" name="plan_type" id="selectedPlan" value="">
            
            <div class="row row-cols-1 row-cols-md-3 g-4 mb-5">
                <div class="col">
                    <div class="plan-card lite shadow-sm" id="planLite">
                        <div class="plan-header">
                            <div class="plan-title">Lite</div>
                            <div class="plan-subtitle">Keep Costs Low</div>
                            <p class="small text-muted">Sell to customers who already know you</p>
                        </div>
                        <div class="plan-rate">15%</div>
                        <div class="rate-details">for "Delivery orders"*</div>
                        <div class="plan-rate">6%</div>
                        <div class="rate-details">for "Pickup orders"*</div>
                        <button type="button" class="btn-choose mt-4" onclick="selectPlan('Lite')">
                            Choose Lite
                        </button>
                    </div>
                </div>

                <div class="col">
                    <div class="plan-card plus shadow-sm" id="planPlus">
                        <div class="plan-header">
                            <div class="plan-title">Plus</div>
                            <div class="plan-subtitle">Grow Your Sales</div>
                            <p class="small text-muted">Get discovered by new customers</p>
                        </div>
                        <div class="plan-rate">25%</div>
                        <div class="rate-details">for "Delivery orders"*</div>
                        <div class="plan-rate">6%</div>
                        <div class="rate-details">for "Pickup orders"*</div>
                        <button type="button" class="btn-choose mt-4" onclick="selectPlan('Plus')">
                            Choose Plus
                        </button>
                    </div>
                </div>

                <div class="col">
                    <div class="plan-card premium shadow-sm" id="planPremium">
                        <div class="plan-header">
                            <div class="plan-title">Premium</div>
                            <div class="plan-subtitle">Maximize Your Sales</div>
                            <div class="plan-star">★</div>
                            <p class="small text-muted">Stand out to new customers</p>
                        </div>
                        <div class="plan-rate">30%</div>
                        <div class="rate-details">for "Delivery orders"*</div>
                        <div class="plan-rate">6%</div>
                        <div class="rate-details">for "Pickup orders"*</div>
                        <button type="button" class="btn-choose mt-4" onclick="selectPlan('Premium')">
                            Choose Premium
                        </button>
                    </div>
                </div>
            </div>
        </form>
        
        <!-- Lite Plan Details (always shown) -->
        <div id="litePlanDetails">
            <div class="row mt-5">
                <div class="col-12 text-center mb-4">
                    <button class="btn btn-outline-dark px-4 fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#comparisonDetails">
                        + Compare Plan Details
                    </button>
                </div>
                
                <!-- Comparison Details (Collapsible) -->
                <div class="collapse" id="comparisonDetails">
                    <div class="row border-bottom py-3 d-none d-md-flex">
                        <div class="col-md-4 comparison-grid-header"></div>
                        <div class="col-md-4 comparison-grid-header">Grow Sales</div>
                        <div class="col-md-4 comparison-grid-header">Maximize Sales</div>
                    </div>

                    <div class="row comparison-item">
                        <div class="col-12 col-md-4 comparison-label">
                            <span class="fw-bold">Keep Costs Low:</span> Customers must search for you, and will see a higher delivery fee
                        </div>
                        <div class="col-6 col-md-4 comparison-benefit text-start text-md-center">
                            <span class="icon icon-check">✓</span> Increased in-app discoverability, shown with lower delivery fee
                        </div>
                        <div class="col-6 col-md-4 comparison-benefit text-start text-md-center">
                            <span class="icon icon-check">✓</span> Increased in-app discoverability, shown with the lowest available delivery fee
                        </div>
                    </div>

                    <div class="row comparison-item">
                        <div class="col-12 col-md-4 comparison-label">
                            <span class="fw-bold">Uber One:</span> Not Included
                        </div>
                        <div class="col-6 col-md-4 comparison-benefit text-start text-md-center">
                            <span class="icon icon-check">✓</span> Stand out to high value Uber One customers with $0 delivery fee, at no additional cost to you <span class="info-icon">ⓘ</span>
                        </div>
                        <div class="col-6 col-md-4 comparison-benefit text-start text-md-center">
                            <span class="icon icon-check">✓</span> Stand out to high value Uber One customers with $0 delivery fee, at no additional cost to you <span class="info-icon">ⓘ</span>
                        </div>
                    </div>

                    <div class="row comparison-item">
                        <div class="col-12 col-md-4 comparison-label">
                            <span class="icon icon-x">x</span> <span class="fw-bold">Sales Guarantee:</span> Not Included
                        </div>
                        <div class="col-6 col-md-4 comparison-benefit text-start text-md-center">
                            <span class="icon icon-x">x</span> Not Included
                        </div>
                        <div class="col-6 col-md-4 comparison-benefit text-start text-md-center">
                            <span class="icon icon-check">✓</span> For the first 6 months, pay 0% per month if you don't get 20 orders <span class="info-icon">ⓘ</span>
                        </div>
                    </div>

                    <div class="row comparison-item">
                        <div class="col-12 col-md-4 comparison-label">
                            <span class="icon icon-x">x</span> <span class="fw-bold">Advertising Benefit:</span> Not Included
                        </div>
                        <div class="col-6 col-md-4 comparison-benefit text-start text-md-center">
                            <span class="icon icon-x">x</span> Not Included
                        </div>
                        <div class="col-6 col-md-4 comparison-benefit text-start text-md-center">
                            <span class="icon icon-check">✓</span> Receive $100 per month in ads spend matching per year (up to $1,200 per year)
                        </div>
                    </div>
                </div>
            </div>

            <div class="all-benefits shadow-sm">
                <h4 class="text-center fw-bold">Benefits across all plans</h4>
                <div class="all-benefits-list">
                    <div class="benefit-item">
                        <div class="icon">📄</div>
                        Reliable order processing
                    </div>
                    <div class="benefit-item">
                        <div class="icon">💳</div>
                        Zero credit card fees
                    </div>
                    <div class="benefit-item">
                        <div class="icon">📊</div>
                        Performance data and insights
                    </div>
                    <div class="benefit-item">
                        <div class="icon">📞</div>
                        24/7 support
                    </div>
                </div>
                <p class="mt-4 small text-muted text-center">
                    *Uber Eats reserves the right to modify plan benefits and service levels as needed, including but not limited to instances where maximum allowable fees are applicable in the Merchant's location or due to other applicable regulations.
                </p>
            </div>
        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    
    <script>
        // Plan selection - Manual click required
        function selectPlan(planType) {
            // Update hidden input
            document.getElementById('selectedPlan').value = planType;
            
            // Remove selected class from all cards
            document.querySelectorAll('.plan-card').forEach(card => {
                card.classList.remove('selected');
            });
            
            // Add selected class to clicked card
            document.getElementById('plan' + planType).classList.add('selected');
            
            // Submit the form immediately
            document.getElementById('planForm').submit();
        }
        
        // Delivery option buttons
        document.querySelectorAll('.toggle-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                document.querySelectorAll('.toggle-btn').forEach(b => b.classList.remove('active'));
                this.classList.add('active');
            });
        });
        
        // Form submission validation
        document.getElementById('planForm').addEventListener('submit', function(e) {
            const selectedPlan = document.getElementById('selectedPlan').value;
            if (!selectedPlan) {
                alert('Please select a plan');
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