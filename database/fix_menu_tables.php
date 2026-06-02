<?php
include "includes/db.php";

echo "<h1>Fixing Menu Tables</h1>";

// 1. Check and fix menu_items table
echo "<h2>1. Checking menu_items table...</h2>";

$check_menu_items = mysqli_query($conn, "SHOW TABLES LIKE 'menu_items'");
if (mysqli_num_rows($check_menu_items) == 0) {
    echo "❌ menu_items table doesn't exist. Creating...<br>";
    $create_menu_items = "
    CREATE TABLE menu_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        merchant_id INT NOT NULL,
        category_id INT NULL,
        name VARCHAR(255) NOT NULL,
        description TEXT,
        price DECIMAL(10,2) NOT NULL,
        image VARCHAR(500),
        is_available TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_merchant_id (merchant_id),
        INDEX idx_category_id (category_id),
        INDEX idx_is_available (is_available)
    )";
    
    if (mysqli_query($conn, $create_menu_items)) {
        echo "✅ menu_items table created successfully<br>";
    } else {
        echo "❌ Error creating menu_items table: " . mysqli_error($conn) . "<br>";
    }
} else {
    echo "✅ menu_items table exists<br>";
    
    // Check if all required columns exist
    $columns_check = mysqli_query($conn, "DESCRIBE menu_items");
    $existing_columns = [];
    while ($col = mysqli_fetch_assoc($columns_check)) {
        $existing_columns[] = $col['Field'];
    }
    
    $required_columns = [
        'id' => 'INT AUTO_INCREMENT PRIMARY KEY',
        'merchant_id' => 'INT NOT NULL',
        'category_id' => 'INT NULL',
        'name' => 'VARCHAR(255) NOT NULL',
        'description' => 'TEXT',
        'price' => 'DECIMAL(10,2) NOT NULL',
        'image' => 'VARCHAR(500)',
        'is_available' => 'TINYINT(1) DEFAULT 1',
        'created_at' => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP',
        'updated_at' => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'
    ];
    
    foreach ($required_columns as $column => $definition) {
        if (!in_array($column, $existing_columns)) {
            echo "⚠️ Adding missing column: $column<br>";
            $add_column = "ALTER TABLE menu_items ADD COLUMN $column $definition";
            if (mysqli_query($conn, $add_column)) {
                echo "✅ Added column $column<br>";
            } else {
                echo "❌ Error adding column $column: " . mysqli_error($conn) . "<br>";
            }
        }
    }
}

// 2. Check and fix menu_categories table
echo "<h2>2. Checking menu_categories table...</h2>";

$check_categories = mysqli_query($conn, "SHOW TABLES LIKE 'menu_categories'");
if (mysqli_num_rows($check_categories) == 0) {
    echo "❌ menu_categories table doesn't exist. Creating...<br>";
    $create_categories = "
    CREATE TABLE menu_categories (
        category_id INT AUTO_INCREMENT PRIMARY KEY,
        merchant_id INT NOT NULL,
        category_name VARCHAR(255) NOT NULL,
        display_order INT DEFAULT 0,
        is_active TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_merchant_id (merchant_id),
        INDEX idx_display_order (display_order)
    )";
    
    if (mysqli_query($conn, $create_categories)) {
        echo "✅ menu_categories table created successfully<br>";
    } else {
        echo "❌ Error creating menu_categories table: " . mysqli_error($conn) . "<br>";
    }
} else {
    echo "✅ menu_categories table exists<br>";
}

// 3. Add some default categories if none exist
echo "<h2>3. Checking default categories...</h2>";

$merchants_with_items = mysqli_query($conn, "SELECT DISTINCT merchant_id FROM menu_items");
while ($merchant = mysqli_fetch_assoc($merchants_with_items)) {
    $merchant_id = $merchant['merchant_id'];
    
    $existing_categories = mysqli_query($conn, "SELECT COUNT(*) as count FROM menu_categories WHERE merchant_id = $merchant_id");
    $cat_count = mysqli_fetch_assoc($existing_categories)['count'];
    
    if ($cat_count == 0) {
        echo "⚠️ Adding default categories for merchant $merchant_id<br>";
        $default_categories = [
            'Appetizers',
            'Main Courses', 
            'Desserts',
            'Beverages'
        ];
        
        foreach ($default_categories as $index => $category) {
            $insert_cat = "INSERT INTO menu_categories (merchant_id, category_name, display_order) VALUES ($merchant_id, '$category', $index)";
            if (mysqli_query($conn, $insert_cat)) {
                echo "✅ Added category: $category<br>";
            }
        }
    }
}

