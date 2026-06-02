<?php
// Fix store image paths in database
include __DIR__ . "/../includes/db.php";

echo "<h1>Fixing Store Image Paths</h1>";

// Update store image paths to include account/ prefix
$update_sql = "UPDATE store_images 
               SET image_path = CONCAT('account/', image_path) 
               WHERE image_path NOT LIKE 'account/%'";

if (mysqli_query($conn, $update_sql)) {
    $affected = mysqli_affected_rows($conn);
    echo "✅ Updated $affected store image paths<br>";
} else {
    echo "❌ Error updating paths: " . mysqli_error($conn) . "<br>";
}

// Verify the changes
echo "<h2>Updated Paths:</h2>";
$result = mysqli_query($conn, "SELECT merchant_id, image_path FROM store_images");
while ($row = mysqli_fetch_assoc($result)) {
    echo "Merchant {$row['merchant_id']}: {$row['image_path']}<br>";
    
    // Check if file exists
    if (file_exists($row['image_path'])) {
        echo "✅ File exists<br>";
    } else {
        echo "❌ File not found<br>";
    }
    echo "<br>";
}

echo "<h2>✅ Store Image Paths Fixed!</h2>";
?>