<?php
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

$user_id = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
$amount = isset($_POST['amount']) ? (float)$_POST['amount'] : 0;
$action = isset($_POST['action']) ? sanitize_input($_POST['action']) : ''; // 'add' or 'subtract'

if ($user_id <= 0 || $amount <= 0 || !in_array($action, ['add', 'subtract'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid parameters']);
    exit();
}

// Get current balance
$user_query = mysqli_query($conn, "SELECT balance, username FROM users WHERE id = $user_id");

if (mysqli_num_rows($user_query) === 0) {
    http_response_code(404);
    echo json_encode(['error' => 'User not found']);
    exit();
}

$user = mysqli_fetch_assoc($user_query);
$current_balance = $user['balance'];

// Calculate new balance
if ($action === 'add') {
    $new_balance = $current_balance + $amount;
} else {
    $new_balance = max(0, $current_balance - $amount);
}

// Update balance
$update_query = "UPDATE users SET balance = $new_balance WHERE id = $user_id";

if (mysqli_query($conn, $update_query)) {
    // Create transaction record
    $transaction_id = 'TXN-' . strtoupper(substr(md5(time() . rand()), 0, 12));
    $type = $action === 'add' ? 'bonus' : 'refund'; // Adjust based on enum
    $description = $action === 'add' ? "Admin added funds" : "Admin deducted funds";
    
    mysqli_query($conn, "INSERT INTO transactions (user_id, transaction_id, type, amount, balance_before, balance_after, description, status) 
                       VALUES ($user_id, '$transaction_id', '$type', $amount, $current_balance, $new_balance, '$description', 'completed')");
    
    // Create notification
    $notif_title = $action === 'add' ? "Funds Added" : "Funds Deducted";
    $notif_message = "Admin " . ($action === 'add' ? 'added' : 'deducted') . " $$amount " . ($action === 'add' ? 'to' : 'from') . " your account.";
    mysqli_query($conn, "INSERT INTO notifications (user_id, title, message, type) 
                       VALUES ($user_id, '$notif_title', '$notif_message', 'info')");
    
    // Log activity
    $admin_id = $_SESSION['user_id'];
    $ip_address = $_SERVER['REMOTE_ADDR'];
    mysqli_query($conn, "INSERT INTO activity_logs (user_id, action, description, ip_address) 
                       VALUES ($admin_id, 'Balance Updated', 'Admin " . ($action === 'add' ? 'added' : 'deducted') . " $$amount for user: {$user['username']}', '$ip_address')");
    
    echo json_encode([
        'success' => true,
        'message' => 'Balance updated successfully',
        'new_balance' => $new_balance
    ]);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to update balance']);
}
?>
