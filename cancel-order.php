<?php
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit();
}

$user_id = $_SESSION['user_id'];
$order_id = isset($_POST['order_id']) ? (int)$_POST['order_id'] : 0;

if ($order_id <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid order ID']);
    exit();
}

// Get order details
$order_query = mysqli_query($conn, "
    SELECT * FROM orders 
    WHERE id = $order_id 
    AND user_id = $user_id
");

if (mysqli_num_rows($order_query) === 0) {
    http_response_code(404);
    echo json_encode(['error' => 'Order not found']);
    exit();
}

$order = mysqli_fetch_assoc($order_query);

// Check if order can be cancelled (only pending orders)
if ($order['status'] !== 'pending') {
    http_response_code(400);
    echo json_encode(['error' => 'Only pending orders can be cancelled']);
    exit();
}

// Get user's current balance
$user_query = mysqli_query($conn, "SELECT balance FROM users WHERE id = $user_id");
$user = mysqli_fetch_assoc($user_query);

// Start transaction
mysqli_begin_transaction($conn);

try {
    // Update order status to cancelled
    $update_order = "UPDATE orders SET status = 'cancelled', updated_at = NOW() WHERE id = $order_id";
    if (!mysqli_query($conn, $update_order)) {
        throw new Exception('Failed to update order status');
    }
    
    // Refund the amount to user's balance
    $new_balance = $user['balance'] + $order['price'];
    $update_balance = "UPDATE users SET balance = $new_balance WHERE id = $user_id";
    if (!mysqli_query($conn, $update_balance)) {
        throw new Exception('Failed to update balance');
    }
    
    // Create refund transaction
    $transaction_id = 'TXN-' . strtoupper(substr(md5(time() . rand()), 0, 12));
    $description = "Refund for cancelled order: " . $order['order_number'];
    $insert_transaction = "INSERT INTO transactions (user_id, transaction_id, type, amount, balance_before, balance_after, description, status) 
                          VALUES ($user_id, '$transaction_id', 'refund', {$order['price']}, {$user['balance']}, $new_balance, '$description', 'completed')";
    
    if (!mysqli_query($conn, $insert_transaction)) {
        throw new Exception('Failed to create transaction record');
    }
    
    // Create notification
    $notif_title = "Order Cancelled";
    $notif_message = "Your order #{$order['order_number']} has been cancelled and refunded.";
    $insert_notification = "INSERT INTO notifications (user_id, title, message, type, link) 
                           VALUES ($user_id, '$notif_title', '$notif_message', 'info', 'orders.php?id=$order_id')";
    
    mysqli_query($conn, $insert_notification); // Notification is not critical
    
    // Log activity
    $ip_address = $_SERVER['REMOTE_ADDR'];
    $log_activity = "INSERT INTO activity_logs (user_id, action, description, ip_address) 
                    VALUES ($user_id, 'Order Cancelled', 'Order #{$order['order_number']} cancelled', '$ip_address')";
    
    mysqli_query($conn, $log_activity); // Activity log is not critical
    
    // Commit transaction
    mysqli_commit($conn);
    
    // Update session balance
    $_SESSION['balance'] = $new_balance;
    
    // Return success response
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'message' => 'Order cancelled successfully',
        'new_balance' => $new_balance,
        'refunded_amount' => $order['price']
    ]);
    
} catch (Exception $e) {
    // Rollback transaction on error
    mysqli_rollback($conn);
    
    http_response_code(500);
    echo json_encode(['error' => 'Failed to cancel order: ' . $e->getMessage()]);
}
?>