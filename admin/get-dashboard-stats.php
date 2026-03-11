<?php
// FILE: admin/get-dashboard-stats.php
require_once '../config.php';

header('Content-Type: application/json');

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

// Get statistics
$stats = [];

// Total Users
$total_users_query = mysqli_query($conn, "SELECT COUNT(*) as count FROM users WHERE user_role != 'admin'");
$stats['total_users'] = mysqli_fetch_assoc($total_users_query)['count'];

// Total Orders
$total_orders_query = mysqli_query($conn, "SELECT COUNT(*) as count FROM orders");
$stats['total_orders'] = mysqli_fetch_assoc($total_orders_query)['count'];

// Pending Orders
$pending_orders_query = mysqli_query($conn, "SELECT COUNT(*) as count FROM orders WHERE status = 'pending'");
$stats['pending_orders'] = mysqli_fetch_assoc($pending_orders_query)['count'];

// Total Revenue
$total_revenue_query = mysqli_query($conn, "SELECT SUM(amount) as total FROM transactions WHERE type = 'deposit' AND status = 'completed'");
$stats['total_revenue'] = mysqli_fetch_assoc($total_revenue_query)['total'] ?? 0;

// Today's Revenue
$today_revenue_query = mysqli_query($conn, "SELECT SUM(amount) as total FROM transactions WHERE type = 'deposit' AND status = 'completed' AND DATE(created_at) = CURDATE()");
$stats['today_revenue'] = mysqli_fetch_assoc($today_revenue_query)['total'] ?? 0;

// Open Tickets
$open_tickets_query = mysqli_query($conn, "SELECT COUNT(*) as count FROM support_tickets WHERE status != 'closed'");
$stats['open_tickets'] = mysqli_fetch_assoc($open_tickets_query)['count'];

echo json_encode($stats);
?>

---

<?php
// FILE: admin/process-order.php
require_once '../config.php';

header('Content-Type: application/json');

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
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

$order_id = isset($_POST['order_id']) ? (int)$_POST['order_id'] : 0;

if ($order_id <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid order ID']);
    exit();
}

// Get order details
$order_query = mysqli_query($conn, "SELECT * FROM orders WHERE id = $order_id");

if (mysqli_num_rows($order_query) === 0) {
    http_response_code(404);
    echo json_encode(['error' => 'Order not found']);
    exit();
}

$order = mysqli_fetch_assoc($order_query);

// Check if order is pending
if ($order['status'] !== 'pending') {
    http_response_code(400);
    echo json_encode(['error' => 'Only pending orders can be processed']);
    exit();
}

// Update order status to processing
$update_order = "UPDATE orders SET status = 'processing', updated_at = NOW() WHERE id = $order_id";

if (mysqli_query($conn, $update_order)) {
    // Create notification for user
    $user_id = $order['user_id'];
    $notif_title = "Order Processing";
    $notif_message = "Your order #{$order['order_number']} is now being processed.";
    mysqli_query($conn, "INSERT INTO notifications (user_id, title, message, type, link) 
                       VALUES ($user_id, '$notif_title', '$notif_message', 'info', '../orders.php?id=$order_id')");
    
    // Log activity
    $admin_id = $_SESSION['user_id'];
    $ip_address = $_SERVER['REMOTE_ADDR'];
    mysqli_query($conn, "INSERT INTO activity_logs (user_id, action, description, ip_address) 
                       VALUES ($admin_id, 'Order Processed', 'Order #{$order['order_number']} set to processing', '$ip_address')");
    
    echo json_encode(['success' => true, 'message' => 'Order processed successfully']);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to process order']);
}
?>

---

<?php
// FILE: admin/quick-stats.php
// Quick stats for real-time updates
require_once '../config.php';

header('Content-Type: application/json');

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$stats = [
    'pending_orders' => 0,
    'open_tickets' => 0,
    'new_users_today' => 0,
    'revenue_today' => 0
];

// Pending Orders
$pending_query = mysqli_query($conn, "SELECT COUNT(*) as count FROM orders WHERE status = 'pending'");
$stats['pending_orders'] = mysqli_fetch_assoc($pending_query)['count'];

// Open Tickets
$tickets_query = mysqli_query($conn, "SELECT COUNT(*) as count FROM support_tickets WHERE status IN ('open', 'answered')");
$stats['open_tickets'] = mysqli_fetch_assoc($tickets_query)['count'];

// New Users Today
$users_query = mysqli_query($conn, "SELECT COUNT(*) as count FROM users WHERE DATE(created_at) = CURDATE() AND user_role != 'admin'");
$stats['new_users_today'] = mysqli_fetch_assoc($users_query)['count'];

// Revenue Today
$revenue_query = mysqli_query($conn, "SELECT SUM(amount) as total FROM transactions WHERE type = 'deposit' AND status = 'completed' AND DATE(created_at) = CURDATE()");
$stats['revenue_today'] = mysqli_fetch_assoc($revenue_query)['total'] ?? 0;

echo json_encode($stats);
?>

---

<?php
// FILE: admin/update-order-status.php
require_once '../config.php';

header('Content-Type: application/json');

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
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

$order_id = isset($_POST['order_id']) ? (int)$_POST['order_id'] : 0;
$status = isset($_POST['status']) ? sanitize_input($_POST['status']) : '';

$allowed_statuses = ['pending', 'processing', 'completed', 'partial', 'cancelled', 'refunded'];

if (!in_array($status, $allowed_statuses)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid status']);
    exit();
}

// Get order
$order_query = mysqli_query($conn, "SELECT * FROM orders WHERE id = $order_id");

if (mysqli_num_rows($order_query) === 0) {
    http_response_code(404);
    echo json_encode(['error' => 'Order not found']);
    exit();
}

$order = mysqli_fetch_assoc($order_query);

// Update order status
$update_query = "UPDATE orders SET status = '$status', updated_at = NOW() WHERE id = $order_id";

if (mysqli_query($conn, $update_query)) {
    // If status is completed, update remains to 0
    if ($status === 'completed') {
        mysqli_query($conn, "UPDATE orders SET remains = 0 WHERE id = $order_id");
    }
    
    // Create notification for user
    $user_id = $order['user_id'];
    $status_messages = [
        'pending' => 'Your order is pending',
        'processing' => 'Your order is being processed',
        'completed' => 'Your order has been completed',
        'partial' => 'Your order was partially completed',
        'cancelled' => 'Your order has been cancelled',
        'refunded' => 'Your order has been refunded'
    ];
    
    $notif_title = "Order Status Updated";
    $notif_message = $status_messages[$status] . " - Order #{$order['order_number']}";
    $notif_type = ($status === 'completed') ? 'success' : (($status === 'cancelled' || $status === 'refunded') ? 'warning' : 'info');
    
    mysqli_query($conn, "INSERT INTO notifications (user_id, title, message, type, link) 
                       VALUES ($user_id, '$notif_title', '$notif_message', '$notif_type', '../orders.php?id=$order_id')");
    
    // Log activity
    $admin_id = $_SESSION['user_id'];
    $ip_address = $_SERVER['REMOTE_ADDR'];
    mysqli_query($conn, "INSERT INTO activity_logs (user_id, action, description, ip_address) 
                       VALUES ($admin_id, 'Order Status Updated', 'Order #{$order['order_number']} status changed to $status', '$ip_address')");
    
    echo json_encode(['success' => true, 'message' => 'Order status updated successfully']);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to update order status']);
}
?>