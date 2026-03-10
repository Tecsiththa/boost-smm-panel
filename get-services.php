<?php
require_once 'config.php';

// Ensure session is started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// TEMPORARILY DISABLE AUTH CHECK FOR DEBUGGING
// Check if user is logged in
// if (!isset($_SESSION['user_id'])) {
//     http_response_code(401);
//     header('Content-Type: application/json');
//     echo json_encode(['error' => 'Unauthorized - no session']);
//     exit();
// }

// For now, assume default user role
$user_role = 'user'; // Default role
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

// Get user data to determine pricing (if logged in)
if ($user_id) {
    $user_query = mysqli_query($conn, "SELECT user_role FROM users WHERE id = $user_id");
    if (!$user_query) {
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Database error: ' . mysqli_error($conn)]);
        exit();
    }

    $user = mysqli_fetch_assoc($user_query);
    if (!$user) {
        http_response_code(401);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'User not found']);
        exit();
    }
    $user_role = $user['user_role'];
} else {
    // Default role for non-logged in users (limited access)
    $user_role = 'user';
}

// Get category from query parameter
$category = isset($_GET['category']) ? mysqli_real_escape_string($conn, trim($_GET['category'])) : '';

if (empty($category)) {
    http_response_code(400);
    echo json_encode(['error' => 'Category is required']);
    exit();
}

// Fetch services for the selected category
$query = "SELECT
            id,
            name,
            description,
            min_quantity,
            max_quantity,
            price_per_1000,
            reseller_price_per_1000,
            delivery_time,
            icon
          FROM services
          WHERE category = '$category'
          AND status = 'active'
          ORDER BY name ASC";

$result = mysqli_query($conn, $query);
if (!$result) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Database query error: ' . mysqli_error($conn) . ' Query: ' . $query]);
    exit();
}

$services = [];
while ($row = mysqli_fetch_assoc($result)) {
    // Set price based on user role
    $price = ($user_role === 'reseller')
        ? $row['reseller_price_per_1000']
        : $row['price_per_1000'];

    $services[] = [
        'id' => (int)$row['id'],
        'name' => $row['name'],
        'description' => $row['description'],
        'min_quantity' => (int)$row['min_quantity'],
        'max_quantity' => (int)$row['max_quantity'],
        'price' => (float)$price,
        'delivery_time' => $row['delivery_time'],
        'icon' => $row['icon']
    ];
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($services);