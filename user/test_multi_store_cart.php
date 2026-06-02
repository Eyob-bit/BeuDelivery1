<?php
// Test Multi-Store Cart Protection
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set test session data
$_SESSION['user_id'] = 1;
$_SESSION['user_name'] = 'Test User';
$_SESSION['logged_in'] = true;

echo "<h1>Multi-Store Cart Protection Test</h1>";

include __DIR__ . "/../includes/db.php";

$user_id = $_SESSION['user_id'];

echo "<h2>1. Current Cart Status</h2>";

// Get current cart with merchant info
$cart_sql = "SELECT 
                ci.cart_id,
                ci.menu_item_id,
                ci.quantity,
                mi.name as item_name,
                mi.price,
                mi.merchant_id,
                m.store_name
             FROM cart_items ci
             JOIN menu_items mi ON ci.menu_item_id = mi.id
             JOIN merchants m ON mi.merchant_id = m.merchant_id
             WHERE ci.user_id = ?
             ORDER BY ci.created_at DESC";
$stmt = mysqli_prepare($conn, $cart_sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$cart_result = mysqli_stmt_get_result($stmt);

$current_merchants = [];
echo "<h3>Current Cart Contents:</h3>";
if (mysqli_num_rows($cart_result) > 0) {
    while ($item = mysqli_fetch_assoc($cart_result)) {
        $current_merchants[$item['merchant_id']] = $item['store_name'];
        echo "- {$item['item_name']} x{$item['quantity']} from {$item['store_name']} (Merchant ID: {$item['merchant_id']})<br>";
    }
} else {
    echo "Cart is empty<br>";
}

echo "<h2>2. Available Menu Items from Different Merchants</h2>";

// Get menu items from different merchants
$items_sql = "SELECT mi.id, mi.name, mi.price, mi.merchant_id, m.store_name 
              FROM menu_items mi 
              JOIN merchants m ON mi.merchant_id = m.merchant_id 
              WHERE mi.is_available = 1 
              ORDER BY mi.merchant_id, mi.name 
              LIMIT 10";
$result = mysqli_query($conn, $items_sql);

$merchants_items = [];
while ($item = mysqli_fetch_assoc($result)) {
    $merchants_items[$item['merchant_id']][] = $item;
}

foreach ($merchants_items as $merchant_id => $items) {
    $store_name = $items[0]['store_name'];
    echo "<h4>$store_name (Merchant ID: $merchant_id)</h4>";
    foreach ($items as $item) {
        echo "- Item ID {$item['id']}: {$item['name']} (\${$item['price']})<br>";
    }
}

echo "<h2>3. Test Multi-Store Protection Logic</h2>";

// Test the same logic as in ajax_add_to_cart.php
if (!empty($current_merchants)) {
    $current_merchant_id = array_keys($current_merchants)[0];
    $current_store_name = $current_merchants[$current_merchant_id];
    
    echo "<h4>Current cart merchant: $current_store_name (ID: $current_merchant_id)</h4>";
    
    // Test adding item from different merchant
    foreach ($merchants_items as $test_merchant_id => $items) {
        if ($test_merchant_id != $current_merchant_id && !empty($items)) {
            $test_item = $items[0];
            echo "<h4>Testing add item from different merchant:</h4>";
            echo "Trying to add: {$test_item['name']} from {$test_item['store_name']}<br>";
            
            // Simulate the check from ajax_add_to_cart.php
            $check_cart = "SELECT DISTINCT mi.merchant_id, m.store_name
                           FROM cart_items ci
                           JOIN menu_items mi ON ci.menu_item_id = mi.id
                           JOIN merchants m ON mi.merchant_id = m.merchant_id
                           WHERE ci.user_id = ? AND mi.merchant_id != ?";
            $stmt = mysqli_prepare($conn, $check_cart);
            mysqli_stmt_bind_param($stmt, "ii", $user_id, $test_merchant_id);
            mysqli_stmt_execute($stmt);
            $check_result = mysqli_stmt_get_result($stmt);
            
            if ($row = mysqli_fetch_assoc($check_result)) {
                echo "✅ Multi-store protection WORKING: Found existing items from {$row['store_name']}<br>";
                echo "🚫 Should show message: 'Your cart contains items from {$row['store_name']}. Clear cart to add items from {$test_item['store_name']}?'<br>";
            } else {
                echo "❌ Multi-store protection NOT WORKING: No conflict detected<br>";
            }
            break;
        }
    }
} else {
    echo "Cart is empty - no multi-store protection needed<br>";
}

echo "<h2>4. Test AJAX Add to Cart Simulation</h2>";

// Simulate AJAX call
if (!empty($merchants_items)) {
    // Get first item from a different merchant than current cart
    $test_item_id = null;
    $test_merchant_id = null;
    
    foreach ($merchants_items as $merchant_id => $items) {
        if (empty($current_merchants) || !isset($current_merchants[$merchant_id])) {
            $test_item_id = $items[0]['id'];
            $test_merchant_id = $merchant_id;
            break;
        }
    }
    
    if ($test_item_id) {
        echo "<h4>Simulating AJAX call to add item ID $test_item_id:</h4>";
        
        // Create a test POST request simulation
        $_POST['id'] = $test_item_id;
        $_POST['action'] = 'add';
        $_POST['quantity'] = 1;
        
        echo "POST data: id=$test_item_id, action=add, quantity=1<br>";
        
        // Test the logic
        $item_sql = "SELECT mi.*, m.merchant_id, m.store_name, m.status as merchant_status 
                     FROM menu_items mi
                     JOIN merchants m ON mi.merchant_id = m.merchant_id
                     WHERE mi.id = ? AND mi.is_available = 1 AND m.status = 'active'";
        $stmt = mysqli_prepare($conn, $item_sql);
        mysqli_stmt_bind_param($stmt, "i", $test_item_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $item = mysqli_fetch_assoc($result);
        
        if ($item) {
            echo "✅ Item found: {$item['name']} from {$item['store_name']}<br>";
            
            $merchant_id = $item['merchant_id'];
            
            // Check if cart has items from different merchant
            $check_cart = "SELECT DISTINCT mi.merchant_id, m.store_name
                           FROM cart_items ci
                           JOIN menu_items mi ON ci.menu_item_id = mi.id
                           JOIN merchants m ON mi.merchant_id = m.merchant_id
                           WHERE ci.user_id = ? AND mi.merchant_id != ?";
            $stmt = mysqli_prepare($conn, $check_cart);
            mysqli_stmt_bind_param($stmt, "ii", $user_id, $merchant_id);
            mysqli_stmt_execute($stmt);
            $cart_check = mysqli_stmt_get_result($stmt);
            
            if ($row = mysqli_fetch_assoc($cart_check)) {
                echo "✅ PROTECTION TRIGGERED: Cart has items from {$row['store_name']}, trying to add from {$item['store_name']}<br>";
                echo "📝 Response should be:<br>";
                echo "<pre>";
                echo json_encode([
                    'success' => false, 
                    'message' => "Your cart contains items from {$row['store_name']}. Clear cart to add items from {$item['store_name']}?",
                    'requires_clear' => true,
                    'current_store' => $row['store_name'],
                    'new_store' => $item['store_name']
                ], JSON_PRETTY_PRINT);
                echo "</pre>";
            } else {
                echo "⚠️ No protection needed - same merchant or empty cart<br>";
            }
        }
    }
}

echo "<h2>5. Manual Test Instructions</h2>";
echo "<div style='background: #fff3cd; padding: 15px; border-radius: 5px;'>";
echo "<h4>To test multi-store protection manually:</h4>";
echo "<ol>";
echo "<li>Go to <a href='store.php?id=3' target='_blank'>Store 1 (Eyobs)</a> and add items to cart</li>";
echo "<li>Then go to <a href='store.php?id=4' target='_blank'>Store 2 (Absiniya)</a> and try to add items</li>";
echo "<li>You should see a popup asking to clear cart first</li>";
echo "</ol>";
echo "</div>";

echo "<h2>✅ Multi-Store Protection Test Complete</h2>";
?>