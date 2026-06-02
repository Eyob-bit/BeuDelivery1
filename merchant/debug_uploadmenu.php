<?php
/**
 * Debug version of uploadmenu.php
 * This will show the actual error
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Debug Upload Menu</h1>";
echo "<pre>";

try {
    echo "Step 1: Starting session...\n";
    session_start();
    echo "✓ Session started\n\n";
    
    echo "Step 2: Including database...\n";
    include "../includes/db.php";
    echo "✓ Database included\n\n";
    
    echo "Step 3: Checking session variables...\n";
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['merchant_id'])) {
        die("❌ Not logged in. Please login first.\n");
    }
    echo "✓ User ID: {$_SESSION['user_id']}\n";
    echo "✓ Merchant ID: {$_SESSION['merchant_id']}\n\n";
    
    $merchant_id = $_SESSION['merchant_id'];
    $user_id = $_SESSION['user_id'];
    
    echo "Step 4: Fetching merchant details...\n";
    $merchant_sql = "SELECT m.*, u.email FROM merchants m 
                     JOIN users u ON m.user_id = u.id 
                     WHERE m.merchant_id = '$merchant_id'";
    $merchant_result = mysqli_query($conn, $merchant_sql);
    
    if (!$merchant_result) {
        die("❌ Query failed: " . mysqli_error($conn) . "\n");
    }
    
    $merchant = mysqli_fetch_assoc($merchant_result);
    if (!$merchant) {
        die("❌ Merchant not found\n");
    }
    echo "✓ Merchant found: {$merchant['store_name']}\n\n";
    
    echo "Step 5: Checking for existing menu...\n";
    $menu_check_sql = "SELECT * FROM merchant_documents WHERE merchant_id = '$merchant_id' 
                       AND document_type IN ('menu_pdf', 'menu_photo', 'menu_link')";
    $menu_check_result = mysqli_query($conn, $menu_check_sql);
    
    if (!$menu_check_result) {
        die("❌ Query failed: " . mysqli_error($conn) . "\n");
    }
    
    $existing_menu = mysqli_fetch_assoc($menu_check_result);
    if ($existing_menu) {
        echo "✓ Existing menu found (type: {$existing_menu['document_type']})\n\n";
    } else {
        echo "✓ No existing menu\n\n";
    }
    
    echo "Step 6: Getting store hours from merchant_details...\n";
    $hours_sql = "SELECT store_hours FROM merchant_details WHERE merchant_id = '$merchant_id'";
    $hours_result = mysqli_query($conn, $hours_sql);
    
    if (!$hours_result) {
        echo "⚠️ Query failed: " . mysqli_error($conn) . "\n";
        $store_hours = "";
    } else {
        $hours_row = mysqli_fetch_assoc($hours_result);
        if ($hours_row && !empty($hours_row['store_hours'])) {
            $store_hours_data = $hours_row['store_hours'];
            echo "✓ Store hours data found\n";
            echo "  Raw data: " . substr($store_hours_data, 0, 100) . "...\n";
            
            // Try to decode JSON
            $decoded = json_decode($store_hours_data, true);
            if ($decoded) {
                echo "  ✓ Valid JSON\n";
                if (isset($decoded['hours'])) {
                    $store_hours = $decoded['hours'];
                } else {
                    $hours_array = [];
                    foreach ($decoded as $day => $hours) {
                        $hours_array[] = "$day: $hours";
                    }
                    $store_hours = implode("\n", $hours_array);
                }
            } else {
                echo "  ⚠️ Not JSON, using as plain text\n";
                $store_hours = $store_hours_data;
            }
        } else {
            echo "⚠️ No store hours found\n";
            $store_hours = "";
        }
    }
    
    if (empty($store_hours)) {
        $store_hours = "Monday - Friday: 10:00AM - 5:00PM\nSaturday: 11:00AM - 4:00PM\nSunday: Closed";
        echo "  Using default store hours\n";
    }
    echo "\n";
    
    echo "Step 7: All checks passed!\n";
    echo "✓ The page should work now\n\n";
    
    echo "Store hours to display:\n";
    echo htmlspecialchars($store_hours) . "\n\n";
    
} catch (Exception $e) {
    echo "\n❌ EXCEPTION: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    echo "\nStack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "</pre>";

echo "<div style='background:#d4edda;padding:20px;border-radius:5px;margin:20px;'>";
echo "<h3>✅ Debug Complete</h3>";
echo "<p>If all steps passed above, the actual page should work.</p>";
echo "<p>The error is likely happening in the HTML/form rendering part.</p>";
echo "<p><a href='uploadmenu.php' style='display:inline-block;padding:12px 24px;background:#28a745;color:#fff;text-decoration:none;border-radius:5px;'>Try Upload Menu Page</a></p>";
echo "</div>";

// Show PHP error log location
echo "<div style='background:#fff3cd;padding:20px;border-radius:5px;margin:20px;'>";
echo "<h3>Check Error Logs</h3>";
echo "<p>If the page still doesn't work, check these logs:</p>";
echo "<ul>";
echo "<li>Apache error log: <code>/opt/lampp/logs/error_log</code></li>";
echo "<li>PHP error log: <code>/opt/lampp/logs/php_error_log</code></li>";
echo "</ul>";
echo "<p>Run in terminal: <code>tail -f /opt/lampp/logs/error_log</code></p>";
echo "</div>";
?>
