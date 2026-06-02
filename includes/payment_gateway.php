<?php
/**
 * Payment Gateway Integration
 * Supports: Cash, Card (Stripe), Mobile Money (Chapa/Telebirr)
 */

class PaymentGateway {
    private $conn;
    private $config;
    
    public function __construct($db_connection) {
        $this->conn = $db_connection;
        $this->config = [
            'chapa_secret_key' => getenv('CHAPA_SECRET_KEY') ?: 'CHASECK_TEST-your-key-here',
            'chapa_public_key' => getenv('CHAPA_PUBLIC_KEY') ?: 'CHAPUBK_TEST-your-key-here',
            'stripe_secret_key' => getenv('STRIPE_SECRET_KEY') ?: 'sk_test_your-key-here',
            'stripe_public_key' => getenv('STRIPE_PUBLIC_KEY') ?: 'pk_test_your-key-here',
        ];
    }
    
    /**
     * Initialize payment based on method
     */
    public function initializePayment($order_id, $amount, $payment_method, $customer_data) {
        switch ($payment_method) {
            case 'cash':
                return $this->processCashPayment($order_id);
                
            case 'card':
                return $this->initializeCardPayment($order_id, $amount, $customer_data);
                
            case 'mobile_money':
                return $this->initializeMobileMoneyPayment($order_id, $amount, $customer_data);
                
            case 'wallet':
                return $this->processWalletPayment($order_id, $amount, $customer_data['user_id']);
                
            default:
                return ['success' => false, 'message' => 'Invalid payment method'];
        }
    }
    
    /**
     * Process cash payment (no gateway needed)
     */
    private function processCashPayment($order_id) {
        // Cash payments are marked as pending until delivery
        $update_sql = "UPDATE orders SET payment_status = 'pending' WHERE id = ?";
        $stmt = mysqli_prepare($this->conn, $update_sql);
        mysqli_stmt_bind_param($stmt, "i", $order_id);
        mysqli_stmt_execute($stmt);
        
        return [
            'success' => true,
            'message' => 'Cash payment will be collected on delivery',
            'payment_method' => 'cash',
            'requires_action' => false
        ];
    }
    
    /**
     * Initialize card payment via Stripe
     */
    private function initializeCardPayment($order_id, $amount, $customer_data) {
        // In production, integrate with Stripe API
        // For now, return mock response
        
        $payment_intent_id = 'pi_' . uniqid();
        $client_secret = 'secret_' . uniqid();
        
        // Store payment intent
        $update_sql = "UPDATE transactions SET reference_number = ?, status = 'processing' 
                       WHERE order_id = ?";
        $stmt = mysqli_prepare($this->conn, $update_sql);
        mysqli_stmt_bind_param($stmt, "si", $payment_intent_id, $order_id);
        mysqli_stmt_execute($stmt);
        
        return [
            'success' => true,
            'message' => 'Card payment initialized',
            'payment_method' => 'card',
            'requires_action' => true,
            'client_secret' => $client_secret,
            'public_key' => $this->config['stripe_public_key'],
            'payment_intent_id' => $payment_intent_id
        ];
    }
    
