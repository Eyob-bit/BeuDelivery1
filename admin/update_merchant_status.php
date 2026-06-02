<?php
/**
 * Update Merchant Status - AJAX Handler
 * Handles approval and rejection of merchant applications
 */

session_start();
require_once "admin_auth.php";
include "../includes/db.php";

header('Content-Type: application/json');

$response = [
    'success' => false,
    'message' => '',
    'data' => null
];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Invalid request method';
    echo json_encode($response);
    exit();
}

$action = $_POST['action'] ?? '';
$merchant_id = $_POST['merchant_id'] ?? '';
$admin_id = $_SESSION['user_id'] ?? null;

if (empty($merchant_id) || empty($admin_id)) {
    $response['message'] = 'Missing required parameters';
    echo json_encode($response);
    exit();
}

$merchant_id = mysqli_real_escape_string($conn, $merchant_id);

mysqli_begin_transaction($conn);

try {
    if ($action === 'approve') {
        $comments = mysqli_real_escape_string($conn, $_POST['comments'] ?? 'Approved by admin');
        
        // Update merchant status to active
        $update_merchant = "UPDATE merchants SET 
            status = 'active',
            updated_at = NOW()
            WHERE merchant_id = '$merchant_id'";
        
        if (!mysqli_query($conn, $update_merchant)) {
            throw new Exception('Failed to update merchant status: ' . mysqli_error($conn));
        }
        
        // Create or update merchant_reviews table if it doesn't exist
        $create_reviews_table = "CREATE TABLE IF NOT EXISTS `merchant_reviews` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `review_id` VARCHAR(50) UNIQUE,
            `merchant_id` INT NOT NULL,
            `status` ENUM('pending', 'in_review', 'approved', 'rejected', 'needs_info') DEFAULT 'pending',
            `reviewer_id` INT DEFAULT NULL,
            `admin_comments` TEXT DEFAULT NULL,
            `notes` TEXT DEFAULT NULL,
            `submitted_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `reviewed_at` TIMESTAMP NULL DEFAULT NULL,
            `reviewed_by` INT DEFAULT NULL,
            `estimated_completion` DATE DEFAULT NULL,
            `rejection_reason` TEXT DEFAULT NULL,
            `verification_score` INT DEFAULT NULL,
            FOREIGN KEY (`merchant_id`) REFERENCES `merchants`(`merchant_id`) ON DELETE CASCADE,
            INDEX `idx_merchant_id` (`merchant_id`),
            INDEX `idx_status` (`status`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        
        mysqli_query($conn, $create_reviews_table);
        
        // Update or insert review record
        $check_review = "SELECT id FROM merchant_reviews WHERE merchant_id = '$merchant_id'";
        $review_result = mysqli_query($conn, $check_review);
        
        if ($review_result && mysqli_num_rows($review_result) > 0) {
            // Update existing review
            $update_review = "UPDATE merchant_reviews SET 
                status = 'approved',
                admin_comments = '$comments',
                reviewed_at = NOW(),
                reviewed_by = '$admin_id',
                verification_score = 100
                WHERE merchant_id = '$merchant_id'";
            
            if (!mysqli_query($conn, $update_review)) {
                throw new Exception('Failed to update review: ' . mysqli_error($conn));
            }
        } else {
            // Insert new review
            $review_id = 'REV' . date('Ymd') . str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);
            $insert_review = "INSERT INTO merchant_reviews 
                (review_id, merchant_id, status, admin_comments, reviewed_at, reviewed_by, verification_score, submitted_at)
                VALUES ('$review_id', '$merchant_id', 'approved', '$comments', NOW(), '$admin_id', 100, NOW())";
            
            if (!mysqli_query($conn, $insert_review)) {
                throw new Exception('Failed to create review: ' . mysqli_error($conn));
            }
        }
        
        mysqli_commit($conn);
        
        $response['success'] = true;
        $response['message'] = 'Merchant approved successfully! They can now access their active dashboard.';
        $response['data'] = [
            'merchant_id' => $merchant_id,
            'new_status' => 'active',
            'reviewed_at' => date('Y-m-d H:i:s')
        ];
        
    } elseif ($action === 'reject') {
        $reason = mysqli_real_escape_string($conn, $_POST['reason'] ?? 'Application rejected');
        
        // Update merchant status to inactive
        $update_merchant = "UPDATE merchants SET 
            status = 'inactive',
            updated_at = NOW()
            WHERE merchant_id = '$merchant_id'";
        
        if (!mysqli_query($conn, $update_merchant)) {
            throw new Exception('Failed to update merchant status: ' . mysqli_error($conn));
        }
        
        // Create table if needed
        $create_reviews_table = "CREATE TABLE IF NOT EXISTS `merchant_reviews` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `review_id` VARCHAR(50) UNIQUE,
            `merchant_id` INT NOT NULL,
            `status` ENUM('pending', 'in_review', 'approved', 'rejected', 'needs_info') DEFAULT 'pending',
            `reviewer_id` INT DEFAULT NULL,
            `admin_comments` TEXT DEFAULT NULL,
            `notes` TEXT DEFAULT NULL,
            `submitted_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `reviewed_at` TIMESTAMP NULL DEFAULT NULL,
            `reviewed_by` INT DEFAULT NULL,
            `estimated_completion` DATE DEFAULT NULL,
            `rejection_reason` TEXT DEFAULT NULL,
            `verification_score` INT DEFAULT NULL,
            FOREIGN KEY (`merchant_id`) REFERENCES `merchants`(`merchant_id`) ON DELETE CASCADE,
            INDEX `idx_merchant_id` (`merchant_id`),
            INDEX `idx_status` (`status`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        
        mysqli_query($conn, $create_reviews_table);
        
        // Update or insert review record
        $check_review = "SELECT id FROM merchant_reviews WHERE merchant_id = '$merchant_id'";
        $review_result = mysqli_query($conn, $check_review);
        
        if ($review_result && mysqli_num_rows($review_result) > 0) {
            // Update existing review
            $update_review = "UPDATE merchant_reviews SET 
                status = 'rejected',
                rejection_reason = '$reason',
                admin_comments = '$reason',
                reviewed_at = NOW(),
                reviewed_by = '$admin_id',
                verification_score = 0
                WHERE merchant_id = '$merchant_id'";
            
            if (!mysqli_query($conn, $update_review)) {
                throw new Exception('Failed to update review: ' . mysqli_error($conn));
            }
        } else {
            // Insert new review
            $review_id = 'REV' . date('Ymd') . str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);
            $insert_review = "INSERT INTO merchant_reviews 
                (review_id, merchant_id, status, rejection_reason, admin_comments, reviewed_at, reviewed_by, verification_score, submitted_at)
                VALUES ('$review_id', '$merchant_id', 'rejected', '$reason', '$reason', NOW(), '$admin_id', 0, NOW())";
            
            if (!mysqli_query($conn, $insert_review)) {
                throw new Exception('Failed to create review: ' . mysqli_error($conn));
            }
        }
        
        mysqli_commit($conn);
        
        $response['success'] = true;
        $response['message'] = 'Merchant application rejected. Notification sent to merchant.';
        $response['data'] = [
            'merchant_id' => $merchant_id,
            'new_status' => 'inactive',
            'reviewed_at' => date('Y-m-d H:i:s'),
            'reason' => $reason
        ];
        
    } else {
        throw new Exception('Invalid action specified');
    }
    
} catch (Exception $e) {
    mysqli_rollback($conn);
    $response['success'] = false;
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
exit();
?>
