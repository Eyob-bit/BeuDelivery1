<?php
session_start();
ob_start();
include "../includes/db.php";

// Clear any previous session data
unset($_SESSION['login_email']);
unset($_SESSION['dev_code']);

$error = '';
$email = '';

// Check if email/phone exists when form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email'])) {
    $email = trim($_POST['email']);
    
    // Validate email format
    if (empty($email)) {
        $error = "Please enter an email address or phone number.";
    } else {
        // Check if it's a valid email
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            // Valid email - check if user exists
            $check_query = mysqli_query($conn, "SELECT id FROM users WHERE email = '$email'");
            
            if (mysqli_num_rows($check_query) == 0) {
                $error = "No account found with this email.";
            } else {
                // User exists - redirect to verification
                $_SESSION['login_email'] = $email;
                
                // Generate code and redirect
                header("Location: send_login_code.php");
                exit();
            }
        } else {
            // Not a valid email - check if it's a valid Ethiopian phone number
            $phone = preg_replace('/[^0-9]/', '', $email);
            
            // Validate Ethiopian phone number
            function validateEthiopianPhone($phone) {
                $digits = preg_replace('/[^0-9]/', '', $phone);
                
                if (strlen($digits) < 9 || strlen($digits) > 12) return false;
                
                // Mobile numbers
                if (preg_match('/^9[0-9]{8}$/', $digits)) return true;
                if (preg_match('/^09[0-9]{8}$/', $digits)) return true;
                if (preg_match('/^2519[0-9]{8}$/', $digits)) return true;
                
                // Area codes
                $area_codes = ['11', '22', '33', '34', '35', '36', '46', '47', '48', 
                              '52', '53', '54', '55', '56', '57', '58', '59', '61', 
                              '62', '63', '64', '65', '66', '67', '68', '69', '71', 
                              '72', '73', '74', '75', '76', '77', '78'];
                
                foreach ($area_codes as $area_code) {
                    if (preg_match('/^' . $area_code . '[0-9]{6,7}$/', $digits)) {
                        return true;
                    }
                }
                return false;
            }
            
            if (!validateEthiopianPhone($email)) {
                $error = "Please enter a valid Ethiopian phone number (e.g., 0912345678 or +251912345678) or email address.";
            } else {
                // Format phone for database check
                function formatEthiopianPhone($phone) {
                    $digits = preg_replace('/[^0-9]/', '', $phone);
                    if (substr($digits, 0, 3) == '251') {
                        $digits = substr($digits, 3);
                    } elseif (substr($digits, 0, 1) == '0') {
                        $digits = substr($digits, 1);
                    }
                    return '0' . $digits;
                }
                
                $formatted_phone = formatEthiopianPhone($email);
                
                // Check if phone exists
                $check_query = mysqli_query($conn, "SELECT id FROM users WHERE phone = '$formatted_phone'");
                
                if (mysqli_num_rows($check_query) == 0) {
                    $error = "No account found with this phone number.";
                } else {
                    // Phone exists - use pseudo-email format for verification system
                    $_SESSION['login_email'] = $formatted_phone . '@phone.beudelivery';
                    $_SESSION['actual_phone'] = $formatted_phone;
                    
                    // Redirect to verification
                    header("Location: send_login_code.php");
                    exit();
                }
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
    <title>BeU Delivery – Log in</title>
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
            width: 100%;
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

        <?php if ($error): ?>
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

        <!-- Login Form -->
        <form action="" method="POST" id="loginForm" onsubmit="return validateForm()">
            <div>
                <input
                    type="text"
                    name="email"
                    id="emailInput"
                    placeholder="Enter email or Ethiopian phone"
                    class="input-field <?php echo $error ? 'error' : ''; ?>"
                    value="<?php echo htmlspecialchars($email); ?>"
                    required
                    autocomplete="off"
                    inputmode="tel"
                >
                <div class="input-hint">
                    Enter your email address or Ethiopian phone number
                </div>
                
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
            Don't have an account? <a href="signup.php" style="color:#000;font-weight:500;">Sign up</a>
        </div>

    </div>
</main>

<script>
function comingSoon() {
    alert("This feature is coming soon 🚀");
}

function validateEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

function validateEthiopianPhone(phone) {
    const digits = phone.replace(/\D/g, '');
    
    if (digits.length < 9 || digits.length > 12) return false;
    
    if (digits.match(/^9[0-9]{8}$/)) return true;
    if (digits.match(/^09[0-9]{8}$/)) return true;
    if (digits.match(/^2519[0-9]{8}$/)) return true;
    
    const areaCodes = ['11', '22', '33', '34', '35', '36', '46', '47', '48', 
                      '52', '53', '54', '55', '56', '57', '58', '59', '61', 
                      '62', '63', '64', '65', '66', '67', '68', '69', '71', 
                      '72', '73', '74', '75', '76', '77', '78'];
    
    for (const areaCode of areaCodes) {
        if (digits.match(new RegExp('^' + areaCode + '[0-9]{6,7}$'))) {
            return true;
        }
    }
    return false;
}

function hideAllHints() {
    document.getElementById('emailHint').style.display = 'none';
    document.getElementById('phoneHint').style.display = 'none';
    document.getElementById('invalidHint').style.display = 'none';
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
        return true;
    } else {
        document.getElementById('invalidHint').style.display = 'flex';
        emailInput.classList.add('error');
        emailInput.focus();
        return false;
    }
}

// Real-time validation
document.getElementById('emailInput').addEventListener('input', function(e) {
    const value = e.target.value.trim();
    
    hideAllHints();
    this.classList.remove('error');
    
    if (!value) return;
    
    const isEmail = validateEmail(value);
    const isEthiopianPhone = validateEthiopianPhone(value);
    
    if (isEmail) {
        document.getElementById('emailHint').style.display = 'flex';
        this.classList.remove('error');
    } else if (isEthiopianPhone) {
        document.getElementById('phoneHint').style.display = 'flex';
        this.classList.remove('error');
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
    }
});

// Clear error when user starts typing again
document.getElementById('emailInput').addEventListener('focus', function() {
    this.classList.remove('error');
    document.getElementById('invalidHint').style.display = 'none';
});

// Initialize
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('emailInput').focus();
});
</script>

</body>
</html>