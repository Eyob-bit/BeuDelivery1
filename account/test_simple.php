<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Simple Test Page</h1>";

// Test 1: Basic PHP
echo "<p>✅ PHP is working</p>";

// Test 2: Session
session_start();
echo "<p>✅ Session started</p>";

// Test 3: Database include
try {
    include "../includes/db.php";
    echo "<p>✅ Database include successful</p>";
    
    if ($conn) {
        echo "<p>✅ Database connection successful</p>";
    } else {
        echo "<p>❌ Database connection failed</p>";
    }
} catch (Exception $e) {
    echo "<p>❌ Database include failed: " . $e->getMessage() . "</p>";
}

// Test 4: Check session variables
echo "<p>User ID: " . ($_SESSION['user_id'] ?? 'Not set') . "</p>";
echo "<p>Merchant ID: " . ($_SESSION['merchant_id'] ?? 'Not set') . "</p>";

// Test 5: Try to include sidebar
try {
    if (isset($_SESSION['merchant_id'])) {
        $merchant_id = $_SESSION['merchant_id'];
        echo "<p>✅ Merchant ID available: $merchant_id</p>";
        
        // Try merchant query
        $merchant_sql = "SELECT m.*, u.first_name, u.last_name 
                         FROM merchants m 
                         JOIN users u ON m.user_id = u.id 
                         WHERE m.merchant_id = ?";
        $stmt = mysqli_prepare($conn, $merchant_sql);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "i", $merchant_id);
            mysqli_stmt_execute($stmt);
            $merchant_result = mysqli_stmt_get_result($stmt);
            $merchant = mysqli_fetch_assoc($merchant_result);
            
            if ($merchant) {
                echo "<p>✅ Merchant data found: " . htmlspecialchars($merchant['store_name'] ?? 'No name') . "</p>";
            } else {
                echo "<p>❌ No merchant data found for ID: $merchant_id</p>";
            }
        } else {
            echo "<p>❌ Failed to prepare merchant query</p>";
        }
    } else {
        echo "<p>❌ No merchant ID in session</p>";
    }
} catch (Exception $e) {
    echo "<p>❌ Merchant query failed: " . $e->getMessage() . "</p>";
}

echo "<p><a href='orders.php'>Test Orders Page</a></p>";
echo "<p><a href='reports.php'>Test Reports Page</a></p>";
echo "<p><a href='settings.php'>Test Settings Page</a></p>";
?>