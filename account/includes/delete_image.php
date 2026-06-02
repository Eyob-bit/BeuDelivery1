<?php
session_start();
include "../db.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $merchant_id = $_SESSION['merchant_id'];
    $type = $data['type'] ?? '';
    
    if ($type === 'featured') {
        // Get current featured image filename
        $sql = "SELECT featured_image FROM merchants WHERE merchant_id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $merchant_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $merchant = mysqli_fetch_assoc($result);
        
        if ($merchant['featured_image']) {
            // Delete file
            $file_path = "../uploads/store_images" . $merchant['featured_image'];
            if (file_exists($file_path)) {
                unlink($file_path);
            }
            
            // Update database
            $update_sql = "UPDATE merchants SET featured_image = NULL WHERE merchant_id = ?";
            $stmt = mysqli_prepare($conn, $update_sql);
            mysqli_stmt_bind_param($stmt, "i", $merchant_id);
            
            if (mysqli_stmt_execute($stmt)) {
                echo json_encode(['success' => true, 'message' => 'Featured image removed']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Database error']);
            }
        }
    }
}
?>