<?php
// Debug Cart and Ordering System
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set test session data
$_SESSION['user_id'] = 1;
$_SESSION['user_name'] = 'Test User';
$_SESSION['logged_in'] = true;

echo "<h1>Cart and Ordering System Debug</h1>";

include __DIR__ . "/../includes/db.php";

echo "<h2>1. Database Connection:</h2>";
if ($conn) {
    echo "✅ Database connected<br>";
} else {
    echo "❌ Database connection failed<br>";
    exit();
}

echo "<h2>2. Check Required Tables:</h2>";

$required_tables = [
    'cart_items',
    'orders',
    'order_items',
    'order_tracking',
    'merchant_earnings',
    'transactions',
    'notifications',
    'user_addresses',
    'delivery_settings'
];

foreach ($required_tables as $table) {
    $check_sql = "SHOW TABLES LIKE '$table'";
    $result = mysqli_query($conn, $check_sql);
    if (mysqli_num_rows($result) > 0) {
        echo "✅ $table table exists<br>";
    } else {
        echo "❌ $table table missing<br>";
    }
}

echo "<h2>3. Check Table Structures:</h2>";

// Check cart_items table structure
$cart_structure = "DESCRIBE cart_items";
$result = mysqli_query($conn, $cart_structure);
if ($result) {
    echo "<h4>cart_items table structure:</h4>";
    while ($row = mysqli_fetch_assoc($result)) {
        echo "- {$row['Field']} ({$row['Type']})<br>";
    }
} else {
    echo "❌ Cannot describe cart_items table<br>";
}

echo "<h2>4. Test Cart Operations:</h2>";

// Test adding item to cart
$user_id = $_SESSION['user_id'];
$test_item_id = 3; // Use an existing menu item

// Check if menu item exists
$item_check = "SELECT id, name, price, merchant_id FROM menu_items WHERE id = ? AND is_available = 1";
$stmt = mysqli_prepare($conn, $item_check);
mysqli_stmt_bind_param($stmt, "i", $test_item_id);
mysqli_stmt_execute($stmt);
$item_result = mysqli_stmt_get_result($stmt);

if ($item = mysqli_fetch_assoc($item_result)) {
    echo "✅ Test menu item found: {$item['name']} (\${$item['price']})<br>";
    
    // Clear existing cart for test
    $clear_sql = "DELETE FROM cart_items WHERE user_id = ?";
    $stmt = mysqli_prepare($conn, $clear_sql);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    
    // Add item to cart
    $add_sql = "INSERT INTO cart_items (user_id, menu_item_id, quantity) VALUES (?, ?, 2)";
    $stmt = mysqli_prepare($conn, $add_sql);
    mysqli_stmt_bind_param($stmt, "ii", $user_id, $test_item_id);
    
    if (mysqli_stmt_execute($stmt)) {
        echo "✅ Item added to cart successfully<br>";
        
        // Get cart contents
        $cart_sql = "SELECT ci.*, mi.name, mi.price FROM cart_items ci 
                     JOIN menu_items mi ON ci.menu_item_id = mi.id 
                     WHERE ci.user_id = ?";
        $stmt = mysqli_prepare($conn, $cart_sql);
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        $cart_result = mysqli_stmt_get_result($stmt);
        
        echo "<h4>Cart Contents:</h4>";
        while ($cart_item = mysqli_fetch_assoc($cart_result)) {
            echo "- {$cart_item['name']} x{$cart_item['quantity']} (\${$cart_item['price']} each)<br>";
        }
    } else {
        echo "❌ Failed to add item to cart: " . mysqli_error($conn) . "<br>";
    }
} else {
    echo "❌ No test menu item found<br>";
}

echo "<h2>5. Test AJAX Endpoints:</h2>";

$ajax_files = [
    __DIR__ . '/ajax/ajax_add_to_cart.php',
    __DIR__ . '/ajax/ajax_update_cart.php',
    __DIR__ . '/ajax/ajax_remove_from_cart.php',
    __DIR__ . '/ajax/ajax_get_cart.php'
];

foreach ($ajax_files as $file) {
    $filename = basename($file);
    if (file_exists($file)) {
        echo "✅ $filename exists<br>";
    } else {
        echo "❌ $filename missing<br>";
    }
}

echo "<h2>6. Test Order Processing:</h2>";

$order_files = [
    __DIR__ . '/cart.php',
    __DIR__ . '/checkout.php',
    __DIR__ . '/process_order.php',
    __DIR__ . '/order_confirmation.php'
];

foreach ($order_files as $file) {
    $filename = basename($file);
    if (file_exists($file)) {
        echo "✅ $filename exists<br>";
    } else {
        echo "❌ $filename missing<br>";
    }
}

echo "<h2>7. Test Links:</h2>";
echo "🔗 <a href='cart.php' target='_blank'>View Cart</a><br>";
echo "🔗 <a href='store.php?id=4' target='_blank'>Store Page (Add Items)</a><br>";
echo "🔗 <a href='checkout.php' target='_blank'>Checkout</a><br>";

echo "<h2>8. Sample Data Check:</h2>";

// Check delivery settings
$delivery_sql = "SELECT COUNT(*) as count FROM delivery_settings";
$result = mysqli_query($conn, $delivery_sql);
$count = mysqli_fetch_assoc($result)['count'];
echo "✅ Found $count delivery settings records<br>";

// Check user addresses
$addresses_sql = "SELECT COUNT(*) as count FROM user_addresses";
$result = mysqli_query($conn, $addresses_sql);
$count = mysqli_fetch_assoc($result)['count'];
echo "✅ Found $count user addresses<br>";

echo "<h2>✅ Cart and Ordering System Debug Complete!</h2>";
?>