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

$page_title = "Panel Settings";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - SMM Panel Admin</title>
    <link rel="stylesheet" href="../assets/css/styles.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../assets/css/dashboard.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../assets/css/admin-dashboard.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .settings-container {
            display: grid;
            grid-template-columns: 250px 1fr;
            gap: 2rem;
            margin-bottom: 2rem;
        }
        
        @media (max-width: 768px) {
            .settings-container {
                grid-template-columns: 1fr;
            }
        }
        
        .settings-nav {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            overflow: hidden;
            align-self: start;
        }
        
        .settings-nav a {
            display: block;
            padding: 1rem 1.5rem;
            color: #4a5568;
            text-decoration: none;
            font-weight: 600;
            border-left: 3px solid transparent;
            transition: all 0.3s ease;
        }
        
        .settings-nav a:hover, .settings-nav a.active {
            background: #f7fafc;
            color: #667eea;
            border-left-color: #667eea;
        }
        
        .settings-nav a i {
            margin-right: 0.5rem;
            width: 20px;
            text-align: center;
        }
        
        .settings-content-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            padding: 2rem;
        }
        
        .settings-content-card h2 {
            margin-top: 0;
            margin-bottom: 1.5rem;
            color: #2d3748;
            font-size: 1.5rem;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 0.5rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #4a5568;
        }
        
        .form-group input, .form-group select {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 1rem;
            color: #2d3748;
            transition: all 0.3s ease;
        }
        
        .form-group input:focus, .form-group select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .btn-save {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 0.75rem 2rem;
            border-radius: 8px;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-save:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
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
            <a href="reports.php" class="nav-item">
                <i class="fas fa-chart-bar"></i>
                <span>Reports</span>
            </a>
            <a href="settings.php" class="nav-item active">
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
                <h1>Platform Settings</h1>
                <p>Configure panel operations</p>
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
            <div class="settings-container">
                <nav class="settings-nav">
                    <a href="#" class="active"><i class="fas fa-cog"></i> General</a>
                    <a href="#"><i class="fas fa-money-bill-wave"></i> Payment Gateways</a>
                    <a href="#"><i class="fas fa-envelope"></i> SMTP Settings</a>
                    <a href="#"><i class="fas fa-code-branch"></i> Providers API</a>
                    <a href="#"><i class="fas fa-paint-brush"></i> Theme</a>
                </nav>
                
                <div class="settings-content">
                    <div class="settings-content-card">
                        <h2>General Information</h2>
                        
                        <form action="" method="POST" onsubmit="event.preventDefault(); showToast('Settings Updated (Demo)', 'success');">
                            <div class="form-group">
                                <label>Site Name</label>
                                <input type="text" value="SMM Panel">
                            </div>
                            
                            <div class="form-group">
                                <label>Currency Symbol</label>
                                <input type="text" value="$">
                            </div>
                            
                            <div class="form-group">
                                <label>Timezone</label>
                                <select>
                                    <option value="UTC">UTC</option>
                                    <option value="Asia/Colombo" selected>Asia/Colombo</option>
                                    <option value="America/New_York">America/New_York</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label>Minimum Deposit ($)</label>
                                <input type="number" step="0.01" value="10.00">
                            </div>
                            
                            <button class="btn-save" type="submit"><i class="fas fa-save"></i> Save Changes</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="../assets/js/dashboard.js?v=<?php echo time(); ?>"></script>
    <script src="../assets/js/admin-dashboard.js?v=<?php echo time(); ?>"></script>
</body>
</html>
