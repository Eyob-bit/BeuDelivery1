# Fix Store Details Error - Quick Solutions

## Problem
The `merchant/enter_store_details.php` page is failing, likely due to missing database columns.

## Quick Fix Options

### Option 1: Add Columns Only (Fastest - Recommended)
This adds just the missing columns without reinstalling everything.

**Run this URL:**
```
http://localhost/BeU%20Delivery/database/add_columns.php
```

This will:
- Check which columns are missing
- Add only the missing columns
- Show you the current table structure
- Won't delete any existing data

### Option 2: Run SQL Manually in phpMyAdmin
If the PHP script doesn't work, run this SQL directly:

1. Open phpMyAdmin
2. Select database: `beu_delivery_v2`
3. Click "SQL" tab
4. Copy and paste this:

```sql
-- Add missing columns to merchant_plans
ALTER TABLE merchant_plans 
ADD COLUMN delivery_fee_percentage DECIMAL(5,2) DEFAULT 15.00 AFTER plan_type,
ADD COLUMN pickup_fee_percentage DECIMAL(5,2) DEFAULT 12.00 AFTER delivery_fee_percentage,
ADD COLUMN device_rental BOOLEAN DEFAULT FALSE AFTER pickup_fee_percentage;

-- Add missing column to merchant_details
ALTER TABLE merchant_details 
ADD COLUMN launch_date DATE DEFAULT NULL AFTER pickup_instructions;
```

4. Click "Go"

### Option 3: Full Database Reinstall
If you want to start fresh:

1. Run: `http://localhost/BeU%20Delivery/database/cleanup.php`
2. Run: `http://localhost/BeU%20Delivery/database/install.php`

**Warning:** This will delete all existing data!

### Option 4: Test Install (Diagnostic)
To see detailed error messages:

```
http://localhost/BeU%20Delivery/database/test_install.php
```

This shows exactly what's happening during installation.

## What Columns Are Missing?

### merchant_plans table needs:
- `delivery_fee_percentage` - Commission for delivery orders (default 15%)
- `pickup_fee_percentage` - Commission for pickup orders (default 12%)
- `device_rental` - Tablet rental option (default FALSE)

### merchant_details table needs:
- `launch_date` - Preferred store launch date (default NULL)

## How to Verify It's Fixed

After running one of the options above:

1. Go to: `http://localhost/BeU%20Delivery/merchant/enter_store_details.php`
2. Fill out the form:
   - Store phone number
   - Select cuisines
   - Pickup instructions
   - Launch date
3. Click "Save & Continue"
4. Should see: "Store details saved successfully!"

## Common Error Messages

### "Unknown column 'launch_date' in 'field list'"
**Solution:** Run Option 1 or 2 above to add the launch_date column

### "Unknown column 'delivery_fee_percentage' in 'field list'"
**Solution:** Run Option 1 or 2 above to add merchant_plans columns

### "Table 'merchant_details' doesn't exist"
**Solution:** Run Option 3 (full reinstall) to create all tables

### "Access denied for user 'root'@'localhost'"
**Solution:** Check your database credentials in `includes/db.php`

## Files Created to Help You

1. **database/add_columns.php** - Adds missing columns only (recommended)
2. **database/add_missing_columns.sql** - SQL to run manually in phpMyAdmin
3. **database/test_install.php** - Diagnostic version with detailed errors
4. **database/install.php** - Original full installation script
5. **database/cleanup.php** - Drops database for fresh start

## Step-by-Step: Recommended Approach

1. **First, try the quick fix:**
   ```
   http://localhost/BeU%20Delivery/database/add_columns.php
   ```

2. **If that works, test the page:**
   ```
   http://localhost/BeU%20Delivery/merchant/enter_store_details.php
   ```

3. **If it still fails, check what error you get and:**
   - If it's a column error → Try Option 2 (manual SQL)
   - If it's a table error → Try Option 3 (full reinstall)
   - If it's a connection error → Check database credentials

4. **If nothing works, run the diagnostic:**
   ```
   http://localhost/BeU%20Delivery/database/test_install.php
   ```
   This will show you exactly what's wrong.

## Need More Help?

If you're still having issues, please provide:
1. The exact error message you're seeing
2. Which option you tried
3. The output from test_install.php

## Quick Reference

| Problem | Solution | URL |
|---------|----------|-----|
| Missing columns | Add columns only | `database/add_columns.php` |
| Want to see errors | Diagnostic install | `database/test_install.php` |
| Start completely fresh | Full reinstall | `database/cleanup.php` then `install.php` |
| PHP not working | Manual SQL | Use `add_missing_columns.sql` in phpMyAdmin |

## Status Check

After fixing, verify these work:
- ✅ `merchant/getStarted.php` - Registration
- ✅ `merchant/chooseplan.php` - Plan selection
- ✅ `merchant/agreement.php` - Agreement acceptance
- ✅ `merchant/enter_store_details.php` - Store details ← Should work now!
- ⏳ `merchant/uploadmenu.php` - Menu upload (next step)
- ⏳ `merchant/setup_payment.php` - Payment setup (next step)
- ⏳ `merchant/enter_tax_info.php` - Tax info (next step)

---

**TL;DR:** Run `database/add_columns.php` and it should fix everything! 🚀
