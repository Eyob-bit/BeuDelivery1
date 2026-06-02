<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set test session data
$_SESSION['user_id'] = 1;
$_SESSION['user_name'] = 'Test User';
$_SESSION['logged_in'] = true;

include __DIR__ . "/../includes/db.php";

echo "<h1>AJAX Multi-Store Protection Test</h1>";

// Simulate AJAX call to add_to_cart
function simulateAddToCart($item_id, $action = 'add', $quantity = 1) {
    global $conn;
    
    $user_id = $_SESSION['user_id'];
    
    echo "<h3>Simulating: Add item $item_id to cart</h3>";
    
    // Get item details with merchant info (same as AJAX script)
    $sql = "SELECT mi.*, m.merchant_id, m.store_name, m.status as merchant_status 
            FROM menu_items mi
            JOIN merchants m ON mi.merchant_id = m.merchant_id
            WHERE mi.id = ? AND mi.is_available = 1 AND m.status = 'active'";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $item_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $item = mysqli_fetch_assoc($result);

    if (!$item) {
        echo "<div style='background: #f8d7da; padding: 10px; margin: 5px 0;'>❌ Item not found or unavailable</div>";
        return;
    }

    $merchant_id = $item['merchant_id'];
    echo "<p>Item: {$item['name']} from {$item['store_name']} (Merchant ID: $merchant_id)</p>";

    // Check if cart has items from different merchant (same as AJAX script)
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
        echo "<div style='background: #fff3cd; padding: 10px; margin: 5px 0;'>";
        echo "🚫 <strong>MULTI-STORE PROTECTION TRIGGERED!</strong><br>";
        echo "Response would be:<br>";
        echo "<code>";
        echo json_encode([
            'success' => false, 
            'message' => "Your cart contains items from {$row['store_name']}. Clear cart to add items from {$item['store_name']}?",
            'requires_clear' => true,
            'current_store' => $row['store_name'],
            'new_store' => $item['store_name']
        ], JSON_PRETTY_PRINT);
        echo "</code>";
        echo "</div>";
        return;
    }

    // If no conflict, would proceed to add item
    echo "<div style='background: #d4edda; padding: 10px; margin: 5px 0;'>";
    echo "✅ <strong>NO CONFLICT - WOULD ADD TO CART</strong><br>";
    echo "Item would be added successfully";
    echo "</div>";
}

// Test scenarios
echo "<h2>Test Scenarios:</h2>";

// First, show current cart
$user_id = $_SESSION['user_id'];
$cart_sql = "SELECT 
                ci.menu_item_id,
                mi.name,
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

echo "<h3>Current Cart Contents:</h3>";
$cart_merchants = [];
while ($item = mysqli_fetch_assoc($cart_result)) {
    echo "<p>- {$item['name']} from {$item['store_name']} (Merchant {$item['merchant_id']})</p>";
    $cart_merchants[] = $item['merchant_id'];
}

if (empty($cart_merchants)) {
    echo "<p><em>Cart is empty</em></p>";
} else {
    $unique_merchants = array_unique($cart_merchants);
    echo "<p><strong>Cart has items from merchant(s): " . implode(', ', $unique_merchants) . "</strong></p>";
}

// Test adding items
echo "<hr>";
simulateAddToCart(3); // Beef Burger from Eyobs (merchant 3)
echo "<hr>";
simulateAddToCart(5); // 2000habesh from Absiniya (merchant 4)
echo "<hr>";
simulateAddToCart(1); // Margherita Pizza from Eyobs (merchant 3)

echo "<h2>Manual Actions:</h2>";
echo "<form method='post'>";
echo "<button type='submit' name='clear_cart' style='background: #dc3545; color: white; padding: 10px; border: none; cursor: pointer; margin: 5px;'>Clear Cart</button>";
echo "<button type='submit' name='add_eyobs' style='background: #007bff; color: white; padding: 10px; border: none; cursor: pointer; margin: 5px;'>Add Eyobs Item</button>";
echo "<button type='submit' name='add_absiniya' style='background: #28a745; color: white; padding: 10px; border: none; cursor: pointer; margin: 5px;'>Add Absiniya Item</button>";
echo "</form>";

// Handle actions
if ($_POST['clear_cart'] ?? false) {
    $clear_sql = "DELETE FROM cart_items WHERE user_id = ?";
    $stmt = mysqli_prepare($conn, $clear_sql);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    echo "<script>location.reload();</script>";
}

if ($_POST['add_eyobs'] ?? false) {
    $add_sql = "INSERT INTO cart_items (user_id, menu_item_id, quantity) VALUES (?, 3, 1)";
    $stmt = mysqli_prepare($conn, $add_sql);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    echo "<script>location.reload();</script>";
}

if ($_POST['add_absiniya'] ?? false) {
    $add_sql = "INSERT INTO cart_items (user_id, menu_item_id, quantity) VALUES (?, 5, 1)";
    $stmt = mysqli_prepare($conn, $add_sql);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    echo "<script>location.reload();</script>";
}

echo "<p><a href='test_multi_store_browser.php'>🔗 Open Browser Test</a></p>";
?>