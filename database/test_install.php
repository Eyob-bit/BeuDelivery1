<?php
/**
 * Test Database Installation - Diagnostic Version
 * This will show more detailed error information
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Database Installation Test</h1>";
echo "<pre>";

// Database configuration
$host = "localhost";
$user = "root";
$password = "";
$database = "beu_delivery_v2";

echo "Step 1: Connecting to MySQL...\n";
$conn = mysqli_connect($host, $user, $password);

if (!$conn) {
    die("❌ Connection failed: " . mysqli_connect_error());
}
echo "✓ Connected to MySQL\n\n";

echo "Step 2: Creating/selecting database...\n";
$create_db = "CREATE DATABASE IF NOT EXISTS `$database` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
if (mysqli_query($conn, $create_db)) {
    echo "✓ Database '$database' created or exists\n";
} else {
    die("❌ Error creating database: " . mysqli_error($conn));
}

mysqli_select_db($conn, $database);
echo "✓ Database selected\n\n";

echo "Step 3: Checking schema.sql file...\n";
$sql_file = __DIR__ . '/schema.sql';
if (!file_exists($sql_file)) {
    die("❌ schema.sql file not found at: $sql_file");
}
echo "✓ schema.sql found\n";
echo "File size: " . filesize($sql_file) . " bytes\n\n";

echo "Step 4: Reading schema.sql...\n";
$sql = file_get_contents($sql_file);
if (!$sql) {
    die("❌ Could not read schema.sql");
}
echo "✓ Schema file read successfully\n";
echo "Content length: " . strlen($sql) . " characters\n\n";

echo "Step 5: Disabling foreign key checks...\n";
if (mysqli_query($conn, "SET FOREIGN_KEY_CHECKS = 0")) {
    echo "✓ Foreign key checks disabled\n\n";
} else {
    echo "❌ Could not disable foreign key checks: " . mysqli_error($conn) . "\n\n";
}

echo "Step 6: Splitting queries...\n";
$queries = array_filter(array_map('trim', explode(';', $sql)));
echo "✓ Found " . count($queries) . " queries\n\n";

echo "Step 7: Executing queries...\n";
echo str_repeat("-", 80) . "\n";

$success_count = 0;
$error_count = 0;
$errors = [];

foreach ($queries as $index => $query) {
    if (empty($query) || substr(trim($query), 0, 2) === '--') {
        continue;
    }
    
    // Skip SET commands
    if (stripos($query, 'SET FOREIGN_KEY_CHECKS') !== false) {
        continue;
    }
    
    // Show first 100 chars of query
    $preview = substr(str_replace(["\n", "\r", "\t"], ' ', $query), 0, 100);
    echo "\nQuery " . ($index + 1) . ": " . $preview . "...\n";
    
    if (mysqli_query($conn, $query)) {
        $success_count++;
        
        // Extract table name for display
        if (preg_match('/CREATE TABLE `?(\w+)`?/i', $query, $matches)) {
            echo "  ✓ Created table: {$matches[1]}\n";
        } elseif (preg_match('/INSERT INTO `?(\w+)`?/i', $query, $matches)) {
            echo "  ✓ Inserted data into: {$matches[1]}\n";
        } elseif (preg_match('/DROP TABLE.*`?(\w+)`?/i', $query, $matches)) {
            echo "  ✓ Dropped table: {$matches[1]}\n";
        } else {
            echo "  ✓ Success\n";
        }
    } else {
        $error = mysqli_error($conn);
        
        // Only count real errors
        if (stripos($error, "Unknown table") === false && 
            stripos($error, "doesn't exist") === false) {
            $error_count++;
            $errors[] = [
                'query_num' => $index + 1,
                'query' => $preview,
                'error' => $error
            ];
            echo "  ❌ ERROR: " . $error . "\n";
        } else {
            echo "  ⚠ Warning (ignored): " . $error . "\n";
        }
    }
}

echo "\n" . str_repeat("-", 80) . "\n";

echo "\nStep 8: Re-enabling foreign key checks...\n";
if (mysqli_query($conn, "SET FOREIGN_KEY_CHECKS = 1")) {
    echo "✓ Foreign key checks re-enabled\n\n";
}

echo "Step 9: Verifying tables...\n";
$critical_tables = [
    'users', 'email_verifications', 'roles', 'user_roles',
    'merchants', 'merchant_details', 'merchant_plans', 
    'merchant_documents', 'merchant_banking', 'merchant_tax_info',
    'menu_items', 'menu_categories', 'orders', 'order_items',
    'cart_items', 'transactions', 'delivery_settings'
];

$missing_tables = [];
foreach ($critical_tables as $table) {
    $result = mysqli_query($conn, "SHOW TABLES LIKE '$table'");
    if (mysqli_num_rows($result) > 0) {
        echo "  ✓ $table\n";
    } else {
        echo "  ❌ $table (MISSING)\n";
        $missing_tables[] = $table;
    }
}

echo "</pre>";

// Summary
echo "<div style='background: " . ($error_count > 0 ? "#f8d7da" : "#d4edda") . "; padding: 20px; border-radius: 5px; margin: 20px 0;'>";
echo "<h2 style='margin-top: 0;'>" . ($error_count > 0 ? "⚠️ Installation Completed with Errors" : "🎉 Installation Successful!") . "</h2>";
echo "<p><strong>Summary:</strong></p>";
echo "<ul>";
echo "<li>Successful queries: $success_count</li>";
echo "<li>Errors: $error_count</li>";
echo "<li>Missing tables: " . count($missing_tables) . "</li>";
echo "</ul>";

if ($error_count > 0) {
    echo "<h3>Errors Details:</h3>";
    echo "<ol>";
    foreach ($errors as $err) {
        echo "<li>";
        echo "<strong>Query #{$err['query_num']}:</strong> {$err['query']}<br>";
        echo "<span style='color: #721c24;'>Error: {$err['error']}</span>";
        echo "</li>";
    }
    echo "</ol>";
}

if (count($missing_tables) > 0) {
    echo "<h3>Missing Tables:</h3>";
    echo "<p>" . implode(', ', $missing_tables) . "</p>";
}

echo "</div>";

if ($error_count == 0 && count($missing_tables) == 0) {
    echo "<div style='background: #d1ecf1; padding: 20px; border-radius: 5px;'>";
    echo "<h3>✅ Database is Ready!</h3>";
    echo "<p><strong>Default Admin Account:</strong></p>";
    echo "<ul>";
    echo "<li>Email: admin@beudelivery.com</li>";
    echo "<li>Phone: +251911000000</li>";
    echo "<li>Password: admin123</li>";
    echo "</ul>";
    echo "<p><a href='../merchant/getStarted.php' style='display: inline-block; padding: 10px 20px; background: #000; color: #fff; text-decoration: none; border-radius: 5px;'>Start Testing Merchant Registration</a></p>";
    echo "</div>";
}

echo "<p><a href='install.php'>← Back to Regular Install</a> | <a href='../index.php'>Go to Home</a></p>";

mysqli_close($conn);
?>

<style>
    body { 
        font-family: 'Courier New', monospace; 
        max-width: 1200px; 
        margin: 20px auto; 
        padding: 20px;
        background: #f5f5f5;
    }
    pre { 
        background: #fff; 
        padding: 20px; 
        border-radius: 5px; 
        overflow-x: auto;
        border: 1px solid #ddd;
        font-size: 13px;
        line-height: 1.5;
    }
    h1 {
        background: #000;
        color: #fff;
        padding: 20px;
        border-radius: 5px;
        margin: 0 0 20px 0;
    }
</style>
