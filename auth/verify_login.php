<?php
session_start();
include "../includes/db.php";

// Set timezone for PHP
date_default_timezone_set('Africa/Addis_Ababa');

// Clear any previous verification errors
$error = "";
$message = "";
$can_resend = false;
$resend_cooldown = 0;

/* ========= CHECK CODE STATUS ========= */
$email = $_SESSION['login_email'] ?? '';
$dev_mode_code = $_SESSION['login_verification_code'] ?? '';

// Format display email
$display_email = $email;
if (strpos($email, '@phone.beudelivery') !== false) {
    $display_email = str_replace('@phone.beudelivery', '', $email);
}

if (!empty($email)) {
    // Check database for code status using prepared statement
    $stmt = mysqli_prepare($conn, "SELECT code, expires_at FROM email_verifications WHERE email = ? LIMIT 1");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $db_check = mysqli_stmt_get_result($stmt);
        
        if ($db_check && mysqli_num_rows($db_check) > 0) {
            $row = mysqli_fetch_assoc($db_check);
            $dev_mode_code = $row['code'];
            mysqli_stmt_close($stmt);
            
            // Check resend cooldown
            if (isset($_SESSION['resend_allowed_after'])) {
                $cooldown_end = $_SESSION['resend_allowed_after'];
                $current_time = time();
                
                if ($current_time >= $cooldown_end) {
                    $can_resend = true;
                    unset($_SESSION['resend_allowed_after']);
                } else {
                    $can_resend = false;
                    $resend_cooldown = $cooldown_end - $current_time;
                }
            } else {
                $can_resend = true;
            }
        } else {
            $can_resend = true;
        }
    }
}

