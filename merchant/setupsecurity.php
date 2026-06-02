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
$merchant_sql = "SELECT m.*, u.email FROM merchants m 
                 JOIN users u ON m.user_id = u.id 
                 WHERE m.merchant_id = '$merchant_id'";
$merchant_result = mysqli_query($conn, $merchant_sql);
$merchant = mysqli_fetch_assoc($merchant_result);

// Handle password update
$error = "";
$success = "";
$password_requirements_met = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_password'])) {
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validation
    if (empty($new_password) || empty($confirm_password)) {
        $error = "Please fill in both password fields";
    } elseif ($new_password !== $confirm_password) {
        $error = "Passwords do not match";
    } elseif (strlen($new_password) < 8) {
        $error = "Password must be at least 8 characters long";
    } elseif (!preg_match('/\d/', $new_password)) {
        $error = "Password must contain at least one digit";
    } elseif (!preg_match('/[^0-9]/', $new_password)) {
        $error = "Password must contain at least one non-digit character";
    } else {
        // All requirements met
        $password_requirements_met = true;
        
        // Hash the password
        $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);
        
        // Update password in database
        $update_sql = "UPDATE users SET password_hash = '$hashed_password' WHERE id = '$user_id'";
        
        if (mysqli_query($conn, $update_sql)) {
            $success = "Password updated successfully! Redirecting back to setup...";
            
            // Store in session that security is completed
            $_SESSION['security_completed'] = true;
            
            // Redirect back to setup.php after 2 seconds
            header("refresh:2;url=setup.php");
        } else {
            $error = "Failed to update password: " . mysqli_error($conn);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Setup - Uber Account</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <style>
        /* General body styling */
        body {
            background-color: white;
            font-family: Arial, sans-serif;
            color: #333;
        }

        .page-content {
            max-width: 500px;
            margin: 100px auto 0;
            padding: 20px;
            text-align: center;
        }

        /* Header */
        .header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            background-color: white;
            border-bottom: 1px solid #eee;
            padding: 15px 20px;
            z-index: 1000;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            font-size: 1.2rem;
            font-weight: bold;
        }

        .merchant-info {
            text-align: right;
        }

        .merchant-name {
            font-weight: bold;
            font-size: 0.9rem;
        }

        .merchant-address {
            font-size: 0.8rem;
            color: #666;
        }

        /* Title and Description */
        .page-title {
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 20px;
            margin-top: 80px;
        }

        .password-description {
            font-size: 0.95rem;
            color: #333;
            margin-bottom: 30px;
        }

        /* Form Styling */
        .form-group-custom {
            text-align: left;
            margin-bottom: 20px;
        }

        .form-group-custom label {
            font-weight: bold;
            display: block;
            margin-bottom: 10px;
        }

        .password-input-group {
            position: relative;
            display: flex;
            align-items: center;
        }

        .password-input-group .form-control {
            padding-right: 40px;
            height: 50px;
            border-radius: 6px;
            background-color: #f7f7f7;
            border: 1px solid #ccc;
            font-family: monospace;
        }
        
        /* Icon button inside the input */
        .toggle-password {
            position: absolute;
            right: 10px;
            background: none;
            border: none;
            color: #6c757d;
            cursor: pointer;
            padding: 0;
        }

        /* Requirement Checklist */
        .requirement-checklist {
            list-style: none;
            padding-left: 0;
            margin-top: 30px;
            text-align: left;
        }

        .requirement-checklist li {
            font-size: 0.9rem;
            color: #6c757d;
            margin-bottom: 5px;
            display: flex;
            align-items: center;
            transition: color 0.3s;
        }

        .requirement-checklist li.met {
            color: #38c172;
            font-weight: 500;
        }

        .requirement-checklist li.unmet {
            color: #dc3545;
            font-weight: 500;
        }

        .requirement-icon {
            margin-right: 8px;
            font-size: 1rem;
            width: 20px;
            text-align: center;
        }
        
        /* Update Button */
        .btn-update {
            background-color: black;
            color: white;
            padding: 12px 30px;
            font-weight: bold;
            border-radius: 6px;
            margin-top: 50px;
            min-width: 120px;
            border: none;
            cursor: pointer;
            transition: background-color 0.2s;
        }
        
        .btn-update:hover {
            background-color: #333;
        }
        
        .btn-update:disabled {
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
            .header {
                flex-direction: column;
                text-align: center;
                padding: 10px;
            }
            
            .merchant-info {
                text-align: center;
                margin-top: 10px;
            }
            
            .page-title {
                margin-top: 120px;
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <div class="logo">BeU Delivery <span class="text-muted" style="font-weight: normal; font-size: 1rem;">for Merchants</span></div>
        <div class="merchant-info">
            <div class="merchant-name"><?php echo htmlspecialchars($merchant['store_name']); ?></div>
            <div class="merchant-address"><?php echo htmlspecialchars($merchant['store_address']); ?></div>
        </div>
    </div>

    <div class="page-content">
        <h1 class="page-title">BeU Account</h1>
        
        <h2 class="h5 fw-bold">Password</h2>
        <p class="password-description">
            Your password must be at least 8 characters long, and contain at least one digit and one non-digit character
        </p>

        <!-- Messages -->
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>

        <form method="POST" action="" id="passwordForm">
            <input type="hidden" name="update_password" value="1">
            
            <div class="form-group-custom">
                <label for="newPassword">New password</label>
                <div class="password-input-group">
                    <input type="password" class="form-control" id="newPassword" name="new_password" 
                           placeholder="Enter new password" required 
                           oninput="validatePassword()">
                    <button type="button" class="toggle-password" data-target="newPassword">
                        <i class="bi bi-eye-slash-fill"></i>
                    </button>
                </div>
            </div>

            <div class="form-group-custom">
                <label for="confirmPassword">Confirm new password</label>
                <div class="password-input-group">
                    <input type="password" class="form-control" id="confirmPassword" name="confirm_password" 
                           placeholder="Confirm new password" required 
                           oninput="validatePassword()">
                    <button type="button" class="toggle-password" data-target="confirmPassword">
                        <i class="bi bi-eye-slash-fill"></i>
                    </button>
                </div>
            </div>

            <ul class="requirement-checklist">
                <li id="reqLength"><span class="requirement-icon">•</span>Has at least 8 characters?</li>
                <li id="reqDigit"><span class="requirement-icon">•</span>Has one digit?</li>
                <li id="reqNonDigit"><span class="requirement-icon">•</span>Has one non-digit character?</li>
                <li id="reqMatch"><span class="requirement-icon">•</span>Passwords match?</li>
            </ul>

            <button type="submit" class="btn btn-update" id="updateBtn" disabled>Update</button>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    
    <script>
        // Toggle password visibility
        document.querySelectorAll('.toggle-password').forEach(button => {
            button.addEventListener('click', function() {
                const targetId = this.getAttribute('data-target');
                const targetInput = document.getElementById(targetId);
                const icon = this.querySelector('i');
                
                if (targetInput.type === 'password') {
                    targetInput.type = 'text';
                    icon.classList.remove('bi-eye-slash-fill');
                    icon.classList.add('bi-eye-fill');
                } else {
                    targetInput.type = 'password';
                    icon.classList.remove('bi-eye-fill');
                    icon.classList.add('bi-eye-slash-fill');
                }
            });
        });

        // Password validation
        function validatePassword() {
            const newPassword = document.getElementById('newPassword').value;
            const confirmPassword = document.getElementById('confirmPassword').value;
            const updateBtn = document.getElementById('updateBtn');
            
            // Check requirements
            const hasMinLength = newPassword.length >= 8;
            const hasDigit = /\d/.test(newPassword);
            const hasNonDigit = /[^0-9]/.test(newPassword);
            const passwordsMatch = newPassword === confirmPassword && newPassword.length > 0;
            
            // Update requirement indicators
            updateRequirement('reqLength', hasMinLength);
            updateRequirement('reqDigit', hasDigit);
            updateRequirement('reqNonDigit', hasNonDigit);
            updateRequirement('reqMatch', passwordsMatch);
            
            // Enable/disable update button
            const allRequirementsMet = hasMinLength && hasDigit && hasNonDigit && passwordsMatch;
            updateBtn.disabled = !allRequirementsMet;
            
            return allRequirementsMet;
        }
        
        function updateRequirement(elementId, isMet) {
            const element = document.getElementById(elementId);
            const icon = element.querySelector('.requirement-icon');
            
            if (isMet) {
                element.classList.remove('unmet');
                element.classList.add('met');
                icon.textContent = '✓';
            } else {
                element.classList.remove('met');
                element.classList.add('unmet');
                icon.textContent = '•';
            }
        }
        
        // Form submission validation
        document.getElementById('passwordForm').addEventListener('submit', function(e) {
            if (!validatePassword()) {
                e.preventDefault();
                alert('Please meet all password requirements before submitting.');
                return false;
            }
            return true;
        });
        
        // Initialize validation on page load
        document.addEventListener('DOMContentLoaded', function() {
            validatePassword();
        });
    </script>
</body>
</html>