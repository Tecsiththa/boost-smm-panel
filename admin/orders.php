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

// Handle order status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $order_id = (int)$_POST['order_id'];
    $new_status = sanitize_input($_POST['status']);
    $remains = isset($_POST['remains']) ? (int)$_POST['remains'] : 0;
    
    $allowed_statuses = ['pending', 'processing', 'completed', 'partial', 'cancelled', 'refunded'];
    
    if (in_array($new_status, $allowed_statuses)) {
        $update_query = "UPDATE orders SET status = '$new_status', remains = $remains, updated_at = NOW() WHERE id = $order_id";
        
        if (mysqli_query($conn, $update_query)) {
            // Get order details
            $order_query = mysqli_query($conn, "SELECT * FROM orders WHERE id = $order_id");
            $order = mysqli_fetch_assoc($order_query);
            
            // Create notification for user
            $status_messages = [
                'pending' => 'Your order is pending',
                'processing' => 'Your order is being processed',
                'completed' => 'Your order has been completed',
                'partial' => 'Your order was partially completed',
                'cancelled' => 'Your order has been cancelled',
                'refunded' => 'Your order has been refunded'
            ];
            
            $notif_type = ($new_status === 'completed') ? 'success' : (($new_status === 'cancelled' || $new_status === 'refunded') ? 'warning' : 'info');
            
            mysqli_query($conn, "INSERT INTO notifications (user_id, title, message, type, link) 
                               VALUES ({$order['user_id']}, 'Order Status Updated', '{$status_messages[$new_status]} - Order #{$order['order_number']}', '$notif_type', '../orders.php?id=$order_id')");
            
            // Log activity
            $ip_address = $_SERVER['REMOTE_ADDR'];
            mysqli_query($conn, "INSERT INTO activity_logs (user_id, action, description, ip_address) 
                               VALUES ($admin_id, 'Order Status Updated', 'Updated order #{$order['order_number']} to $new_status', '$ip_address')");
            
            $success = "Order status updated successfully!";
        } else {
            $errors[] = "Failed to update order status";
        }
    }
}

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Filters
$status_filter = isset($_GET['status']) ? sanitize_input($_GET['status']) : '';
$service_filter = isset($_GET['service']) ? sanitize_input($_GET['service']) : '';
$user_filter = isset($_GET['user']) ? sanitize_input($_GET['user']) : '';
$date_from = isset($_GET['date_from']) ? sanitize_input($_GET['date_from']) : '';
$date_to = isset($_GET['date_to']) ? sanitize_input($_GET['date_to']) : '';
$search = isset($_GET['search']) ? sanitize_input($_GET['search']) : '';

$where_clauses = ['1=1'];

if ($status_filter) {
    $where_clauses[] = "o.status = '$status_filter'";
}

if ($service_filter) {
    $where_clauses[] = "s.category = '$service_filter'";
}

if ($user_filter) {
    $where_clauses[] = "o.user_id = '$user_filter'";
}

if ($date_from && $date_to) {
    $where_clauses[] = "DATE(o.created_at) BETWEEN '$date_from' AND '$date_to'";
} elseif ($date_from) {
    $where_clauses[] = "DATE(o.created_at) >= '$date_from'";
} elseif ($date_to) {
    $where_clauses[] = "DATE(o.created_at) <= '$date_to'";
}

if ($search) {
    $where_clauses[] = "(o.order_number LIKE '%$search%' OR u.username LIKE '%$search%' OR o.link LIKE '%$search%')";
}

$where_sql = implode(' AND ', $where_clauses);

// Count total orders
$count_query = mysqli_query($conn, "
    SELECT COUNT(*) as total 
    FROM orders o
    LEFT JOIN users u ON o.user_id = u.id
    LEFT JOIN services s ON o.service_id = s.id
    WHERE $where_sql
");
$total_orders = mysqli_fetch_assoc($count_query)['total'];
$total_pages = ceil($total_orders / $per_page);

// Get orders
$orders_query = mysqli_query($conn, "
    SELECT 
        o.*,
        u.username,
        u.email,
        s.name as service_name,
        s.category,
        s.icon
    FROM orders o
    LEFT JOIN users u ON o.user_id = u.id
    LEFT JOIN services s ON o.service_id = s.id
    WHERE $where_sql
    ORDER BY o.created_at DESC
    LIMIT $per_page OFFSET $offset
");

// Get statistics
$stats_query = mysqli_query($conn, "
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN status = 'processing' THEN 1 ELSE 0 END) as processing,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
        SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled,
        SUM(CASE WHEN status = 'partial' THEN 1 ELSE 0 END) as partial,
        SUM(price) as total_revenue
    FROM orders
");
$stats = mysqli_fetch_assoc($stats_query);

// Get service categories
$categories_query = mysqli_query($conn, "SELECT DISTINCT category FROM services ORDER BY category");
$categories = [];
while ($cat = mysqli_fetch_assoc($categories_query)) {
    $categories[] = $cat['category'];
}

// Get unread notifications
$unread_notifications = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM notifications WHERE user_id = $admin_id AND is_read = 0"));

// Get open tickets
$open_tickets = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM support_tickets WHERE status != 'closed'"));

$page_title = "Orders Management";
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
    <link rel="stylesheet" href="../assets/css/admin-orders.css?v=<?php echo time(); ?>">
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
            <a href="orders.php" class="nav-item active">
                <i class="fas fa-shopping-cart"></i>
                <span>Orders</span>
                <?php if ($stats['pending'] > 0): ?>
                    <span class="badge"><?php echo $stats['pending']; ?></span>
                <?php endif; ?>
            </a>
            <a href="services.php" class="nav-item">
                <i class="fas fa-list"></i>
                <span>Services</span>
            </a>
            <a href="transactions.php" class="nav-item">
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
                <h1>Orders Management</h1>
                <p>Manage and monitor all platform orders</p>
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

        <!-- Orders Content -->
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

            <!-- Orders Statistics -->
            <div class="orders-stats-grid">
                <div class="stat-card blue">
                    <div class="stat-icon">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <div class="stat-details">
                        <h3><?php echo number_format($stats['total']); ?></h3>
                        <p>Total Orders</p>
                    </div>
                </div>

                <div class="stat-card orange">
                    <div class="stat-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-details">
                        <h3><?php echo number_format($stats['pending']); ?></h3>
                        <p>Pending Orders</p>
                    </div>
                </div>

                <div class="stat-card purple">
                    <div class="stat-icon">
                        <i class="fas fa-spinner"></i>
                    </div>
                    <div class="stat-details">
                        <h3><?php echo number_format($stats['processing']); ?></h3>
                        <p>Processing</p>
                    </div>
                </div>

                <div class="stat-card green">
                    <div class="stat-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-details">
                        <h3><?php echo number_format($stats['completed']); ?></h3>
                        <p>Completed</p>
                    </div>
                </div>

                <div class="stat-card yellow">
                    <div class="stat-icon">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <div class="stat-details">
                        <h3><?php echo number_format($stats['partial']); ?></h3>
                        <p>Partial</p>
                    </div>
                </div>

                <div class="stat-card teal">
                    <div class="stat-icon">
                        <i class="fas fa-dollar-sign"></i>
                    </div>
                    <div class="stat-details">
                        <h3>$<?php echo number_format($stats['total_revenue'], 2); ?></h3>
                        <p>Total Revenue</p>
                    </div>
                </div>
            </div>

            <!-- Filters and Search -->
            <div class="orders-toolbar">
                <div class="filters-group">
                    <div class="filter-item">
                        <label><i class="fas fa-filter"></i> Status:</label>
                        <select id="statusFilter" class="filter-select" onchange="applyFilters()">
                            <option value="">All Status</option>
                            <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="processing" <?php echo $status_filter === 'processing' ? 'selected' : ''; ?>>Processing</option>
                            <option value="completed" <?php echo $status_filter === 'completed' ? 'selected' : ''; ?>>Completed</option>
                            <option value="partial" <?php echo $status_filter === 'partial' ? 'selected' : ''; ?>>Partial</option>
                            <option value="cancelled" <?php echo $status_filter === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                            <option value="refunded" <?php echo $status_filter === 'refunded' ? 'selected' : ''; ?>>Refunded</option>
                        </select>
                    </div>

                    <div class="filter-item">
                        <label><i class="fas fa-layer-group"></i> Service:</label>
                        <select id="serviceFilter" class="filter-select" onchange="applyFilters()">
                            <option value="">All Services</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo htmlspecialchars($category); ?>" <?php echo $service_filter === $category ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($category); ?>
                                </option>
                            <?php endforeach; ?>
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
                               placeholder="Search orders..." 
                               value="<?php echo htmlspecialchars($search); ?>"
                               onkeyup="handleSearch(event)">
                    </div>
                    <button class="btn-export" onclick="window.print()">
                        <i class="fas fa-file-pdf"></i> Export PDF
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
                                    <th>User</th>
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
                                        <td><strong class="order-id"><?php echo htmlspecialchars($order['order_number']); ?></strong></td>
                                        <td>
                                            <div class="user-cell">
                                                <div class="user-avatar-tiny">
                                                    <i class="fas fa-user"></i>
                                                </div>
                                                <div>
                                                    <strong><?php echo htmlspecialchars($order['username']); ?></strong>
                                                    <small><?php echo htmlspecialchars($order['email']); ?></small>
                                                </div>
                                            </div>
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
                                                <?php echo substr(htmlspecialchars($order['link']), 0, 25); ?>...
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
                                                <small class="remains-text"><?php echo number_format($order['remains']); ?> remaining</small>
                                            </div>
                                        </td>
                                        <td><strong class="order-price">$<?php echo number_format($order['price'], 2); ?></strong></td>
                                        <td>
                                            <?php
                                            $status_classes = [
                                                'pending' => 'warning',
                                                'processing' => 'info',
                                                'completed' => 'success',
                                                'partial' => 'warning',
                                                'cancelled' => 'danger',
                                                'refunded' => 'secondary'
                                            ];
                                            $class = $status_classes[$order['status']] ?? 'secondary';

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
                                                <span><?php echo date('M d, Y', strtotime($order['created_at'])); ?></span>
                                                <small><?php echo date('h:i A', strtotime($order['created_at'])); ?></small>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="action-buttons" style="display: flex; flex-direction: column; gap: 0.5rem;">
                                                <button class="btn-text-action view" onclick="viewOrder(<?php echo $order['id']; ?>)" style="background: #e3f2fd; color: #1976d2; padding: 0.5rem 1rem; border: none; border-radius: 6px; cursor: pointer; display: flex; align-items: center; gap: 0.5rem; font-weight: 600; font-size: 0.85rem;">
                                                    <i class="fas fa-eye"></i> View Order Details
                                                </button>
                                                <button class="btn-text-action edit" onclick="editOrder(<?php echo $order['id']; ?>)" style="background: #fdf2f8; color: #db2777; padding: 0.5rem 1rem; border: none; border-radius: 6px; cursor: pointer; display: flex; align-items: center; gap: 0.5rem; font-weight: 600; font-size: 0.85rem;">
                                                    <i class="fas fa-edit"></i> Update Order Status
                                                </button>
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
                                <a href="?page=<?php echo $page - 1; ?><?php echo $status_filter ? '&status=' . $status_filter : ''; ?><?php echo $service_filter ? '&service=' . urlencode($service_filter) : ''; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>" class="page-link">
                                    <i class="fas fa-chevron-left"></i> Previous
                                </a>
                            <?php endif; ?>

                            <div class="page-numbers">
                                <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                    <a href="?page=<?php echo $i; ?><?php echo $status_filter ? '&status=' . $status_filter : ''; ?><?php echo $service_filter ? '&service=' . urlencode($service_filter) : ''; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>" 
                                       class="page-link <?php echo $i === $page ? 'active' : ''; ?>">
                                        <?php echo $i; ?>
                                    </a>
                                <?php endfor; ?>
                            </div>

                            <?php if ($page < $total_pages): ?>
                                <a href="?page=<?php echo $page + 1; ?><?php echo $status_filter ? '&status=' . $status_filter : ''; ?><?php echo $service_filter ? '&service=' . urlencode($service_filter) : ''; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>" class="page-link">
                                    Next <i class="fas fa-chevron-right"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                <?php else: ?>
                    <div class="empty-orders">
                        <i class="fas fa-shopping-cart"></i>
                        <h3>No Orders Found</h3>
                        <p>No orders match your search criteria.</p>
                        <a href="orders.php" class="btn-primary">
                            <i class="fas fa-redo"></i> Reset Filters
                        </a>
                    </div>
                <?php endif; ?>
            </div>

        </div>
    </div>

    <!-- View Order Modal -->
    <div id="viewOrderModal" class="modal">
        <div class="modal-content large">
            <div class="modal-header">
                <h2><i class="fas fa-shopping-cart"></i> Order Details</h2>
                <button class="modal-close" onclick="closeViewOrderModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body" id="viewOrderContent">
                <div class="modal-loading">
                    <i class="fas fa-spinner fa-spin"></i>
                    <p>Loading order details...</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Order Modal -->
    <div id="editOrderModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-edit"></i> Update Order Status</h2>
                <button class="modal-close" onclick="closeEditOrderModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body" id="editOrderContent">
                <div class="modal-loading">
                    <i class="fas fa-spinner fa-spin"></i>
                    <p>Loading order details...</p>
                </div>
            </div>
        </div>
    </div>

    <script src="../assets/js/dashboard.js"></script>
    <script src="../assets/js/admin-orders.js"></script>
</body>
</html>