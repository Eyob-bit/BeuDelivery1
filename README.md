# BeU Delivery - Food Delivery Platform

A complete food delivery platform built with PHP and MySQL, featuring multi-user roles (customers, merchants, admins, delivery drivers), real-time order tracking, and integrated payment processing.

## Features

### Customer Features
- Browse restaurants and stores by category
- Search and filter stores
- View menus and add items to cart
- Real-time cart management
- Multiple payment methods (Cash, Card, Mobile Money)
- Order tracking with live updates
- Order history and favorites
- Multiple delivery addresses
- User profile management

### Merchant Features
- Complete merchant onboarding flow
- Menu management (categories and items)
- Order management and status updates
- Real-time order notifications
- Earnings and reports dashboard
- Store settings and customization
- Delivery settings configuration
- Customer reviews management

### Admin Features
- Merchant approval and management
- User management
- Order oversight
- Revenue reports
- System-wide analytics
- Store categories management

### Technical Features
- Database-driven persistent cart
- AJAX-powered real-time updates
- Payment gateway integration (Chapa, Stripe)
- Order tracking system
- Transaction management
- Notification system
- Responsive design
- Secure authentication

## Installation

### Prerequisites
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache/Nginx web server
- Composer (optional, for dependencies)

### Setup Instructions

1. **Clone or download the project**
   ```bash
   git clone <repository-url>
   cd beu-delivery
   ```

2. **Configure Database**
   - Open `includes/db.php`
   - Update database credentials:
     ```php
     $host = "localhost";
     $user = "root";
     $password = "your_password";
     $database = "beu_delivery_v2";
     ```

3. **Install Database Schema**
   - Navigate to `http://localhost/your-project/database/install.php`
   - This will create all necessary tables and insert default data
   - Default admin credentials:
     - Phone: +251911000000
     - Password: admin123

4. **Configure File Permissions**
   ```bash
   chmod 755 merchant/uploads
   chmod 755 account/uploads
   chmod 755 restaurant/uploads
   ```

5. **Payment Gateway Configuration** (Optional)
   - For production, add your API keys in `includes/payment_gateway.php`:
     ```php
     'chapa_secret_key' => 'your_chapa_secret_key',
     'stripe_secret_key' => 'your_stripe_secret_key',
     ```
   - Or set environment variables:
     ```bash
     export CHAPA_SECRET_KEY="your_key"
     export STRIPE_SECRET_KEY="your_key"
     ```

6. **Access the Application**
   - Homepage: `http://localhost/your-project/`
   - Admin Panel: `http://localhost/your-project/admin/`
   - Merchant Portal: `http://localhost/your-project/account/`

## Project Structure

```
beu-delivery/
├── account/              # Merchant dashboard and management
│   ├── ajax/            # AJAX endpoints for merchant operations
│   ├── includes/        # Merchant-specific includes
│   └── uploads/         # Store images and documents
├── admin/               # Admin panel
├── auth/                # Authentication system
├── database/            # Database schema and installation
├── delivery/            # Delivery driver interface
├── includes/            # Shared includes (db, auth, payment)
├── merchant/            # Merchant onboarding flow
├── public/              # Public assets (CSS, JS, images)
├── restaurant/          # Restaurant management (legacy)
├── user/                # Customer interface
│   ├── ajax/           # AJAX endpoints for cart and orders
│   └── includes/       # User-specific includes
└── index.php           # Landing page
```

## Database Schema

The platform uses 20+ tables including:
- `users` - All user accounts
- `merchants` - Store/restaurant information
- `menu_items` - Product catalog
- `orders` - Order records
- `order_tracking` - Real-time order status
- `cart_items` - Persistent shopping cart
- `transactions` - Payment records
- `merchant_earnings` - Earnings tracking
- `notifications` - User notifications

See `database/schema.sql` for complete schema.

## API Endpoints

### Cart Management
- `POST /user/ajax/ajax_add_to_cart.php` - Add item to cart
- `POST /user/ajax/ajax_update_cart.php` - Update cart quantity
- `POST /user/ajax/ajax_remove_from_cart.php` - Remove from cart
- `GET /user/ajax/ajax_get_cart.php` - Get cart contents
- `POST /user/ajax/ajax_clear_cart.php` - Clear entire cart

### Order Processing
- `POST /user/process_order.php` - Create new order
- `GET /user/ajax/ajax_track_order.php` - Track order status
- `POST /account/ajax/ajax_update_order_status.php` - Update order (merchant)

### Payment
- `POST /user/ajax/ajax_process_payment.php` - Initialize payment
- `GET /user/ajax/ajax_verify_payment.php` - Verify payment status

## Payment Integration

### Supported Methods
1. **Cash on Delivery** - No integration needed
2. **Card Payment** - Stripe integration
3. **Mobile Money** - Chapa (Telebirr, CBE Birr)
4. **Wallet** - Coming soon

### Testing Payments
In development mode, payments are simulated. For production:
1. Sign up for Chapa: https://chapa.co
2. Sign up for Stripe: https://stripe.com
3. Add API keys to `includes/payment_gateway.php`

## User Roles

### Customer
- Browse and order from stores
- Track orders in real-time
- Manage profile and addresses
- View order history

### Merchant
- Manage store and menu
- Process orders
- View earnings and reports
- Update store settings

### Admin
- Approve new merchants
- Manage all users
- View system-wide reports
- Configure categories

### Delivery Driver
- View assigned orders
- Update delivery status
- Navigate to delivery locations

## Security Features

- Password hashing with bcrypt
- SQL injection prevention (prepared statements)
- XSS protection
- CSRF protection (implement tokens)
- Session management
- Input validation and sanitization

## Development

### Adding New Features
1. Create database migrations in `database/`
2. Add backend logic in appropriate directory
3. Create AJAX endpoints in `ajax/` folders
4. Update frontend with JavaScript

### Code Style
- Use prepared statements for all database queries
- Sanitize all user inputs
- Follow PSR-12 coding standards
- Comment complex logic
- Use meaningful variable names

## Troubleshooting

### Database Connection Issues
- Verify credentials in `includes/db.php`
- Ensure MySQL service is running
- Check database exists: `SHOW DATABASES;`

### Cart Not Working
- Clear browser cache and cookies
- Check session is started
- Verify cart_items table exists
- Check browser console for errors

### Orders Not Creating
- Check database permissions
- Verify all required tables exist
- Check PHP error logs
- Ensure transaction support in MySQL

### Payment Failures
- Verify API keys are correct
- Check payment gateway status
- Review transaction logs
- Test with sandbox/test mode first

## Production Deployment

1. **Security Checklist**
   - Change default admin password
   - Update database credentials
   - Enable HTTPS
   - Set secure session settings
   - Disable error display
   - Enable error logging

2. **Performance**
   - Enable PHP OPcache
   - Configure MySQL query cache
   - Implement CDN for static assets
   - Enable gzip compression
   - Optimize images

3. **Monitoring**
   - Set up error logging
   - Monitor database performance
   - Track payment success rates
   - Monitor order completion rates

## Support

For issues and questions:
- Check documentation in `/docs`
- Review code comments
- Check database schema
- Test with sample data

## License

This project is proprietary software. All rights reserved.

## Credits

Built with:
- PHP
- MySQL
- Bootstrap 5
- Bootstrap Icons
- JavaScript (Vanilla)

Payment integrations:
- Chapa (Ethiopian payment gateway)
- Stripe (International cards)

## Version History

### v2.0.0 (Current)
- Complete database schema
- AJAX cart system
- Order tracking
- Payment integration
- Multi-user roles
- Responsive design

### v1.0.0
- Initial release
- Basic functionality
