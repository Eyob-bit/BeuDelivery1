<?php
// Debug User Home Page
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>User Home Page Debug</h1>";

// Test database connection
include __DIR__ . "/../includes/db.php";

echo "<h2>1. Database Connection:</h2>";
if ($conn) {
    echo "✅ Database connected<br>";
} else {
    echo "❌ Database connection failed<br>";
    exit();
}

echo "<h2>2. Check Required Tables:</h2>";

// Check merchants table
$tables_to_check = [
    'merchants',
    'merchant_details', 
    'store_categories',
    'delivery_settings',
    'menu_items',
    'menu_categories',
    'store_images'
];

foreach ($tables_to_check as $table) {
    $check_sql = "SHOW TABLES LIKE '$table'";
    $result = mysqli_query($conn, $check_sql);
    if (mysqli_num_rows($result) > 0) {
        echo "✅ $table table exists<br>";
    } else {
        echo "❌ $table table missing<br>";
    }
}

echo "<h2>3. Check Sample Data:</h2>";

// Check merchants
$merchants_sql = "SELECT COUNT(*) as count FROM merchants WHERE status IN ('active', 'setup')";
$result = mysqli_query($conn, $merchants_sql);
$count = mysqli_fetch_assoc($result)['count'];
echo "✅ Found $count active merchants<br>";

// Check store categories
$categories_sql = "SELECT COUNT(*) as count FROM store_categories WHERE is_active = 1";
$result = mysqli_query($conn, $categories_sql);
$count = mysqli_fetch_assoc($result)['count'];
echo "✅ Found $count active store categories<br>";

// Check store images
$images_sql = "SELECT COUNT(*) as count FROM store_images";
$result = mysqli_query($conn, $images_sql);
$count = mysqli_fetch_assoc($result)['count'];
echo "✅ Found $count store images<br>";

echo "<h2>4. Test Store Image Paths:</h2>";

// Get a sample merchant with images
$sample_sql = "SELECT m.merchant_id, m.store_name, si.image_path 
               FROM merchants m 
               LEFT JOIN store_images si ON m.merchant_id = si.merchant_id 
               WHERE m.status = 'active' 
               LIMIT 5";
$result = mysqli_query($conn, $sample_sql);

while ($row = mysqli_fetch_assoc($result)) {
    echo "<strong>Store:</strong> " . htmlspecialchars($row['store_name']) . "<br>";
    if ($row['image_path']) {
        $image_path = $row['image_path'];
        echo "Image path: $image_path<br>";
        
        // Check if file exists
        if (file_exists($image_path)) {
            echo "✅ Image file exists<br>";
        } else {
            echo "❌ Image file not found<br>";
        }
    } else {
        echo "⚠️ No image uploaded<br>";
    }
    echo "<br>";
}

echo "<h2>5. Test Store Categories:</h2>";

$categories_sql = "SELECT * FROM store_categories WHERE is_active = 1 ORDER BY name";
$result = mysqli_query($conn, $categories_sql);

if (mysqli_num_rows($result) > 0) {
    while ($cat = mysqli_fetch_assoc($result)) {
        echo "✅ Category: " . htmlspecialchars($cat['name']) . " (Icon: " . htmlspecialchars($cat['icon']) . ")<br>";
    }
} else {
    echo "❌ No categories found<br>";
}

echo "<h2>6. Test Menu Items:</h2>";

$menu_sql = "SELECT COUNT(*) as count FROM menu_items WHERE is_available = 1";
$result = mysqli_query($conn, $menu_sql);
$count = mysqli_fetch_assoc($result)['count'];
echo "✅ Found $count available menu items<br>";

echo "<h2>7. Test Links:</h2>";
echo "🔗 <a href='home.php' target='_blank'>Open User Home Page</a><br>";
echo "🔗 <a href='store.php?id=4' target='_blank'>Test Store Page (ID: 4)</a><br>";

echo "<h2>8. Check Upload Directories:</h2>";

$upload_dirs = [
    __DIR__ . '/../account/uploads/store_images/',
    __DIR__ . '/../account/uploads/menu_items/',
    __DIR__ . '/../merchant/uploads/',
    __DIR__ . '/../uploads/merchants/'
];

foreach ($upload_dirs as $dir) {
    if (is_dir($dir)) {
        $files = scandir($dir);
        $file_count = count($files) - 2; // Exclude . and ..
        echo "✅ $dir exists ($file_count files)<br>";
    } else {
        echo "❌ $dir not found<br>";
    }
}

echo "<h2>✅ Debug Complete!</h2>";
?>