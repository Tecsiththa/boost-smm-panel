<?php
require_once 'config.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$user_id = $_SESSION['user_id'];
$is_admin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
$order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($order_id <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid order ID']);
    exit();
}

// Get order details
// If admin, they can see any order. If user, only their own.
$user_condition = $is_admin ? "" : "AND o.user_id = $user_id";

$query = "SELECT 
            o.*, 
            s.name as service_name, 
            s.category,
            s.delivery_time,
            s.icon
          FROM orders o 
          LEFT JOIN services s ON o.service_id = s.id 
          WHERE o.id = $order_id 
          $user_condition";

$result = mysqli_query($conn, $query);

if (mysqli_num_rows($result) === 0) {
    http_response_code(404);
    echo json_encode(['error' => 'Order not found']);
    exit();
}

$order = mysqli_fetch_assoc($result);

// Calculate progress
$progress = 0;
if ($order['quantity'] > 0) {
    $delivered = $order['quantity'] - $order['remains'];
    $progress = round(($delivered / $order['quantity']) * 100);
}

// Prepare response
$response = [
    'id' => (int)$order['id'],
    'order_number' => $order['order_number'],
    'service_name' => $order['service_name'],
    'category' => $order['category'],
    'icon' => $order['icon'],
    'link' => $order['link'],
    'quantity' => (int)$order['quantity'],
    'start_count' => (int)$order['start_count'],
    'remains' => (int)$order['remains'],
    'price' => (float)$order['price'],
    'status' => $order['status'],
    'delivery_time' => $order['delivery_time'],
    'created_at' => $order['created_at'],
    'updated_at' => $order['updated_at'],
    'progress' => $progress
];

// Return JSON response
echo json_encode($response);
?>