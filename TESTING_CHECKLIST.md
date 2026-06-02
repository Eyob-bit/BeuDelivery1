# Testing Checklist - BeU Delivery

## 🧪 Complete Testing Guide

### Pre-Testing Setup
- [ ] Database installed successfully
- [ ] All tables created
- [ ] Default admin account exists
- [ ] Upload folders have correct permissions
- [ ] PHP error reporting enabled (for development)

---

## 1. Database Installation

### Test Database Setup
- [ ] Navigate to `/database/install.php`
- [ ] Verify all tables created successfully
- [ ] Check for any error messages
- [ ] Verify default admin user created
- [ ] Verify store categories inserted

**Expected Result:** "Installation Complete!" message with green checkmarks

---

## 2. Authentication System

### Admin Login
- [ ] Go to `/admin/`
- [ ] Login with phone: +251911000000
- [ ] Login with password: admin123
- [ ] Verify redirect to admin dashboard
- [ ] Check session is maintained
- [ ] Test logout functionality

### Customer Registration
- [ ] Go to `/auth/signup.php`
- [ ] Enter phone number
- [ ] Enter name and email
- [ ] Accept terms and conditions
- [ ] Verify phone verification (if enabled)
- [ ] Complete registration
- [ ] Verify redirect to home page

### Merchant Registration
- [ ] Go to `/merchant/getStarted.php`
- [ ] Complete all onboarding steps
- [ ] Upload required documents
- [ ] Submit for approval
- [ ] Verify "under review" status

---

## 3. Cart Functionality

### Add to Cart
- [ ] Browse to a store page
- [ ] Click "Add to Cart" on an item
- [ ] Verify success message appears
- [ ] Check cart count updates in navbar
- [ ] Verify item appears in cart page

### Update Cart
- [ ] Go to cart page
- [ ] Click increase quantity button
- [ ] Verify quantity updates
- [ ] Verify subtotal recalculates
- [ ] Click decrease quantity button
- [ ] Verify updates work correctly

### Remove from Cart
- [ ] Click remove button on cart item
- [ ] Confirm removal
- [ ] Verify item disappears
- [ ] Verify totals update
- [ ] Verify cart count updates

### Multi-Store Prevention
- [ ] Add item from Store A
- [ ] Try to add item from Store B
- [ ] Verify warning message appears
- [ ] Verify cart not mixed
- [ ] Test clear cart option

### Cart Persistence
- [ ] Add items to cart
- [ ] Logout
- [ ] Login again
- [ ] Verify cart items still there

---

## 4. Order Placement

### Checkout Process
- [ ] Add items to cart
- [ ] Click "Proceed to Checkout"
- [ ] Verify cart items displayed
- [ ] Enter delivery address
- [ ] Add delivery instructions
- [ ] Select delivery/pickup option
- [ ] Choose payment method
- [ ] Verify totals are correct

### Order Creation
- [ ] Click "Place Order"
- [ ] Verify loading state shows
- [ ] Wait for order confirmation
- [ ] Verify redirect to confirmation page
- [ ] Check order number generated
- [ ] Verify cart is cleared

### Order Confirmation Page
- [ ] Verify order details displayed
- [ ] Check order number
- [ ] Verify items list
- [ ] Check totals
- [ ] Verify delivery address
- [ ] Check payment method
- [ ] Verify status badge

---

## 5. Order Tracking

### Customer Tracking
- [ ] Go to order confirmation page
- [ ] Verify timeline displayed
- [ ] Check current status highlighted
- [ ] Click "Track Live" button
- [ ] Verify tracking page loads
- [ ] Check auto-refresh works (wait 30 seconds)

### Merchant Order Management
- [ ] Login as merchant
- [ ] Go to orders page
- [ ] Verify pending orders shown
- [ ] Click on an order
- [ ] Update status to "Confirmed"
- [ ] Verify status updates
- [ ] Update to "Preparing"
- [ ] Update to "Ready"
- [ ] Verify customer sees updates

---

## 6. Payment Processing

### Cash on Delivery
- [ ] Select "Cash on Delivery"
- [ ] Place order
- [ ] Verify order created
- [ ] Check payment status is "pending"
- [ ] Verify no payment gateway redirect

### Card Payment (Test Mode)
- [ ] Select "Card Payment"
- [ ] Place order
- [ ] Verify payment initialization
- [ ] Check transaction record created
- [ ] Verify payment status

### Mobile Money (Test Mode)
- [ ] Select "Mobile Money"
- [ ] Place order
- [ ] Verify payment initialization
- [ ] Check redirect URL generated
- [ ] Verify transaction record

---

## 7. Merchant Dashboard

### Dashboard Access
- [ ] Login as merchant
- [ ] Verify dashboard loads
- [ ] Check today's stats displayed
- [ ] Verify pending orders shown
- [ ] Check earnings summary
- [ ] Verify menu item count

### Menu Management
- [ ] Go to Menu Manager
- [ ] Add new category
- [ ] Add new menu item
- [ ] Upload item image
- [ ] Set price and description
- [ ] Save item
- [ ] Verify item appears
- [ ] Edit item
- [ ] Delete item

### Order Management
- [ ] Go to Orders page
- [ ] View order details
- [ ] Update order status
- [ ] Add custom message
- [ ] Verify customer notified
- [ ] Filter orders by status
- [ ] Search orders

