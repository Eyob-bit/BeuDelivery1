<?php
/**
 * Add Missing Columns to Existing Tables
 * Use this if you don't want to reinstall the entire database
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database configuration
$host = "localhost";
$user = "root";
$password = "";
$database = "beu_delivery_v2";

echo "<h1>Add Missing Columns</h1>";
echo "<pre>";

// Connect to database
$conn = mysqli_connect($host, $user, $password, $database);

if (!$conn) {
    die("❌ Connection failed: " . mysqli_connect_error());
}

echo "✓ Connected to database: $database\n\n";

$success_count = 0;
$error_count = 0;
$already_exists = 0;

// Columns to add
$columns_to_add = [
    [
        'table' => 'merchant_plans',
        'column' => 'delivery_fee_percentage',
        'definition' => 'DECIMAL(5,2) DEFAULT 15.00 AFTER plan_type'
    ],
    [
        'table' => 'merchant_plans',
        'column' => 'pickup_fee_percentage',
        'definition' => 'DECIMAL(5,2) DEFAULT 12.00 AFTER delivery_fee_percentage'
    ],
    [
        'table' => 'merchant_plans',
        'column' => 'device_rental',
        'definition' => 'BOOLEAN DEFAULT FALSE AFTER pickup_fee_percentage'
    ],
    [
        'table' => 'merchant_details',
        'column' => 'launch_date',
        'definition' => 'DATE DEFAULT NULL AFTER pickup_instructions'
    ]
];

echo "Checking and adding missing columns...\n";
echo str_repeat("-", 80) . "\n\n";

foreach ($columns_to_add as $col) {
    $table = $col['table'];
    $column = $col['column'];
    $definition = $col['definition'];
    
    echo "Table: $table, Column: $column\n";
    
    // Check if column exists
    $check_sql = "SHOW COLUMNS FROM `$table` LIKE '$column'";
    $result = mysqli_query($conn, $check_sql);
    
    if (!$result) {
        echo "  ❌ Error checking table: " . mysqli_error($conn) . "\n\n";
        $error_count++;
        continue;
    }
    
    if (mysqli_num_rows($result) > 0) {
        echo "  ⚠ Column already exists (skipping)\n\n";
        $already_exists++;
    } else {
        // Add the column
        $add_sql = "ALTER TABLE `$table` ADD COLUMN `$column` $definition";
        
        if (mysqli_query($conn, $add_sql)) {
            echo "  ✓ Column added successfully\n\n";
            $success_count++;
        } else {
            echo "  ❌ Error adding column: " . mysqli_error($conn) . "\n\n";
            $error_count++;
        }
    }
}

echo str_repeat("-", 80) . "\n";
echo "\nSummary:\n";
echo "  ✓ Columns added: $success_count\n";
echo "  ⚠ Already existed: $already_exists\n";
echo "  ❌ Errors: $error_count\n\n";

// Verify the tables
echo str_repeat("=", 80) . "\n";
echo "Verification - Current Table Structures:\n";
echo str_repeat("=", 80) . "\n\n";

$tables_to_verify = ['merchant_plans', 'merchant_details'];

foreach ($tables_to_verify as $table) {
    echo "Table: $table\n";
    echo str_repeat("-", 80) . "\n";
    
    $columns_sql = "SHOW COLUMNS FROM `$table`";
    $result = mysqli_query($conn, $columns_sql);
    
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $null = $row['Null'] == 'YES' ? 'NULL' : 'NOT NULL';
            $default = $row['Default'] !== null ? "DEFAULT '{$row['Default']}'" : '';
            echo sprintf("  %-30s %-20s %s %s\n", 
                $row['Field'], 
                $row['Type'], 
                $null,
                $default
            );
        }
    }
    echo "\n";
}

echo "</pre>";

if ($error_count == 0) {
    echo "<div style='background: #d4edda; padding: 20px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h2 style='color: #155724; margin-top: 0;'>✅ Success!</h2>";
    echo "<p>All missing columns have been added successfully.</p>";
    echo "<p><strong>You can now:</strong></p>";
    echo "<ul>";
    echo "<li>Continue with merchant registration</li>";
    echo "<li>Accept the agreement at <a href='../merchant/agreement.php'>agreement.php</a></li>";
    echo "<li>Fill store details at <a href='../merchant/enter_store_details.php'>enter_store_details.php</a></li>";
    echo "</ul>";
    echo "</div>";
} else {
    echo "<div style='background: #f8d7da; padding: 20px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h2 style='color: #721c24; margin-top: 0;'>⚠️ Some Errors Occurred</h2>";
    echo "<p>Please check the errors above and try again, or run the SQL manually in phpMyAdmin.</p>";
    echo "</div>";
}

echo "<p>";
echo "<a href='test_install.php' style='display: inline-block; padding: 10px 20px; background: #007bff; color: #fff; text-decoration: none; border-radius: 5px; margin-right: 10px;'>Test Full Install</a>";
echo "<a href='../merchant/setup.php' style='display: inline-block; padding: 10px 20px; background: #28a745; color: #fff; text-decoration: none; border-radius: 5px;'>Go to Setup</a>";
echo "</p>";

mysqli_close($conn);
?>

<style>
    body { 
        font-family: Arial, sans-serif; 
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
        font-family: 'Courier New', monospace;
        font-size: 13px;
        line-height: 1.6;
    }
    h1 {
        background: #000;
        color: #fff;
        padding: 20px;
        border-radius: 5px;
        margin: 0 0 20px 0;
    }
</style>
