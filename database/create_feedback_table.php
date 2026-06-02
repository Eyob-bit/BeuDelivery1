<?php
include "includes/db.php";

echo "<h1>Creating Customer Feedback Table</h1>";

// Create customer_feedback table
$create_feedback_table = "
CREATE TABLE IF NOT EXISTS customer_feedback (
    id INT AUTO_INCREMENT PRIMARY KEY,
    merchant_id INT NOT NULL,
    order_id INT NULL,
    customer_id INT NOT NULL,
    customer_name VARCHAR(255) NOT NULL,
    customer_email VARCHAR(255),
    customer_phone VARCHAR(20),
    rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
    feedback_text TEXT,
    feedback_type ENUM('order', 'service', 'food', 'delivery', 'general') DEFAULT 'general',
    is_public TINYINT(1) DEFAULT 1,
    merchant_response TEXT NULL,
    responded_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_merchant_id (merchant_id),
    INDEX idx_customer_id (customer_id),
    INDEX idx_order_id (order_id),
    INDEX idx_rating (rating),
    INDEX idx_created_at (created_at),
    FOREIGN KEY (merchant_id) REFERENCES merchants(merchant_id) ON DELETE CASCADE,
    FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE CASCADE
)";

if (mysqli_query($conn, $create_feedback_table)) {
    echo "✅ customer_feedback table created successfully<br>";
} else {
    echo "❌ Error creating customer_feedback table: " . mysqli_error($conn) . "<br>";
}

// Insert some sample feedback data
echo "<h2>Adding Sample Feedback Data</h2>";

$sample_feedback = [
    [
        'merchant_id' => 4,
        'customer_id' => 1,
        'customer_name' => 'John Doe',
        'customer_email' => 'john@example.com',
        'customer_phone' => '0911234567',
        'rating' => 5,
        'feedback_text' => 'Excellent food and fast delivery! The pizza was hot and delicious.',
        'feedback_type' => 'food'
    ],
    [
        'merchant_id' => 4,
        'customer_id' => 2,
        'customer_name' => 'Sarah Smith',
        'customer_email' => 'sarah@example.com',
        'customer_phone' => '0912345678',
        'rating' => 4,
        'feedback_text' => 'Good service overall, but delivery took a bit longer than expected.',
        'feedback_type' => 'delivery'
    ],
    [
        'merchant_id' => 4,
        'customer_id' => 3,
        'customer_name' => 'Mike Johnson',
        'customer_email' => 'mike@example.com',
        'customer_phone' => '0913456789',
        'rating' => 5,
        'feedback_text' => 'Amazing restaurant! Great atmosphere and friendly staff.',
        'feedback_type' => 'service'
    ],
    [
        'merchant_id' => 4,
        'customer_id' => 4,
        'customer_name' => 'Lisa Brown',
        'customer_email' => 'lisa@example.com',
        'customer_phone' => '0914567890',
        'rating' => 3,
        'feedback_text' => 'Food was okay, but could use more seasoning. Service was good though.',
        'feedback_type' => 'food'
    ],
    [
        'merchant_id' => 4,
        'customer_id' => 5,
        'customer_name' => 'David Wilson',
        'customer_email' => 'david@example.com',
        'customer_phone' => '0915678901',
        'rating' => 5,
        'feedback_text' => 'Outstanding experience! Will definitely order again.',
        'feedback_type' => 'general'
    ]
];

$insert_sql = "INSERT INTO customer_feedback (merchant_id, customer_id, customer_name, customer_email, customer_phone, rating, feedback_text, feedback_type) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
$stmt = mysqli_prepare($conn, $insert_sql);

foreach ($sample_feedback as $feedback) {
    mysqli_stmt_bind_param($stmt, "iisssiis", 
        $feedback['merchant_id'],
        $feedback['customer_id'],
        $feedback['customer_name'],
        $feedback['customer_email'],
        $feedback['customer_phone'],
        $feedback['rating'],
        $feedback['feedback_text'],
        $feedback['feedback_type']
    );
    
    if (mysqli_stmt_execute($stmt)) {
        echo "✅ Added feedback from {$feedback['customer_name']}<br>";
    } else {
        echo "❌ Error adding feedback from {$feedback['customer_name']}: " . mysqli_error($conn) . "<br>";
    }
}

echo "<h2>✅ Customer Feedback System Setup Complete!</h2>";
echo "<p><a href='../account/customer_feedback.php'>View Customer Feedback Page</a></p>";
?>