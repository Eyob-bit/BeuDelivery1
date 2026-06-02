<?php
session_start();
require_once "admin_auth.php";
include "../includes/db.php";

echo "<h2>Debug Merchants Query</h2>";

// Get filters
$status = isset($_GET['status']) ? mysqli_real_escape_string($conn, $_GET['status']) : 'all';
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';

echo "<p><strong>Status filter:</strong> $status</p>";
echo "<p><strong>Search:</strong> " . ($search ?: 'none') . "</p>";

// Build where conditions
$where = [];
if ($status !== 'all') {
    $where[] = "m.status = '$status'";
}
if (!empty($search)) {
    $where[] = "(m.store_name LIKE '%$search%' OR u.first_name LIKE '%$search%' OR u.last_name LIKE '%$search%' OR u.email LIKE '%$search%')";
}
$where_clause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

echo "<p><strong>Where clause:</strong> " . ($where_clause ?: 'none') . "</p>";

// Test query
$test_sql = "SELECT 
    m.merchant_id,
    m.store_name,
    m.status,
    u.first_name,
    u.last_name,
    u.email
    FROM merchants m
    JOIN users u ON m.user_id = u.id
    $where_clause
    ORDER BY m.created_at DESC";

echo "<h3>SQL Query:</h3>";
echo "<pre>" . htmlspecialchars($test_sql) . "</pre>";

$result = mysqli_query($conn, $test_sql);

if (!$result) {
    echo "<p style='color: red;'><strong>Query Error:</strong> " . mysqli_error($conn) . "</p>";
} else {
    $count = mysqli_num_rows($result);
    echo "<p><strong>Results found:</strong> $count</p>";
    
    if ($count > 0) {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ID</th><th>Store Name</th><th>Status</th><th>Owner</th><th>Email</th></tr>";
        while ($row = mysqli_fetch_assoc($result)) {
            echo "<tr>";
            echo "<td>" . $row['merchant_id'] . "</td>";
            echo "<td>" . htmlspecialchars($row['store_name']) . "</td>";
            echo "<td>" . $row['status'] . "</td>";
            echo "<td>" . htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) . "</td>";
            echo "<td>" . htmlspecialchars($row['email']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
}

// Check merchants table directly
echo "<h3>Direct Merchants Table Check:</h3>";
$direct_sql = "SELECT merchant_id, store_name, status, user_id FROM merchants";
$direct_result = mysqli_query($conn, $direct_sql);
echo "<p><strong>Total merchants in table:</strong> " . mysqli_num_rows($direct_result) . "</p>";

if (mysqli_num_rows($direct_result) > 0) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Store Name</th><th>Status</th><th>User ID</th></tr>";
    while ($row = mysqli_fetch_assoc($direct_result)) {
        echo "<tr>";
        echo "<td>" . $row['merchant_id'] . "</td>";
        echo "<td>" . htmlspecialchars($row['store_name']) . "</td>";
        echo "<td>" . $row['status'] . "</td>";
        echo "<td>" . $row['user_id'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}
?>
