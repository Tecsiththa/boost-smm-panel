<?php
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Redirect admin to admin dashboard
if ($_SESSION['role'] === 'admin') {
    header('Location: admin/dashboard.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Get user data
$user_query = mysqli_query($conn, "SELECT * FROM users WHERE id = $user_id");
$user = mysqli_fetch_assoc($user_query);

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 15;
$offset = ($page - 1) * $per_page;

// Type filter
$type_filter = isset($_GET['type']) ? sanitize_input($_GET['type']) : '';
$type_where = $type_filter ? "AND type = '$type_filter'" : '';

// Status filter
$status_filter = isset($_GET['status']) ? sanitize_input($_GET['status']) : '';
$status_where = $status_filter ? "AND status = '$status_filter'" : '';

// Date range filter
$date_from = isset($_GET['date_from']) ? sanitize_input($_GET['date_from']) : '';
$date_to = isset($_GET['date_to']) ? sanitize_input($_GET['date_to']) : '';
$date_where = '';

if ($date_from && $date_to) {
    $date_where = "AND DATE(created_at) BETWEEN '$date_from' AND '$date_to'";
} elseif ($date_from) {
    $date_where = "AND DATE(created_at) >= '$date_from'";
} elseif ($date_to) {
    $date_where = "AND DATE(created_at) <= '$date_to'";
}

// Search
$search = isset($_GET['search']) ? sanitize_input($_GET['search']) : '';
$search_where = $search ? "AND (transaction_id LIKE '%$search%' OR description LIKE '%$search%')" : '';

// Count total transactions
$count_query = mysqli_query($conn, "
    SELECT COUNT(*) as total 
    FROM transactions 
    WHERE user_id = $user_id 
    $type_where 
    $status_where 
    $date_where 
    $search_where
");
$total_transactions = mysqli_fetch_assoc($count_query)['total'];
$total_pages = ceil($total_transactions / $per_page);

// Get transactions
$transactions_query = mysqli_query($conn, "
    SELECT * FROM transactions 
    WHERE user_id = $user_id 
    $type_where 
    $status_where 
    $date_where 
    $search_where
    ORDER BY created_at DESC 
    LIMIT $per_page OFFSET $offset
");

// Get transaction statistics
$stats_query = mysqli_query($conn, "
    SELECT 
        SUM(CASE WHEN type = 'deposit' AND status = 'completed' THEN amount ELSE 0 END) as total_deposited,
        SUM(CASE WHEN type = 'order' AND status = 'completed' THEN amount ELSE 0 END) as total_spent,
        SUM(CASE WHEN type = 'refund' AND status = 'completed' THEN amount ELSE 0 END) as total_refunded,
        COUNT(CASE WHEN type = 'deposit' THEN 1 END) as deposit_count,
        COUNT(CASE WHEN type = 'order' THEN 1 END) as order_count
    FROM transactions 
    WHERE user_id = $user_id
");
$stats = mysqli_fetch_assoc($stats_query);

// Get notifications count
$unread_notifications = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM notifications WHERE user_id = $user_id AND is_read = 0"));

// Get active tickets count
$active_tickets = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM support_tickets WHERE user_id = $user_id AND status != 'closed'"));

$page_title = "Transactions";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - SMM Panel</title>
    <link rel="stylesheet" href="assets/css/styles.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="assets/css/dashboard.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="assets/css/transactions.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="dashboard-body">
    
    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <div class="logo">
                <i class="fas fa-rocket"></i>
                <span>SMM Panel</span>
            </div>
            <button class="sidebar-close" id="sidebarClose">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <div class="sidebar-user">
            <div class="user-avatar">
                <i class="fas fa-user"></i>
            </div>
            <div class="user-info">
                <h4><?php echo htmlspecialchars($user['full_name']); ?></h4>
                <p><?php echo htmlspecialchars($user['email']); ?></p>
                <span class="user-badge"><?php echo ucfirst($user['user_role']); ?></span>
            </div>
        </div>

        <nav class="sidebar-nav">
            <a href="dashboard.php" class="nav-item">
                <i class="fas fa-home"></i>
                <span>Dashboard</span>
            </a>
            <a href="new-order.php" class="nav-item">
                <i class="fas fa-plus-circle"></i>
                <span>New Order</span>
            </a>
            <a href="orders.php" class="nav-item">
                <i class="fas fa-shopping-cart"></i>
                <span>My Orders</span>
            </a>
            <a href="services.php" class="nav-item">
                <i class="fas fa-list"></i>
                <span>Services</span>
            </a>
            <a href="add-funds.php" class="nav-item">
                <i class="fas fa-wallet"></i>
                <span>Add Funds</span>
            </a>
            <a href="transactions.php" class="nav-item active">
                <i class="fas fa-history"></i>
                <span>Transactions</span>
            </a>
            <a href="support.php" class="nav-item">
                <i class="fas fa-headset"></i>
                <span>Support</span>
                <?php if ($active_tickets > 0): ?>
                    <span class="badge"><?php echo $active_tickets; ?></span>
                <?php endif; ?>
            </a>
            <a href="profile.php" class="nav-item">
                <i class="fas fa-user-cog"></i>
                <span>Profile Settings</span>
            </a>
            <a href="logout.php" class="nav-item logout-item">
                <i class="fas fa-sign-out-alt"></i>
                <span>Logout</span>
            </a>
        </nav>
    </aside>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Top Header -->
        <header class="top-header">
            <button class="menu-toggle" id="menuToggle">
                <i class="fas fa-bars"></i>
            </button>
            
            <div class="header-title">
                <h1>Transaction History</h1>
                <p>View all your payment transactions</p>
            </div>

            <div class="header-actions">
                <div class="balance-display">
                    <i class="fas fa-wallet"></i>
                    <div>
                        <small>Balance</small>
                        <strong>$<?php echo number_format($user['balance'], 2); ?></strong>
                    </div>
                </div>

                <div class="notification-icon">
                    <i class="fas fa-bell"></i>
                    <?php if ($unread_notifications > 0): ?>
                        <span class="notification-badge"><?php echo $unread_notifications; ?></span>
                    <?php endif; ?>
                </div>

                <div class="user-menu">
                    <div class="user-avatar-fallback">
                        <i class="fas fa-user"></i>
                    </div>
                </div>
            </div>
        </header>

        <!-- Transactions Content -->
        <div class="dashboard-content">
            
            <!-- Transaction Statistics -->
            <div class="transaction-stats-grid">
                <div class="stat-card deposits">
                    <div class="stat-icon">
                        <i class="fas fa-arrow-down"></i>
                    </div>
                    <div class="stat-info">
                        <h3>$<?php echo number_format($stats['total_deposited'], 2); ?></h3>
                        <p>Total Deposited</p>
                        <small><?php echo number_format($stats['deposit_count']); ?> transactions</small>
                    </div>
                </div>

                <div class="stat-card spending">
                    <div class="stat-icon">
                        <i class="fas fa-arrow-up"></i>
                    </div>
                    <div class="stat-info">
                        <h3>$<?php echo number_format($stats['total_spent'], 2); ?></h3>
                        <p>Total Spent</p>
                        <small><?php echo number_format($stats['order_count']); ?> orders</small>
                    </div>
                </div>

                <div class="stat-card refunds">
                    <div class="stat-icon">
                        <i class="fas fa-undo"></i>
                    </div>
                    <div class="stat-info">
                        <h3>$<?php echo number_format($stats['total_refunded'], 2); ?></h3>
                        <p>Total Refunded</p>
                        <small>Money returned</small>
                    </div>
                </div>

                <div class="stat-card balance">
                    <div class="stat-icon">
                        <i class="fas fa-wallet"></i>
                    </div>
                    <div class="stat-info">
                        <h3>$<?php echo number_format($user['balance'], 2); ?></h3>
                        <p>Current Balance</p>
                        <small>Available to spend</small>
                    </div>
                </div>
            </div>

            <!-- Filters and Search -->
            <div class="transactions-toolbar">
                <div class="filters-group">
                    <div class="filter-item">
                        <label><i class="fas fa-filter"></i> Type:</label>
                        <select id="typeFilter" class="filter-select">
                            <option value="">All Types</option>
                            <option value="deposit" <?php echo $type_filter === 'deposit' ? 'selected' : ''; ?>>Deposits</option>
                            <option value="order" <?php echo $type_filter === 'order' ? 'selected' : ''; ?>>Orders</option>
                            <option value="refund" <?php echo $type_filter === 'refund' ? 'selected' : ''; ?>>Refunds</option>
                            <option value="bonus" <?php echo $type_filter === 'bonus' ? 'selected' : ''; ?>>Bonuses</option>
                        </select>
                    </div>

                    <div class="filter-item">
                        <label><i class="fas fa-check-circle"></i> Status:</label>
                        <select id="statusFilter" class="filter-select">
                            <option value="">All Status</option>
                            <option value="completed" <?php echo $status_filter === 'completed' ? 'selected' : ''; ?>>Completed</option>
                            <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="failed" <?php echo $status_filter === 'failed' ? 'selected' : ''; ?>>Failed</option>
                        </select>
                    </div>

                    <div class="filter-item date-filter">
                        <label><i class="fas fa-calendar"></i> From:</label>
                        <input type="date" 
                               id="dateFrom" 
                               class="filter-input"
                               value="<?php echo htmlspecialchars($date_from); ?>">
                    </div>

                    <div class="filter-item date-filter">
                        <label><i class="fas fa-calendar"></i> To:</label>
                        <input type="date" 
                               id="dateTo" 
                               class="filter-input"
                               value="<?php echo htmlspecialchars($date_to); ?>">
                    </div>
                </div>

                <div class="search-actions">
                    <div class="search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" 
                               id="searchInput" 
                               placeholder="Search transactions..." 
                               value="<?php echo htmlspecialchars($search); ?>">
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
                                        <td>
                                            <strong class="transaction-id"><?php echo htmlspecialchars($txn['transaction_id']); ?></strong>
                                        </td>
                                        <td>
                                            <?php
                                            $type_icons = [
                                                'deposit' => 'fa-arrow-down',
                                                'order' => 'fa-shopping-cart',
                                                'refund' => 'fa-undo',
                                                'bonus' => 'fa-gift'
                                            ];
                                            $type_colors = [
                                                'deposit' => 'success',
                                                'order' => 'danger',
                                                'refund' => 'info',
                                                'bonus' => 'warning'
                                            ];
                                            $icon = $type_icons[$txn['type']] ?? 'fa-exchange-alt';
                                            $color = $type_colors[$txn['type']] ?? 'secondary';
                                            ?>
                                            <span class="type-badge <?php echo $color; ?>">
                                                <i class="fas <?php echo $icon; ?>"></i>
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
                                            $status_icons = [
                                                'completed' => 'fa-check-circle',
                                                'pending' => 'fa-clock',
                                                'failed' => 'fa-times-circle'
                                            ];
                                            $s_class = $status_classes[$txn['status']] ?? 'secondary';
                                            $s_icon = $status_icons[$txn['status']] ?? 'fa-question-circle';
                                            ?>
                                            <span class="status-badge <?php echo $s_class; ?>">
                                                <i class="fas <?php echo $s_icon; ?>"></i>
                                                <?php echo ucfirst($txn['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="date-cell">
                                                <span class="date"><?php echo date('M d, Y', strtotime($txn['created_at'])); ?></span>
                                                <span class="time"><?php echo date('h:i A', strtotime($txn['created_at'])); ?></span>
                                            </div>
                                        </td>
                                        <td>
                                            <button class="btn-action view" 
                                                    onclick="viewTransaction('<?php echo $txn['transaction_id']; ?>')"
                                                    title="View Details">
                                                <i class="fas fa-eye"></i>
                                            </button>
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
                                <a href="?page=<?php echo $page - 1; ?><?php echo $type_filter ? '&type=' . $type_filter : ''; ?><?php echo $status_filter ? '&status=' . $status_filter : ''; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>" 
                                   class="page-link">
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
                                <a href="?page=<?php echo $page + 1; ?><?php echo $type_filter ? '&type=' . $type_filter : ''; ?><?php echo $status_filter ? '&status=' . $status_filter : ''; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>" 
                                   class="page-link">
                                    Next <i class="fas fa-chevron-right"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                <?php else: ?>
                    <div class="empty-transactions">
                        <i class="fas fa-receipt"></i>
                        <h3>No Transactions Found</h3>
                        <p>You haven't made any transactions yet or no results match your filters.</p>
                        <a href="add-funds.php" class="btn-primary">
                            <i class="fas fa-plus-circle"></i> Add Funds
                        </a>
                    </div>
                <?php endif; ?>
            </div>

        </div>
    </div>

    <!-- Transaction Details Modal -->
    <div id="transactionModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-receipt"></i> Transaction Details</h2>
                <button class="modal-close" onclick="closeTransactionModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body" id="transactionModalBody">
                <div class="modal-loading">
                    <i class="fas fa-spinner fa-spin"></i>
                    <p>Loading transaction details...</p>
                </div>
            </div>
        </div>
    </div>

    <script src="assets/js/dashboard.js"></script>
    <script src="assets/js/transactions.js"></script>
</body>
</html>