/* ========= VERIFY CODE ========= */
if (isset($_POST['verify_code']) && isset($_POST['code'])) {
    $user_code = trim($_POST['code']);
    
    // Get code from session
    $session_code = $_SESSION['login_verification_code'] ?? '';
    
    if (empty($email)) {
        $error = "Session expired. Please start over.";
    } elseif (empty($user_code) || strlen($user_code) !== 6) {
        $error = "Please enter a valid 6-digit code.";
    } else {
        // Check database using prepared statement
        $current_utc = gmdate("Y-m-d H:i:s");
        $stmt = mysqli_prepare($conn, 
            "SELECT code FROM email_verifications WHERE email = ? AND code = ? AND expires_at > ? LIMIT 1"
        );
        
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "sss", $email, $user_code, $current_utc);
            mysqli_stmt_execute($stmt);
            $db_result = mysqli_stmt_get_result($stmt);
            $db_valid = ($db_result && mysqli_num_rows($db_result) > 0);
            
            // Check session match
            $session_match = ($user_code === $session_code);
            
            // Accept EITHER session OR database verification
            if ($session_match || $db_valid) {
                // SUCCESS - Delete verification code
                $delete_stmt = mysqli_prepare($conn, "DELETE FROM email_verifications WHERE email = ?");
                if ($delete_stmt) {
                    mysqli_stmt_bind_param($delete_stmt, "s", $email);
                    mysqli_stmt_execute($delete_stmt);
                    mysqli_stmt_close($delete_stmt);
                }
                
                unset($_SESSION['login_verification_code']);
                unset($_SESSION['code_generated_at']);
                unset($_SESSION['resend_allowed_after']);
                
                // Determine if it's email or phone login
                if (strpos($email, '@phone.beudelivery') !== false) {
                    // Phone login - get actual phone
                    $phone = str_replace('@phone.beudelivery', '', $email);
                    
                    // Get user info with prepared statement
                    $user_stmt = mysqli_prepare($conn, "
                        SELECT u.*, GROUP_CONCAT(r.name SEPARATOR ', ') as roles
                        FROM users u
                        LEFT JOIN user_roles ur ON u.id = ur.user_id
                        LEFT JOIN roles r ON ur.role_id = r.id
                        WHERE u.phone = ?
                        GROUP BY u.id
                        LIMIT 1
                    ");
                    mysqli_stmt_bind_param($user_stmt, "s", $phone);
                } else {
                    // Email login
                    $user_stmt = mysqli_prepare($conn, "
                        SELECT u.*, GROUP_CONCAT(r.name SEPARATOR ', ') as roles
                        FROM users u
                        LEFT JOIN user_roles ur ON u.id = ur.user_id
                        LEFT JOIN roles r ON ur.role_id = r.id
                        WHERE u.email = ?
                        GROUP BY u.id
                        LIMIT 1
                    ");
                    mysqli_stmt_bind_param($user_stmt, "s", $email);
                }
                
                if ($user_stmt) {
                    mysqli_stmt_execute($user_stmt);
                    $user_query = mysqli_stmt_get_result($user_stmt);
                    
                    if ($user_query && mysqli_num_rows($user_query) > 0) {
                        $user = mysqli_fetch_assoc($user_query);
                        mysqli_stmt_close($user_stmt);
                        
                        // Get roles as array
                        $user_roles = !empty($user['roles']) ? explode(', ', $user['roles']) : [];
                        
                        // Set session variables
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['user_email'] = $user['email'];
                        $_SESSION['user_phone'] = $user['phone'];
                        $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
                        $_SESSION['user_roles'] = $user_roles;
                        $_SESSION['user_type'] = $user['user_type'];
                        $_SESSION['logged_in'] = true;
                        
                        // Update last login
                        $update_stmt = mysqli_prepare($conn, "UPDATE users SET last_login = NOW() WHERE id = ?");
                        if ($update_stmt) {
                            mysqli_stmt_bind_param($update_stmt, "i", $user['id']);
                            mysqli_stmt_execute($update_stmt);
                            mysqli_stmt_close($update_stmt);
                        }
                        
                        // ===== PRIORITY-BASED REDIRECT LOGIC =====
                        $redirect_url = "../user/home.php"; // Default
                        
                        // PRIORITY 1: Check if user has ADMIN role
                        if (in_array('admin', $user_roles) || $user['user_type'] === 'admin') {
                            $redirect_url = "../admin/admin_panel.php";
                        }
                        // PRIORITY 2: Check if user has merchant record
                        elseif ($user['user_type'] === 'merchant' || in_array('merchant', $user_roles)) {
                            $merchant_stmt = mysqli_prepare($conn, 
                                "SELECT merchant_id, status FROM merchants WHERE user_id = ? LIMIT 1"
                            );
                            
                            if ($merchant_stmt) {
                                mysqli_stmt_bind_param($merchant_stmt, "i", $user['id']);
                                mysqli_stmt_execute($merchant_stmt);
                                $merchant_check = mysqli_stmt_get_result($merchant_stmt);
                                
                                if (mysqli_num_rows($merchant_check) > 0) {
                                    // User has merchant record
                                    $merchant = mysqli_fetch_assoc($merchant_check);
                                    
                                    $_SESSION['merchant_id'] = $merchant['merchant_id'];
                                    $_SESSION['user_type'] = 'merchant';
                                    
                                    // Set redirect based on merchant status
                                    switch ($merchant['status']) {
                                        case 'active':
                                            $redirect_url = "../account/merchant_dashboard.php";
                                            break;
                                        case 'under_review':
                                            $redirect_url = "../account/accountunderreview.php";
                                            break;
                                        case 'setup':
                                            $redirect_url = "../merchant/setup.php";
                                            break;
                                        case 'inactive':
                                            $redirect_url = "../account/account_disabled.php";
                                            break;
                                        default:
                                            $redirect_url = "../merchant/getStarted.php";
                                            break;
                                    }
                                } else {
                                    // Merchant type but no merchant record - start registration
                                    $redirect_url = "../merchant/getStarted.php";
                                }
                                mysqli_stmt_close($merchant_stmt);
                            }
                        }
                        // PRIORITY 3: Check for delivery role
                        elseif ($user['user_type'] === 'delivery' || in_array('delivery', $user_roles)) {
                            $redirect_url = "../delivery/dashboard.php";
                        }
                        // PRIORITY 4: Default to customer
                        else {
                            $redirect_url = "../user/home.php";
                        }
                        
                        // Perform redirect
                        header("Location: " . $redirect_url);
                        exit();
                        
                    } else {
                        mysqli_stmt_close($user_stmt);
                        $error = "User account not found.";
                    }
                } else {
                    $error = "Database error. Please try again.";
                }
            } else {
                mysqli_stmt_close($stmt);
                $error = "The login code you've entered is incorrect.";
            }
        }
    }
}

