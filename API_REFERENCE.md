# API Reference - BeU Delivery

Quick reference for all AJAX endpoints and their usage.

---

## 🛒 Cart Management APIs

### Add to Cart
**Endpoint:** `POST /user/ajax/ajax_add_to_cart.php`

**Parameters:**
```javascript
{
  id: 123,                    // Menu item ID (required)
  quantity: 2,                // Quantity (default: 1)
  action: 'add',              // Action type (default: 'add')
  special_instructions: ''    // Special instructions (optional)
}
```

**Response:**
```javascript
{
  success: true,
  message: "Item added to cart successfully!",
  cart_count: 5,
  cart_total: 45.99,
  cart_total_formatted: "45.99",
  items: [...]
}
```

**Example:**
```javascript
fetch('ajax/ajax_add_to_cart.php', {
  method: 'POST',
  headers: {'Content-Type': 'application/x-www-form-urlencoded'},
  body: 'id=123&quantity=2'
})
.then(r => r.json())
.then(data => console.log(data));
```

---

### Update Cart
**Endpoint:** `POST /user/ajax/ajax_update_cart.php`

**Parameters:**
```javascript
{
  id: 123,              // Menu item ID (required)
  action: 'increase',   // 'increase', 'decrease', or 'set'
  quantity: 5           // Required if action is 'set'
}
```

**Response:**
```javascript
{
  success: true,
  message: "Cart updated",
  new_quantity: 3,
  cart_count: 8,
  cart_total: 67.50,
  cart_total_formatted: "67.50"
}
```

---

### Remove from Cart
**Endpoint:** `POST /user/ajax/ajax_remove_from_cart.php`

**Parameters:**
```javascript
{
  id: 123  // Menu item ID (required)
}
```

**Response:**
```javascript
{
  success: true,
  message: "Item removed from cart",
  cart_count: 3
}
```

---

### Get Cart
**Endpoint:** `GET /user/ajax/ajax_get_cart.php`

**Parameters:** None

**Response:**
```javascript
{
  success: true,
  items: [
    {
      id: 123,
      name: "Burger",
      price: 12.99,
      quantity: 2,
      subtotal: 25.98,
      image: "burger.jpg",
      store_name: "Fast Food Place",
      special_instructions: "No onions"
    }
  ],
  subtotal: 45.00,
  delivery_fee: 2.99,
  tax: 3.60,
  total: 51.59,
  total_items: 3,
  total_quantity: 5,
  store_name: "Fast Food Place",
  merchant_id: 5,
  min_order_amount: 10.00,
  meets_minimum: true
}
```

---

### Get Cart Summary
**Endpoint:** `GET /user/ajax/ajax_get_cart_summary.php`

**Parameters:** None

**Response:**
```javascript
{
  success: true,
  item_count: 3,
  total_quantity: 5,
  cart_total: 45.00,
  cart_total_formatted: "45.00",
  store_name: "Fast Food Place",
  merchant_id: 5
}
```

---

### Clear Cart
**Endpoint:** `POST /user/ajax/ajax_clear_cart.php`

**Parameters:** None

**Response:**
```javascript
{
  success: true,
  message: "Cart cleared successfully"
}
```

---

## 📦 Order Management APIs

### Process Order
**Endpoint:** `POST /user/process_order.php`

**Parameters:**
```javascript
{
  delivery_address: "123 Main St",
  delivery_instructions: "Ring doorbell",
  payment_method: "cash",        // 'cash', 'card', 'mobile_money'
  order_type: "delivery",        // 'delivery' or 'pickup'
  address_id: 5                  // Optional: saved address ID
}
```

**Response:**
```javascript
{
  success: true,
  message: "Order placed successfully!",
  order_id: 456,
  order_number: "ORD-ABC123",
  redirect: "order_confirmation.php?id=456"
}
```

---

### Track Order
**Endpoint:** `GET /user/ajax/ajax_track_order.php`

**Parameters:**
```javascript
{
  order_id: 456  // Order ID (required)
}
```

**Response:**
```javascript
{
  success: true,
  order: {
    id: 456,
    order_number: "ORD-ABC123",
    status: "preparing",
    payment_status: "paid",
    payment_method: "cash",
    order_type: "delivery",
    subtotal: 45.00,
    delivery_fee: 2.99,
    tax: 3.60,
    total: 51.59,
    delivery_address: "123 Main St",
    delivery_instructions: "Ring doorbell",
    estimated_delivery_time: "2024-01-15 14:30:00",
    actual_delivery_time: null,
    created_at: "2024-01-15 13:45:00",
    store_name: "Fast Food Place",
    store_phone: "+251911000000",
    merchant_address: "456 Store St",
    driver: {
      name: "John Doe",
      phone: "+251922000000"
    },
    progress: 50
  },
  items: [
    {
      name: "Burger",
      quantity: 2,
      price: 12.99,
      subtotal: 25.98,
      special_instructions: "No onions"
    }
  ],
  tracking: [
    {
      status: "pending",
      message: "Order placed successfully",
      created_at: "2024-01-15 13:45:00",
      created_by: "Customer Name"
    },
    {
      status: "confirmed",
      message: "Order confirmed by restaurant",
      created_at: "2024-01-15 13:47:00",
      created_by: "Restaurant Name"
    }
  ]
}
```

---

### Update Order Status (Merchant)
**Endpoint:** `POST /account/ajax/ajax_update_order_status.php`

