<?php
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$user_id = $_SESSION['user_id'];
$transaction_id = isset($_GET['id']) ? sanitize_input($_GET['id']) : '';

if (empty($transaction_id)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid transaction ID']);
    exit();
}

// Get transaction details
$query = "SELECT * FROM transactions WHERE transaction_id = '$transaction_id' AND user_id = $user_id";
$result = mysqli_query($conn, $query);

if (mysqli_num_rows($result) === 0) {
    http_response_code(404);
    echo json_encode(['error' => 'Transaction not found']);
    exit();
}

$transaction = mysqli_fetch_assoc($result);

// Prepare response
$response = [
    'id' => (int)$transaction['id'],
    'transaction_id' => $transaction['transaction_id'],
    'type' => $transaction['type'],
    'amount' => (float)$transaction['amount'],
    'balance_before' => (float)$transaction['balance_before'],
    'balance_after' => (float)$transaction['balance_after'],
    'description' => $transaction['description'],
    'payment_method' => $transaction['payment_method'],
    'status' => $transaction['status'],
    'created_at' => $transaction['created_at']
];

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response);
?>