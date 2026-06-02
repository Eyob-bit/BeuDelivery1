<?php
session_start();

// Check if user just completed signup or is logged in
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    header("Location: signup.php");
    exit();
}

// Set a flag to show this is a new signup
$_SESSION['just_signed_up'] = true;

// Clear the signup flag after 5 seconds (for safety)
header("Refresh: 2; url=login.php");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BeU Delivery - Signup Successful</title>
    <link href="https://fonts.googleapis.com/css2?family=Uber+Move+Text:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Uber Move Text', Arial, sans-serif;
            background: #fff;
            color: #000;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            padding: 20px;
        }
        .success-container {
            max-width: 500px;
            width: 100%;
        }
        .success-icon {
            font-size: 80px;
            color: #06C167;
            margin-bottom: 30px;
            animation: successAnimation 1s ease-in-out;
        }
        @keyframes successAnimation {
            0% { transform: scale(0.5); opacity: 0; }
            70% { transform: scale(1.1); }
            100% { transform: scale(1); opacity: 1; }
        }
        .success-title {
            font-size: 36px;
            margin-bottom: 20px;
            color: #000;
        }
        .success-message {
            font-size: 18px;
            color: #666;
            margin-bottom: 25px;
            line-height: 1.5;
        }
        .redirect-message {
            font-size: 16px;
            color: #888;
            margin-bottom: 30px;
        }
        .countdown {
            font-weight: bold;
            color: #06C167;
        }
        .user-name {
            color: #06C167;
            font-weight: bold;
        }
        .button-group {
            display: flex;
            flex-direction: column;
            gap: 15px;
            margin-top: 30px;
        }
        .button {
            padding: 16px 32px;
            font-size: 16px;
            font-weight: 500;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
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
            border: 1px solid #ddd;
        }
        .secondary-button:hover {
            background: #e8e8e8;
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
        .progress-step.completed {
            background: #06C167;
        }
    </style>
</head>
<body>
    <div class="success-container">
        <div class="progress-bar">
            <div class="progress-step completed"></div>
            <div class="progress-step completed"></div>
            <div class="progress-step completed"></div>
            <div class="progress-step completed"></div>
            <div class="progress-step completed"></div>
        </div>

        <div class="success-icon">✓</div>
        
        <h1 class="success-title">Welcome to BeU Delivery, <span class="user-name"><?php echo htmlspecialchars($_SESSION['user_name']); ?></span>!</h1>
        
        <p class="success-message">
            Your account has been successfully created and verified. 
            log in to start ordering!
        </p>



        <div class="button-group">
            <a href="login.php" class="button primary-button">
                Log In Now →
            </a>
        </div>
    </div>

    <script>
        // Countdown timer
        let seconds = 5;
        const countdownElement = document.getElementById('countdown');
        
        const countdown = setInterval(function() {
            seconds--;
            countdownElement.textContent = seconds;
            
            if (seconds <= 0) {
                clearInterval(countdown);
                // Optional: Auto-redirect if not already clicked
                // window.location.href = '../index.php';
            }
        }, 1000);

        // Optional: Redirect immediately if user clicks anywhere
        document.addEventListener('click', function() {
            window.location.href = '../index.php';
        });
    </script>
</body>
</html>