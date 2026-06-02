<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['merchant_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

include "../includes/db.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $merchant_id = $_SESSION['merchant_id'];
    $item_id = $_POST['item_id'] ?? null;
    $name = mysqli_real_escape_string($conn, $_POST['name'] ?? '');
    $description = mysqli_real_escape_string($conn, $_POST['description'] ?? '');
    $price = floatval($_POST['price'] ?? 0);
    $category_id = !empty($_POST['category_id']) ? intval($_POST['category_id']) : null;
    $is_available = isset($_POST['is_available']) ? 1 : 0;
    
    // Handle file upload
    $image_path = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/menu_items/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        // Validate file type
        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
        $file_type = $_FILES['image']['type'];
        
        if (in_array($file_type, $allowed_types)) {
            $file_extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $image_name = 'item_' . $merchant_id . '_' . time() . '.' . $file_extension;
            $image_path = $upload_dir . $image_name;
            
            if (!move_uploaded_file($_FILES['image']['tmp_name'], $image_path)) {
                echo json_encode(['success' => false, 'message' => 'Failed to upload image']);
                exit();
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid file type. Please upload JPG, PNG, or GIF images only.']);
            exit();
        }
    }
    
    if ($item_id) {
        // UPDATE existing item
        if ($image_path) {
            // Update with new image
            $sql = "UPDATE menu_items SET name = ?, description = ?, price = ?, category_id = ?, image = ?, is_available = ? 
                    WHERE id = ? AND merchant_id = ?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "ssdisiii", $name, $description, $price, $category_id, $image_path, $is_available, $item_id, $merchant_id);
        } else {
            // Update without changing image
            $sql = "UPDATE menu_items SET name = ?, description = ?, price = ?, category_id = ?, is_available = ? 
                    WHERE id = ? AND merchant_id = ?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "ssdiiiii", $name, $description, $price, $category_id, $is_available, $item_id, $merchant_id);
        }
        
        if (mysqli_stmt_execute($stmt)) {
            echo json_encode(['success' => true, 'message' => 'Menu item updated successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error updating menu item: ' . mysqli_error($conn)]);
        }
    } else {
        // INSERT new item
        $sql = "INSERT INTO menu_items (merchant_id, category_id, name, description, price, image, is_available, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "iissdsi", $merchant_id, $category_id, $name, $description, $price, $image_path, $is_available);
        
        if (mysqli_stmt_execute($stmt)) {
            echo json_encode(['success' => true, 'message' => 'Menu item added successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error adding menu item: ' . mysqli_error($conn)]);
        }
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>