/* ========= RESEND CODE ========= */
if (isset($_POST['resend_code'])) {
    if (!$can_resend) {
        $error = "Please wait before requesting a new code.";
    } else {
        // Generate new code
        $new_code = sprintf("%06d", mt_rand(0, 999999));
        
        // Update database with UTC time using prepared statement
        $expires_utc = gmdate("Y-m-d H:i:s", strtotime("+10 minutes"));
        
        $delete_stmt = mysqli_prepare($conn, "DELETE FROM email_verifications WHERE email = ?");
        if ($delete_stmt) {
            mysqli_stmt_bind_param($delete_stmt, "s", $email);
            mysqli_stmt_execute($delete_stmt);
            mysqli_stmt_close($delete_stmt);
        }
        
        $insert_stmt = mysqli_prepare($conn, 
            "INSERT INTO email_verifications (email, code, expires_at) VALUES (?, ?, ?)"
        );
        if ($insert_stmt) {
            mysqli_stmt_bind_param($insert_stmt, "sss", $email, $new_code, $expires_utc);
            mysqli_stmt_execute($insert_stmt);
            mysqli_stmt_close($insert_stmt);
        }
        
        // Update session
        $_SESSION['login_verification_code'] = $new_code;
        $_SESSION['code_generated_at'] = time();
        $_SESSION['resend_allowed_after'] = time() + 60;
        
        $message = "New verification code sent!";
        $can_resend = false;
        $resend_cooldown = 60;
        
        // Refresh page
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }
}

