<?php
/**
 * Database Installation Script
 * Run this file once to create all database tables
 */

// Database configuration
$host = "localhost";
$user = "root";
$password = "";
$database = "beu_delivery_v2";

// Create connection
$conn = mysqli_connect($host, $user, $password);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Create database if it doesn't exist
$create_db = "CREATE DATABASE IF NOT EXISTS `$database` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
if (mysqli_query($conn, $create_db)) {
    echo "✓ Database created or already exists<br>";
} else {
    die("Error creating database: " . mysqli_error($conn));
}

// Select database
mysqli_select_db($conn, $database);

// Read SQL file
$sql_file = __DIR__ . '/schema.sql';
if (!file_exists($sql_file)) {
    die("Error: schema.sql file not found!");
}

$sql = file_get_contents($sql_file);

// First, disable foreign key checks
mysqli_query($conn, "SET FOREIGN_KEY_CHECKS = 0");

// Split into individual queries
$queries = array_filter(array_map('trim', explode(';', $sql)));

$success_count = 0;
$error_count = 0;
$errors = [];

echo "<h2>Installing Database Schema...</h2>";
echo "<pre>";

foreach ($queries as $query) {
    if (empty($query) || substr(trim($query), 0, 2) === '--') {
        continue;
    }
    
    // Skip SET commands as we handle them separately
    if (stripos($query, 'SET FOREIGN_KEY_CHECKS') !== false) {
        continue;
    }
    
    if (mysqli_query($conn, $query)) {
        $success_count++;
        // Extract table name for display
        if (preg_match('/CREATE TABLE `?(\w+)`?/i', $query, $matches)) {
            echo "✓ Created table: {$matches[1]}\n";
        } elseif (preg_match('/INSERT INTO `?(\w+)`?/i', $query, $matches)) {
            echo "✓ Inserted data into: {$matches[1]}\n";
        } elseif (preg_match('/DROP TABLE.*`?(\w+)`?/i', $query, $matches)) {
            echo "✓ Dropped table: {$matches[1]}\n";
        }
    } else {
        $error = mysqli_error($conn);
        // Only count real errors (not "table doesn't exist" on DROP)
        if (stripos($error, "Unknown table") === false && stripos($error, "doesn't exist") === false) {
            $error_count++;
            $errors[] = $error;
            echo "✗ Error: " . $error . "\n";
        }
    }
}

// Re-enable foreign key checks
mysqli_query($conn, "SET FOREIGN_KEY_CHECKS = 1");

echo "</pre>";
echo "<h3>Installation Complete!</h3>";
echo "<p>Successfully executed: $success_count queries</p>";

if ($error_count > 0) {
    echo "<p style='color: red;'>Errors encountered: $error_count</p>";
    echo "<details><summary>View Errors</summary><pre>";
    foreach ($errors as $error) {
        echo "- " . htmlspecialchars($error) . "\n";
    }
    echo "</pre></details>";
} else {
    echo "<p style='color: green;'>✓ No errors! Database is ready to use.</p>";
}

// Verify critical tables exist
echo "<h3>Verification</h3>";
echo "<pre>";
$critical_tables = ['users', 'merchants', 'menu_items', 'orders', 'cart_items', 'transactions'];
$all_good = true;

foreach ($critical_tables as $table) {
    $result = mysqli_query($conn, "SHOW TABLES LIKE '$table'");
    if (mysqli_num_rows($result) > 0) {
        echo "✓ Table '$table' exists\n";
    } else {
        echo "✗ Table '$table' is missing!\n";
        $all_good = false;
    }
}

echo "</pre>";

if ($all_good) {
    echo "<div style='background: #d4edda; padding: 20px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h3 style='color: #155724; margin-top: 0;'>🎉 Success! Your database is ready.</h3>";
    echo "<p><strong>Default Admin Account:</strong></p>";
    echo "<ul>";
    echo "<li>Phone: +251911000000</li>";
    echo "<li>Email: admin@beudelivery.com</li>";
    echo "<li>Password: admin123</li>";
    echo "</ul>";
    echo "<p><strong>Next Steps:</strong></p>";
    echo "<ol>";
    echo "<li>Login to admin panel: <a href='../admin/'>Admin Panel</a></li>";
    echo "<li>Change the default admin password</li>";
    echo "<li>Start testing the system</li>";
    echo "</ol>";
    echo "</div>";
} else {
    echo "<div style='background: #f8d7da; padding: 20px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h3 style='color: #721c24; margin-top: 0;'>⚠️ Installation Issues Detected</h3>";
    echo "<p>Some critical tables are missing. Please check the errors above and try again.</p>";
    echo "</div>";
}

echo "<p><a href='../index.php' class='btn'>Go to Home Page</a></p>";

echo "<style>
    body { font-family: Arial, sans-serif; max-width: 900px; margin: 50px auto; padding: 20px; }
    pre { background: #f5f5f5; padding: 15px; border-radius: 5px; overflow-x: auto; }
    .btn { display: inline-block; padding: 10px 20px; background: #000; color: #fff; text-decoration: none; border-radius: 5px; }
    .btn:hover { background: #333; }
    details { margin: 10px 0; }
    summary { cursor: pointer; font-weight: bold; }
</style>";

mysqli_close($conn);
?>
