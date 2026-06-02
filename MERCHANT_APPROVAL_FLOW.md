# Merchant Approval Flow - Complete Guide

## How It Works

### 1. Merchant Signup & Submission
When a merchant completes the signup process:
- Status is set to `under_review`
- Merchant is redirected to `account/accountunderreview.php`
- They see a "waiting for approval" dashboard

### 2. Admin Reviews Application
Admin can review the merchant at:
- `admin/admin_panel.php` - See pending merchants
- `admin/admin_merchants.php` - View all merchants
- `admin/admin_merchant_details.php?id=X` - Review specific merchant

### 3. Admin Approves Merchant
When admin clicks "Approve":
1. Merchant status changes from `under_review` → `active`
2. Review record is created/updated in `merchant_reviews` table
3. Admin is redirected back to merchant details page

### 4. Merchant Gets Access
After approval, when merchant logs in or refreshes:
1. `accountunderreview.php` checks status
2. If status = `active`, redirects to `merchant_dashboard.php`
3. Merchant can now manage their store fully

## Current Flow Logic

### accountunderreview.php (Lines 40-44)
```php
// If merchant is active, redirect to active dashboard
if ($merchant_status === 'active') {
    header("Location: merchant_dashboard.php");
    exit();
}
```

### merchant_dashboard.php (Lines 60-72)
```php
// Check merchant status
if ($merchant['status'] !== 'active') {
    switch ($merchant['status']) {
        case 'under_review':
            header("Location: accountunderreview.php");
            break;
        case 'setup':
            header("Location: ../merchant/setup.php");
            break;
        // ... other cases
    }
    exit();
}
```

### admin_merchant_details.php (Lines 119-135)
```php
if (isset($_POST['approve'])) {
    // Update merchant status
    mysqli_query($conn, "UPDATE merchants SET status = 'active' WHERE merchant_id = '$merchant_id'");
    
    // Update or create review
    $review_check = mysqli_query($conn, "SELECT id FROM merchant_reviews WHERE merchant_id = '$merchant_id'");
    if (mysqli_num_rows($review_check) > 0) {
        mysqli_query($conn, "UPDATE merchant_reviews SET 
            status = 'approved',
            admin_comments = '$comments',
            reviewed_at = NOW(),
            reviewed_by = '$admin_id'
            WHERE merchant_id = '$merchant_id'");
    } else {
        mysqli_query($conn, "INSERT INTO merchant_reviews 
            (merchant_id, status, admin_comments, reviewed_at, reviewed_by) 
            VALUES ('$merchant_id', 'approved', '$comments', NOW(), '$admin_id')");
    }
}
```

## Testing the Flow

### Step 1: Set Merchant to Under Review
```sql
UPDATE merchants SET status = 'under_review' WHERE merchant_id = 3;
```

### Step 2: Login as Merchant
- Email: `e2@gmail.com` (or the merchant's email)
- You should see the "Account Under Review" page
- URL: `http://localhost/BeU%20Delivery/account/accountunderreview.php`

### Step 3: Login as Admin
- Email: `admin@beudelivery.com`
- Password: `admin123`
- Go to: `http://localhost/BeU%20Delivery/admin/admin_panel.php`

### Step 4: Approve Merchant
1. Click "Review" button on pending merchant
2. Or go to: `http://localhost/BeU%20Delivery/admin/admin_merchant_details.php?id=3`
3. Click "Approve" button
4. Add optional comments
5. Click "Approve Merchant"

### Step 5: Merchant Logs In Again
- Logout and login as merchant again
- OR just refresh the page
- You should be automatically redirected to: `http://localhost/BeU%20Delivery/account/merchant_dashboard.php`
- Merchant can now access full dashboard with:
  - Orders management
  - Menu manager
  - Reports
  - Earnings
  - Settings

## What Merchant Can Do After Approval

### Full Dashboard Access
- **Dashboard**: View stats, orders, earnings
- **Orders**: Manage incoming orders, update status
- **Menu Manager**: Add/edit/delete menu items
- **Reports**: View sales reports and analytics
- **Earnings**: Track payments and payouts
- **Customers**: View customer data
- **Settings**: Update store info, hours, photos

### User-Facing Store
Once approved, customers can:
- Browse the merchant's menu
- Place orders
- Leave reviews
- Add to favorites

## Status Flow Diagram

```
Merchant Signup
      ↓
status = 'setup'
      ↓
Complete Setup
      ↓
status = 'under_review'
      ↓
[accountunderreview.php]
      ↓
Admin Reviews
      ↓
Admin Approves
      ↓
status = 'active'
      ↓
[merchant_dashboard.php]
      ↓
Full Store Management
```

## Troubleshooting

### Merchant Still Sees "Under Review" After Approval
1. Check merchant status in database:
   ```sql
   SELECT merchant_id, store_name, status FROM merchants WHERE merchant_id = 3;
   ```
2. If status is still 'under_review', manually update:
   ```sql
   UPDATE merchants SET status = 'active' WHERE merchant_id = 3;
   ```
3. Clear browser cache and cookies
4. Logout and login again

### Merchant Can't Access Dashboard
1. Verify status is 'active'
2. Check session variables:
   - `$_SESSION['user_id']` should be set
   - `$_SESSION['merchant_id']` should be set
3. Check PHP error logs: `/opt/lampp/logs/php_error_log`

## Current Test Merchant

**Merchant ID**: 3
**Store Name**: Eyobs
**Email**: e2@gmail.com
**Current Status**: under_review (set for testing)

To test the approval flow:
1. Login as admin
2. Approve merchant ID 3
3. Login as merchant (e2@gmail.com)
4. Should see full dashboard

## Summary

✅ **Flow is already implemented and working!**
- Merchant signup → under_review status
- Admin approval → active status
- Merchant login → full dashboard access

The system automatically handles the redirect based on merchant status. No additional code changes needed!
