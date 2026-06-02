<?php
include "includes/db.php";

echo "<h1>Testing Menu Update Functionality</h1>";

// Get a sample menu item
$sample_query = "SELECT * FROM menu_items LIMIT 1";
$sample_result = mysqli_query($conn, $sample_query);

if (!$sample_result || mysqli_num_rows($sample_result) == 0) {
    echo "❌ No menu items found to test with<br>";
    exit();
}

$sample_item = mysqli_fetch_assoc($sample_result);
echo "<h2>Testing with item:</h2>";
echo "ID: " . $sample_item['id'] . "<br>";
echo "Name: " . htmlspecialchars($sample_item['name']) . "<br>";
echo "Merchant ID: " . $sample_item['merchant_id'] . "<br>";

// Test the exact UPDATE query from menu_manager.php
echo "<h2>Testing UPDATE query without image:</h2>";

$item_id = $sample_item['id'];
$merchant_id = $sample_item['merchant_id'];
$name = "Test Updated Name";
$description = "Test Updated Description";
$price = 12.99;
$category_id = null;
$is_available = 1;

$sql = "UPDATE menu_items SET name=?, description=?, price=?, category_id=?, is_available=? 
        WHERE id=? AND merchant_id=?";

echo "SQL: $sql<br>";
echo "Parameters: name='$name', description='$description', price=$price, category_id=NULL, is_available=$is_available, id=$item_id, merchant_id=$merchant_id<br>";

$stmt = mysqli_prepare($conn, $sql);
if (!$stmt) {
    echo "❌ Failed to prepare statement: " . mysqli_error($conn) . "<br>";
    exit();
}

echo "✅ Statement prepared successfully<br>";

// Test parameter binding
$bind_result = mysqli_stmt_bind_param($stmt, "ssdiiii", $name, $description, $price, $category_id, $is_available, $item_id, $merchant_id);
if (!$bind_result) {
    echo "❌ Failed to bind parameters: " . mysqli_stmt_error($stmt) . "<br>";
    exit();
}

echo "✅ Parameters bound successfully<br>";

// Test execution
$execute_result = mysqli_stmt_execute($stmt);
if (!$execute_result) {
    echo "❌ Failed to execute statement: " . mysqli_stmt_error($stmt) . "<br>";
    exit();
}

echo "✅ Statement executed successfully<br>";
echo "Affected rows: " . mysqli_stmt_affected_rows($stmt) . "<br>";

// Test with image path
echo "<h2>Testing UPDATE query with image:</h2>";

$image_path = "uploads/menu_items/test_image.jpg";
$sql_with_image = "UPDATE menu_items SET name=?, description=?, price=?, category_id=?, image=?, is_available=? 
                   WHERE id=? AND merchant_id=?";

echo "SQL: $sql_with_image<br>";

$stmt2 = mysqli_prepare($conn, $sql_with_image);
if (!$stmt2) {
    echo "❌ Failed to prepare statement with image: " . mysqli_error($conn) . "<br>";
    exit();
}

echo "✅ Statement with image prepared successfully<br>";

$bind_result2 = mysqli_stmt_bind_param($stmt2, "ssdisiii", $name, $description, $price, $category_id, $image_path, $is_available, $item_id, $merchant_id);
if (!$bind_result2) {
    echo "❌ Failed to bind parameters with image: " . mysqli_stmt_error($stmt2) . "<br>";
    exit();
}

echo "✅ Parameters with image bound successfully<br>";

$execute_result2 = mysqli_stmt_execute($stmt2);
if (!$execute_result2) {
    echo "❌ Failed to execute statement with image: " . mysqli_stmt_error($stmt2) . "<br>";
    exit();
}

echo "✅ Statement with image executed successfully<br>";
echo "Affected rows: " . mysqli_stmt_affected_rows($stmt2) . "<br>";

// Restore original data
$restore_sql = "UPDATE menu_items SET name=?, description=?, price=?, image=? WHERE id=?";
$restore_stmt = mysqli_prepare($conn, $restore_sql);
mysqli_stmt_bind_param($restore_stmt, "ssdsi", $sample_item['name'], $sample_item['description'], $sample_item['price'], $sample_item['image'], $sample_item['id']);
mysqli_stmt_execute($restore_stmt);

echo "<h2>✅ All tests passed! Menu update functionality should work.</h2>";
echo "<p>The issue might be in the form submission or session handling in the web interface.</p>";
?>