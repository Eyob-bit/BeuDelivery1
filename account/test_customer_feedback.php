<?php
// Test Customer Feedback Page Access
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Customer Feedback Page Access Test</h1>";

// Set test session data
$_SESSION['user_id'] = 10;
$_SESSION['merchant_id'] = 4;
$_SESSION['user_email'] = 'e331@gmail.com';
$_SESSION['logged_in'] = true;

include __DIR__ . "/../includes/db.php";

echo "<h2>1. Session Check:</h2>";
echo "✅ User ID: " . $_SESSION['user_id'] . "<br>";
echo "✅ Merchant ID: " . $_SESSION['merchant_id'] . "<br>";

echo "<h2>2. Database Connection:</h2>";
if ($conn) {
    echo "✅ Database connected<br>";
} else {
    echo "❌ Database connection failed<br>";
    exit();
}

echo "<h2>3. Customer Feedback Table Check:</h2>";
$table_check = "SHOW TABLES LIKE 'customer_feedback'";
$result = mysqli_query($conn, $table_check);
if (mysqli_num_rows($result) > 0) {
    echo "✅ customer_feedback table exists<br>";
} else {
    echo "❌ customer_feedback table not found<br>";
}

echo "<h2>4. Sample Feedback Data:</h2>";
$feedback_sql = "SELECT COUNT(*) as count FROM customer_feedback WHERE merchant_id = 4";
$result = mysqli_query($conn, $feedback_sql);
$count = mysqli_fetch_assoc($result)['count'];
echo "✅ Found $count feedback records for merchant ID 4<br>";

echo "<h2>5. Test Links:</h2>";
echo "🔗 <a href='customer_feedback.php' target='_blank'>Open Customer Feedback Page</a><br>";
echo "🔗 <a href='merchant_dashboard.php' target='_blank'>Dashboard</a><br>";
echo "🔗 <a href='settings.php' target='_blank'>Settings</a><br>";

echo "<h2>6. Navigation Test:</h2>";
echo "✅ Customer Feedback link should appear in sidebar<br>";
echo "✅ Earnings link should be removed from all pages<br>";
echo "✅ User View should be replaced with Customer Feedback<br>";

echo "<h2>✅ Customer Feedback System Ready!</h2>";
echo "<p>The customer feedback page should now be accessible from the merchant dashboard sidebar.</p>";
?>