// Calculate remaining cooldown time
if (isset($_SESSION['resend_allowed_after'])) {
    $remaining = $_SESSION['resend_allowed_after'] - time();
    if ($remaining > 0) {
        $resend_cooldown = $remaining;
    } else {
        unset($_SESSION['resend_allowed_after']);
        $can_resend = true;
        $resend_cooldown = 0;
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BeU Delivery – Login Verification</title>
    <link href="https://fonts.googleapis.com/css2?family=Uber+Move+Text:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Uber Move Text', Arial, sans-serif;
            background: #fff;
            color: #000;
        }
        header {
            background: #000;
            color: #fff;
            height: 60px;
            display: flex;
            align-items: center;
            padding: 0 20px;
        }
        .logo { font-size: 24px; font-weight: 700; }
        main {
            display: flex;
            justify-content: center;
            padding-top: 80px;
        }
        .container {
            max-width: 400px;
            width: 100%;
            padding: 0 20px;
            text-align: center;
        }
        .title {
            font-size: 22px;
            margin-bottom: 10px;
        }
        .subtitle {
            color: #666;
            margin-bottom: 30px;
            font-size: 16px;
            line-height: 1.5;
        }
        .dev-mode {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 15px;
            margin: 25px 0;
            font-size: 16px;
        }
        .dev-mode strong {
            color: #06C167;
            display: block;
            margin-bottom: 5px;
            font-size: 14px;
        }
        .dev-mode .code {
            font-size: 24px;
            font-weight: 700;
            letter-spacing: 3px;
            color: #000;
        }
        .code-inputs {
            display: flex;
            justify-content: center;
            gap: 12px;
            margin: 30px 0;
        }
        .code-box {
            width: 50px;
            height: 60px;
            font-size: 24px;
            text-align: center;
            border: 2px solid #c0c0c0;
            border-radius: 8px;
            transition: all 0.3s;
            font-weight: bold;
        }
        .code-box:focus {
            outline: none;
            border-color: #000;
            box-shadow: 0 0 0 2px rgba(0,0,0,0.1);
        }
        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 12px;
            border-radius: 4px;
            margin-bottom: 15px;
            border: 1px solid #f5c6cb;
            font-size: 14px;
            text-align: left;
        }
        .success-message {
            background: #d4edda;
            color: #155724;
            padding: 12px;
            border-radius: 4px;
            margin-bottom: 15px;
            border: 1px solid #c3e6cb;
            font-size: 14px;
        }
        .button-group {
            display: flex;
            gap: 12px;
            margin-top: 30px;
        }
        .button {
            padding: 14px 20px;
            font-size: 16px;
            font-weight: 500;
            border-radius: 4px;
            border: none;
            cursor: pointer;
            transition: background-color 0.3s;
            flex: 1;
        }
        .primary-button {
            background: #000;
            color: #fff;
        }
        .primary-button:hover {
            background: #333;
        }
        .secondary-button {
            background: #f6f6f6;
            color: #333;
            border: 1px solid #e2e2e2;
        }
        .secondary-button:hover {
            background: #e8e8e8;
        }
        .button:disabled {
            background: #ccc;
            cursor: not-allowed;
            color: #666;
        }
        .back-link {
            color: #666;
            text-decoration: none;
            margin-top: 25px;
            display: inline-block;
            padding: 10px 20px;
            border-radius: 6px;
            border: 1px solid #ddd;
            transition: all 0.3s;
            font-size: 14px;
        }
        .back-link:hover {
            color: #000;
            background: #f8f9fa;
            border-color: #000;
        }
        .resend-info {
            font-size: 14px;
            color: #666;
            margin-top: 15px;
        }
        .countdown {
            font-weight: bold;
            color: #06C167;
        }
        .resend-timer {
            display: inline-block;
            background: #e9ecef;
            padding: 2px 8px;
            border-radius: 12px;
            margin-left: 5px;
            font-size: 12px;
            font-weight: bold;
            color: #495057;
        }
        .loading {
            display: inline-block;
            width: 16px;
            height: 16px;
            border: 2px solid #fff;
            border-radius: 50%;
            border-top-color: transparent;
            animation: spin 1s linear infinite;
            margin-right: 8px;
            vertical-align: middle;
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
</head>

<body>

<header>
    <a href="../index.php" style="color:white;text-decoration:none">
        <h1 class="logo">BeU Delivery</h1>
    </a>
</header>

<main>
    <div class="container">
        <h2 class="title">Enter verification code</h2>
        <p class="subtitle">We sent a 6-digit code to <strong><?php echo !empty($display_email) ? htmlspecialchars($display_email) : 'your email/phone'; ?></strong></p>
        
        <?php if ($message): ?>
            <div class="success-message">
                <i class="fas fa-check-circle"></i> <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <?php if ($dev_mode_code): ?>
        <div class="dev-mode">
            <strong>DEV MODE CODE</strong>
            <div class="code"><?php echo $dev_mode_code; ?></div>
            
            <?php if ($resend_cooldown > 0 && !$can_resend): ?>
                <div class="resend-info">
                    Resend available in <span class="countdown" id="cooldownDisplay"><?php echo $resend_cooldown; ?></span> seconds
                </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- Verification Form -->
        <form method="POST" id="verificationForm">
            <div class="code-inputs">
                <input class="code-box" type="text" maxlength="1" id="c1" autofocus oninput="handleInput(1, this)" onkeydown="handleKey(1, event)" onpaste="handlePaste(event)" inputmode="numeric" autocomplete="off">
                <input class="code-box" type="text" maxlength="1" id="c2" oninput="handleInput(2, this)" onkeydown="handleKey(2, event)" onpaste="handlePaste(event)" inputmode="numeric" autocomplete="off">
                <input class="code-box" type="text" maxlength="1" id="c3" oninput="handleInput(3, this)" onkeydown="handleKey(3, event)" onpaste="handlePaste(event)" inputmode="numeric" autocomplete="off">
                <input class="code-box" type="text" maxlength="1" id="c4" oninput="handleInput(4, this)" onkeydown="handleKey(4, event)" onpaste="handlePaste(event)" inputmode="numeric" autocomplete="off">
                <input class="code-box" type="text" maxlength="1" id="c5" oninput="handleInput(5, this)" onkeydown="handleKey(5, event)" onpaste="handlePaste(event)" inputmode="numeric" autocomplete="off">
                <input class="code-box" type="text" maxlength="1" id="c6" oninput="handleInput(6, this)" onkeydown="handleKey(6, event)" onpaste="handlePaste(event)" inputmode="numeric" autocomplete="off">
            </div>
            
            <input type="hidden" name="code" id="hiddenCode">
            <input type="hidden" name="verify_code" value="1">
        </form>

        <div class="button-group">
            <button type="button" onclick="submitVerification()" id="verifyBtn" class="button primary-button">
                Verify
            </button>
            
            <!-- Resend Form -->
            <form method="POST" id="resendForm" style="display:inline; flex:1;">
                <input type="hidden" name="resend_code" value="1">
                <button type="submit" class="button secondary-button" id="resendBtn" <?php echo !$can_resend ? 'disabled' : ''; ?>>
                    Resend Code
                    <?php if ($resend_cooldown > 0 && !$can_resend): ?>
                        <span class="resend-timer" id="resendTimer"><?php echo $resend_cooldown; ?>s</span>
                    <?php endif; ?>
                </button>
            </form>
        </div>

        <div style="margin-top: 30px;">
            <a href="login.php" class="back-link">
                <i class="fas fa-arrow-left"></i> Use different email or phone
            </a>
        </div>
    </div>
</main>

<script>
// Store the correct code from PHP
const correctCode = "<?php echo $dev_mode_code; ?>";
let canResend = <?php echo $can_resend ? 'true' : 'false'; ?>;
let resendTimeLeft = <?php echo $resend_cooldown; ?>;

// Timer for resend button and cooldown display
if (resendTimeLeft > 0) {
    const resendTimer = document.getElementById('resendTimer');
    const cooldownDisplay = document.getElementById('cooldownDisplay');
    const resendBtn = document.getElementById('resendBtn');
    
    const countdown = setInterval(() => {
        resendTimeLeft--;
        
        // Update timer displays
        if (resendTimer) {
            resendTimer.textContent = resendTimeLeft + 's';
        }
        if (cooldownDisplay) {
            cooldownDisplay.textContent = resendTimeLeft;
        }
        
        if (resendTimeLeft <= 0) {
            clearInterval(countdown);
            canResend = true;
            if (resendBtn) {
                resendBtn.disabled = false;
                resendBtn.innerHTML = 'Resend Code';
            }
            if (cooldownDisplay) {
                cooldownDisplay.parentElement.style.display = 'none';
            }
        }
    }, 1000);
}

// Input handling
function handleInput(index, input) {
    const value = input.value;
    
    // Only allow digits
    if (!/^\d?$/.test(value)) {
        input.value = '';
        return;
    }
    
    // If a digit was entered and not the last input
    if (value.length === 1 && index < 6) {
        // Focus next input
        setTimeout(() => {
            document.getElementById('c' + (index + 1)).focus();
        }, 10);
    }
    
    // If last input and has value, auto-submit
    if (index === 6 && value.length === 1) {
        setTimeout(() => {
            if (validateAllInputs()) {
                submitVerification();
            }
        }, 300);
    }
}

function handleKey(index, event) {
    const input = document.getElementById('c' + index);
    
    if (event.key === 'Backspace') {
        if (input.value === '') {
            // If empty and not first input, go to previous
            if (index > 1) {
                event.preventDefault();
                const prevInput = document.getElementById('c' + (index - 1));
                prevInput.value = '';
                prevInput.focus();
            }
        } else {
            // If has value, clear it
            event.preventDefault();
            input.value = '';
        }
    } else if (event.key === 'Enter') {
        event.preventDefault();
        submitVerification();
    } else if (event.key.length === 1 && !/\d/.test(event.key)) {
        // Block non-digit characters
        event.preventDefault();
    }
}

function handlePaste(event) {
    event.preventDefault();
    const pasteData = event.clipboardData.getData('text').trim();
    const digits = pasteData.replace(/\D/g, '');
    
    if (digits.length >= 6) {
        // Fill all inputs
        for (let i = 1; i <= 6; i++) {
            document.getElementById('c' + i).value = digits[i-1] || '';
        }
        document.getElementById('c6').focus();
        
        // Auto-submit
        setTimeout(submitVerification, 300);
    }
}

function validateAllInputs() {
    let code = '';
    let allFilled = true;
    
    for (let i = 1; i <= 6; i++) {
        const input = document.getElementById('c' + i);
        const value = input.value.trim();
        
        if (!value) {
            allFilled = false;
            input.style.borderColor = '#d63031';
            setTimeout(() => input.style.borderColor = '', 1500);
        } else {
            code += value;
        }
    }
    
    if (!allFilled) {
        alert("Please fill all 6 digits");
        return false;
    }
    
    if (!/^\d{6}$/.test(code)) {
        alert("Please enter numbers only (0-9)");
        return false;
    }
    
    return true;
}

function getEnteredCode() {
    let code = '';
    for (let i = 1; i <= 6; i++) {
        code += document.getElementById('c' + i).value || '';
    }
    return code;
}

function submitVerification() {
    if (!validateAllInputs()) {
        return;
    }
    
    const code = getEnteredCode();
    const verifyBtn = document.getElementById('verifyBtn');
    
    // Show loading
    verifyBtn.innerHTML = '<span class="loading"></span> Verifying...';
    verifyBtn.disabled = true;
    
    // Set hidden input
    document.getElementById('hiddenCode').value = code;
    
    // Submit form
    document.getElementById('verificationForm').submit();
}

// Initialize
document.addEventListener('DOMContentLoaded', function() {
    // Auto-focus first empty input
    setTimeout(() => {
        for (let i = 1; i <= 6; i++) {
            const input = document.getElementById('c' + i);
            if (!input.value) {
                input.focus();
                break;
            }
        }
    }, 100);
});
</script>

</body>
</html>