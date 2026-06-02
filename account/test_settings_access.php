<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Settings Page Access Test</h1>";

// Test 1: Check if user is logged in
echo "<h3>1. Session Check:</h3>";
if (isset($_SESSION['user_id'])) {
    echo "✅ User ID: " . $_SESSION['user_id'] . "<br>";
} else {
    echo "❌ No user logged in<br>";
    echo "<a href='../auth/login.php'>Login here</a><br>";
    exit();
}

// Test 2: Database connection
echo "<h3>2. Database Connection:</h3>";
include "../includes/db.php";
if ($conn) {
    echo "✅ Database connected<br>";
} else {
    echo "❌ Database connection failed<br>";
    exit();
}

// Test 3: Check merchant_id
echo "<h3>3. Merchant ID Check:</h3>";
if (isset($_SESSION['merchant_id'])) {
    echo "✅ Merchant ID in session: " . $_SESSION['merchant_id'] . "<br>";
    $merchant_id = $_SESSION['merchant_id'];
} else {
    echo "❌ No merchant_id in session. Trying to find one...<br>";
    
    $user_id = $_SESSION['user_id'];
    $merchant_sql = "SELECT merchant_id FROM merchants WHERE user_id = ? LIMIT 1";
    $stmt = mysqli_prepare($conn, $merchant_sql);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($row = mysqli_fetch_assoc($result)) {
        $_SESSION['merchant_id'] = $row['merchant_id'];
        $merchant_id = $row['merchant_id'];
        echo "✅ Found merchant ID: $merchant_id<br>";
    } else {
        echo "❌ No merchant found for user. <a href='debug_merchant_login.php'>Debug here</a><br>";
        exit();
    }
}

// Test 4: Get merchant details
echo "<h3>4. Merchant Details:</h3>";
$merchant_sql = "SELECT m.*, u.first_name, u.last_name 
                 FROM merchants m 
                 JOIN users u ON m.user_id = u.id 
                 WHERE m.merchant_id = ?";
$stmt = mysqli_prepare($conn, $merchant_sql);
mysqli_stmt_bind_param($stmt, "i", $merchant_id);
mysqli_stmt_execute($stmt);
$merchant_result = mysqli_stmt_get_result($stmt);
$merchant = mysqli_fetch_assoc($merchant_result);

if ($merchant) {
    echo "✅ Merchant found: " . htmlspecialchars($merchant['store_name']) . "<br>";
    echo "Status: " . $merchant['status'] . "<br>";
} else {
    echo "❌ Merchant details not found<br>";
    exit();
}

// Test 5: Check store_images table
echo "<h3>5. Store Images Table:</h3>";
$table_check = mysqli_query($conn, "SHOW TABLES LIKE 'store_images'");
if (mysqli_num_rows($table_check) > 0) {
    echo "✅ store_images table exists<br>";
    
    // Check for existing images
    $images_sql = "SELECT COUNT(*) as count FROM store_images WHERE merchant_id = ?";
    $stmt = mysqli_prepare($conn, $images_sql);
    mysqli_stmt_bind_param($stmt, "i", $merchant_id);
    mysqli_stmt_execute($stmt);
    $images_result = mysqli_stmt_get_result($stmt);
    $images_count = mysqli_fetch_assoc($images_result)['count'];
    
    echo "Current images for this merchant: $images_count<br>";
} else {
    echo "❌ store_images table missing. <a href='../database/add_store_images_table.php'>Create it</a><br>";
}

echo "<h3>6. Test Links:</h3>";
echo "<a href='settings.php' target='_blank'>🔗 Open Settings Page</a><br>";
echo "<a href='debug_merchant_login.php'>🔧 Debug Merchant Login</a><br>";
echo "<a href='merchant_dashboard.php'>🏠 Dashboard</a><br>";

echo "<h3>7. Current Session Data:</h3>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";
?>