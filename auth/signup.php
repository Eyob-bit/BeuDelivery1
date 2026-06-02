<?php
session_start();
ob_start();
include "../includes/db.php";

// Clear any previous session data
unset($_SESSION['signup_email']);
unset($_SESSION['dev_code']);
unset($_SESSION['first_name']);
unset($_SESSION['last_name']);
unset($_SESSION['phone']);

$error = '';
$existing_user = false;
$email = '';

// Check if email already exists when form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email'])) {
    $email = trim($_POST['email']);
    
    // Validate email format
    if (empty($email)) {
        $error = "Please enter an email address or phone number.";
    } else {
        // Check if it's a valid email
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            // Valid email - check if user already exists
            $check_query = mysqli_query($conn, "SELECT id FROM users WHERE email = '$email'");
            
            if (mysqli_num_rows($check_query) > 0) {
                // User exists - show message and redirect option
                $existing_user = true;
                $error = "An account with this email already exists.";
            } else {
                // New user - proceed with verification
                $_SESSION['signup_email'] = $email;

                $code = sprintf("%06d", mt_rand(0, 999999));
                $expires_utc = gmdate("Y-m-d H:i:s", strtotime("+10 minutes"));

                mysqli_query($conn, "DELETE FROM email_verifications WHERE email='$email'");
                mysqli_query($conn, "
                    INSERT INTO email_verifications (email, code, expires_at)
                    VALUES ('$email', '$code', '$expires_utc')
                ");

                $_SESSION['verification_code'] = $code;
                $_SESSION['code_generated_at'] = time();
                
                // Redirect to verification page
                header("Location: verify_signup.php");
                exit();
            }
        } else {
            // Not a valid email - check if it's a valid Ethiopian phone number
            $phone = preg_replace('/[^0-9]/', '', $email); // Remove all non-digits
            
            // Validate Ethiopian phone number
            $is_valid_ethiopian_phone = validateEthiopianPhone($phone);
            
            if (!$is_valid_ethiopian_phone) {
                $error = "Please enter a valid Ethiopian phone number (e.g., 0912345678 or +251912345678) or email address.";
            } else {
                // Format phone number for storage
                $formatted_phone = formatEthiopianPhone($phone);
                
                // Check if phone already exists
                $check_query = mysqli_query($conn, "SELECT id FROM users WHERE phone = '$formatted_phone'");
                
                if (mysqli_num_rows($check_query) > 0) {
                    // Phone exists - show message
                    $existing_user = true;
                    $error = "An account with this phone number already exists.";
                } else {
                    // New user with phone - proceed with verification
                    // For phone numbers, we'll use them as email for the verification system
                    $_SESSION['signup_email'] = $formatted_phone . '@phone.beudelivery';
                    $_SESSION['actual_phone'] = $formatted_phone; // Store actual phone separately
                    $_SESSION['is_phone'] = true; // Flag that this is a phone number

                    $code = sprintf("%06d", mt_rand(0, 999999));
                    $expires_utc = gmdate("Y-m-d H:i:s", strtotime("+10 minutes"));

                    mysqli_query($conn, "DELETE FROM email_verifications WHERE email='{$_SESSION['signup_email']}'");
                    mysqli_query($conn, "
                        INSERT INTO email_verifications (email, code, expires_at)
                        VALUES ('{$_SESSION['signup_email']}', '$code', '$expires_utc')
                    ");

                    $_SESSION['verification_code'] = $code;
                    $_SESSION['code_generated_at'] = time();
                    
                    // In a real app, you would send SMS here
                    // For now, we'll just show the code in DEV MODE
                    
                    // Redirect to verification page
                    header("Location: verify_signup.php");
                    exit();
                }
            }
        }
    }
}

// Function to validate Ethiopian phone number
function validateEthiopianPhone($phone) {
    // Remove all non-digits
    $digits = preg_replace('/[^0-9]/', '', $phone);
    
    // Check length
    if (strlen($digits) < 9 || strlen($digits) > 12) {
        return false;
    }
    
    // Ethiopian phone number patterns:
    // 09XXXXXXXX (10 digits) - Mobile
    // 9XXXXXXXX (9 digits) - Mobile without leading 0
    // 2519XXXXXXXX (12 digits) - International format
    // 011XXXXXXX (9-10 digits) - Addis Ababa landline
    // Other area codes (022, 033, etc.) for regions
    
    // Check if it's a mobile number (starting with 9)
    if (preg_match('/^9[0-9]{8}$/', $digits)) {
        return true; // 9XXXXXXXX (9 digits)
    }
    
    // Check if it's a mobile number with leading 0
    if (preg_match('/^09[0-9]{8}$/', $digits)) {
        return true; // 09XXXXXXXX (10 digits)
    }
    
    // Check if it's international format
    if (preg_match('/^2519[0-9]{8}$/', $digits)) {
        return true; // 2519XXXXXXXX (12 digits)
    }
    
    // Check if it's a landline (area code + number)
    $area_codes = ['11', '22', '33', '34', '35', '36', '46', '47', '48', '52', '53', '54', '55', '56', '57', '58', '59', '61', '62', '63', '64', '65', '66', '67', '68', '69', '71', '72', '73', '74', '75', '76', '77', '78'];
    
    foreach ($area_codes as $area_code) {
        if (preg_match('/^' . $area_code . '[0-9]{6,7}$/', $digits)) {
            return true;
        }
    }
    
    return false;
}

