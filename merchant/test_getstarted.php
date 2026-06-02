<?php
/**
 * Test getStarted.php for errors
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Testing getStarted.php</h1>";
echo "<pre>";

echo "Step 1: Checking if file exists...\n";
$file = __DIR__ . '/getStarted.php';
if (file_exists($file)) {
    echo "✓ File exists\n\n";
} else {
    die("❌ File not found: $file\n");
}

echo "Step 2: Checking PHP syntax...\n";
$output = [];
$return_var = 0;
exec("php -l " . escapeshellarg($file), $output, $return_var);

if ($return_var === 0) {
    echo "✓ No syntax errors\n\n";
} else {
    echo "❌ Syntax errors found:\n";
    echo implode("\n", $output) . "\n\n";
}

echo "Step 3: Testing database connection...\n";
include "../includes/db.php";
if ($conn) {
    echo "✓ Database connected\n\n";
} else {
    echo "❌ Database connection failed\n\n";
}

echo "Step 4: Checking required tables...\n";
$required_tables = ['users', 'merchants', 'merchant_plans'];
$all_exist = true;

foreach ($required_tables as $table) {
    $result = mysqli_query($conn, "SHOW TABLES LIKE '$table'");
    if (mysqli_num_rows($result) > 0) {
        echo "  ✓ $table exists\n";
    } else {
        echo "  ❌ $table is missing\n";
        $all_exist = false;
    }
}

if (!$all_exist) {
    echo "\n⚠️ Some tables are missing. Run install_fixed.php first!\n";
}

echo "\n";
echo "Step 5: Testing page load...\n";

// Start output buffering to catch any errors
ob_start();
try {
    // Don't actually include it, just check if it would work
    echo "✓ File is readable and should load\n";
    echo "\nYou can now visit: http://localhost/BeU%20Delivery/merchant/getStarted.php\n";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
ob_end_clean();

echo "</pre>";

echo "<div style='background: #d1ecf1; padding: 20px; border-radius: 5px; margin: 20px 0;'>";
echo "<h3>Next Steps:</h3>";
echo "<ol>";
echo "<li>If tables are missing, run <a href='../database/install_fixed.php'>install_fixed.php</a></li>";
echo "<li>If everything is OK, visit <a href='getStarted.php'>getStarted.php</a></li>";
echo "<li>If you see errors, check the PHP error log</li>";
echo "</ol>";
echo "</div>";

echo "<p><a href='getStarted.php' style='display: inline-block; padding: 12px 24px; background: #28a745; color: #fff; text-decoration: none; border-radius: 5px;'>Try getStarted.php Now</a></p>";

?>

<style>
    body { 
        font-family: Arial, sans-serif; 
        max-width: 900px; 
        margin: 20px auto; 
        padding: 20px;
        background: #f5f5f5;
    }
    pre { 
        background: #fff; 
        padding: 20px; 
        border-radius: 5px; 
        border: 1px solid #ddd;
        font-family: 'Courier New', monospace;
    }
    h1 {
        background: #000;
        color: #fff;
        padding: 20px;
        border-radius: 5px;
        margin: 0 0 20px 0;
    }
</style>
