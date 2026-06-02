# Admin System Guide - BeU Delivery

## Overview

The admin system allows administrators to review and approve merchant applications, manage users, monitor orders, and oversee the entire platform.

## Quick Start

### Step 1: Access Admin Panel

Visit: `http://localhost/BeU%20Delivery/admin/access_admin.php`

This will:
- Create an admin user if one doesn't exist
- Provide quick login access
- Redirect you to the admin dashboard

**Default Admin Credentials:**
- Email: `admin@beudelivery.com`
- Password: `admin123`

### Step 2: Admin Dashboard

Once logged in, you'll see:
- **Pending Review Count**: Merchants waiting for approval
- **Today's Orders**: Orders placed today
- **Total Revenue**: All-time revenue
- **New Users**: Users who signed up today

## Merchant Review Workflow

### 1. View Pending Merchants

From the dashboard:
- Click "Pending Review" card OR
- Go to "All Merchants" → Filter by "Under Review"

You'll see a list of merchants with:
- Store name and owner information
- Business type
- Contact details
- Days since application
- Quick action buttons

### 2. Review Merchant Details

Click "View" on any merchant to see:

#### Store Information
- Store name and brand
- Business type
- Address and contact
- Website/social media
- Cuisine types
- Launch date

#### Owner Information
- Full name
- Email and phone
- Account creation date

#### Financial Information
- Bank account details (last 4 digits)
- Routing number
- Tax classification
- Tax ID (SSN/EIN)
- Tax payer name

#### Documents
- Uploaded menu images
- Business documents
- License/permits

#### Store Hours
- Operating hours for each day
- Closed days

#### Plan Information
- Selected plan type
- Delivery fee percentage
- Pickup fee percentage

### 3. Approve or Reject

#### To Approve:
1. Click "Approve" button
2. Add optional comments
3. Confirm approval

**What happens:**
- Merchant status changes from `under_review` to `active`
- Merchant can access active dashboard
- Merchant can create menus, add items, upload photos
- Store becomes visible to customers (when implemented)
- Review record created with approval timestamp

#### To Reject:
1. Click "Reject" button
2. **Required:** Provide rejection reason
3. Confirm rejection

**What happens:**
- Merchant status changes to `inactive`
- Merchant receives rejection notification (email - to be implemented)
- Merchant can see rejection reason
- Merchant can reapply or fix issues

### 4. Quick Approve

For merchants you trust or have verified externally:
1. Click "Quick Approve" from the list view
2. Add optional comments
3. Confirm

This approves without viewing full details.

## Admin Features

### Dashboard
- **Stats Overview**: Key metrics at a glance
- **Pending Applications**: Merchants awaiting review
- **Recent Orders**: Latest orders across platform
- **Quick Statistics**: Total merchants, orders, revenue, users

### Merchants Management
- **Filter by Status**: All, Under Review, Active, Inactive
- **Search**: By store name, owner name, or email
- **Pagination**: Browse through large lists
- **Bulk Actions**: (Coming soon)

### Orders Management
- View all orders
- Filter by status
- Track deliveries
- Manage refunds

### Users Management
- View all users
- Filter by type (customer, merchant, delivery, admin)
- Manage user accounts
- View user activity

### Reports
- Revenue reports
- Merchant performance
- Order analytics
- User growth

## Database Tables Used

### merchants
- Stores merchant business information
- **status** field: `setup`, `under_review`, `active`, `inactive`

### merchant_reviews
- Tracks review process
- Stores admin comments and decisions
- Records approval/rejection timestamps
- **status** field: `pending`, `in_review`, `approved`, `rejected`

### merchant_details
- Extended merchant information
- Store hours, cuisine types, launch date

### merchant_banking
- Banking information for payouts
- Account and routing numbers (encrypted)

### merchant_tax_info
- Tax classification and IDs
- SSN/EIN information (encrypted)

### merchant_plans
- Selected plan and fee structure
- Delivery and pickup percentages

### merchant_documents
- Uploaded files and images
- Menu photos, licenses, permits

## Status Flow

