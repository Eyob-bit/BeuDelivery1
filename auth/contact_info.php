<?php
session_start();

// Check if user is coming from verification
if (!isset($_SESSION['verified_email'])) {
    header("Location: signup.php");
    exit();
}

// Determine what contact info we need
$email = $_SESSION['verified_email'];
$needs_email = false;
$needs_phone = false;
$page_title = "";
$field_label = "";
$placeholder = "";

// Check if the verified email is actually a phone number (has @phone.beudelivery suffix)
if (strpos($email, '@phone.beudelivery') !== false) {
    // User signed up with phone number - ask for email
    $needs_email = true;
    $page_title = "What's your email?";
    $field_label = "Email address (optional)";
    $placeholder = "Enter your email address";
    $_SESSION['user_phone'] = str_replace('@phone.beudelivery', '', $email);
} else {
    // User signed up with email - ask for phone
    $needs_phone = true;
    $page_title = "What's your phone number?";
    $field_label = "Phone number (optional)";
    $placeholder = "Enter your phone number";
    $_SESSION['user_email'] = $email;
}

// Handle form submission
$error = "";
$success = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $contact_info = trim($_POST['contact_info'] ?? '');
    
    if ($needs_email && !empty($contact_info)) {
        // Validate email
        if (!filter_var($contact_info, FILTER_VALIDATE_EMAIL)) {
            $error = "Please enter a valid email address.";
        } else {
            $_SESSION['user_email'] = $contact_info;
            $success = "Email saved successfully!";
        }
    }
    
    if ($needs_phone && !empty($contact_info)) {
        // Validate Ethiopian phone number
        $phone = preg_replace('/[^0-9]/', '', $contact_info);
        
        // Validate Ethiopian phone
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
        
        if (!validateEthiopianPhone($contact_info)) {
            $error = "Please enter a valid Ethiopian phone number.";
        } else {
            // Format phone
            function formatEthiopianPhone($phone) {
                $digits = preg_replace('/[^0-9]/', '', $phone);
                if (substr($digits, 0, 3) == '251') {
                    $digits = substr($digits, 3);
                } elseif (substr($digits, 0, 1) == '0') {
                    $digits = substr($digits, 1);
                }
                return '0' . $digits;
            }
            
            $_SESSION['user_phone'] = formatEthiopianPhone($contact_info);
            $success = "Phone number saved successfully!";
        }
    }
    
    // If skip or successful, proceed to next step
    if (isset($_POST['skip']) || $success) {
        // Check if we need to ask for name or if we already have it
        if (isset($_SESSION['user_name'])) {
            // We already have name (if user came from somewhere else)
            header("Location: name_entry.php");
        } else {
            header("Location: name_entry.php");
        }
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BeU Delivery – <?php echo $page_title; ?></title>
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
        .container {
            max-width: 400px;
            width: 100%;
            padding: 0 20px;
            text-align: center;
        }
        .title {
            font-size: 22px;
            margin-bottom: 30px;
        }
        .subtitle {
            color: #666;
            margin-bottom: 30px;
            font-size: 16px;
            line-height: 1.5;
        }
        .optional-badge {
            background: #f6f6f6;
            color: #666;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 14px;
            display: inline-block;
            margin-bottom: 15px;
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
        .button-group {
            display: flex;
            flex-direction: column;
            gap: 10px;
            margin-top: 10px;
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
            color: #333;
            border: 1px solid #e2e2e2;
        }
        .secondary-button:hover {
            background: #e8e8e8;
        }
        .back-button {
            background: transparent;
            color: #333;
            border: 1px solid #ddd;
            margin-top: 15px;
        }
        .back-button:hover {
            background: #f6f6f6;
        }
        .progress-bar {
            display: flex;
            justify-content: center;
            margin-bottom: 40px;
        }
        .progress-step {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #ddd;
            margin: 0 5px;
        }
        .progress-step.active {
            background: #000;
        }
        .validation-hint {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            color: #666;
            margin-top: 5px;
            text-align: left;
        }
        .validation-hint.valid {
            color: #06C167;
        }
        .validation-hint.invalid {
            color: #d63031;
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
        <div class="progress-bar">
            <div class="progress-step"></div>
            <div class="progress-step"></div>
            <div class="progress-step active"></div>
            <div class="progress-step"></div>
            <div class="progress-step"></div>
        </div>

        <h1 class="title"><?php echo $page_title; ?></h1>
        <p class="subtitle">Add your <?php echo $needs_email ? 'email' : 'phone'; ?> to aid in account recovery</p>

        <?php if ($error): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="success-message">
                <i class="fas fa-check-circle"></i> <?php echo $success; ?>
            </div>
        <?php endif; ?>

        <form action="" method="POST" id="contactForm">
            <div class="optional-badge">Optional</div>
            
            <div>
                <input
                    type="<?php echo $needs_email ? 'email' : 'tel'; ?>"
                    id="contactInput"
                    name="contact_info"
                    placeholder="<?php echo $placeholder; ?>"
                    class="input-field"
                    <?php echo $needs_email ? 'inputmode="email"' : 'inputmode="tel"'; ?>
                    autocomplete="off"
                >
                
                <?php if ($needs_email): ?>
                <div id="emailHint" class="validation-hint" style="display: none;">
                    <i class="fas fa-check-circle"></i> Valid email format
                </div>
                <div id="invalidEmailHint" class="validation-hint invalid" style="display: none;">
                    <i class="fas fa-times-circle"></i> Please enter a valid email address
                </div>
                <?php else: ?>
                <div id="phoneHint" class="validation-hint" style="display: none;">
                    <i class="fas fa-check-circle"></i> Valid Ethiopian phone number
                </div>
                <div id="invalidPhoneHint" class="validation-hint invalid" style="display: none;">
                    <i class="fas fa-times-circle"></i> Please enter a valid Ethiopian phone number
                </div>
                <?php endif; ?>
            </div>

            <div class="button-group">
                <button type="submit" class="button primary-button" id="submitBtn">
                    Next →
                </button>

                <button type="submit" name="skip" value="1" class="button secondary-button">
                    Skip
                </button>

                <button type="button" class="button back-button" onclick="window.history.back()">
                    ← Back
                </button>
            </div>
        </form>
    </div>
</main>

<script>
<?php if ($needs_email): ?>
// Email validation
function validateEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

document.getElementById('contactInput').addEventListener('input', function(e) {
    const value = e.target.value.trim();
    const emailHint = document.getElementById('emailHint');
    const invalidHint = document.getElementById('invalidEmailHint');
    
    emailHint.style.display = 'none';
    invalidHint.style.display = 'none';
    this.classList.remove('error');
    
    if (value && !validateEmail(value)) {
        invalidHint.style.display = 'flex';
        this.classList.add('error');
    } else if (value && validateEmail(value)) {
        emailHint.style.display = 'flex';
    }
});

<?php else: ?>
// Phone validation for Ethiopian numbers
function validateEthiopianPhone(phone) {
    const digits = phone.replace(/\D/g, '');
    
    if (digits.length < 9 || digits.length > 12) return false;
    
    // Mobile numbers
    if (digits.match(/^9[0-9]{8}$/)) return true;
    if (digits.match(/^09[0-9]{8}$/)) return true;
    if (digits.match(/^2519[0-9]{8}$/)) return true;
    
    // Ethiopian area codes
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

function formatEthiopianPhone(phone) {
    let digits = phone.replace(/\D/g, '');
    
    if (digits.startsWith('251')) {
        digits = digits.substring(3);
    } else if (digits.startsWith('0')) {
        digits = digits.substring(1);
    }
    
    return '0' + digits;
}

document.getElementById('contactInput').addEventListener('input', function(e) {
    let value = e.target.value;
    const phoneHint = document.getElementById('phoneHint');
    const invalidHint = document.getElementById('invalidPhoneHint');
    
    phoneHint.style.display = 'none';
    invalidHint.style.display = 'none';
    this.classList.remove('error');
    
    // Auto-format as user types
    const digits = value.replace(/\D/g, '');
    if (digits.length >= 2) {
        if (digits.match(/^(09|9|251)/)) {
            let formatted = '';
            
            if (digits.startsWith('251') && digits.length > 3) {
                formatted = '+251 ' + digits.substring(3, 5);
                if (digits.length > 5) formatted += ' ' + digits.substring(5, 8);
                if (digits.length > 8) formatted += ' ' + digits.substring(8, 12);
            } else if (digits.startsWith('09') && digits.length > 2) {
                formatted = digits.substring(0, 3);
                if (digits.length > 3) formatted += ' ' + digits.substring(3, 6);
                if (digits.length > 6) formatted += ' ' + digits.substring(6, 10);
            } else if (digits.startsWith('9') && digits.length > 1) {
                formatted = digits.substring(0, 2);
                if (digits.length > 2) formatted += ' ' + digits.substring(2, 5);
                if (digits.length > 5) formatted += ' ' + digits.substring(5, 9);
            } else if (digits.length >= 2 && digits.length <= 3) {
                formatted = digits;
            } else if (digits.length > 3) {
                formatted = digits.substring(0, 3);
                if (digits.length > 3) formatted += ' ' + digits.substring(3, 6);
                if (digits.length > 6) formatted += ' ' + digits.substring(6, 10);
            }
            
            e.target.value = formatted.trim();
        }
    }
    
    // Validate
    if (value && !validateEthiopianPhone(value)) {
        invalidHint.style.display = 'flex';
        this.classList.add('error');
    } else if (value && validateEthiopianPhone(value)) {
        phoneHint.style.display = 'flex';
    }
});

<?php endif; ?>

// Form validation
document.getElementById('contactForm').addEventListener('submit', function(e) {
    const input = document.getElementById('contactInput');
    const value = input.value.trim();
    
    if (!value) {
        // Empty is OK (optional field)
        return true;
    }
    
    <?php if ($needs_email): ?>
    if (!validateEmail(value)) {
        e.preventDefault();
        document.getElementById('invalidEmailHint').style.display = 'flex';
        input.classList.add('error');
        input.focus();
        return false;
    }
    <?php else: ?>
    if (!validateEthiopianPhone(value)) {
        e.preventDefault();
        document.getElementById('invalidPhoneHint').style.display = 'flex';
        input.classList.add('error');
        input.focus();
        return false;
    }
    <?php endif; ?>
});

// Initialize
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('contactInput').focus();
});
</script>

</body>
</html>
