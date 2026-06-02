# System Architecture - BeU Delivery

## 📐 High-Level Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                        CLIENT LAYER                          │
├─────────────────────────────────────────────────────────────┤
│  Web Browser (Chrome, Firefox, Safari, Edge)                │
│  - HTML5 + CSS3 + JavaScript                                │
│  - Bootstrap 5 (Responsive UI)                              │
│  - AJAX (Real-time updates)                                 │
└─────────────────────────────────────────────────────────────┘
                            ↓ HTTP/HTTPS
┌─────────────────────────────────────────────────────────────┐
│                    APPLICATION LAYER                         │
├─────────────────────────────────────────────────────────────┤
│  PHP 7.4+ (Server-side Logic)                               │
│  ├── User Interface (/user/)                                │
│  ├── Merchant Portal (/account/, /merchant/)                │
│  ├── Admin Panel (/admin/)                                  │
│  ├── Authentication (/auth/)                                │
│  └── AJAX APIs (/ajax/)                                     │
└─────────────────────────────────────────────────────────────┘
                            ↓ MySQLi
┌─────────────────────────────────────────────────────────────┐
│                      DATABASE LAYER                          │
├─────────────────────────────────────────────────────────────┤
│  MySQL 5.7+ (Relational Database)                           │
│  - 20+ Tables                                               │
│  - Foreign Key Constraints                                  │
│  - Indexes for Performance                                  │
│  - Transaction Support                                      │
└─────────────────────────────────────────────────────────────┘
                            ↓ API Calls
