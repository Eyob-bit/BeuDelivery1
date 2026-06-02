<?php
session_start();

// Set up session like a logged-in user
$_SESSION['user_id'] = 10;
$_SESSION['merchant_id'] = 4;

echo "<h1>Direct Store Upload Test</h1>";

// Create a test image
$test_image_content = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==');
$temp_file = '/tmp/test_direct_upload.png';
file_put_contents($temp_file, $test_image_content);

// Set up $_FILES array
$_FILES['store_images'] = [
    'name' => ['test_direct_upload.png'],
    'type' => ['image/png'],
    'tmp_name' => [$temp_file],
    'error' => [0],
    'size' => [filesize($temp_file)]
];

$_SERVER['REQUEST_METHOD'] = 'POST';

echo "Session setup complete<br>";
echo "Files array setup complete<br>";
echo "Calling upload script...<br><br>";

// Capture output from the upload script
ob_start();
include 'includes/upload_store_images.php';
$output = ob_get_clean();

echo "<h2>Upload Script Output:</h2>";
echo "<pre>$output</pre>";

// Check if any files were created
echo "<h2>Checking for uploaded files:</h2>";
$upload_dir = 'uploads/store_images/';
$files = glob($upload_dir . 'store_4_*');
if (!empty($files)) {
    echo "✅ Found uploaded files:<br>";
    foreach ($files as $file) {
        echo "- $file (" . filesize($file) . " bytes)<br>";
    }
} else {
    echo "❌ No uploaded files found<br>";
}

// Check database
echo "<h2>Checking database:</h2>";
include "../includes/db.php";
$check_sql = "SELECT * FROM store_images WHERE merchant_id = 4 ORDER BY created_at DESC LIMIT 5";
$result = mysqli_query($conn, $check_sql);
if (mysqli_num_rows($result) > 0) {
    echo "✅ Found records in database:<br>";
    while ($row = mysqli_fetch_assoc($result)) {
        echo "- ID: {$row['id']}, Path: {$row['image_path']}, Created: {$row['created_at']}<br>";
    }
} else {
    echo "❌ No records found in database<br>";
}
?>