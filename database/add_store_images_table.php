<?php
include "includes/db.php";

echo "<h2>Adding Store Images Table</h2>";

// Create store_images table
$sql = "CREATE TABLE IF NOT EXISTS `store_images` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `merchant_id` INT NOT NULL,
  `image_path` VARCHAR(255) NOT NULL,
  `image_name` VARCHAR(255) DEFAULT NULL,
  `image_type` VARCHAR(100) DEFAULT NULL,
  `is_featured` BOOLEAN DEFAULT FALSE,
  `display_order` INT DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`merchant_id`) REFERENCES `merchants`(`merchant_id`) ON DELETE CASCADE,
  INDEX `idx_merchant_images` (`merchant_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

if (mysqli_query($conn, $sql)) {
    echo "✅ Store images table created successfully<br>";
} else {
    echo "❌ Error creating store images table: " . mysqli_error($conn) . "<br>";
}

// Create store_hours table if it doesn't exist
$sql2 = "CREATE TABLE IF NOT EXISTS `store_hours` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `merchant_id` INT NOT NULL,
  `day_of_week` INT NOT NULL,
  `is_closed` BOOLEAN DEFAULT FALSE,
  `open_time` TIME DEFAULT NULL,
  `close_time` TIME DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`merchant_id`) REFERENCES `merchants`(`merchant_id`) ON DELETE CASCADE,
  UNIQUE KEY `unique_merchant_day` (`merchant_id`, `day_of_week`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

if (mysqli_query($conn, $sql2)) {
    echo "✅ Store hours table created successfully<br>";
} else {
    echo "❌ Error creating store hours table: " . mysqli_error($conn) . "<br>";
}

echo "<br><a href='../account/debug_errors.php'>Test Pages</a>";
echo "<br><a href='../account/settings.php'>Go to Settings</a>";
?>