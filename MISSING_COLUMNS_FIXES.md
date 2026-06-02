# Missing Columns - All Fixes

## Overview
Several merchant setup pages were referencing database columns that didn't exist in the schema. This document tracks all the missing columns that have been added.

---

## Fix #1: Agreement Page Columns

### Error
```
Failed to save agreement: Unknown column 'agreement_accepted_at' in 'field list'
```

### Columns Fixed in `merchant_plans` table:
1. ✅ Changed `agreement_accepted_at` → `terms_agreed_at` (column name mismatch)
2. ✅ Added `delivery_fee_percentage` DECIMAL(5,2) DEFAULT 15.00
3. ✅ Added `pickup_fee_percentage` DECIMAL(5,2) DEFAULT 12.00
4. ✅ Added `device_rental` BOOLEAN DEFAULT FALSE

**File Modified**: `merchant/agreement.php`, `database/schema.sql`

---

## Fix #2: Store Details Page Columns

### Error
```
Failed to save store details: Unknown column 'launch_date' in 'field list'
```

### Column Added to `merchant_details` table:
1. ✅ Added `launch_date` DATE DEFAULT NULL

**Purpose**: Stores the merchant's preferred Uber Eats launch date (can be ASAP or a specific date)

**File Modified**: `database/schema.sql`

---

## Updated Table Structures

### merchant_plans Table (Complete)
```sql
CREATE TABLE `merchant_plans` (
  `plan_id` INT AUTO_INCREMENT PRIMARY KEY,
  `merchant_id` INT NOT NULL,
  `plan_type` ENUM('Lite', 'Plus', 'Premium') DEFAULT 'Lite',
  `delivery_fee_percentage` DECIMAL(5,2) DEFAULT 15.00,  -- NEW
  `pickup_fee_percentage` DECIMAL(5,2) DEFAULT 12.00,    -- NEW
  `device_rental` BOOLEAN DEFAULT FALSE,                  -- NEW
  `agreed_to_terms` BOOLEAN DEFAULT FALSE,
  `terms_agreed_at` DATETIME DEFAULT NULL,                -- FIXED NAME
  `plan_start_date` DATE DEFAULT NULL,
  `plan_end_date` DATE DEFAULT NULL,
  `is_active` BOOLEAN DEFAULT TRUE,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`merchant_id`) REFERENCES `merchants`(`merchant_id`) ON DELETE CASCADE,
  UNIQUE KEY `unique_merchant_plan` (`merchant_id`)
)
```

### merchant_details Table (Complete)
```sql
CREATE TABLE `merchant_details` (
  `detail_id` INT AUTO_INCREMENT PRIMARY KEY,
  `merchant_id` INT NOT NULL,
  `store_phone` VARCHAR(20) DEFAULT NULL,
  `store_email` VARCHAR(255) DEFAULT NULL,
  `cuisine_types` JSON DEFAULT NULL,
  `store_hours` JSON DEFAULT NULL,
  `pickup_instructions` TEXT DEFAULT NULL,
  `launch_date` DATE DEFAULT NULL,                        -- NEW
  `special_instructions` TEXT DEFAULT NULL,
  `accepts_cash` BOOLEAN DEFAULT TRUE,
  `accepts_card` BOOLEAN DEFAULT TRUE,
  `latitude` DECIMAL(10,8) DEFAULT NULL,
  `longitude` DECIMAL(11,8) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`merchant_id`) REFERENCES `merchants`(`merchant_id`) ON DELETE CASCADE,
  UNIQUE KEY `unique_merchant` (`merchant_id`)
)
```

---

## How These Columns Are Used

### merchant_plans Columns:

**delivery_fee_percentage** (15% default)
- Commission rate charged for delivery orders
- Displayed on agreement page
- Can vary by plan type (Lite/Plus/Premium)

**pickup_fee_percentage** (12% default)
- Commission rate charged for pickup orders
- Lower than delivery since no delivery cost
- Displayed on agreement page

**device_rental** (FALSE default)
- Whether merchant wants to rent a tablet device
- $6.99 per week if enabled
- Displayed on agreement page

