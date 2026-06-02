<?php
/**
 * Database Table Checker
 * Shows what tables exist and what's missing
 */

$host = "localhost";
$user = "root";
$password = "";
$database = "beu_delivery_v2";

$conn = mysqli_connect($host, $user, $password, $database);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

echo "<h2>Database Table Check</h2>";
echo "<h3>Database: $database</h3>";

// Get all tables
$result = mysqli_query($conn, "SHOW TABLES");

echo "<h4>Existing Tables:</h4>";
echo "<pre>";
$existing_tables = [];
while ($row = mysqli_fetch_array($result)) {
    $table_name = $row[0];
    $existing_tables[] = $table_name;
    
    // Get row count
    $count_result = mysqli_query($conn, "SELECT COUNT(*) as count FROM `$table_name`");
    $count = mysqli_fetch_assoc($count_result)['count'];
    
    echo "✓ $table_name ($count rows)\n";
}
echo "</pre>";

// Check for required tables
$required_tables = [
    'users',
    'merchants',
    'menu_items',
    'orders',
    'cart_items',
    'transactions',
    'email_verifications',  // Missing!
    'user_roles',           // Missing!
    'roles'                 // Missing!
];

echo "<h4>Required Tables Status:</h4>";
echo "<pre>";
foreach ($required_tables as $table) {
    if (in_array($table, $existing_tables)) {
        echo "✓ $table - EXISTS\n";
    } else {
        echo "✗ $table - MISSING\n";
    }
}
echo "</pre>";

echo "<style>
    body { font-family: Arial, sans-serif; max-width: 900px; margin: 50px auto; padding: 20px; }
    pre { background: #f5f5f5; padding: 15px; border-radius: 5px; }
    h2 { color: #333; }
    h4 { color: #666; margin-top: 20px; }
</style>";

mysqli_close($conn);
?>
