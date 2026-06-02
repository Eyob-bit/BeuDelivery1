<?php
include "../includes/db.php";

echo "<h1>Testing Image Display</h1>";

// Get store images from database
$images_sql = "SELECT * FROM store_images ORDER BY created_at DESC";
$result = mysqli_query($conn, $images_sql);

if ($result && mysqli_num_rows($result) > 0) {
    echo "<h2>Images in Database:</h2>";
    while ($image = mysqli_fetch_assoc($result)) {
        echo "<div style='border: 1px solid #ccc; margin: 10px; padding: 10px;'>";
        echo "<p><strong>ID:</strong> {$image['id']}</p>";
        echo "<p><strong>Merchant ID:</strong> {$image['merchant_id']}</p>";
        echo "<p><strong>Image Path:</strong> {$image['image_path']}</p>";
        echo "<p><strong>Image Name:</strong> {$image['image_name']}</p>";
        echo "<p><strong>Created:</strong> {$image['created_at']}</p>";
        
        // Test different path variations
        $paths_to_test = [
            $image['image_path'],                    // uploads/store_images/filename.png
            '../' . $image['image_path'],           // ../uploads/store_images/filename.png
            '/' . $image['image_path'],             // /uploads/store_images/filename.png
        ];
        
        echo "<h4>Testing different paths:</h4>";
        foreach ($paths_to_test as $index => $path) {
            echo "<p><strong>Path {$index}:</strong> $path</p>";
            if (file_exists($path)) {
                echo "<p style='color: green;'>✅ File exists on disk</p>";
                echo "<img src='$path' style='max-width: 200px; height: auto; border: 1px solid #ddd;' alt='Test Image'>";
            } else {
                echo "<p style='color: red;'>❌ File not found on disk</p>";
                echo "<img src='$path' style='max-width: 200px; height: auto; border: 1px solid #ddd; background: #f0f0f0;' alt='Broken Image' onerror='this.style.display=\"none\"; this.nextElementSibling.style.display=\"block\";'>";
                echo "<div style='display: none; width: 200px; height: 150px; background: #f0f0f0; border: 1px solid #ddd; text-align: center; line-height: 150px;'>Image not found</div>";
            }
            echo "<br><br>";
        }
        echo "</div>";
    }
} else {
    echo "<p>No images found in database</p>";
}

// Also check what files actually exist
echo "<h2>Files on Disk:</h2>";
$upload_dir = 'uploads/store_images/';
if (is_dir($upload_dir)) {
    $files = scandir($upload_dir);
    foreach ($files as $file) {
        if ($file != '.' && $file != '..') {
            $full_path = $upload_dir . $file;
            echo "<p>File: $file (Size: " . filesize($full_path) . " bytes)</p>";
            echo "<img src='$full_path' style='max-width: 200px; height: auto; border: 1px solid #ddd;' alt='$file'><br><br>";
        }
    }
} else {
    echo "<p>Upload directory not found</p>";
}
?>