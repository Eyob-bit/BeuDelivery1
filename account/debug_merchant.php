<?php
// debug_merchant.php
session_start();
include "../includes/db.php";

echo "<h2>Merchant Debug Information</h2>";
echo "<pre>";

// 1. Check session
echo "=== SESSION DATA ===\n";
print_r($_SESSION);
echo "\n";

// 2. Check merchant from database using user_id
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    echo "=== DATABASE CHECK (by user_id: $user_id) ===\n";
    
    $sql = "SELECT merchant_id, status, store_name FROM merchants WHERE user_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $merchant = mysqli_fetch_assoc($result);
    
    if ($merchant) {
        print_r($merchant);
        echo "\nStatus exact value: '" . $merchant['status'] . "'\n";
        echo "Status length: " . strlen($merchant['status']) . "\n";
        
        // Check exact comparison
        echo "\n=== STATUS COMPARISON ===\n";
        echo "Is status == 'active'? " . ($merchant['status'] == 'active' ? 'YES' : 'NO') . "\n";
        echo "Is status === 'active'? " . ($merchant['status'] === 'active' ? 'YES' : 'NO') . "\n";
        echo "Is status == 'under_review'? " . ($merchant['status'] == 'under_review' ? 'YES' : 'NO') . "\n";
        
        // Check for whitespace
        echo "Status with trim: '" . trim($merchant['status']) . "'\n";
        
    } else {
        echo "No merchant found for user_id: $user_id\n";
    }
}

// 3. Check merchant by merchant_id in session
if (isset($_SESSION['merchant_id'])) {
    $merchant_id = $_SESSION['merchant_id'];
    echo "\n=== DATABASE CHECK (by merchant_id: $merchant_id) ===\n";
    
    $sql = "SELECT merchant_id, status, store_name FROM merchants WHERE merchant_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $merchant_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $merchant = mysqli_fetch_assoc($result);
    
    if ($merchant) {
        print_r($merchant);
    } else {
        echo "No merchant found with merchant_id: $merchant_id\n";
    }
}

echo "</pre>";
?>