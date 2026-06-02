# Quick Setup Guide - BeU Delivery

## 🚀 Quick Start (5 Minutes)

### Step 1: Database Setup
1. Open your browser and go to: `http://localhost/your-project-folder/database/install.php`
2. Wait for the installation to complete
3. You should see "Installation Complete!" message

### Step 2: Login as Admin
1. Go to: `http://localhost/your-project-folder/admin/`
2. Login with:
   - **Phone:** +251911000000
   - **Password:** admin123
3. Change the password immediately!

### Step 3: Test the System

#### As a Customer:
1. Go to homepage: `http://localhost/your-project-folder/`
2. Click "Sign up" and create a customer account
3. Browse stores and add items to cart
4. Complete checkout process

#### As a Merchant:
1. Go to: `http://localhost/your-project-folder/merchant/getStarted.php`
2. Complete merchant registration
3. Wait for admin approval (or approve yourself as admin)
4. Access merchant dashboard
5. Add menu items

## 📋 Default Credentials

### Admin Account
- Phone: +251911000000
- Email: admin@beudelivery.com
- Password: admin123

### Test Merchant (Create your own)
1. Register at `/merchant/getStarted.php`
2. Complete onboarding
3. Login as admin and approve the merchant

### Test Customer (Create your own)
1. Register at `/auth/signup.php`
2. Verify phone number
3. Start ordering!

## 🔧 Configuration

### Database Configuration
File: `includes/db.php`
```php
$host = "localhost";
$user = "root";
$password = "";  // Your MySQL password
$database = "beu_delivery_v2";
```

### Payment Gateway (Optional)
File: `includes/payment_gateway.php`

For Chapa (Ethiopian Mobile Money):
```php
'chapa_secret_key' => 'CHASECK_TEST-your-key-here',
'chapa_public_key' => 'CHAPUBK_TEST-your-key-here',
```

For Stripe (International Cards):
```php
'stripe_secret_key' => 'sk_test_your-key-here',
'stripe_public_key' => 'pk_test_your-key-here',
```

## 🧪 Testing Features

### 1. Test Cart System
```javascript
// Open browser console on any store page
// Add item to cart
fetch('ajax/ajax_add_to_cart.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: 'id=1&quantity=2'
}).then(r => r.json()).then(console.log);
```

### 2. Test Order Creation
1. Add items to cart
2. Go to checkout
3. Fill delivery details
4. Select payment method
5. Place order
6. Check order confirmation page

### 3. Test Merchant Dashboard
1. Login as merchant
2. View pending orders
3. Update order status
4. Check earnings

### 4. Test Admin Panel
1. Login as admin
2. View all merchants
3. Approve/reject merchants
4. View system reports

## 📁 Important Files

### Core Files
- `index.php` - Landing page
- `includes/db.php` - Database connection
- `includes/auth.php` - Authentication helpers
- `includes/payment_gateway.php` - Payment processing

### User Interface
- `user/home.php` - Store browsing
- `user/store.php` - Store menu
- `user/cart.php` - Shopping cart
- `user/checkout.php` - Checkout process
- `user/orders.php` - Order history

### Merchant Interface
- `account/merchant_dashboard.php` - Dashboard
- `account/orders.php` - Order management
- `account/menu_manager.php` - Menu management
- `account/earnings.php` - Earnings reports

### Admin Interface
- `admin/dashboard.php` - Admin dashboard
- `admin/admin_merchants.php` - Merchant management
- `admin/admin_users.php` - User management

## 🐛 Common Issues & Solutions

### Issue: "Connection failed"
**Solution:** Check database credentials in `includes/db.php`

### Issue: "Cart not updating"
**Solution:** 
1. Clear browser cache
2. Check if cart_items table exists
3. Verify user is logged in

### Issue: "Order not creating"
**Solution:**
1. Check all required tables exist
2. Verify merchant_id is valid
3. Check browser console for errors

### Issue: "Payment failing"
**Solution:**
1. For testing, use "Cash on Delivery"
2. For card/mobile money, verify API keys
3. Check transaction logs in database

### Issue: "Images not showing"
**Solution:**
1. Check upload folder permissions (755)
2. Verify image paths in database
3. Check if images exist in upload folders

## 📊 Database Tables Overview

### Core Tables
- `users` - All user accounts
- `merchants` - Store information
- `menu_items` - Products/dishes
- `orders` - Order records
- `order_items` - Order line items

### Supporting Tables
- `cart_items` - Shopping cart
- `order_tracking` - Order status history
- `transactions` - Payment records
- `merchant_earnings` - Earnings tracking
- `notifications` - User notifications
- `user_addresses` - Saved addresses
- `favorites` - Favorite stores

## 🔐 Security Checklist

### Before Going Live:
- [ ] Change default admin password
- [ ] Update database credentials
- [ ] Enable HTTPS
- [ ] Set secure session settings
- [ ] Disable error display
- [ ] Enable error logging
- [ ] Add CSRF tokens
- [ ] Implement rate limiting
- [ ] Set up backup system
- [ ] Configure firewall rules

## 📱 Testing on Mobile

1. Find your local IP: `ipconfig` (Windows) or `ifconfig` (Mac/Linux)
2. Access from mobile: `http://YOUR_IP/your-project-folder/`
3. Test responsive design
4. Test touch interactions
5. Test mobile payment flows

## 🚀 Next Steps

1. **Customize Branding**
   - Update logo and colors
   - Modify landing page content
   - Add your business information

2. **Add Content**
   - Create store categories
   - Add sample merchants
   - Upload menu items
   - Add store images

3. **Configure Settings**
   - Set delivery fees
   - Configure tax rates
   - Set commission rates
   - Define service areas

4. **Test Everything**
   - Complete order flow
   - Test all payment methods
   - Verify email notifications
   - Check mobile responsiveness

5. **Go Live**
   - Deploy to production server
   - Configure domain and SSL
   - Set up monitoring
   - Launch marketing campaign

## 📞 Support

If you encounter issues:
1. Check this guide first
2. Review README.md
3. Check database schema
4. Review code comments
5. Test with sample data

## 🎉 Success Indicators

You'll know setup is successful when:
- ✅ Database tables are created
- ✅ Admin login works
- ✅ Customer can register
- ✅ Merchant can register
- ✅ Items can be added to cart
- ✅ Orders can be placed
- ✅ Order tracking works
- ✅ Payments process correctly

## 📈 Performance Tips

1. **Enable Caching**
   - PHP OPcache
   - MySQL query cache
   - Browser caching

2. **Optimize Images**
   - Compress before upload
   - Use appropriate formats
   - Implement lazy loading

3. **Database Optimization**
   - Add indexes on frequently queried columns
   - Regular maintenance
   - Monitor slow queries

4. **Code Optimization**
   - Minimize database queries
   - Use prepared statements
   - Implement pagination

Happy Coding! 🎉
