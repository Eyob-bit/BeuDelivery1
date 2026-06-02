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

// Check if banking info already exists
$banking_sql = "SELECT * FROM merchant_banking WHERE merchant_id = '$merchant_id'";
$banking_result = mysqli_query($conn, $banking_sql);
$existing_banking = mysqli_fetch_assoc($banking_result);

// Handle form submission
$error = "";
$success = "";
$banking_submitted = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_banking'])) {
    // Get form data
    $account_holder_name = mysqli_real_escape_string($conn, trim($_POST['account_holder_name']));
    $routing_number = mysqli_real_escape_string($conn, trim($_POST['routing_number']));
    $account_number = mysqli_real_escape_string($conn, trim($_POST['account_number']));
    $confirm_account_number = mysqli_real_escape_string($conn, trim($_POST['confirm_account_number']));
    $business_legal_entity_name = mysqli_real_escape_string($conn, trim($_POST['business_legal_entity_name']));
    $company_mailing_address = mysqli_real_escape_string($conn, trim($_POST['company_mailing_address']));
    $city = mysqli_real_escape_string($conn, trim($_POST['city']));
    $state = mysqli_real_escape_string($conn, $_POST['state']);
    $postal_code = mysqli_real_escape_string($conn, trim($_POST['postal_code']));
    
    // Validation
    $errors = [];
    
    // Required fields
    if (empty($account_holder_name)) {
        $errors[] = "Account holder name is required";
    }
    
    if (empty($routing_number)) {
        $errors[] = "Routing number is required";
    } elseif (!preg_match('/^[0-9]{9}$/', $routing_number)) {
        $errors[] = "Routing number must be 9 digits";
    }
    
    if (empty($account_number)) {
        $errors[] = "Account number is required";
    } elseif (!preg_match('/^[0-9]{4,17}$/', $account_number)) {
        $errors[] = "Account number must be 4-17 digits";
    }
    
    if ($account_number !== $confirm_account_number) {
        $errors[] = "Account numbers do not match";
    }
    
    if (empty($business_legal_entity_name)) {
        $errors[] = "Business legal entity name is required";
    }
    
    if (empty($company_mailing_address)) {
        $errors[] = "Company mailing address is required";
    }
    
    if (empty($city)) {
        $errors[] = "City is required";
    }
    
    if (empty($state)) {
        $errors[] = "State is required";
    }
    
    if (empty($postal_code)) {
        $errors[] = "Postal code is required";
    } elseif (!preg_match('/^[0-9]{5}(?:-[0-9]{4})?$/', $postal_code)) {
        $errors[] = "Please enter a valid ZIP code (5 digits or 5+4 format)";
    }
    
    // If no errors, save to database
    if (empty($errors)) {
        // Encrypt sensitive data
        $encrypted_account_number = base64_encode($account_number);
        $encrypted_routing_number = base64_encode($routing_number);
        
        mysqli_begin_transaction($conn);
        
        try {
            if ($existing_banking) {
                // Update existing banking info
                $update_sql = "UPDATE merchant_banking SET 
                              account_holder_name = '$account_holder_name',
                              bank_name = 'Bank',
                              routing_number = '$encrypted_routing_number',
                              account_number = '$encrypted_account_number',
                              business_legal_entity_name = '$business_legal_entity_name',
                              company_mailing_address = '$company_mailing_address',
                              city = '$city',
                              state = '$state',
                              postal_code = '$postal_code',
                              verified = 0,
                              updated_at = NOW()
                              WHERE merchant_id = '$merchant_id'";
            } else {
                // Insert new banking info
                $update_sql = "INSERT INTO merchant_banking 
                              (merchant_id, account_holder_name, bank_name, routing_number, account_number, 
                               business_legal_entity_name, company_mailing_address, city, 
                               state, postal_code, verified, created_at)
                              VALUES ('$merchant_id', '$account_holder_name', 'Bank', '$encrypted_routing_number', 
                              '$encrypted_account_number', '$business_legal_entity_name', 
                              '$company_mailing_address', '$city', '$state', '$postal_code', 
                              0, NOW())";
            }
            
            if (!mysqli_query($conn, $update_sql)) {
                throw new Exception("Failed to save banking info: " . mysqli_error($conn));
            }
            
            // Update progress in session
            $_SESSION['banking_setup'] = true;
            
            // Commit transaction
            mysqli_commit($conn);
            
            $success = "Banking information saved successfully! Redirecting back to setup...";
            $banking_submitted = true;
            
            // Redirect back to setup.php after 2 seconds
            header("refresh:2;url=setup.php");
            
        } catch (Exception $e) {
            // Rollback on error
            mysqli_rollback($conn);
            $error = "Failed to save: " . $e->getMessage();
        }
    } else {
        $error = implode("<br>", $errors);
    }
}