// 4. Fix any orphaned menu items (items without valid merchant_id)
echo "<h2>4. Checking for orphaned menu items...</h2>";

$orphaned_items = mysqli_query($conn, "
    SELECT mi.id, mi.name, mi.merchant_id 
    FROM menu_items mi 
    LEFT JOIN merchants m ON mi.merchant_id = m.merchant_id 
    WHERE m.merchant_id IS NULL
");

if (mysqli_num_rows($orphaned_items) > 0) {
    echo "⚠️ Found orphaned menu items:<br>";
    while ($item = mysqli_fetch_assoc($orphaned_items)) {
        echo "- Item ID {$item['id']}: {$item['name']} (merchant_id: {$item['merchant_id']})<br>";
    }
    
    // Option to delete orphaned items
    $delete_orphaned = "DELETE FROM menu_items WHERE merchant_id NOT IN (SELECT merchant_id FROM merchants)";
    if (mysqli_query($conn, $delete_orphaned)) {
        echo "✅ Cleaned up orphaned menu items<br>";
    }
} else {
    echo "✅ No orphaned menu items found<br>";
}

// 5. Create upload directories
echo "<h2>5. Checking upload directories...</h2>";

$upload_dirs = [
    'account/uploads/menu_items/',
    'account/uploads/store_images/'
];

foreach ($upload_dirs as $dir) {
    if (!file_exists($dir)) {
        if (mkdir($dir, 0777, true)) {
            echo "✅ Created directory: $dir<br>";
        } else {
            echo "❌ Failed to create directory: $dir<br>";
        }
    } else {
        echo "✅ Directory exists: $dir<br>";
    }
}

// 6. Test database operations
echo "<h2>6. Testing database operations...</h2>";

// Test SELECT
$test_select = mysqli_query($conn, "SELECT COUNT(*) as count FROM menu_items");
if ($test_select) {
    $count = mysqli_fetch_assoc($test_select)['count'];
    echo "✅ SELECT test passed. Found $count menu items<br>";
} else {
    echo "❌ SELECT test failed: " . mysqli_error($conn) . "<br>";
}

// Test INSERT (with rollback)
mysqli_autocommit($conn, false);
$test_insert = mysqli_query($conn, "INSERT INTO menu_items (merchant_id, name, price) VALUES (1, 'Test Item', 9.99)");
if ($test_insert) {
    echo "✅ INSERT test passed<br>";
    mysqli_rollback($conn); // Rollback test insert
    echo "✅ Test insert rolled back<br>";
} else {
    echo "❌ INSERT test failed: " . mysqli_error($conn) . "<br>";
    mysqli_rollback($conn);
}
mysqli_autocommit($conn, true);

// 7. Show final table structure
echo "<h2>7. Final table structures:</h2>";

echo "<h3>menu_items table:</h3>";
$structure = mysqli_query($conn, "DESCRIBE menu_items");
echo "<table border='1'><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
while ($row = mysqli_fetch_assoc($structure)) {
    echo "<tr><td>{$row['Field']}</td><td>{$row['Type']}</td><td>{$row['Null']}</td><td>{$row['Key']}</td><td>{$row['Default']}</td></tr>";
}
echo "</table>";

echo "<h3>menu_categories table:</h3>";
$structure2 = mysqli_query($conn, "DESCRIBE menu_categories");
echo "<table border='1'><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
while ($row = mysqli_fetch_assoc($structure2)) {
    echo "<tr><td>{$row['Field']}</td><td>{$row['Type']}</td><td>{$row['Null']}</td><td>{$row['Key']}</td><td>{$row['Default']}</td></tr>";
}
echo "</table>";

echo "<h2>✅ Database fix completed!</h2>";
echo "<p><a href='../account/menu_manager.php'>Test Menu Manager</a></p>";
echo "<p><a href='../account/menu_manager_debug.php'>Debug Menu Manager</a></p>";
?>