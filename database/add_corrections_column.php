<?php
/**
 * Add corrections_needed column to merchant_reviews table
 */

$host = "localhost";
$user = "root";
$password = "";
$database = "beu_delivery_v2";

$conn = mysqli_connect($host, $user, $password, $database);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

echo "Adding corrections_needed column to merchant_reviews table...<br>";

$sql = "ALTER TABLE merchant_reviews ADD COLUMN IF NOT EXISTS corrections_needed TEXT DEFAULT NULL AFTER admin_comments";

if (mysqli_query($conn, $sql)) {
    echo "✅ Column added successfully!<br>";
} else {
    // Column might already exist
    echo "Note: " . mysqli_error($conn) . "<br>";
}

mysqli_close($conn);
echo "<br><a href='../admin/admin_merchants.php'>Go to Admin Panel</a>";
?>
