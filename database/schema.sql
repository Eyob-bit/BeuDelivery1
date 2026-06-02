-- BeU Delivery Database Schema
-- Complete database structure for food delivery platform

-- Disable foreign key checks temporarily
SET FOREIGN_KEY_CHECKS = 0;

-- Drop existing tables if they exist (order doesn't matter with FK checks disabled)
DROP TABLE IF EXISTS `merchant_earnings`;
DROP TABLE IF EXISTS `merchant_reviews`;
DROP TABLE IF EXISTS `order_tracking`;
DROP TABLE IF EXISTS `order_items`;
DROP TABLE IF EXISTS `orders`;
DROP TABLE IF EXISTS `cart_items`;
DROP TABLE IF EXISTS `menu_items`;
DROP TABLE IF EXISTS `menu_categories`;
DROP TABLE IF EXISTS `delivery_settings`;
DROP TABLE IF EXISTS `merchant_tax_info`;
DROP TABLE IF EXISTS `merchant_banking`;
DROP TABLE IF EXISTS `merchant_documents`;
DROP TABLE IF EXISTS `merchant_plans`;
DROP TABLE IF EXISTS `merchant_details`;
DROP TABLE IF EXISTS `merchants`;
DROP TABLE IF EXISTS `store_categories`;
DROP TABLE IF EXISTS `user_addresses`;
DROP TABLE IF EXISTS `payment_methods`;
DROP TABLE IF EXISTS `transactions`;
DROP TABLE IF EXISTS `notifications`;
DROP TABLE IF EXISTS `favorites`;
DROP TABLE IF EXISTS `user_roles`;
DROP TABLE IF EXISTS `roles`;
DROP TABLE IF EXISTS `email_verifications`;
DROP TABLE IF EXISTS `users`;

-- Re-enable foreign key checks
SET FOREIGN_KEY_CHECKS = 1;

-- Users table (customers, merchants, admins, delivery drivers)
CREATE TABLE `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `phone` VARCHAR(20) DEFAULT NULL,
  `email` VARCHAR(255) DEFAULT NULL,
  `first_name` VARCHAR(100) DEFAULT NULL,
  `last_name` VARCHAR(100) DEFAULT NULL,
  `password_hash` VARCHAR(255) DEFAULT NULL,
  `user_type` ENUM('customer', 'merchant', 'admin', 'delivery', 'owner') DEFAULT 'customer',
  `profile_image` VARCHAR(255) DEFAULT NULL,
  `is_verified` BOOLEAN DEFAULT FALSE,
  `is_active` BOOLEAN DEFAULT TRUE,
  `agreed_to_terms` BOOLEAN DEFAULT FALSE,
  `verification_code` VARCHAR(10) DEFAULT NULL,
  `verification_expires` DATETIME DEFAULT NULL,
  `last_login` DATETIME DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `unique_phone` (`phone`),
  UNIQUE KEY `unique_email` (`email`),
  INDEX `idx_phone` (`phone`),
  INDEX `idx_email` (`email`),
  INDEX `idx_user_type` (`user_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Email Verifications (for signup/login codes)
CREATE TABLE `email_verifications` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `email` VARCHAR(255) NOT NULL,
  `code` VARCHAR(10) NOT NULL,
  `expires_at` DATETIME NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_email_code` (`email`, `code`),
  INDEX `idx_expires` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Roles (for role-based access control)
CREATE TABLE `roles` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(50) NOT NULL UNIQUE,
  `description` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- User Roles (many-to-many relationship)
CREATE TABLE `user_roles` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `role_id` INT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`role_id`) REFERENCES `roles`(`id`) ON DELETE CASCADE,
  UNIQUE KEY `unique_user_role` (`user_id`, `role_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Store Categories
CREATE TABLE `store_categories` (
  `category_id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `icon` VARCHAR(100) DEFAULT NULL,
  `description` TEXT DEFAULT NULL,
  `is_active` BOOLEAN DEFAULT TRUE,
  `display_order` INT DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Merchants/Stores
CREATE TABLE `merchants` (
  `merchant_id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `store_name` VARCHAR(255) NOT NULL,
  `brand_name` VARCHAR(255) DEFAULT NULL,
  `business_type` VARCHAR(100) DEFAULT NULL,
  `store_address` TEXT NOT NULL,
  `floor_suite` VARCHAR(100) DEFAULT NULL,
  `mobile_phone` VARCHAR(20) DEFAULT NULL,
  `social_media_website` VARCHAR(255) DEFAULT NULL,
  `opt_in_sms` BOOLEAN DEFAULT FALSE,
  `store_type` ENUM('restaurant', 'grocery', 'pharmacy', 'retail') DEFAULT 'restaurant',
  `category_id` INT DEFAULT NULL,
  `featured_image` VARCHAR(255) DEFAULT NULL,
  `cover_image` VARCHAR(255) DEFAULT NULL,
  `logo` VARCHAR(255) DEFAULT NULL,
  `description` TEXT DEFAULT NULL,
  `status` ENUM('setup', 'under_review', 'active', 'inactive', 'suspended') DEFAULT 'setup',
  `is_featured` BOOLEAN DEFAULT FALSE,
  `rating` DECIMAL(3,2) DEFAULT 0.00,
  `review_count` INT DEFAULT 0,
  `total_orders` INT DEFAULT 0,
  `plan_type` ENUM('basic', 'premium', 'enterprise') DEFAULT 'basic',
  `commission_rate` DECIMAL(5,2) DEFAULT 15.00,
  `tax_id` VARCHAR(50) DEFAULT NULL,
  `business_license` VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`category_id`) REFERENCES `store_categories`(`category_id`) ON DELETE SET NULL,
  INDEX `idx_status` (`status`),
  INDEX `idx_featured` (`is_featured`),
  INDEX `idx_rating` (`rating`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Merchant Details
CREATE TABLE `merchant_details` (
  `detail_id` INT AUTO_INCREMENT PRIMARY KEY,
  `merchant_id` INT NOT NULL,
  `store_phone` VARCHAR(20) DEFAULT NULL,
  `store_email` VARCHAR(255) DEFAULT NULL,
  `cuisine_types` JSON DEFAULT NULL,
  `store_hours` JSON DEFAULT NULL,
  `pickup_instructions` TEXT DEFAULT NULL,
  `launch_date` DATE DEFAULT NULL,
  `special_instructions` TEXT DEFAULT NULL,
  `accepts_cash` BOOLEAN DEFAULT TRUE,
  `accepts_card` BOOLEAN DEFAULT TRUE,
  `latitude` DECIMAL(10,8) DEFAULT NULL,
  `longitude` DECIMAL(11,8) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`merchant_id`) REFERENCES `merchants`(`merchant_id`) ON DELETE CASCADE,
  UNIQUE KEY `unique_merchant` (`merchant_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Merchant Plans
CREATE TABLE `merchant_plans` (
  `plan_id` INT AUTO_INCREMENT PRIMARY KEY,
  `merchant_id` INT NOT NULL,
  `plan_type` ENUM('Lite', 'Plus', 'Premium') DEFAULT 'Lite',
  `delivery_fee_percentage` DECIMAL(5,2) DEFAULT 15.00,
  `pickup_fee_percentage` DECIMAL(5,2) DEFAULT 12.00,
  `device_rental` BOOLEAN DEFAULT FALSE,
  `agreed_to_terms` BOOLEAN DEFAULT FALSE,
  `terms_agreed_at` DATETIME DEFAULT NULL,
  `plan_start_date` DATE DEFAULT NULL,
  `plan_end_date` DATE DEFAULT NULL,
  `is_active` BOOLEAN DEFAULT TRUE,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`merchant_id`) REFERENCES `merchants`(`merchant_id`) ON DELETE CASCADE,
  UNIQUE KEY `unique_merchant_plan` (`merchant_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Merchant Documents (for menu uploads, licenses, etc.)
CREATE TABLE `merchant_documents` (
  `document_id` INT AUTO_INCREMENT PRIMARY KEY,
  `merchant_id` INT NOT NULL,
  `document_type` ENUM('menu_pdf', 'menu_photo', 'menu_link', 'business_license', 'tax_certificate', 'other') NOT NULL,
  `document_path` VARCHAR(255) DEFAULT NULL,
  `document_url` TEXT DEFAULT NULL,
  `file_name` VARCHAR(255) DEFAULT NULL,
  `file_size` INT DEFAULT NULL,
  `mime_type` VARCHAR(100) DEFAULT NULL,
  `status` ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
  `notes` TEXT DEFAULT NULL,
  `uploaded_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`merchant_id`) REFERENCES `merchants`(`merchant_id`) ON DELETE CASCADE,
  INDEX `idx_merchant_docs` (`merchant_id`, `document_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Merchant Banking (for payment/payout information)
CREATE TABLE `merchant_banking` (
  `banking_id` INT AUTO_INCREMENT PRIMARY KEY,
  `merchant_id` INT NOT NULL,
  `account_holder_name` VARCHAR(255) NOT NULL,
  `bank_name` VARCHAR(255) NOT NULL,
  `account_number` VARCHAR(100) NOT NULL,
  `routing_number` VARCHAR(50) DEFAULT NULL,
  `account_type` ENUM('checking', 'savings', 'business') DEFAULT 'business',
  `business_legal_entity_name` VARCHAR(255) DEFAULT NULL,
  `company_mailing_address` TEXT DEFAULT NULL,
  `city` VARCHAR(100) DEFAULT NULL,
  `state` VARCHAR(100) DEFAULT NULL,
  `postal_code` VARCHAR(20) DEFAULT NULL,
  `verified` BOOLEAN DEFAULT FALSE,
  `is_verified` BOOLEAN DEFAULT FALSE,
  `verified_at` DATETIME DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`merchant_id`) REFERENCES `merchants`(`merchant_id`) ON DELETE CASCADE,
  UNIQUE KEY `unique_merchant_banking` (`merchant_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Merchant Tax Info
CREATE TABLE `merchant_tax_info` (
  `tax_id` INT AUTO_INCREMENT PRIMARY KEY,
  `merchant_id` INT NOT NULL,
  `tax_classification` VARCHAR(100) DEFAULT NULL,
  `full_name` VARCHAR(255) DEFAULT NULL,
  `ssn` VARCHAR(255) DEFAULT NULL,
  `ssn_last_four` VARCHAR(4) DEFAULT NULL,
  `ein` VARCHAR(255) DEFAULT NULL,
  `ein_last_four` VARCHAR(4) DEFAULT NULL,
  `business_name` VARCHAR(255) DEFAULT NULL,
  `address` TEXT DEFAULT NULL,
  `city` VARCHAR(100) DEFAULT NULL,
  `state` VARCHAR(100) DEFAULT NULL,
  `postal_code` VARCHAR(20) DEFAULT NULL,
  `tax_identification_number` VARCHAR(100) DEFAULT NULL,
  `business_type` ENUM('sole_proprietor', 'partnership', 'corporation', 'llc', 'other') DEFAULT 'sole_proprietor',
  `registration_number` VARCHAR(100) DEFAULT NULL,
  `vat_number` VARCHAR(100) DEFAULT NULL,
  `tax_address` TEXT DEFAULT NULL,
  `verified` BOOLEAN DEFAULT FALSE,
  `is_verified` BOOLEAN DEFAULT FALSE,
  `verified_at` DATETIME DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`merchant_id`) REFERENCES `merchants`(`merchant_id`) ON DELETE CASCADE,
  UNIQUE KEY `unique_merchant_tax` (`merchant_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Delivery Settings
CREATE TABLE `delivery_settings` (
  `setting_id` INT AUTO_INCREMENT PRIMARY KEY,
  `merchant_id` INT NOT NULL,
  `delivery_fee` DECIMAL(10,2) DEFAULT 0.00,
  `min_order_amount` DECIMAL(10,2) DEFAULT 0.00,
  `free_delivery_threshold` DECIMAL(10,2) DEFAULT NULL,
  `estimated_delivery_time` INT DEFAULT 30,
  `max_delivery_distance` DECIMAL(5,2) DEFAULT 10.00,
  `is_delivery_available` BOOLEAN DEFAULT TRUE,
  `is_pickup_available` BOOLEAN DEFAULT TRUE,
  `is_open_now` BOOLEAN DEFAULT TRUE,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`merchant_id`) REFERENCES `merchants`(`merchant_id`) ON DELETE CASCADE,
  UNIQUE KEY `unique_merchant` (`merchant_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Menu Categories
CREATE TABLE `menu_categories` (
  `category_id` INT AUTO_INCREMENT PRIMARY KEY,
  `merchant_id` INT NOT NULL,
  `category_name` VARCHAR(100) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `is_active` BOOLEAN DEFAULT TRUE,
  `display_order` INT DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`merchant_id`) REFERENCES `merchants`(`merchant_id`) ON DELETE CASCADE,
  INDEX `idx_merchant_active` (`merchant_id`, `is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Menu Items
CREATE TABLE `menu_items` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `merchant_id` INT NOT NULL,
  `category_id` INT DEFAULT NULL,
  `name` VARCHAR(255) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `price` DECIMAL(10,2) NOT NULL,
  `image` VARCHAR(255) DEFAULT NULL,
  `is_available` BOOLEAN DEFAULT TRUE,
  `is_featured` BOOLEAN DEFAULT FALSE,
  `is_vegetarian` BOOLEAN DEFAULT FALSE,
  `is_vegan` BOOLEAN DEFAULT FALSE,
  `is_gluten_free` BOOLEAN DEFAULT FALSE,
  `calories` INT DEFAULT NULL,
  `prep_time` INT DEFAULT NULL,
  `display_order` INT DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`merchant_id`) REFERENCES `merchants`(`merchant_id`) ON DELETE CASCADE,
  FOREIGN KEY (`category_id`) REFERENCES `menu_categories`(`category_id`) ON DELETE SET NULL,
  INDEX `idx_merchant_available` (`merchant_id`, `is_available`),
  INDEX `idx_featured` (`is_featured`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- User Addresses
CREATE TABLE `user_addresses` (
  `address_id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `address_type` ENUM('home', 'work', 'other') DEFAULT 'home',
  `address_line1` VARCHAR(255) NOT NULL,
  `address_line2` VARCHAR(255) DEFAULT NULL,
  `city` VARCHAR(100) NOT NULL,
  `state` VARCHAR(100) DEFAULT NULL,
  `postal_code` VARCHAR(20) DEFAULT NULL,
  `country` VARCHAR(100) DEFAULT 'Ethiopia',
  `latitude` DECIMAL(10,8) DEFAULT NULL,
  `longitude` DECIMAL(11,8) DEFAULT NULL,
  `is_default` BOOLEAN DEFAULT FALSE,
  `delivery_instructions` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  INDEX `idx_user_default` (`user_id`, `is_default`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Orders
CREATE TABLE `orders` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `order_number` VARCHAR(50) UNIQUE NOT NULL,
  `user_id` INT NOT NULL,
  `merchant_id` INT NOT NULL,
  `delivery_address_id` INT DEFAULT NULL,
  `delivery_address` TEXT NOT NULL,
  `delivery_instructions` TEXT DEFAULT NULL,
  `subtotal` DECIMAL(10,2) NOT NULL,
  `delivery_fee` DECIMAL(10,2) DEFAULT 0.00,
  `tax` DECIMAL(10,2) DEFAULT 0.00,
  `discount` DECIMAL(10,2) DEFAULT 0.00,
  `total` DECIMAL(10,2) NOT NULL,
  `status` ENUM('pending', 'confirmed', 'preparing', 'ready', 'picked_up', 'on_the_way', 'delivered', 'cancelled', 'refunded') DEFAULT 'pending',
  `payment_method` ENUM('cash', 'card', 'mobile_money', 'wallet') DEFAULT 'cash',
  `payment_status` ENUM('pending', 'paid', 'failed', 'refunded') DEFAULT 'pending',
  `order_type` ENUM('delivery', 'pickup') DEFAULT 'delivery',
  `estimated_delivery_time` DATETIME DEFAULT NULL,
  `actual_delivery_time` DATETIME DEFAULT NULL,
  `driver_id` INT DEFAULT NULL,
  `driver_assigned_at` DATETIME DEFAULT NULL,
  `cancelled_by` INT DEFAULT NULL,
  `cancellation_reason` TEXT DEFAULT NULL,
  `cancelled_at` DATETIME DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`merchant_id`) REFERENCES `merchants`(`merchant_id`) ON DELETE CASCADE,
  FOREIGN KEY (`delivery_address_id`) REFERENCES `user_addresses`(`address_id`) ON DELETE SET NULL,
  FOREIGN KEY (`driver_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
  INDEX `idx_user_orders` (`user_id`, `created_at`),
  INDEX `idx_merchant_orders` (`merchant_id`, `status`),
  INDEX `idx_status` (`status`),
  INDEX `idx_order_number` (`order_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Order Items
CREATE TABLE `order_items` (
  `item_id` INT AUTO_INCREMENT PRIMARY KEY,
  `order_id` INT NOT NULL,
  `menu_item_id` INT NOT NULL,
  `item_name` VARCHAR(255) NOT NULL,
  `quantity` INT NOT NULL DEFAULT 1,
  `price` DECIMAL(10,2) NOT NULL,
  `subtotal` DECIMAL(10,2) NOT NULL,
  `special_instructions` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`menu_item_id`) REFERENCES `menu_items`(`id`) ON DELETE CASCADE,
  INDEX `idx_order` (`order_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Order Tracking
CREATE TABLE `order_tracking` (
  `tracking_id` INT AUTO_INCREMENT PRIMARY KEY,
  `order_id` INT NOT NULL,
  `status` VARCHAR(50) NOT NULL,
  `message` TEXT DEFAULT NULL,
  `latitude` DECIMAL(10,8) DEFAULT NULL,
  `longitude` DECIMAL(11,8) DEFAULT NULL,
  `created_by` INT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
  INDEX `idx_order_tracking` (`order_id`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Cart Items (persistent cart)
CREATE TABLE `cart_items` (
  `cart_id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `menu_item_id` INT NOT NULL,
  `quantity` INT NOT NULL DEFAULT 1,
  `special_instructions` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`menu_item_id`) REFERENCES `menu_items`(`id`) ON DELETE CASCADE,
  UNIQUE KEY `unique_user_item` (`user_id`, `menu_item_id`),
  INDEX `idx_user_cart` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Payment Methods
CREATE TABLE `payment_methods` (
  `payment_id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `payment_type` ENUM('card', 'mobile_money', 'bank_account') NOT NULL,
  `provider` VARCHAR(100) DEFAULT NULL,
  `account_number` VARCHAR(255) DEFAULT NULL,
  `account_name` VARCHAR(255) DEFAULT NULL,
  `is_default` BOOLEAN DEFAULT FALSE,
  `is_verified` BOOLEAN DEFAULT FALSE,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  INDEX `idx_user_payment` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Transactions
CREATE TABLE `transactions` (
  `transaction_id` INT AUTO_INCREMENT PRIMARY KEY,
  `order_id` INT NOT NULL,
  `user_id` INT NOT NULL,
  `merchant_id` INT NOT NULL,
  `amount` DECIMAL(10,2) NOT NULL,
  `transaction_type` ENUM('payment', 'refund', 'payout') NOT NULL,
  `payment_method` VARCHAR(50) DEFAULT NULL,
  `status` ENUM('pending', 'processing', 'completed', 'failed', 'cancelled') DEFAULT 'pending',
  `reference_number` VARCHAR(100) UNIQUE DEFAULT NULL,
  `gateway_response` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`merchant_id`) REFERENCES `merchants`(`merchant_id`) ON DELETE CASCADE,
  INDEX `idx_order_transaction` (`order_id`),
  INDEX `idx_reference` (`reference_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Merchant Earnings
CREATE TABLE `merchant_earnings` (
  `earning_id` INT AUTO_INCREMENT PRIMARY KEY,
  `merchant_id` INT NOT NULL,
  `order_id` INT NOT NULL,
  `order_amount` DECIMAL(10,2) NOT NULL,
  `commission_rate` DECIMAL(5,2) NOT NULL,
  `commission_amount` DECIMAL(10,2) NOT NULL,
  `net_amount` DECIMAL(10,2) NOT NULL,
  `payment_status` ENUM('pending', 'paid', 'on_hold') DEFAULT 'pending',
  `paid_at` DATETIME DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`merchant_id`) REFERENCES `merchants`(`merchant_id`) ON DELETE CASCADE,
  FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE,
  INDEX `idx_merchant_earnings` (`merchant_id`, `payment_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Merchant Reviews
CREATE TABLE `merchant_reviews` (
  `review_id` INT AUTO_INCREMENT PRIMARY KEY,
  `merchant_id` INT NOT NULL,
  `user_id` INT NOT NULL,
  `order_id` INT DEFAULT NULL,
  `rating` INT NOT NULL CHECK (`rating` >= 1 AND `rating` <= 5),
  `review_text` TEXT DEFAULT NULL,
  `status` ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`merchant_id`) REFERENCES `merchants`(`merchant_id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE SET NULL,
  INDEX `idx_merchant_reviews` (`merchant_id`, `status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Favorites
CREATE TABLE `favorites` (
  `favorite_id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `merchant_id` INT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`merchant_id`) REFERENCES `merchants`(`merchant_id`) ON DELETE CASCADE,
  UNIQUE KEY `unique_favorite` (`user_id`, `merchant_id`),
  INDEX `idx_user_favorites` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Notifications
CREATE TABLE `notifications` (
  `notification_id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `message` TEXT NOT NULL,
  `type` ENUM('order', 'promotion', 'system', 'payment') DEFAULT 'system',
  `related_id` INT DEFAULT NULL,
  `is_read` BOOLEAN DEFAULT FALSE,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  INDEX `idx_user_notifications` (`user_id`, `is_read`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default store categories
INSERT INTO `store_categories` (`name`, `icon`, `description`, `display_order`) VALUES
('Restaurant', 'bi-shop', 'Restaurants and eateries', 1),
('Fast Food', 'bi-cup-straw', 'Quick service restaurants', 2),
('Cafe', 'bi-cup-hot', 'Coffee shops and cafes', 3),
('Grocery', 'bi-basket', 'Grocery stores and supermarkets', 4),
('Pharmacy', 'bi-capsule', 'Pharmacies and drugstores', 5),
('Bakery', 'bi-cake2', 'Bakeries and pastry shops', 6),
('Dessert', 'bi-ice-cream', 'Dessert and sweet shops', 7),
('Healthy', 'bi-heart-pulse', 'Healthy food options', 8);

-- Insert default roles
INSERT INTO `roles` (`name`, `description`) VALUES
('customer', 'Regular customer user'),
('merchant', 'Store/restaurant owner'),
('admin', 'System administrator'),
('delivery', 'Delivery driver'),
('owner', 'Platform owner');

-- Insert default admin user (password: admin123)
INSERT INTO `users` (`phone`, `email`, `first_name`, `last_name`, `password_hash`, `user_type`, `is_verified`, `is_active`, `agreed_to_terms`) VALUES
('+251911000000', 'admin@beudelivery.com', 'Admin', 'User', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', TRUE, TRUE, TRUE);
