<?php
/**
 * Add missing columns to merchant_tax_info table
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

$host = "localhost";
$user = "root";
$password = "";
$database = "beu_delivery_v2";

echo "<h1>Fix merchant_tax_info Table</h1><pre>";

$conn = mysqli_connect($host, $user, $password, $database);

if (!$conn) {
    die("❌ Connection failed: " . mysqli_connect_error());
}

echo "✓ Connected to database\n\n";

// First, make business_name nullable
echo "Making business_name nullable...\n";
$modify_sql = "ALTER TABLE merchant_tax_info MODIFY COLUMN business_name VARCHAR(255) DEFAULT NULL";
if (mysqli_query($conn, $modify_sql)) {
    echo "✓ business_name is now nullable\n\n";
} else {
    echo "⚠️ " . mysqli_error($conn) . "\n\n";
}

// Make tax_identification_number nullable
echo "Making tax_identification_number nullable...\n";
$modify_sql = "ALTER TABLE merchant_tax_info MODIFY COLUMN tax_identification_number VARCHAR(100) DEFAULT NULL";
if (mysqli_query($conn, $modify_sql)) {
    echo "✓ tax_identification_number is now nullable\n\n";
} else {
    echo "⚠️ " . mysqli_error($conn) . "\n\n";
}

$columns_to_add = [
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

$success = 0;
$already_exists = 0;
$errors = 0;

foreach ($columns_to_add as $column_def) {
    preg_match('/^(\w+)/', $column_def, $matches);
    $column_name = $matches[1];
    
    echo "Checking column: $column_name\n";
    
    $check = mysqli_query($conn, "SHOW COLUMNS FROM merchant_tax_info LIKE '$column_name'");
    
    if (mysqli_num_rows($check) > 0) {
        echo "  ⚠️ Already exists\n\n";
        $already_exists++;
    } else {
        $sql = "ALTER TABLE merchant_tax_info ADD COLUMN $column_def";
        if (mysqli_query($conn, $sql)) {
            echo "  ✓ Added successfully\n\n";
            $success++;
        } else {
            echo "  ❌ Error: " . mysqli_error($conn) . "\n\n";
            $errors++;
        }
    }
}

echo str_repeat("-", 60) . "\n";
echo "Summary:\n";
echo "  ✓ Added: $success\n";
echo "  ⚠️ Already existed: $already_exists\n";
echo "  ❌ Errors: $errors\n";

echo "</pre>";

if ($errors == 0) {
    echo "<div style='background:#d4edda;padding:20px;border-radius:5px;margin:20px;'>";
    echo "<h3>✅ Success!</h3>";
    echo "<p>All missing columns have been added to merchant_tax_info table.</p>";
    echo "<p><a href='../merchant/enter_tax_info.php' style='display:inline-block;padding:12px 24px;background:#28a745;color:#fff;text-decoration:none;border-radius:5px;'>Try Tax Info Again</a></p>";
    echo "</div>";
} else {
    echo "<div style='background:#f8d7da;padding:20px;border-radius:5px;margin:20px;'>";
    echo "<h3>⚠️ Some Errors Occurred</h3>";
    echo "<p>Check the errors above.</p>";
    echo "</div>";
}

mysqli_close($conn);
?>
