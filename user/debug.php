<?php
// debug.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Debug Information</h2>";

// Test database connection
echo "<h3>1. Testing Database Connection</h3>";
include "../includes/db.php";
if ($conn) {
    echo "✓ Database connection successful<br>";
} else {
    echo "✗ Database connection failed: " . mysqli_connect_error() . "<br>";
}

// Test session
echo "<h3>2. Testing Session</h3>";
session_start();
echo "Session ID: " . session_id() . "<br>";
echo "Session status: " . session_status() . "<br>";

// Test included files
echo "<h3>3. Testing File Includes</h3>";
$files_to_test = [
    '../includes/db.php',
    'includes/auth_check.php',
    '../partials/navbar.php'
];

foreach ($files_to_test as $file) {
    if (file_exists($file)) {
        echo "✓ File exists: $file<br>";
    } else {
        echo "✗ File NOT found: $file<br>";
    }
}

// Simple query test
echo "<h3>4. Testing Database Query</h3>";
$test_query = "SELECT 1 as test";
$result = mysqli_query($conn, $test_query);
if ($result) {
    echo "✓ Basic query successful<br>";
} else {
    echo "✗ Query failed: " . mysqli_error($conn) . "<br>";
}

echo "<hr><h3>Complete Debug Info</h3>";
phpinfo();
?>