### Earnings
- [ ] Go to Earnings page
- [ ] Verify total earnings shown
- [ ] Check commission calculation
- [ ] Verify net amount correct
- [ ] Check payment status
- [ ] View earnings history

---

## 8. Admin Panel

### Merchant Management
- [ ] Login as admin
- [ ] Go to Merchants page
- [ ] View pending merchants
- [ ] Approve a merchant
- [ ] Verify merchant status changes
- [ ] Verify merchant can login
- [ ] Test reject merchant
- [ ] Test suspend merchant

### User Management
- [ ] Go to Users page
- [ ] View all users
- [ ] Filter by user type
- [ ] Search users
- [ ] View user details
- [ ] Deactivate user
- [ ] Reactivate user

### System Reports
- [ ] Go to Reports page
- [ ] View revenue reports
- [ ] Check order statistics
- [ ] Verify merchant performance
- [ ] Export reports (if available)

---

## 9. User Interface

### Responsive Design
- [ ] Test on desktop (1920x1080)
- [ ] Test on laptop (1366x768)
- [ ] Test on tablet (768x1024)
- [ ] Test on mobile (375x667)
- [ ] Verify all elements visible
- [ ] Check navigation works
- [ ] Test touch interactions

### Navigation
- [ ] Test all menu links
- [ ] Verify breadcrumbs work
- [ ] Test back buttons
- [ ] Check footer links
- [ ] Test search functionality
- [ ] Verify filters work

### Forms
- [ ] Test all form validations
- [ ] Check required fields
- [ ] Test email validation
- [ ] Test phone validation
- [ ] Verify error messages
- [ ] Test success messages

---

## 10. Performance

### Page Load Times
- [ ] Homepage loads < 3 seconds
- [ ] Store page loads < 2 seconds
- [ ] Cart page loads < 1 second
- [ ] Checkout loads < 2 seconds
- [ ] Dashboard loads < 3 seconds

### AJAX Requests
- [ ] Cart updates < 500ms
- [ ] Order status < 1 second
- [ ] Search results < 1 second
- [ ] No console errors
- [ ] Proper error handling

---

## 11. Security

### Authentication
- [ ] Cannot access protected pages without login
- [ ] Session expires correctly
- [ ] Logout works properly
- [ ] Password is hashed in database
- [ ] SQL injection prevented

### Authorization
- [ ] Customers cannot access merchant pages
- [ ] Merchants cannot access admin pages
- [ ] Users can only see their own orders
- [ ] Merchants can only manage their orders
- [ ] Admin has full access

### Data Validation
- [ ] All inputs sanitized
- [ ] XSS attacks prevented
- [ ] File uploads validated
- [ ] Price manipulation prevented
- [ ] Order tampering prevented

---

## 12. Error Handling

### User Errors
- [ ] Empty cart checkout prevented
- [ ] Invalid address rejected
- [ ] Minimum order enforced
- [ ] Out of stock items handled
- [ ] Payment failures handled

### System Errors
- [ ] Database errors caught
- [ ] Payment gateway errors handled
- [ ] File upload errors shown
- [ ] Network errors handled
- [ ] Proper error messages displayed

---

## 13. Edge Cases

### Cart Edge Cases
- [ ] Add same item multiple times
- [ ] Update quantity to 0
- [ ] Remove last item
- [ ] Add item from closed store
- [ ] Add unavailable item

### Order Edge Cases
- [ ] Order with minimum amount
- [ ] Order with maximum amount
- [ ] Cancel order immediately
- [ ] Cancel order after confirmation
- [ ] Multiple orders simultaneously

### Payment Edge Cases
- [ ] Payment timeout
- [ ] Payment cancellation
- [ ] Duplicate payment
- [ ] Refund request
- [ ] Payment verification failure

---

## 14. Integration Testing

### Complete User Journey
- [ ] Register as customer
- [ ] Browse stores
- [ ] Add items to cart
- [ ] Proceed to checkout
- [ ] Place order
- [ ] Track order
- [ ] Receive order
- [ ] Leave review

### Complete Merchant Journey
- [ ] Register as merchant
- [ ] Complete onboarding
- [ ] Wait for approval
- [ ] Setup store
- [ ] Add menu items
- [ ] Receive order
- [ ] Process order
- [ ] Complete delivery
- [ ] View earnings

---

## 15. Browser Compatibility

### Desktop Browsers
- [ ] Chrome (latest)
- [ ] Firefox (latest)
- [ ] Safari (latest)
- [ ] Edge (latest)

### Mobile Browsers
- [ ] Chrome Mobile
- [ ] Safari iOS
- [ ] Samsung Internet
- [ ] Firefox Mobile

---

## 🐛 Bug Tracking

### Found Issues
| Issue | Severity | Status | Notes |
|-------|----------|--------|-------|
|       |          |        |       |

### Severity Levels
- **Critical:** Blocks core functionality
- **High:** Major feature broken
- **Medium:** Minor feature issue
- **Low:** Cosmetic issue

---

## ✅ Sign-Off

### Testing Completed By
- Name: _______________
- Date: _______________
- Environment: _______________

### Test Results
- Total Tests: _______________
- Passed: _______________
- Failed: _______________
- Blocked: _______________

### Ready for Production?
- [ ] Yes, all tests passed
- [ ] No, issues found (see bug tracking)
- [ ] Partial, with known limitations

---

## 📝 Notes

Add any additional notes, observations, or recommendations here:

```
[Your notes here]
```

---

**Remember:** Test thoroughly before deploying to production!
