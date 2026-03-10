<?php
require_once '../config.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Get admin data
$admin_query = mysqli_query($conn, "SELECT * FROM users WHERE id = $user_id");
if (!$admin_query) {
    die('Database error: ' . mysqli_error($conn));
}
$admin = mysqli_fetch_assoc($admin_query);

// if for some reason the record is missing, log out to avoid null accesses
if (!$admin) {
    session_destroy();
    header('Location: ../login.php');
    exit();
}

// Get statistics
// Total Users
$total_users_query = mysqli_query($conn, "SELECT COUNT(*) as count FROM users WHERE user_role != 'admin'");
$total_users = mysqli_fetch_assoc($total_users_query)['count'];

// Active Users (logged in last 30 days)
$active_users_query = mysqli_query($conn, "SELECT COUNT(*) as count FROM users WHERE last_login >= DATE_SUB(NOW(), INTERVAL 30 DAY) AND user_role != 'admin'");
$active_users = mysqli_fetch_assoc($active_users_query)['count'];

// Total Orders
$total_orders_query = mysqli_query($conn, "SELECT COUNT(*) as count FROM orders");
$total_orders = mysqli_fetch_assoc($total_orders_query)['count'];

// Pending Orders
$pending_orders_query = mysqli_query($conn, "SELECT COUNT(*) as count FROM orders WHERE status = 'pending'");
$pending_orders = mysqli_fetch_assoc($pending_orders_query)['count'];

// Total Revenue
$total_revenue_query = mysqli_query($conn, "SELECT SUM(amount) as total FROM transactions WHERE type = 'deposit' AND status = 'completed'");
$total_revenue = mysqli_fetch_assoc($total_revenue_query)['total'] ?? 0;

// Today's Revenue
$today_revenue_query = mysqli_query($conn, "SELECT SUM(amount) as total FROM transactions WHERE type = 'deposit' AND status = 'completed' AND DATE(created_at) = CURDATE()");
$today_revenue = mysqli_fetch_assoc($today_revenue_query)['total'] ?? 0;

// Open Tickets
$open_tickets_query = mysqli_query($conn, "SELECT COUNT(*) as count FROM support_tickets WHERE status != 'closed'");
$open_tickets = mysqli_fetch_assoc($open_tickets_query)['count'];

// Total Services
$total_services_query = mysqli_query($conn, "SELECT COUNT(*) as count FROM services WHERE status = 'active'");
$total_services = mysqli_fetch_assoc($total_services_query)['count'];

