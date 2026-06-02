<?php
// debug_user.php - Put this in your root folder
session_start();
include "includes/db.php";

echo "<h1>User Type Debug</h1>";
echo "<pre>";

// Get email from session or use a test email
if (isset($_GET['email'])) {
    $test_email = $_GET['email'];
} else {
    // Use the email of the merchant you're trying to login with
    $test_email = "biniam33@gmail.com"; // ← CHANGE THIS TO YOUR MERCHANT'S EMAIL
}

echo "Testing email: $test_email\n\n";

// Check user table
$user_query = mysqli_query($conn, "
    SELECT u.*, 
           GROUP_CONCAT(DISTINCT r.name SEPARATOR ', ') as roles,
           COUNT(m.merchant_id) as merchant_count
    FROM users u
    LEFT JOIN user_roles ur ON u.id = ur.user_id
    LEFT JOIN roles r ON ur.role_id = r.id
    LEFT JOIN merchants m ON u.id = m.user_id
    WHERE u.email = '$test_email'
    GROUP BY u.id
    LIMIT 1
");

if ($user_query && mysqli_num_rows($user_query) > 0) {
    $user = mysqli_fetch_assoc($user_query);
    echo "=== USER INFORMATION ===\n";
    echo "ID: " . $user['id'] . "\n";
    echo "Email: " . $user['email'] . "\n";
    echo "First Name: " . $user['first_name'] . "\n";
    echo "Last Name: " . $user['last_name'] . "\n";
    echo "Phone: " . $user['phone'] . "\n";
    echo "User Type: " . $user['user_type'] . "\n";
    echo "Roles: " . ($user['roles'] ?: 'None') . "\n";
    echo "Has Merchant Record: " . ($user['merchant_count'] > 0 ? 'YES' : 'NO') . "\n\n";
    
    // Check merchant table separately
    $merchant_query = mysqli_query($conn, "
        SELECT m.*, mr.status as review_status
        FROM merchants m
        LEFT JOIN merchant_reviews mr ON m.merchant_id = mr.merchant_id
        WHERE m.user_id = " . $user['id'] . " 
        ORDER BY m.created_at DESC
    ");
    
    if (mysqli_num_rows($merchant_query) > 0) {
        echo "=== MERCHANT INFORMATION ===\n";
        while ($merchant = mysqli_fetch_assoc($merchant_query)) {
            echo "Merchant ID: " . $merchant['merchant_id'] . "\n";
            echo "Store Name: " . $merchant['store_name'] . "\n";
            echo "Business Type: " . $merchant['business_type'] . "\n";
            echo "Status: " . $merchant['status'] . "\n";
            echo "Review Status: " . ($merchant['review_status'] ?: 'Not reviewed') . "\n";
            echo "Created: " . $merchant['created_at'] . "\n";
            echo "\n";
        }
        
        echo "=== EXPECTED REDIRECTION ===\n";
        $status_check = mysqli_query($conn, 
            "SELECT status FROM merchants WHERE user_id = " . $user['id'] . " LIMIT 1");
        $status_row = mysqli_fetch_assoc($status_check);
        
        switch ($status_row['status']) {
            case 'active':
                echo "➡ Should redirect to: merchant/merchant_dashboard.php\n";
                break;
            case 'under_review':
                echo "➡ Should redirect to: merchant/accountunderreview.php\n";
                break;
            case 'setup':
                echo "➡ Should redirect to: merchant/setup.php\n";
                break;
            case 'inactive':
                echo "➡ Should redirect to: merchant/account_disabled.php\n";
                break;
            default:
                echo "➡ Should redirect to: merchant/getStarted.php\n";
        }
    } else {
        echo "=== NO MERCHANT RECORD FOUND ===\n";
        echo "User is marked as '{$user['user_type']}' but has no merchant record.\n";
        echo "This might be why they're being redirected to users page.\n";
    }
    
} else {
    echo "=== USER NOT FOUND ===\n";
    echo "No user found with email: $test_email\n";
    echo "Make sure you're using the correct email address.\n";
}

echo "\n=== SESSION DATA ===\n";
print_r($_SESSION);

echo "\n=== LOGIN_VERIFY.PHP REDIRECTION LOGIC ===\n";
echo "Current logic checks:\n";
echo "1. user_type = 'merchant' → merchant section\n";
echo "2. user_type = 'admin' → admin section\n"; 
echo "3. user_type = 'customer' → customer section\n";
echo "4. If no user_type, checks roles: admin, restaurant, delivery_man\n";
echo "5. Default: user/home.php\n";

echo "</pre>";

// Add test form
echo '<h2>Test with Different Email</h2>';
echo '<form method="GET">
    <input type="email" name="email" placeholder="Enter email to test" style="padding: 8px; width: 300px;">
    <button type="submit" style="padding: 8px 16px;">Test Email</button>
</form>';

// Quick links to test common emails
echo '<h3>Quick Test Links:</h3>';
echo '<ul>';
echo '<li><a href="?email=biniam33@gmail.com">Test biniam33@gmail.com</a></li>';
echo '<li><a href="?email=eyobbehailu33@gmail.com">Test eyobbehailu33@gmail.com</a></li>';
echo '<li><a href="?email=eyobbehailu331@gmail.com">Test eyobbehailu331@gmail.com</a></li>';
echo '<li><a href="?email=eyob.behailu@haramaya.edu.et">Test eyob.behailu@haramaya.edu.et</a></li>';
echo '</ul>';
?>