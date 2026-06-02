<?php
/**
 * Fix merchant_reviews table
 * This ensures the table exists for finalpage.php to work
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

$host = "localhost";
$user = "root";
$password = "";
$database = "beu_delivery_v2";

echo "<!DOCTYPE html><html><head><title>Fix Merchant Reviews Table</title>";
echo "<style>body{font-family:Arial;max-width:800px;margin:20px auto;padding:20px;background:#f5f5f5;}";
echo "pre{background:#fff;padding:20px;border-radius:5px;border:1px solid #ddd;font-size:13px;}";
echo ".success{color:#28a745;} .warning{color:#ffc107;} .error{color:#dc3545;}</style></head><body>";

echo "<h1>Fix Merchant Reviews Table</h1><pre>";

$conn = mysqli_connect($host, $user, $password, $database);

if (!$conn) {
    die("❌ Connection failed: " . mysqli_connect_error());
}

echo "✓ Connected to database: $database\n\n";

echo str_repeat("=", 70) . "\n";
echo "CHECKING: merchant_reviews table\n";
echo str_repeat("=", 70) . "\n\n";

// Check if table exists
$check = mysqli_query($conn, "SHOW TABLES LIKE 'merchant_reviews'");

if (mysqli_num_rows($check) > 0) {
    echo "  <span class='success'>✓</span> Table already exists\n\n";
    
    echo "Checking table structure...\n";
    $cols = mysqli_query($conn, "SHOW COLUMNS FROM merchant_reviews");
    while ($col = mysqli_fetch_assoc($cols)) {
        echo "  - {$col['Field']} ({$col['Type']})\n";
    }
    
    echo "\n<span class='success'>✓ Table is ready!</span>\n";
} else {
    echo "  <span class='warning'>⚠️</span> Table does not exist. Creating...\n\n";
    
    $create_sql = "CREATE TABLE `merchant_reviews` (
      `review_id` VARCHAR(50) PRIMARY KEY,
      `merchant_id` INT NOT NULL,
      `status` ENUM('pending', 'in_review', 'approved', 'rejected', 'needs_info') DEFAULT 'pending',
      `reviewer_id` INT DEFAULT NULL,
      `notes` TEXT DEFAULT NULL,
      `submitted_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      `reviewed_at` TIMESTAMP NULL DEFAULT NULL,
      `estimated_completion` DATE DEFAULT NULL,
      `rejection_reason` TEXT DEFAULT NULL,
      FOREIGN KEY (`merchant_id`) REFERENCES `merchants`(`merchant_id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    
    if (mysqli_query($conn, $create_sql)) {
        echo "  <span class='success'>✓</span> Table created successfully!\n";
    } else {
        echo "  <span class='error'>❌</span> Failed to create table: " . mysqli_error($conn) . "\n";
    }
}

echo "\n" . str_repeat("=", 70) . "\n";
echo "SUMMARY\n";
echo str_repeat("=", 70) . "\n";

// Verify table exists now
$verify = mysqli_query($conn, "SHOW TABLES LIKE 'merchant_reviews'");
if (mysqli_num_rows($verify) > 0) {
    echo "<span class='success'>✓ merchant_reviews table is ready!</span>\n";
    
    echo "</pre>";
    echo "<div style='background:#d4edda;padding:30px;border-radius:10px;margin:20px 0;border:2px solid #28a745;'>";
    echo "<h2 style='color:#155724;margin-top:0;'>🎉 Success!</h2>";
    echo "<p><strong>The merchant_reviews table is now ready.</strong></p>";
    echo "<p>You can now use finalpage.php without errors.</p>";
    echo "<p><a href='../merchant/finalpage.php' style='display:inline-block;padding:15px 30px;background:#28a745;color:#fff;text-decoration:none;border-radius:5px;font-size:16px;font-weight:bold;'>Test Final Page</a></p>";
    echo "</div>";
} else {
    echo "<span class='error'>❌ Table still missing. Check errors above.</span>\n";
    echo "</pre>";
}

mysqli_close($conn);
echo "</body></html>";
?>
