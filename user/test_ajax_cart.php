<?php
// Test AJAX Cart Functionality
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set test session data
$_SESSION['user_id'] = 1;
$_SESSION['user_name'] = 'Test User';
$_SESSION['logged_in'] = true;

echo "<h1>AJAX Cart Functionality Test</h1>";

include __DIR__ . "/../includes/db.php";

$user_id = $_SESSION['user_id'];

echo "<h2>1. Current Cart Status</h2>";

// Get current cart
$cart_sql = "SELECT ci.*, mi.name, mi.merchant_id, m.store_name 
             FROM cart_items ci 
             JOIN menu_items mi ON ci.menu_item_id = mi.id 
             JOIN merchants m ON mi.merchant_id = m.merchant_id 
             WHERE ci.user_id = ?";
$stmt = mysqli_prepare($conn, $cart_sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$cart_result = mysqli_stmt_get_result($stmt);

echo "<h3>Current Cart:</h3>";
if (mysqli_num_rows($cart_result) > 0) {
    while ($item = mysqli_fetch_assoc($cart_result)) {
        echo "- {$item['name']} x{$item['quantity']} from {$item['store_name']} (Merchant: {$item['merchant_id']})<br>";
    }
} else {
    echo "Cart is empty<br>";
}

echo "<h2>2. Test Add Item from Same Merchant</h2>";

// Try adding item from same merchant (should work)
$same_merchant_item = 3; // Beef Burger from Eyobs (merchant 3)
echo "<h4>Adding item $same_merchant_item (should work):</h4>";

// Simulate POST request
$_POST = [
    'id' => $same_merchant_item,
    'action' => 'add',
    'quantity' => 1
];

// Capture output from AJAX file
ob_start();
include 'ajax/ajax_add_to_cart.php';
$response1 = ob_get_clean();

echo "Response: <pre>$response1</pre>";

echo "<h2>3. Test Add Item from Different Merchant</h2>";

// Try adding item from different merchant (should be blocked)
$different_merchant_item = 5; // Item from Absiniya (merchant 4)
echo "<h4>Adding item $different_merchant_item from different merchant (should be blocked):</h4>";

// Reset POST data
$_POST = [
    'id' => $different_merchant_item,
    'action' => 'add',
    'quantity' => 1
];

// Capture output from AJAX file
ob_start();
include 'ajax/ajax_add_to_cart.php';
$response2 = ob_get_clean();

echo "Response: <pre>$response2</pre>";

$response_data = json_decode($response2, true);
if (isset($response_data['requires_clear']) && $response_data['requires_clear']) {
    echo "✅ Multi-store protection is working!<br>";
    echo "Message: {$response_data['message']}<br>";
} else {
    echo "❌ Multi-store protection is NOT working!<br>";
}

echo "<h2>4. Test Clear Cart</h2>";

echo "<h4>Clearing cart:</h4>";

// Test clear cart
ob_start();
include 'ajax/ajax_clear_cart.php';
$clear_response = ob_get_clean();

echo "Clear response: <pre>$clear_response</pre>";

// Check if cart is actually cleared
$check_sql = "SELECT COUNT(*) as count FROM cart_items WHERE user_id = ?";
$stmt = mysqli_prepare($conn, $check_sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$check_result = mysqli_stmt_get_result($stmt);
$count = mysqli_fetch_assoc($check_result)['count'];

if ($count == 0) {
    echo "✅ Cart cleared successfully<br>";
} else {
    echo "❌ Cart not cleared - still has $count items<br>";
}

echo "<h2>5. Test Add After Clear</h2>";

echo "<h4>Adding item from different merchant after clearing:</h4>";

// Try adding the different merchant item again
$_POST = [
    'id' => $different_merchant_item,
    'action' => 'add',
    'quantity' => 1
];

ob_start();
include 'ajax/ajax_add_to_cart.php';
$response3 = ob_get_clean();

echo "Response: <pre>$response3</pre>";

$response_data3 = json_decode($response3, true);
if (isset($response_data3['success']) && $response_data3['success']) {
    echo "✅ Item added successfully after clearing cart<br>";
} else {
    echo "❌ Failed to add item after clearing cart<br>";
}

echo "<h2>6. Final Cart Status</h2>";

// Check final cart status
mysqli_stmt_execute($stmt = mysqli_prepare($conn, $cart_sql));
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$final_cart = mysqli_stmt_get_result($stmt);

echo "<h3>Final Cart:</h3>";
if (mysqli_num_rows($final_cart) > 0) {
    while ($item = mysqli_fetch_assoc($final_cart)) {
        echo "- {$item['name']} x{$item['quantity']} from {$item['store_name']} (Merchant: {$item['merchant_id']})<br>";
    }
} else {
    echo "Cart is empty<br>";
}

echo "<h2>7. Browser Test Instructions</h2>";
echo "<div style='background: #fff3cd; padding: 15px; border-radius: 5px;'>";
echo "<h4>To test in browser:</h4>";
echo "<ol>";
echo "<li>Open <a href='store.php?id=3' target='_blank'>Eyobs Store</a> and add an item</li>";
echo "<li>Then open <a href='store.php?id=4' target='_blank'>Absiniya Store</a> and try to add an item</li>";
echo "<li>You should see a confirmation dialog asking to clear cart</li>";
echo "<li>Click OK to clear cart and add new item</li>";
echo "</ol>";
echo "</div>";

echo "<h2>✅ AJAX Cart Test Complete</h2>";
?>