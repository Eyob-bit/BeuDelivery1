# Fixed: Agreement Column Name Error

## Error Message
```
Failed to save agreement: Unknown column 'agreement_accepted_at' in 'field list'
```

## Root Cause
The `merchant/agreement.php` file was using column name `agreement_accepted_at` but the database schema defined it as `terms_agreed_at`.

Additionally, the agreement page was trying to display columns that didn't exist in the merchant_plans table:
- `delivery_fee_percentage`
- `pickup_fee_percentage`
- `device_rental`

## Fixes Applied

### 1. Fixed Column Name in agreement.php
**File**: `merchant/agreement.php`

Changed:
```php
// BEFORE (broken):
agreement_accepted_at = '$current_time'

// AFTER (fixed):
terms_agreed_at = '$current_time'
```

### 2. Added Missing Columns to merchant_plans Table
**File**: `database/schema.sql`

Added columns:
```sql
`delivery_fee_percentage` DECIMAL(5,2) DEFAULT 15.00,
`pickup_fee_percentage` DECIMAL(5,2) DEFAULT 12.00,
`device_rental` BOOLEAN DEFAULT FALSE,
```

These columns are used by the agreement page to display:
- Commission rates for delivery orders (default 15%)
- Commission rates for pickup orders (default 12%)
- Device rental option (default FALSE)

## Updated merchant_plans Table Structure

```sql
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
)
```

## What to Do Now

### Option 1: Reinstall Database (Recommended)
This will create all tables with the correct structure:

1. Go to: `http://localhost/BeU%20Delivery/database/cleanup.php`
2. Go to: `http://localhost/BeU%20Delivery/database/install.php`

### Option 2: Alter Existing Table (If you have data to preserve)
Run this SQL query in phpMyAdmin or MySQL:

```sql
ALTER TABLE merchant_plans 
ADD COLUMN delivery_fee_percentage DECIMAL(5,2) DEFAULT 15.00 AFTER plan_type,
ADD COLUMN pickup_fee_percentage DECIMAL(5,2) DEFAULT 12.00 AFTER delivery_fee_percentage,
ADD COLUMN device_rental BOOLEAN DEFAULT FALSE AFTER pickup_fee_percentage;
```

## Test the Fix

1. Go to merchant registration: `merchant/getStarted.php`
2. Complete the registration form
3. Select a plan in `chooseplan.php`
4. Accept the agreement in `agreement.php`
5. You should now see "Agreement accepted! Redirecting to setup..."
6. Should redirect to `setup.php`

## Files Modified

1. ✅ `merchant/agreement.php` - Fixed column name
2. ✅ `database/schema.sql` - Added missing columns to merchant_plans

## Status: ✅ FIXED

The agreement acceptance should now work correctly after running the database installation.
