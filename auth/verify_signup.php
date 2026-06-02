<?php
session_start();
include "../includes/db.php";

// Set timezone for PHP
date_default_timezone_set('Africa/Addis_Ababa');

// Clear any previous verification errors
$error = "";
$message = "";
$can_resend = false; // Track if resend is allowed
$resend_cooldown = 0; // Remaining seconds for cooldown

/* ========= STEP 1: GENERATE CODE (from signup.php) ========= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email']) && !isset($_POST['verify_code'])) {
    $email = trim($_POST['email']);
    $_SESSION['signup_email'] = $email;
    
    // Generate 6-digit code
    $code = sprintf("%06d", mt_rand(0, 999999));
    
    // Store in session
    $_SESSION['verification_code'] = $code;
    $_SESSION['code_generated_at'] = time();
    
    // Store in database - Use UTC time for consistency
    $expires_utc = gmdate("Y-m-d H:i:s", strtotime("+10 minutes"));
    
    // Clear any existing codes
    mysqli_query($conn, "DELETE FROM email_verifications WHERE email='$email'");
    
    // Insert new code with UTC time
    $sql = "INSERT INTO email_verifications (email, code, expires_at) VALUES ('$email', '$code', '$expires_utc')";
    mysqli_query($conn, $sql);
    
    // Set resend cooldown (60 seconds)
    $_SESSION['resend_allowed_after'] = time() + 60;
    $can_resend = false;
    $resend_cooldown = 60;
}

/* ========= CHECK CODE STATUS ========= */
$email = $_SESSION['signup_email'] ?? '';
$code_expired = false;
$dev_mode_code = '';

// Format display email (remove @phone.beudelivery suffix if present)
$display_email = $email;
if (strpos($email, '@phone.beudelivery') !== false) {
    $display_email = str_replace('@phone.beudelivery', '', $email);
}

if (!empty($email)) {
    // Check database for code status
    $current_utc = gmdate("Y-m-d H:i:s");
    $db_check = mysqli_query($conn, "SELECT code, expires_at FROM email_verifications WHERE email='$email' LIMIT 1");
    
    if ($db_check && mysqli_num_rows($db_check) > 0) {
        $row = mysqli_fetch_assoc($db_check);
        $dev_mode_code = $row['code'];
        
        // Convert DB UTC time to local for expiration check
        $expires_local = strtotime($row['expires_at'] . ' UTC');
        $current_local = time();
        
        // Check if code is expired
        if ($current_local > $expires_local) {
            $code_expired = true;
            $error = "Your verification code has expired. Please request a new one.";
            $can_resend = true; // Allow resend when expired
            
            // Clear expired code
            mysqli_query($conn, "DELETE FROM email_verifications WHERE email='$email'");
            unset($_SESSION['verification_code']);
        } else {
            // Code is still valid
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
        }
    } else {
        // No code in database
        $can_resend = true;
    }
}

/* ========= STEP 2: VERIFY CODE ========= */
if (isset($_POST['verify_code']) && isset($_POST['code'])) {
    $user_code = trim($_POST['code']);
    
    // Get code from session
    $session_code = $_SESSION['verification_code'] ?? '';
    
    // Check database using UTC time
    $current_utc = gmdate("Y-m-d H:i:s");
    $db_query = "SELECT code FROM email_verifications WHERE email='$email' AND code='$user_code' AND expires_at > '$current_utc' LIMIT 1";
    
    $db_result = mysqli_query($conn, $db_query);
    $db_valid = ($db_result && mysqli_num_rows($db_result) > 0);
    
    // Check session match
    $session_match = ($user_code === $session_code);
    
    if (empty($email)) {
        $error = "Session expired. Please start over.";
    } elseif (empty($user_code) || strlen($user_code) !== 6) {
        $error = "Please enter a valid 6-digit code.";
    } else {
        // Accept EITHER session OR database verification
        if ($session_match || $db_valid) {
            // SUCCESS
            mysqli_query($conn, "DELETE FROM email_verifications WHERE email='$email'");
            unset($_SESSION['verification_code']);
            unset($_SESSION['code_generated_at']);
            unset($_SESSION['resend_allowed_after']);
            
            $_SESSION['verified_email'] = $email;
            
            // Redirect
            header("Location: name_entry.php");
            exit();
        } else {
            // FAILURE - Show user-friendly message
            $error = "The email passcode you've entered is incorrect.";
        }
    }
}

/* ========= STEP 3: RESEND CODE ========= */
if (isset($_POST['resend_code'])) {
    if (!$can_resend) {
        $error = "Please wait before requesting a new code.";
    } else {
        // Generate new code
        $new_code = sprintf("%06d", mt_rand(0, 999999));
        
        // Update session
        $_SESSION['verification_code'] = $new_code;
        $_SESSION['code_generated_at'] = time();
        
        // Update database with UTC time
        $expires_utc = gmdate("Y-m-d H:i:s", strtotime("+10 minutes"));
        mysqli_query($conn, "DELETE FROM email_verifications WHERE email='$email'");
        mysqli_query($conn, "INSERT INTO email_verifications (email, code, expires_at) VALUES ('$email', '$new_code', '$expires_utc')");
        
        $message = "New verification code sent!";
        
        // Set resend cooldown (60 seconds)
        $_SESSION['resend_allowed_after'] = time() + 60;
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

// If no dev mode code from DB, try session
if (empty($dev_mode_code) && isset($_SESSION['verification_code'])) {
    $dev_mode_code = $_SESSION['verification_code'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BeU Delivery – Verification</title>
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
        .expired-warning {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
            text-align: left;
            font-size: 14px;
            color: #856404;
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
        
        <?php if ($code_expired): ?>
            <div class="expired-warning">
                <i class="fas fa-exclamation-circle"></i> Your verification code has expired. Please request a new one.
            </div>
        <?php endif; ?>
        
        <?php if ($message): ?>
            <div class="success-message">
                <i class="fas fa-check-circle"></i> <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <?php if ($error && !$code_expired): ?>
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
            <?php elseif ($code_expired): ?>
                <div class="resend-info">
                    <i class="fas fa-info-circle"></i> Code expired. You can request a new one now.
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
            <a href="signup.php" class="back-link">
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