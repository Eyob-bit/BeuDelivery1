# 🚀 Admin System - Quick Test Guide

## Test the Complete Admin System in 5 Minutes

---

## Step 1: Login as Admin (30 seconds)

```
URL: http://localhost/BeU%20Delivery/auth/login.php

Credentials:
- Email: admin@beudelivery.com
- Phone: 0911111111  
- Password: admin123
```

**Expected:** Redirects to `admin/admin_panel.php`

---

## Step 2: View Dashboard (30 seconds)

**Check:**
- [ ] Pending merchants count
- [ ] Today's orders count
- [ ] Total revenue
- [ ] Total users
- [ ] Recent activity list

**Expected:** All statistics display correctly

---

## Step 3: Review Merchants (1 minute)

1. Click "All Merchants" in sidebar
2. Click "Under Review" filter
3. Click "View Details" on any merchant

**Check:**
- [ ] Store information displays
- [ ] Owner information displays
- [ ] Banking info displays
- [ ] Tax info displays
- [ ] Documents list displays
- [ ] Three action buttons visible:
  - Approve (Green)
  - Reject (Red)
  - Request Corrections (Yellow)

---

## Step 4: Test Request Corrections (1 minute)

1. Click "Request Corrections" button
2. Check "Store Information" checkbox
3. Enter: "Please provide complete business hours"
4. Check "Banking Information" checkbox
5. Enter: "Account number format is incorrect"
6. Add general notes: "Please resubmit within 48 hours"
7. Click "Send Correction Request"

**Expected:**
- Redirects back to merchant details
- Success message appears
- Merchant status remains "under_review"

---

## Step 5: Test Approve Merchant (30 seconds)

1. Go back to merchant details
2. Click "Approve" button
3. Add comment: "All information verified"
4. Click "Approve Merchant"

**Expected:**
- Status changes to "Active"
- Success message appears
- Approve/Reject buttons disappear

---

## Step 6: View Orders (1 minute)

1. Click "Orders" in sidebar
2. Try different status filters
3. Click "View" on any order

**Check:**
- [ ] Order details modal opens
- [ ] Customer information displays
- [ ] Store information displays
- [ ] Order items list displays
- [ ] Price breakdown shows (subtotal, delivery, tax, total)

---

## Step 7: View Users (30 seconds)

1. Click "Users" in sidebar
2. Try role filters (Customers, Merchants, etc.)
3. Try search functionality

**Check:**
- [ ] User list displays
- [ ] Role badges show correctly
- [ ] Order count and total spent display
- [ ] Activate/Deactivate buttons work

---

## Step 8: View Reports (1 minute)

1. Click "Reports" in sidebar
2. Try different time periods (Today, Week, Month)

**Check:**
- [ ] Revenue statistics update
- [ ] Revenue trend chart displays
- [ ] Orders by status chart displays
- [ ] Top merchants table shows
- [ ] Popular items table shows

---

## Step 9: View Restaurants (30 seconds)

1. Click "Restaurants" in sidebar
2. Try status filters
3. Try search functionality

**Check:**
- [ ] Restaurant list displays
- [ ] Logo/images show
- [ ] Order count and revenue display
- [ ] Rating displays
- [ ] View button links to merchant details

---

## 🎯 Quick Verification Checklist

### Navigation
- [ ] All sidebar links work
- [ ] Active page highlights correctly
- [ ] Logout works

### Functionality
- [ ] Filters work on all pages
- [ ] Search works on all pages
- [ ] Pagination works (if enough data)
- [ ] AJAX operations work (approve, reject, corrections)
- [ ] Modals open and close properly

### Data Display
- [ ] Statistics calculate correctly
- [ ] Charts render properly
- [ ] Tables display data
- [ ] Status badges show correct colors
- [ ] Dates format correctly

### Responsive Design
- [ ] Pages look good on desktop
- [ ] Sidebar is visible
- [ ] Tables are scrollable
- [ ] Buttons are clickable

---

## 🐛 Common Issues & Solutions

### Issue: "Access Denied"
**Solution:** Make sure you're logged in as admin. Check `admin/admin_auth.php` is included.

### Issue: Charts not showing
**Solution:** Check browser console for JavaScript errors. Ensure Chart.js CDN is loading.

### Issue: No data in tables
**Solution:** Make sure you have test data in database. Run merchant signup flow first.

### Issue: AJAX operations fail
**Solution:** Check browser console for errors. Verify endpoint files exist and have correct permissions.

### Issue: Corrections not saving
**Solution:** Run `database/add_corrections_column.php` to add the column if not exists.

---

## 📊 Test Data Requirements

To fully test the system, you need:

### Minimum Data:
- [ ] 1 admin user (already created)
- [ ] 2-3 merchants (at least 1 under review)
- [ ] 5-10 orders (various statuses)
- [ ] 3-5 regular users
- [ ] Some menu items

### Create Test Merchant:
1. Go to merchant signup: `merchant/getStarted.php`
2. Complete all steps
3. Submit for review
4. Now test admin review process

---

## ✅ Success Criteria

**System is working correctly if:**
1. ✅ Admin can login
2. ✅ Dashboard shows statistics
3. ✅ Can view all merchants
4. ✅ Can approve/reject merchants
5. ✅ Can request corrections
6. ✅ Can view order details
7. ✅ Can manage users
8. ✅ Reports show charts and data
9. ✅ Restaurants list displays
10. ✅ All filters and search work

---

## 🎉 Next Steps After Testing

1. **If everything works:**
   - System is ready for production
   - Consider adding email notifications
   - Add merchant corrections view
   - Deploy to live server

2. **If issues found:**
   - Check PHP error log: `/opt/lampp/logs/php_error_log`
   - Check browser console for JavaScript errors
   - Verify database tables exist
   - Check file permissions

---

## 📞 Quick Reference

### Admin URLs:
- Dashboard: `admin/admin_panel.php`
- Merchants: `admin/admin_merchants.php`
- Orders: `admin/orders.php`
- Users: `admin/admin_users.php`
- Reports: `admin/admin_reports.php`
- Restaurants: `admin/restaurants.php`

### Database Tables:
- `users` - All users
- `merchants` - Merchant stores
- `merchant_reviews` - Review records
- `orders` - All orders
- `order_items` - Order details

### Key Files:
- `admin/admin_auth.php` - Authentication
- `admin/admin_sidebar.php` - Navigation
- `admin/request_corrections.php` - Corrections system

---

**Total Test Time:** ~5 minutes
**Difficulty:** Easy
**Status:** Ready to test! 🚀
