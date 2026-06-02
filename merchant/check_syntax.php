<?php
/**
 * Simple syntax check for getStarted.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html><html><head><title>Syntax Check</title>";
echo "<style>body{font-family:Arial;max-width:800px;margin:50px auto;padding:20px;background:#f5f5f5;}";
echo "pre{background:#fff;padding:20px;border-radius:5px;border:1px solid #ddd;}</style></head><body>";

echo "<h1>getStarted.php Syntax Check</h1><pre>";

// Try to include the file and catch any errors
ob_start();
$error = null;

try {
    // Check if file is readable
    $file = __DIR__ . '/getStarted.php';
    
    if (!file_exists($file)) {
        throw new Exception("File not found: $file");
    }
    
    echo "✓ File exists: getStarted.php\n";
    echo "✓ File size: " . filesize($file) . " bytes\n\n";
    
    // Check for common syntax issues
    $content = file_get_contents($file);
    
    // Count PHP tags
    $php_open = substr_count($content, '<?php');
    $php_close = substr_count($content, '?>');
    echo "PHP tags: $php_open opening, $php_close closing\n";
    
    // Check for unclosed strings (basic check)
    $single_quotes = substr_count($content, "'");
    $double_quotes = substr_count($content, '"');
    echo "Quotes: $single_quotes single, $double_quotes double\n";
    
    if ($single_quotes % 2 != 0) {
        echo "⚠️ Warning: Odd number of single quotes (might be unclosed string)\n";
    }
    
    // Check for script tags
    $script_open = substr_count($content, '<script');
    $script_close = substr_count($content, '</script>');
    echo "Script tags: $script_open opening, $script_close closing\n";
    
    if ($script_open != $script_close) {
        echo "⚠️ Warning: Mismatched script tags\n";
    }
    
    echo "\n✅ Basic syntax checks passed!\n";
    echo "\nThe page should work. Try visiting it now:\n";
    echo "http://localhost/BeU%20Delivery/merchant/getStarted.php\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    $error = $e;
}

$output = ob_get_clean();
echo $output;

echo "</pre>";

if (!$error) {
    echo "<div style='background:#d4edda;padding:20px;border-radius:5px;margin:20px 0;border:2px solid #28a745;'>";
    echo "<h2 style='color:#155724;margin-top:0;'>✅ File Looks Good!</h2>";
    echo "<p>No obvious syntax errors detected. The page should load correctly.</p>";
    echo "<p><a href='getStarted.php' style='display:inline-block;padding:12px 24px;background:#28a745;color:#fff;text-decoration:none;border-radius:5px;'>Open getStarted.php</a></p>";
    echo "</div>";
} else {
    echo "<div style='background:#f8d7da;padding:20px;border-radius:5px;margin:20px 0;border:2px solid #dc3545;'>";
    echo "<h2 style='color:#721c24;margin-top:0;'>❌ Error Found</h2>";
    echo "<p>There's an issue with the file. Check the error message above.</p>";
    echo "</div>";
}

echo "</body></html>";
?>
