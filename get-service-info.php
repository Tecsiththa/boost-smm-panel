<?php
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$user_id = $_SESSION['user_id'];
$service_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($service_id <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid service ID']);
    exit();
}

// Get user role
$user_query = mysqli_query($conn, "SELECT role FROM users WHERE id = $user_id");
$user = mysqli_fetch_assoc($user_query);

// Get service details
$query = "SELECT * FROM services WHERE id = $service_id AND status = 'active'";
$result = mysqli_query($conn, $query);

if (mysqli_num_rows($result) === 0) {
    http_response_code(404);
    echo json_encode(['error' => 'Service not found']);
    exit();
}

$service = mysqli_fetch_assoc($result);

// Determine price based on user role
$price = ($user['role'] === 'reseller') 
    ? $service['reseller_price_per_1000'] 
    : $service['price_per_1000'];

// Prepare response
$response = [
    'id' => (int)$service['id'],
    'name' => $service['name'],
    'category' => $service['category'],
    'description' => $service['description'],
    'min_quantity' => (int)$service['min_quantity'],
    'max_quantity' => (int)$service['max_quantity'],
    'price' => (float)$price,
    'delivery_time' => $service['delivery_time'],
    'icon' => $service['icon'],
    'is_reseller' => ($user['role'] === 'reseller')
];

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response);
?>