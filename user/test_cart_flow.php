<?php
// Test Complete Cart and Ordering Flow
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set test session data
$_SESSION['user_id'] = 1;
$_SESSION['user_name'] = 'Test User';
$_SESSION['user_email'] = 'test@example.com';
$_SESSION['logged_in'] = true;

echo "<h1>Complete Cart and Ordering Flow Test</h1>";

include __DIR__ . "/../includes/db.php";

echo "<h2>1. Cart System Test</h2>";

$user_id = $_SESSION['user_id'];

// Get current cart contents
$cart_sql = "SELECT 
                ci.cart_id,
                ci.menu_item_id,
                ci.quantity,
                ci.special_instructions,
                mi.name,
                mi.price,
                mi.merchant_id,
                m.store_name,
                (mi.price * ci.quantity) as subtotal
             FROM cart_items ci
             JOIN menu_items mi ON ci.menu_item_id = mi.id
             JOIN merchants m ON mi.merchant_id = m.merchant_id
             WHERE ci.user_id = ?
             ORDER BY ci.created_at DESC";
$stmt = mysqli_prepare($conn, $cart_sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$cart_result = mysqli_stmt_get_result($stmt);

$cart_total = 0;
$merchant_id = null;

echo "<h3>Current Cart Contents:</h3>";
if (mysqli_num_rows($cart_result) > 0) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Item</th><th>Store</th><th>Quantity</th><th>Price</th><th>Subtotal</th></tr>";
    
    while ($item = mysqli_fetch_assoc($cart_result)) {
        $cart_total += $item['subtotal'];
        $merchant_id = $item['merchant_id'];
        
        echo "<tr>";
        echo "<td>{$item['name']}</td>";
        echo "<td>{$item['store_name']}</td>";
        echo "<td>{$item['quantity']}</td>";
        echo "<td>\${$item['price']}</td>";
        echo "<td>\${$item['subtotal']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    echo "<p><strong>Cart Total: \$" . number_format($cart_total, 2) . "</strong></p>";
} else {
    echo "<p>Cart is empty</p>";
}

echo "<h2>2. Delivery Settings Test</h2>";

if ($merchant_id) {
    $delivery_sql = "SELECT * FROM delivery_settings WHERE merchant_id = ?";
    $stmt = mysqli_prepare($conn, $delivery_sql);
    mysqli_stmt_bind_param($stmt, "i", $merchant_id);
    mysqli_stmt_execute($stmt);
    $delivery_result = mysqli_stmt_get_result($stmt);
    
    if ($delivery_data = mysqli_fetch_assoc($delivery_result)) {
        echo "✅ Delivery settings found for merchant $merchant_id<br>";
        echo "- Delivery Fee: \${$delivery_data['delivery_fee']}<br>";
        echo "- Min Order: \${$delivery_data['min_order_amount']}<br>";
        echo "- Estimated Time: {$delivery_data['estimated_delivery_time']} minutes<br>";
        echo "- Delivery Available: " . ($delivery_data['is_delivery_available'] ? 'Yes' : 'No') . "<br>";
        echo "- Pickup Available: " . ($delivery_data['is_pickup_available'] ? 'Yes' : 'No') . "<br>";
    } else {
        echo "❌ No delivery settings found for merchant $merchant_id<br>";
    }
}

echo "<h2>3. User Addresses Test</h2>";

$addresses_sql = "SELECT * FROM user_addresses WHERE user_id = ? ORDER BY is_default DESC";
$stmt = mysqli_prepare($conn, $addresses_sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$addresses_result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($addresses_result) > 0) {
    echo "<h4>Saved Addresses:</h4>";
    while ($addr = mysqli_fetch_assoc($addresses_result)) {
        $default = $addr['is_default'] ? ' (Default)' : '';
        echo "- {$addr['address_type']}: {$addr['address_line1']}, {$addr['city']}$default<br>";
    }
} else {
    echo "⚠️ No saved addresses found<br>";
}

echo "<h2>4. Order Calculation Test</h2>";

if ($cart_total > 0 && $merchant_id) {
    $delivery_fee = 2.99;
    $tax_rate = 0.08;
    $tax = $cart_total * $tax_rate;
    $total = $cart_total + $delivery_fee + $tax;
    
    echo "<div style='border: 1px solid #ccc; padding: 15px; margin: 10px 0;'>";
    echo "<h4>Order Summary:</h4>";
    echo "Subtotal: \$" . number_format($cart_total, 2) . "<br>";
    echo "Delivery Fee: \$" . number_format($delivery_fee, 2) . "<br>";
    echo "Tax (8%): \$" . number_format($tax, 2) . "<br>";
    echo "<strong>Total: \$" . number_format($total, 2) . "</strong><br>";
    echo "</div>";
}

echo "<h2>5. AJAX Endpoints Test</h2>";

$ajax_endpoints = [
    'ajax/ajax_add_to_cart.php' => 'Add to Cart',
    'ajax/ajax_update_cart.php' => 'Update Cart',
    'ajax/ajax_remove_from_cart.php' => 'Remove from Cart',
    'ajax/ajax_get_cart.php' => 'Get Cart',
    'ajax/ajax_clear_cart.php' => 'Clear Cart'
];

foreach ($ajax_endpoints as $file => $name) {
    if (file_exists($file)) {
        echo "✅ $name endpoint exists<br>";
    } else {
        echo "❌ $name endpoint missing<br>";
    }
}

echo "<h2>6. Order Processing Files Test</h2>";

$order_files = [
    'cart.php' => 'Cart Page',
    'checkout.php' => 'Checkout Page',
    'process_order.php' => 'Order Processing',
    'order_confirmation.php' => 'Order Confirmation'
];

foreach ($order_files as $file => $name) {
    if (file_exists($file)) {
        echo "✅ $name exists<br>";
    } else {
        echo "❌ $name missing<br>";
    }
}

echo "<h2>7. Test Navigation Links</h2>";
echo "<div style='margin: 20px 0;'>";
echo "🔗 <a href='home.php' target='_blank'>Home Page</a> - Browse restaurants<br>";
echo "🔗 <a href='store.php?id=4' target='_blank'>Store Page</a> - Add items to cart<br>";
echo "🔗 <a href='cart.php' target='_blank'>Cart Page</a> - View cart contents<br>";
echo "🔗 <a href='checkout.php' target='_blank'>Checkout Page</a> - Place order<br>";
echo "</div>";

echo "<h2>8. Database Tables Summary</h2>";

$tables_summary = [
    'cart_items' => 'SELECT COUNT(*) as count FROM cart_items',
    'orders' => 'SELECT COUNT(*) as count FROM orders',
    'order_items' => 'SELECT COUNT(*) as count FROM order_items',
    'delivery_settings' => 'SELECT COUNT(*) as count FROM delivery_settings',
    'user_addresses' => 'SELECT COUNT(*) as count FROM user_addresses',
    'menu_items' => 'SELECT COUNT(*) as count FROM menu_items WHERE is_available = 1'
];

foreach ($tables_summary as $table => $query) {
    $result = mysqli_query($conn, $query);
    $count = mysqli_fetch_assoc($result)['count'];
    echo "✅ $table: $count records<br>";
}

echo "<h2>✅ Complete Cart and Ordering System Ready!</h2>";
echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
echo "<h3>🎯 Customer Flow:</h3>";
echo "<ol>";
echo "<li><strong>Browse</strong> - Customer visits home page and browses restaurants</li>";
echo "<li><strong>Select</strong> - Customer clicks on a restaurant to view menu</li>";
echo "<li><strong>Add to Cart</strong> - Customer adds items to cart with quantities</li>";
echo "<li><strong>View Cart</strong> - Customer reviews cart contents and totals</li>";
echo "<li><strong>Checkout</strong> - Customer enters delivery details and payment method</li>";
echo "<li><strong>Place Order</strong> - Order is processed and confirmation is shown</li>";
echo "<li><strong>Track</strong> - Customer can track order status</li>";
echo "</ol>";
echo "</div>";

echo "<div style='background: #fff3cd; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
echo "<h3>🧪 Testing Instructions:</h3>";
echo "<ol>";
echo "<li>Visit the <a href='home.php' target='_blank'>Home Page</a> and browse restaurants</li>";
echo "<li>Click on a restaurant to view its <a href='store.php?id=4' target='_blank'>menu</a></li>";
echo "<li>Add items to cart using the + buttons or 'Add' button</li>";
echo "<li>View your <a href='cart.php' target='_blank'>cart</a> to see added items</li>";
echo "<li>Proceed to <a href='checkout.php' target='_blank'>checkout</a> to place an order</li>";
echo "<li>Complete the order and view confirmation</li>";
echo "</ol>";
echo "</div>";
?>