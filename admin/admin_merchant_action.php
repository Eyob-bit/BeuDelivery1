<?php
session_start();
include "../includes/db.php";

// Admin check
if (!isset($_SESSION['user_id'])) {
    die("Access denied");
}

$user_id = $_SESSION['user_id'];

$admin_check_sql = "SELECT ur.user_id FROM user_roles ur 
                    JOIN roles r ON ur.role_id = r.id 
                    WHERE ur.user_id = '$user_id' 
                    AND r.name IN ('admin', 'super_admin')";
$admin_check_result = mysqli_query($conn, $admin_check_sql);

if (mysqli_num_rows($admin_check_result) == 0) {
    die("Access denied. Admin privileges required.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $merchant_id = $_POST['merchant_id'] ?? '';
    
    if (empty($merchant_id)) {
        die("Merchant ID required");
    }
    
    switch($action) {
        case 'suspend':
            $update = "UPDATE merchants SET status = 'suspended', updated_at = NOW() 
                      WHERE merchant_id = '$merchant_id'";
            mysqli_query($conn, $update);
            echo "Merchant suspended successfully";
            break;
            
        case 'activate':
            $update = "UPDATE merchants SET status = 'active', updated_at = NOW() 
                      WHERE merchant_id = '$merchant_id'";
            mysqli_query($conn, $update);
            echo "Merchant activated successfully";
            break;
            
        case 'delete':
            // Soft delete - mark as deleted
            $update = "UPDATE merchants SET status = 'deleted', updated_at = NOW() 
                      WHERE merchant_id = '$merchant_id'";
            mysqli_query($conn, $update);
            echo "Merchant deleted successfully";
            break;
            
        default:
            echo "Invalid action";
    }
}
?>