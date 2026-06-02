<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Menu Manager Debug</h1>";

try {
    session_start();
    echo "✅ Session started<br>";
    
    // Check if user is logged in
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['merchant_id'])) {
        echo "❌ Not logged in properly<br>";
        echo "User ID: " . ($_SESSION['user_id'] ?? 'Not set') . "<br>";
        echo "Merchant ID: " . ($_SESSION['merchant_id'] ?? 'Not set') . "<br>";
        exit();
    }
    echo "✅ User logged in: " . $_SESSION['user_id'] . "<br>";
    echo "✅ Merchant ID: " . $_SESSION['merchant_id'] . "<br>";

    include "../includes/db.php";
    echo "✅ Database included<br>";
    
    $merchant_id = $_SESSION['merchant_id'];

    // Test if this is a POST request
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        echo "<h2>POST Request Detected</h2>";
        echo "Action: " . ($_POST['action'] ?? 'Not set') . "<br>";
        
        if (isset($_POST['action']) && $_POST['action'] === 'edit_item') {
            echo "<h3>Processing Edit Item</h3>";
            
            $item_id = intval($_POST['item_id']);
            $name = mysqli_real_escape_string($conn, $_POST['name']);
            $description = mysqli_real_escape_string($conn, $_POST['description']);
            $price = floatval($_POST['price']);
            $category_id = !empty($_POST['category_id']) ? intval($_POST['category_id']) : null;
            $is_available = isset($_POST['is_available']) ? 1 : 0;
            
            echo "Item ID: $item_id<br>";
            echo "Name: $name<br>";
            echo "Description: $description<br>";
            echo "Price: $price<br>";
            echo "Category ID: " . ($category_id ?? 'NULL') . "<br>";
            echo "Is Available: $is_available<br>";
            
            // Check if item exists and belongs to merchant
            $check_sql = "SELECT * FROM menu_items WHERE id = ? AND merchant_id = ?";
            $check_stmt = mysqli_prepare($conn, $check_sql);
            mysqli_stmt_bind_param($check_stmt, "ii", $item_id, $merchant_id);
            mysqli_stmt_execute($check_stmt);
            $check_result = mysqli_stmt_get_result($check_stmt);
            
            if (mysqli_num_rows($check_result) === 0) {
                echo "❌ Item not found or doesn't belong to merchant<br>";
                exit();
            }
            echo "✅ Item exists and belongs to merchant<br>";
            
            // Handle image upload
            if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
                echo "<h4>Processing Image Upload</h4>";
                $upload_dir = 'uploads/menu_items/';
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                    echo "✅ Created upload directory<br>";
                }
                
                // Validate file type
                $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
                $file_type = $_FILES['image']['type'];
                
                if (in_array($file_type, $allowed_types)) {
                    $file_ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                    $file_name = 'item_' . $merchant_id . '_' . time() . '.' . $file_ext;
                    $image_path = $upload_dir . $file_name;
                    
                    if (move_uploaded_file($_FILES['image']['tmp_name'], $image_path)) {
                        echo "✅ Image uploaded: $image_path<br>";
                        $sql = "UPDATE menu_items SET name=?, description=?, price=?, category_id=?, image=?, is_available=? 
                                WHERE id=? AND merchant_id=?";
                        $stmt = mysqli_prepare($conn, $sql);
                        echo "✅ Prepared statement with image<br>";
                        mysqli_stmt_bind_param($stmt, "ssdisiii", $name, $description, $price, $category_id, $image_path, $is_available, $item_id, $merchant_id);
                        echo "✅ Bound parameters with image<br>";
                    } else {
                        echo "❌ Failed to upload image<br>";
                        exit();
                    }
                } else {
                    echo "❌ Invalid file type: $file_type<br>";
                    exit();
                }
            } else {
                echo "<h4>No Image Upload</h4>";
                $sql = "UPDATE menu_items SET name=?, description=?, price=?, category_id=?, is_available=? 
                        WHERE id=? AND merchant_id=?";
                $stmt = mysqli_prepare($conn, $sql);
                echo "✅ Prepared statement without image<br>";
                mysqli_stmt_bind_param($stmt, "ssdiii", $name, $description, $price, $category_id, $is_available, $item_id, $merchant_id);
                echo "✅ Bound parameters without image<br>";
            }
            
            if (mysqli_stmt_execute($stmt)) {
                echo "✅ Menu item updated successfully!<br>";
            } else {
                echo "❌ Error updating menu item: " . mysqli_error($conn) . "<br>";
            }
        }
    } else {
        echo "<h2>GET Request - Showing Form</h2>";
        
        // Get a sample menu item for testing
        $sample_sql = "SELECT * FROM menu_items WHERE merchant_id = ? LIMIT 1";
        $sample_stmt = mysqli_prepare($conn, $sample_sql);
        mysqli_stmt_bind_param($sample_stmt, "i", $merchant_id);
        mysqli_stmt_execute($sample_stmt);
        $sample_result = mysqli_stmt_get_result($sample_stmt);
        $sample_item = mysqli_fetch_assoc($sample_result);
        
        if ($sample_item) {
            echo "<h3>Test Edit Form</h3>";
            echo "<form method='POST' enctype='multipart/form-data'>";
            echo "<input type='hidden' name='action' value='edit_item'>";
            echo "<input type='hidden' name='item_id' value='" . $sample_item['id'] . "'>";
            echo "<p>Item: " . htmlspecialchars($sample_item['name']) . "</p>";
            echo "<input type='text' name='name' value='" . htmlspecialchars($sample_item['name']) . "' required><br><br>";
            echo "<textarea name='description'>" . htmlspecialchars($sample_item['description']) . "</textarea><br><br>";
            echo "<input type='number' name='price' value='" . $sample_item['price'] . "' step='0.01' required><br><br>";
            echo "<input type='file' name='image' accept='image/*'><br><br>";
            echo "<input type='checkbox' name='is_available' " . ($sample_item['is_available'] ? 'checked' : '') . "> Available<br><br>";
            echo "<button type='submit'>Test Update</button>";
            echo "</form>";
        } else {
            echo "❌ No menu items found for testing<br>";
        }
    }

} catch (Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . "<br>";
    echo "File: " . $e->getFile() . "<br>";
    echo "Line: " . $e->getLine() . "<br>";
} catch (Error $e) {
    echo "❌ Fatal Error: " . $e->getMessage() . "<br>";
    echo "File: " . $e->getFile() . "<br>";
    echo "Line: " . $e->getLine() . "<br>";
}
?>