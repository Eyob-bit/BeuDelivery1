# Admin Login Guide - BeU Delivery

## 🔐 How to Login as Admin

### Method 1: Quick Access (Recommended for Testing)

**Step 1:** Visit the admin access page:
```
http://localhost/BeU%20Delivery/admin/access_admin.php
```

**Step 2:** The page will:
- Create all system roles (admin, merchant, delivery, customer)
- Create an admin user if one doesn't exist
- Show you the login credentials
- Provide a "Quick Login" button

**Step 3:** Click "Quick Login as Admin" to automatically log in

### Method 2: Regular Login Page

**Step 1:** Go to the login page:
```
http://localhost/BeU%20Delivery/auth/login.php
```

**Step 2:** Enter admin credentials:
- **Email:** `admin@beudelivery.com`
- **Phone:** `0911111111` (alternative)
- **Password:** `admin123`

**Step 3:** Enter the verification code sent (or shown in dev mode)

**Step 4:** You'll be automatically redirected to the admin panel

## 👥 User Roles in the System

### 1. Admin
- **Role:** System Administrator
- **Access:** Full system access
- **Can:**
  - Review and approve merchants
  - Manage all users
  - View all orders
  - Access reports and analytics
  - Manage system settings

### 2. Merchant (Store Owner)
- **Role:** Store Owner/Restaurant Owner
- **Access:** Merchant dashboard
- **Can:**
  - Manage their store
  - Create menus and add items
  - Receive and manage orders
  - View earnings and reports
  - Update store settings

### 3. Delivery Person
- **Role:** Delivery Driver
- **Access:** Delivery dashboard
- **Can:**
  - View assigned deliveries
  - Update delivery status
  - Track earnings
  - Navigate to delivery locations

### 4. Customer
- **Role:** Regular User
- **Access:** Customer interface
- **Can:**
  - Browse stores and menus
  - Place orders
  - Track deliveries
  - Save favorites
  - Manage addresses

## 🔄 How Roles Work

### Role Assignment

Users can have multiple roles. For example:
- A user can be both a **customer** and a **merchant**
- An **admin** can also be a **customer**

### Role-Based Redirects

After login, users are redirected based on their roles:

```
Admin → /admin/admin_panel.php
Merchant (active) → /account/merchant_dashboard.php
Merchant (under review) → /account/accountunderreview.php
Merchant (setup) → /merchant/setup.php
Customer → /user/home.php
Delivery → /delivery/dashboard.php
```

### Role Checking

The system checks roles in this order:
1. Check if user has merchant record → Redirect to merchant dashboard
2. Check user_type field → Redirect based on type
3. Check user_roles table → Redirect based on primary role
4. Default → Customer dashboard

## 📊 Database Structure

### roles table
```sql
CREATE TABLE roles (
  id INT PRIMARY KEY,
  name VARCHAR(50) UNIQUE,  -- 'admin', 'merchant', 'delivery', 'customer'
  description TEXT
);
```

### user_roles table (Many-to-Many)
```sql
CREATE TABLE user_roles (
  id INT PRIMARY KEY,
  user_id INT,  -- Links to users.id
  role_id INT,  -- Links to roles.id
  FOREIGN KEY (user_id) REFERENCES users(id),
  FOREIGN KEY (role_id) REFERENCES roles(id)
);
```

### users table
```sql
CREATE TABLE users (
  id INT PRIMARY KEY,
  email VARCHAR(255),
  phone VARCHAR(20),
  user_type ENUM('customer', 'merchant', 'delivery', 'admin'),
  ...
);
```

## 🛠️ Creating Users with Different Roles

### Create Admin User
```sql
-- Insert user
INSERT INTO users (email, phone, password_hash, first_name, last_name, user_type)
VALUES ('admin@example.com', '0911111111', '$2y$10$...', 'Admin', 'User', 'admin');

-- Get user ID
SET @user_id = LAST_INSERT_ID();

-- Get admin role ID
SET @role_id = (SELECT id FROM roles WHERE name = 'admin');

-- Assign role
INSERT INTO user_roles (user_id, role_id) VALUES (@user_id, @role_id);
```

