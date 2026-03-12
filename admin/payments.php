<?php
require_once '../config.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

$admin_id = $_SESSION['user_id'];
$admin_query = mysqli_query($conn, "SELECT * FROM users WHERE id = $admin_id");
$admin = mysqli_fetch_assoc($admin_query);

// Handle actions here if needed

// Get stats
$total_deposits = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM transactions WHERE type = 'deposit'"));
$completed_deposits = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM transactions WHERE type = 'deposit' AND status = 'completed'"));
$pending_deposits = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM transactions WHERE type = 'deposit' AND status = 'pending'"));

$revenue_query = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(amount) as total FROM transactions WHERE type = 'deposit' AND status = 'completed'"));
$total_revenue = $revenue_query['total'] ?? 0;

// Get pending counts for sidebar
$pending_orders = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM orders WHERE status = 'pending'"));
$open_tickets = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM support_tickets WHERE status != 'closed'"));

// Fetch deposits
$payments_query = mysqli_query($conn, "
    SELECT t.*, u.username, u.email 
    FROM transactions t
    LEFT JOIN users u ON t.user_id = u.id
    WHERE t.type = 'deposit'
    ORDER BY t.created_at DESC
");

// Get unread notifications
$unread_notifications = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM notifications WHERE user_id = $admin_id AND is_read = 0"));

$page_title = "Payments & Deposits";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payments - SMM Panel Admin</title>
    <link rel="stylesheet" href="../assets/css/styles.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../assets/css/dashboard.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../assets/css/admin-dashboard.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .payments-stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        .payment-status-completed { color: #38a169; font-weight: 600; background: #c6f6d5; padding: 0.2rem 0.6rem; border-radius: 4px; }
        .payment-status-pending { color: #d69e2e; font-weight: 600; background: #fef08a; padding: 0.2rem 0.6rem; border-radius: 4px; }
        .payment-status-failed { color: #e53e3e; font-weight: 600; background: #fed7d7; padding: 0.2rem 0.6rem; border-radius: 4px; }
        
        .payments-table-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            overflow-x: auto;
        }
    </style>
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
            <a href="payments.php" class="nav-item active">
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

    <div class="main-content">
        <!-- Top Header -->
        <header class="top-header admin-header">
            <button class="menu-toggle" id="menuToggle">
                <i class="fas fa-bars"></i>
            </button>
            <div class="header-title">
                <h1>Payments</h1>
                <p>Manage deposits and payouts</p>
            </div>
            <div class="header-actions">
                <div class="notification-icon">
                    <i class="fas fa-bell"></i>
                    <?php if ($unread_notifications > 0): ?>
                        <span class="notification-badge"><?php echo $unread_notifications; ?></span>
                    <?php endif; ?>
                </div>
            </div>
        </header>

        <div class="dashboard-content">
            <!-- Stats -->
            <div class="payments-stats-grid">
                <div class="stat-card primary">
                    <div class="stat-icon"><i class="fas fa-dollar-sign"></i></div>
                    <div class="stat-details">
                        <h3>$<?php echo number_format($total_revenue, 2); ?></h3>
                        <p>Total Revenue</p>
                    </div>
                </div>
                <div class="stat-card info">
                    <div class="stat-icon"><i class="fas fa-list"></i></div>
                    <div class="stat-details">
                        <h3><?php echo number_format($total_deposits); ?></h3>
                        <p>Total Deposits</p>
                    </div>
                </div>
                <div class="stat-card success">
                    <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
                    <div class="stat-details">
                        <h3><?php echo number_format($completed_deposits); ?></h3>
                        <p>Completed Deposits</p>
                    </div>
                </div>
                <div class="stat-card warning">
                    <div class="stat-icon"><i class="fas fa-clock"></i></div>
                    <div class="stat-details">
                        <h3><?php echo number_format($pending_deposits); ?></h3>
                        <p>Pending Deposits</p>
                    </div>
                </div>
            </div>

            <!-- Payments Table -->
            <div class="payments-table-card table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Transaction ID</th>
                            <th>User</th>
                            <th>Amount</th>
                            <th>Method</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($payments_query) > 0): ?>
                            <?php while ($payment = mysqli_fetch_assoc($payments_query)): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($payment['transaction_id']); ?></strong></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($payment['username'] ?? 'User'); ?></strong><br>
                                        <small><?php echo htmlspecialchars($payment['email'] ?? ''); ?></small>
                                    </td>
                                    <td><strong>$<?php echo number_format($payment['amount'], 2); ?></strong></td>
                                    <td><i class="fab fa-paypal"></i> <?php echo ucfirst($payment['payment_method'] ?? 'Manual'); ?></td>
                                    <td>
                                        <span class="payment-status-<?php echo $payment['status'] ?? 'pending'; ?>">
                                            <?php echo ucfirst($payment['status'] ?? 'Pending'); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($payment['created_at'])); ?></td>
                                    <td>
                                        <button class="btn-action view" title="View"><i class="fas fa-eye"></i></button>
                                        <?php if (($payment['status'] ?? '') === 'pending'): ?>
                                            <button class="btn-action success" title="Approve"><i class="fas fa-check"></i></button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" style="text-align: center;">No payments found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <script src="../assets/js/dashboard.js?v=<?php echo time(); ?>"></script>
    <script src="../assets/js/admin-dashboard.js?v=<?php echo time(); ?>"></script>
</body>
</html>