**terms_agreed_at** (NULL default)
- Timestamp when merchant accepted the agreement
- Set when agreement form is submitted
- Used to track compliance

### merchant_details Columns:

**launch_date** (NULL default)
- Merchant's preferred Uber Eats launch date
- Can be NULL for "ASAP" launch
- Or a specific future date
- Used in store details form

---

## Testing After Fix

### Test Agreement Page:
1. Register merchant at `merchant/getStarted.php`
2. Choose plan at `merchant/chooseplan.php`
3. Accept agreement at `merchant/agreement.php`
4. Should see: "Agreement accepted! Redirecting to setup..."
5. ✅ No column errors

### Test Store Details Page:
1. Continue to `merchant/setup.php`
2. Click "Start" on "Enter store details" task
3. Fill out form at `merchant/enter_store_details.php`:
   - Store phone
   - Cuisine types
   - Store hours
   - Pickup instructions
   - Launch date (ASAP or specific date)
4. Submit form
5. Should see: "Store details saved successfully!"
6. ✅ No column errors

---

## Installation Instructions

### Option 1: Fresh Install (Recommended)
Run these URLs to recreate all tables with correct structure:

1. `http://localhost/BeU%20Delivery/database/cleanup.php`
2. `http://localhost/BeU%20Delivery/database/install.php`

### Option 2: Alter Existing Tables (If preserving data)
Run these SQL queries:

```sql
-- Fix merchant_plans table
ALTER TABLE merchant_plans 
ADD COLUMN delivery_fee_percentage DECIMAL(5,2) DEFAULT 15.00 AFTER plan_type,
ADD COLUMN pickup_fee_percentage DECIMAL(5,2) DEFAULT 12.00 AFTER delivery_fee_percentage,
ADD COLUMN device_rental BOOLEAN DEFAULT FALSE AFTER pickup_fee_percentage;

-- Fix merchant_details table
ALTER TABLE merchant_details 
ADD COLUMN launch_date DATE DEFAULT NULL AFTER pickup_instructions;
```

---

## Files Modified

1. ✅ `database/schema.sql` - Added all missing columns
2. ✅ `merchant/agreement.php` - Fixed column name reference
3. ✅ `FIX_AGREEMENT_ERROR.md` - Documentation for fix #1
4. ✅ `MISSING_COLUMNS_FIXES.md` - This comprehensive document

---

## Potential Future Issues

If you encounter more "Unknown column" errors, follow this pattern:

1. **Find the error message** - Note the column name and table
2. **Search for usage** - Use grep to find where it's used: `grep -r "column_name" merchant/`
3. **Check the schema** - Look at `database/schema.sql` for the table
4. **Add the column** - Add it with appropriate type and default value
5. **Reinstall database** - Run cleanup.php then install.php
6. **Test the page** - Verify the error is fixed

---

## Common Column Patterns

Based on the fixes so far, here are common patterns:

**Percentage/Rate Columns**:
- Type: `DECIMAL(5,2)` (allows 0.00 to 999.99)
- Default: Appropriate percentage (e.g., 15.00 for 15%)

**Boolean Flags**:
- Type: `BOOLEAN`
- Default: `FALSE` or `TRUE` as appropriate

**Date Columns**:
- Type: `DATE` for dates only
- Type: `DATETIME` for dates with time
- Default: `NULL` if optional

**Timestamp Columns**:
- Type: `DATETIME`
- Default: `NULL` if optional
- Use `NOW()` or `CURRENT_TIMESTAMP` for auto-set

---

## Status: ✅ ALL FIXED

Both missing column issues have been resolved:
- ✅ Agreement page columns added
- ✅ Store details page columns added

The merchant setup flow should now work without column errors after running the database installation.

---

## Next Steps

1. **Reinstall database** (cleanup.php → install.php)
2. **Test complete merchant flow**:
   - Registration
   - Plan selection
   - Agreement acceptance ← Fixed
   - Store details entry ← Fixed
   - Menu upload
   - Payment setup
   - Tax info entry
3. **Watch for more column errors** and fix using the pattern above

If you encounter any more "Unknown column" errors, just let me know the column name and I'll add it to the schema!
