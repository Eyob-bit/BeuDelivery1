<?php
include "includes/db.php";

echo "<h1>Testing Store Image Upload Functionality</h1>";

// 1. Check if store_images table exists
echo "<h2>1. Checking store_images table...</h2>";
$check_table = mysqli_query($conn, "SHOW TABLES LIKE 'store_images'");
if (mysqli_num_rows($check_table) == 0) {
    echo "❌ store_images table doesn't exist<br>";
    exit();
}
echo "✅ store_images table exists<br>";

// 2. Check table structure
echo "<h2>2. Table structure:</h2>";
$structure = mysqli_query($conn, "DESCRIBE store_images");
echo "<table border='1'><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
while ($row = mysqli_fetch_assoc($structure)) {
    echo "<tr><td>{$row['Field']}</td><td>{$row['Type']}</td><td>{$row['Null']}</td><td>{$row['Key']}</td><td>{$row['Default']}</td></tr>";
}
echo "</table>";

// 3. Check upload directory
echo "<h2>3. Checking upload directory...</h2>";
$upload_dir = 'account/uploads/store_images/';
if (!file_exists($upload_dir)) {
    echo "❌ Upload directory doesn't exist: $upload_dir<br>";
    if (mkdir($upload_dir, 0777, true)) {
        echo "✅ Created upload directory<br>";
    } else {
        echo "❌ Failed to create upload directory<br>";
        exit();
    }
} else {
    echo "✅ Upload directory exists: $upload_dir<br>";
}

// Check permissions
$perms = fileperms($upload_dir);
echo "Directory permissions: " . substr(sprintf('%o', $perms), -4) . "<br>";

if (is_writable($upload_dir)) {
    echo "✅ Directory is writable<br>";
} else {
    echo "❌ Directory is not writable<br>";
    chmod($upload_dir, 0777);
    echo "✅ Fixed directory permissions<br>";
}

// 4. Test database operations
echo "<h2>4. Testing database operations...</h2>";

$test_merchant_id = 4; // Use your merchant ID
$test_image_path = "uploads/store_images/test_image.jpg";

// Test INSERT
$insert_sql = "INSERT INTO store_images (merchant_id, image_path, display_order) VALUES (?, ?, ?)";
$stmt = mysqli_prepare($conn, $insert_sql);
if (!$stmt) {
    echo "❌ Failed to prepare INSERT statement: " . mysqli_error($conn) . "<br>";
    exit();
}

$display_order = 1;
mysqli_stmt_bind_param($stmt, "isi", $test_merchant_id, $test_image_path, $display_order);

if (mysqli_stmt_execute($stmt)) {
    $insert_id = mysqli_insert_id($conn);
    echo "✅ INSERT test passed. New ID: $insert_id<br>";
    
    // Test SELECT
    $select_sql = "SELECT * FROM store_images WHERE id = ?";
    $select_stmt = mysqli_prepare($conn, $select_sql);
    mysqli_stmt_bind_param($select_stmt, "i", $insert_id);
    mysqli_stmt_execute($select_stmt);
    $result = mysqli_stmt_get_result($select_stmt);
    
    if ($row = mysqli_fetch_assoc($result)) {
        echo "✅ SELECT test passed. Found record:<br>";
        echo "- ID: {$row['id']}<br>";
        echo "- Merchant ID: {$row['merchant_id']}<br>";
        echo "- Image Path: {$row['image_path']}<br>";
        echo "- Display Order: {$row['display_order']}<br>";
    }
    
    // Test DELETE (cleanup)
    $delete_sql = "DELETE FROM store_images WHERE id = ?";
    $delete_stmt = mysqli_prepare($conn, $delete_sql);
    mysqli_stmt_bind_param($delete_stmt, "i", $insert_id);
    
    if (mysqli_stmt_execute($delete_stmt)) {
        echo "✅ DELETE test passed. Test record cleaned up<br>";
    } else {
        echo "❌ DELETE test failed: " . mysqli_stmt_error($delete_stmt) . "<br>";
    }
    
} else {
    echo "❌ INSERT test failed: " . mysqli_stmt_error($stmt) . "<br>";
}

// 5. Check upload_store_images.php file
echo "<h2>5. Checking upload_store_images.php file...</h2>";
$upload_file = 'account/includes/upload_store_images.php';
if (file_exists($upload_file)) {
    echo "✅ Upload file exists<br>";
    
    // Check syntax
    $syntax_check = shell_exec("/opt/lampp/bin/php -l $upload_file 2>&1");
    if (strpos($syntax_check, 'No syntax errors') !== false) {
        echo "✅ Upload file syntax is correct<br>";
    } else {
        echo "❌ Upload file has syntax errors:<br>";
        echo "<pre>$syntax_check</pre>";
    }
} else {
    echo "❌ Upload file doesn't exist: $upload_file<br>";
}

// 6. Test file upload simulation
echo "<h2>6. Testing file upload simulation...</h2>";

// Create a test image file
$test_image_content = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==');
$test_file_path = $upload_dir . 'test_upload.png';

if (file_put_contents($test_file_path, $test_image_content)) {
    echo "✅ Test image file created: $test_file_path<br>";
    
    // Check file properties
    $file_size = filesize($test_file_path);
    $file_type = mime_content_type($test_file_path);
    echo "File size: $file_size bytes<br>";
    echo "File type: $file_type<br>";
    
    // Validate file type
    $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
    if (in_array($file_type, $allowed_types)) {
        echo "✅ File type is valid<br>";
    } else {
        echo "❌ File type is not valid<br>";
    }
    
    // Clean up test file
    unlink($test_file_path);
    echo "✅ Test file cleaned up<br>";
} else {
    echo "❌ Failed to create test image file<br>";
}

// 7. Show current store images
echo "<h2>7. Current store images in database:</h2>";
$current_images = mysqli_query($conn, "SELECT * FROM store_images ORDER BY merchant_id, display_order");
if (mysqli_num_rows($current_images) > 0) {
    echo "<table border='1'><tr><th>ID</th><th>Merchant ID</th><th>Image Path</th><th>Display Order</th><th>Created At</th></tr>";
    while ($img = mysqli_fetch_assoc($current_images)) {
        echo "<tr>";
        echo "<td>{$img['id']}</td>";
        echo "<td>{$img['merchant_id']}</td>";
        echo "<td>{$img['image_path']}</td>";
        echo "<td>{$img['display_order']}</td>";
        echo "<td>{$img['created_at']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "No store images found in database<br>";
}

echo "<h2>✅ Store upload debug completed!</h2>";
echo "<p>If all tests passed, the issue might be in the web interface or JavaScript.</p>";
?>