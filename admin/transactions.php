<?php
require_once '../config.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

$admin_id = $_SESSION['user_id'];
$errors = [];
$success = '';

// Get admin data
$admin_query = mysqli_query($conn, "SELECT * FROM users WHERE id = $admin_id");
$admin = mysqli_fetch_assoc($admin_query);

// Handle transaction actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Approve/Reject Payment
    if (isset($_POST['update_transaction_status'])) {
        $transaction_id = sanitize_input($_POST['transaction_id']);
        $new_status = sanitize_input($_POST['status']);
        
        // Get transaction details
        $txn_query = mysqli_query($conn, "SELECT * FROM transactions WHERE transaction_id = '$transaction_id'");
        
        if (mysqli_num_rows($txn_query) > 0) {
            $transaction = mysqli_fetch_assoc($txn_query);
            $user_id = $transaction['user_id'];
            $amount = $transaction['amount'];
            
            // Get user balance
            $user_query = mysqli_query($conn, "SELECT balance FROM users WHERE id = $user_id");
            $user = mysqli_fetch_assoc($user_query);
            $current_balance = $user['balance'];
            
            if ($new_status === 'completed' && $transaction['status'] === 'pending' && $transaction['type'] === 'deposit') {
                // Approve deposit
                $new_balance = $current_balance + $amount;
                
                // Update user balance
                mysqli_query($conn, "UPDATE users SET balance = $new_balance WHERE id = $user_id");
                
                // Update transaction
                mysqli_query($conn, "UPDATE transactions SET status = 'completed', balance_after = $new_balance, updated_at = NOW() WHERE transaction_id = '$transaction_id'");
                
                // Create notification
                mysqli_query($conn, "INSERT INTO notifications (user_id, title, message, type) 
                                   VALUES ($user_id, 'Payment Approved', 'Your deposit of $$amount has been approved and added to your account.', 'success')");
                
                $success = "Transaction approved and funds added to user account!";
                
            } elseif ($new_status === 'failed' && $transaction['status'] === 'pending') {
                // Reject transaction
                mysqli_query($conn, "UPDATE transactions SET status = 'failed', updated_at = NOW() WHERE transaction_id = '$transaction_id'");
                
                // Create notification
                mysqli_query($conn, "INSERT INTO notifications (user_id, title, message, type) 
                                   VALUES ($user_id, 'Payment Rejected', 'Your payment of $$amount has been rejected. Please contact support.', 'warning')");
                
                $success = "Transaction rejected!";
            }
            
            // Log activity
            $ip_address = $_SERVER['REMOTE_ADDR'];
            mysqli_query($conn, "INSERT INTO activity_logs (user_id, action, description, ip_address) 
                               VALUES ($admin_id, 'Transaction Updated', 'Updated transaction $transaction_id to $new_status', '$ip_address')");
        }
    }
}

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Filters
$type_filter = isset($_GET['type']) ? sanitize_input($_GET['type']) : '';
$status_filter = isset($_GET['status']) ? sanitize_input($_GET['status']) : '';
$user_filter = isset($_GET['user']) ? sanitize_input($_GET['user']) : '';
$date_from = isset($_GET['date_from']) ? sanitize_input($_GET['date_from']) : '';
$date_to = isset($_GET['date_to']) ? sanitize_input($_GET['date_to']) : '';
$search = isset($_GET['search']) ? sanitize_input($_GET['search']) : '';

$where_clauses = ['1=1'];

if ($type_filter) {
    $where_clauses[] = "t.type = '$type_filter'";
}

if ($status_filter) {
    $where_clauses[] = "t.status = '$status_filter'";
}

if ($user_filter) {
    $where_clauses[] = "t.user_id = '$user_filter'";
}

if ($date_from && $date_to) {
    $where_clauses[] = "DATE(t.created_at) BETWEEN '$date_from' AND '$date_to'";
} elseif ($date_from) {
    $where_clauses[] = "DATE(t.created_at) >= '$date_from'";
} elseif ($date_to) {
    $where_clauses[] = "DATE(t.created_at) <= '$date_to'";
}

if ($search) {
    $where_clauses[] = "(t.transaction_id LIKE '%$search%' OR u.username LIKE '%$search%' OR t.description LIKE '%$search%')";
}

$where_sql = implode(' AND ', $where_clauses);

// Count total transactions
$count_query = mysqli_query($conn, "
    SELECT COUNT(*) as total 
    FROM transactions t
    LEFT JOIN users u ON t.user_id = u.id
    WHERE $where_sql
");
$total_transactions = mysqli_fetch_assoc($count_query)['total'];
$total_pages = ceil($total_transactions / $per_page);

// Get transactions
$transactions_query = mysqli_query($conn, "
    SELECT 
        t.*,
        u.username,
        u.email
    FROM transactions t
    LEFT JOIN users u ON t.user_id = u.id
    WHERE $where_sql
    ORDER BY t.created_at DESC
    LIMIT $per_page OFFSET $offset
");

// Get statistics
$stats_query = mysqli_query($conn, "
    SELECT 
        SUM(CASE WHEN type = 'deposit' AND status = 'completed' THEN amount ELSE 0 END) as total_deposits,
        SUM(CASE WHEN type = 'order' AND status = 'completed' THEN amount ELSE 0 END) as total_orders,
        SUM(CASE WHEN type = 'refund' AND status = 'completed' THEN amount ELSE 0 END) as total_refunds,
        COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_count,
        COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_count,
        COUNT(CASE WHEN status = 'failed' THEN 1 END) as failed_count,
        SUM(CASE WHEN DATE(created_at) = CURDATE() AND type = 'deposit' AND status = 'completed' THEN amount ELSE 0 END) as today_revenue
    FROM transactions
");
$stats = mysqli_fetch_assoc($stats_query);

// Get unread notifications
$unread_notifications = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM notifications WHERE user_id = $admin_id AND is_read = 0"));

// Get pending orders
$pending_orders = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM orders WHERE status = 'pending'"));

// Get open tickets
$open_tickets = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM support_tickets WHERE status != 'closed'"));

$page_title = "Transactions Management";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - SMM Panel Admin</title>
    <link rel="stylesheet" href="../assets/css/styles.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../assets/css/dashboard.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../assets/css/admin-dashboard.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../assets/css/admin-transactions.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="dashboard-body admin-body">
    
    <!-- Admin Sidebar -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <div class="logo">
                <i class="fas fa-rocket"></i>
                <span>SMM Panel Admin</span>
            </div>
            <button class="sidebar-close" id="sidebarClose">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <div class="sidebar-user">
            <div class="user-avatar admin-avatar">
                <i class="fas fa-user-shield"></i>
            </div>
            <div class="user-info">
                <h4><?php echo htmlspecialchars($admin['full_name']); ?></h4>
                <p><?php echo htmlspecialchars($admin['email']); ?></p>
                <span class="user-badge admin-badge">Administrator</span>
            </div>
        </div>

        <nav class="sidebar-nav">
            <a href="dashboard.php" class="nav-item">
                <i class="fas fa-tachometer-alt"></i>
                <span>Dashboard</span>
            </a>
            <a href="users.php" class="nav-item">
                <i class="fas fa-users"></i>
                <span>Users Management</span>
            </a>
            <a href="orders.php" class="nav-item">
                <i class="fas fa-shopping-cart"></i>
                <span>Orders</span>
                <?php if ($pending_orders > 0): ?>
                    <span class="badge"><?php echo $pending_orders; ?></span>
                <?php endif; ?>
            </a>
            <a href="services.php" class="nav-item">
                <i class="fas fa-list"></i>
                <span>Services</span>
            </a>
            <a href="transactions.php" class="nav-item active">
                <i class="fas fa-exchange-alt"></i>
                <span>Transactions</span>
            </a>
            <a href="tickets.php" class="nav-item">
                <i class="fas fa-ticket-alt"></i>
                <span>Support Tickets</span>
                <?php if ($open_tickets > 0): ?>
                    <span class="badge"><?php echo $open_tickets; ?></span>
                <?php endif; ?>
            </a>
            <a href="payments.php" class="nav-item">
                <i class="fas fa-credit-card"></i>
                <span>Payments</span>
            </a>
            <a href="reports.php" class="nav-item">
                <i class="fas fa-chart-bar"></i>
                <span>Reports</span>
            </a>
            <a href="settings.php" class="nav-item">
                <i class="fas fa-cog"></i>
                <span>Settings</span>
            </a>
            <a href="../logout.php" class="nav-item logout-item">
                <i class="fas fa-sign-out-alt"></i>
                <span>Logout</span>
            </a>
        </nav>
    </aside>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Top Header -->
        <header class="top-header admin-header">
            <button class="menu-toggle" id="menuToggle">
                <i class="fas fa-bars"></i>
            </button>
            
            <div class="header-title">
                <h1>Transactions Management</h1>
                <p>Monitor all financial transactions</p>
            </div>

            <div class="header-actions">
                <button class="btn-refresh" onclick="location.reload()">
                    <i class="fas fa-sync-alt"></i> Refresh
                </button>

                <div class="notification-icon">
                    <i class="fas fa-bell"></i>
                    <?php if ($unread_notifications > 0): ?>
                        <span class="notification-badge"><?php echo $unread_notifications; ?></span>
                    <?php endif; ?>
                </div>

                <div class="user-menu">
                    <div class="user-avatar-fallback admin-avatar-small">
                        <i class="fas fa-user-shield"></i>
                    </div>
                </div>
            </div>
        </header>

        <!-- Transactions Content -->
        <div class="dashboard-content">
            
            <?php if (!empty($errors)): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <div>
                        <strong>Error!</strong>
                        <ul>
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo $error; ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <div>
                        <strong>Success!</strong>
                        <p><?php echo $success; ?></p>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Transaction Statistics -->
            <div class="transactions-stats-grid">
                <div class="stat-card green">
                    <div class="stat-icon">
                        <i class="fas fa-arrow-down"></i>
                    </div>
                    <div class="stat-details">
                        <h3>$<?php echo number_format($stats['total_deposits'], 2); ?></h3>
                        <p>Total Deposits</p>
                    </div>
                </div>

                <div class="stat-card red">
                    <div class="stat-icon">
                        <i class="fas fa-arrow-up"></i>
                    </div>
                    <div class="stat-details">
                        <h3>$<?php echo number_format($stats['total_orders'], 2); ?></h3>
                        <p>Total Spending</p>
                    </div>
                </div>

                <div class="stat-card blue">
                    <div class="stat-icon">
                        <i class="fas fa-undo"></i>
                    </div>
                    <div class="stat-details">
                        <h3>$<?php echo number_format($stats['total_refunds'], 2); ?></h3>
                        <p>Total Refunds</p>
                    </div>
                </div>

                <div class="stat-card orange">
                    <div class="stat-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-details">
                        <h3><?php echo number_format($stats['pending_count']); ?></h3>
                        <p>Pending</p>
                    </div>
                </div>

                <div class="stat-card purple">
                    <div class="stat-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-details">
                        <h3><?php echo number_format($stats['completed_count']); ?></h3>
                        <p>Completed</p>
                    </div>
                </div>

                <div class="stat-card teal">
                    <div class="stat-icon">
                        <i class="fas fa-calendar-day"></i>
                    </div>
                    <div class="stat-details">
                        <h3>$<?php echo number_format($stats['today_revenue'], 2); ?></h3>
                        <p>Today's Revenue</p>
                    </div>
                </div>
            </div>

            <!-- Filters and Search -->
            <div class="transactions-toolbar">
                <div class="filters-group">
                    <div class="filter-item">
                        <label><i class="fas fa-filter"></i> Type:</label>
                        <select id="typeFilter" class="filter-select" onchange="applyFilters()">
                            <option value="">All Types</option>
                            <option value="deposit" <?php echo $type_filter === 'deposit' ? 'selected' : ''; ?>>Deposits</option>
                            <option value="order" <?php echo $type_filter === 'order' ? 'selected' : ''; ?>>Orders</option>
                            <option value="refund" <?php echo $type_filter === 'refund' ? 'selected' : ''; ?>>Refunds</option>
                            <option value="bonus" <?php echo $type_filter === 'bonus' ? 'selected' : ''; ?>>Bonuses</option>
                        </select>
                    </div>

                    <div class="filter-item">
                        <label><i class="fas fa-toggle-on"></i> Status:</label>
                        <select id="statusFilter" class="filter-select" onchange="applyFilters()">
                            <option value="">All Status</option>
                            <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="completed" <?php echo $status_filter === 'completed' ? 'selected' : ''; ?>>Completed</option>
                            <option value="failed" <?php echo $status_filter === 'failed' ? 'selected' : ''; ?>>Failed</option>
                        </select>
                    </div>

                    <div class="filter-item date-filter">
                        <label><i class="fas fa-calendar"></i> From:</label>
                        <input type="date" id="dateFrom" class="filter-input" value="<?php echo htmlspecialchars($date_from); ?>" onchange="applyFilters()">
                    </div>

                    <div class="filter-item date-filter">
                        <label><i class="fas fa-calendar"></i> To:</label>
                        <input type="date" id="dateTo" class="filter-input" value="<?php echo htmlspecialchars($date_to); ?>" onchange="applyFilters()">
                    </div>
                </div>

                <div class="search-actions">
                    <div class="search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" 
                               id="searchInput" 
                               placeholder="Search transactions..." 
                               value="<?php echo htmlspecialchars($search); ?>"
                               onkeyup="handleSearch(event)">
                    </div>
                    <button class="btn-export" onclick="exportTransactions()">
                        <i class="fas fa-download"></i> Export
                    </button>
                </div>
            </div>

            <!-- Transactions Table -->
            <div class="transactions-table-card">
                <?php if ($total_transactions > 0): ?>
                    <div class="table-responsive">
                        <table class="transactions-table">
                            <thead>
                                <tr>
                                    <th>Transaction ID</th>
                                    <th>User</th>
                                    <th>Type</th>
                                    <th>Description</th>
                                    <th>Payment Method</th>
                                    <th>Amount</th>
                                    <th>Balance After</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($txn = mysqli_fetch_assoc($transactions_query)): ?>
                                    <tr>
                                        <td><strong class="transaction-id"><?php echo htmlspecialchars($txn['transaction_id']); ?></strong></td>
                                        <td>
                                            <div class="user-cell">
                                                <div class="user-avatar-tiny">
                                                    <i class="fas fa-user"></i>
                                                </div>
                                                <div>
                                                    <strong><?php echo htmlspecialchars($txn['username']); ?></strong>
                                                    <small><?php echo htmlspecialchars($txn['email']); ?></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <?php
                                            $type_icons = [
                                                'deposit' => ['icon' => 'fa-arrow-down', 'color' => 'success'],
                                                'order' => ['icon' => 'fa-shopping-cart', 'color' => 'danger'],
                                                'refund' => ['icon' => 'fa-undo', 'color' => 'info'],
                                                'bonus' => ['icon' => 'fa-gift', 'color' => 'warning']
                                            ];
                                            $type_info = $type_icons[$txn['type']] ?? ['icon' => 'fa-exchange-alt', 'color' => 'secondary'];
                                            ?>
                                            <span class="type-badge <?php echo $type_info['color']; ?>">
                                                <i class="fas <?php echo $type_info['icon']; ?>"></i>
                                                <?php echo ucfirst($txn['type']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="description-cell">
                                                <?php echo htmlspecialchars($txn['description']); ?>
                                            </div>
                                        </td>
                                        <td>
                                            <?php if ($txn['payment_method']): ?>
                                                <span class="payment-method">
                                                    <i class="fas fa-credit-card"></i>
                                                    <?php echo ucfirst(str_replace('_', ' ', $txn['payment_method'])); ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php
                                            $amount_class = '';
                                            $amount_prefix = '';
                                            if ($txn['type'] === 'deposit' || $txn['type'] === 'refund' || $txn['type'] === 'bonus') {
                                                $amount_class = 'amount-positive';
                                                $amount_prefix = '+';
                                            } elseif ($txn['type'] === 'order') {
                                                $amount_class = 'amount-negative';
                                                $amount_prefix = '-';
                                            }
                                            ?>
                                            <strong class="transaction-amount <?php echo $amount_class; ?>">
                                                <?php echo $amount_prefix; ?>$<?php echo number_format($txn['amount'], 2); ?>
                                            </strong>
                                        </td>
                                        <td>
                                            <span class="balance-after">$<?php echo number_format($txn['balance_after'], 2); ?></span>
                                        </td>
                                        <td>
                                            <?php
                                            $status_classes = [
                                                'completed' => 'success',
                                                'pending' => 'warning',
                                                'failed' => 'danger'
                                            ];
                                            $s_class = $status_classes[$txn['status']] ?? 'secondary';
                                            ?>
                                            <span class="status-badge <?php echo $s_class; ?>">
                                                <i class="fas fa-circle"></i>
                                                <?php echo ucfirst($txn['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="date-cell">
                                                <span><?php echo date('M d, Y', strtotime($txn['created_at'])); ?></span>
                                                <small><?php echo date('h:i A', strtotime($txn['created_at'])); ?></small>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <button class="btn-action view" onclick="viewTransaction('<?php echo htmlspecialchars($txn['transaction_id']); ?>')" title="View Details">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <?php if ($txn['status'] === 'pending' && $txn['type'] === 'deposit'): ?>
                                                    <button class="btn-action success" onclick="approveTransaction('<?php echo htmlspecialchars($txn['transaction_id']); ?>')" title="Approve">
                                                        <i class="fas fa-check"></i>
                                                    </button>
                                                    <button class="btn-action danger" onclick="rejectTransaction('<?php echo htmlspecialchars($txn['transaction_id']); ?>')" title="Reject">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                        <div class="pagination">
                            <?php if ($page > 1): ?>
                                <a href="?page=<?php echo $page - 1; ?><?php echo $type_filter ? '&type=' . $type_filter : ''; ?><?php echo $status_filter ? '&status=' . $status_filter : ''; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>" class="page-link">
                                    <i class="fas fa-chevron-left"></i> Previous
                                </a>
                            <?php endif; ?>

                            <div class="page-numbers">
                                <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                    <a href="?page=<?php echo $i; ?><?php echo $type_filter ? '&type=' . $type_filter : ''; ?><?php echo $status_filter ? '&status=' . $status_filter : ''; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>" 
                                       class="page-link <?php echo $i === $page ? 'active' : ''; ?>">
                                        <?php echo $i; ?>
                                    </a>
                                <?php endfor; ?>
                            </div>

                            <?php if ($page < $total_pages): ?>
                                <a href="?page=<?php echo $page + 1; ?><?php echo $type_filter ? '&type=' . $type_filter : ''; ?><?php echo $status_filter ? '&status=' . $status_filter : ''; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>" class="page-link">
                                    Next <i class="fas fa-chevron-right"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                <?php else: ?>
                    <div class="empty-transactions">
                        <i class="fas fa-receipt"></i>
                        <h3>No Transactions Found</h3>
                        <p>No transactions match your search criteria.</p>
                        <a href="transactions.php" class="btn-primary">
                            <i class="fas fa-redo"></i> Reset Filters
                        </a>
                    </div>
                <?php endif; ?>
            </div>

        </div>
    </div>

    <!-- View Transaction Modal -->
    <div id="viewTransactionModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-receipt"></i> Transaction Details</h2>
                <button class="modal-close" onclick="closeViewTransactionModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body" id="viewTransactionContent">
                <div class="modal-loading">
                    <i class="fas fa-spinner fa-spin"></i>
                    <p>Loading transaction details...</p>
                </div>
            </div>
        </div>
    </div>

    <script src="../assets/js/dashboard.js"></script>
    <script src="../assets/js/admin-transactions.js"></script>
</body>
</html>