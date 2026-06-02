<?php
session_start();
include "../includes/db.php";

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Simple validation
    if (empty($first_name) || empty($last_name) || empty($email) || empty($password)) {
        $error = 'Please fill in all required fields';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters';
    } else {
        // Check if email exists
        $check = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
        $check->execute([$email]);
        
        if ($check->rowCount() > 0) {
            $error = 'Email already registered';
        } else {
            // Hash password
            $hashed_password = password_hash($password, PASSWORD_BCRYPT);
            
            // Insert user
            $stmt = $conn->prepare("INSERT INTO users (first_name, last_name, email, phone, password_hash, user_type) 
                                    VALUES (?, ?, ?, ?, ?, 'merchant')");
            
            if ($stmt->execute([$first_name, $last_name, $email, $phone, $hashed_password])) {
                $user_id = $conn->lastInsertId();
                $_SESSION['user_id'] = $user_id;
                $success = 'Registration successful!';
                
                // Redirect to next step after 2 seconds
                header("refresh:2;url=setup.php");
            } else {
                $error = 'Registration failed. Please try again.';
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
    <title>Sign Up - BeU Delivery</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .container { max-width: 500px; margin-top: 50px; }
        .alert { margin-top: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <h2 class="mb-4">Create Merchant Account</h2>
        
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="row mb-3">
                <div class="col-md-6">
                    <label>First Name *</label>
                    <input type="text" name="first_name" class="form-control" required>
                </div>
                <div class="col-md-6">
                    <label>Last Name *</label>
                    <input type="text" name="last_name" class="form-control" required>
                </div>
            </div>
            
            <div class="mb-3">
                <label>Email *</label>
                <input type="email" name="email" class="form-control" required>
            </div>
            
            <div class="mb-3">
                <label>Phone Number</label>
                <input type="tel" name="phone" class="form-control">
            </div>
            
            <div class="mb-3">
                <label>Password * (min 8 characters)</label>
                <input type="password" name="password" class="form-control" required minlength="8">
            </div>
            
            <div class="mb-3">
                <label>Confirm Password *</label>
                <input type="password" name="confirm_password" class="form-control" required>
            </div>
            
            <button type="submit" class="btn btn-primary w-100">Sign Up</button>
        </form>
        
        <p class="mt-3">Already have an account? <a href="login.php">Login here</a></p>
    </div>
</body>
</html>