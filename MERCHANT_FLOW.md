# Merchant Signup and Login Flow

## Overview
This document explains the complete merchant registration, setup, and login process for BeU Delivery.

## Database Tables Required

### Core Merchant Tables
1. **merchants** - Main merchant/store information
2. **merchant_details** - Additional store details (phone, cuisine, hours)
3. **merchant_plans** - Subscription plan and agreement tracking
4. **merchant_documents** - Menu uploads, licenses, certificates
5. **merchant_banking** - Bank account information for payouts
6. **merchant_tax_info** - Tax identification and business registration

All tables have been added to `database/schema.sql`.

## Merchant Registration Flow

### Step 1: Initial Registration (`merchant/getStarted.php`)
**Purpose**: Collect basic merchant information and create account

**Form Fields**:
- First Name, Last Name (required)
- Email (required, unique)
- Phone (required, format: +251XXXXXXXXX)
- Store Address (required)
- Floor/Suite (optional)
- Store Name (required)
- Brand Name (optional)
- Business Type (Restaurant, Retail, Grocery, Other)
- Social Media/Website (optional)
- SMS Opt-in (checkbox)

**Process**:
1. Validates all required fields
2. Checks if email already exists
3. Creates user account with `user_type = 'merchant'`
4. Creates merchant record with status = 'setup'
5. Creates default plan record (Lite plan)
6. Stores merchant_id and user_id in session
7. Redirects to `chooseplan.php`

**Database Operations**:
```sql
INSERT INTO users (email, first_name, last_name, phone, user_type)
INSERT INTO merchants (user_id, store_name, brand_name, business_type, ...)
INSERT INTO merchant_plans (merchant_id, plan_type)
```

### Step 2: Plan Selection (`merchant/chooseplan.php`)
**Purpose**: Select subscription plan (Lite, Plus, Premium)

**Plans**:
- **Lite**: Basic features, standard commission
- **Plus**: Enhanced features, reduced commission
- **Premium**: Full features, lowest commission

**Process**:
1. Display plan options with pricing
2. Update merchant_plans table with selected plan
3. Redirect to `agreement.php`

### Step 3: Agreement (`merchant/agreement.php`)
**Purpose**: Accept terms and conditions

**Process**:
1. Display terms and conditions
2. Require checkbox acceptance
3. Update merchant_plans.agreed_to_terms = 1
4. Set terms_agreed_at timestamp
5. Redirect to `setup.php`

### Step 4: Multi-Step Setup (`merchant/setup.php`)
**Purpose**: Complete 5 required setup tasks

**Setup Tasks**:

#### Task 1: Security Setup (MANDATORY) - `setupsecurity.php`
- Set password for account
- Add two-factor authentication (optional)
- Updates users.password_hash

#### Task 2: Store Details - `enter_store_details.php`
- Store phone number
- Cuisine types
- Store hours
- Pickup instructions
- Preferred launch date
- Inserts into merchant_details table

#### Task 3: Menu Upload - `uploadmenu.php`
- Upload menu as PDF, photos, or provide link
- Inserts into merchant_documents table
- document_type: 'menu_pdf', 'menu_photo', or 'menu_link'

#### Task 4: Payment Setup - `setup_payment.php`
- Bank account information
- Account holder name
- Bank name, account number, routing number
- Inserts into merchant_banking table

#### Task 5: Tax Information - `enter_tax_info.php`
- Business name
- Tax identification number (TIN)
- Business type
- VAT number (if applicable)
- Inserts into merchant_tax_info table

**Progress Tracking**:
- Checks completion of each task
- Shows progress percentage
- Auto-redirects to final page when all tasks complete

### Step 5: Final Review (`merchant/finalpage.php`)
**Purpose**: Submit for review

**Process**:
1. Review all submitted information
2. Update merchant status to 'under_review'
3. Send notification to admin
4. Redirect to `accountunderreview.php`

### Step 6: Under Review (`account/accountunderreview.php`)
**Purpose**: Wait for admin approval

**Display**:
- "Your account is under review" message
- Estimated review time (1-3 days)
- Contact information for questions

**Admin Action Required**:
- Admin reviews merchant application
- Updates merchant.status to 'active' or 'rejected'

### Step 7: Active Dashboard (`account/merchant_dashboard.php`)
**Purpose**: Main merchant control panel

**Features**:
- View orders
- Manage menu items
- Track earnings
- View reports
- Update settings

## Merchant Login Flow

### Login Process (`auth/login.php` → `auth/verify_login.php`)

1. **User enters email or phone**
2. **Verification code sent**
3. **User enters code**
4. **System checks merchant status**:

```php
// Check if user has merchant record
SELECT merchant_id, status FROM merchants WHERE user_id = ?

// Redirect based on status:
- 'active' → merchant_dashboard.php
- 'under_review' → accountunderreview.php
- 'setup' → merchant/setup.php (resume setup)
- 'inactive' → account_disabled.php
- No merchant record → merchant/getStarted.php
```

## Status Flow Diagram

```
[Registration] → setup
     ↓
[Complete Tasks] → setup
     ↓
[Submit Review] → under_review
     ↓
[Admin Approval] → active
     ↓
[Merchant Dashboard] → active
```

## Key Session Variables

```php
$_SESSION['user_id']        // User ID from users table
$_SESSION['merchant_id']    // Merchant ID from merchants table
$_SESSION['user_type']      // 'merchant'
$_SESSION['store_name']     // Store name for display
$_SESSION['logged_in']      // true
```

## Common Issues and Solutions

### Issue 1: "Column 'phone' cannot be null"
**Solution**: Phone and email columns are now nullable in users table

### Issue 2: "merchant_plans table doesn't exist"
**Solution**: Run `database/cleanup.php` then `database/install.php` to recreate all tables

### Issue 3: Merchant login redirects to user home
**Solution**: Fixed in `auth/verify_login.php` - now checks merchants table first

### Issue 4: Dashboard shows "merchant_data undefined"
**Solution**: Fixed in `account/merchant_dashboard.php` - now queries merchants table first

## Testing Checklist

- [ ] New merchant can register via getStarted.php
- [ ] Email validation works
- [ ] Phone format validation works (+251XXXXXXXXX)
- [ ] Merchant record created with status='setup'
- [ ] Plan selection saves correctly
- [ ] Agreement acceptance works
- [ ] All 5 setup tasks can be completed
- [ ] Progress tracking shows correct percentage
- [ ] Auto-redirect works when all tasks complete
- [ ] Status changes to 'under_review' after submission
- [ ] Merchant login redirects to correct page based on status
- [ ] Active merchants can access dashboard
- [ ] Dashboard displays correct merchant data

## Files Modified

1. `database/schema.sql` - Added 4 new merchant tables
2. `account/merchant_dashboard.php` - Fixed merchant_id query
3. `auth/verify_login.php` - Enhanced merchant redirect logic
4. `merchant/getStarted.php` - Registration form (already complete)
5. `merchant/setup.php` - Multi-step setup tracker (already complete)

## Next Steps

1. **Run database cleanup and install**:
   ```
   http://localhost/BeU%20Delivery/database/cleanup.php
   http://localhost/BeU%20Delivery/database/install.php
   ```

2. **Test merchant registration**:
   - Go to `merchant/getStarted.php`
   - Fill out registration form
   - Complete all setup tasks

3. **Test merchant login**:
   - Logout
   - Login with merchant email/phone
   - Verify redirect to correct page

4. **Admin approval** (if needed):
   - Login as admin
   - Approve merchant application
   - Verify merchant can access dashboard
