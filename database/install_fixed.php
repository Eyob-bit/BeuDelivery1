<?php
/**
 * Fixed Database Installation Script
 * Properly handles multi-line SQL statements
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database configuration
$host = "localhost";
$user = "root";
$password = "";
$database = "beu_delivery_v2";

echo "<h1>Database Installation (Fixed Version)</h1>";
echo "<pre>";

// Create connection
$conn = mysqli_connect($host, $user, $password);

if (!$conn) {
    die("❌ Connection failed: " . mysqli_connect_error());
}

echo "Step 1: Connecting to MySQL...\n";
echo "✓ Connected to MySQL\n\n";

// Create database if it doesn't exist
echo "Step 2: Creating/selecting database...\n";
$create_db = "CREATE DATABASE IF NOT EXISTS `$database` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
if (mysqli_query($conn, $create_db)) {
    echo "✓ Database '$database' created or exists\n";
} else {
    die("❌ Error creating database: " . mysqli_error($conn));
}

// Select database
mysqli_select_db($conn, $database);
echo "✓ Database selected\n\n";

// Read SQL file
echo "Step 3: Reading schema.sql...\n";
$sql_file = __DIR__ . '/schema.sql';
if (!file_exists($sql_file)) {
    die("❌ Error: schema.sql file not found!");
}

$sql = file_get_contents($sql_file);
echo "✓ Schema file read (" . strlen($sql) . " characters)\n\n";

// Disable foreign key checks
echo "Step 4: Disabling foreign key checks...\n";
mysqli_query($conn, "SET FOREIGN_KEY_CHECKS = 0");
echo "✓ Foreign key checks disabled\n\n";

echo "Step 5: Executing SQL statements...\n";
echo str_repeat("-", 80) . "\n";

// Use mysqli_multi_query to execute the entire SQL file at once
if (mysqli_multi_query($conn, $sql)) {
    $query_count = 0;
    
    do {
        $query_count++;
        
        // Store first result set
        if ($result = mysqli_store_result($conn)) {
            mysqli_free_result($result);
        }
        
        // Check for errors
        if (mysqli_errno($conn)) {
            $error = mysqli_error($conn);
            // Ignore "table doesn't exist" errors on DROP
            if (stripos($error, "Unknown table") === false && 
                stripos($error, "doesn't exist") === false) {
                echo "  ⚠ Warning: " . $error . "\n";
            }
        }
        
        // Print progress every 5 queries
        if ($query_count % 5 == 0) {
            echo "  ... processed $query_count statements\n";
        }
        
    } while (mysqli_more_results($conn) && mysqli_next_result($conn));
    
    echo "\n✓ Executed $query_count SQL statements\n";
} else {
    echo "❌ Error executing SQL: " . mysqli_error($conn) . "\n";
}

echo str_repeat("-", 80) . "\n\n";

// Re-enable foreign key checks
echo "Step 6: Re-enabling foreign key checks...\n";
mysqli_query($conn, "SET FOREIGN_KEY_CHECKS = 1");
echo "✓ Foreign key checks re-enabled\n\n";

// Verify critical tables exist
echo "Step 7: Verifying tables...\n";
echo str_repeat("-", 80) . "\n";

$critical_tables = [
    'users', 'email_verifications', 'roles', 'user_roles',
    'store_categories', 'merchants', 'merchant_details', 'merchant_plans',
    'merchant_documents', 'merchant_banking', 'merchant_tax_info',
    'delivery_settings', 'menu_categories', 'menu_items',
    'user_addresses', 'orders', 'order_items', 'order_tracking',
    'cart_items', 'payment_methods', 'transactions',
    'merchant_earnings', 'merchant_reviews', 'favorites', 'notifications'
];

$missing_tables = [];
$existing_tables = [];

foreach ($critical_tables as $table) {
    $result = mysqli_query($conn, "SHOW TABLES LIKE '$table'");
    if (mysqli_num_rows($result) > 0) {
        echo "  ✓ $table\n";
        $existing_tables[] = $table;
    } else {
        echo "  ❌ $table (MISSING)\n";
        $missing_tables[] = $table;
    }
}

echo str_repeat("-", 80) . "\n";
echo "</pre>";

// Summary
if (count($missing_tables) == 0) {
    echo "<div style='background: #d4edda; padding: 30px; border-radius: 10px; margin: 20px 0; border: 2px solid #28a745;'>";
    echo "<h2 style='color: #155724; margin-top: 0;'>🎉 Installation Successful!</h2>";
    echo "<p><strong>All " . count($existing_tables) . " tables created successfully!</strong></p>";
    
    echo "<h3>Default Admin Account:</h3>";
    echo "<div style='background: #fff; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<ul style='margin: 0;'>";
    echo "<li><strong>Email:</strong> admin@beudelivery.com</li>";
    echo "<li><strong>Phone:</strong> +251911000000</li>";
    echo "<li><strong>Password:</strong> admin123</li>";
    echo "</ul>";
    echo "</div>";
    
    echo "<h3>Next Steps:</h3>";
    echo "<ol>";
    echo "<li><a href='../merchant/getStarted.php' style='color: #155724; font-weight: bold;'>Test Merchant Registration</a></li>";
    echo "<li><a href='../auth/login.php' style='color: #155724; font-weight: bold;'>Test User Login</a></li>";
    echo "<li><a href='../admin/admin_panel.php' style='color: #155724; font-weight: bold;'>Access Admin Panel</a></li>";
    echo "</ol>";
    echo "</div>";
    
    echo "<div style='background: #d1ecf1; padding: 20px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h3 style='margin-top: 0;'>📋 Tables Created:</h3>";
    echo "<div style='columns: 3; column-gap: 20px;'>";
    foreach ($existing_tables as $table) {
        echo "<div style='break-inside: avoid; padding: 3px 0;'>✓ $table</div>";
    }
    echo "</div>";
    echo "</div>";
    
} else {
    echo "<div style='background: #f8d7da; padding: 30px; border-radius: 10px; margin: 20px 0; border: 2px solid #dc3545;'>";
    echo "<h2 style='color: #721c24; margin-top: 0;'>⚠️ Installation Incomplete</h2>";
    echo "<p><strong>" . count($missing_tables) . " tables are missing!</strong></p>";
    
    echo "<h3>Missing Tables:</h3>";
    echo "<ul>";
    foreach ($missing_tables as $table) {
        echo "<li>$table</li>";
    }
    echo "</ul>";
    
    echo "<h3>What to do:</h3>";
    echo "<ol>";
    echo "<li>Check the error messages above</li>";
    echo "<li>Make sure MySQL user has CREATE TABLE permissions</li>";
    echo "<li>Try running <a href='cleanup.php'>cleanup.php</a> first, then run this again</li>";
    echo "<li>Or create tables manually using phpMyAdmin</li>";
    echo "</ol>";
    echo "</div>";
    
    if (count($existing_tables) > 0) {
        echo "<div style='background: #fff3cd; padding: 20px; border-radius: 5px; margin: 20px 0;'>";
        echo "<h3 style='margin-top: 0;'>✓ Successfully Created (" . count($existing_tables) . "):</h3>";
        echo "<div style='columns: 3; column-gap: 20px;'>";
        foreach ($existing_tables as $table) {
            echo "<div style='break-inside: avoid; padding: 3px 0;'>✓ $table</div>";
        }
        echo "</div>";
        echo "</div>";
    }
}

echo "<p style='text-align: center; margin-top: 30px;'>";
echo "<a href='test_install.php' style='display: inline-block; padding: 12px 24px; background: #007bff; color: #fff; text-decoration: none; border-radius: 5px; margin: 5px;'>Run Diagnostic Test</a>";
echo "<a href='../index.php' style='display: inline-block; padding: 12px 24px; background: #28a745; color: #fff; text-decoration: none; border-radius: 5px; margin: 5px;'>Go to Home Page</a>";
echo "</p>";

mysqli_close($conn);
?>

<style>
    body { 
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        max-width: 1200px; 
        margin: 20px auto; 
        padding: 20px;
        background: #f8f9fa;
    }
    pre { 
        background: #fff; 
        padding: 20px; 
        border-radius: 8px; 
        overflow-x: auto;
        border: 1px solid #dee2e6;
        font-family: 'Courier New', monospace;
        font-size: 13px;
        line-height: 1.6;
    }
    h1 {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: #fff;
        padding: 25px;
        border-radius: 10px;
        margin: 0 0 20px 0;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    }
    a {
        transition: all 0.3s;
    }
    a:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.2);
    }
</style>
