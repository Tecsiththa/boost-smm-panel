<?php
require_once '../config.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$service_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($service_id <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid service ID']);
    exit();
}

// Get service details
$query = "SELECT * FROM services WHERE id = $service_id";
$result = mysqli_query($conn, $query);

if (mysqli_num_rows($result) === 0) {
    http_response_code(404);
    echo json_encode(['error' => 'Service not found']);
    exit();
}

$service = mysqli_fetch_assoc($result);

header('Content-Type: application/json');
echo json_encode($service);
?>
