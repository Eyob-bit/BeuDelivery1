<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set test session data
$_SESSION['user_id'] = 1;
$_SESSION['user_name'] = 'Test User';
$_SESSION['logged_in'] = true;

include __DIR__ . "/../includes/db.php";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Multi-Store Cart Test</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .test-section {
            border: 1px solid #ddd;
            padding: 20px;
            margin: 20px 0;
            border-radius: 8px;
        }
        .success { background-color: #d4edda; }
        .warning { background-color: #fff3cd; }
        .danger { background-color: #f8d7da; }
    </style>
</head>
<body>
    <div class="container py-4">
        <h1>Multi-Store Cart Protection Test</h1>
        
        <div class="test-section">
            <h3>Current Cart Status</h3>
            <div id="cartStatus">Loading...</div>
        </div>
        
        <div class="test-section">
            <h3>Test Items</h3>
            <div class="row">
                <div class="col-md-6">
                    <h5>Eyobs Restaurant (Merchant ID: 3)</h5>
                    <button class="btn btn-primary mb-2" onclick="addToCart(3, 'Beef Burger', 11.50)">
                        Add Beef Burger ($11.50)
                    </button><br>
                    <button class="btn btn-primary mb-2" onclick="addToCart(1, 'Margherita Pizza', 12.99)">
                        Add Margherita Pizza ($12.99)
                    </button>
                </div>
                <div class="col-md-6">
                    <h5>Absiniya Restaurant (Merchant ID: 4)</h5>
                    <button class="btn btn-success mb-2" onclick="addToCart(5, '2000habesh', 344.00)">
                        Add 2000habesh ($344.00)
                    </button><br>
                    <button class="btn btn-success mb-2" onclick="addToCart(6, 'doro', 56.00)">
                        Add doro ($56.00)
                    </button>
                </div>
            </div>
        </div>
        
        <div class="test-section">
            <h3>Test Results</h3>
            <div id="testResults"></div>
        </div>
        
        <div class="test-section">
            <h3>Instructions</h3>
            <ol>
                <li><strong>Step 1:</strong> Click any item from Eyobs Restaurant to add it to cart</li>
                <li><strong>Step 2:</strong> Then click any item from Absiniya Restaurant</li>
                <li><strong>Expected Result:</strong> You should see a confirmation dialog asking to clear cart</li>
                <li><strong>Step 3:</strong> Click "OK" to clear cart and add new item, or "Cancel" to keep current cart</li>
            </ol>
        </div>
        
        <div class="test-section">
            <button class="btn btn-warning" onclick="clearCart()">Clear Cart</button>
            <button class="btn btn-info" onclick="refreshCartStatus()">Refresh Cart Status</button>
        </div>
    </div>

    <script>
    // Load cart status on page load
    document.addEventListener('DOMContentLoaded', function() {
        refreshCartStatus();
    });
    
    function refreshCartStatus() {
        fetch('ajax/ajax_get_cart.php')
            .then(response => response.json())
            .then(data => {
                const cartDiv = document.getElementById('cartStatus');
                if (data.success && data.items && data.items.length > 0) {
                    let html = '<div class="alert alert-info"><h5>Cart Contents:</h5><ul>';
                    data.items.forEach(item => {
                        html += `<li>${item.name} x${item.quantity} from ${item.store_name} - $${item.subtotal.toFixed(2)}</li>`;
                    });
                    html += `</ul><strong>Total: $${data.cart_total.toFixed(2)}</strong></div>`;
                    cartDiv.innerHTML = html;
                } else {
                    cartDiv.innerHTML = '<div class="alert alert-secondary">Cart is empty</div>';
                }
            })
            .catch(error => {
                document.getElementById('cartStatus').innerHTML = '<div class="alert alert-danger">Error loading cart</div>';
            });
    }
    
    function addToCart(itemId, itemName, itemPrice) {
        const resultsDiv = document.getElementById('testResults');
        resultsDiv.innerHTML += `<div class="alert alert-info">Attempting to add: ${itemName} (ID: ${itemId})</div>`;
        
        fetch('ajax/ajax_add_to_cart.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'id=' + itemId + '&action=add&quantity=1'
        })
        .then(response => response.json())
        .then(data => {
            console.log('Response:', data);
            
            if (data.success) {
                resultsDiv.innerHTML += `<div class="alert alert-success">✅ ${itemName} added to cart successfully!</div>`;
                refreshCartStatus();
            } else {
                if (data.requires_clear) {
                    resultsDiv.innerHTML += `<div class="alert alert-warning">🚫 Multi-store protection triggered!</div>`;
                    resultsDiv.innerHTML += `<div class="alert alert-info">Message: ${data.message}</div>`;
                    
                    if (confirm(data.message)) {
                        resultsDiv.innerHTML += `<div class="alert alert-info">User chose to clear cart...</div>`;
                        clearCartAndAdd(itemId, itemName);
                    } else {
                        resultsDiv.innerHTML += `<div class="alert alert-secondary">User cancelled - keeping current cart</div>`;
                    }
                } else {
                    resultsDiv.innerHTML += `<div class="alert alert-danger">❌ Error: ${data.message}</div>`;
                }
            }
        })
        .catch(error => {
            console.error('Error:', error);
            resultsDiv.innerHTML += `<div class="alert alert-danger">❌ Network error occurred</div>`;
        });
    }
    
    function clearCartAndAdd(itemId, itemName) {
        const resultsDiv = document.getElementById('testResults');
        
        fetch('ajax/ajax_clear_cart.php', {
            method: 'POST'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                resultsDiv.innerHTML += `<div class="alert alert-success">✅ Cart cleared successfully</div>`;
                
                // Now add the new item
                fetch('ajax/ajax_add_to_cart.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'id=' + itemId + '&action=add&quantity=1'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        resultsDiv.innerHTML += `<div class="alert alert-success">✅ ${itemName} added to cart after clearing!</div>`;
                        refreshCartStatus();
                    } else {
                        resultsDiv.innerHTML += `<div class="alert alert-danger">❌ Failed to add item after clearing: ${data.message}</div>`;
                    }
                });
            } else {
                resultsDiv.innerHTML += `<div class="alert alert-danger">❌ Failed to clear cart: ${data.message}</div>`;
            }
        });
    }
    
    function clearCart() {
        const resultsDiv = document.getElementById('testResults');
        
        fetch('ajax/ajax_clear_cart.php', {
            method: 'POST'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                resultsDiv.innerHTML += `<div class="alert alert-success">✅ Cart cleared manually</div>`;
                refreshCartStatus();
            } else {
                resultsDiv.innerHTML += `<div class="alert alert-danger">❌ Failed to clear cart: ${data.message}</div>`;
            }
        });
    }
    </script>
</body>
</html>