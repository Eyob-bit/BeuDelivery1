<?php
/**
 * Test uploadmenu.php for errors
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html><html><head><title>Test Upload Menu</title>";
echo "<style>body{font-family:Arial;max-width:900px;margin:50px auto;padding:20px;background:#f5f5f5;}";
echo "pre{background:#fff;padding:20px;border-radius:5px;border:1px solid #ddd;}</style></head><body>";

echo "<h1>Testing uploadmenu.php</h1><pre>";

// Step 1: Check file exists
echo "Step 1: Checking file...\n";
$file = __DIR__ . '/uploadmenu.php';
if (file_exists($file)) {
    echo "✓ File exists\n";
    echo "  Size: " . filesize($file) . " bytes\n\n";
} else {
    die("❌ File not found\n");
}

// Step 2: Check for basic syntax issues
echo "Step 2: Checking for common issues...\n";
$content = file_get_contents($file);

// Count braces
$open_braces = substr_count($content, '{');
$close_braces = substr_count($content, '}');
echo "  Braces: $open_braces opening, $close_braces closing";
if ($open_braces != $close_braces) {
    echo " ❌ MISMATCH!\n";
} else {
    echo " ✓\n";
}

// Count parentheses
$open_parens = substr_count($content, '(');
$close_parens = substr_count($content, ')');
echo "  Parentheses: $open_parens opening, $close_parens closing";
if ($open_parens != $close_parens) {
    echo " ❌ MISMATCH!\n";
} else {
    echo " ✓\n";
}

// Check for die() statements
$die_count = substr_count($content, 'die(');
echo "  die() statements: $die_count";
if ($die_count > 0) {
    echo " ⚠️ (may cause issues)\n";
} else {
    echo " ✓\n";
}

echo "\n";

// Step 3: Check database connection
echo "Step 3: Testing database...\n";
include "../includes/db.php";
if ($conn) {
    echo "✓ Database connected\n\n";
} else {
    echo "❌ Database connection failed\n\n";
}

// Step 4: Check required tables
echo "Step 4: Checking tables...\n";
$tables = ['merchant_documents', 'merchant_details'];
foreach ($tables as $table) {
    $result = mysqli_query($conn, "SHOW TABLES LIKE '$table'");
    if (mysqli_num_rows($result) > 0) {
        echo "  ✓ $table\n";
    } else {
        echo "  ❌ $table (missing)\n";
    }
}

echo "\n";

// Step 5: Check upload directory
echo "Step 5: Checking upload directory...\n";
$upload_dir = __DIR__ . "/uploads/menus";
if (is_dir($upload_dir)) {
    echo "  ✓ Directory exists: $upload_dir\n";
    if (is_writable($upload_dir)) {
        echo "  ✓ Directory is writable\n";
    } else {
        echo "  ⚠️ Directory is NOT writable\n";
    }
} else {
    echo "  ⚠️ Directory doesn't exist (will be created on upload)\n";
}

echo "\n";

// Step 6: Check session
echo "Step 6: Checking session requirements...\n";
session_start();
if (isset($_SESSION['user_id']) && isset($_SESSION['merchant_id'])) {
    echo "  ✓ Session variables set\n";
    echo "    user_id: {$_SESSION['user_id']}\n";
    echo "    merchant_id: {$_SESSION['merchant_id']}\n";
} else {
    echo "  ⚠️ Session variables not set (you need to login first)\n";
}

echo "</pre>";

echo "<div style='background:#d1ecf1;padding:20px;border-radius:5px;margin:20px 0;'>";
echo "<h3>Summary</h3>";
echo "<p>If all checks passed, the page should work.</p>";
echo "<p><strong>Next steps:</strong></p>";
echo "<ol>";
echo "<li>Make sure you're logged in as a merchant</li>";
echo "<li>Visit <a href='uploadmenu.php'>uploadmenu.php</a></li>";
echo "<li>If you still get HTTP 500, check Apache error logs</li>";
echo "</ol>";
echo "</div>";

echo "<p><a href='uploadmenu.php' style='display:inline-block;padding:12px 24px;background:#28a745;color:#fff;text-decoration:none;border-radius:5px;'>Try Upload Menu Page</a></p>";

echo "</body></html>";
?>
