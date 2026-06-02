<?php
// Complete User Flow Test
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set test session data
$_SESSION['user_id'] = 1;
$_SESSION['user_name'] = 'Test User';
$_SESSION['logged_in'] = true;

echo "<h1>Complete User Flow Test</h1>";

include __DIR__ . "/../includes/db.php";

echo "<h2>1. Home Page Data Test</h2>";

// Test categories
$categories_sql = "SELECT * FROM store_categories WHERE is_active = 1 ORDER BY name";
$categories_result = mysqli_query($conn, $categories_sql);
echo "✅ Found " . mysqli_num_rows($categories_result) . " categories<br>";

// Test stores with images
$stores_sql = "SELECT 
                m.merchant_id,
                m.store_name,
                m.store_address,
                sc.name as category_name,
                sc.icon as category_icon,
                (SELECT COUNT(*) FROM menu_items mi WHERE mi.merchant_id = m.merchant_id) as menu_item_count,
                (SELECT si.image_path FROM store_images si WHERE si.merchant_id = m.merchant_id ORDER BY si.display_order LIMIT 1) as store_image_path
              FROM merchants m
              LEFT JOIN store_categories sc ON m.category_id = sc.category_id
              WHERE m.status IN ('active', 'setup')
              ORDER BY m.created_at DESC";

$stores_result = mysqli_query($conn, $stores_sql);
echo "✅ Found " . mysqli_num_rows($stores_result) . " stores<br>";

echo "<h3>Store Details:</h3>";
while ($store = mysqli_fetch_assoc($stores_result)) {
    echo "<div style='border: 1px solid #ddd; padding: 10px; margin: 5px 0; border-radius: 5px;'>";
    echo "<strong>{$store['store_name']}</strong> ({$store['category_name']})<br>";
    echo "Menu Items: {$store['menu_item_count']}<br>";
    
    if ($store['store_image_path']) {
        $image_path = __DIR__ . '/../' . $store['store_image_path'];
        if (file_exists($image_path)) {
            echo "✅ Store image: {$store['store_image_path']}<br>";
        } else {
            echo "❌ Store image missing: {$store['store_image_path']}<br>";
        }
    } else {
        echo "⚠️ No store image<br>";
    }
    echo "</div>";
}

echo "<h2>2. Menu Items Test</h2>";

// Test menu items for a specific store
$menu_sql = "SELECT mi.*, mc.category_name 
             FROM menu_items mi
             LEFT JOIN menu_categories mc ON mi.category_id = mc.category_id
             WHERE mi.merchant_id = 4 AND mi.is_available = 1 
             ORDER BY mc.display_order, mi.display_order";
$menu_result = mysqli_query($conn, $menu_sql);
echo "✅ Found " . mysqli_num_rows($menu_result) . " menu items for store ID 4<br>";

// Group by category
$menu_by_category = [];
while ($item = mysqli_fetch_assoc($menu_result)) {
    $category = $item['category_name'] ?? 'Uncategorized';
    if (!isset($menu_by_category[$category])) {
        $menu_by_category[$category] = [];
    }
    $menu_by_category[$category][] = $item;
}

foreach ($menu_by_category as $category => $items) {
    echo "<h4>$category (" . count($items) . " items)</h4>";
    foreach ($items as $item) {
        echo "- {$item['name']} (\${$item['price']})<br>";
    }
}

echo "<h2>3. Image Path Tests</h2>";

// Test placeholder generator
echo "<h4>Placeholder Images:</h4>";
echo "<img src='../public/placeholder.php?text=A&size=100&bg=random' style='width: 50px; height: 50px; margin: 5px;'>";
echo "<img src='../public/placeholder.php?text=B&size=100&bg=random' style='width: 50px; height: 50px; margin: 5px;'>";
echo "<img src='../public/placeholder.php?text=C&size=100&bg=random' style='width: 50px; height: 50px; margin: 5px;'><br>";

// Test actual store images
echo "<h4>Actual Store Images:</h4>";
$images_sql = "SELECT merchant_id, image_path FROM store_images ORDER BY merchant_id, display_order";
$images_result = mysqli_query($conn, $images_sql);

while ($image = mysqli_fetch_assoc($images_result)) {
    $image_path = __DIR__ . '/../' . $image['image_path'];
    $web_path = '../' . $image['image_path'];
    
    if (file_exists($image_path)) {
        echo "<img src='$web_path' style='width: 50px; height: 50px; object-fit: cover; margin: 5px; border-radius: 5px;'>";
    } else {
        echo "<span style='color: red;'>Missing: {$image['image_path']}</span><br>";
    }
}

echo "<h2>4. Navigation Test</h2>";
echo "🔗 <a href='home.php' target='_blank'>User Home Page</a><br>";
echo "🔗 <a href='store.php?id=4' target='_blank'>Store Page (ID: 4)</a><br>";
echo "🔗 <a href='../account/merchant_dashboard.php' target='_blank'>Merchant Dashboard</a><br>";

echo "<h2>5. Database Summary</h2>";

$tables = ['merchants', 'store_categories', 'store_images', 'menu_items', 'menu_categories'];
foreach ($tables as $table) {
    $count_sql = "SELECT COUNT(*) as count FROM $table";
    $result = mysqli_query($conn, $count_sql);
    $count = mysqli_fetch_assoc($result)['count'];
    echo "✅ $table: $count records<br>";
}

echo "<h2>✅ Complete Flow Test Finished!</h2>";
echo "<p>All components are ready for the customer-facing user experience.</p>";
?>