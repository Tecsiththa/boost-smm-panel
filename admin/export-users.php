<?php
require_once '../config.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

// Get filters
$role_filter = isset($_GET['role']) ? sanitize_input($_GET['role']) : '';
$status_filter = isset($_GET['status']) ? sanitize_input($_GET['status']) : '';
$search = isset($_GET['search']) ? sanitize_input($_GET['search']) : '';

$where_clauses = ["user_role != 'admin'"];

if ($role_filter) {
    $where_clauses[] = "user_role = '$role_filter'";
}

if ($status_filter) {
    $where_clauses[] = "status = '$status_filter'";
}

if ($search) {
    $where_clauses[] = "(username LIKE '%$search%' OR email LIKE '%$search%' OR full_name LIKE '%$search%')";
}

$where_sql = implode(' AND ', $where_clauses);

// Get users
$users_query = mysqli_query($conn, "SELECT * FROM users WHERE $where_sql ORDER BY created_at DESC");

// Set headers for CSV download
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="users_export_' . date('Y-m-d_H-i-s') . '.csv"');

// Create CSV
$output = fopen('php://output', 'w');

// Add headers
fputcsv($output, ['ID', 'Username', 'Full Name', 'Email', 'Phone', 'Role', 'Balance', 'Status', 'Joined Date', 'Last Login', 'IP Address']);

// Add data
while ($user = mysqli_fetch_assoc($users_query)) {
    fputcsv($output, [
        $user['id'],
        $user['username'],
        $user['full_name'],
        $user['email'],
        $user['phone'],
        $user['user_role'],
        $user['balance'],
        $user['status'],
        $user['created_at'],
        $user['last_login'],
        $user['ip_address']
    ]);
}

fclose($output);
exit();
?>
