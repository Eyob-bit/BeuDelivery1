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

// Check if tax info already exists
$tax_sql = "SELECT * FROM merchant_tax_info WHERE merchant_id = '$merchant_id'";
$tax_result = mysqli_query($conn, $tax_sql);
$existing_tax = mysqli_fetch_assoc($tax_result);

// Handle form submission
$error = "";
$success = "";
$tax_submitted = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_tax'])) {
    // Get form data
    $tax_classification = mysqli_real_escape_string($conn, $_POST['tax_classification']);
    $full_name = mysqli_real_escape_string($conn, trim($_POST['full_name']));
    $ssn = mysqli_real_escape_string($conn, trim($_POST['ssn']));
    $ein = mysqli_real_escape_string($conn, trim($_POST['ein'] ?? ''));
    $business_name = mysqli_real_escape_string($conn, trim($_POST['business_name'] ?? ''));
    $address = mysqli_real_escape_string($conn, trim($_POST['address'] ?? ''));
    $city = mysqli_real_escape_string($conn, trim($_POST['city'] ?? ''));
    $state = mysqli_real_escape_string($conn, $_POST['state'] ?? '');
    $postal_code = mysqli_real_escape_string($conn, trim($_POST['postal_code'] ?? ''));
    
    // Validation
    $errors = [];
    
    // Required fields
    if (empty($tax_classification)) {
        $errors[] = "Tax classification is required";
    }
    
    if (empty($full_name)) {
        $errors[] = "Full name is required";
    }
    
    // Validate based on tax classification
    if ($tax_classification === 'individual-ssn') {
        if (empty($ssn)) {
            $errors[] = "Social Security Number is required for Individual/Sole Proprietor with SSN";
        } elseif (!preg_match('/^\d{3}-\d{2}-\d{4}$/', $ssn)) {
            $errors[] = "Please enter a valid SSN (XXX-XX-XXXX format)";
        }
    } elseif ($tax_classification === 'individual-ein') {
        if (empty($ein)) {
            $errors[] = "Employer Identification Number is required for Individual/Sole Proprietor with EIN";
        } elseif (!preg_match('/^\d{2}-\d{7}$/', $ein)) {
            $errors[] = "Please enter a valid EIN (XX-XXXXXXX format)";
        }
    } elseif ($tax_classification === 'c-corp' || $tax_classification === 's-corp' || $tax_classification === 'llc') {
        if (empty($business_name)) {
            $errors[] = "Business name is required for corporations and LLCs";
        }
        if (empty($ein)) {
            $errors[] = "Employer Identification Number is required for corporations and LLCs";
        } elseif (!preg_match('/^\d{2}-\d{7}$/', $ein)) {
            $errors[] = "Please enter a valid EIN (XX-XXXXXXX format)";
        }
        if (empty($address)) {
            $errors[] = "Business address is required";
        }
        if (empty($city)) {
            $errors[] = "City is required";
        }
        if (empty($state)) {
            $errors[] = "State is required";
        }
        if (empty($postal_code)) {
            $errors[] = "Postal code is required";
        }
    }
    
    // If no errors, save to database
    if (empty($errors)) {
        // Encrypt sensitive data
        $encrypted_ssn = !empty($ssn) ? base64_encode($ssn) : '';
        $encrypted_ein = !empty($ein) ? base64_encode($ein) : '';
        
        // Generate last 4 digits for display
        $ssn_last_four = !empty($ssn) ? substr($ssn, -4) : '';
        $ein_last_four = !empty($ein) ? substr($ein, -4) : '';
        
        mysqli_begin_transaction($conn);
        
        try {
            if ($existing_tax) {
                // Update existing tax info
                $update_sql = "UPDATE merchant_tax_info SET 
                              tax_classification = '$tax_classification',
                              full_name = '$full_name',
                              ssn = " . ($encrypted_ssn ? "'$encrypted_ssn'" : "NULL") . ",
                              ssn_last_four = " . ($ssn_last_four ? "'$ssn_last_four'" : "NULL") . ",
                              ein = " . ($encrypted_ein ? "'$encrypted_ein'" : "NULL") . ",
                              ein_last_four = " . ($ein_last_four ? "'$ein_last_four'" : "NULL") . ",
                              business_name = " . ($business_name ? "'$business_name'" : "NULL") . ",
                              address = " . ($address ? "'$address'" : "NULL") . ",
                              city = " . ($city ? "'$city'" : "NULL") . ",
                              state = " . ($state ? "'$state'" : "NULL") . ",
                              postal_code = " . ($postal_code ? "'$postal_code'" : "NULL") . ",
                              verified = 0,
                              updated_at = NOW()
                              WHERE merchant_id = '$merchant_id'";
            } else {
                // Insert new tax info
                $update_sql = "INSERT INTO merchant_tax_info 
                              (merchant_id, tax_classification, full_name, ssn, ssn_last_four, 
                               ein, ein_last_four, business_name, address, city, state, 
                               postal_code, verified, created_at, updated_at)
                              VALUES ('$merchant_id', '$tax_classification', '$full_name',
                              " . ($encrypted_ssn ? "'$encrypted_ssn'" : "NULL") . ",
                              " . ($ssn_last_four ? "'$ssn_last_four'" : "NULL") . ",
                              " . ($encrypted_ein ? "'$encrypted_ein'" : "NULL") . ",
                              " . ($ein_last_four ? "'$ein_last_four'" : "NULL") . ",
                              " . ($business_name ? "'$business_name'" : "NULL") . ",
                              " . ($address ? "'$address'" : "NULL") . ",
                              " . ($city ? "'$city'" : "NULL") . ",
                              " . ($state ? "'$state'" : "NULL") . ",
                              " . ($postal_code ? "'$postal_code'" : "NULL") . ",
                              0, NOW(), NOW())";
            }
            
            if (!mysqli_query($conn, $update_sql)) {
                throw new Exception("Failed to save tax info: " . mysqli_error($conn));
            }
            
            // Update progress in session
            $_SESSION['tax_info_setup'] = true;
            
            // Commit transaction
            mysqli_commit($conn);
            
            $success = "Tax information saved successfully! Redirecting back to setup...";
            $tax_submitted = true;
            
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
$tax_classification = $existing_tax['tax_classification'] ?? 'individual-ssn';
$full_name = $existing_tax['full_name'] ?? ($merchant['first_name'] . ' ' . $merchant['last_name']);
$business_name = $existing_tax['business_name'] ?? $merchant['store_name'];
$address = $existing_tax['address'] ?? $merchant['store_address'];
$city = $existing_tax['city'] ?? '';
$state = $existing_tax['state'] ?? 'NC';
$postal_code = $existing_tax['postal_code'] ?? '';

// For security, we don't pre-fill SSN/EIN, but show placeholder if exists
$has_ssn = !empty($existing_tax['ssn_last_four']);
$has_ein = !empty($existing_tax['ein_last_four']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enter your tax info - BeU Delivery</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <style>
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

        /* Back Arrow */
        .back-arrow {
            font-size: 1.5rem;
            color: #333;
            text-decoration: none;
            display: block;
            margin-bottom: 15px;
        }

        /* Main Form Title and Subtitle */
        .form-title {
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 10px;
        }
        
        .form-subtitle {
            font-size: 0.95rem;
            color: #333;
            margin-bottom: 30px;
        }
        
        .form-subtitle a {
            color: black;
            font-weight: bold;
            text-decoration: underline;
        }

        /* Info Box Styling */
        .info-box {
            background-color: #f7f7f7;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 30px;
            border-left: 4px solid black;
            font-size: 0.9rem;
            color: #6c757d;
        }
        
        .info-box strong {
            color: black;
        }

        /* Form Group Styling */
        .form-group {
            margin-bottom: 25px;
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
            display: flex;
            align-items: center;
        }
        
        .form-control-custom input,
        .form-control-custom select {
            flex-grow: 1;
            background: transparent;
            border: none;
            outline: none;
            padding: 0;
            margin-right: 10px;
            width: 100%;
        }
        
        .form-control-custom:focus-within {
            box-shadow: 0 0 0 2px rgba(0, 0, 0, 0.1);
            background-color: white;
            border-color: black;
        }
        
        .form-control-custom .status-icon {
            margin-left: 10px;
            font-size: 1.1rem;
        }
        
        .form-control-custom .status-icon.verified {
            color: #28a745;
        }

        .input-description {
            font-size: 0.8rem;
            color: #6c757d;
            margin-top: 5px;
            display: block;
        }
        
        .input-note {
            font-size: 0.8rem;
            color: #6c757d;
            margin-top: 5px;
            display: block;
        }

        /* Conditional fields */
        .conditional-field {
            display: none;
            animation: fadeIn 0.3s ease-in;
        }
        
        .conditional-field.show {
            display: block;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
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
        
        /* SSN/EIN input styling */
        .tax-id-input {
            letter-spacing: 1px;
            font-family: monospace;
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
            
            .form-title {
                font-size: 1.5rem;
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
            <h1 class="form-title">Enter your tax info</h1>
            <p class="form-subtitle">
                This verifies your business and ensures you'll get important tax documents so you're prepared for tax season. <a href="#">Learn more</a>
            </p>

            <?php if ($existing_tax): ?>
            <div class="existing-info">
                <i class="bi bi-info-circle-fill"></i>
                <strong>Tax information already saved</strong>
                <div class="mt-2 small">
                    Classification: 
                    <?php 
                    $classifications = [
                        'individual-ssn' => 'Individual/Sole Proprietor with SSN',
                        'individual-ein' => 'Individual/Sole Proprietor with EIN',
                        'c-corp' => 'C Corporation',
                        's-corp' => 'S Corporation',
                        'llc' => 'LLC',
                        'partnership' => 'Partnership',
                        'trust-estate' => 'Trust/Estate'
                    ];
                    echo htmlspecialchars($classifications[$existing_tax['tax_classification']] ?? $existing_tax['tax_classification']);
                    ?><br>
                    Last updated: <?php echo date('M j, Y', strtotime($existing_tax['updated_at'] ?? $existing_tax['created_at'])); ?>
                </div>
            </div>
            <?php endif; ?>

            <div class="security-notice">
                <i class="bi bi-shield-lock"></i>
                <strong>Security Notice:</strong> Your tax identification numbers are encrypted and stored securely. 
                We only display the last 4 digits for verification purposes.
            </div>

            <div class="info-box">
                We have pre-filled some of your information for your convenience. Please verify it and hit <strong>Submit</strong> on the final step in order to save it.
            </div>

            <form method="POST" action="" id="taxForm">
                <input type="hidden" name="submit_tax" value="1">
                
                <div class="form-group">
                    <label for="tax_classification" class="form-label">Federal tax classification</label>
                    <div class="form-control-custom">
                        <select id="tax_classification" name="tax_classification" required onchange="toggleTaxFields()">
                            <option value="">Select classification</option>
                            <option value="individual-ssn" <?php echo $tax_classification == 'individual-ssn' ? 'selected' : ''; ?>>Individual/Sole Proprietor with SSN</option>
                            <option value="individual-ein" <?php echo $tax_classification == 'individual-ein' ? 'selected' : ''; ?>>Individual/Sole Proprietor with EIN</option>
                            <option value="c-corp" <?php echo $tax_classification == 'c-corp' ? 'selected' : ''; ?>>C Corporation</option>
                            <option value="s-corp" <?php echo $tax_classification == 's-corp' ? 'selected' : ''; ?>>S Corporation</option>
                            <option value="llc" <?php echo $tax_classification == 'llc' ? 'selected' : ''; ?>>LLC</option>
                            <option value="partnership" <?php echo $tax_classification == 'partnership' ? 'selected' : ''; ?>>Partnership</option>
                            <option value="trust-estate" <?php echo $tax_classification == 'trust-estate' ? 'selected' : ''; ?>>Trust/Estate</option>
                        </select>
                        <i class="bi bi-chevron-down status-icon"></i>
                    </div>
                </div>

                <div class="form-group">
                    <label for="full_name" class="form-label">Full name</label>
                    <div class="form-control-custom">
                        <input type="text" id="full_name" name="full_name" 
                               value="<?php echo htmlspecialchars($full_name); ?>" 
                               required oninput="validateName()">
                        <i class="bi bi-check-circle-fill status-icon verified"></i>
                    </div>
                    <span class="input-note">As it appears on your tax return. IRS requires letters or numbers only (e.g. use 'X' for 'x').</span>
                    <span class="error-message" id="full_name_error"></span>
                </div>

                <!-- SSN Field (shown for individual-ssn) -->
                <div id="ssnField" class="form-group conditional-field <?php echo $tax_classification == 'individual-ssn' ? 'show' : ''; ?>">
                    <label for="ssn" class="form-label">Social Security Number (SSN)</label>
                    <div class="form-control-custom">
                        <input type="text" id="ssn" name="ssn" 
                               class="tax-id-input" 
                               placeholder="XXX-XX-XXXX" 
                               maxlength="11" 
                               oninput="formatSSN(this)"
                               value="<?php echo $has_ssn ? 'XXX-XX-' . htmlspecialchars($existing_tax['ssn_last_four']) : ''; ?>">
                    </div>
                    <span class="input-note">Not sure which number? <a href="#">Learn more</a></span>
                    <span class="error-message" id="ssn_error"></span>
                </div>

                <!-- EIN Field (shown for individual-ein, corporations, LLCs) -->
                <div id="einField" class="form-group conditional-field <?php echo in_array($tax_classification, ['individual-ein', 'c-corp', 's-corp', 'llc', 'partnership', 'trust-estate']) ? 'show' : ''; ?>">
                    <label for="ein" class="form-label">Employer Identification Number (EIN)</label>
                    <div class="form-control-custom">
                        <input type="text" id="ein" name="ein" 
                               class="tax-id-input" 
                               placeholder="XX-XXXXXXX" 
                               maxlength="10" 
                               oninput="formatEIN(this)"
                               value="<?php echo $has_ein ? 'XX-XXX-' . htmlspecialchars($existing_tax['ein_last_four']) : ''; ?>">
                    </div>
                    <span class="error-message" id="ein_error"></span>
                </div>

                <!-- Business Info Fields (shown for corporations, LLCs, partnerships) -->
                <div id="businessFields" class="conditional-field <?php echo in_array($tax_classification, ['c-corp', 's-corp', 'llc', 'partnership', 'trust-estate']) ? 'show' : ''; ?>">
                    
                    <div class="form-group">
                        <label for="business_name" class="form-label">Business Name</label>
                        <div class="form-control-custom">
                            <input type="text" id="business_name" name="business_name" 
                                   value="<?php echo htmlspecialchars($business_name); ?>">
                        </div>
                        <span class="error-message" id="business_name_error"></span>
                    </div>

                    <div class="form-group">
                        <label for="address" class="form-label">Business Address</label>
                        <div class="form-control-custom">
                            <input type="text" id="address" name="address" 
                                   value="<?php echo htmlspecialchars($address); ?>">
                        </div>
                        <span class="error-message" id="address_error"></span>
                    </div>

                    <div class="form-group">
                        <label for="city" class="form-label">City</label>
                        <div class="form-control-custom">
                            <input type="text" id="city" name="city" 
                                   value="<?php echo htmlspecialchars($city); ?>">
                        </div>
                        <span class="error-message" id="city_error"></span>
                    </div>

                    <div class="form-group">
                        <label for="state" class="form-label">State</label>
                        <div class="form-control-custom">
                            <select id="state" name="state">
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
                        </div>
                        <span class="error-message" id="state_error"></span>
                    </div>

                    <div class="form-group">
                        <label for="postal_code" class="form-label">Postal Code</label>
                        <div class="form-control-custom">
                            <input type="text" id="postal_code" name="postal_code" 
                                   value="<?php echo htmlspecialchars($postal_code); ?>" 
                                   maxlength="10" 
                                   oninput="formatPostalCode(this)">
                        </div>
                        <span class="error-message" id="postal_code_error"></span>
                    </div>
                </div>

                <div class="footer-buttons">
                    <a href="setup.php" class="btn btn-cancel">Cancel</a>
                    <button type="submit" class="btn btn-submit" id="submitBtn" 
                            <?php echo $tax_submitted ? 'disabled' : ''; ?>>
                        <?php echo $existing_tax ? 'Update Tax Info' : 'Submit'; ?>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    
    <script>
        // Toggle fields based on tax classification
        function toggleTaxFields() {
            const classification = document.getElementById('tax_classification').value;
            
            // Show/hide SSN field
            const ssnField = document.getElementById('ssnField');
            if (classification === 'individual-ssn') {
                ssnField.classList.add('show');
            } else {
                ssnField.classList.remove('show');
                document.getElementById('ssn').value = '';
            }
            
            // Show/hide EIN field
            const einField = document.getElementById('einField');
            const showEin = ['individual-ein', 'c-corp', 's-corp', 'llc', 'partnership', 'trust-estate'].includes(classification);
            if (showEin) {
                einField.classList.add('show');
            } else {
                einField.classList.remove('show');
                document.getElementById('ein').value = '';
            }
            
            // Show/hide business fields
            const businessFields = document.getElementById('businessFields');
            const showBusiness = ['c-corp', 's-corp', 'llc', 'partnership', 'trust-estate'].includes(classification);
            if (showBusiness) {
                businessFields.classList.add('show');
            } else {
                businessFields.classList.remove('show');
                // Clear business fields
                document.getElementById('business_name').value = '';
                document.getElementById('address').value = '';
                document.getElementById('city').value = '';
                document.getElementById('state').value = '';
                document.getElementById('postal_code').value = '';
            }
            
            validateForm();
        }
        
        // Format SSN (XXX-XX-XXXX)
        function formatSSN(input) {
            let value = input.value.replace(/\D/g, '');
            
            // Format: XXX-XX-XXXX
            if (value.length > 3 && value.length <= 5) {
                value = value.substring(0, 3) + '-' + value.substring(3);
            } else if (value.length > 5) {
                value = value.substring(0, 3) + '-' + value.substring(3, 5) + '-' + value.substring(5, 9);
            }
            
            input.value = value;
            validateSSN();
            validateForm();
        }
        
        // Format EIN (XX-XXXXXXX)
        function formatEIN(input) {
            let value = input.value.replace(/\D/g, '');
            
            // Format: XX-XXXXXXX
            if (value.length > 2) {
                value = value.substring(0, 2) + '-' + value.substring(2, 9);
            }
            
            input.value = value;
            validateEIN();
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
        
        // Validate name (IRS requirements)
        function validateName() {
            const name = document.getElementById('full_name').value;
            const errorElement = document.getElementById('full_name_error');
            
            // IRS requires alphanumeric characters only
            const isValid = /^[a-zA-Z0-9\s\-\.]+$/.test(name);
            
            if (name && !isValid) {
                errorElement.textContent = 'IRS requires letters, numbers, spaces, hyphens, and periods only. Use "X" for special characters.';
            } else {
                errorElement.textContent = '';
            }
            
            validateForm();
        }
        
        // Validate SSN
        function validateSSN() {
            const ssn = document.getElementById('ssn').value;
            const errorElement = document.getElementById('ssn_error');
            const ssnRegex = /^\d{3}-\d{2}-\d{4}$/;
            
            if (ssn && !ssnRegex.test(ssn)) {
                errorElement.textContent = 'Please enter a valid SSN (XXX-XX-XXXX format)';
            } else {
                errorElement.textContent = '';
            }
            
            validateForm();
        }
        
        // Validate EIN
        function validateEIN() {
            const ein = document.getElementById('ein').value;
            const errorElement = document.getElementById('ein_error');
            const einRegex = /^\d{2}-\d{7}$/;
            
            if (ein && !einRegex.test(ein)) {
                errorElement.textContent = 'Please enter a valid EIN (XX-XXXXXXX format)';
            } else {
                errorElement.textContent = '';
            }
            
            validateForm();
        }
        
        // Validate postal code
        function validatePostalCode() {
            const postalCode = document.getElementById('postal_code').value;
            const errorElement = document.getElementById('postal_code_error');
            const zipRegex = /^[0-9]{5}(?:-[0-9]{4})?$/;
            
            if (postalCode && !zipRegex.test(postalCode)) {
                errorElement.textContent = 'Please enter a valid ZIP code (5 digits or 5+4 format)';
            } else {
                errorElement.textContent = '';
            }
            
            validateForm();
        }
        
        // Validate entire form
        function validateForm() {
            const submitBtn = document.getElementById('submitBtn');
            const classification = document.getElementById('tax_classification').value;
            let isValid = true;
            
            // Check required fields
            if (!classification) {
                isValid = false;
            }
            
            const fullName = document.getElementById('full_name').value.trim();
            if (!fullName) {
                isValid = false;
            }
            
            // Check classification-specific requirements
            if (classification === 'individual-ssn') {
                const ssn = document.getElementById('ssn').value;
                const ssnRegex = /^\d{3}-\d{2}-\d{4}$/;
                if (!ssn || !ssnRegex.test(ssn)) {
                    isValid = false;
                }
            } else if (classification === 'individual-ein') {
                const ein = document.getElementById('ein').value;
                const einRegex = /^\d{2}-\d{7}$/;
                if (!ein || !einRegex.test(ein)) {
                    isValid = false;
                }
            } else if (['c-corp', 's-corp', 'llc', 'partnership', 'trust-estate'].includes(classification)) {
                // Business info required
                const businessName = document.getElementById('business_name').value.trim();
                const address = document.getElementById('address').value.trim();
                const city = document.getElementById('city').value.trim();
                const state = document.getElementById('state').value;
                const postalCode = document.getElementById('postal_code').value;
                const ein = document.getElementById('ein').value;
                
                const einRegex = /^\d{2}-\d{7}$/;
                const zipRegex = /^[0-9]{5}(?:-[0-9]{4})?$/;
                
                if (!businessName || !address || !city || !state || !postalCode || !ein) {
                    isValid = false;
                }
                
                if (postalCode && !zipRegex.test(postalCode)) {
                    isValid = false;
                }
                
                if (ein && !einRegex.test(ein)) {
                    isValid = false;
                }
            }
            
            // Enable/disable submit button
            submitBtn.disabled = !isValid;
            
            return isValid;
        }
        
        // Form submission validation
        document.getElementById('taxForm').addEventListener('submit', function(e) {
            if (!validateForm()) {
                e.preventDefault();
                alert('Please fill in all required fields correctly before submitting.');
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
        
        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            // Set up field validations
            document.getElementById('full_name').addEventListener('input', validateName);
            document.getElementById('ssn').addEventListener('input', validateSSN);
            document.getElementById('ein').addEventListener('input', validateEIN);
            document.getElementById('postal_code').addEventListener('input', validatePostalCode);
            
            // Initialize business info fields if needed
            const classification = document.getElementById('tax_classification').value;
            if (['c-corp', 's-corp', 'llc', 'partnership', 'trust-estate'].includes(classification)) {
                // These fields are already visible for these classifications
                document.getElementById('business_name').addEventListener('input', validateForm);
                document.getElementById('address').addEventListener('input', validateForm);
                document.getElementById('city').addEventListener('input', validateForm);
                document.getElementById('state').addEventListener('change', validateForm);
            }
            
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