<?php
/**
 * Fix ALL merchant setup tables at once
 * This adds all missing columns needed for the complete merchant flow
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

$host = "localhost";
$user = "root";
$password = "";
$database = "beu_delivery_v2";

echo "<!DOCTYPE html><html><head><title>Fix All Merchant Tables</title>";
echo "<style>body{font-family:Arial;max-width:1000px;margin:20px auto;padding:20px;background:#f5f5f5;}";
echo "pre{background:#fff;padding:20px;border-radius:5px;border:1px solid #ddd;font-size:13px;}";
echo ".success{color:#28a745;} .warning{color:#ffc107;} .error{color:#dc3545;}</style></head><body>";

echo "<h1>Fix All Merchant Tables</h1><pre>";

$conn = mysqli_connect($host, $user, $password, $database);

if (!$conn) {
    die("❌ Connection failed: " . mysqli_connect_error());
}

echo "✓ Connected to database: $database\n\n";

$total_success = 0;
$total_exists = 0;
$total_errors = 0;

// ============================================
// FIX 1: merchant_banking table
// ============================================
echo str_repeat("=", 70) . "\n";
echo "FIXING: merchant_banking\n";
echo str_repeat("=", 70) . "\n\n";

$banking_columns = [
    "business_legal_entity_name VARCHAR(255) DEFAULT NULL",
    "company_mailing_address TEXT DEFAULT NULL",
    "city VARCHAR(100) DEFAULT NULL",
    "state VARCHAR(100) DEFAULT NULL",
    "postal_code VARCHAR(20) DEFAULT NULL",
    "verified BOOLEAN DEFAULT FALSE"
];

foreach ($banking_columns as $col_def) {
    preg_match('/^(\w+)/', $col_def, $m);
    $col_name = $m[1];
    
    $check = mysqli_query($conn, "SHOW COLUMNS FROM merchant_banking LIKE '$col_name'");
    if (mysqli_num_rows($check) > 0) {
        echo "  ⚠️  $col_name (already exists)\n";
        $total_exists++;
    } else {
        if (mysqli_query($conn, "ALTER TABLE merchant_banking ADD COLUMN $col_def")) {
            echo "  <span class='success'>✓</span> $col_name (added)\n";
            $total_success++;
        } else {
            echo "  <span class='error'>❌</span> $col_name: " . mysqli_error($conn) . "\n";
            $total_errors++;
        }
    }
}

// ============================================
// FIX 2: merchant_tax_info table
// ============================================
echo "\n" . str_repeat("=", 70) . "\n";
echo "FIXING: merchant_tax_info\n";
echo str_repeat("=", 70) . "\n\n";

// First make required columns nullable
echo "Making required columns nullable...\n";
$nullable_cols = [
    "business_name VARCHAR(255) DEFAULT NULL",
    "tax_identification_number VARCHAR(100) DEFAULT NULL"
];

foreach ($nullable_cols as $col_def) {
    preg_match('/^(\w+)/', $col_def, $m);
    $col_name = $m[1];
    
    if (mysqli_query($conn, "ALTER TABLE merchant_tax_info MODIFY COLUMN $col_def")) {
        echo "  <span class='success'>✓</span> $col_name (now nullable)\n";
    } else {
        echo "  <span class='warning'>⚠️</span> $col_name: " . mysqli_error($conn) . "\n";
    }
}

echo "\nAdding missing columns...\n";
$tax_columns = [
    "tax_classification VARCHAR(100) DEFAULT NULL",
    "full_name VARCHAR(255) DEFAULT NULL",
    "ssn VARCHAR(255) DEFAULT NULL",
    "ssn_last_four VARCHAR(4) DEFAULT NULL",
    "ein VARCHAR(255) DEFAULT NULL",
    "ein_last_four VARCHAR(4) DEFAULT NULL",
    "address TEXT DEFAULT NULL",
    "city VARCHAR(100) DEFAULT NULL",
    "state VARCHAR(100) DEFAULT NULL",
    "postal_code VARCHAR(20) DEFAULT NULL",
    "verified BOOLEAN DEFAULT FALSE"
];

foreach ($tax_columns as $col_def) {
    preg_match('/^(\w+)/', $col_def, $m);
    $col_name = $m[1];
    
    $check = mysqli_query($conn, "SHOW COLUMNS FROM merchant_tax_info LIKE '$col_name'");
    if (mysqli_num_rows($check) > 0) {
        echo "  ⚠️  $col_name (already exists)\n";
        $total_exists++;
    } else {
        if (mysqli_query($conn, "ALTER TABLE merchant_tax_info ADD COLUMN $col_def")) {
            echo "  <span class='success'>✓</span> $col_name (added)\n";
            $total_success++;
        } else {
            echo "  <span class='error'>❌</span> $col_name: " . mysqli_error($conn) . "\n";
            $total_errors++;
        }
    }
}

// ============================================
// SUMMARY
// ============================================
echo "\n" . str_repeat("=", 70) . "\n";
echo "SUMMARY\n";
echo str_repeat("=", 70) . "\n";
echo "  <span class='success'>✓ Added: $total_success</span>\n";
echo "  <span class='warning'>⚠️ Already existed: $total_exists</span>\n";
echo "  <span class='error'>❌ Errors: $total_errors</span>\n";

echo "</pre>";

if ($total_errors == 0) {
    echo "<div style='background:#d4edda;padding:30px;border-radius:10px;margin:20px 0;border:2px solid #28a745;'>";
    echo "<h2 style='color:#155724;margin-top:0;'>🎉 All Tables Fixed!</h2>";
    echo "<p><strong>The merchant setup flow is now complete!</strong></p>";
    echo "<p>All missing columns have been added. You can now:</p>";
    echo "<ul>";
    echo "<li>✅ Complete payment setup</li>";
    echo "<li>✅ Complete tax info</li>";
    echo "<li>✅ Finish merchant onboarding</li>";
    echo "</ul>";
    echo "<p><a href='../merchant/setup.php' style='display:inline-block;padding:15px 30px;background:#28a745;color:#fff;text-decoration:none;border-radius:5px;font-size:16px;font-weight:bold;'>Go to Setup Page</a></p>";
    echo "</div>";
} else {
    echo "<div style='background:#f8d7da;padding:30px;border-radius:10px;margin:20px 0;border:2px solid:#dc3545;'>";
    echo "<h2 style='color:#721c24;margin-top:0;'>⚠️ Some Errors Occurred</h2>";
    echo "<p>Check the errors above. You may need to:</p>";
    echo "<ul>";
    echo "<li>Check database permissions</li>";
    echo "<li>Verify table names are correct</li>";
    echo "<li>Run this script again</li>";
    echo "</ul>";
    echo "</div>";
}

// Show current table structures
echo "<details style='margin:20px 0;'><summary style='cursor:pointer;padding:10px;background:#fff;border:1px solid #ddd;border-radius:5px;font-weight:bold;'>📋 View Current Table Structures</summary>";
echo "<div style='background:#fff;padding:20px;border:1px solid #ddd;border-radius:5px;margin-top:10px;'>";

$tables = ['merchant_banking', 'merchant_tax_info'];
foreach ($tables as $table) {
    echo "<h3>$table</h3><pre style='font-size:12px;'>";
    $result = mysqli_query($conn, "SHOW COLUMNS FROM $table");
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            printf("%-30s %-20s %s\n", $row['Field'], $row['Type'], $row['Null'] == 'YES' ? 'NULL' : 'NOT NULL');
        }
    }
    echo "</pre>";
}

echo "</div></details>";

mysqli_close($conn);
echo "</body></html>";
?>
