<?php
session_start();
include "../includes/db.php";

// Initialize variables
$error = "";
$success = "";
$first_name = $last_name = $email = $phone = $store_address = $floor_suite = "";
$store_name = $brand_name = $business_type = $social_media = "";
$opt_in_sms = 0;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {
    // Get form data
    $first_name = mysqli_real_escape_string($conn, trim($_POST['first_name']));
    $last_name = mysqli_real_escape_string($conn, trim($_POST['last_name']));
    $email = mysqli_real_escape_string($conn, trim($_POST['email']));
    $phone = mysqli_real_escape_string($conn, trim($_POST['phone']));
    $store_address = mysqli_real_escape_string($conn, trim($_POST['store_address']));
    $floor_suite = mysqli_real_escape_string($conn, trim($_POST['floor_suite'] ?? ''));
    $store_name = mysqli_real_escape_string($conn, trim($_POST['store_name']));
    $brand_name = mysqli_real_escape_string($conn, trim($_POST['brand_name'] ?? ''));
    $business_type = mysqli_real_escape_string($conn, trim($_POST['business_type'] ?? ''));
    $social_media = mysqli_real_escape_string($conn, trim($_POST['social_media'] ?? ''));
    $opt_in_sms = isset($_POST['opt_in_sms']) ? 1 : 0;
    
    // Validation
    if (empty($first_name) || empty($last_name) || empty($email) || 
        empty($phone) || empty($store_address) || empty($store_name)) {
        $error = "Please fill in all required fields (*)";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address";
    } else {
        // Check if email already exists
        $check_sql = "SELECT id FROM users WHERE email = '$email'";
        $check_result = mysqli_query($conn, $check_sql);
        
        if (mysqli_num_rows($check_result) > 0) {
            $error = "Email already registered. Please login and add your restaurant.";
        } else {
            // Start transaction
            mysqli_begin_transaction($conn);
            
            try {
                // 1. Insert into users table WITHOUT password (will be set later)
                $user_sql = "INSERT INTO users (email, first_name, last_name, phone, user_type) 
                            VALUES ('$email', '$first_name', '$last_name', '$phone', 'merchant')";
                
                if (!mysqli_query($conn, $user_sql)) {
                    throw new Exception("User creation failed: " . mysqli_error($conn));
                }
                
                $user_id = mysqli_insert_id($conn);
                
                // 2. Insert into merchants table
                $merchant_sql = "INSERT INTO merchants (user_id, store_name, brand_name, business_type, 
                                store_address, floor_suite, mobile_phone, social_media_website, opt_in_sms) 
                                VALUES ('$user_id', '$store_name', '$brand_name', '$business_type', 
                                '$store_address', '$floor_suite', '$phone', '$social_media', '$opt_in_sms')";
                
                if (!mysqli_query($conn, $merchant_sql)) {
                    throw new Exception("Merchant creation failed: " . mysqli_error($conn));
                }
                
                $merchant_id = mysqli_insert_id($conn);
                
                // 3. Insert default plan (Lite)
                $plan_sql = "INSERT INTO merchant_plans (merchant_id, plan_type) VALUES ('$merchant_id', 'Lite')";
                
                if (!mysqli_query($conn, $plan_sql)) {
                    // If plan table doesn't exist, just log and continue
                    error_log("Plan creation skipped or failed: " . mysqli_error($conn));
                }
                
                // Commit transaction
                mysqli_commit($conn);
                
                // Store in session
                $_SESSION['user_id'] = $user_id;
                $_SESSION['merchant_id'] = $merchant_id;
                $_SESSION['store_name'] = $store_name;
                $_SESSION['store_address'] = $store_address;
                $_SESSION['email'] = $email;
                
                $success = "Information saved! Redirecting to plan selection...";
                
                // Redirect to agreement page
                header("Location: chooseplan.php");
                
            } catch (Exception $e) {
                // Rollback on error
                mysqli_rollback($conn);
                $error = "Registration failed: " . $e->getMessage();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Get Started - BeU Delivery for Merchants</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #000;
            --secondary-color: #f8f9fa;
            --accent-color: #00B14F;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background-color: #f5f5f5;
            color: #333;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        /* Header - BLACK */
        .main-header {
            background-color: #000;
            padding: 12px 0;
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .header-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .logo {
            font-size: 22px;
            font-weight: bold;
            color: white;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .logo-icon {
            color: var(--accent-color);
        }
        
        .header-links {
            display: flex;
            gap: 30px;
            align-items: center;
        }
        
        .header-link {
            color: rgba(255, 255, 255, 0.9);
            text-decoration: none;
            font-size: 14px;
            transition: color 0.2s;
        }
        
        .header-link:hover {
            color: white;
        }
        
        .btn-signin {
            background-color: white;
            color: black;
            border: none;
            padding: 8px 22px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .btn-signin:hover {
            background-color: #f0f0f0;
            transform: translateY(-1px);
        }
        
        /* Main Content */
        .main-content {
            display: flex;
            flex: 1;
            min-height: calc(100vh - 140px);
        }
        
        /* Left Side - Hero/Image */
        .hero-side {
            flex: 1;
            background-image: url(../public/images/feed.jpg);
            background-size: cover;
            background-position: center;
            color: white;
            padding: 60px 50px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            position: relative;
        }
        
        .hero-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, rgba(0,0,0,0.9) 0%, rgba(0,0,0,0.7) 100%);
        }
        
        .hero-content {
            position: relative;
            z-index: 1;
            max-width: 600px;
        }
        
        .hero-title {
            font-size: 42px;
            font-weight: bold;
            margin-bottom: 20px;
            line-height: 1.2;
        }
        
        .hero-subtitle {
            font-size: 18px;
            margin-bottom: 40px;
            color: rgba(255, 255, 255, 0.9);
            line-height: 1.6;
        }
        
        .benefits {
            margin-top: 50px;
        }
        
        .benefit-item {
            display: flex;
            align-items: flex-start;
            margin-bottom: 30px;
        }
        
        .benefit-icon {
            font-size: 22px;
            margin-right: 18px;
            color: var(--accent-color);
            min-width: 24px;
        }
        
        .benefit-text h4 {
            margin: 0 0 8px 0;
            font-size: 18px;
            font-weight: 600;
        }
        
        .benefit-text p {
            margin: 0;
            color: rgba(255, 255, 255, 0.8);
            font-size: 15px;
            line-height: 1.5;
        }
        
        /* Right Side - Form (Narrower) */
        .form-side {
            flex: 0.8; /* Make form side narrower */
            padding: 50px 0;
            background-color: #f5f5f5;
            display: flex;
            justify-content: flex-start; /* Align form to left to show body color on right */
            align-items: center;
            min-height: calc(100vh - 140px);
        }
        
        .form-container {
            width: 450px; /* Narrower form width */
            background: white;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            margin-left: 50px; /* Move form left to show background on right */
        }
        
        .form-header {
            margin-bottom: 30px;
        }
        
        .form-title {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 8px;
            color: var(--primary-color);
        }
        
        .form-subtitle {
            color: #666;
            font-size: 15px;
            line-height: 1.5;
        }
        
        .login-prompt {
            text-align: center;
            margin-bottom: 25px;
            color: #666;
            font-size: 14px;
            padding: 12px;
            background-color: #f8f9fa;
            border-radius: 8px;
        }
        
        .login-link {
            color: var(--accent-color);
            text-decoration: none;
            font-weight: 600;
        }
        
        .form-label {
            font-weight: 500;
            margin-bottom: 6px;
            display: block;
            color: #333;
            font-size: 14px;
        }
        
        .required:after {
            content: " *";
            color: #d00;
        }
        
        .form-control {
            padding: 14px 16px;
            border-radius: 8px;
            border: 1px solid #ddd;
            margin-bottom: 20px;
            font-size: 15px;
            width: 100%;
            transition: all 0.2s;
            background-color: #fafafa;
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(0,0,0,0.05);
            outline: none;
            background-color: white;
        }
        
        .phone-group {
            display: flex;
            align-items: center;
            border: 1px solid #ddd;
            border-radius: 8px;
            overflow: hidden;
            margin-bottom: 20px;
            background-color: #fafafa;
        }
        
        .country-code {
            padding: 14px 16px;
            background-color: #f0f0f0;
            border-right: 1px solid #ddd;
            font-weight: 500;
            color: #333;
            font-size: 15px;
            min-width: 70px;
        }
        
        .phone-input {
            flex: 1;
            border: none;
            padding: 14px 16px;
            font-size: 15px;
            background-color: #fafafa;
        }
        
        .phone-input:focus {
            outline: none;
            background-color: white;
        }
        
        .checkbox-container {
            display: flex;
            align-items: center;
            margin-bottom: 25px;
            padding: 10px;
            background-color: #f8f9fa;
            border-radius: 8px;
        }
        
        .checkbox-container input {
            margin-right: 12px;
            width: 18px;
            height: 18px;
            cursor: pointer;
        }
        
        .checkbox-label {
            font-size: 14px;
            color: #333;
            cursor: pointer;
            line-height: 1.4;
        }
        
        .btn-submit {
            background-color: var(--primary-color);
            color: white;
            border: none;
            padding: 16px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 16px;
            cursor: pointer;
            width: 100%;
            transition: all 0.2s;
            margin-top: 10px;
            letter-spacing: 0.5px;
        }
        
        .btn-submit:hover {
            background-color: #333;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        
        /* Footer */
        .main-footer {
            background-color: white;
            padding: 40px 0 30px;
            border-top: 1px solid #eaeaea;
            margin-top: auto;
        }
        
        .footer-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 40px;
        }
        
        .footer-columns {
            display: flex;
            justify-content: space-between;
            flex-wrap: wrap;
            margin-bottom: 40px;
        }
        
        .footer-column {
            flex: 1;
            min-width: 200px;
            margin-bottom: 30px;
        }
        
        .footer-heading {
            font-weight: 600;
            margin-bottom: 20px;
            font-size: 16px;
            color: #333;
        }
        
        .footer-links {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .footer-links li {
            margin-bottom: 10px;
        }
        
        .footer-links a {
            color: #666;
            text-decoration: none;
            font-size: 14px;
            transition: color 0.2s;
        }
        
        .footer-links a:hover {
            color: var(--primary-color);
        }
        
        .footer-bottom {
            text-align: center;
            padding-top: 30px;
            border-top: 1px solid #eaeaea;
            color: #999;
            font-size: 14px;
        }
        
        /* Messages */
        .alert {
            padding: 14px 16px;
            border-radius: 8px;
            margin-bottom: 25px;
            font-size: 14px;
            line-height: 1.5;
        }
        
        .alert-danger {
            background-color: rgba(220, 53, 69, 0.08);
            color: #dc3545;
            border: 1px solid rgba(220, 53, 69, 0.2);
        }
        
        .alert-success {
            background-color: rgba(40, 167, 69, 0.08);
            color: #28a745;
            border: 1px solid rgba(40, 167, 69, 0.2);
        }
        
        /* Responsive */
        @media (max-width: 1100px) {
            .form-container {
                width: 400px;
                margin-left: 30px;
            }
        }
        
        @media (max-width: 992px) {
            .main-content {
                flex-direction: column;
            }
            
            .hero-side, .form-side {
                padding: 40px 30px;
            }
            
            .form-side {
                justify-content: center;
            }
            
            .form-container {
                width: 100%;
                max-width: 500px;
                margin: 0 auto;
            }
            
            .hero-title {
                font-size: 36px;
            }
            
            .header-container {
                padding: 0 20px;
            }
        }
        
        @media (max-width: 768px) {
            .hero-title {
                font-size: 30px;
            }
            
            .form-title {
                font-size: 22px;
            }
            
            .form-container {
                padding: 30px;
            }
            
            .footer-columns {
                flex-direction: column;
                gap: 25px;
            }
            
            .header-links {
                gap: 15px;
            }
            
            .header-container {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }
        }
        
        @media (max-width: 480px) {
            .hero-side, .form-side {
                padding: 30px 20px;
            }
            
            .form-container {
                padding: 25px;
            }
            
            .form-title {
                font-size: 20px;
            }
            
            .hero-title {
                font-size: 26px;
            }
        }
    </style>
</head>
<body>
    <!-- Header - BLACK -->
    <header class="main-header">
        <div class="header-container">
            <a href="/" class="logo">
                <i class="fas fa-utensils logo-icon"></i>
                BeU Delivery
            </a>
            <div class="header-links">
                <a href="#" class="header-link">Merchant Resources</a>
                <a href="#" class="header-link">Pricing</a>
                <a href="#" class="header-link">FAQ</a>
                <button class="btn-signin">Sign In</button>
            </div>
        </div>
    </header>
    
    <!-- Main Content -->
    <div class="main-content">
        <!-- Left Side: Hero/Image with Benefits -->
        <div class="hero-side">
            <div class="hero-overlay"></div>
            <div class="hero-content">
                <h1 class="hero-title">Contact with millions of customers through the #1 on-demand delivery app</h1>
                <p class="hero-subtitle">Fetch a more customer, see if there are get the tools you can use.</p>
                
                <div class="benefits">
                    <div class="benefit-item">
                        <div class="benefit-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="benefit-text">
                            <h4>Access new customers</h4>
                            <p>Get discovered by people actively searching for food, groceries, and retail items.</p>
                        </div>
                    </div>
                    
                    <div class="benefit-item">
                        <div class="benefit-icon">
                            <i class="fas fa-shipping-fast"></i>
                        </div>
                        <div class="benefit-text">
                            <h4>Seamless delivery network</h4>
                            <p>Leverage Uber's extensive network of couriers for fast and reliable order fulfillment.</p>
                        </div>
                    </div>
                    
                    <div class="benefit-item">
                        <div class="benefit-icon">
                            <i class="fas fa-calendar-alt"></i>
                        </div>
                        <div class="benefit-text">
                            <h4>Flexible choices</h4>
                            <p>Offer on-demand delivery, pickup, and scheduled orders.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Right Side: Form (Narrower) -->
        <div class="form-side">
            <div class="form-container">
                <div class="form-header">
                    <h1 class="form-title">Get started</h1>
                    <p class="form-subtitle">Fill in your information to start selling on BeU Delivery</p>
                </div>
                
                <div class="login-prompt">
                    Already have an account? <a href="login.php" class="login-link">Log in and add your restaurant</a>
                </div>
                
                <!-- Messages -->
                <?php if ($error): ?>
                    <div class="alert alert-danger">
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <?php echo htmlspecialchars($success); ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="" onsubmit="return validateForm()">
                    <input type="hidden" name="submit" value="1">
                    
                    <!-- Name -->
                    <div class="row">
                        <div class="col-md-6">
                            <label class="form-label required">First name</label>
                            <input type="text" name="first_name" class="form-control" 
                                   value="<?php echo htmlspecialchars($first_name); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label required">Last name</label>
                            <input type="text" name="last_name" class="form-control" 
                                   value="<?php echo htmlspecialchars($last_name); ?>" required>
                        </div>
                    </div>
                    
                    <!-- Email -->
                    <label class="form-label required">Email</label>
                    <input type="email" name="email" class="form-control" 
                           value="<?php echo htmlspecialchars($email); ?>" required>
                    
                    <!-- Phone -->
                    <label class="form-label required">Mobile phone number</label>
                    <div class="phone-group">
                        <div class="country-code">+251</div>
                        <input type="tel" name="phone" class="phone-input" 
                               value="<?php echo htmlspecialchars($phone); ?>" required 
                               pattern="[0-9]{9,10}" placeholder="912345678" maxlength="10">
                    </div>
                    <small style="color: #666; font-size: 12px; display: block; margin-top: -15px; margin-bottom: 20px;">
                        Enter 9-10 digits without country code
                    </small>
                    
                    <!-- Store Address -->
                    <label class="form-label required">Store address</label>
                    <input type="text" name="store_address" class="form-control" 
                           value="<?php echo htmlspecialchars($store_address); ?>" required>
                    
                    <!-- Floor/Suite -->
                    <label class="form-label">Floor / Suite (Optional)</label>
                    <input type="text" name="floor_suite" class="form-control" 
                           value="<?php echo htmlspecialchars($floor_suite); ?>">
                    
                    <!-- Store Name -->
                    <label class="form-label required">Store name</label>
                    <input type="text" name="store_name" class="form-control" 
                           value="<?php echo htmlspecialchars($store_name); ?>" required>
                    
                    <!-- Brand Name -->
                    <label class="form-label">Brand name</label>
                    <input type="text" name="brand_name" class="form-control" 
                           value="<?php echo htmlspecialchars($brand_name); ?>">
                    
                    <!-- Business Type -->
                    <label class="form-label">Business type</label>
                    <select name="business_type" class="form-control">
                        <option value="">Select business type</option>
                        <option value="Restaurant" <?php echo ($business_type == 'Restaurant') ? 'selected' : ''; ?>>Restaurant</option>
                        <option value="Retail" <?php echo ($business_type == 'Retail') ? 'selected' : ''; ?>>Retail</option>
                        <option value="Grocery" <?php echo ($business_type == 'Grocery') ? 'selected' : ''; ?>>Grocery</option>
                        <option value="Other" <?php echo ($business_type == 'Other') ? 'selected' : ''; ?>>Other</option>
                    </select>
                    
                    <!-- Social Media -->
                    <label class="form-label">Social Media / Website (Optional)</label>
                    <input type="url" name="social_media" class="form-control" 
                           value="<?php echo htmlspecialchars($social_media); ?>" 
                           placeholder="https://example.com">
                    
                    <!-- Opt-in -->
                    <div class="checkbox-container">
                        <input type="checkbox" name="opt_in_sms" id="opt_in_sms" value="1" 
                               <?php echo $opt_in_sms ? 'checked' : ''; ?>>
                        <label for="opt_in_sms" class="checkbox-label">
                            Opt in to SMS text messages
                        </label>
                    </div>
                    
                    <!-- Submit -->
                    <button type="submit" class="btn-submit">Submit & Start Selling</button>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Footer -->
    <footer class="main-footer">
        <div class="footer-container">
            <div class="footer-columns">
                <div class="footer-column">
                    <div class="footer-heading">Solutions</div>
                    <ul class="footer-links">
                        <li><a href="#">Delivery</a></li>
                        <li><a href="#">Pickup</a></li>
                        <li><a href="#">Scheduled orders</a></li>
                    </ul>
                </div>
                
                <div class="footer-column">
                    <div class="footer-heading">Business types</div>
                    <ul class="footer-links">
                        <li><a href="#">Restaurants</a></li>
                        <li><a href="#">Retail</a></li>
                        <li><a href="#">Grocery</a></li>
                    </ul>
                </div>
                
                <div class="footer-column">
                    <div class="footer-heading">More options</div>
                    <ul class="footer-links">
                        <li><a href="#">FAQ</a></li>
                        <li><a href="#">Gift cards</a></li>
                        <li><a href="#">Merchant resources</a></li>
                    </ul>
                </div>
                
                <div class="footer-column">
                    <div class="footer-heading">Company</div>
                    <ul class="footer-links">
                        <li><a href="#">About us</a></li>
                        <li><a href="#">Careers</a></li>
                        <li><a href="#">Contact</a></li>
                    </ul>
                </div>
            </div>
            
            <div class="footer-bottom">
                <p>&copy; 2025 BeU Technologies Inc. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    
    <script>
        // Form validation
        function validateForm() {
            // Check required fields
            const requiredFields = document.querySelectorAll('[required]');
            for (let field of requiredFields) {
                if (!field.value.trim()) {
                    alert('Please fill in all required fields');
                    field.focus();
                    return false;
                }
            }
            
            // Email validation
            const email = document.querySelector('input[name="email"]');
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email.value)) {
                alert('Please enter a valid email address');
                email.focus();
                return false;
            }
            
            // Phone validation
            const phone = document.querySelector('input[name="phone"]');
            const phoneRegex = /^[0-9]{9,10}$/;
            if (!phoneRegex.test(phone.value.replace(/\D/g, ''))) {
                alert('Please enter a valid phone number (9-10 digits)');
                phone.focus();
                return false;
            }
            
            return true;
        }
        
        // Auto-format phone number (remove non-digits)
        document.querySelector('input[name="phone"]').addEventListener('input', function(e) {
            this.value = this.value.replace(/\D/g, '');
            if (this.value.length > 10) {
                this.value = this.value.substring(0, 10);
            }
        });
        
        // Prevent form resubmission on page refresh
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }
    </script>
</body>
</html>