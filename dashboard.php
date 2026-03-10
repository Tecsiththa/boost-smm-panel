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

// Get statistics
$total_orders = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM orders WHERE user_id = $user_id"));
$pending_orders = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM orders WHERE user_id = $user_id AND status = 'pending'"));
$completed_orders = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM orders WHERE user_id = $user_id AND status = 'completed'"));

// Get total spent
$spent_query = mysqli_query($conn, "SELECT SUM(amount) as total FROM transactions WHERE user_id = $user_id AND type = 'order'");
$spent_data = mysqli_fetch_assoc($spent_query);
$total_spent = $spent_data['total'] ?? 0;

// Get recent orders
$recent_orders = mysqli_query($conn, "
    SELECT o.*, s.name as service_name, s.category 
    FROM orders o 
    LEFT JOIN services s ON o.service_id = s.id 
    WHERE o.user_id = $user_id 
    ORDER BY o.created_at DESC 
    LIMIT 5
");

// Get notifications count
$unread_notifications = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM notifications WHERE user_id = $user_id AND is_read = 0"));

// Get active tickets count
$active_tickets = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM support_tickets WHERE user_id = $user_id AND status != 'closed'"));

$page_title = "Dashboard";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - SMM Panel</title>
    <link rel="stylesheet" href="assets/css/styles.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="assets/css/dashboard.css?v=<?php echo time(); ?>">
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
            <a href="dashboard.php" class="nav-item active">
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
                <?php if ($pending_orders > 0): ?>
                    <span class="badge"><?php echo $pending_orders; ?></span>
                <?php endif; ?>
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
                <h1>Dashboard</h1>
                <p>Welcome back, <?php echo htmlspecialchars($user['full_name']); ?>!</p>
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
                    <img src="assets/images/default-avatar.png" alt="User" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                    <div class="user-avatar-fallback" style="display: none;">
                        <i class="fas fa-user"></i>
                    </div>
                </div>
            </div>
        </header>

        <!-- Dashboard Content -->
        <div class="dashboard-content">
            
            <!-- Stats Cards -->
            <div class="stats-grid">
                <div class="stat-card primary">
                    <div class="stat-icon">
                        <i class="fas fa-wallet"></i>
                    </div>
                    <div class="stat-details">
                        <h3>$<?php echo number_format($user['balance'], 2); ?></h3>
                        <p>Current Balance</p>
                    </div>
                    <a href="add-funds.php" class="stat-link">Add Funds <i class="fas fa-arrow-right"></i></a>
                </div>

                <div class="stat-card success">
                    <div class="stat-icon">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <div class="stat-details">
                        <h3><?php echo number_format($total_orders); ?></h3>
                        <p>Total Orders</p>
                    </div>
                    <a href="orders.php" class="stat-link">View All <i class="fas fa-arrow-right"></i></a>
                </div>

                <div class="stat-card warning">
                    <div class="stat-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-details">
                        <h3><?php echo number_format($pending_orders); ?></h3>
                        <p>Pending Orders</p>
                    </div>
                    <a href="orders.php?status=pending" class="stat-link">Track <i class="fas fa-arrow-right"></i></a>
                </div>

                <div class="stat-card info">
                    <div class="stat-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div class="stat-details">
                        <h3>$<?php echo number_format($total_spent, 2); ?></h3>
                        <p>Total Spent</p>
                    </div>
                    <a href="transactions.php" class="stat-link">History <i class="fas fa-arrow-right"></i></a>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="quick-actions">
                <h2 class="section-title">Quick Actions</h2>
                <div class="actions-grid">
                    <a href="new-order.php" class="action-card">
                        <div class="action-icon purple">
                            <i class="fas fa-plus-circle"></i>
                        </div>
                        <h3>New Order</h3>
                        <p>Place a new order instantly</p>
                    </a>

                    <a href="add-funds.php" class="action-card">
                        <div class="action-icon green">
                            <i class="fas fa-credit-card"></i>
                        </div>
                        <h3>Add Funds</h3>
                        <p>Top up your account balance</p>
                    </a>

                    <a href="services.php" class="action-card">
                        <div class="action-icon blue">
                            <i class="fas fa-list-ul"></i>
                        </div>
                        <h3>Browse Services</h3>
                        <p>View all available services</p>
                    </a>

                    <a href="support.php" class="action-card">
                        <div class="action-icon orange">
                            <i class="fas fa-headset"></i>
                        </div>
                        <h3>Get Support</h3>
                        <p>Contact our support team</p>
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
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Service</th>
                                <th>Link</th>
                                <th>Quantity</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (mysqli_num_rows($recent_orders) > 0): ?>
                                <?php while ($order = mysqli_fetch_assoc($recent_orders)): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($order['order_number']); ?></strong></td>
                                        <td>
                                            <div class="service-cell">
                                                <span class="category-badge"><?php echo htmlspecialchars($order['category']); ?></span>
                                                <?php echo htmlspecialchars($order['service_name']); ?>
                                            </div>
                                        </td>
                                        <td>
                                            <a href="<?php echo htmlspecialchars($order['link']); ?>" target="_blank" class="link-text">
                                                <?php echo substr(htmlspecialchars($order['link']), 0, 30); ?>...
                                            </a>
                                        </td>
                                        <td><?php echo number_format($order['quantity']); ?></td>
                                        <td><strong>$<?php echo number_format($order['price'], 2); ?></strong></td>
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
                                            ?>
                                            <span class="status-badge <?php echo $class; ?>">
                                                <?php echo ucfirst($order['status']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('M d, Y', strtotime($order['created_at'])); ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center">
                                        <div class="empty-state">
                                            <i class="fas fa-shopping-cart"></i>
                                            <h3>No orders yet</h3>
                                            <p>Start by placing your first order</p>
                                            <a href="new-order.php" class="btn-primary">Create Order</a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>

    <script src="assets/js/dashboard.js"></script>
</body>
</html>