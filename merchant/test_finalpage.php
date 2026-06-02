<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Testing finalpage.php Requirements</h2><pre>";

include "../includes/db.php";

echo "Step 1: Database connection... ";
if ($conn) {
    echo "✓ Connected\n\n";
} else {
    die("❌ Failed: " . mysqli_connect_error());
}

echo "Step 2: Checking merchant_reviews table... ";
$check = mysqli_query($conn, "SHOW TABLES LIKE 'merchant_reviews'");
if (mysqli_num_rows($check) > 0) {
    echo "✓ Table exists\n";
    
    echo "Step 3: Checking table structure... \n";
    $cols = mysqli_query($conn, "SHOW COLUMNS FROM merchant_reviews");
    while ($col = mysqli_fetch_assoc($cols)) {
        echo "  - {$col['Field']} ({$col['Type']})\n";
    }
} else {
    echo "❌ Table missing!\n\n";
    echo "Creating merchant_reviews table...\n";
    
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
        echo "✓ Table created successfully!\n";
    } else {
        echo "❌ Failed to create table: " . mysqli_error($conn) . "\n";
    }
}

echo "\nStep 4: Testing session variables...\n";
session_start();
if (isset($_SESSION['user_id'])) {
    echo "  ✓ user_id: {$_SESSION['user_id']}\n";
} else {
    echo "  ⚠️ user_id not set\n";
}

if (isset($_SESSION['merchant_id'])) {
    echo "  ✓ merchant_id: {$_SESSION['merchant_id']}\n";
} else {
    echo "  ⚠️ merchant_id not set\n";
}

echo "\n</pre>";

if (isset($_SESSION['merchant_id'])) {
    echo "<p><a href='finalpage.php' style='padding:10px 20px;background:black;color:white;text-decoration:none;border-radius:5px;'>Test finalpage.php Now</a></p>";
} else {
    echo "<p style='color:red;'>You need to be logged in as a merchant to test finalpage.php</p>";
}
?>
