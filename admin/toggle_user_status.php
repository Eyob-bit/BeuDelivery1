<?php
/**
 * Toggle User Status - AJAX Endpoint
 * Activate or deactivate user accounts
 */

session_start();
require_once "admin_auth.php";
include "../includes/db.php";

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['user_id']) || !isset($data['status'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit();
}

$user_id = (int)$data['user_id'];
$status = (int)$data['status'];

// Update user status
$sql = "UPDATE users SET is_active = $status WHERE id = $user_id";

if (mysqli_query($conn, $sql)) {
    echo json_encode([
        'success' => true,
        'message' => 'User status updated successfully'
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . mysqli_error($conn)
    ]);
}
