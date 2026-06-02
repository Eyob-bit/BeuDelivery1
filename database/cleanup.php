<?php
/**
 * Database Cleanup Script
 * Use this if install.php fails due to foreign key constraints
 * This will completely drop and recreate the database
 */

// Database configuration
$host = "localhost";
$user = "root";
$password = "";
$database = "beu_delivery_v2";

// Create connection
$conn = mysqli_connect($host, $user, $password);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

echo "<h2>Database Cleanup</h2>";
echo "<pre>";

// Drop the entire database
echo "Dropping database '$database'...\n";
if (mysqli_query($conn, "DROP DATABASE IF EXISTS `$database`")) {
    echo "✓ Database dropped successfully\n";
} else {
    echo "✗ Error dropping database: " . mysqli_error($conn) . "\n";
}

// Recreate the database
echo "\nCreating fresh database '$database'...\n";
if (mysqli_query($conn, "CREATE DATABASE `$database` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci")) {
    echo "✓ Database created successfully\n";
} else {
    echo "✗ Error creating database: " . mysqli_error($conn) . "\n";
}

echo "</pre>";

echo "<div style='background: #d4edda; padding: 20px; border-radius: 5px; margin: 20px 0;'>";
echo "<h3 style='color: #155724; margin-top: 0;'>✓ Cleanup Complete!</h3>";
echo "<p>The database has been completely reset.</p>";
echo "<p><strong>Next step:</strong> <a href='install.php'>Run the installation script</a></p>";
echo "</div>";

echo "<style>
    body { font-family: Arial, sans-serif; max-width: 900px; margin: 50px auto; padding: 20px; }
    pre { background: #f5f5f5; padding: 15px; border-radius: 5px; }
    a { color: #007bff; text-decoration: none; }
    a:hover { text-decoration: underline; }
</style>";

mysqli_close($conn);
?>
