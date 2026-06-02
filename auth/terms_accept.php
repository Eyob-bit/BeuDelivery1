<?php
session_start();

// Check if user is coming from name entry
if (!isset($_SESSION['signup_email']) || empty($_SESSION['signup_email'])) {
    header("Location: signup.php");
    exit();
}

// Save name if coming from previous step
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['first_name'])) {
    $_SESSION['first_name'] = $_POST['first_name'];
    $_SESSION['last_name'] = $_POST['last_name'];
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BeU Delivery - Terms & Privacy</title>
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
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 40px 20px;
            text-align: center;
        }
        .container {
            max-width: 500px;
            width: 100%;
        }
        .title {
            font-size: 28px;
            margin-bottom: 30px;
        }
        .terms-box {
            text-align: left;
            background: #f9f9f9;
            padding: 25px;
            border-radius: 12px;
            margin-bottom: 30px;
            border: 1px solid #eee;
            max-height: 300px;
            overflow-y: auto;
        }
        .terms-text {
            line-height: 1.6;
            color: #333;
        }
        .terms-text h3 {
            margin: 20px 0 10px;
            font-size: 18px;
        }
        .terms-text p {
            margin-bottom: 15px;
            font-size: 14px;
        }
        .checkbox-group {
            display: flex;
            align-items: flex-start;
            margin: 25px 0;
            text-align: left;
            gap: 15px;
        }
        .checkbox-group input[type="checkbox"] {
            width: 22px;
            height: 22px;
            margin-top: 3px;
            cursor: pointer;
        }
        .checkbox-group label {
            font-size: 15px;
            line-height: 1.5;
            color: #333;
            cursor: pointer;
        }
        .button {
            width: 100%;
            padding: 16px;
            font-size: 16px;
            font-weight: 500;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            margin-top: 10px;
            transition: all 0.3s;
        }
        .primary-button {
            background: #000;
            color: #fff;
        }
        .primary-button:hover {
            background: #333;
        }
        .primary-button:disabled {
            background: #ccc;
            cursor: not-allowed;
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
                <div class="progress-step"></div>
                <div class="progress-step active"></div>
                <div class="progress-step"></div>
            </div>

            <h1 class="title">Accept BeU Delivery's Terms & Review Privacy Notice</h1>

            <div class="terms-box">
                <div class="terms-text">
                    <h3>Terms of Use</h3>
                    <p>By using BeU Delivery, you agree to our Terms of Use. These terms govern your use of our services, including ordering food, delivery, and payment processing.</p>
                    
                    <h3>Privacy Notice</h3>
                    <p>We collect and use your personal information to provide, improve, and personalize our services. This includes your name, contact information, order history, and location data.</p>
                    
                    <h3>Age Requirement</h3>
                    <p>You must be at least 18 years old to create an account and use our services. By creating an account, you confirm that you meet this age requirement.</p>
                    
                    <h3>Account Responsibility</h3>
                    <p>You are responsible for maintaining the confidentiality of your account credentials and for all activities that occur under your account.</p>
                    
                    <h3>Service Changes</h3>
                    <p>BeU Delivery reserves the right to modify, suspend, or discontinue any aspect of the service at any time without prior notice.</p>
                </div>
            </div>

            <form action="process_signup.php" method="POST" id="termsForm">
                <div class="checkbox-group">
                    <input type="checkbox" id="agree_terms" name="agree_terms" value="1" required>
                    <label for="agree_terms">
                        By selecting "I agree" below, I have reviewed and agree to the Terms of Use 
                        and acknowledge the Privacy Notice. I am at least 18 years of age.
                    </label>
                </div>

                <button type="submit" id="submitBtn" class="button primary-button" disabled>
                    I agree
                </button>

                <button type="button" class="button back-button" onclick="window.history.back()">
                    ← Back
                </button>
            </form>
        </div>
    </main>

    <script>
        document.getElementById('agree_terms').addEventListener('change', function() {
            document.getElementById('submitBtn').disabled = !this.checked;
        });
    </script>
</body>
</html>