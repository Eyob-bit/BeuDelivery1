<?php
// add_admin_review_columns.php - Add admin review columns to merchant_reviews table
error_reporting(E_ALL);
ini_set('display_errors', 1);

include "../includes/db.php";

echo "<h2>Adding Admin Review Columns to merchant_reviews Table</h2>";

// Add admin review columns
$columns_to_add = [
    "ALTER TABLE merchant_reviews ADD COLUMN admin_comments TEXT NULL AFTER review_text",
    "ALTER TABLE merchant_reviews ADD COLUMN reviewed_at TIMESTAMP NULL AFTER admin_comments",
    "ALTER TABLE merchant_reviews ADD COLUMN reviewed_by INT(11) NULL AFTER reviewed_at"
];

foreach ($columns_to_add as $sql) {
    echo "<p>Executing: <code>$sql</code></p>";
    
    if (mysqli_query($conn, $sql)) {
        echo "<p style='color: green;'>✓ Success</p>";
    } else {
        $error = mysqli_error($conn);
        if (strpos($error, 'Duplicate column name') !== false) {
            echo "<p style='color: orange;'>⚠ Column already exists</p>";
        } else {
            echo "<p style='color: red;'>✗ Error: $error</p>";
        }
    }
}

echo "<hr>";
echo "<h3>Current merchant_reviews Table Structure:</h3>";
$result = mysqli_query($conn, "DESCRIBE merchant_reviews");
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
while ($row = mysqli_fetch_assoc($result)) {
    echo "<tr>";
    echo "<td>{$row['Field']}</td>";
    echo "<td>{$row['Type']}</td>";
    echo "<td>{$row['Null']}</td>";
    echo "<td>{$row['Key']}</td>";
    echo "<td>{$row['Default']}</td>";
    echo "</tr>";
}
echo "</table>";

echo "<hr>";
echo "<p><strong>Done!</strong> Admin review columns have been added.</p>";
echo "<p><a href='../admin/admin_merchant_details.php?id=3'>Go to Merchant Details</a></p>";

mysqli_close($conn);
?>
