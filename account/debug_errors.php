<?php
// Enable error reporting to see what's wrong
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
include "../includes/db.php";

echo "<h2>Debug Page Errors</h2>";

// Test database connection
echo "<h3>Database Connection:</h3>";
if ($conn) {
    echo "✅ Database connected successfully<br>";
} else {
    echo "❌ Database connection failed: " . mysqli_connect_error() . "<br>";
}

// Test session
echo "<h3>Session Info:</h3>";
echo "User ID: " . ($_SESSION['user_id'] ?? 'Not set') . "<br>";
echo "Merchant ID: " . ($_SESSION['merchant_id'] ?? 'Not set') . "<br>";

// Test each page
$pages = ['orders.php', 'reports.php', 'settings.php'];

echo "<h3>Page Tests:</h3>";
foreach ($pages as $page) {
    echo "<h4>Testing $page:</h4>";
    
    // Check if file exists
    if (file_exists($page)) {
        echo "✅ File exists<br>";
        
        // Try to include and catch errors
        ob_start();
        try {
            // Capture any output/errors
            $error_output = '';
            set_error_handler(function($severity, $message, $file, $line) use (&$error_output) {
                $error_output .= "Error: $message in $file on line $line<br>";
            });
            
            // Don't actually include, just check syntax
            $content = file_get_contents($page);
            if (strpos($content, '<?php') !== false) {
                echo "✅ PHP file format correct<br>";
            }
            
            restore_error_handler();
            
            if ($error_output) {
                echo "❌ Errors found:<br>$error_output";
            } else {
                echo "✅ No syntax errors detected<br>";
            }
            
        } catch (Exception $e) {
            echo "❌ Exception: " . $e->getMessage() . "<br>";
        }
        ob_end_clean();
        
        echo "<a href='$page' target='_blank'>🔗 Test $page</a><br>";
    } else {
        echo "❌ File does not exist<br>";
    }
    echo "<br>";
}

// Test includes
echo "<h3>Include Files:</h3>";
$includes = ['includes/sidebar_only.php', '../includes/db.php'];
foreach ($includes as $include) {
    if (file_exists($include)) {
        echo "✅ $include exists<br>";
    } else {
        echo "❌ $include missing<br>";
    }
}
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
h2, h3, h4 { color: #333; }
</style>