// Recent Orders
$recent_orders = mysqli_query($conn, "
    SELECT o.*, u.username, u.email, s.name as service_name, s.category
    FROM orders o
    LEFT JOIN users u ON o.user_id = u.id
    LEFT JOIN services s ON o.service_id = s.id
    ORDER BY o.created_at DESC
    LIMIT 10
");

// Recent Users
$recent_users = mysqli_query($conn, "
    SELECT * FROM users 
    WHERE user_role != 'admin'
    ORDER BY created_at DESC 
    LIMIT 5
");

// Revenue chart data (last 7 days)
$revenue_chart_data = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $revenue_query = mysqli_query($conn, "
        SELECT SUM(amount) as total 
        FROM transactions 
        WHERE type = 'deposit' 
        AND status = 'completed' 
        AND DATE(created_at) = '$date'
    ");
    $revenue = mysqli_fetch_assoc($revenue_query)['total'] ?? 0;
    $revenue_chart_data[] = [
        'date' => date('M d', strtotime($date)),
        'revenue' => $revenue
    ];
}

// Orders chart data (last 7 days)
$orders_chart_data = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $orders_query = mysqli_query($conn, "
        SELECT COUNT(*) as count 
        FROM orders 
        WHERE DATE(created_at) = '$date'
    ");
    $count = mysqli_fetch_assoc($orders_query)['count'];
    $orders_chart_data[] = [
        'date' => date('M d', strtotime($date)),
        'count' => $count
    ];
}

// Get unread notifications count
$unread_notifications = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM notifications WHERE user_id = $user_id AND is_read = 0"));

$page_title = "Admin Dashboard";
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
                <h4><?php echo htmlspecialchars($admin['full_name'] ?? 'Administrator'); ?></h4>
                <p><?php echo htmlspecialchars($admin['email'] ?? ''); ?></p>
                <span class="user-badge admin-badge">Administrator</span>
            </div>
        </div>

        <nav class="sidebar-nav">
            <a href="dashboard.php" class="nav-item active">
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
                <h1>Admin Dashboard</h1>
                <p>Welcome back, <?php echo htmlspecialchars($admin['full_name']); ?>!</p>
            </div>

            <div class="header-actions">
                <div class="quick-stats">
                    <div class="quick-stat-item">
                        <i class="fas fa-users"></i>
                        <div>
                            <small>Total Users</small>
                            <strong><?php echo number_format($total_users); ?></strong>
                        </div>
                    </div>
                    <div class="quick-stat-item">
                        <i class="fas fa-shopping-cart"></i>
                        <div>
                            <small>Total Orders</small>
                            <strong><?php echo number_format($total_orders); ?></strong>
                        </div>
                    </div>
                </div>

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

        <!-- Dashboard Content -->
        <div class="dashboard-content">
            
            <!-- Statistics Cards -->
            <div class="stats-grid admin-stats-grid">
                <div class="stat-card primary">
                    <div class="stat-icon">
                        <i class="fas fa-dollar-sign"></i>
                    </div>
                    <div class="stat-details">
                        <h3>$<?php echo number_format($total_revenue, 2); ?></h3>
                        <p>Total Revenue</p>
                        <div class="stat-trend positive">
                            <i class="fas fa-arrow-up"></i>
                            <span>+12.5% from last month</span>
                        </div>
                    </div>
                </div>

                <div class="stat-card success">
                    <div class="stat-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-details">
                        <h3><?php echo number_format($total_users); ?></h3>
                        <p>Total Users</p>
                        <div class="stat-info">
                            <span><?php echo number_format($active_users); ?> active this month</span>
                        </div>
                    </div>
                </div>

                <div class="stat-card warning">
                    <div class="stat-icon">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <div class="stat-details">
                        <h3><?php echo number_format($total_orders); ?></h3>
                        <p>Total Orders</p>
                        <div class="stat-info">
                            <span><?php echo number_format($pending_orders); ?> pending</span>
                        </div>
                    </div>
                </div>

                <div class="stat-card info">
                    <div class="stat-icon">
                        <i class="fas fa-calendar-day"></i>
                    </div>
                    <div class="stat-details">
                        <h3>$<?php echo number_format($today_revenue, 2); ?></h3>
                        <p>Today's Revenue</p>
                        <div class="stat-trend positive">
                            <i class="fas fa-arrow-up"></i>
                            <span>+8.3% from yesterday</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Charts Section -->
            <div class="charts-grid">
                <div class="chart-card">
                    <div class="chart-header">
                        <h3><i class="fas fa-chart-line"></i> Revenue Overview</h3>
                        <select class="chart-filter">
                            <option value="7">Last 7 Days</option>
                            <option value="30">Last 30 Days</option>
                            <option value="90">Last 90 Days</option>
                        </select>
                    </div>
                    <div class="chart-body">
                        <canvas id="revenueChart"></canvas>
                    </div>
                </div>

                <div class="chart-card">
                    <div class="chart-header">
                        <h3><i class="fas fa-chart-bar"></i> Orders Overview</h3>
                        <select class="chart-filter">
                            <option value="7">Last 7 Days</option>
                            <option value="30">Last 30 Days</option>
                            <option value="90">Last 90 Days</option>
                        </select>
                    </div>
                    <div class="chart-body">
                        <canvas id="ordersChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="quick-actions-section">
                <h2 class="section-title">Quick Actions</h2>
                <div class="admin-actions-grid">
                    <a href="users.php?action=add" class="admin-action-card">
                        <div class="action-icon blue">
                            <i class="fas fa-user-plus"></i>
                        </div>
                        <h3>Add New User</h3>
                        <p>Create a new user account</p>
                    </a>

                    <a href="services.php?action=add" class="admin-action-card">
                        <div class="action-icon green">
                            <i class="fas fa-plus-circle"></i>
                        </div>
                        <h3>Add Service</h3>
                        <p>Create a new SMM service</p>
                    </a>

                    <a href="orders.php?status=pending" class="admin-action-card">
                        <div class="action-icon orange">
                            <i class="fas fa-tasks"></i>
                        </div>
                        <h3>Manage Orders</h3>
                        <p>Process pending orders</p>
                    </a>

                    <a href="tickets.php?status=open" class="admin-action-card">
                        <div class="action-icon purple">
                            <i class="fas fa-headset"></i>
                        </div>
                        <h3>Support Tickets</h3>
                        <p>Answer customer inquiries</p>
                    </a>

                    <a href="payments.php?status=pending" class="admin-action-card">
                        <div class="action-icon red">
                            <i class="fas fa-money-check-alt"></i>
                        </div>
                        <h3>Approve Payments</h3>
                        <p>Process pending deposits</p>
                    </a>

                    <a href="reports.php" class="admin-action-card">
                        <div class="action-icon teal">
                            <i class="fas fa-file-alt"></i>
                        </div>
                        <h3>Generate Reports</h3>
                        <p>View detailed analytics</p>
                    </a>
                </div>
            </div>

            <!-- Recent Orders -->
            <div class="recent-section">
                <div class="section-header">
                    <h2 class="section-title">Recent Orders</h2>
                    <a href="orders.php" class="view-all">View All <i class="fas fa-arrow-right"></i></a>
                </div>

                <div class="table-responsive">
                    <table class="data-table admin-table">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>User</th>
                                <th>Service</th>
                                <th>Quantity</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (mysqli_num_rows($recent_orders) > 0): ?>
                                <?php while ($order = mysqli_fetch_assoc($recent_orders)): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($order['order_number']); ?></strong></td>
                                        <td>
                                            <div class="user-cell">
                                                <strong><?php echo htmlspecialchars($order['username']); ?></strong>
                                                <small><?php echo htmlspecialchars($order['email']); ?></small>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="service-cell">
                                                <span class="category-badge"><?php echo htmlspecialchars($order['category']); ?></span>
                                                <?php echo htmlspecialchars($order['service_name']); ?>
                                            </div>
                                        </td>
                                        <td><?php echo number_format($order['quantity']); ?></td>
                                        <td><strong>$<?php echo number_format($order['price'], 2); ?></strong></td>
                                        <td>
                                            <?php
                                            $status_class = [
                                                'pending' => 'warning',
                                                'processing' => 'info',
                                                'completed' => 'success',
                                                'cancelled' => 'danger'
                                            ];
                                            $class = $status_class[$order['status']] ?? 'secondary';
                                            ?>
                                            <span class="status-badge <?php echo $class; ?>">
                                                <?php echo ucfirst($order['status']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('M d, Y', strtotime($order['created_at'])); ?></td>
                                        <td>
                                            <div class="action-buttons">
                                                <button class="btn-action view" onclick="viewOrder(<?php echo $order['id']; ?>)" title="View">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <?php if ($order['status'] === 'pending'): ?>
                                                    <button class="btn-action success" onclick="processOrder(<?php echo $order['id']; ?>)" title="Process">
                                                        <i class="fas fa-check"></i>
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="text-center">No orders yet</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Recent Users -->
            <div class="recent-section">
                <div class="section-header">
                    <h2 class="section-title">Recent Users</h2>
                    <a href="users.php" class="view-all">View All <i class="fas fa-arrow-right"></i></a>
                </div>

                <div class="users-grid">
                    <?php while ($user = mysqli_fetch_assoc($recent_users)): ?>
                        <div class="user-card">
                            <div class="user-card-header">
                                <div class="user-avatar-large">
                                    <i class="fas fa-user"></i>
                                </div>
                                <span class="user-role-badge <?php echo $user['user_role']; ?>">
                                    <?php echo ucfirst($user['user_role']); ?>
                                </span>
                            </div>
                            <div class="user-card-body">
                                <h3><?php echo htmlspecialchars($user['full_name']); ?></h3>
                                <p><?php echo htmlspecialchars($user['email']); ?></p>
                                <div class="user-stats-mini">
                                    <div class="stat-mini">
                                        <i class="fas fa-wallet"></i>
                                        <span>$<?php echo number_format($user['balance'], 2); ?></span>
                                    </div>
                                    <div class="stat-mini">
                                        <i class="fas fa-calendar"></i>
                                        <span><?php echo date('M d, Y', strtotime($user['created_at'])); ?></span>
                                    </div>
                                </div>
                            </div>
                            <div class="user-card-footer">
                                <a href="users.php?id=<?php echo $user['id']; ?>" class="btn-view-user">
                                    View Profile <i class="fas fa-arrow-right"></i>
                                </a>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>

        </div>
    </div>

    <script src="../assets/js/dashboard.js"></script>
    <script src="../assets/js/admin-dashboard.js"></script>
    <script>
        // Chart Data from PHP
        const revenueData = <?php echo json_encode($revenue_chart_data); ?>;
        const ordersData = <?php echo json_encode($orders_chart_data); ?>;
    </script>
</body>
</html>