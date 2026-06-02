<?php
// Setup Cart and Ordering System
include __DIR__ . "/../includes/db.php";

echo "<h1>Setting Up Cart and Ordering System</h1>";

echo "<h2>1. Creating Delivery Settings</h2>";

// Add delivery settings for existing merchants
$merchants_sql = "SELECT merchant_id, store_name FROM merchants WHERE status = 'active'";
$result = mysqli_query($conn, $merchants_sql);

while ($merchant = mysqli_fetch_assoc($result)) {
    // Check if delivery settings exist
    $check_sql = "SELECT COUNT(*) as count FROM delivery_settings WHERE merchant_id = ?";
    $stmt = mysqli_prepare($conn, $check_sql);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $merchant['merchant_id']);
        mysqli_stmt_execute($stmt);
        $check_result = mysqli_stmt_get_result($stmt);
        $exists = mysqli_fetch_assoc($check_result)['count'] > 0;
        
        if (!$exists) {
            $insert_sql = "INSERT INTO delivery_settings (
                            merchant_id, delivery_fee, min_order_amount, free_delivery_threshold,
                            estimated_delivery_time, is_delivery_available, is_pickup_available,
                            max_delivery_distance
                           ) VALUES (?, 2.99, 15.00, 50.00, 35, 1, 1, 15.0)";
            $stmt2 = mysqli_prepare($conn, $insert_sql);
            if ($stmt2) {
                mysqli_stmt_bind_param($stmt2, "i", $merchant['merchant_id']);
                
                if (mysqli_stmt_execute($stmt2)) {
                    echo "✅ Added delivery settings for {$merchant['store_name']}<br>";
                } else {
                    echo "❌ Error adding delivery settings for {$merchant['store_name']}: " . mysqli_error($conn) . "<br>";
                }
                mysqli_stmt_close($stmt2);
            } else {
                echo "❌ Error preparing insert statement: " . mysqli_error($conn) . "<br>";
            }
        } else {
            echo "⚠️ Delivery settings already exist for {$merchant['store_name']}<br>";
        }
        mysqli_stmt_close($stmt);
    } else {
        echo "❌ Error preparing check statement: " . mysqli_error($conn) . "<br>";
    }
}

echo "<h2>2. Creating Sample User Addresses</h2>";

// Add sample addresses for test users
$sample_addresses = [
    [
        'user_id' => 1,
        'address_type' => 'Home',
        'address_line1' => '123 Main Street',
        'address_line2' => 'Apt 4B',
        'city' => 'Addis Ababa',
        'state' => 'Addis Ababa',
        'postal_code' => '1000',
        'country' => 'Ethiopia',
        'delivery_instructions' => 'Ring doorbell twice',
        'is_default' => 1
    ],
    [
        'user_id' => 1,
        'address_type' => 'Work',
        'address_line1' => '456 Business Ave',
        'address_line2' => 'Floor 3',
        'city' => 'Addis Ababa',
        'state' => 'Addis Ababa',
        'postal_code' => '1001',
        'country' => 'Ethiopia',
        'delivery_instructions' => 'Call when arriving',
        'is_default' => 0
    ]
];

foreach ($sample_addresses as $addr) {
    // Check if address exists
    $check_sql = "SELECT COUNT(*) as count FROM user_addresses WHERE user_id = ? AND address_type = ?";
    $stmt = mysqli_prepare($conn, $check_sql);
    mysqli_stmt_bind_param($stmt, "is", $addr['user_id'], $addr['address_type']);
    mysqli_stmt_execute($stmt);
    $check_result = mysqli_stmt_get_result($stmt);
    $exists = mysqli_fetch_assoc($check_result)['count'] > 0;
    
    if (!$exists) {
        $insert_sql = "INSERT INTO user_addresses (
                        user_id, address_type, address_line1, address_line2, city, state,
                        postal_code, country, delivery_instructions, is_default
                       ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $insert_sql);
        mysqli_stmt_bind_param($stmt, "issssssssi", 
            $addr['user_id'], $addr['address_type'], $addr['address_line1'], $addr['address_line2'],
            $addr['city'], $addr['state'], $addr['postal_code'], $addr['country'],
            $addr['delivery_instructions'], $addr['is_default']
        );
        
        if (mysqli_stmt_execute($stmt)) {
            echo "✅ Added {$addr['address_type']} address for user {$addr['user_id']}<br>";
        } else {
            echo "❌ Error adding address: " . mysqli_error($conn) . "<br>";
        }
    } else {
        echo "⚠️ {$addr['address_type']} address already exists for user {$addr['user_id']}<br>";
    }
}

