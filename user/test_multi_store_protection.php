<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set test session data
$_SESSION['user_id'] = 1;
$_SESSION['user_name'] = 'Test User';
$_SESSION['logged_in'] = true;

include __DIR__ . "/../includes/db.php";

echo "<h1>Multi-Store Cart Protection Test</h1>";

$user_id = $_SESSION['user_id'];

echo "<h2>Current Cart State:</h2>";

// Get current cart contents
$cart_sql = "SELECT 
                ci.cart_id,
                ci.menu_item_id,
                ci.quantity,
                mi.name,
                mi.price,
                mi.merchant_id,
                m.store_name
             FROM cart_items ci
             JOIN menu_items mi ON ci.menu_item_id = mi.id
             JOIN merchants m ON mi.merchant_id = m.merchant_id
             WHERE ci.user_id = ?";
$stmt = mysqli_prepare($conn, $cart_sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$cart_result = mysqli_stmt_get_result($stmt);

$current_merchants = [];
echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
echo "<tr><th>Item</th><th>Quantity</th><th>Price</th><th>Store</th><th>Merchant ID</th></tr>";

while ($item = mysqli_fetch_assoc($cart_result)) {
    echo "<tr>";
    echo "<td>{$item['name']}</td>";
    echo "<td>{$item['quantity']}</td>";
    echo "<td>\${$item['price']}</td>";
    echo "<td>{$item['store_name']}</td>";
    echo "<td>{$item['merchant_id']}</td>";
    echo "</tr>";
    $current_merchants[] = $item['merchant_id'];
}
echo "</table>";

if (empty($current_merchants)) {
    echo "<p><strong>Cart is empty</strong></p>";
} else {
    $unique_merchants = array_unique($current_merchants);
    echo "<p><strong>Cart contains items from " . count($unique_merchants) . " merchant(s): " . implode(', ', $unique_merchants) . "</strong></p>";
}

echo "<h2>Test Multi-Store Protection:</h2>";

// Test adding item from different merchant
$test_scenarios = [
    ['item_id' => 3, 'merchant_id' => 3, 'name' => 'Beef Burger', 'store' => 'Eyobs Restaurant'],
    ['item_id' => 5, 'merchant_id' => 4, 'name' => '2000habesh', 'store' => 'Absiniya Restaurant'],
    ['item_id' => 1, 'merchant_id' => 3, 'name' => 'Margherita Pizza', 'store' => 'Eyobs Restaurant']
];

foreach ($test_scenarios as $scenario) {
    echo "<h3>Testing: Add {$scenario['name']} from {$scenario['store']} (Merchant {$scenario['merchant_id']})</h3>";
    
    // Simulate the protection check logic
    $check_cart = "SELECT DISTINCT mi.merchant_id, m.store_name
                   FROM cart_items ci
                   JOIN menu_items mi ON ci.menu_item_id = mi.id
                   JOIN merchants m ON mi.merchant_id = m.merchant_id
                   WHERE ci.user_id = ? AND mi.merchant_id != ?";
    $stmt = mysqli_prepare($conn, $check_cart);
    mysqli_stmt_bind_param($stmt, "ii", $user_id, $scenario['merchant_id']);
    mysqli_stmt_execute($stmt);
    $check_result = mysqli_stmt_get_result($stmt);
    
    if ($row = mysqli_fetch_assoc($check_result)) {
        echo "<div style='background: #fff3cd; padding: 10px; border: 1px solid #ffeaa7; margin: 5px 0;'>";
        echo "🚫 <strong>PROTECTION TRIGGERED!</strong><br>";
        echo "Current cart has items from: {$row['store_name']}<br>";
        echo "Trying to add from: {$scenario['store']}<br>";
        echo "Message would be: \"Your cart contains items from {$row['store_name']}. Clear cart to add items from {$scenario['store']}?\"";
        echo "</div>";
    } else {
        echo "<div style='background: #d4edda; padding: 10px; border: 1px solid #c3e6cb; margin: 5px 0;'>";
        echo "✅ <strong>ALLOWED</strong> - No conflict detected";
        echo "</div>";
    }
}

echo "<h2>Manual Test Actions:</h2>";
echo "<form method='post' style='margin: 10px 0;'>";
echo "<button type='submit' name='clear_cart' style='background: #dc3545; color: white; padding: 10px; border: none; cursor: pointer;'>Clear Cart</button>";
echo "</form>";

echo "<form method='post' style='margin: 10px 0;'>";
echo "<button type='submit' name='add_eyobs' style='background: #007bff; color: white; padding: 10px; border: none; cursor: pointer;'>Add Beef Burger (Eyobs)</button>";
echo "</form>";

echo "<form method='post' style='margin: 10px 0;'>";
echo "<button type='submit' name='add_absiniya' style='background: #28a745; color: white; padding: 10px; border: none; cursor: pointer;'>Add 2000habesh (Absiniya)</button>";
echo "</form>";

// Handle form submissions
if ($_POST['clear_cart'] ?? false) {
    $clear_sql = "DELETE FROM cart_items WHERE user_id = ?";
    $stmt = mysqli_prepare($conn, $clear_sql);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    if (mysqli_stmt_execute($stmt)) {
        echo "<div style='background: #d4edda; padding: 10px; margin: 10px 0;'>✅ Cart cleared successfully!</div>";
        echo "<script>setTimeout(() => location.reload(), 1000);</script>";
    }
}

if ($_POST['add_eyobs'] ?? false) {
    $item_id = 3; // Beef Burger from Eyobs
    $add_sql = "INSERT INTO cart_items (user_id, menu_item_id, quantity) VALUES (?, ?, 1)";
    $stmt = mysqli_prepare($conn, $add_sql);
    mysqli_stmt_bind_param($stmt, "ii", $user_id, $item_id);
    if (mysqli_stmt_execute($stmt)) {
        echo "<div style='background: #d4edda; padding: 10px; margin: 10px 0;'>✅ Beef Burger added to cart!</div>";
        echo "<script>setTimeout(() => location.reload(), 1000);</script>";
    }
}

if ($_POST['add_absiniya'] ?? false) {
    $item_id = 5; // 2000habesh from Absiniya
    $add_sql = "INSERT INTO cart_items (user_id, menu_item_id, quantity) VALUES (?, ?, 1)";
    $stmt = mysqli_prepare($conn, $add_sql);
    mysqli_stmt_bind_param($stmt, "ii", $user_id, $item_id);
    if (mysqli_stmt_execute($stmt)) {
        echo "<div style='background: #d4edda; padding: 10px; margin: 10px 0;'>✅ 2000habesh added to cart!</div>";
        echo "<script>setTimeout(() => location.reload(), 1000);</script>";
    }
}

echo "<h2>Available Menu Items:</h2>";
$items_sql = "SELECT mi.id, mi.name, mi.price, mi.merchant_id, m.store_name 
              FROM menu_items mi 
              JOIN merchants m ON mi.merchant_id = m.merchant_id 
              WHERE mi.is_available = 1 
              ORDER BY m.store_name, mi.name";
$result = mysqli_query($conn, $items_sql);

echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
echo "<tr><th>ID</th><th>Item Name</th><th>Price</th><th>Store</th><th>Merchant ID</th></tr>";
while ($item = mysqli_fetch_assoc($result)) {
    echo "<tr>";
    echo "<td>{$item['id']}</td>";
    echo "<td>{$item['name']}</td>";
    echo "<td>\${$item['price']}</td>";
    echo "<td>{$item['store_name']}</td>";
    echo "<td>{$item['merchant_id']}</td>";
    echo "</tr>";
}
echo "</table>";

echo "<p><a href='store.php?id=3'>🔗 Visit Eyobs Restaurant</a> | <a href='store.php?id=4'>🔗 Visit Absiniya Restaurant</a></p>";
?>