<?php
require_once '../config.php';

header('Content-Type: application/json');

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$user_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($user_id <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid user ID']);
    exit();
}

// Get user details
$user_query = mysqli_query($conn, "SELECT * FROM users WHERE id = $user_id");

if (mysqli_num_rows($user_query) === 0) {
    http_response_code(404);
    echo json_encode(['error' => 'User not found']);
    exit();
}

$user = mysqli_fetch_assoc($user_query);

// Get user statistics
$orders_query = mysqli_query($conn, "SELECT COUNT(*) as count, SUM(price) as total FROM orders WHERE user_id = $user_id");
$orders_stats = mysqli_fetch_assoc($orders_query);

// Prepare response
$response = [
    'id' => (int)$user['id'],
    'username' => $user['username'],
    'email' => $user['email'],
    'full_name' => $user['full_name'],
    'phone' => $user['phone'],
    'user_role' => $user['user_role'],
    'balance' => (float)$user['balance'],
    'status' => $user['status'],
    'created_at' => $user['created_at'],
    'last_login' => $user['last_login'],
    'ip_address' => $user['ip_address'],
    'total_orders' => (int)$orders_stats['count'],
    'total_spent' => (float)$orders_stats['total']
];

echo json_encode($response);
?>