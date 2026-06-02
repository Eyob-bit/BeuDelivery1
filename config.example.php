<?php
/**
 * BeU Delivery Configuration File
 * Copy this file to config.php and update with your settings
 */

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'beu_delivery_v2');

// Application Settings
define('APP_NAME', 'BeU Delivery');
define('APP_URL', 'http://localhost/beu-delivery');
define('APP_ENV', 'development'); // development, staging, production

// Session Configuration
define('SESSION_LIFETIME', 86400); // 24 hours in seconds
define('SESSION_NAME', 'beu_session');

// Security Settings
define('PASSWORD_MIN_LENGTH', 8);
define('ENABLE_CSRF_PROTECTION', true);
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_TIME', 900); // 15 minutes

// File Upload Settings
define('MAX_FILE_SIZE', 5242880); // 5MB in bytes
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/jpg', 'image/gif']);
define('UPLOAD_PATH_MENU', 'uploads/menu_items/');
define('UPLOAD_PATH_STORE', 'uploads/merchants/');
define('UPLOAD_PATH_PROFILE', 'uploads/profiles/');

// Payment Gateway Configuration
// Chapa (Ethiopian Payment Gateway)
define('CHAPA_SECRET_KEY', 'CHASECK_TEST-your-secret-key-here');
define('CHAPA_PUBLIC_KEY', 'CHAPUBK_TEST-your-public-key-here');
define('CHAPA_WEBHOOK_SECRET', 'your-webhook-secret');
define('CHAPA_API_URL', 'https://api.chapa.co/v1');

// Stripe (International Cards)
define('STRIPE_SECRET_KEY', 'sk_test_your-secret-key-here');
define('STRIPE_PUBLIC_KEY', 'pk_test_your-public-key-here');
define('STRIPE_WEBHOOK_SECRET', 'whsec_your-webhook-secret');

// Business Settings
define('DEFAULT_CURRENCY', 'ETB'); // Ethiopian Birr
define('CURRENCY_SYMBOL', 'Br');
define('TAX_RATE', 0.08); // 8%
define('DEFAULT_DELIVERY_FEE', 2.99);
define('DEFAULT_COMMISSION_RATE', 15.00); // 15%

// Order Settings
define('ORDER_PREFIX', 'ORD-');
define('MIN_ORDER_AMOUNT', 0);
define('MAX_ORDER_AMOUNT', 10000);
define('ESTIMATED_DELIVERY_TIME', 45); // minutes
define('ORDER_CANCELLATION_WINDOW', 300); // 5 minutes

// Notification Settings
define('ENABLE_EMAIL_NOTIFICATIONS', false);
define('ENABLE_SMS_NOTIFICATIONS', false);
define('ENABLE_PUSH_NOTIFICATIONS', false);

// Email Configuration (if enabled)
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'your-email@gmail.com');
define('SMTP_PASSWORD', 'your-app-password');
define('SMTP_FROM_EMAIL', 'noreply@beudelivery.com');
define('SMTP_FROM_NAME', 'BeU Delivery');

// SMS Configuration (if enabled)
define('SMS_PROVIDER', 'twilio'); // twilio, africastalking
define('SMS_API_KEY', 'your-sms-api-key');
define('SMS_SENDER_ID', 'BeU');

// Map/Location Settings
define('DEFAULT_LATITUDE', 9.0320);  // Addis Ababa
define('DEFAULT_LONGITUDE', 38.7469);
define('MAX_DELIVERY_RADIUS', 10); // kilometers
define('GOOGLE_MAPS_API_KEY', 'your-google-maps-api-key');

// Cache Settings
define('ENABLE_CACHE', false);
define('CACHE_DRIVER', 'file'); // file, redis, memcached
define('CACHE_LIFETIME', 3600); // 1 hour

// Logging Settings
define('ENABLE_LOGGING', true);
define('LOG_PATH', __DIR__ . '/logs/');
define('LOG_LEVEL', 'debug'); // debug, info, warning, error

// API Settings
define('API_VERSION', 'v1');
define('API_RATE_LIMIT', 100); // requests per minute
define('API_KEY_HEADER', 'X-API-Key');

// Social Media Links
define('FACEBOOK_URL', 'https://facebook.com/beudelivery');
define('TWITTER_URL', 'https://twitter.com/beudelivery');
define('INSTAGRAM_URL', 'https://instagram.com/beudelivery');

// Support Contact
define('SUPPORT_EMAIL', 'support@beudelivery.com');
define('SUPPORT_PHONE', '+251911000000');

// Feature Flags
define('FEATURE_FAVORITES', true);
define('FEATURE_REVIEWS', true);
define('FEATURE_LOYALTY_POINTS', false);
define('FEATURE_SCHEDULED_ORDERS', false);
define('FEATURE_MULTI_STORE_CART', false);

// Merchant Settings
define('MERCHANT_APPROVAL_REQUIRED', true);
define('MERCHANT_MIN_PAYOUT', 100); // Minimum amount for payout
define('MERCHANT_PAYOUT_SCHEDULE', 'weekly'); // daily, weekly, monthly

// Customer Settings
define('CUSTOMER_REFERRAL_BONUS', 10);
define('CUSTOMER_FIRST_ORDER_DISCOUNT', 5);
define('CUSTOMER_LOYALTY_POINTS_RATE', 0.01); // 1% of order value

// Delivery Settings
define('DELIVERY_DRIVER_COMMISSION', 0.70); // 70% of delivery fee
define('DELIVERY_RADIUS_ZONES', [
    ['max' => 3, 'fee' => 2.99],
    ['max' => 5, 'fee' => 4.99],
    ['max' => 10, 'fee' => 7.99]
]);

// Time Settings
define('TIMEZONE', 'Africa/Addis_Ababa');
define('DATE_FORMAT', 'Y-m-d');
define('TIME_FORMAT', 'H:i:s');
define('DATETIME_FORMAT', 'Y-m-d H:i:s');

// Pagination
define('ITEMS_PER_PAGE', 20);
define('MAX_PAGINATION_LINKS', 5);

// Search Settings
define('MIN_SEARCH_LENGTH', 3);
define('MAX_SEARCH_RESULTS', 50);

// Image Settings
define('IMAGE_QUALITY', 85); // JPEG quality 0-100
define('THUMBNAIL_WIDTH', 300);
define('THUMBNAIL_HEIGHT', 300);
define('MAX_IMAGE_WIDTH', 1920);
define('MAX_IMAGE_HEIGHT', 1080);

// Error Handling
if (APP_ENV === 'production') {
    error_reporting(0);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', LOG_PATH . 'php_errors.log');
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}

// Set timezone
date_default_timezone_set(TIMEZONE);

// Session configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', APP_ENV === 'production' ? 1 : 0);
ini_set('session.gc_maxlifetime', SESSION_LIFETIME);

/**
 * Helper function to get config value
 */
function config($key, $default = null) {
    return defined($key) ? constant($key) : $default;
}

/**
 * Helper function to check if feature is enabled
 */
function feature_enabled($feature) {
    $constant = 'FEATURE_' . strtoupper($feature);
    return defined($constant) && constant($constant) === true;
}

/**
 * Helper function to format currency
 */
function format_currency($amount) {
    return CURRENCY_SYMBOL . ' ' . number_format($amount, 2);
}

/**
 * Helper function to format date
 */
function format_date($date, $format = null) {
    $format = $format ?: DATETIME_FORMAT;
    return date($format, strtotime($date));
}
?>
