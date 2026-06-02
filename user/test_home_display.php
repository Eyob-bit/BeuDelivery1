<?php
// Test User Home Page Display
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set test session data for user
$_SESSION['user_id'] = 1;
$_SESSION['user_name'] = 'Test User';
$_SESSION['logged_in'] = true;

echo "<h1>User Home Page Display Test</h1>";

include __DIR__ . "/../includes/db.php";

echo "<h2>1. Session Check:</h2>";
echo "✅ User ID: " . $_SESSION['user_id'] . "<br>";
echo "✅ User Name: " . $_SESSION['user_name'] . "<br>";

echo "<h2>2. Test Store Query:</h2>";

// Test the same query used in home.php
$stores_sql = "SELECT 
                m.merchant_id,
                m.store_name,
                m.store_address,
                m.featured_image,
                m.store_type,
                m.is_featured,
                m.rating,
                m.review_count,
                sc.name as category_name,
                sc.icon as category_icon,
                md.cuisine_types,
                md.store_phone,
                ds.delivery_fee,
                ds.estimated_delivery_time,
                ds.is_delivery_available,
                ds.is_pickup_available,
                ds.min_order_amount,
                (SELECT COUNT(*) FROM menu_items mi WHERE mi.merchant_id = m.merchant_id) as menu_item_count,
                (SELECT si.image_path FROM store_images si WHERE si.merchant_id = m.merchant_id ORDER BY si.display_order LIMIT 1) as store_image_path
              FROM merchants m
              LEFT JOIN merchant_details md ON m.merchant_id = md.merchant_id
              LEFT JOIN delivery_settings ds ON m.merchant_id = ds.merchant_id
              LEFT JOIN store_categories sc ON m.category_id = sc.category_id
              WHERE m.status IN ('active', 'setup')
              ORDER BY m.created_at DESC
              LIMIT 5";

$result = mysqli_query($conn, $stores_sql);

if ($result && mysqli_num_rows($result) > 0) {
    echo "✅ Found " . mysqli_num_rows($result) . " stores<br><br>";
    
    while ($store = mysqli_fetch_assoc($result)) {
        echo "<div style='border: 1px solid #ccc; padding: 15px; margin: 10px 0; border-radius: 8px;'>";
        echo "<h3>" . htmlspecialchars($store['store_name']) . "</h3>";
        echo "<strong>Category:</strong> " . htmlspecialchars($store['category_name'] ?? 'None') . "<br>";
        echo "<strong>Address:</strong> " . htmlspecialchars($store['store_address']) . "<br>";
        echo "<strong>Menu Items:</strong> " . $store['menu_item_count'] . "<br>";
        
        // Test image path
        if ($store['store_image_path']) {
            echo "<strong>Store Image:</strong> " . htmlspecialchars($store['store_image_path']) . "<br>";
            $image_path = __DIR__ . '/../' . $store['store_image_path'];
            if (file_exists($image_path)) {
                echo "✅ Image file exists<br>";
                $web_path = '../' . $store['store_image_path'];
                echo "<img src='$web_path' style='width: 100px; height: 100px; object-fit: cover; border-radius: 8px;'><br>";
            } else {
                echo "❌ Image file not found at: $image_path<br>";
            }
        } else {
            echo "⚠️ No store image<br>";
            echo "<img src='../public/placeholder.php?text=" . urlencode(substr($store['store_name'], 0, 1)) . "&size=100&bg=random' style='width: 100px; height: 100px; border-radius: 8px;'><br>";
        }
        
        // Parse cuisine types
        if ($store['cuisine_types']) {
            $cuisine_data = json_decode($store['cuisine_types'], true);
            if (is_array($cuisine_data) && isset($cuisine_data[0])) {
                $cuisine_list = json_decode($cuisine_data[0], true);
                if (is_array($cuisine_list)) {
                    echo "<strong>Cuisines:</strong> " . implode(', ', $cuisine_list) . "<br>";
                }
            }
        }
        
        echo "</div>";
    }
} else {
    echo "❌ No stores found or query error: " . mysqli_error($conn) . "<br>";
}

echo "<h2>3. Test Links:</h2>";
echo "🔗 <a href='home.php' target='_blank'>Open User Home Page</a><br>";
echo "🔗 <a href='store.php?id=4' target='_blank'>Test Store Page (ID: 4)</a><br>";

echo "<h2>✅ Display Test Complete!</h2>";
?>