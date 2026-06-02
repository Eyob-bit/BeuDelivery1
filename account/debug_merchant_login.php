<?php
session_start();
include "../includes/db.php";

echo "<h2>Merchant Login Debug</h2>";

// Check session
echo "<h3>Session Info:</h3>";
echo "User ID: " . ($_SESSION['user_id'] ?? 'Not set') . "<br>";
echo "Merchant ID: " . ($_SESSION['merchant_id'] ?? 'Not set') . "<br>";

if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    
    // Check user details
    echo "<h3>User Details:</h3>";
    $user_sql = "SELECT * FROM users WHERE id = ?";
    $stmt = mysqli_prepare($conn, $user_sql);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $user_result = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($user_result);
    
    if ($user) {
        echo "Name: " . $user['first_name'] . " " . $user['last_name'] . "<br>";
        echo "Email: " . $user['email'] . "<br>";
        echo "Phone: " . $user['phone'] . "<br>";
        echo "User Type: " . $user['user_type'] . "<br>";
        echo "Is Active: " . ($user['is_active'] ? 'Yes' : 'No') . "<br>";
    } else {
        echo "User not found!<br>";
    }
    
    // Check merchant record
    echo "<h3>Merchant Record:</h3>";
    $merchant_sql = "SELECT * FROM merchants WHERE user_id = ?";
    $stmt = mysqli_prepare($conn, $merchant_sql);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $merchant_result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($merchant_result) > 0) {
        $merchant = mysqli_fetch_assoc($merchant_result);
        echo "Merchant ID: " . $merchant['merchant_id'] . "<br>";
        echo "Store Name: " . $merchant['store_name'] . "<br>";
        echo "Status: " . $merchant['status'] . "<br>";
        echo "Created: " . $merchant['created_at'] . "<br>";
    } else {
        echo "No merchant record found for this user!<br>";
        echo "<br><strong>This is why you're being redirected to getStarted.php</strong><br>";
        
        // Show all merchants in database
        echo "<h3>All Merchants in Database:</h3>";
        $all_merchants_sql = "SELECT m.merchant_id, m.store_name, m.status, u.first_name, u.last_name, u.email 
                              FROM merchants m 
                              JOIN users u ON m.user_id = u.id 
                              ORDER BY m.merchant_id";
        $all_result = mysqli_query($conn, $all_merchants_sql);
        
        if (mysqli_num_rows($all_result) > 0) {
            echo "<table border='1' style='border-collapse: collapse;'>";
            echo "<tr><th>Merchant ID</th><th>Store Name</th><th>Owner</th><th>Email</th><th>Status</th><th>Action</th></tr>";
            while ($row = mysqli_fetch_assoc($all_result)) {
                echo "<tr>";
                echo "<td>" . $row['merchant_id'] . "</td>";
                echo "<td>" . $row['store_name'] . "</td>";
                echo "<td>" . $row['first_name'] . " " . $row['last_name'] . "</td>";
                echo "<td>" . $row['email'] . "</td>";
                echo "<td>" . $row['status'] . "</td>";
                echo "<td><a href='?switch_to=" . $row['merchant_id'] . "'>Switch to this merchant</a></td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "No merchants found in database!<br>";
        }
    }
} else {
    echo "No user logged in. <a href='../auth/login.php'>Login here</a>";
}

// Handle merchant switching
if (isset($_GET['switch_to'])) {
    $merchant_id = intval($_GET['switch_to']);
    
    // Get the user_id for this merchant
    $switch_sql = "SELECT user_id FROM merchants WHERE merchant_id = ?";
    $stmt = mysqli_prepare($conn, $switch_sql);
    mysqli_stmt_bind_param($stmt, "i", $merchant_id);
    mysqli_stmt_execute($stmt);
    $switch_result = mysqli_stmt_get_result($stmt);
    
    if ($switch_row = mysqli_fetch_assoc($switch_result)) {
        $_SESSION['user_id'] = $switch_row['user_id'];
        $_SESSION['merchant_id'] = $merchant_id;
        echo "<br><strong>Switched to merchant ID: $merchant_id</strong><br>";
        echo "<a href='merchant_dashboard.php'>Go to Dashboard</a><br>";
    }
}

echo "<br><br><a href='merchant_dashboard.php'>Try Dashboard</a> | <a href='../auth/login.php'>Login</a>";
?>