**Parameters:**
```javascript
{
  order_id: 456,
  status: "confirmed",  // 'confirmed', 'preparing', 'ready', 'cancelled'
  message: "Your order is being prepared"  // Optional custom message
}
```

**Response:**
```javascript
{
  success: true,
  message: "Order status updated successfully",
  new_status: "confirmed"
}
```

---

## 💳 Payment APIs

### Process Payment
**Endpoint:** `POST /user/ajax/ajax_process_payment.php`

**Parameters:**
```javascript
{
  order_id: 456,
  payment_method: "card"  // 'cash', 'card', 'mobile_money', 'wallet'
}
```

**Response (Cash):**
```javascript
{
  success: true,
  message: "Cash payment will be collected on delivery",
  payment_method: "cash",
  requires_action: false
}
```

**Response (Card):**
```javascript
{
  success: true,
  message: "Card payment initialized",
  payment_method: "card",
  requires_action: true,
  client_secret: "secret_abc123",
  public_key: "pk_test_...",
  payment_intent_id: "pi_abc123"
}
```

**Response (Mobile Money):**
```javascript
{
  success: true,
  message: "Mobile money payment initialized",
  payment_method: "mobile_money",
  requires_action: true,
  checkout_url: "https://checkout.chapa.co/abc123",
  tx_ref: "beu-ORD-ABC123-1234567890"
}
```

---

### Verify Payment
**Endpoint:** `GET /user/ajax/ajax_verify_payment.php`

**Parameters:**
```javascript
{
  order_id: 456,
  reference: "beu-ORD-ABC123-1234567890"
}
```

**Response:**
```javascript
{
  success: true,
  message: "Payment verified successfully",
  status: "completed"
}
```

---

## 🔍 Search & Filter APIs

### Search Stores
**Endpoint:** `GET /user/ajax/ajax_search_stores.php`

**Parameters:**
```javascript
{
  query: "pizza",
  category: "restaurant",
  sort: "rating",
  page: 1
}
```

**Response:**
```javascript
{
  success: true,
  stores: [
    {
      merchant_id: 5,
      store_name: "Pizza Place",
      rating: 4.5,
      review_count: 120,
      delivery_fee: 2.99,
      estimated_delivery_time: 30,
      featured_image: "pizza.jpg"
    }
  ],
  total: 15,
  page: 1,
  pages: 2
}
```

---

## ❤️ Favorites APIs

### Add Favorite
**Endpoint:** `POST /user/ajax/ajax_add_favorite.php`

**Parameters:**
```javascript
{
  merchant_id: 5
}
```

**Response:**
```javascript
{
  success: true,
  message: "Added to favorites"
}
```

---

## 📊 Common Response Codes

### Success Responses
```javascript
{
  success: true,
  message: "Operation successful",
  data: {...}
}
```

### Error Responses
```javascript
{
  success: false,
  message: "Error description",
  error_code: "CART_EMPTY"  // Optional
}
```

### Authentication Error
```javascript
{
  success: false,
  message: "Please login to continue",
  redirect: "../auth/login.php"
}
```

---

## 🔐 Authentication

All AJAX endpoints require user authentication via PHP sessions.

**Session Variables:**
- `$_SESSION['user_id']` - User ID
- `$_SESSION['user_type']` - User type (customer, merchant, admin)
- `$_SESSION['merchant_id']` - Merchant ID (for merchants)

**Headers:**
```javascript
headers: {
  'Content-Type': 'application/x-www-form-urlencoded'
}
```

---

## 🛡️ Error Handling

### Client-Side Example
```javascript
fetch('ajax/ajax_add_to_cart.php', {
  method: 'POST',
  body: 'id=123&quantity=2'
})
.then(response => response.json())
.then(data => {
  if (data.success) {
    // Handle success
    console.log(data.message);
    updateCartUI(data);
  } else {
    // Handle error
    alert(data.message);
    if (data.redirect) {
      window.location.href = data.redirect;
    }
  }
})
.catch(error => {
  console.error('Error:', error);
  alert('An error occurred. Please try again.');
});
```

---

## 📝 Request Examples

### Using Fetch API
```javascript
// POST request
fetch('ajax/endpoint.php', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/x-www-form-urlencoded',
  },
  body: 'param1=value1&param2=value2'
})
.then(r => r.json())
.then(data => console.log(data));

// GET request
fetch('ajax/endpoint.php?param1=value1&param2=value2')
.then(r => r.json())
.then(data => console.log(data));
```

### Using jQuery
```javascript
// POST request
$.ajax({
  url: 'ajax/endpoint.php',
  method: 'POST',
  data: {
    param1: 'value1',
    param2: 'value2'
  },
  success: function(data) {
    console.log(data);
  },
  error: function(xhr, status, error) {
    console.error(error);
  }
});

// GET request
$.get('ajax/endpoint.php', {
  param1: 'value1',
  param2: 'value2'
}, function(data) {
  console.log(data);
});
```

---

## 🔄 Rate Limiting

Currently no rate limiting implemented. For production, consider:
- Max 100 requests per minute per user
- Max 10 cart updates per minute
- Max 5 order placements per hour

---

## 📚 Additional Resources

- **Database Schema:** See `database/schema.sql`
- **Setup Guide:** See `SETUP_GUIDE.md`
- **Testing:** See `TESTING_CHECKLIST.md`
- **Full Documentation:** See `README.md`

---

**Last Updated:** January 2026
**API Version:** 1.0