    /**
     * Initialize mobile money payment via Chapa
     */
    private function initializeMobileMoneyPayment($order_id, $amount, $customer_data) {
        // Get order details
        $order_sql = "SELECT order_number FROM orders WHERE id = ?";
        $stmt = mysqli_prepare($this->conn, $order_sql);
        mysqli_stmt_bind_param($stmt, "i", $order_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $order = mysqli_fetch_assoc($result);
        
        // Prepare Chapa payment request
        $tx_ref = 'beu-' . $order['order_number'] . '-' . time();
        
        $chapa_data = [
            'amount' => $amount,
            'currency' => 'ETB',
            'email' => $customer_data['email'] ?? 'customer@beudelivery.com',
            'first_name' => $customer_data['first_name'] ?? 'Customer',
            'last_name' => $customer_data['last_name'] ?? 'User',
            'phone_number' => $customer_data['phone'],
            'tx_ref' => $tx_ref,
            'callback_url' => 'https://yourdomain.com/payment/callback.php',
            'return_url' => 'https://yourdomain.com/user/order_confirmation.php?id=' . $order_id,
            'customization' => [
                'title' => 'BeU Delivery',
                'description' => 'Payment for order ' . $order['order_number']
            ]
        ];
        
        // In production, make actual API call to Chapa
        // $response = $this->callChapaAPI($chapa_data);
        
        // Mock response for development
        $checkout_url = 'https://checkout.chapa.co/' . uniqid();
        
        // Update transaction with reference
        $update_sql = "UPDATE transactions SET reference_number = ?, status = 'processing' 
                       WHERE order_id = ?";
        $stmt = mysqli_prepare($this->conn, $update_sql);
        mysqli_stmt_bind_param($stmt, "si", $tx_ref, $order_id);
        mysqli_stmt_execute($stmt);
        
        return [
            'success' => true,
            'message' => 'Mobile money payment initialized',
            'payment_method' => 'mobile_money',
            'requires_action' => true,
            'checkout_url' => $checkout_url,
            'tx_ref' => $tx_ref
        ];
    }
    
    /**
     * Process wallet payment
     */
    private function processWalletPayment($order_id, $amount, $user_id) {
        // Check wallet balance (implement wallet system)
        // For now, return not implemented
        
        return [
            'success' => false,
            'message' => 'Wallet payment not yet implemented',
            'payment_method' => 'wallet'
        ];
    }
    
    /**
     * Verify payment status
     */
    public function verifyPayment($order_id, $reference_number) {
        // Get transaction details
        $trans_sql = "SELECT * FROM transactions WHERE order_id = ? AND reference_number = ?";
        $stmt = mysqli_prepare($this->conn, $trans_sql);
        mysqli_stmt_bind_param($stmt, "is", $order_id, $reference_number);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $transaction = mysqli_fetch_assoc($result);
        
        if (!$transaction) {
            return ['success' => false, 'message' => 'Transaction not found'];
        }
        
        // In production, verify with payment gateway
        // For now, mark as completed
        
        $this->completePayment($order_id, $reference_number);
        
        return [
            'success' => true,
            'message' => 'Payment verified successfully',
            'status' => 'completed'
        ];
    }
    
    /**
     * Complete payment and update order
     */
    public function completePayment($order_id, $reference_number) {
        mysqli_begin_transaction($this->conn);
        
        try {
            // Update transaction status
            $trans_sql = "UPDATE transactions SET status = 'completed', updated_at = NOW() 
                          WHERE order_id = ? AND reference_number = ?";
            $stmt = mysqli_prepare($this->conn, $trans_sql);
            mysqli_stmt_bind_param($stmt, "is", $order_id, $reference_number);
            mysqli_stmt_execute($stmt);
            
            // Update order payment status
            $order_sql = "UPDATE orders SET payment_status = 'paid', updated_at = NOW() 
                          WHERE id = ?";
            $stmt = mysqli_prepare($this->conn, $order_sql);
            mysqli_stmt_bind_param($stmt, "i", $order_id);
            mysqli_stmt_execute($stmt);
            
            // Add tracking entry
            $tracking_sql = "INSERT INTO order_tracking (order_id, status, message)
                             VALUES (?, 'confirmed', 'Payment received successfully')";
            $stmt = mysqli_prepare($this->conn, $tracking_sql);
            mysqli_stmt_bind_param($stmt, "i", $order_id);
            mysqli_stmt_execute($stmt);
            
            mysqli_commit($this->conn);
            
            return true;
        } catch (Exception $e) {
            mysqli_rollback($this->conn);
            return false;
        }
    }
    
    /**
     * Process refund
     */
    public function processRefund($order_id, $amount, $reason) {
        mysqli_begin_transaction($this->conn);
        
        try {
            // Get order details
            $order_sql = "SELECT * FROM orders WHERE id = ?";
            $stmt = mysqli_prepare($this->conn, $order_sql);
            mysqli_stmt_bind_param($stmt, "i", $order_id);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $order = mysqli_fetch_assoc($result);
            
            if (!$order) {
                throw new Exception('Order not found');
            }
            
            // Create refund transaction
            $refund_sql = "INSERT INTO transactions (order_id, user_id, merchant_id, amount, transaction_type, status, reference_number)
                           VALUES (?, ?, ?, ?, 'refund', 'completed', ?)";
            $stmt = mysqli_prepare($this->conn, $refund_sql);
            $refund_ref = 'REFUND-' . uniqid();
            mysqli_stmt_bind_param($stmt, "iiids", 
                $order_id, $order['user_id'], $order['merchant_id'], $amount, $refund_ref
            );
            mysqli_stmt_execute($stmt);
            
            // Update order status
            $update_sql = "UPDATE orders SET payment_status = 'refunded', status = 'refunded' WHERE id = ?";
            $stmt = mysqli_prepare($this->conn, $update_sql);
            mysqli_stmt_bind_param($stmt, "i", $order_id);
            mysqli_stmt_execute($stmt);
            
            // Add tracking
            $tracking_sql = "INSERT INTO order_tracking (order_id, status, message)
                             VALUES (?, 'refunded', ?)";
            $stmt = mysqli_prepare($this->conn, $tracking_sql);
            $tracking_message = "Refund processed: $reason";
            mysqli_stmt_bind_param($stmt, "is", $order_id, $tracking_message);
            mysqli_stmt_execute($stmt);
            
            mysqli_commit($this->conn);
            
            return [
                'success' => true,
                'message' => 'Refund processed successfully',
                'refund_reference' => $refund_ref
            ];
            
        } catch (Exception $e) {
            mysqli_rollback($this->conn);
            return [
                'success' => false,
                'message' => 'Refund failed: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Call Chapa API (for production use)
     */
    private function callChapaAPI($data) {
        $url = 'https://api.chapa.co/v1/transaction/initialize';
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $this->config['chapa_secret_key'],
            'Content-Type: application/json'
        ]);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        return json_decode($response, true);
    }
}
?>
