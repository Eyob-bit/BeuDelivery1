<?php
// simple_debug.php
echo "<h1>Debug Script Running</h1>";

// Include database
require_once "includes/db.php";

// Test connection
if (!$conn) {
    die("Database connection failed!");
} else {
    echo "Database connected successfully!<br>";
}

$test_email = "eyobbehailu33@gmail.com";
echo "Testing email: $test_email<br><br>";

// 1. Check user
$sql = "SELECT * FROM users WHERE email = '$test_email' LIMIT 1";
echo "SQL Query 1: $sql<br>";

$result = mysqli_query($conn, $sql);
if (!$result) {
    echo "Query error: " . mysqli_error($conn) . "<br>";
} elseif (mysqli_num_rows($result) == 0) {
    echo "No user found with email: $test_email<br>";
} else {
    $user = mysqli_fetch_assoc($result);
    echo "<h3>User Found:</h3>";
    echo "<pre>";
    print_r($user);
    echo "</pre>";
    
    $user_id = $user['id'];
    
    // 2. Check roles
    $sql2 = "SELECT r.name FROM user_roles ur JOIN roles r ON ur.role_id = r.id WHERE ur.user_id = $user_id";
    echo "<br>SQL Query 2: $sql2<br>";
    
    $result2 = mysqli_query($conn, $sql2);
    if (!$result2) {
        echo "Roles query error: " . mysqli_error($conn) . "<br>";
    } else {
        $roles = [];
        while ($row = mysqli_fetch_assoc($result2)) {
            $roles[] = $row['name'];
        }
        echo "User Roles: " . (count($roles) > 0 ? implode(', ', $roles) : 'None') . "<br>";
    }
    
    // 3. Check merchants
    $sql3 = "SELECT * FROM merchants WHERE user_id = $user_id";
    echo "<br>SQL Query 3: $sql3<br>";
    
    $result3 = mysqli_query($conn, $sql3);
    if (!$result3) {
        echo "Merchant query error: " . mysqli_error($conn) . "<br>";
    } elseif (mysqli_num_rows($result3) == 0) {
        echo "No merchant records found for this user.<br>";
    } else {
        echo "<h3>Merchant Records:</h3>";
        while ($merchant = mysqli_fetch_assoc($result3)) {
            echo "<pre>";
            print_r($merchant);
            echo "</pre>";
        }
    }
}

// Close connection
mysqli_close($conn);
?>