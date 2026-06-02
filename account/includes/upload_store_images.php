<?php
session_start();
header('Content-Type: application/json');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['merchant_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit();
}

// Include database connection
$db_path = "../../includes/db.php";
if (!file_exists($db_path)) {
    echo json_encode(['success' => false, 'message' => 'Database connection file not found']);
    exit();
}

include $db_path;

if (!$conn) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

$merchant_id = $_SESSION['merchant_id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

if (!isset($_FILES['store_images']) || empty($_FILES['store_images']['name'][0])) {
    echo json_encode(['success' => false, 'message' => 'No files uploaded']);
    exit();
}

// Set upload directory relative to the script location
$upload_dir = '../uploads/store_images/';
$full_upload_path = realpath(dirname(__FILE__) . '/' . $upload_dir);

if (!$full_upload_path) {
    // Directory doesn't exist, try to create it
    $upload_dir_absolute = dirname(__FILE__) . '/' . $upload_dir;
    if (!mkdir($upload_dir_absolute, 0777, true)) {
        echo json_encode(['success' => false, 'message' => 'Failed to create upload directory']);
        exit();
    }
    $full_upload_path = realpath($upload_dir_absolute);
}

if (!is_writable($full_upload_path)) {
    chmod($full_upload_path, 0777);
}

$allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
$max_file_size = 5 * 1024 * 1024; // 5MB
$uploaded_files = [];
$errors = [];

foreach ($_FILES['store_images']['name'] as $key => $name) {
    if ($_FILES['store_images']['error'][$key] === 0) {
        $file_type = $_FILES['store_images']['type'][$key];
        $file_size = $_FILES['store_images']['size'][$key];
        $tmp_name = $_FILES['store_images']['tmp_name'][$key];
        
        // Validate file type
        if (!in_array($file_type, $allowed_types)) {
            $errors[] = "$name: Invalid file type. Please upload JPG, PNG, or GIF images only.";
            continue;
        }
        
        // Validate file size
        if ($file_size > $max_file_size) {
            $errors[] = "$name: File too large. Maximum size is 5MB.";
            continue;
        }
        
        // Generate unique filename
        $file_ext = pathinfo($name, PATHINFO_EXTENSION);
        $file_name = 'store_' . $merchant_id . '_' . time() . '_' . $key . '.' . $file_ext;
        $file_path = $full_upload_path . '/' . $file_name;
        
        if (move_uploaded_file($tmp_name, $file_path)) {
            // Save to database
            $sql = "INSERT INTO store_images (merchant_id, image_path, image_name, image_type) VALUES (?, ?, ?, ?)";
            $stmt = mysqli_prepare($conn, $sql);
            $relative_path = 'uploads/store_images/' . $file_name;
            mysqli_stmt_bind_param($stmt, "isss", $merchant_id, $relative_path, $name, $file_type);
            
            if (mysqli_stmt_execute($stmt)) {
                $uploaded_files[] = $file_name;
            } else {
                $errors[] = "$name: Database error - " . mysqli_error($conn);
                // Remove the uploaded file if database insert failed
                unlink($file_path);
            }
        } else {
            $errors[] = "$name: Failed to upload file to $file_path";
        }
    } else {
        $errors[] = "$name: Upload error code " . $_FILES['store_images']['error'][$key];
    }
}

if (!empty($uploaded_files)) {
    $message = count($uploaded_files) . " image(s) uploaded successfully!";
    if (!empty($errors)) {
        $message .= " Some files had errors: " . implode(', ', $errors);
    }
    echo json_encode(['success' => true, 'message' => $message, 'uploaded' => $uploaded_files]);
} else {
    echo json_encode(['success' => false, 'message' => 'No files uploaded. Errors: ' . implode(', ', $errors)]);
}
?>