echo "<h2>3. Updating Merchant Commission Rates</h2>";

// Set default commission rates for merchants
$update_sql = "UPDATE merchants SET commission_rate = 15.00 WHERE commission_rate IS NULL OR commission_rate = 0";
if (mysqli_query($conn, $update_sql)) {
    $affected = mysqli_affected_rows($conn);
    echo "✅ Updated commission rates for $affected merchants<br>";
} else {
    echo "❌ Error updating commission rates: " . mysqli_error($conn) . "<br>";
}

echo "<h2>4. Creating Sample Cart Items</h2>";

// Add some items to cart for testing
$user_id = 1;
$sample_cart_items = [
    ['menu_item_id' => 3, 'quantity' => 2, 'special_instructions' => 'Extra cheese please'],
    ['menu_item_id' => 4, 'quantity' => 1, 'special_instructions' => '']
];

// Clear existing cart first
$clear_sql = "DELETE FROM cart_items WHERE user_id = ?";
$stmt = mysqli_prepare($conn, $clear_sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);

foreach ($sample_cart_items as $item) {
    $insert_sql = "INSERT INTO cart_items (user_id, menu_item_id, quantity, special_instructions) VALUES (?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $insert_sql);
    mysqli_stmt_bind_param($stmt, "iiis", $user_id, $item['menu_item_id'], $item['quantity'], $item['special_instructions']);
    
    if (mysqli_stmt_execute($stmt)) {
        echo "✅ Added menu item {$item['menu_item_id']} to cart<br>";
    } else {
        echo "❌ Error adding to cart: " . mysqli_error($conn) . "<br>";
    }
}

echo "<h2>5. Verifying System Components</h2>";

// Check if auth_check.php exists
$auth_file = __DIR__ . "/../user/includes/auth_check.php";
if (!file_exists($auth_file)) {
    echo "⚠️ Creating auth_check.php<br>";
    $auth_content = '<?php
// Simple auth check
if (!isset($_SESSION["user_id"]) || !$_SESSION["logged_in"]) {
    header("Location: ../auth/login.php");
    exit();
}
?>';
    
    // Create includes directory if it doesn't exist
    $includes_dir = dirname($auth_file);
    if (!is_dir($includes_dir)) {
        mkdir($includes_dir, 0755, true);
    }
    
    file_put_contents($auth_file, $auth_content);
    echo "✅ Created auth_check.php<br>";
} else {
    echo "✅ auth_check.php exists<br>";
}

// Check navbar.php
$navbar_file = __DIR__ . "/../partials/navbar.php";
if (!file_exists($navbar_file)) {
    echo "⚠️ Creating navbar.php<br>";
    $navbar_content = '<?php
// Simple navbar for user pages
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
        <a class="navbar-brand" href="home.php">BeU Delivery</a>
        <div class="navbar-nav ms-auto">
            <a class="nav-link" href="home.php">Home</a>
            <a class="nav-link" href="cart.php">Cart</a>
            <a class="nav-link" href="orders.php">Orders</a>
            <a class="nav-link" href="../auth/logout.php">Logout</a>
        </div>
    </div>
</nav>';
    
    // Create partials directory if it doesn't exist
    $partials_dir = dirname($navbar_file);
    if (!is_dir($partials_dir)) {
        mkdir($partials_dir, 0755, true);
    }
    
    file_put_contents($navbar_file, $navbar_content);
    echo "✅ Created navbar.php<br>";
} else {
    echo "✅ navbar.php exists<br>";
}

echo "<h2>✅ Cart and Ordering System Setup Complete!</h2>";
echo "<p>The system is now ready for testing:</p>";
echo "<ul>";
echo "<li>🔗 <a href='../user/cart.php' target='_blank'>View Cart</a></li>";
echo "<li>🔗 <a href='../user/store.php?id=4' target='_blank'>Store Page (Add Items)</a></li>";
echo "<li>🔗 <a href='../user/checkout.php' target='_blank'>Checkout</a></li>";
echo "</ul>";
?>