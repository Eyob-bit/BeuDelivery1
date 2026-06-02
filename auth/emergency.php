<?php
// emergency.php - CORRECTED VERSION
session_start();

echo "<h1>Emergency Merchant Login</h1>";

// Choose which merchant to login as
$merchants = [
    [
        'user_id' => 1,
        'email' => 'eyobbehailu33@gmail.com',
        'name' => 'Eyob Behailu',
        'merchant_id' => 1,
        'store' => 'Eyobs',
        'status' => 'active'
    ],
    [
        'user_id' => 28,
        'email' => 'biniam33@gmail.com', 
        'name' => 'Biniam Behailu',
        'merchant_id' => 7,
        'store' => 'Absiniya',
        'status' => 'under_review'
    ]
];

// If merchant ID is specified
if (isset($_GET['merchant_id'])) {
    $merchant_id = $_GET['merchant_id'];
    $merchant = null;
    
    foreach ($merchants as $m) {
        if ($m['merchant_id'] == $merchant_id) {
            $merchant = $m;
            break;
        }
    }
    
    if ($merchant) {
        // Set session
        $_SESSION['user_id'] = $merchant['user_id'];
        $_SESSION['user_email'] = $merchant['email'];
        $_SESSION['user_name'] = $merchant['name'];
        $_SESSION['user_type'] = 'merchant';
        $_SESSION['merchant_id'] = $merchant['merchant_id'];
        $_SESSION['logged_in'] = true;
        
        echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; margin: 15px 0;'>";
        echo "<h3 style='color: #155724;'>✓ Login Successful!</h3>";
        echo "<p><strong>Merchant:</strong> {$merchant['store']}</p>";
        echo "<p><strong>Email:</strong> {$merchant['email']}</p>";
        echo "<p><strong>Merchant ID:</strong> {$merchant['merchant_id']}</p>";
        echo "<p><strong>Status:</strong> {$merchant['status']}</p>";
        echo "</div>";
        
        // Determine correct redirect path
        $redirect_page = ($merchant['status'] == 'under_review') 
            ? 'accountunderreview.php' 
            : 'merchant_dashboard.php';
        
        // CORRECTED PATHS:
        // Since emergency.php is in /auth/ folder, we need to go up one level then to merchant
        $correct_path = "../account/{$redirect_page}";
        
        echo "<h3>Redirecting Options:</h3>";
        echo "<div style='margin: 20px 0;'>";
        
        // Option 1: JavaScript redirect
        echo "<p><strong>Auto-redirect in 3 seconds...</strong></p>";
        echo "<script>
            setTimeout(function() {
                window.location.href = '{$correct_path}';
            }, 3000);
        </script>";
        
        // Option 2: Direct link with CORRECT path
        echo "<p><a href='{$correct_path}' 
              style='display: inline-block; background: #28a745; color: white; padding: 12px 24px; 
                     text-decoration: none; border-radius: 5px; font-weight: bold; margin: 10px 0;'>
              🚀 Click here to go to Merchant Dashboard
              </a></p>";
        
        // Option 3: Debug links
        echo "<p><small>Debug links:</small><br>";
        echo "<a href='../merchant/accountunderreview.php' style='color: #666;'>accountunderreview.php</a> | ";
        echo "<a href='../merchant/merchant_dashboard.php' style='color: #666;'>merchant_dashboard.php</a>";
        echo "</p>";
        
        echo "</div>";
        
    } else {
        echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px;'>";
        echo "<h3 style='color: #721c24;'>❌ Merchant not found!</h3>";
        echo "</div>";
    }
    
} else {
    // Show merchant selection
    echo "<h3>Select Merchant to Login:</h3>";
    echo "<div style='display: grid; gap: 15px; max-width: 500px;'>";
    
    foreach ($merchants as $merchant) {
        $status_color = ($merchant['status'] == 'active') ? 'green' : 'orange';
        
        echo "<div style='padding: 15px; border: 1px solid #ddd; border-radius: 8px;'>";
        echo "<h4 style='margin: 0 0 10px 0;'>{$merchant['store']}</h4>";
        echo "<p style='margin: 5px 0;'><strong>Email:</strong> {$merchant['email']}</p>";
        echo "<p style='margin: 5px 0;'><strong>Status:</strong> 
              <span style='color: {$status_color};'>{$merchant['status']}</span></p>";
        echo "<p style='margin: 5px 0;'><strong>Will redirect to:</strong> " . 
             (($merchant['status'] == 'under_review') ? 'Under Review Dashboard' : 'Active Dashboard') . "</p>";
        
        echo "<a href='?merchant_id={$merchant['merchant_id']}' 
              style='display: inline-block; background: #007bff; color: white; padding: 8px 16px; 
                     text-decoration: none; border-radius: 4px; margin-top: 10px;'>
              Login as this Merchant
              </a>";
        echo "</div>";
    }
    
    echo "</div>";
    
    // Add path debug info
    echo "<hr>";
    echo "<h4>Debug Info:</h4>";
    echo "<p>Current URL: " . $_SERVER['REQUEST_URI'] . "</p>";
    echo "<p>Script location: " . __FILE__ . "</p>";
}
?>