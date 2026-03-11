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
$per_page = 10;
$offset = ($page - 1) * $per_page;

// Status filter
$status_filter = isset($_GET['status']) ? sanitize_input($_GET['status']) : '';
$status_where = $status_filter ? "AND o.status = '$status_filter'" : '';

// Search
$search = isset($_GET['search']) ? sanitize_input($_GET['search']) : '';
$search_where = $search ? "AND (o.order_number LIKE '%$search%' OR s.name LIKE '%$search%' OR o.link LIKE '%$search%')" : '';

// Count total orders
$count_query = mysqli_query($conn, "
    SELECT COUNT(*) as total 
    FROM orders o 
    LEFT JOIN services s ON o.service_id = s.id 
    WHERE o.user_id = $user_id $status_where $search_where
");
$total_orders = mysqli_fetch_assoc($count_query)['total'];
$total_pages = ceil($total_orders / $per_page);

// Get orders
$orders_query = mysqli_query($conn, "
    SELECT 
        o.*, 
        s.name as service_name, 
        s.category,
        s.icon
    FROM orders o 
    LEFT JOIN services s ON o.service_id = s.id 
    WHERE o.user_id = $user_id 
    $status_where 
    $search_where
    ORDER BY o.created_at DESC 
    LIMIT $per_page OFFSET $offset
");

// Get order statistics
$stats_query = mysqli_query($conn, "
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN status = 'processing' THEN 1 ELSE 0 END) as processing,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
        SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled,
        SUM(price) as total_spent
    FROM orders 
    WHERE user_id = $user_id
");
$stats = mysqli_fetch_assoc($stats_query);

// Get notifications count
$unread_notifications = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM notifications WHERE user_id = $user_id AND is_read = 0"));

// Get active tickets count
$active_tickets = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM support_tickets WHERE user_id = $user_id AND status != 'closed'"));

$page_title = "My Orders";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - SMM Panel</title>
    <link rel="stylesheet" href="assets/css/styles.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="assets/css/dashboard.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="assets/css/orders.css?v=<?php echo time(); ?>">
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
            <a href="orders.php" class="nav-item active">
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
            <a href="transactions.php" class="nav-item">
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
                <h1>My Orders</h1>
                <p>Track and manage all your orders</p>
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

        <!-- Orders Content -->
        <div class="dashboard-content">
            
            <!-- Order Statistics -->
            <div class="order-stats-grid">
                <div class="order-stat-card total">
                    <div class="stat-icon">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo number_format($stats['total']); ?></h3>
                        <p>Total Orders</p>
                    </div>
                </div>

                <div class="order-stat-card pending">
                    <div class="stat-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo number_format($stats['pending']); ?></h3>
                        <p>Pending</p>
                    </div>
                </div>

                <div class="order-stat-card processing">
                    <div class="stat-icon">
                        <i class="fas fa-spinner"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo number_format($stats['processing']); ?></h3>
                        <p>Processing</p>
                    </div>
                </div>

                <div class="order-stat-card completed">
                    <div class="stat-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo number_format($stats['completed']); ?></h3>
                        <p>Completed</p>
                    </div>
                </div>
            </div>

            <!-- Filters and Search -->
            <div class="orders-toolbar">
                <div class="toolbar-left">
                    <div class="filter-group">
                        <label><i class="fas fa-filter"></i> Filter by Status:</label>
                        <select id="statusFilter" class="filter-select">
                            <option value="">All Orders</option>
                            <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="processing" <?php echo $status_filter === 'processing' ? 'selected' : ''; ?>>Processing</option>
                            <option value="completed" <?php echo $status_filter === 'completed' ? 'selected' : ''; ?>>Completed</option>
                            <option value="partial" <?php echo $status_filter === 'partial' ? 'selected' : ''; ?>>Partial</option>
                            <option value="cancelled" <?php echo $status_filter === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                            <option value="refunded" <?php echo $status_filter === 'refunded' ? 'selected' : ''; ?>>Refunded</option>
                        </select>
                    </div>
                </div>

                <div class="toolbar-right">
                    <div class="search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" 
                               id="searchInput" 
                               placeholder="Search orders..." 
                               value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    <button class="btn-refresh" onclick="location.reload()">
                        <i class="fas fa-sync-alt"></i> Refresh
                    </button>
                </div>
            </div>

            <!-- Orders Table -->
            <div class="orders-table-card">
                <?php if ($total_orders > 0): ?>
                    <div class="table-responsive">
                        <table class="orders-table">
                            <thead>
                                <tr>
                                    <th>Order ID</th>
                                    <th>Service</th>
                                    <th>Link</th>
                                    <th>Quantity</th>
                                    <th>Progress</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($order = mysqli_fetch_assoc($orders_query)): ?>
                                    <?php
                                    $progress = 0;
                                    if ($order['quantity'] > 0) {
                                        $delivered = $order['quantity'] - $order['remains'];
                                        $progress = ($delivered / $order['quantity']) * 100;
                                    }
                                    ?>
                                    <tr>
                                        <td>
                                            <strong class="order-id"><?php echo htmlspecialchars($order['order_number']); ?></strong>
                                        </td>
                                        <td>
                                            <div class="service-cell">
                                                <span class="service-icon">
                                                    <i class="fas <?php echo htmlspecialchars($order['icon']); ?>"></i>
                                                </span>
                                                <div>
                                                    <span class="category-badge"><?php echo htmlspecialchars($order['category']); ?></span>
                                                    <p class="service-name"><?php echo htmlspecialchars($order['service_name']); ?></p>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <a href="<?php echo htmlspecialchars($order['link']); ?>" 
                                               target="_blank" 
                                               class="order-link"
                                               title="<?php echo htmlspecialchars($order['link']); ?>">
                                                <i class="fas fa-external-link-alt"></i>
                                                <?php echo substr(htmlspecialchars($order['link']), 0, 30); ?>...
                                            </a>
                                        </td>
                                        <td>
                                            <strong><?php echo number_format($order['quantity']); ?></strong>
                                        </td>
                                        <td>
                                            <div class="progress-cell">
                                                <div class="progress-bar">
                                                    <div class="progress-fill" style="width: <?php echo $progress; ?>%"></div>
                                                </div>
                                                <span class="progress-text"><?php echo round($progress); ?>%</span>
                                            </div>
                                        </td>
                                        <td>
                                            <strong class="order-price">$<?php echo number_format($order['price'], 2); ?></strong>
                                        </td>
                                        <td>
                                            <?php
                                            $status_class = [
                                                'pending' => 'warning',
                                                'processing' => 'info',
                                                'completed' => 'success',
                                                'partial' => 'warning',
                                                'cancelled' => 'danger',
                                                'refunded' => 'secondary'
                                            ];
                                            $class = $status_class[$order['status']] ?? 'secondary';
                                            
                                            $status_icons = [
                                                'pending' => 'fa-clock',
                                                'processing' => 'fa-spinner fa-spin',
                                                'completed' => 'fa-check-circle',
                                                'partial' => 'fa-exclamation-circle',
                                                'cancelled' => 'fa-times-circle',
                                                'refunded' => 'fa-undo'
                                            ];
                                            $icon = $status_icons[$order['status']] ?? 'fa-question-circle';
                                            ?>
                                            <span class="status-badge <?php echo $class; ?>">
                                                <i class="fas <?php echo $icon; ?>"></i>
                                                <?php echo ucfirst($order['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="date-cell">
                                                <span class="date"><?php echo date('M d, Y', strtotime($order['created_at'])); ?></span>
                                                <span class="time"><?php echo date('h:i A', strtotime($order['created_at'])); ?></span>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <button class="btn-action view" 
                                                        onclick="viewOrder(<?php echo $order['id']; ?>)"
                                                        title="View Details">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <?php if ($order['status'] === 'pending'): ?>
                                                    <button class="btn-action cancel" 
                                                            onclick="cancelOrder(<?php echo $order['id']; ?>)"
                                                            title="Cancel Order">
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
                                <a href="?page=<?php echo $page - 1; ?><?php echo $status_filter ? '&status=' . $status_filter : ''; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>" 
                                   class="page-link">
                                    <i class="fas fa-chevron-left"></i> Previous
                                </a>
                            <?php endif; ?>

                            <div class="page-numbers">
                                <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                    <a href="?page=<?php echo $i; ?><?php echo $status_filter ? '&status=' . $status_filter : ''; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>" 
                                       class="page-link <?php echo $i === $page ? 'active' : ''; ?>">
                                        <?php echo $i; ?>
                                    </a>
                                <?php endfor; ?>
                            </div>

                            <?php if ($page < $total_pages): ?>
                                <a href="?page=<?php echo $page + 1; ?><?php echo $status_filter ? '&status=' . $status_filter : ''; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>" 
                                   class="page-link">
                                    Next <i class="fas fa-chevron-right"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                <?php else: ?>
                    <div class="empty-orders">
                        <i class="fas fa-shopping-cart"></i>
                        <h3>No Orders Found</h3>
                        <p>You haven't placed any orders yet.</p>
                        <a href="new-order.php" class="btn-primary">
                            <i class="fas fa-plus-circle"></i> Place Your First Order
                        </a>
                    </div>
                <?php endif; ?>
            </div>

        </div>
    </div>

    <!-- Order Details Modal -->
    <div id="orderModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-shopping-cart"></i> Order Details</h2>
                <button class="modal-close" onclick="closeModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body" id="modalBody">
                <div class="modal-loading">
                    <i class="fas fa-spinner fa-spin"></i>
                    <p>Loading order details...</p>
                </div>
            </div>
        </div>
    </div>

    <script src="assets/js/dashboard.js"></script>
    <script src="assets/js/orders.js"></script>
</body>
</html>