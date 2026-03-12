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

// Handle user actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Add New User
    if (isset($_POST['add_user'])) {
        $username = sanitize_input($_POST['username']);
        $email = sanitize_input($_POST['email']);
        $password = $_POST['password'];
        $full_name = sanitize_input($_POST['full_name']);
        $phone = sanitize_input($_POST['phone']);
        $user_role = sanitize_input($_POST['user_role']);
        $balance = isset($_POST['balance']) ? (float)$_POST['balance'] : 0;
        $status = sanitize_input($_POST['status']);
        
        // Validation
        if (empty($username) || empty($email) || empty($password) || empty($full_name)) {
            $errors[] = "All fields are required";
        }
        
        // Check if username exists
        $check_username = mysqli_query($conn, "SELECT id FROM users WHERE username = '$username'");
        if (mysqli_num_rows($check_username) > 0) {
            $errors[] = "Username already exists";
        }
        
        // Check if email exists
        $check_email = mysqli_query($conn, "SELECT id FROM users WHERE email = '$email'");
        if (mysqli_num_rows($check_email) > 0) {
            $errors[] = "Email already exists";
        }
        
        if (empty($errors)) {
            $ip_address = $_SERVER['REMOTE_ADDR'];
            $insert_query = "INSERT INTO users (username, email, password, full_name, phone, user_role, balance, status, ip_address) 
                           VALUES ('$username', '$email', '$password', '$full_name', '$phone', '$user_role', $balance, '$status', '$ip_address')";
            
            if (mysqli_query($conn, $insert_query)) {
                $new_user_id = mysqli_insert_id($conn);
                
                // Log activity
                mysqli_query($conn, "INSERT INTO activity_logs (user_id, action, description, ip_address) 
                                   VALUES ($admin_id, 'User Created', 'Created new user: $username', '$ip_address')");
                
                $success = "User created successfully!";
            } else {
                $errors[] = "Failed to create user";
            }
        }
    }
    
    // Update User
    if (isset($_POST['update_user'])) {
        $user_id = (int)$_POST['user_id'];
        $email = sanitize_input($_POST['email']);
        $full_name = sanitize_input($_POST['full_name']);
        $phone = sanitize_input($_POST['phone']);
        $user_role = sanitize_input($_POST['user_role']);
        $balance = (float)$_POST['balance'];
        $status = sanitize_input($_POST['status']);
        $password = $_POST['password'];
        
        $password_update = '';
        if (!empty($password)) {
            $password_update = ", password = '$password'";
        }
        
        $update_query = "UPDATE users SET 
                        email = '$email',
                        full_name = '$full_name',
                        phone = '$phone',
                        user_role = '$user_role',
                        balance = $balance,
                        status = '$status'
                        $password_update
                        WHERE id = $user_id";
        
        if (mysqli_query($conn, $update_query)) {
            // Log activity
            $ip_address = $_SERVER['REMOTE_ADDR'];
            mysqli_query($conn, "INSERT INTO activity_logs (user_id, action, description, ip_address) 
                               VALUES ($admin_id, 'User Updated', 'Updated user ID: $user_id', '$ip_address')");
            
            $success = "User updated successfully!";
        } else {
            $errors[] = "Failed to update user";
        }
    }
    
    // Delete User
    if (isset($_POST['delete_user'])) {
        $user_id = (int)$_POST['user_id'];
        
        // Don't allow deleting admin
        $check_admin = mysqli_query($conn, "SELECT user_role FROM users WHERE id = $user_id");
        $user_to_delete = mysqli_fetch_assoc($check_admin);
        
        if ($user_to_delete['user_role'] === 'admin') {
            $errors[] = "Cannot delete admin users";
        } else {
            $delete_query = "DELETE FROM users WHERE id = $user_id";
            
            if (mysqli_query($conn, $delete_query)) {
                // Log activity
                $ip_address = $_SERVER['REMOTE_ADDR'];
                mysqli_query($conn, "INSERT INTO activity_logs (user_id, action, description, ip_address) 
                                   VALUES ($admin_id, 'User Deleted', 'Deleted user ID: $user_id', '$ip_address')");
                
                $success = "User deleted successfully!";
            } else {
                $errors[] = "Failed to delete user";
            }
        }
    }
}

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 15;
$offset = ($page - 1) * $per_page;

// Filters
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

// Count total users
$count_query = mysqli_query($conn, "SELECT COUNT(*) as total FROM users WHERE $where_sql");
$total_users = mysqli_fetch_assoc($count_query)['total'];
$total_pages = ceil($total_users / $per_page);

// Get users
$users_query = mysqli_query($conn, "
    SELECT * FROM users 
    WHERE $where_sql
    ORDER BY created_at DESC 
    LIMIT $per_page OFFSET $offset
");

// Get statistics
$stats_query = mysqli_query($conn, "
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN user_role = 'user' THEN 1 ELSE 0 END) as regular_users,
        SUM(CASE WHEN user_role = 'reseller' THEN 1 ELSE 0 END) as resellers,
        SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active,
        SUM(CASE WHEN status = 'banned' THEN 1 ELSE 0 END) as banned,
        SUM(balance) as total_balance
    FROM users 
    WHERE user_role != 'admin'
");
$stats = mysqli_fetch_assoc($stats_query);

// Get unread notifications
$unread_notifications = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM notifications WHERE user_id = $admin_id AND is_read = 0"));

// Get pending orders
$pending_orders = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM orders WHERE status = 'pending'"));

// Get open tickets
$open_tickets = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM support_tickets WHERE status != 'closed'"));

$page_title = "Users Management";
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
    <link rel="stylesheet" href="../assets/css/admin-users.css?v=<?php echo time(); ?>">
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
            <a href="users.php" class="nav-item active">
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
                <h1>Users Management</h1>
                <p>Manage all platform users</p>
            </div>

            <div class="header-actions">
                <button class="btn-add-user" onclick="openAddUserModal()">
                    <i class="fas fa-user-plus"></i> Add New User
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

        <!-- Users Content -->
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

            <!-- Users Statistics -->
            <div class="users-stats-grid">
                <div class="stat-card blue">
                    <div class="stat-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-details">
                        <h3><?php echo number_format($stats['total']); ?></h3>
                        <p>Total Users</p>
                    </div>
                </div>

                <div class="stat-card green">
                    <div class="stat-icon">
                        <i class="fas fa-user"></i>
                    </div>
                    <div class="stat-details">
                        <h3><?php echo number_format($stats['regular_users']); ?></h3>
                        <p>Regular Users</p>
                    </div>
                </div>

                <div class="stat-card purple">
                    <div class="stat-icon">
                        <i class="fas fa-user-tie"></i>
                    </div>
                    <div class="stat-details">
                        <h3><?php echo number_format($stats['resellers']); ?></h3>
                        <p>Resellers</p>
                    </div>
                </div>

                <div class="stat-card orange">
                    <div class="stat-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-details">
                        <h3><?php echo number_format($stats['active']); ?></h3>
                        <p>Active Users</p>
                    </div>
                </div>

                <div class="stat-card red">
                    <div class="stat-icon">
                        <i class="fas fa-ban"></i>
                    </div>
                    <div class="stat-details">
                        <h3><?php echo number_format($stats['banned']); ?></h3>
                        <p>Banned Users</p>
                    </div>
                </div>

                <div class="stat-card teal">
                    <div class="stat-icon">
                        <i class="fas fa-wallet"></i>
                    </div>
                    <div class="stat-details">
                        <h3>$<?php echo number_format($stats['total_balance'], 2); ?></h3>
                        <p>Total Balance</p>
                    </div>
                </div>
            </div>

            <!-- Filters and Search -->
            <div class="users-toolbar">
                <div class="filters-group">
                    <div class="filter-item">
                        <label><i class="fas fa-filter"></i> Role:</label>
                        <select id="roleFilter" class="filter-select" onchange="applyFilters()">
                            <option value="">All Roles</option>
                            <option value="user" <?php echo $role_filter === 'user' ? 'selected' : ''; ?>>Regular User</option>
                            <option value="reseller" <?php echo $role_filter === 'reseller' ? 'selected' : ''; ?>>Reseller</option>
                        </select>
                    </div>

                    <div class="filter-item">
                        <label><i class="fas fa-toggle-on"></i> Status:</label>
                        <select id="statusFilter" class="filter-select" onchange="applyFilters()">
                            <option value="">All Status</option>
                            <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>Active</option>
                            <option value="inactive" <?php echo $status_filter === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                            <option value="banned" <?php echo $status_filter === 'banned' ? 'selected' : ''; ?>>Banned</option>
                        </select>
                    </div>
                </div>

                <div class="search-actions">
                    <div class="search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" 
                               id="searchInput" 
                               placeholder="Search users..." 
                               value="<?php echo htmlspecialchars($search); ?>"
                               onkeyup="handleSearch(event)">
                    </div>
                    <button class="btn-export" onclick="window.print()">
                        <i class="fas fa-file-pdf"></i> Export PDF
                    </button>
                </div>
            </div>

            <!-- Users Table -->
            <div class="users-table-card">
                <?php if ($total_users > 0): ?>
                    <div class="table-responsive">
                        <table class="users-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>User</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>Role</th>
                                    <th>Balance</th>
                                    <th>Status</th>
                                    <th>Joined</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($user = mysqli_fetch_assoc($users_query)): ?>
                                    <tr>
                                        <td><strong>#<?php echo $user['id']; ?></strong></td>
                                        <td>
                                            <div class="user-cell">
                                                <div class="user-avatar-small">
                                                    <i class="fas fa-user"></i>
                                                </div>
                                                <div>
                                                    <strong><?php echo htmlspecialchars($user['full_name']); ?></strong>
                                                    <small>@<?php echo htmlspecialchars($user['username']); ?></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                                        <td><?php echo htmlspecialchars($user['phone']) ?: '-'; ?></td>
                                        <td>
                                            <span class="role-badge <?php echo $user['user_role']; ?>">
                                                <i class="fas fa-<?php echo $user['user_role'] === 'reseller' ? 'user-tie' : 'user'; ?>"></i>
                                                <?php echo ucfirst($user['user_role']); ?>
                                            </span>
                                        </td>
                                        <td><strong class="balance-amount">$<?php echo number_format($user['balance'], 2); ?></strong></td>
                                        <td>
                                            <?php
                                            $status_classes = [
                                                'active' => 'success',
                                                'inactive' => 'warning',
                                                'banned' => 'danger'
                                            ];
                                            $class = $status_classes[$user['status']] ?? 'secondary';
                                            ?>
                                            <span class="status-badge <?php echo $class; ?>">
                                                <i class="fas fa-circle"></i>
                                                <?php echo ucfirst($user['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="date-cell">
                                                <span><?php echo date('M d, Y', strtotime($user['created_at'])); ?></span>
                                                <small><?php echo date('h:i A', strtotime($user['created_at'])); ?></small>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <button class="btn-action view" onclick="viewUser(<?php echo $user['id']; ?>)" title="View Details">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button class="btn-action edit" onclick="editUser(<?php echo $user['id']; ?>)" title="Edit User">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="btn-action danger" onclick="deleteUser(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['username']); ?>')" title="Delete User">
                                                    <i class="fas fa-trash"></i>
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
                                <a href="?page=<?php echo $page - 1; ?><?php echo $role_filter ? '&role=' . $role_filter : ''; ?><?php echo $status_filter ? '&status=' . $status_filter : ''; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>" class="page-link">
                                    <i class="fas fa-chevron-left"></i> Previous
                                </a>
                            <?php endif; ?>

                            <div class="page-numbers">
                                <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                    <a href="?page=<?php echo $i; ?><?php echo $role_filter ? '&role=' . $role_filter : ''; ?><?php echo $status_filter ? '&status=' . $status_filter : ''; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>" 
                                       class="page-link <?php echo $i === $page ? 'active' : ''; ?>">
                                        <?php echo $i; ?>
                                    </a>
                                <?php endfor; ?>
                            </div>

                            <?php if ($page < $total_pages): ?>
                                <a href="?page=<?php echo $page + 1; ?><?php echo $role_filter ? '&role=' . $role_filter : ''; ?><?php echo $status_filter ? '&status=' . $status_filter : ''; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>" class="page-link">
                                    Next <i class="fas fa-chevron-right"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                <?php else: ?>
                    <div class="empty-users">
                        <i class="fas fa-users-slash"></i>
                        <h3>No Users Found</h3>
                        <p>No users match your search criteria.</p>
                        <a href="users.php" class="btn-primary">
                            <i class="fas fa-redo"></i> Reset Filters
                        </a>
                    </div>
                <?php endif; ?>
            </div>

        </div>
    </div>

    <!-- Add User Modal -->
    <div id="addUserModal" class="modal">
        <div class="modal-content large">
            <div class="modal-header">
                <h2><i class="fas fa-user-plus"></i> Add New User</h2>
                <button class="modal-close" onclick="closeAddUserModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <form method="POST" action="" id="addUserForm">
                    <div class="form-row">
                        <div class="form-group">
                            <label><i class="fas fa-user"></i> Username *</label>
                            <input type="text" name="username" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-envelope"></i> Email *</label>
                            <input type="email" name="email" class="form-control" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label><i class="fas fa-id-card"></i> Full Name *</label>
                            <input type="text" name="full_name" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-phone"></i> Phone</label>
                            <input type="tel" name="phone" class="form-control">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label><i class="fas fa-lock"></i> Password *</label>
                            <input type="password" name="password" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-user-tag"></i> Role *</label>
                            <select name="user_role" class="form-control" required>
                                <option value="user">Regular User</option>
                                <option value="reseller">Reseller</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label><i class="fas fa-wallet"></i> Initial Balance</label>
                            <input type="number" name="balance" class="form-control" value="0" min="0" step="0.01">
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-toggle-on"></i> Status *</label>
                            <select name="status" class="form-control" required>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                                <option value="banned">Banned</option>
                            </select>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn-secondary" onclick="closeAddUserModal()">Cancel</button>
                        <button type="submit" name="add_user" class="btn-primary">
                            <i class="fas fa-plus"></i> Create User
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit User Modal -->
    <div id="editUserModal" class="modal">
        <div class="modal-content large">
            <div class="modal-header">
                <h2><i class="fas fa-user-edit"></i> Edit User</h2>
                <button class="modal-close" onclick="closeEditUserModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body" id="editUserContent">
                <div class="modal-loading">
                    <i class="fas fa-spinner fa-spin"></i>
                    <p>Loading user details...</p>
                </div>
            </div>
        </div>
    </div>

    <!-- View User Modal -->
    <div id="viewUserModal" class="modal">
        <div class="modal-content large">
            <div class="modal-header">
                <h2><i class="fas fa-user"></i> User Details</h2>
                <button class="modal-close" onclick="closeViewUserModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body" id="viewUserContent">
                <div class="modal-loading">
                    <i class="fas fa-spinner fa-spin"></i>
                    <p>Loading user details...</p>
                </div>
            </div>
        </div>
    </div>

    <script src="../assets/js/dashboard.js"></script>
    <script src="../assets/js/admin-users.js"></script>
</body>
</html>