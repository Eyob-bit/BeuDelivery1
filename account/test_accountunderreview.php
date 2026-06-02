<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Testing accountunderreview.php Requirements</h2><pre>";

include "../includes/db.php";

echo "Step 1: Database connection... ";
if ($conn) {
    echo "✓ Connected\n\n";
} else {
    die("❌ Failed: " . mysqli_connect_error());
}

echo "Step 2: Checking merchant_reviews table... ";
$check = mysqli_query($conn, "SHOW TABLES LIKE 'merchant_reviews'");
if (mysqli_num_rows($check) > 0) {
    echo "✓ Table exists\n";
} else {
    echo "⚠️ Table missing (will be auto-created)\n";
}

echo "\nStep 3: Checking session variables...\n";
session_start();
if (isset($_SESSION['user_id'])) {
    echo "  ✓ user_id: {$_SESSION['user_id']}\n";
} else {
    echo "  ⚠️ user_id not set\n";
}

if (isset($_SESSION['merchant_id'])) {
    echo "  ✓ merchant_id: {$_SESSION['merchant_id']}\n";
    
    $merchant_id = $_SESSION['merchant_id'];
    
    echo "\nStep 4: Checking merchant data...\n";
    $merchant_sql = "SELECT m.*, u.email, u.first_name, u.last_name 
                     FROM merchants m 
                     JOIN users u ON m.user_id = u.id 
                     WHERE m.merchant_id = '$merchant_id'";
    $merchant_result = mysqli_query($conn, $merchant_sql);
    
    if ($merchant_result && mysqli_num_rows($merchant_result) > 0) {
        $merchant = mysqli_fetch_assoc($merchant_result);
        echo "  ✓ Merchant found: {$merchant['store_name']}\n";
        echo "  ✓ Status: {$merchant['status']}\n";
        echo "  ✓ Owner: {$merchant['first_name']} {$merchant['last_name']}\n";
    } else {
        echo "  ❌ Merchant not found\n";
    }
} else {
    echo "  ⚠️ merchant_id not set\n";
}

echo "\n</pre>";

if (isset($_SESSION['merchant_id'])) {
    echo "<p><a href='accountunderreview.php' style='padding:10px 20px;background:black;color:white;text-decoration:none;border-radius:5px;'>Test accountunderreview.php Now</a></p>";
} else {
    echo "<p style='color:red;'>You need to be logged in as a merchant to test this page</p>";
    echo "<p><a href='../merchant/getStarted.php'>Go to Merchant Signup</a></p>";
}
?>
