<?php
// Setup store categories
include __DIR__ . "/../includes/db.php";

echo "<h1>Setting Up Store Categories</h1>";

// Check if store_categories table exists
$check_table = "SHOW TABLES LIKE 'store_categories'";
$result = mysqli_query($conn, $check_table);

if (mysqli_num_rows($result) == 0) {
    // Create store_categories table
    $create_table = "
    CREATE TABLE store_categories (
        category_id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        icon VARCHAR(50) DEFAULT 'bi-shop',
        description TEXT,
        is_active TINYINT(1) DEFAULT 1,
        display_order INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    
    if (mysqli_query($conn, $create_table)) {
        echo "✅ store_categories table created<br>";
    } else {
        echo "❌ Error creating table: " . mysqli_error($conn) . "<br>";
    }
}

// Insert default categories
$categories = [
    ['Restaurant', 'bi-shop', 'Full-service restaurants and dining'],
    ['Fast Food', 'bi-cup-straw', 'Quick service restaurants'],
    ['Cafe', 'bi-cup-hot', 'Coffee shops and cafes'],
    ['Bakery', 'bi-cake2', 'Bakeries and pastry shops'],
    ['Grocery', 'bi-basket', 'Grocery stores and supermarkets'],
    ['Pharmacy', 'bi-capsule', 'Pharmacies and health stores'],
    ['Dessert', 'bi-ice-cream', 'Ice cream and dessert shops'],
    ['Healthy', 'bi-heart-pulse', 'Health food and organic stores']
];

foreach ($categories as $index => $cat) {
    $check_sql = "SELECT COUNT(*) as count FROM store_categories WHERE name = ?";
    $stmt = mysqli_prepare($conn, $check_sql);
    mysqli_stmt_bind_param($stmt, "s", $cat[0]);
    mysqli_stmt_execute($stmt);
    $check_result = mysqli_stmt_get_result($stmt);
    $exists = mysqli_fetch_assoc($check_result)['count'] > 0;
    
    if (!$exists) {
        $insert_sql = "INSERT INTO store_categories (name, icon, description, display_order) VALUES (?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $insert_sql);
        mysqli_stmt_bind_param($stmt, "sssi", $cat[0], $cat[1], $cat[2], $index);
        
        if (mysqli_stmt_execute($stmt)) {
            echo "✅ Added category: {$cat[0]}<br>";
        } else {
            echo "❌ Error adding {$cat[0]}: " . mysqli_error($conn) . "<br>";
        }
    } else {
        echo "⚠️ Category {$cat[0]} already exists<br>";
    }
}

// Update merchants to have category_id if they don't
echo "<h2>Assigning Categories to Merchants</h2>";

$merchants_sql = "SELECT merchant_id, store_name, store_type FROM merchants WHERE category_id IS NULL OR category_id = 0";
$result = mysqli_query($conn, $merchants_sql);

while ($merchant = mysqli_fetch_assoc($result)) {
    // Assign category based on store_type or default to Restaurant
    $category_name = 'Restaurant'; // default
    
    if (!empty($merchant['store_type'])) {
        $store_type = strtolower($merchant['store_type']);
        if (strpos($store_type, 'cafe') !== false) $category_name = 'Cafe';
        elseif (strpos($store_type, 'bakery') !== false) $category_name = 'Bakery';
        elseif (strpos($store_type, 'fast') !== false) $category_name = 'Fast Food';
        elseif (strpos($store_type, 'grocery') !== false) $category_name = 'Grocery';
        elseif (strpos($store_type, 'pharmacy') !== false) $category_name = 'Pharmacy';
    }
    
    // Get category_id
    $cat_sql = "SELECT category_id FROM store_categories WHERE name = ?";
    $stmt = mysqli_prepare($conn, $cat_sql);
    mysqli_stmt_bind_param($stmt, "s", $category_name);
    mysqli_stmt_execute($stmt);
    $cat_result = mysqli_stmt_get_result($stmt);
    $category = mysqli_fetch_assoc($cat_result);
    
    if ($category) {
        $update_sql = "UPDATE merchants SET category_id = ? WHERE merchant_id = ?";
        $stmt = mysqli_prepare($conn, $update_sql);
        mysqli_stmt_bind_param($stmt, "ii", $category['category_id'], $merchant['merchant_id']);
        
        if (mysqli_stmt_execute($stmt)) {
            echo "✅ Assigned '{$category_name}' to {$merchant['store_name']}<br>";
        } else {
            echo "❌ Error updating {$merchant['store_name']}: " . mysqli_error($conn) . "<br>";
        }
    }
}

echo "<h2>✅ Store Categories Setup Complete!</h2>";
?>