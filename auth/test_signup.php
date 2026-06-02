<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Signup Debug Test</h2>";
echo "<pre>";

// Test 1: Session
session_start();
echo "✓ Session started\n";

// Test 2: Database connection
include "../includes/db.php";
if ($conn) {
    echo "✓ Database connected\n";
} else {
    echo "✗ Database connection failed: " . mysqli_connect_error() . "\n";
    exit;
}

// Test 3: Check if users table exists
$result = mysqli_query($conn, "SHOW TABLES LIKE 'users'");
if (mysqli_num_rows($result) > 0) {
    echo "✓ Users table exists\n";
} else {
    echo "✗ Users table does not exist\n";
}

// Test 4: Check if email_verifications table exists
$result = mysqli_query($conn, "SHOW TABLES LIKE 'email_verifications'");
if (mysqli_num_rows($result) > 0) {
    echo "✓ Email verifications table exists\n";
} else {
    echo "✗ Email verifications table does not exist\n";
}

// Test 5: Check users table structure
$result = mysqli_query($conn, "DESCRIBE users");
echo "\n✓ Users table structure:\n";
while ($row = mysqli_fetch_assoc($result)) {
    $null = $row['Null'] == 'YES' ? 'NULL' : 'NOT NULL';
    echo "  - {$row['Field']}: {$row['Type']} {$null}\n";
}

echo "\n</pre>";

echo "<h3>Test Signup Form</h3>";
echo '<form method="POST" action="signup.php">';
echo '<input type="email" name="email" placeholder="Enter email" required><br><br>';
echo '<button type="submit">Test Signup</button>';
echo '</form>';

echo "<p><a href='signup.php'>Go to actual signup page</a></p>";
?>
