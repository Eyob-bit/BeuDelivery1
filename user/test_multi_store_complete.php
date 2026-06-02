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
    <title>Complete Multi-Store Cart Test</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .test-section {
            border: 1px solid #ddd;
            padding: 20px;
            margin: 20px 0;
            border-radius: 8px;
        }
        .log-entry {
            padding: 5px 10px;
            margin: 2px 0;
            border-radius: 4px;
            font-family: monospace;
            font-size: 12px;
        }
        .log-info { background-color: #d1ecf1; }
        .log-success { background-color: #d4edda; }
        .log-warning { background-color: #fff3cd; }
        .log-error { background-color: #f8d7da; }
    </style>
</head>
<body>
    <div class="container py-4">
        <h1>Complete Multi-Store Cart Protection Test</h1>
        
        <div class="test-section">
            <h3>Current Cart Status</h3>
            <div id="cartStatus">Loading...</div>
        </div>
        
        <div class="test-section">
            <h3>Test Actions</h3>
            <div class="row">
                <div class="col-md-4">
                    <h5>Eyobs Restaurant (ID: 3)</h5>
                    <button class="btn btn-primary btn-sm mb-2 w-100" onclick="testAddToCart(3, 'Beef Burger', 11.50, 'Eyobs Restaurant')">
                        Add Beef Burger ($11.50)
                    </button>
                    <button class="btn btn-primary btn-sm mb-2 w-100" onclick="testAddToCart(1, 'Margherita Pizza', 12.99, 'Eyobs Restaurant')">
                        Add Margherita Pizza ($12.99)
                    </button>
                </div>
                <div class="col-md-4">
                    <h5>Absiniya Restaurant (ID: 4)</h5>
                    <button class="btn btn-success btn-sm mb-2 w-100" onclick="testAddToCart(5, '2000habesh', 344.00, 'Absiniya Restaurant')">
                        Add 2000habesh ($344.00)
                    </button>
                    <button class="btn btn-success btn-sm mb-2 w-100" onclick="testAddToCart(6, 'doro', 56.00, 'Absiniya Restaurant')">
                        Add doro ($56.00)
                    </button>
                </div>
                <div class="col-md-4">
                    <h5>Cart Actions</h5>
                    <button class="btn btn-warning btn-sm mb-2 w-100" onclick="clearCart()">
                        Clear Cart
                    </button>
                    <button class="btn btn-info btn-sm mb-2 w-100" onclick="refreshCartStatus()">
                        Refresh Status
                    </button>
                </div>
            </div>
        </div>
        
        <div class="test-section">
            <h3>Test Log</h3>
            <div id="testLog" style="max-height: 400px; overflow-y: auto; background: #f8f9fa; padding: 10px; border-radius: 4px;">
                <div class="log-entry log-info">Test started - Ready to test multi-store protection</div>
            </div>
        </div>
        
        <div class="test-section">
            <h3>Expected Behavior</h3>
            <ol>
                <li><strong>Empty Cart:</strong> Any item can be added</li>
                <li><strong>Cart with Eyobs items:</strong> Adding Absiniya items should trigger confirmation dialog</li>
                <li><strong>Cart with Absiniya items:</strong> Adding Eyobs items should trigger confirmation dialog</li>
                <li><strong>Same Restaurant:</strong> Items from same restaurant should add without confirmation</li>
                <li><strong>Confirmation Dialog:</strong> Should show clear message about clearing cart</li>
                <li><strong>User Choice:</strong> OK = clear cart and add new item, Cancel = keep current cart</li>
            </ol>
        </div>
        
        <div class="test-section">
            <h3>Quick Links</h3>
            <a href="store.php?id=3" class="btn btn-outline-primary me-2" target="_blank">Visit Eyobs Store</a>
            <a href="store.php?id=4" class="btn btn-outline-success me-2" target="_blank">Visit Absiniya Store</a>
            <a href="cart.php" class="btn btn-outline-info me-2" target="_blank">View Cart</a>
        </div>
    </div>

    <script>
    let logCounter = 0;
    
    // Load cart status on page load
    document.addEventListener('DOMContentLoaded', function() {
        refreshCartStatus();
    });
    
    function addLog(message, type = 'info') {
        logCounter++;
        const logDiv = document.getElementById('testLog');
        const timestamp = new Date().toLocaleTimeString();
        const entry = document.createElement('div');
        entry.className = `log-entry log-${type}`;
        entry.innerHTML = `[${timestamp}] ${logCounter}. ${message}`;
        logDiv.appendChild(entry);
        logDiv.scrollTop = logDiv.scrollHeight;
    }
    
    function refreshCartStatus() {
        addLog('Refreshing cart status...', 'info');
        
        fetch('ajax/ajax_get_cart.php')
            .then(response => response.json())
            .then(data => {
                const cartDiv = document.getElementById('cartStatus');
                if (data.success && data.items && data.items.length > 0) {
                    let html = '<div class="alert alert-info"><h5>Cart Contents:</h5><ul>';
                    let stores = new Set();
                    
                    data.items.forEach(item => {
                        html += `<li>${item.name} x${item.quantity} from ${item.store_name} - $${item.subtotal.toFixed(2)}</li>`;
                        stores.add(item.store_name);
                    });
                    
                    html += `</ul><strong>Total: $${data.cart_total.toFixed(2)}</strong><br>`;
                    html += `<strong>Stores: ${Array.from(stores).join(', ')}</strong></div>`;
                    cartDiv.innerHTML = html;
                    
                    addLog(`Cart loaded: ${data.items.length} items from ${stores.size} store(s)`, 'success');
                } else {
                    cartDiv.innerHTML = '<div class="alert alert-secondary">Cart is empty</div>';
                    addLog('Cart is empty', 'info');
                }
            })
            .catch(error => {
                document.getElementById('cartStatus').innerHTML = '<div class="alert alert-danger">Error loading cart</div>';
                addLog('Error loading cart: ' + error.message, 'error');
            });
    }
    
    function testAddToCart(itemId, itemName, itemPrice, storeName) {
        addLog(`Attempting to add: ${itemName} from ${storeName} (ID: ${itemId})`, 'info');
        
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
                addLog(`✅ SUCCESS: ${itemName} added to cart!`, 'success');
                refreshCartStatus();
            } else {
                if (data.requires_clear) {
                    addLog(`🚫 MULTI-STORE PROTECTION: ${data.message}`, 'warning');
                    addLog(`Current store: ${data.current_store}, New store: ${data.new_store}`, 'warning');
                    
                    const userChoice = confirm(data.message);
                    if (userChoice) {
                        addLog(`User chose OK - clearing cart and adding ${itemName}`, 'info');
                        clearCartAndAdd(itemId, itemName, storeName);
                    } else {
                        addLog(`User chose Cancel - keeping current cart`, 'info');
                    }
                } else {
                    addLog(`❌ ERROR: ${data.message}`, 'error');
                }
            }
        })
        .catch(error => {
            console.error('Error:', error);
            addLog(`❌ NETWORK ERROR: ${error.message}`, 'error');
        });
    }
    
    function clearCartAndAdd(itemId, itemName, storeName) {
        addLog(`Clearing cart to add ${itemName} from ${storeName}...`, 'info');
        
        fetch('ajax/ajax_clear_cart.php', {
            method: 'POST'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                addLog(`✅ Cart cleared successfully`, 'success');
                
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
                        addLog(`✅ ${itemName} added to cart after clearing!`, 'success');
                        refreshCartStatus();
                    } else {
                        addLog(`❌ Failed to add ${itemName} after clearing: ${data.message}`, 'error');
                    }
                });
            } else {
                addLog(`❌ Failed to clear cart: ${data.message}`, 'error');
            }
        })
        .catch(error => {
            addLog(`❌ Error clearing cart: ${error.message}`, 'error');
        });
    }
    
    function clearCart() {
        addLog('Manually clearing cart...', 'info');
        
        fetch('ajax/ajax_clear_cart.php', {
            method: 'POST'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                addLog('✅ Cart cleared manually', 'success');
                refreshCartStatus();
            } else {
                addLog(`❌ Failed to clear cart: ${data.message}`, 'error');
            }
        })
        .catch(error => {
            addLog(`❌ Error clearing cart: ${error.message}`, 'error');
        });
    }
    </script>
</body>
</html>