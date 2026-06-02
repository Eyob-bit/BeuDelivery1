<?php
echo "<h1>Testing Store Upload Simulation</h1>";

// Simulate the exact conditions from the web interface
$_SESSION['user_id'] = 10;
$_SESSION['merchant_id'] = 4;
$_SERVER['REQUEST_METHOD'] = 'POST';

// Create a test image file
$test_image_content = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==');
$temp_file = '/tmp/test_store_image.png';
file_put_contents($temp_file, $test_image_content);

// Simulate $_FILES array
$_FILES['store_images'] = [
    'name' => ['test_store_image.png'],
    'type' => ['image/png'],
    'tmp_name' => [$temp_file],
    'error' => [0],
    'size' => [filesize($temp_file)]
];

echo "<h2>Simulated upload conditions:</h2>";
echo "Session user_id: " . $_SESSION['user_id'] . "<br>";
echo "Session merchant_id: " . $_SESSION['merchant_id'] . "<br>";
echo "Request method: " . $_SERVER['REQUEST_METHOD'] . "<br>";
echo "Files array: <pre>" . print_r($_FILES, true) . "</pre>";

// Test the upload process step by step
echo "<h2>Testing upload process:</h2>";

// Step 1: Authentication check
if (!isset($_SESSION['user_id']) || !isset($_SESSION['merchant_id'])) {
    echo "❌ Authentication failed<br>";
    exit();
}
echo "✅ Authentication passed<br>";

// Step 2: Request method check
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo "❌ Request method check failed<br>";
    exit();
}
echo "✅ Request method check passed<br>";

// Step 3: Files check
if (!isset($_FILES['store_images']) || empty($_FILES['store_images']['name'][0])) {
    echo "❌ Files check failed<br>";
    exit();
}
echo "✅ Files check passed<br>";

// Step 4: Database connection
include "includes/db.php";
if (!$conn) {
    echo "❌ Database connection failed<br>";
    exit();
}
echo "✅ Database connection successful<br>";

$merchant_id = $_SESSION['merchant_id'];

// Step 5: Upload directory
$upload_dir = 'account/uploads/store_images/';
if (!file_exists($upload_dir)) {
    if (mkdir($upload_dir, 0777, true)) {
        echo "✅ Created upload directory<br>";
    } else {
        echo "❌ Failed to create upload directory<br>";
        exit();
    }
} else {
    echo "✅ Upload directory exists<br>";
}

// Step 6: Process file upload
$allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
$max_file_size = 5 * 1024 * 1024; // 5MB

$name = $_FILES['store_images']['name'][0];
$file_type = $_FILES['store_images']['type'][0];
$file_size = $_FILES['store_images']['size'][0];
$tmp_name = $_FILES['store_images']['tmp_name'][0];

echo "<h3>File details:</h3>";
echo "Name: $name<br>";
echo "Type: $file_type<br>";
echo "Size: $file_size bytes<br>";
echo "Temp name: $tmp_name<br>";

// Validate file type
if (!in_array($file_type, $allowed_types)) {
    echo "❌ Invalid file type<br>";
    exit();
}
echo "✅ File type validation passed<br>";

// Validate file size
if ($file_size > $max_file_size) {
    echo "❌ File too large<br>";
    exit();
}
echo "✅ File size validation passed<br>";

// Generate filename
$file_ext = pathinfo($name, PATHINFO_EXTENSION);
$file_name = 'store_' . $merchant_id . '_' . time() . '_0.' . $file_ext;
$file_path = $upload_dir . $file_name;

echo "Generated filename: $file_name<br>";
echo "Full path: $file_path<br>";

// Move uploaded file
if (move_uploaded_file($tmp_name, $file_path)) {
    echo "✅ File moved successfully<br>";
    
    // Save to database
    $sql = "INSERT INTO store_images (merchant_id, image_path, image_name, image_type) VALUES (?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $sql);
    $relative_path = 'uploads/store_images/' . $file_name;
    mysqli_stmt_bind_param($stmt, "isss", $merchant_id, $relative_path, $name, $file_type);
    
    if (mysqli_stmt_execute($stmt)) {
        $insert_id = mysqli_insert_id($conn);
        echo "✅ Database insert successful. ID: $insert_id<br>";
        
        // Verify the record
        $verify_sql = "SELECT * FROM store_images WHERE id = ?";
        $verify_stmt = mysqli_prepare($conn, $verify_sql);
        mysqli_stmt_bind_param($verify_stmt, "i", $insert_id);
        mysqli_stmt_execute($verify_stmt);
        $result = mysqli_stmt_get_result($verify_stmt);
        
        if ($row = mysqli_fetch_assoc($result)) {
            echo "✅ Record verified in database:<br>";
            echo "- ID: {$row['id']}<br>";
            echo "- Merchant ID: {$row['merchant_id']}<br>";
            echo "- Image Path: {$row['image_path']}<br>";
            echo "- Image Name: {$row['image_name']}<br>";
            echo "- Image Type: {$row['image_type']}<br>";
        }
        
        // Check if file exists on disk
        if (file_exists($file_path)) {
            echo "✅ File exists on disk<br>";
            echo "File size on disk: " . filesize($file_path) . " bytes<br>";
        } else {
            echo "❌ File not found on disk<br>";
        }
        
        // Clean up test data
        unlink($file_path);
        mysqli_query($conn, "DELETE FROM store_images WHERE id = $insert_id");
        echo "✅ Test data cleaned up<br>";
        
    } else {
        echo "❌ Database insert failed: " . mysqli_error($conn) . "<br>";
        unlink($file_path);
    }
} else {
    echo "❌ Failed to move uploaded file<br>";
}

echo "<h2>✅ Upload simulation completed!</h2>";
?>