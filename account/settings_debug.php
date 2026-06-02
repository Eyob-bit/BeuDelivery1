<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Settings Debug</h1>";

try {
    session_start();
    echo "✅ Session started<br>";
    
    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        echo "❌ No user logged in<br>";
        header("Location: ../auth/login.php");
        exit();
    }
    echo "✅ User logged in: " . $_SESSION['user_id'] . "<br>";

    include "../includes/db.php";
    echo "✅ Database included<br>";

    // Get or set merchant_id
    if (!isset($_SESSION['merchant_id'])) {
        echo "⚠️ No merchant_id in session, trying to find one...<br>";
        $user_id = $_SESSION['user_id'];
        $merchant_sql = "SELECT merchant_id FROM merchants WHERE user_id = ? LIMIT 1";
        $stmt = mysqli_prepare($conn, $merchant_sql);
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if ($row = mysqli_fetch_assoc($result)) {
            $_SESSION['merchant_id'] = $row['merchant_id'];
            echo "✅ Found merchant_id: " . $row['merchant_id'] . "<br>";
        } else {
            echo "❌ No merchant found<br>";
            header("Location: ../merchant/getStarted.php");
            exit();
        }
    }

    $merchant_id = $_SESSION['merchant_id'];
    echo "✅ Using merchant_id: $merchant_id<br>";

    // Get merchant details for sidebar
    $merchant_sql = "SELECT m.*, u.first_name, u.last_name 
                     FROM merchants m 
                     JOIN users u ON m.user_id = u.id 
                     WHERE m.merchant_id = ?";
    $stmt = mysqli_prepare($conn, $merchant_sql);
    mysqli_stmt_bind_param($stmt, "i", $merchant_id);
    mysqli_stmt_execute($stmt);
    $merchant_result = mysqli_stmt_get_result($stmt);
    $merchant = mysqli_fetch_assoc($merchant_result);

    if (!$merchant) {
        echo "❌ Merchant not found<br>";
        header("Location: ../merchant/getStarted.php");
        exit();
    }
    echo "✅ Merchant found: " . htmlspecialchars($merchant['store_name']) . "<br>";

    // Test merchant details query
    $merchant_details_sql = "SELECT m.*, md.*, u.email, u.phone as user_phone 
                             FROM merchants m
                             LEFT JOIN merchant_details md ON m.merchant_id = md.merchant_id
                             JOIN users u ON m.user_id = u.id
                             WHERE m.merchant_id = ?";
    $stmt = mysqli_prepare($conn, $merchant_details_sql);
    mysqli_stmt_bind_param($stmt, "i", $merchant_id);
    mysqli_stmt_execute($stmt);
    $merchant_details_result = mysqli_stmt_get_result($stmt);
    $merchant_details = mysqli_fetch_assoc($merchant_details_result);
    echo "✅ Merchant details query successful<br>";

    // Test store images query
    $images_sql = "SELECT * FROM store_images WHERE merchant_id = ? ORDER BY display_order, created_at";
    $stmt = mysqli_prepare($conn, $images_sql);
    mysqli_stmt_bind_param($stmt, "i", $merchant_id);
    mysqli_stmt_execute($stmt);
    $images_result = mysqli_stmt_get_result($stmt);
    echo "✅ Store images query successful<br>";

    // Test store hours query
    $hours_sql = "SELECT * FROM store_hours WHERE merchant_id = ? ORDER BY day_of_week";
    $stmt = mysqli_prepare($conn, $hours_sql);
    mysqli_stmt_bind_param($stmt, "i", $merchant_id);
    mysqli_stmt_execute($stmt);
    $hours_result = mysqli_stmt_get_result($stmt);
    echo "✅ Store hours query successful<br>";

    echo "<h2>All checks passed! The settings page should work.</h2>";
    echo "<a href='settings.php'>Try Settings Page</a>";

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
    echo "File: " . $e->getFile() . "<br>";
    echo "Line: " . $e->getLine() . "<br>";
}
?>