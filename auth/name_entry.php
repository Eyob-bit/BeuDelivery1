<?php
session_start();

// Check if user is coming from verification
if (!isset($_SESSION['signup_email']) || empty($_SESSION['signup_email'])) {
    header("Location: signup.php");
    exit();
}

$email = $_SESSION['signup_email'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BeU Delivery - What's your name?</title>
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
            justify-content: center;
            align-items: center;
            padding: 40px 20px;
        }
        .container {
            max-width: 450px;
            width: 100%;
            text-align: center;
        }
        .title {
            font-size: 28px;
            margin-bottom: 10px;
        }
        .subtitle {
            color: #666;
            margin-bottom: 30px;
            font-size: 16px;
        }
        .form-group {
            margin-bottom: 20px;
            text-align: left;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #333;
        }
        .input-field {
            width: 100%;
            padding: 15px;
            border: 1px solid #c0c0c0;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s;
        }
        .input-field:focus {
            outline: none;
            border-color: #000;
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
        }
        .primary-button {
            background: #000;
            color: #fff;
        }
        .primary-button:hover {
            background: #333;
        }
        .back-button {
            background: #f6f6f6;
            color: #333;
            margin-top: 15px;
        }
        .back-button:hover {
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
                <div class="progress-step active"></div>
                <div class="progress-step"></div>
                <div class="progress-step"></div>
                <div class="progress-step"></div>
            </div>

            <h1 class="title">What's your name?</h1>
            <p class="subtitle">Let us know how to properly address you</p>

            <form action="terms_accept.php" method="POST">
                <div class="form-group">
                    <label for="first_name">First name</label>
                    <input type="text" id="first_name" name="first_name" 
                           class="input-field" placeholder="Enter first name" required>
                </div>

                <div class="form-group">
                    <label for="last_name">Last name</label>
                    <input type="text" id="last_name" name="last_name" 
                           class="input-field" placeholder="Enter last name" required>
                </div>

                <button type="submit" class="button primary-button">
                    Next →
                </button>

                <button type="button" class="button back-button" onclick="window.history.back()">
                    ← Back
                </button>
            </form>
        </div>
    </main>
</body>
</html>