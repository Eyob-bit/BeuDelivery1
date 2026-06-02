# Admin System - Quick Start Guide

## 🚀 Get Started in 3 Steps

### Step 1: Access Admin Panel
```
http://localhost/BeU%20Delivery/admin/access_admin.php
```
This will create an admin user and log you in automatically.

### Step 2: View Pending Merchants
From the dashboard, click on:
- "Pending Review" card, OR
- "All Merchants" → "Under Review" tab

### Step 3: Approve or Reject
- Click "View" to see full merchant details
- Click "Approve" to activate the merchant
- Click "Reject" to decline the application

## 📋 What You Can Do

### Review Merchant Applications
- ✅ View store information
- ✅ Check owner details
- ✅ Verify banking information
- ✅ Review tax information
- ✅ See uploaded documents
- ✅ Check store hours
- ✅ Approve or reject with comments

### After Approval
When you approve a merchant:
1. Status changes from `under_review` to `active`
2. Merchant can access their active dashboard
3. Merchant can create menus and add items
4. Merchant can upload photos
5. Merchant can start receiving orders

### After Rejection
When you reject a merchant:
1. Status changes to `inactive`
2. Merchant sees rejection reason
3. Merchant can fix issues and reapply

## 🔑 Default Admin Credentials

**Email:** admin@beudelivery.com  
**Password:** admin123

## 📊 Admin Dashboard Features

### Stats Cards
- **Pending Review**: Merchants waiting for approval
- **Today's Orders**: Orders placed today
- **Total Revenue**: All-time platform revenue
- **New Users**: Users who signed up today

### Quick Actions
- View pending applications
- Review merchant details
- Approve/reject applications
- Search and filter merchants
- View recent orders

## 🔄 Complete Workflow

```
1. Merchant Registers
   ↓
2. Merchant Completes Setup
   (Store details, menu, payment, tax info)
   ↓
3. Merchant Submits for Review
   (Status: under_review)
   ↓
4. Admin Reviews Application
   (View all details)
   ↓
5. Admin Approves/Rejects
   ↓
6a. APPROVED → Status: active
    - Merchant can manage store
    - Merchant can receive orders
    
6b. REJECTED → Status: inactive
    - Merchant sees rejection reason
    - Merchant can reapply
```

## 🎯 Key Files

- `admin/admin_panel.php` - Main dashboard
- `admin/admin_merchants.php` - Merchants list
- `admin/admin_merchant_details.php` - Detailed view
- `admin/update_merchant_status.php` - Approve/reject handler
- `admin/access_admin.php` - Quick access (DEV ONLY)

## ⚡ Quick Tips

1. **Quick Approve**: Use the "Quick Approve" button from the list view for faster processing
2. **Search**: Use the search bar to find merchants by name, email, or store name
3. **Filter**: Use status tabs to filter merchants (All, Under Review, Active, Inactive)
4. **Days Ago**: Red badge means application is older than 3 days - prioritize these!

## 🐛 Troubleshooting

### Can't access admin panel?
```
Visit: http://localhost/BeU%20Delivery/admin/access_admin.php
```

### No pending merchants showing?
- Make sure merchants have completed all setup steps
- Check if they visited finalpage.php
- Verify merchant status is 'under_review' in database

### Approval not working?
- Check browser console for errors
- Verify database connection
- Check PHP error logs: `/opt/lampp/logs/php_error_log`

## 📝 Testing Checklist

- [ ] Access admin panel successfully
- [ ] See dashboard with stats
- [ ] View pending merchants list
- [ ] Open merchant details modal
- [ ] Approve a test merchant
- [ ] Verify merchant status changed to 'active'
- [ ] Merchant can access active dashboard
- [ ] Reject a test merchant
- [ ] Verify rejection reason is saved

## 🎉 You're Ready!

The admin system is now fully functional. You can:
- Review merchant applications
- Approve qualified merchants
- Reject incomplete applications
- Monitor platform activity
- Manage users and orders

---

**Next:** Read `ADMIN_SYSTEM_GUIDE.md` for detailed documentation.
