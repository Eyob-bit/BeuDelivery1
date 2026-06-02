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

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['success' => false, 'message' => 'Invalid input data']);
    exit();
}

$basic = $input['basic'] ?? [];
$hours = $input['hours'] ?? [];

// Update basic merchant info
if (!empty($basic)) {
    $update_fields = [];
    $params = [];
    $param_types = "";
    
    if (!empty($basic['store_name'])) {
        $update_fields[] = "store_name = ?";
        $params[] = $basic['store_name'];
        $param_types .= "s";
    }
    
    if (!empty($basic['brand_name'])) {
        $update_fields[] = "brand_name = ?";
        $params[] = $basic['brand_name'];
        $param_types .= "s";
    }
    
    if (!empty($basic['business_type'])) {
        $update_fields[] = "business_type = ?";
        $params[] = $basic['business_type'];
        $param_types .= "s";
    }
    
    if (!empty($basic['store_address'])) {
        $update_fields[] = "store_address = ?";
        $params[] = $basic['store_address'];
        $param_types .= "s";
    }
    
    if (!empty($basic['mobile_phone'])) {
        $update_fields[] = "mobile_phone = ?";
        $params[] = $basic['mobile_phone'];
        $param_types .= "s";
    }
    
    if (!empty($basic['social_media_website'])) {
        $update_fields[] = "social_media_website = ?";
        $params[] = $basic['social_media_website'];
        $param_types .= "s";
    }
    
    if (!empty($basic['description'])) {
        $update_fields[] = "description = ?";
        $params[] = $basic['description'];
        $param_types .= "s";
    }
    
    if (!empty($update_fields)) {
        $params[] = $merchant_id;
        $param_types .= "i";
        
        $sql = "UPDATE merchants SET " . implode(", ", $update_fields) . " WHERE merchant_id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, $param_types, ...$params);
        
        if (!mysqli_stmt_execute($stmt)) {
            echo json_encode(['success' => false, 'message' => 'Failed to update merchant info: ' . mysqli_error($conn)]);
            exit();
        }
    }
    
    // Update merchant details
    $detail_fields = [];
    $detail_params = [];
    $detail_types = "";
    
    if (!empty($basic['cuisine_types'])) {
        $detail_fields[] = "cuisine_types = ?";
        $detail_params[] = $basic['cuisine_types'];
        $detail_types .= "s";
    }
    
    if (!empty($detail_fields)) {
        $detail_params[] = $merchant_id;
        $detail_types .= "i";
        
        // Check if merchant_details record exists
        $check_sql = "SELECT merchant_id FROM merchant_details WHERE merchant_id = ?";
        $stmt = mysqli_prepare($conn, $check_sql);
        mysqli_stmt_bind_param($stmt, "i", $merchant_id);
        mysqli_stmt_execute($stmt);
        $check_result = mysqli_stmt_get_result($stmt);
        
        if (mysqli_num_rows($check_result) > 0) {
            // Update existing record
            $sql = "UPDATE merchant_details SET " . implode(", ", $detail_fields) . " WHERE merchant_id = ?";
        } else {
            // Insert new record
            $sql = "INSERT INTO merchant_details (merchant_id, " . str_replace(" = ?", "", implode(", ", $detail_fields)) . ") VALUES (?, " . str_repeat("?, ", count($detail_fields) - 1) . "?)";
            array_unshift($detail_params, $merchant_id);
            $detail_types = "i" . $detail_types;
        }
        
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, $detail_types, ...$detail_params);
        
        if (!mysqli_stmt_execute($stmt)) {
            echo json_encode(['success' => false, 'message' => 'Failed to update merchant details: ' . mysqli_error($conn)]);
            exit();
        }
    }
}

// Update store hours
if (!empty($hours)) {
    // Delete existing hours
    $delete_sql = "DELETE FROM store_hours WHERE merchant_id = ?";
    $stmt = mysqli_prepare($conn, $delete_sql);
    mysqli_stmt_bind_param($stmt, "i", $merchant_id);
    mysqli_stmt_execute($stmt);
    
    // Insert new hours
    foreach ($hours as $hour) {
        $sql = "INSERT INTO store_hours (merchant_id, day_of_week, is_closed, open_time, close_time) VALUES (?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $sql);
        $is_closed = $hour['is_closed'] ? 1 : 0;
        $open_time = $hour['is_closed'] ? null : $hour['open_time'];
        $close_time = $hour['is_closed'] ? null : $hour['close_time'];
        
        mysqli_stmt_bind_param($stmt, "iisss", $merchant_id, $hour['day_of_week'], $is_closed, $open_time, $close_time);
        mysqli_stmt_execute($stmt);
    }
}

echo json_encode(['success' => true, 'message' => 'Settings saved successfully!']);
?>