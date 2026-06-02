# Complete Admin System - Implementation Plan

## ✅ Already Implemented

### 1. Admin Dashboard (`admin/admin_panel.php`)
- Overview statistics
- Pending merchants count
- Today's orders
- Total revenue
- Recent activity

### 2. Merchant Management (`admin/admin_merchants.php`)
- List all merchants
- Filter by status (All, Under Review, Active, Inactive)
- Search functionality
- Pagination
- Quick approve button

### 3. Merchant Details (`admin/admin_merchant_details.php`)
- Complete merchant information
- Store details
- Owner information
- Financial data (banking & tax)
- Documents
- Store hours
- Plan information
- Approve/Reject actions

### 4. Status Management (`admin/update_merchant_status.php`)
- AJAX approve/reject
- Status updates
- Review record creation
- Audit trail

### 5. Request Corrections (`admin/request_corrections.php`) ✨ NEW
- Section-by-section correction requests
- Store information corrections
- Banking corrections
- Tax information corrections
- Document corrections
- Menu corrections
- General notes
- Email notifications (TODO)

## 🚧 To Be Implemented

### 1. Orders Management (`admin/orders.php`)
**Features:**
- List all orders across platform
- Filter by status (pending, preparing, delivered, cancelled)
- Search by order ID, customer, merchant
- View order details
- Update order status
- Refund management
- Order analytics

### 2. Users Management (`admin/admin_users.php`)
**Features:**
- List all users
- Filter by type (customer, merchant, delivery, admin)
- Search by name, email, phone
- View user details
- Manage user roles
- Suspend/activate accounts
- User activity log

### 3. Reports & Analytics (`admin/admin_reports.php`)
**Features:**
- Revenue reports (daily, weekly, monthly)
- Merchant performance
- Order statistics
- User growth metrics
- Popular items
- Delivery performance
- Export to CSV/PDF

### 4. Restaurants Management (`admin/restaurants.php`)
**Features:**
- List all restaurants
- View restaurant details
- Manage restaurant status
- View menus
- Performance metrics

## 📋 Implementation Priority

### Phase 1: Core Review System (DONE ✅)
- [x] Admin dashboard
- [x] Merchant list
- [x] Merchant details
- [x] Approve/Reject
- [x] Request corrections

### Phase 2: Orders & Users (NEXT)
- [ ] Orders management
- [ ] Users management
- [ ] Basic reports

### Phase 3: Advanced Features
- [ ] Advanced analytics
- [ ] Email notifications
- [ ] Bulk actions
- [ ] Export functionality

## 🎯 Quick Implementation Guide

### To Add Request Corrections Feature:

1. **Run migration:**
```
http://localhost/BeU%20Delivery/database/add_corrections_column.php
```

2. **Add button to merchant details page:**
```php
<a href="request_corrections.php?id=<?php echo $merchant_id; ?>" 
   class="btn btn-warning">
    <i class="bi bi-exclamation-triangle"></i> Request Corrections
</a>
```

3. **Merchant sees corrections in their dashboard**
4. **Merchant can resubmit corrected information**
5. **Admin reviews again**

### To Implement Orders Page:

Create `admin/orders.php` with:
- Query all orders from `orders` table
- Join with `users`, `merchants`, `restaurants`
- Display in table with filters
- Add status update functionality
- Add order details modal

### To Implement Users Page:

Create `admin/admin_users.php` with:
- Query all users from `users` table
- Join with `user_roles`, `roles`
- Display with role badges
- Add user details modal
- Add role management
- Add suspend/activate actions

## 📊 Database Schema for Admin Features

### merchant_reviews (Enhanced)
```sql
ALTER TABLE merchant_reviews 
ADD COLUMN corrections_needed TEXT DEFAULT NULL;
```

### orders (Existing)
```sql
SELECT o.*, u.first_name, u.last_name, m.store_name
FROM orders o
JOIN users u ON o.user_id = u.id
JOIN merchants m ON o.merchant_id = m.merchant_id
ORDER BY o.created_at DESC;
```

### users (Existing)
```sql
SELECT u.*, GROUP_CONCAT(r.name) as roles
FROM users u
LEFT JOIN user_roles ur ON u.id = ur.user_id
LEFT JOIN roles r ON ur.role_id = r.id
GROUP BY u.id;
```

## 🔔 Notification System (TODO)

### Email Notifications Needed:
1. **Merchant approved** → Send to merchant
2. **Merchant rejected** → Send to merchant with reason
3. **Corrections requested** → Send to merchant with details
4. **Corrections resubmitted** → Notify admin
5. **New merchant application** → Notify admin

### Implementation:
```php
// Use PHPMailer or similar
function sendCorrectionRequest($merchant_email, $corrections) {
    // Email template with correction details
    // Send email
}
```

## 🎨 UI Components

### Status Badges
```php
$status_classes = [
    'under_review' => 'warning',
    'active' => 'success',
    'inactive' => 'danger',
    'needs_info' => 'info'
];
```

### Action Buttons
- Approve (Green)
- Reject (Red)
- Request Corrections (Yellow)
- View Details (Blue)
- Edit (Gray)

## 📱 Responsive Design

All admin pages should be:
- Mobile-friendly
- Tablet-optimized
- Desktop-enhanced
- Touch-friendly buttons
- Collapsible sidebar on mobile

## 🔐 Security Considerations

1. **Admin authentication** - Check on every page
2. **CSRF protection** - Add tokens to forms
3. **SQL injection** - Use prepared statements
4. **XSS protection** - Escape all output
5. **Audit logging** - Log all admin actions

## 📈 Next Steps

1. **Test current system:**
   - Create test merchant
   - Review as admin
   - Request corrections
   - Approve merchant

2. **Implement Orders page:**
   - Basic list view
   - Status filters
   - Order details
   - Status updates

3. **Implement Users page:**
   - User list
   - Role management
   - Account actions

4. **Add email notifications:**
   - Setup SMTP
   - Create email templates
   - Send on actions

5. **Build reports:**
   - Revenue charts
   - Order statistics
   - User growth

---

**Current Status:** Core review system complete with correction requests!
**Next:** Implement Orders and Users management pages.
