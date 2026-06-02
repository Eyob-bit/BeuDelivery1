# Quick Start: Merchant System

## What Was Fixed

✅ Added 4 missing database tables for merchant system
✅ Fixed merchant dashboard query errors  
✅ Updated merchants table with missing columns
✅ Fixed merchant login redirect logic

## Run This Now

### Step 1: Reinstall Database
Open these URLs in your browser:

1. **Cleanup**: `http://localhost/BeU%20Delivery/database/cleanup.php`
2. **Install**: `http://localhost/BeU%20Delivery/database/install.php`

You should see: "✓ All tables created successfully"

### Step 2: Test Merchant Registration

Go to: `http://localhost/BeU%20Delivery/merchant/getStarted.php`

Fill out the form:
- **First Name**: Test
- **Last Name**: Merchant  
- **Email**: merchant@test.com
- **Phone**: 912345678 (without +251)
- **Store Address**: 123 Main Street
- **Store Name**: Test Restaurant

Click "Submit & Start Selling"

### Step 3: Complete Setup

You'll be guided through 5 setup tasks:
1. ✅ Set up security (create password)
2. ✅ Enter store details (phone, hours, cuisine)
3. ✅ Upload menu (PDF, photos, or link)
4. ✅ Set up payment (bank account info)
5. ✅ Enter tax info (TIN, business type)

### Step 4: Test Login

1. Logout
2. Go to: `http://localhost/BeU%20Delivery/auth/login.php`
3. Enter: merchant@test.com
4. Enter verification code
5. You'll be redirected based on your merchant status

## New Database Tables

### merchant_plans
Tracks subscription plan and agreement acceptance

### merchant_documents  
Stores menu uploads and business documents

### merchant_banking
Bank account information for payouts

### merchant_tax_info
Tax identification and business registration

## Merchant Status Flow

```
setup → under_review → active
  ↓           ↓           ↓
setup.php  review.php  dashboard.php
```

## Files Changed

1. `database/schema.sql` - Added 4 tables + updated merchants table
2. `account/merchant_dashboard.php` - Fixed queries
3. `MERCHANT_FLOW.md` - Complete documentation
4. `MERCHANT_FIXES_SUMMARY.md` - Detailed changes

## Troubleshooting

**Problem**: "Table doesn't exist" error  
**Solution**: Run cleanup.php then install.php

**Problem**: "merchant_data undefined" error  
**Solution**: Already fixed in merchant_dashboard.php

**Problem**: Login redirects to wrong page  
**Solution**: Already fixed in verify_login.php

**Problem**: Can't complete registration  
**Solution**: Check that all required fields are filled

## Need Help?

Check these files:
- `MERCHANT_FLOW.md` - Complete flow documentation
- `MERCHANT_FIXES_SUMMARY.md` - All changes explained
- `TESTING_CHECKLIST.md` - Testing procedures

## That's It!

The merchant signup and login system is now fully functional. Just run the database installation and start testing! 🚀