// Pre-fill form data
$account_holder_name = $existing_banking['account_holder_name'] ?? ($merchant['first_name'] . ' ' . $merchant['last_name']);
$business_legal_entity_name = $existing_banking['business_legal_entity_name'] ?? $merchant['store_name'];
$company_mailing_address = $existing_banking['company_mailing_address'] ?? $merchant['store_address'];
$city = $existing_banking['city'] ?? '';
$state = $existing_banking['state'] ?? 'NC';
$postal_code = $existing_banking['postal_code'] ?? '';

// Note: We don't pre-fill account numbers for security
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Set up payment - Add your business banking info - BeU Delivery</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <style>
        /* [Keep all CSS styles from previous version] */
        /* General styling */
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
        
        .form-container {
            max-width: 500px;
            margin: 0 auto;
        }

        /* Header and Back Arrow */
        .back-arrow {
            font-size: 1.5rem;
            color: #333;
            text-decoration: none;
            display: block;
            margin-bottom: 15px;
        }

        /* Main Form Title and Subtitle */
        .form-title {
            font-size: 1.5rem;
            font-weight: bold;
            margin-bottom: 10px;
        }
        
        .form-subtitle {
            font-size: 0.95rem;
            color: #6c757d;
            margin-bottom: 30px;
        }

        /* Form Group Styling */
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-label {
            font-weight: bold;
            font-size: 0.95rem;
            margin-bottom: 5px;
            color: black;
            display: block;
        }
        
        .form-control-custom {
            background-color: #f7f7f7;
            border: 1px solid #ddd;
            border-radius: 6px;
            padding: 10px 15px;
            width: 100%;
            font-size: 0.95rem;
            transition: all 0.3s;
        }
        
        .form-control-custom:focus {
            outline: none;
            box-shadow: 0 0 0 2px rgba(0, 0, 0, 0.1);
            background-color: white;
            border-color: black;
        }
        
        .form-control-custom.error {
            border-color: #dc3545;
            background-color: #fff8f8;
        }

        .input-description {
            font-size: 0.8rem;
            color: #6c757d;
            margin-top: 5px;
            display: block;
        }
        
        .error-message {
            color: #dc3545;
            font-size: 0.8rem;
            margin-top: 5px;
            display: block;
        }

        /* Separator and Bold Headers */
        .section-header {
            font-size: 1.1rem;
            font-weight: bold;
            margin-top: 30px;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }

        /* Security Notice */
        .security-notice {
            background-color: #f8f9fa;
            border-left: 4px solid #007bff;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
            font-size: 0.9rem;
        }
        
        .security-notice i {
            color: #007bff;
            margin-right: 8px;
        }

        /* Footer Buttons */
        .footer-buttons {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 50px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }

        .btn-cancel {
            background-color: white;
            color: black;
            border: 1px solid #ccc;
            padding: 10px 25px;
            border-radius: 6px;
            font-weight: bold;
            text-decoration: none;
            display: inline-block;
            text-align: center;
            transition: all 0.2s;
        }
        
        .btn-cancel:hover {
            background-color: #f8f9fa;
            text-decoration: none;
            color: black;
        }

        .btn-submit {
            background-color: black;
            color: white;
            padding: 10px 25px;
            border: none;
            border-radius: 6px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .btn-submit:hover {
            background-color: #333;
        }
        
        .btn-submit:disabled {
            background-color: #ccc;
            color: #666;
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
        
        /* Existing info notice */
        .existing-info {
            background-color: #e8f4f8;
            border: 1px solid #b6e0fe;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 25px;
            font-size: 0.9rem;
        }
        
        .existing-info i {
            color: #0066cc;
            margin-right: 8px;
        }
        
        @media (max-width: 768px) {
            .page-content {
                padding: 15px;
            }
            
            .form-container {
                max-width: 100%;
            }
            
            .footer-buttons {
                flex-direction: column;
            }
            
            .btn-cancel, .btn-submit {
                width: 100%;
                text-align: center;
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

        <!-- Messages -->
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo nl2br(htmlspecialchars($error)); ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <div class="form-container">
            <a href="setup.php" class="back-arrow">&larr;</a>
            <h1 class="form-title">Add your business banking info</h1>
            <p class="form-subtitle">Add the bank account associated to your business to receive weekly payouts. Your banking information is secure.</p>

            <?php if ($existing_banking): ?>
            <div class="existing-info">
                <i class="bi bi-info-circle-fill"></i>
                <strong>Banking information already saved</strong>
                <div class="mt-2 small">
                    Account holder: <?php echo htmlspecialchars($existing_banking['account_holder_name']); ?><br>
                    Last updated: <?php echo date('M j, Y', strtotime($existing_banking['created_at'])); ?>
                </div>
            </div>
            <?php endif; ?>

            <div class="security-notice">
                <i class="bi bi-shield-lock"></i>
                <strong>Security Notice:</strong> Your banking information is encrypted and stored securely. 
                We never display full account numbers for your protection.
            </div>

            <form method="POST" action="" id="bankingForm">
                <input type="hidden" name="submit_banking" value="1">
                
                <div class="form-group">
                    <label for="account_holder_name" class="form-label">Name of Account Holder</label>
                    <input type="text" class="form-control-custom" id="account_holder_name" 
                           name="account_holder_name" value="<?php echo htmlspecialchars($account_holder_name); ?>" 
                           required aria-describedby="accountHolderHelp">
                    <small id="accountHolderHelp" class="input-description">
                        Please enter here the legal name of the business. If the business is set up as an individual sole proprietorship, enter the first and last name.
                    </small>
                    <span class="error-message" id="account_holder_name_error"></span>
                </div>

                <div class="form-group">
                    <label for="routing_number" class="form-label">Routing Number</label>
                    <input type="text" class="form-control-custom" id="routing_number" 
                           name="routing_number" maxlength="9" 
                           placeholder="123456789" required 
                           aria-describedby="routingHelp" 
                           oninput="formatRoutingNumber(this)">
                    <small id="routingHelp" class="input-description">
                        If you have an electronic routing number that differs from your check, please use that instead.
                    </small>
                    <span class="error-message" id="routing_number_error"></span>
                </div>

                <div class="form-group">
                    <label for="account_number" class="form-label">Bank Account Number</label>
                    <input type="text" class="form-control-custom" id="account_number" 
                           name="account_number" required 
                           oninput="validateAccountNumber(this)">
                    <span class="error-message" id="account_number_error"></span>
                </div>
                
                <div class="form-group">
                    <label for="confirm_account_number" class="form-label">Re-enter account number</label>
                    <input type="text" class="form-control-custom" id="confirm_account_number" 
                           name="confirm_account_number" required 
                           oninput="validateAccountMatch()">
                    <span class="error-message" id="confirm_account_number_error"></span>
                </div>
                
                <div class="section-header">Company Mailing Address</div>

                <div class="form-group">
                    <label for="business_legal_entity_name" class="form-label">Business Legal Entity Name</label>
                    <input type="text" class="form-control-custom" id="business_legal_entity_name" 
                           name="business_legal_entity_name" value="<?php echo htmlspecialchars($business_legal_entity_name); ?>" 
                           required aria-describedby="entityHelp">
                    <small id="entityHelp" class="input-description">
                        The Legal entity name of your business including abbreviation, if applicable (e.g. LLC, Corp, Inc.) If the business is set up as an individual Sole Proprietorship, enter the first and the last name of the Sole Proprietor.
                    </small>
                    <span class="error-message" id="business_legal_entity_name_error"></span>
                </div>

                <div class="form-group">
                    <label for="company_mailing_address" class="form-label">Address</label>
                    <input type="text" class="form-control-custom" id="company_mailing_address" 
                           name="company_mailing_address" value="<?php echo htmlspecialchars($company_mailing_address); ?>" required>
                    <span class="error-message" id="company_mailing_address_error"></span>
                </div>

                <div class="form-group">
                    <label for="city" class="form-label">City</label>
                    <input type="text" class="form-control-custom" id="city" 
                           name="city" value="<?php echo htmlspecialchars($city); ?>" required>
                    <span class="error-message" id="city_error"></span>
                </div>

                <div class="form-group">
                    <label for="state" class="form-label">State</label>
                    <select class="form-control-custom" id="state" name="state" required>
                        <option value="">Select State</option>
                        <option value="AL" <?php echo $state == 'AL' ? 'selected' : ''; ?>>Alabama</option>
                        <option value="AK" <?php echo $state == 'AK' ? 'selected' : ''; ?>>Alaska</option>
                        <option value="AZ" <?php echo $state == 'AZ' ? 'selected' : ''; ?>>Arizona</option>
                        <option value="AR" <?php echo $state == 'AR' ? 'selected' : ''; ?>>Arkansas</option>
                        <option value="CA" <?php echo $state == 'CA' ? 'selected' : ''; ?>>California</option>
                        <option value="CO" <?php echo $state == 'CO' ? 'selected' : ''; ?>>Colorado</option>
                        <option value="CT" <?php echo $state == 'CT' ? 'selected' : ''; ?>>Connecticut</option>
                        <option value="DE" <?php echo $state == 'DE' ? 'selected' : ''; ?>>Delaware</option>
                        <option value="FL" <?php echo $state == 'FL' ? 'selected' : ''; ?>>Florida</option>
                        <option value="GA" <?php echo $state == 'GA' ? 'selected' : ''; ?>>Georgia</option>
                        <option value="HI" <?php echo $state == 'HI' ? 'selected' : ''; ?>>Hawaii</option>
                        <option value="ID" <?php echo $state == 'ID' ? 'selected' : ''; ?>>Idaho</option>
                        <option value="IL" <?php echo $state == 'IL' ? 'selected' : ''; ?>>Illinois</option>
                        <option value="IN" <?php echo $state == 'IN' ? 'selected' : ''; ?>>Indiana</option>
                        <option value="IA" <?php echo $state == 'IA' ? 'selected' : ''; ?>>Iowa</option>
                        <option value="KS" <?php echo $state == 'KS' ? 'selected' : ''; ?>>Kansas</option>
                        <option value="KY" <?php echo $state == 'KY' ? 'selected' : ''; ?>>Kentucky</option>
                        <option value="LA" <?php echo $state == 'LA' ? 'selected' : ''; ?>>Louisiana</option>
                        <option value="ME" <?php echo $state == 'ME' ? 'selected' : ''; ?>>Maine</option>
                        <option value="MD" <?php echo $state == 'MD' ? 'selected' : ''; ?>>Maryland</option>
                        <option value="MA" <?php echo $state == 'MA' ? 'selected' : ''; ?>>Massachusetts</option>
                        <option value="MI" <?php echo $state == 'MI' ? 'selected' : ''; ?>>Michigan</option>
                        <option value="MN" <?php echo $state == 'MN' ? 'selected' : ''; ?>>Minnesota</option>
                        <option value="MS" <?php echo $state == 'MS' ? 'selected' : ''; ?>>Mississippi</option>
                        <option value="MO" <?php echo $state == 'MO' ? 'selected' : ''; ?>>Missouri</option>
                        <option value="MT" <?php echo $state == 'MT' ? 'selected' : ''; ?>>Montana</option>
                        <option value="NE" <?php echo $state == 'NE' ? 'selected' : ''; ?>>Nebraska</option>
                        <option value="NV" <?php echo $state == 'NV' ? 'selected' : ''; ?>>Nevada</option>
                        <option value="NH" <?php echo $state == 'NH' ? 'selected' : ''; ?>>New Hampshire</option>
                        <option value="NJ" <?php echo $state == 'NJ' ? 'selected' : ''; ?>>New Jersey</option>
                        <option value="NM" <?php echo $state == 'NM' ? 'selected' : ''; ?>>New Mexico</option>
                        <option value="NY" <?php echo $state == 'NY' ? 'selected' : ''; ?>>New York</option>
                        <option value="NC" <?php echo $state == 'NC' ? 'selected' : ''; ?>>North Carolina</option>
                        <option value="ND" <?php echo $state == 'ND' ? 'selected' : ''; ?>>North Dakota</option>
                        <option value="OH" <?php echo $state == 'OH' ? 'selected' : ''; ?>>Ohio</option>
                        <option value="OK" <?php echo $state == 'OK' ? 'selected' : ''; ?>>Oklahoma</option>
                        <option value="OR" <?php echo $state == 'OR' ? 'selected' : ''; ?>>Oregon</option>
                        <option value="PA" <?php echo $state == 'PA' ? 'selected' : ''; ?>>Pennsylvania</option>
                        <option value="RI" <?php echo $state == 'RI' ? 'selected' : ''; ?>>Rhode Island</option>
                        <option value="SC" <?php echo $state == 'SC' ? 'selected' : ''; ?>>South Carolina</option>
                        <option value="SD" <?php echo $state == 'SD' ? 'selected' : ''; ?>>South Dakota</option>
                        <option value="TN" <?php echo $state == 'TN' ? 'selected' : ''; ?>>Tennessee</option>
                        <option value="TX" <?php echo $state == 'TX' ? 'selected' : ''; ?>>Texas</option>
                        <option value="UT" <?php echo $state == 'UT' ? 'selected' : ''; ?>>Utah</option>
                        <option value="VT" <?php echo $state == 'VT' ? 'selected' : ''; ?>>Vermont</option>
                        <option value="VA" <?php echo $state == 'VA' ? 'selected' : ''; ?>>Virginia</option>
                        <option value="WA" <?php echo $state == 'WA' ? 'selected' : ''; ?>>Washington</option>
                        <option value="WV" <?php echo $state == 'WV' ? 'selected' : ''; ?>>West Virginia</option>
                        <option value="WI" <?php echo $state == 'WI' ? 'selected' : ''; ?>>Wisconsin</option>
                        <option value="WY" <?php echo $state == 'WY' ? 'selected' : ''; ?>>Wyoming</option>
                        <option value="DC" <?php echo $state == 'DC' ? 'selected' : ''; ?>>District of Columbia</option>
                    </select>
                    <span class="error-message" id="state_error"></span>
                </div>

                <div class="form-group">
                    <label for="postal_code" class="form-label">Postal Code</label>
                    <input type="text" class="form-control-custom" id="postal_code" 
                           name="postal_code" value="<?php echo htmlspecialchars($postal_code); ?>" 
                           required maxlength="10" 
                           oninput="formatPostalCode(this)">
                    <span class="error-message" id="postal_code_error"></span>
                </div>

                <div class="footer-buttons">
                    <a href="setup.php" class="btn btn-cancel">Cancel</a>
                    <button type="submit" class="btn btn-submit" id="submitBtn" 
                            <?php echo $banking_submitted ? 'disabled' : ''; ?>>
                        <?php echo $existing_banking ? 'Update Banking Info' : 'Submit'; ?>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    
    <script>
        // Format routing number (digits only)
        function formatRoutingNumber(input) {
            input.value = input.value.replace(/\D/g, '');
            if (input.value.length > 9) {
                input.value = input.value.substring(0, 9);
            }
            
            validateRoutingNumber();
            validateForm();
        }
        
        // Validate account number (digits only)
        function validateAccountNumber(input) {
            input.value = input.value.replace(/\D/g, '');
            if (input.value.length > 17) {
                input.value = input.value.substring(0, 17);
            }
            
            const errorElement = document.getElementById('account_number_error');
            if (input.value.length < 4) {
                errorElement.textContent = 'Account number must be at least 4 digits';
                input.classList.add('error');
            } else if (input.value.length > 17) {
                errorElement.textContent = 'Account number cannot exceed 17 digits';
                input.classList.add('error');
            } else {
                errorElement.textContent = '';
                input.classList.remove('error');
            }
            
            validateAccountMatch();
            validateForm();
        }
        
        // Validate account numbers match
        function validateAccountMatch() {
            const accountNumber = document.getElementById('account_number').value;
            const confirmNumber = document.getElementById('confirm_account_number').value;
            const errorElement = document.getElementById('confirm_account_number_error');
            
            if (confirmNumber && accountNumber !== confirmNumber) {
                errorElement.textContent = 'Account numbers do not match';
                document.getElementById('confirm_account_number').classList.add('error');
            } else {
                errorElement.textContent = '';
                document.getElementById('confirm_account_number').classList.remove('error');
            }
            
            validateForm();
        }
        
        // Validate routing number
        function validateRoutingNumber() {
            const routingNumber = document.getElementById('routing_number').value;
            const errorElement = document.getElementById('routing_number_error');
            
            if (routingNumber.length !== 9) {
                errorElement.textContent = 'Routing number must be exactly 9 digits';
                document.getElementById('routing_number').classList.add('error');
            } else {
                errorElement.textContent = '';
                document.getElementById('routing_number').classList.remove('error');
            }
            
            validateForm();
        }
        
        // Format postal code
        function formatPostalCode(input) {
            let value = input.value.replace(/[^0-9-]/g, '');
            
            // Remove any existing hyphens
            value = value.replace(/-/g, '');
            
            // Add hyphen after 5 digits if more than 5 digits
            if (value.length > 5) {
                value = value.substring(0, 5) + '-' + value.substring(5, 9);
            }
            
            input.value = value;
            validatePostalCode();
            validateForm();
        }
        
        // Validate postal code
        function validatePostalCode() {
            const postalCode = document.getElementById('postal_code').value;
            const errorElement = document.getElementById('postal_code_error');
            const zipRegex = /^[0-9]{5}(?:-[0-9]{4})?$/;
            
            if (!zipRegex.test(postalCode)) {
                errorElement.textContent = 'Please enter a valid ZIP code (5 digits or 5+4 format)';
                document.getElementById('postal_code').classList.add('error');
            } else {
                errorElement.textContent = '';
                document.getElementById('postal_code').classList.remove('error');
            }
            
            validateForm();
        }
        
        // Real-time validation for all fields
        function validateField(fieldId, validationFn) {
            const field = document.getElementById(fieldId);
            if (field) {
                field.addEventListener('input', validationFn);
                field.addEventListener('blur', validationFn);
            }
        }
        
        // Validate entire form
        function validateForm() {
            const submitBtn = document.getElementById('submitBtn');
            let isValid = true;
            
            // Check all required fields
            const requiredFields = [
                'account_holder_name',
                'routing_number',
                'account_number',
                'confirm_account_number',
                'business_legal_entity_name',
                'company_mailing_address',
                'city',
                'state',
                'postal_code'
            ];
            
            requiredFields.forEach(fieldId => {
                const field = document.getElementById(fieldId);
                if (field && !field.value.trim()) {
                    isValid = false;
                }
            });
            
            // Check specific validations
            const routingNumber = document.getElementById('routing_number').value;
            if (routingNumber.length !== 9) {
                isValid = false;
            }
            
            const accountNumber = document.getElementById('account_number').value;
            const confirmNumber = document.getElementById('confirm_account_number').value;
            if (accountNumber.length < 4 || accountNumber !== confirmNumber) {
                isValid = false;
            }
            
            const postalCode = document.getElementById('postal_code').value;
            const zipRegex = /^[0-9]{5}(?:-[0-9]{4})?$/;
            if (!zipRegex.test(postalCode)) {
                isValid = false;
            }
            
            // Enable/disable submit button
            submitBtn.disabled = !isValid;
            
            return isValid;
        }
        
        // Form submission validation
        document.getElementById('bankingForm').addEventListener('submit', function(e) {
            if (!validateForm()) {
                e.preventDefault();
                alert('Please fix all errors before submitting.');
                return false;
            }
            
            // Show loading state
            const submitBtn = document.getElementById('submitBtn');
            const originalText = submitBtn.textContent;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span> Saving...';
            submitBtn.disabled = true;
            
            // Re-enable after 10 seconds if something goes wrong
            setTimeout(() => {
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            }, 10000);
            
            return true;
        });
        
        // Initialize validations
        document.addEventListener('DOMContentLoaded', function() {
            // Set up field validations
            validateField('account_holder_name', () => {
                const field = document.getElementById('account_holder_name');
                const errorElement = document.getElementById('account_holder_name_error');
                if (!field.value.trim()) {
                    errorElement.textContent = 'Account holder name is required';
                    field.classList.add('error');
                } else {
                    errorElement.textContent = '';
                    field.classList.remove('error');
                }
                validateForm();
            });
            
            validateField('business_legal_entity_name', () => {
                const field = document.getElementById('business_legal_entity_name');
                const errorElement = document.getElementById('business_legal_entity_name_error');
                if (!field.value.trim()) {
                    errorElement.textContent = 'Business legal entity name is required';
                    field.classList.add('error');
                } else {
                    errorElement.textContent = '';
                    field.classList.remove('error');
                }
                validateForm();
            });
            
            validateField('company_mailing_address', () => {
                const field = document.getElementById('company_mailing_address');
                const errorElement = document.getElementById('company_mailing_address_error');
                if (!field.value.trim()) {
                    errorElement.textContent = 'Company mailing address is required';
                    field.classList.add('error');
                } else {
                    errorElement.textContent = '';
                    field.classList.remove('error');
                }
                validateForm();
            });
            
            validateField('city', () => {
                const field = document.getElementById('city');
                const errorElement = document.getElementById('city_error');
                if (!field.value.trim()) {
                    errorElement.textContent = 'City is required';
                    field.classList.add('error');
                } else {
                    errorElement.textContent = '';
                    field.classList.remove('error');
                }
                validateForm();
            });
            
            validateField('state', () => {
                const field = document.getElementById('state');
                const errorElement = document.getElementById('state_error');
                if (!field.value) {
                    errorElement.textContent = 'State is required';
                    field.classList.add('error');
                } else {
                    errorElement.textContent = '';
                    field.classList.remove('error');
                }
                validateForm();
            });
            
            // Initialize form validation
            validateForm();
        });
        
        // Prevent form resubmission on page refresh
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }
    </script>
</body>
</html>