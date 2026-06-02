<?php
/**
 * Get Merchant Details - AJAX Handler
 * Returns complete merchant information for the modal view
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

if (!isset($_GET['id']) || empty($_GET['id'])) {
    $response['message'] = 'Merchant ID is required';
    echo json_encode($response);
    exit();
}

$merchant_id = mysqli_real_escape_string($conn, $_GET['id']);

try {
    // Get comprehensive merchant data
    $merchant_sql = "SELECT 
        m.*,
        u.first_name,
        u.last_name,
        u.email,
        u.phone,
        u.created_at as user_created
        FROM merchants m
        JOIN users u ON m.user_id = u.id
        WHERE m.merchant_id = '$merchant_id'";
    
    $merchant_result = mysqli_query($conn, $merchant_sql);
    
    if (!$merchant_result || mysqli_num_rows($merchant_result) == 0) {
        throw new Exception('Merchant not found');
    }
    
    $merchant = mysqli_fetch_assoc($merchant_result);
    
    // Get merchant details
    $details_sql = "SELECT * FROM merchant_details WHERE merchant_id = '$merchant_id'";
    $details_result = mysqli_query($conn, $details_sql);
    $details = $details_result ? mysqli_fetch_assoc($details_result) : null;
    
    // Get banking info
    $banking_sql = "SELECT * FROM merchant_banking WHERE merchant_id = '$merchant_id'";
    $banking_result = mysqli_query($conn, $banking_sql);
    $banking = $banking_result ? mysqli_fetch_assoc($banking_result) : null;
    
    // Get tax info
    $tax_sql = "SELECT * FROM merchant_tax_info WHERE merchant_id = '$merchant_id'";
    $tax_result = mysqli_query($conn, $tax_sql);
    $tax = $tax_result ? mysqli_fetch_assoc($tax_result) : null;
    
    // Get plan info
    $plan_sql = "SELECT * FROM merchant_plans WHERE merchant_id = '$merchant_id'";
    $plan_result = mysqli_query($conn, $plan_sql);
    $plan = $plan_result ? mysqli_fetch_assoc($plan_result) : null;
    
    // Get review info
    $review_sql = "SELECT * FROM merchant_reviews WHERE merchant_id = '$merchant_id' ORDER BY submitted_at DESC LIMIT 1";
    $review_result = mysqli_query($conn, $review_sql);
    $review = $review_result ? mysqli_fetch_assoc($review_result) : null;
    
    // Get documents
    $docs_sql = "SELECT * FROM merchant_documents WHERE merchant_id = '$merchant_id'";
    $docs_result = mysqli_query($conn, $docs_sql);
    $documents = [];
    if ($docs_result) {
        while ($doc = mysqli_fetch_assoc($docs_result)) {
            $documents[] = $doc;
        }
    }
    
    // Compile all data
    $response['success'] = true;
    $response['data'] = [
        'merchant' => $merchant,
        'details' => $details,
        'banking' => $banking,
        'tax' => $tax,
        'plan' => $plan,
        'review' => $review,
        'documents' => $documents
    ];
    
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
exit();
?>
