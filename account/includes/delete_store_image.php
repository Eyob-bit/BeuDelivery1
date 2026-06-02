<?php
session_start();
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['merchant_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit();
}

include "../../includes/db.php";
$merchant_id = $_SESSION['merchant_id'];
$image_id = intval($_GET['image_id'] ?? 0);

if ($image_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid image ID']);
    exit();
}

// Get image info and verify ownership
$sql = "SELECT * FROM store_images WHERE id = ? AND merchant_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "ii", $image_id, $merchant_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$image = mysqli_fetch_assoc($result);

if (!$image) {
    echo json_encode(['success' => false, 'message' => 'Image not found or access denied']);
    exit();
}

// Delete file from filesystem
$file_path = '../' . $image['image_path'];
if (file_exists($file_path)) {
    unlink($file_path);
}

// Delete from database
$delete_sql = "DELETE FROM store_images WHERE id = ? AND merchant_id = ?";
$stmt = mysqli_prepare($conn, $delete_sql);
mysqli_stmt_bind_param($stmt, "ii", $image_id, $merchant_id);

if (mysqli_stmt_execute($stmt)) {
    echo json_encode(['success' => true, 'message' => 'Image deleted successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . mysqli_error($conn)]);
}
?>