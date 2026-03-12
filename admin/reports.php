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

// Get pending counts for sidebar
$pending_orders = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM orders WHERE status = 'pending'"));
$open_tickets = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM support_tickets WHERE status != 'closed'"));

// Get unread notifications
$unread_notifications = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM notifications WHERE user_id = $admin_id AND is_read = 0"));

$page_title = "Reports & Analytics";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - SMM Panel Admin</title>
    <link rel="stylesheet" href="../assets/css/styles.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../assets/css/dashboard.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../assets/css/admin-dashboard.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .reports-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .report-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            padding: 2rem;
            text-align: center;
            transition: all 0.3s ease;
        }
        
        .report-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.12);
        }
        
        .report-icon {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            font-size: 2.5rem;
            color: white;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .report-icon.sales { background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); }
        .report-icon.users { background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); }
        .report-icon.tickets { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); }
        
        .report-card h3 {
            font-size: 1.4rem;
            color: #2d3748;
            margin-bottom: 0.5rem;
        }
        
        .report-card p {
            color: #718096;
            margin-bottom: 1.5rem;
            font-size: 0.95rem;
        }
        
        .btn-generate {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            background: #f7fafc;
            color: #4a5568;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .btn-generate:hover {
            background: #e2e8f0;
            color: #2d3748;
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
            <a href="payments.php" class="nav-item">
                <i class="fas fa-credit-card"></i>
                <span>Payments</span>
            </a>
            <a href="reports.php" class="nav-item active">
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
                <h1>Reports & Analytics</h1>
                <p>Generate detailed system reports</p>
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
            <div class="reports-grid">
                <div class="report-card">
                    <div class="report-icon sales">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <h3>Financial Report</h3>
                    <p>Detailed analysis of revenue, deposits, and refunds over time.</p>
                    <a href="#" class="btn-generate" onclick="window.print()">
                        <i class="fas fa-download"></i> Generate PDF
                    </a>
                </div>
                
                <div class="report-card">
                    <div class="report-icon users">
                        <i class="fas fa-users"></i>
                    </div>
                    <h3>User Analytics</h3>
                    <p>Growth tracking, active users, and demographic statistics.</p>
                    <a href="#" class="btn-generate" onclick="window.print()">
                        <i class="fas fa-download"></i> Generate PDF
                    </a>
                </div>
                
                <div class="report-card">
                    <div class="report-icon">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <h3>Order Statistics</h3>
                    <p>Service popularity, order completion rates, and volume data.</p>
                    <a href="#" class="btn-generate" onclick="window.print()">
                        <i class="fas fa-download"></i> Generate PDF
                    </a>
                </div>
                
                <div class="report-card">
                    <div class="report-icon tickets">
                        <i class="fas fa-ticket-alt"></i>
                    </div>
                    <h3>Support Report</h3>
                    <p>Ticket resolution times, common issues, and staff performance.</p>
                    <a href="#" class="btn-generate" onclick="window.print()">
                        <i class="fas fa-download"></i> Generate PDF
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <script src="../assets/js/dashboard.js?v=<?php echo time(); ?>"></script>
    <script src="../assets/js/admin-dashboard.js?v=<?php echo time(); ?>"></script>
</body>
</html>