```
Merchant Registration
        ↓
    [setup]
        ↓
Complete All Setup Steps
        ↓
Submit for Review
        ↓
  [under_review]
        ↓
    Admin Reviews
        ↓
    ┌───────┴───────┐
    ↓               ↓
[approved]    [rejected]
    ↓               ↓
 [active]      [inactive]
```

## After Approval

Once a merchant is approved (`active` status), they can:

1. **Access Active Dashboard**
   - Full merchant portal
   - Real-time order management
   - Performance analytics

2. **Menu Management**
   - Create menu categories
   - Add menu items
   - Set prices and descriptions
   - Upload food photos

3. **Store Settings**
   - Update store hours
   - Change contact information
   - Upload store photos
   - Manage delivery zones

4. **Order Management**
   - Receive orders
   - Accept/reject orders
   - Update order status
   - Communicate with customers

5. **Financial Management**
   - View earnings
   - Track payouts
   - Download reports
   - Update banking info (with admin approval)

## Admin Actions

### Approve Merchant
```php
POST /admin/update_merchant_status.php
{
    "action": "approve",
    "merchant_id": "123",
    "comments": "All documents verified"
}
```

### Reject Merchant
```php
POST /admin/update_merchant_status.php
{
    "action": "reject",
    "merchant_id": "123",
    "reason": "Incomplete documentation"
}
```

## Security Notes

1. **Admin Authentication**
   - All admin pages check for admin role
   - Session-based authentication
   - Auto-redirect if not authorized

2. **Sensitive Data**
   - Bank account numbers: Only last 4 digits shown
   - Tax IDs: Encrypted in database
   - Passwords: Never displayed

3. **Audit Trail**
   - All approvals/rejections logged
   - Reviewer ID recorded
   - Timestamps for all actions

## File Structure

```
admin/
├── access_admin.php          # Quick admin access (DEV ONLY)
├── admin_auth.php            # Authentication check
├── admin_panel.php           # Main dashboard
├── admin_merchants.php       # Merchants list
├── admin_merchant_details.php # Detailed merchant view
├── admin_sidebar.php         # Reusable sidebar
├── get_merchant_details.php  # AJAX merchant data
├── update_merchant_status.php # AJAX approve/reject
├── orders.php                # Orders management
├── admin_users.php           # Users management
├── admin_reports.php         # Reports and analytics
└── restaurants.php           # Restaurant management
```

## Testing the Flow

### Complete Test Scenario:

1. **Create Merchant Application**
   - Go to merchant signup
   - Complete all steps
   - Submit for review

2. **Admin Review**
   - Access admin panel
   - View pending merchant
   - Review all information
   - Approve or reject

3. **Merchant Dashboard**
   - Merchant logs in
   - Sees active dashboard
   - Can create menus
   - Can manage orders

## Troubleshooting

### Can't Access Admin Panel
- Check if admin user exists
- Use `access_admin.php` to create/login
- Verify session is active
- Check user_roles table

### Merchant Not Showing in List
- Check merchant status in database
- Verify merchant completed all setup steps
- Check if finalpage.php was visited

### Approval Not Working
- Check browser console for errors
- Verify AJAX endpoint is accessible
- Check database permissions
- Review PHP error logs

## Next Steps

After setting up the admin system:

1. **Test the complete flow**
   - Create test merchant
   - Review and approve
   - Verify merchant can access active dashboard

2. **Implement email notifications**
   - Approval emails
   - Rejection emails with reasons
   - Status update notifications

3. **Add more admin features**
   - Bulk actions
   - Advanced filtering
   - Export reports
   - Analytics dashboard

4. **Security hardening**
   - Remove access_admin.php in production
   - Add 2FA for admin accounts
   - Implement IP whitelisting
   - Add activity logging

## Support

For issues or questions:
- Check PHP error logs: `/opt/lampp/logs/php_error_log`
- Check Apache error logs: `/opt/lampp/logs/error_log`
- Review database queries in phpMyAdmin
- Test AJAX endpoints directly in browser

---

**Remember:** The `access_admin.php` file is for development only. Remove it before deploying to production!