┌─────────────────────────────────────────────────────────────┐
│                   EXTERNAL SERVICES                          │
├─────────────────────────────────────────────────────────────┤
│  Payment Gateways:                                          │
│  ├── Chapa (Mobile Money - Telebirr, CBE Birr)            │
│  ├── Stripe (International Cards)                          │
│  └── Cash on Delivery (No gateway)                         │
│                                                             │
│  Optional Services:                                         │
│  ├── SMS Gateway (Twilio, Africa's Talking)               │
│  ├── Email Service (SMTP)                                  │
│  └── Maps API (Google Maps)                                │
└─────────────────────────────────────────────────────────────┘
```

---

## 🏗️ Application Structure

### Directory Organization
```
beu-delivery/
│
├── 📁 user/                    # Customer Interface
│   ├── home.php               # Store browsing
│   ├── store.php              # Store menu
│   ├── cart.php               # Shopping cart
│   ├── checkout.php           # Checkout process
│   ├── orders.php             # Order history
│   ├── order_confirmation.php # Order success
│   ├── track_order.php        # Order tracking
│   ├── process_order.php      # Order creation
│   └── ajax/                  # AJAX endpoints
│       ├── ajax_add_to_cart.php
│       ├── ajax_update_cart.php
│       ├── ajax_remove_from_cart.php
│       ├── ajax_get_cart.php
│       ├── ajax_track_order.php
│       └── ajax_process_payment.php
│
├── 📁 account/                 # Merchant Dashboard
│   ├── merchant_dashboard.php # Main dashboard
│   ├── orders.php             # Order management
│   ├── menu_manager.php       # Menu management
│   ├── earnings.php           # Earnings reports
│   ├── settings.php           # Store settings
│   └── ajax/                  # Merchant AJAX
│       └── ajax_update_order_status.php
│
├── 📁 merchant/                # Merchant Onboarding
│   ├── getStarted.php         # Registration start
│   ├── signup.php             # Account creation
│   ├── enter_store_details.php
│   ├── uploadmenu.php
│   ├── setup_payment.php
│   └── finalpage.php
│
├── 📁 admin/                   # Admin Panel
│   ├── dashboard.php          # Admin dashboard
│   ├── admin_merchants.php    # Merchant management
│   ├── admin_users.php        # User management
│   ├── orders.php             # All orders
│   └── revenue.php            # Revenue reports
│
├── 📁 auth/                    # Authentication
│   ├── login.php              # User login
│   ├── signup.php             # User registration
│   ├── verify_login.php       # Phone verification
│   └── logout.php             # Logout
│
├── 📁 includes/                # Shared Components
│   ├── db.php                 # Database connection
│   ├── auth.php               # Auth helpers
│   ├── payment_gateway.php    # Payment processing
│   └── header.php             # Common header
│
├── 📁 database/                # Database Files
│   ├── schema.sql             # Complete schema
│   └── install.php            # Installation script
│
├── 📁 public/                  # Static Assets
│   ├── css/                   # Stylesheets
│   ├── js/                    # JavaScript
│   └── images/                # Images
│
└── 📁 uploads/                 # User Uploads
    ├── menu_items/
    ├── merchants/
    └── profiles/
```

---

## 🔄 Data Flow Diagrams

### 1. Order Placement Flow
```
Customer                    Application              Database           Payment Gateway
   │                            │                        │                     │
   │──Add to Cart──────────────>│                        │                     │
   │                            │──Insert cart_items────>│                     │
   │<───Cart Updated────────────│                        │                     │
   │                            │                        │                     │
   │──Checkout─────────────────>│                        │                     │
   │                            │──Get cart items───────>│                     │
   │<───Show Checkout───────────│<───Cart data───────────│                     │
   │                            │                        │                     │
   │──Place Order──────────────>│                        │                     │
   │                            │──BEGIN TRANSACTION────>│                     │
   │                            │──Create order─────────>│                     │
   │                            │──Create order_items───>│                     │
   │                            │──Create tracking──────>│                     │
   │                            │──Create transaction───>│                     │
   │                            │──Clear cart───────────>│                     │
   │                            │──COMMIT───────────────>│                     │
   │                            │                        │                     │
   │                            │──Initialize Payment────────────────────────>│
   │                            │<───Payment Response────────────────────────│
   │<───Order Confirmation──────│                        │                     │
```

### 2. Order Tracking Flow
```
Customer              Application           Database           Merchant
   │                      │                     │                  │
   │──Track Order────────>│                     │                  │
   │                      │──Get order details─>│                  │
   │                      │──Get tracking──────>│                  │
   │<───Order Status──────│<───Data─────────────│                  │
   │                      │                     │                  │
   │                      │                     │<──Update Status──│
   │                      │<──Notification──────│                  │
   │<───Status Update─────│                     │                  │
```

### 3. Payment Processing Flow
```
Customer          Application       Database      Payment Gateway
   │                  │                 │                │
   │──Pay────────────>│                 │                │
   │                  │──Get order─────>│                │
   │                  │──Initialize─────────────────────>│
   │                  │<──Payment URL───────────────────│
   │<──Redirect───────│                 │                │
   │                  │                 │                │
   │──Complete Payment────────────────────────────────>│
   │<──Redirect to Callback──────────────────────────│
   │                  │                 │                │
   │──Verify──────────>│                 │                │
   │                  │──Verify Payment─────────────────>│
   │                  │<──Confirmation──────────────────│
   │                  │──Update order──>│                │
   │                  │──Update transaction>│            │
   │<──Success────────│                 │                │
```

---

## 🗄️ Database Architecture

### Core Tables Relationships
```
users (id)
  ├── merchants (user_id) ──┬── merchant_details (merchant_id)
  │                         ├── delivery_settings (merchant_id)
  │                         ├── menu_categories (merchant_id)
  │                         │   └── menu_items (category_id, merchant_id)
  │                         ├── orders (merchant_id)
  │                         └── merchant_earnings (merchant_id)
  │
  ├── orders (user_id) ─────┬── order_items (order_id)
  │                         ├── order_tracking (order_id)
  │                         └── transactions (order_id)
  │
  ├── cart_items (user_id, menu_item_id)
  ├── user_addresses (user_id)
  ├── favorites (user_id, merchant_id)
  └── notifications (user_id)
```

### Table Categories
```
┌─────────────────────────────────────────────┐
│           USER MANAGEMENT                    │
├─────────────────────────────────────────────┤
│ • users                                     │
│ • user_addresses                            │
│ • payment_methods                           │
└─────────────────────────────────────────────┘

┌─────────────────────────────────────────────┐
│         MERCHANT MANAGEMENT                  │
├─────────────────────────────────────────────┤
│ • merchants                                 │
│ • merchant_details                          │
│ • delivery_settings                         │
│ • store_categories                          │
└─────────────────────────────────────────────┘

┌─────────────────────────────────────────────┐
│          MENU MANAGEMENT                     │
├─────────────────────────────────────────────┤
│ • menu_categories                           │
│ • menu_items                                │
└─────────────────────────────────────────────┘

┌─────────────────────────────────────────────┐
│         ORDER MANAGEMENT                     │
├─────────────────────────────────────────────┤
│ • orders                                    │
│ • order_items                               │
│ • order_tracking                            │
│ • cart_items                                │
└─────────────────────────────────────────────┘

┌─────────────────────────────────────────────┐
│        PAYMENT & FINANCE                     │
├─────────────────────────────────────────────┤
│ • transactions                              │
│ • merchant_earnings                         │
└─────────────────────────────────────────────┘

┌─────────────────────────────────────────────┐
│         ENGAGEMENT                           │
├─────────────────────────────────────────────┤
│ • favorites                                 │
│ • merchant_reviews                          │
│ • notifications                             │
└─────────────────────────────────────────────┘
```

---

## 🔐 Security Architecture

### Authentication Flow
```
1. User Login
   ├── Phone number validation
   ├── Password verification (bcrypt)
   ├── Session creation
   └── User type identification

2. Session Management
   ├── PHP Sessions
   ├── Session timeout (24 hours)
   ├── Secure cookies (httponly)
   └── Session regeneration

3. Authorization
   ├── Role-based access control
   ├── Page-level protection
   ├── API endpoint protection
   └── Data ownership verification
```

### Data Protection
```
Input Validation
   ├── Type checking
   ├── Length validation
   ├── Format validation
   └── Sanitization

SQL Injection Prevention
   ├── Prepared statements
   ├── Parameter binding
   └── No dynamic SQL

XSS Prevention
   ├── htmlspecialchars()
   ├── Content-Type headers
   └── Input encoding

CSRF Protection
   └── Token-based (to implement)
```

---

## 🚀 Performance Optimization

### Database Optimization
```
Indexes
   ├── Primary keys (all tables)
   ├── Foreign keys (relationships)
   ├── Search fields (name, phone, email)
   └── Status fields (order status, payment status)

Query Optimization
   ├── Prepared statements (reusable)
   ├── JOIN optimization
   ├── Limit result sets
   └── Pagination
```

### Application Optimization
```
Caching Strategy
   ├── Session caching
   ├── Query result caching
   └── Static asset caching

Code Optimization
   ├── Minimal database queries
   ├── Efficient loops
   ├── Lazy loading
   └── AJAX for updates
```

---

## 📱 Responsive Design

### Breakpoints
```
Mobile:    < 768px
Tablet:    768px - 1024px
Desktop:   > 1024px
```

### Mobile-First Approach
```
Base Styles (Mobile)
   ↓
Tablet Adjustments
   ↓
Desktop Enhancements
```

---

## 🔄 State Management

### Client-Side State
```
Cart State
   ├── Stored in database
   ├── Synced via AJAX
   └── Real-time updates

Order State
   ├── Tracked in database
   ├── Polled every 30 seconds
   └── Push notifications (future)

User State
   ├── PHP Sessions
   ├── Persistent login
   └── Remember me (future)
```

---

## 🧩 Integration Points

### Payment Gateways
```
Chapa Integration
   ├── Initialize payment
   ├── Redirect to checkout
   ├── Handle callback
   └── Verify transaction

Stripe Integration
   ├── Create payment intent
   ├── Client-side card collection
   ├── Confirm payment
   └── Handle webhooks
```

### Future Integrations
```
SMS Gateway
   ├── Order notifications
   ├── OTP verification
   └── Marketing messages

Email Service
   ├── Order confirmations
   ├── Receipts
   └── Newsletters

Push Notifications
   ├── Order updates
   ├── Promotions
   └── Reminders
```

---

## 📊 Scalability Considerations

### Horizontal Scaling
```
Load Balancer
   ├── Web Server 1
   ├── Web Server 2
   └── Web Server N

Database
   ├── Master (Write)
   └── Slaves (Read)
```

### Vertical Scaling
```
Increase Resources
   ├── More CPU
   ├── More RAM
   └── Faster Storage
```

### Caching Layer
```
Redis/Memcached
   ├── Session storage
   ├── Query results
   └── API responses
```

---

## 🔍 Monitoring & Logging

### Application Logs
```
Error Logs
   ├── PHP errors
   ├── Database errors
   └── Payment failures

Access Logs
   ├── Page views
   ├── API calls
   └── User actions

Transaction Logs
   ├── Orders placed
   ├── Payments processed
   └── Status changes
```

---

## 🎯 Design Patterns Used

1. **MVC-like Structure**
   - Models: Database queries
   - Views: PHP templates
   - Controllers: Page logic

2. **Repository Pattern**
   - Database abstraction
   - Reusable queries

3. **Service Layer**
   - Payment gateway
   - Notification service

4. **Factory Pattern**
   - Payment method creation

---

**Last Updated:** January 2026
**Version:** 2.0