### Create Merchant User
```sql
-- Insert user
INSERT INTO users (email, phone, password_hash, first_name, last_name, user_type)
VALUES ('merchant@example.com', '0922222222', '$2y$10$...', 'Store', 'Owner', 'merchant');

-- Assign merchant role
INSERT INTO user_roles (user_id, role_id) 
VALUES (LAST_INSERT_ID(), (SELECT id FROM roles WHERE name = 'merchant'));
```

### Create Delivery Person
```sql
-- Insert user
INSERT INTO users (email, phone, password_hash, first_name, last_name, user_type)
VALUES ('driver@example.com', '0933333333', '$2y$10$...', 'Delivery', 'Driver', 'delivery');

-- Assign delivery role
INSERT INTO user_roles (user_id, role_id) 
VALUES (LAST_INSERT_ID(), (SELECT id FROM roles WHERE name = 'delivery'));
```

## 🔐 Authentication Flow

### 1. User Enters Email/Phone
```
auth/login.php
  ↓
Validates email or Ethiopian phone
  ↓
Checks if user exists in database
  ↓
Generates verification code
```

### 2. User Enters Verification Code
```
auth/verify_login.php
  ↓
Validates 6-digit code
  ↓
Fetches user data + roles
  ↓
Sets session variables
```

### 3. Session Variables Set
```php
$_SESSION['user_id'] = 123;
$_SESSION['logged_in'] = true;
$_SESSION['user_name'] = 'John Doe';
$_SESSION['user_type'] = 'admin';
$_SESSION['user_roles'] = ['admin', 'customer'];
$_SESSION['merchant_id'] = 456; // If merchant
```

### 4. Redirect Based on Role
```php
if (has_merchant_record) {
    redirect_to_merchant_dashboard();
} elseif (user_type == 'admin') {
    redirect_to_admin_panel();
} elseif (user_type == 'delivery') {
    redirect_to_delivery_dashboard();
} else {
    redirect_to_customer_home();
}
```

## 🧪 Testing Different User Types

### Test Admin Login
1. Visit: `http://localhost/BeU%20Delivery/admin/access_admin.php`
2. Click "Quick Login as Admin"
3. Verify you're on admin panel
4. Check you can see pending merchants

### Test Merchant Login
1. Complete merchant registration
2. Login with merchant email/phone
3. Verify redirect to appropriate dashboard:
   - Setup → setup.php
   - Under Review → accountunderreview.php
   - Active → merchant_dashboard.php

### Test Customer Login
1. Register as regular user
2. Login with email/phone
3. Verify redirect to user/home.php
4. Check you can browse stores

## 🔧 Troubleshooting

### "Not authorized" or redirect loop
- Check `user_roles` table has correct entries
- Verify `roles` table has all 4 roles
- Check session variables are set correctly

### Admin can't access admin panel
- Verify user has 'admin' role in `user_roles` table
- Check `admin_auth.php` is checking roles correctly
- Ensure session has `user_roles` array with 'admin'

### Merchant redirects to wrong page
- Check `merchants` table has correct status
- Verify `merchant_id` is set in session
- Check redirect logic in `verify_login.php`

## 📝 Quick SQL Queries

### Check user's roles
```sql
SELECT u.email, u.user_type, GROUP_CONCAT(r.name) as roles
FROM users u
LEFT JOIN user_roles ur ON u.id = ur.user_id
LEFT JOIN roles r ON ur.role_id = r.id
WHERE u.email = 'admin@beudelivery.com'
GROUP BY u.id;
```

### List all admins
```sql
SELECT u.id, u.email, u.first_name, u.last_name
FROM users u
JOIN user_roles ur ON u.id = ur.user_id
JOIN roles r ON ur.role_id = r.id
WHERE r.name = 'admin';
```

### Add admin role to existing user
```sql
INSERT INTO user_roles (user_id, role_id)
VALUES (
  (SELECT id FROM users WHERE email = 'user@example.com'),
  (SELECT id FROM roles WHERE name = 'admin')
);
```

## 🎯 Summary

**To login as admin:**
1. Use `admin/access_admin.php` for quick access
2. Or use regular login with: `admin@beudelivery.com` / `admin123`
3. System automatically redirects to admin panel

**User roles:**
- Admin: Full system access
- Merchant: Store management
- Delivery: Delivery management
- Customer: Browse and order

**All roles are created automatically** when you visit `access_admin.php`!

---

**Need help?** Check the database tables or PHP error logs for issues.