// Function to format Ethiopian phone number
function formatEthiopianPhone($phone) {
    $digits = preg_replace('/[^0-9]/', '', $phone);
    
    // Remove leading 0 or 251 if present
    if (substr($digits, 0, 3) == '251') {
        $digits = substr($digits, 3);
    } elseif (substr($digits, 0, 1) == '0') {
        $digits = substr($digits, 1);
    }
    
    // Format as 09XXXXXXXX for display
    return '0' . $digits;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BeU Delivery – Sign up</title>
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
            padding-top: 100px;
        }
        .login-container {
            max-width: 400px;
            width: 100%;
            padding: 0 20px;
            text-align: center;
        }
        .prompt {
            font-size: 22px;
            margin-bottom: 30px;
        }
        .existing-user-alert {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
            text-align: left;
        }
        .existing-user-alert h3 {
            color: #856404;
            margin-bottom: 10px;
            font-size: 16px;
        }
        .existing-user-alert p {
            color: #856404;
            margin-bottom: 15px;
            font-size: 14px;
        }
        .alert-buttons {
            display: flex;
            gap: 10px;
            margin-top: 10px;
        }
        .alert-button {
            padding: 10px 20px;
            border-radius: 6px;
            border: none;
            cursor: pointer;
            font-weight: 500;
            flex: 1;
            text-decoration: none;
            text-align: center;
            font-size: 14px;
        }
        .alert-login {
            background: #000;
            color: #fff;
        }
        .alert-login:hover {
            background: #333;
        }
        .alert-signup {
            background: #f6f6f6;
            color: #333;
            border: 1px solid #ddd;
        }
        .alert-signup:hover {
            background: #e8e8e8;
        }
        form {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        .input-field {
            padding: 15px;
            border: 1px solid #c0c0c0;
            border-radius: 4px;
            font-size: 16px;
            width: 100%;
            transition: border-color 0.3s;
        }
        .input-field:focus {
            outline: none;
            border-color: #000;
        }
        .input-field.error {
            border-color: #d63031;
            background-color: #fff8f8;
        }
        .input-hint {
            font-size: 14px;
            color: #666;
            text-align: left;
            margin-top: -10px;
            margin-bottom: 10px;
        }
        .button {
            padding: 15px;
            font-size: 16px;
            font-weight: 500;
            border-radius: 4px;
            border: none;
            cursor: pointer;
            transition: background-color 0.3s;
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
            border: 1px solid #e2e2e2;
        }
        .secondary-button:hover {
            background: #e8e8e8;
        }
        .divider {
            display: flex;
            align-items: center;
            margin: 20px 0;
        }
        .divider::before,
        .divider::after {
            content: '';
            flex: 1;
            border-bottom: 1px solid #e2e2e2;
        }
        .or-text {
            padding: 0 15px;
            font-size: 14px;
            color: #737373;
        }
        .terms-text {
            font-size: 12px;
            color: #737373;
            margin-top: 25px;
            line-height: 1.5;
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
        .validation-hint {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            color: #666;
            margin-top: 5px;
        }
        .validation-hint.valid {
            color: #06C167;
        }
        .validation-hint.invalid {
            color: #d63031;
        }
        .phone-format-examples {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 6px;
            padding: 12px;
            margin: 15px 0;
            font-size: 13px;
            text-align: left;
        }
        .phone-format-examples h4 {
            margin: 0 0 8px 0;
            color: #333;
            font-size: 13px;
        }
        .phone-format-examples ul {
            margin: 0;
            padding-left: 20px;
            color: #666;
        }
        .phone-format-examples li {
            margin-bottom: 4px;
        }
        .country-code {
            color: #000;
            font-weight: 500;
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
    <div class="login-container">

        <h2 class="prompt">What's your phone number or email?</h2>

        <?php if ($existing_user): ?>
        <!-- Existing User Alert -->
        <div class="existing-user-alert">
            <h3>Account Already Exists</h3>
            <p>An account with <strong><?php echo htmlspecialchars($email); ?></strong> already exists.</p>
            <div class="alert-buttons">
                <a href="login.html" class="alert-button alert-login">
                    Log In Instead
                </a>
                <button type="button" onclick="clearForm()" class="alert-button alert-signup">
                    Use Different Email/Phone
                </button>
            </div>
        </div>
        <?php elseif ($error && !$existing_user): ?>
        <div class="error-message">
            <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
        </div>
        <?php endif; ?>

        <!-- Phone Format Examples -->
        <div class="phone-format-examples">
            <h4>Valid Ethiopian Phone Formats:</h4>
            <ul>
                <li>Mobile: <span class="country-code">0912345678</span> or <span class="country-code">+251912345678</span></li>
                <li>Addis Ababa: <span class="country-code">0111234567</span></li>
                <li>Other regions: <span class="country-code">0221234567</span></li>
            </ul>
        </div>

        <!-- Signup Form -->
        <form action="" method="POST" id="signupForm" onsubmit="return validateForm()">
            <div>
                <input
                    type="text"
                    name="email"
                    id="emailInput"
                    placeholder="Enter email or Ethiopian phone"
                    class="input-field <?php echo $error && !$existing_user ? 'error' : ''; ?>"
                    value="<?php echo htmlspecialchars($email); ?>"
                    required
                    autocomplete="off"
                    inputmode="tel"
                >
                
                <!-- Validation Hints -->
                <div id="emailHint" class="validation-hint" style="display: none;">
                    <i class="fas fa-check-circle"></i> Valid email address
                </div>
                <div id="phoneHint" class="validation-hint" style="display: none;">
                    <i class="fas fa-check-circle"></i> Valid Ethiopian phone number
                </div>
                <div id="invalidHint" class="validation-hint invalid" style="display: none;">
                    <i class="fas fa-times-circle"></i> Please enter a valid email or Ethiopian phone number
                </div>
            </div>

            <button type="submit" class="button primary-button" id="submitBtn">
                Continue
            </button>

            <div class="divider">
                <span class="or-text">or</span>
            </div>

            <button type="button" class="button secondary-button" onclick="comingSoon()">
                <i class="fab fa-google"></i> Continue with Google
            </button>

            <button type="button" class="button secondary-button" onclick="comingSoon()">
                <i class="fab fa-apple"></i> Continue with Apple
            </button>

        </form>

        <p class="terms-text">
            By continuing, you agree to calls or texts from BeU Delivery. You also agree to our 
            <a href="#" style="color:#000;text-decoration:underline;">Terms of Service</a> and 
            <a href="#" style="color:#000;text-decoration:underline;">Privacy Policy</a>.
        </p>

        <div style="margin-top: 30px; font-size: 14px; color: #666;">
            Already have an account? <a href="login.html" style="color:#000;font-weight:500;">Log in</a>
        </div>

    </div>
</main>

<script>
function comingSoon() {
    alert("This feature is coming soon 🚀");
}

function clearForm() {
    document.getElementById('signupForm').reset();
    document.getElementById('emailInput').classList.remove('error');
    hideAllHints();
    document.getElementById('emailInput').focus();
}

function hideAllHints() {
    document.getElementById('emailHint').style.display = 'none';
    document.getElementById('phoneHint').style.display = 'none';
    document.getElementById('invalidHint').style.display = 'none';
}

function validateEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

function validateEthiopianPhone(phone) {
    // Remove all non-digits
    const digits = phone.replace(/\D/g, '');
    
    // Check length
    if (digits.length < 9 || digits.length > 12) {
        return false;
    }
    
    // Ethiopian mobile numbers (start with 9)
    if (digits.match(/^9[0-9]{8}$/)) {
        return true; // 9XXXXXXXX (9 digits)
    }
    
    if (digits.match(/^09[0-9]{8}$/)) {
        return true; // 09XXXXXXXX (10 digits)
    }
    
    if (digits.match(/^2519[0-9]{8}$/)) {
        return true; // 2519XXXXXXXX (12 digits)
    }
    
    // Ethiopian area codes
    const areaCodes = [
        '11', // Addis Ababa
        '22', // Amhara
        '33', // Oromia
        '34', // Oromia
        '35', // Oromia
        '36', // Oromia
        '46', // SNNP
        '47', // SNNP
        '48', // SNNP
        '52', // Dire Dawa
        '53', // Harari
        '54', // Somali
        '55', // Somali
        '56', // Afar
        '57', // Benishangul-Gumuz
        '58', // Gambela
        '59', // Tigray
        '61', // Southern
        '62', // Southern
        '63', // Southern
        '64', // Southern
        '65', // Southern
        '66', // Southern
        '67', // Southern
        '68', // Southern
        '69', // Southern
        '71', // Central
        '72', // Central
        '73', // Central
        '74', // Central
        '75', // Central
        '76', // Central
        '77', // Central
        '78'  // Central
    ];
    
    for (const areaCode of areaCodes) {
        if (digits.match(new RegExp('^' + areaCode + '[0-9]{6,7}$'))) {
            return true;
        }
    }
    
    return false;
}

function formatEthiopianPhone(phone) {
    let digits = phone.replace(/\D/g, '');
    
    // Remove country code if present
    if (digits.startsWith('251')) {
        digits = digits.substring(3);
    }
    
    // Remove leading 0 if present
    if (digits.startsWith('0')) {
        digits = digits.substring(1);
    }
    
    // Format as 09XXXXXXXX
    return '0' + digits;
}

function validateForm() {
    const emailInput = document.getElementById('emailInput');
    const value = emailInput.value.trim();
    
    hideAllHints();
    emailInput.classList.remove('error');
    
    if (!value) {
        document.getElementById('invalidHint').style.display = 'flex';
        emailInput.classList.add('error');
        emailInput.focus();
        return false;
    }
    
    const isEmail = validateEmail(value);
    const isEthiopianPhone = validateEthiopianPhone(value);
    
    if (isEmail) {
        document.getElementById('emailHint').style.display = 'flex';
        return true;
    } else if (isEthiopianPhone) {
        document.getElementById('phoneHint').style.display = 'flex';
        
        // Auto-format the phone number
        const formattedPhone = formatEthiopianPhone(value);
        if (formattedPhone !== value) {
            emailInput.value = formattedPhone;
        }
        
        return true;
    } else {
        document.getElementById('invalidHint').style.display = 'flex';
        emailInput.classList.add('error');
        emailInput.focus();
        return false;
    }
}

// Real-time validation as user types
document.getElementById('emailInput').addEventListener('input', function(e) {
    const value = e.target.value.trim();
    
    hideAllHints();
    this.classList.remove('error');
    
    if (!value) {
        return;
    }
    
    const isEmail = validateEmail(value);
    const isEthiopianPhone = validateEthiopianPhone(value);
    
    if (isEmail) {
        document.getElementById('emailHint').style.display = 'flex';
        this.classList.remove('error');
    } else if (isEthiopianPhone) {
        document.getElementById('phoneHint').style.display = 'flex';
        this.classList.remove('error');
        
        // Auto-format as user types
        const formattedPhone = formatEthiopianPhone(value);
        if (formattedPhone !== value) {
            e.target.value = formattedPhone;
        }
    }
});

// Validate on blur
document.getElementById('emailInput').addEventListener('blur', function(e) {
    const value = e.target.value.trim();
    
    if (!value) return;
    
    const isEmail = validateEmail(value);
    const isEthiopianPhone = validateEthiopianPhone(value);
    
    if (!isEmail && !isEthiopianPhone) {
        document.getElementById('invalidHint').style.display = 'flex';
        this.classList.add('error');
    } else if (isEthiopianPhone) {
        // Format on blur
        const formattedPhone = formatEthiopianPhone(value);
        if (formattedPhone !== value) {
            e.target.value = formattedPhone;
        }
    }
});

// Clear error when user starts typing again
document.getElementById('emailInput').addEventListener('focus', function() {
    this.classList.remove('error');
    document.getElementById('invalidHint').style.display = 'none';
});

// Auto-format phone input
document.getElementById('emailInput').addEventListener('input', function(e) {
    let value = e.target.value;
    const digits = value.replace(/\D/g, '');
    
    if (digits.length >= 2) {
        // If it starts like a phone number, format it
        if (digits.match(/^(09|9|251)/)) {
            let formatted = '';
            
            if (digits.startsWith('251') && digits.length > 3) {
                // International format: +251 91 234 5678
                formatted = '+251 ' + digits.substring(3, 5);
                if (digits.length > 5) formatted += ' ' + digits.substring(5, 8);
                if (digits.length > 8) formatted += ' ' + digits.substring(8, 12);
            } else if (digits.startsWith('09') && digits.length > 2) {
                // Local format: 091 234 5678
                formatted = digits.substring(0, 3);
                if (digits.length > 3) formatted += ' ' + digits.substring(3, 6);
                if (digits.length > 6) formatted += ' ' + digits.substring(6, 10);
            } else if (digits.startsWith('9') && digits.length > 1) {
                // Without leading 0: 91 234 5678
                formatted = digits.substring(0, 2);
                if (digits.length > 2) formatted += ' ' + digits.substring(2, 5);
                if (digits.length > 5) formatted += ' ' + digits.substring(5, 9);
            } else if (digits.length >= 2 && digits.length <= 3) {
                // Area code
                formatted = digits;
            } else if (digits.length > 3) {
                // Landline: 011 123 4567
                formatted = digits.substring(0, 3);
                if (digits.length > 3) formatted += ' ' + digits.substring(3, 6);
                if (digits.length > 6) formatted += ' ' + digits.substring(6, 10);
            }
            
            e.target.value = formatted.trim();
        }
    }
});

// Initialize
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('emailInput').focus();
});
</script>

</body>
</html>