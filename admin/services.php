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

// Handle service actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Add New Service
    if (isset($_POST['add_service'])) {
        $name = sanitize_input($_POST['name']);
        $category = sanitize_input($_POST['category']);
        $description = sanitize_input($_POST['description']);
        $min_quantity = (int)$_POST['min_quantity'];
        $max_quantity = (int)$_POST['max_quantity'];
        $price_per_1000 = (float)$_POST['price_per_1000'];
        $reseller_price_per_1000 = (float)$_POST['reseller_price_per_1000'];
        $delivery_time = sanitize_input($_POST['delivery_time']);
        $icon = sanitize_input($_POST['icon']);
        $status = sanitize_input($_POST['status']);
        
        // Validation
        if (empty($name) || empty($category) || empty($description)) {
            $errors[] = "All required fields must be filled";
        }
        
        if ($min_quantity <= 0 || $max_quantity <= 0 || $min_quantity > $max_quantity) {
            $errors[] = "Invalid quantity values";
        }
        
        if ($price_per_1000 <= 0 || $reseller_price_per_1000 <= 0) {
            $errors[] = "Invalid price values";
        }
        
        if (empty($errors)) {
            $insert_query = "INSERT INTO services (name, category, description, min_quantity, max_quantity, price_per_1000, reseller_price_per_1000, delivery_time, icon, status) 
                           VALUES ('$name', '$category', '$description', $min_quantity, $max_quantity, $price_per_1000, $reseller_price_per_1000, '$delivery_time', '$icon', '$status')";
            
            if (mysqli_query($conn, $insert_query)) {
                // Log activity
                $ip_address = $_SERVER['REMOTE_ADDR'];
                mysqli_query($conn, "INSERT INTO activity_logs (user_id, action, description, ip_address) 
                                   VALUES ($admin_id, 'Service Created', 'Created new service: $name', '$ip_address')");
                
                $success = "Service created successfully!";
            } else {
                $errors[] = "Failed to create service";
            }
        }
    }
    
    // Update Service
    if (isset($_POST['update_service'])) {
        $service_id = (int)$_POST['service_id'];
        $name = sanitize_input($_POST['name']);
        $category = sanitize_input($_POST['category']);
        $description = sanitize_input($_POST['description']);
        $min_quantity = (int)$_POST['min_quantity'];
        $max_quantity = (int)$_POST['max_quantity'];
        $price_per_1000 = (float)$_POST['price_per_1000'];
        $reseller_price_per_1000 = (float)$_POST['reseller_price_per_1000'];
        $delivery_time = sanitize_input($_POST['delivery_time']);
        $icon = sanitize_input($_POST['icon']);
        $status = sanitize_input($_POST['status']);
        
        $update_query = "UPDATE services SET 
                        name = '$name',
                        category = '$category',
                        description = '$description',
                        min_quantity = $min_quantity,
                        max_quantity = $max_quantity,
                        price_per_1000 = $price_per_1000,
                        reseller_price_per_1000 = $reseller_price_per_1000,
                        delivery_time = '$delivery_time',
                        icon = '$icon',
                        status = '$status',
                        updated_at = NOW()
                        WHERE id = $service_id";
        
        if (mysqli_query($conn, $update_query)) {
            // Log activity
            $ip_address = $_SERVER['REMOTE_ADDR'];
            mysqli_query($conn, "INSERT INTO activity_logs (user_id, action, description, ip_address) 
                               VALUES ($admin_id, 'Service Updated', 'Updated service: $name', '$ip_address')");
            
            $success = "Service updated successfully!";
        } else {
            $errors[] = "Failed to update service";
        }
    }
    
    // Delete Service
    if (isset($_POST['delete_service'])) {
        $service_id = (int)$_POST['service_id'];
        
        // Check if service has orders
        $orders_check = mysqli_query($conn, "SELECT COUNT(*) as count FROM orders WHERE service_id = $service_id");
        $orders_count = mysqli_fetch_assoc($orders_check)['count'];
        
        if ($orders_count > 0) {
            $errors[] = "Cannot delete service with existing orders. Set status to inactive instead.";
        } else {
            $delete_query = "DELETE FROM services WHERE id = $service_id";
            
            if (mysqli_query($conn, $delete_query)) {
                // Log activity
                $ip_address = $_SERVER['REMOTE_ADDR'];
                mysqli_query($conn, "INSERT INTO activity_logs (user_id, action, description, ip_address) 
                                   VALUES ($admin_id, 'Service Deleted', 'Deleted service ID: $service_id', '$ip_address')");
                
                $success = "Service deleted successfully!";
            } else {
                $errors[] = "Failed to delete service";
            }
        }
    }
}

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 15;
$offset = ($page - 1) * $per_page;

// Filters
$category_filter = isset($_GET['category']) ? sanitize_input($_GET['category']) : '';
$status_filter = isset($_GET['status']) ? sanitize_input($_GET['status']) : '';
$search = isset($_GET['search']) ? sanitize_input($_GET['search']) : '';

$where_clauses = ['1=1'];

if ($category_filter) {
    $where_clauses[] = "category = '$category_filter'";
}

if ($status_filter) {
    $where_clauses[] = "status = '$status_filter'";
}

if ($search) {
    $where_clauses[] = "(name LIKE '%$search%' OR description LIKE '%$search%')";
}

$where_sql = implode(' AND ', $where_clauses);

// Count total services
$count_query = mysqli_query($conn, "SELECT COUNT(*) as total FROM services WHERE $where_sql");
$total_services = mysqli_fetch_assoc($count_query)['total'];
$total_pages = ceil($total_services / $per_page);

// Get services
$services_query = mysqli_query($conn, "
    SELECT * FROM services 
    WHERE $where_sql
    ORDER BY category, name ASC 
    LIMIT $per_page OFFSET $offset
");

// Get statistics
$stats_query = mysqli_query($conn, "
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active,
        SUM(CASE WHEN status = 'inactive' THEN 1 ELSE 0 END) as inactive,
        COUNT(DISTINCT category) as categories
    FROM services
");
$stats = mysqli_fetch_assoc($stats_query);

// Get all categories
$categories_query = mysqli_query($conn, "SELECT DISTINCT category FROM services ORDER BY category");
$categories = [];
while ($cat = mysqli_fetch_assoc($categories_query)) {
    $categories[] = $cat['category'];
}

// Get unread notifications
$unread_notifications = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM notifications WHERE user_id = $admin_id AND is_read = 0"));

// Get pending orders
$pending_orders = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM orders WHERE status = 'pending'"));

// Get open tickets
$open_tickets = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM support_tickets WHERE status != 'closed'"));

$page_title = "Services Management";
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
    <link rel="stylesheet" href="../assets/css/admin-services.css?v=<?php echo time(); ?>">
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
            <a href="services.php" class="nav-item active">
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
                <h1>Services Management</h1>
                <p>Manage all SMM services and pricing</p>
            </div>

            <div class="header-actions">
                <button class="btn-add-service" onclick="openAddServiceModal()">
                    <i class="fas fa-plus-circle"></i> Add New Service
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

        <!-- Services Content -->
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

            <!-- Services Statistics -->
            <div class="services-stats-grid">
                <div class="stat-card blue">
                    <div class="stat-icon">
                        <i class="fas fa-list"></i>
                    </div>
                    <div class="stat-details">
                        <h3><?php echo number_format($stats['total']); ?></h3>
                        <p>Total Services</p>
                    </div>
                </div>

                <div class="stat-card green">
                    <div class="stat-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-details">
                        <h3><?php echo number_format($stats['active']); ?></h3>
                        <p>Active Services</p>
                    </div>
                </div>

                <div class="stat-card orange">
                    <div class="stat-icon">
                        <i class="fas fa-pause-circle"></i>
                    </div>
                    <div class="stat-details">
                        <h3><?php echo number_format($stats['inactive']); ?></h3>
                        <p>Inactive Services</p>
                    </div>
                </div>

                <div class="stat-card purple">
                    <div class="stat-icon">
                        <i class="fas fa-layer-group"></i>
                    </div>
                    <div class="stat-details">
                        <h3><?php echo number_format($stats['categories']); ?></h3>
                        <p>Categories</p>
                    </div>
                </div>
            </div>

            <!-- Filters and Search -->
            <div class="services-toolbar">
                <div class="filters-group">
                    <div class="filter-item">
                        <label><i class="fas fa-filter"></i> Category:</label>
                        <select id="categoryFilter" class="filter-select" onchange="applyFilters()">
                            <option value="">All Categories</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo htmlspecialchars($category); ?>" <?php echo $category_filter === $category ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($category); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="filter-item">
                        <label><i class="fas fa-toggle-on"></i> Status:</label>
                        <select id="statusFilter" class="filter-select" onchange="applyFilters()">
                            <option value="">All Status</option>
                            <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>Active</option>
                            <option value="inactive" <?php echo $status_filter === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                        </select>
                    </div>
                </div>

                <div class="search-actions">
                    <div class="search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" 
                               id="searchInput" 
                               placeholder="Search services..." 
                               value="<?php echo htmlspecialchars($search); ?>"
                               onkeyup="handleSearch(event)">
                    </div>
                    <button class="btn-export" onclick="window.print()">
                        <i class="fas fa-file-pdf"></i> Export PDF
                    </button>
                </div>
            </div>

            <!-- Services Grid -->
            <?php if ($total_services > 0): ?>
                <div class="services-grid">
                    <?php while ($service = mysqli_fetch_assoc($services_query)): ?>
                        <div class="service-card">
                            <div class="service-card-header">
                                <div class="service-icon-large">
                                    <i class="fas <?php echo htmlspecialchars($service['icon']); ?>"></i>
                                </div>
                                <span class="category-badge-large"><?php echo htmlspecialchars($service['category']); ?></span>
                                <span class="status-badge-corner <?php echo $service['status'] === 'active' ? 'success' : 'warning'; ?>">
                                    <i class="fas fa-circle"></i>
                                </span>
                            </div>

                            <div class="service-card-body">
                                <h3 class="service-title"><?php echo htmlspecialchars($service['name']); ?></h3>
                                <p class="service-description"><?php echo htmlspecialchars($service['description']); ?></p>

                                <div class="service-details-grid">
                                    <div class="detail-item">
                                        <i class="fas fa-clock"></i>
                                        <div>
                                            <small>Delivery</small>
                                            <strong><?php echo htmlspecialchars($service['delivery_time']); ?></strong>
                                        </div>
                                    </div>

                                    <div class="detail-item">
                                        <i class="fas fa-arrow-down"></i>
                                        <div>
                                            <small>Min Qty</small>
                                            <strong><?php echo number_format($service['min_quantity']); ?></strong>
                                        </div>
                                    </div>

                                    <div class="detail-item">
                                        <i class="fas fa-arrow-up"></i>
                                        <div>
                                            <small>Max Qty</small>
                                            <strong><?php echo number_format($service['max_quantity']); ?></strong>
                                        </div>
                                    </div>
                                </div>

                                <div class="service-pricing">
                                    <div class="price-row">
                                        <span class="price-label">
                                            <i class="fas fa-user"></i> User Price:
                                        </span>
                                        <span class="price-value">${<?php echo number_format($service['price_per_1000'], 2); ?>/1k</span>
                                    </div>
                                    <div class="price-row">
                                        <span class="price-label">
                                            <i class="fas fa-user-tie"></i> Reseller:
                                        </span>
                                        <span class="price-value reseller">${<?php echo number_format($service['reseller_price_per_1000'], 2); ?>/1k</span>
                                    </div>
                                </div>
                            </div>

                            <div class="service-card-footer">
                                <button class="btn-action view" onclick="viewService(<?php echo $service['id']; ?>)" title="View Details">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button class="btn-action edit" onclick="editService(<?php echo $service['id']; ?>)" title="Edit Service">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn-action danger" onclick="deleteService(<?php echo $service['id']; ?>, '<?php echo htmlspecialchars($service['name']); ?>')" title="Delete Service">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                            <a href="?page=<?php echo $page - 1; ?><?php echo $category_filter ? '&category=' . urlencode($category_filter) : ''; ?><?php echo $status_filter ? '&status=' . $status_filter : ''; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>" class="page-link">
                                <i class="fas fa-chevron-left"></i> Previous
                            </a>
                        <?php endif; ?>

                        <div class="page-numbers">
                            <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                <a href="?page=<?php echo $i; ?><?php echo $category_filter ? '&category=' . urlencode($category_filter) : ''; ?><?php echo $status_filter ? '&status=' . $status_filter : ''; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>" 
                                   class="page-link <?php echo $i === $page ? 'active' : ''; ?>">
                                    <?php echo $i; ?>
                                </a>
                            <?php endfor; ?>
                        </div>

                        <?php if ($page < $total_pages): ?>
                            <a href="?page=<?php echo $page + 1; ?><?php echo $category_filter ? '&category=' . urlencode($category_filter) : ''; ?><?php echo $status_filter ? '&status=' . $status_filter : ''; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>" class="page-link">
                                Next <i class="fas fa-chevron-right"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

            <?php else: ?>
                <div class="empty-services">
                    <i class="fas fa-box-open"></i>
                    <h3>No Services Found</h3>
                    <p>No services match your search criteria.</p>
                    <button class="btn-primary" onclick="openAddServiceModal()">
                        <i class="fas fa-plus-circle"></i> Add First Service
                    </button>
                </div>
            <?php endif; ?>

        </div>
    </div>

    <!-- Add/Edit Service Modal -->
    <div id="serviceModal" class="modal">
        <div class="modal-content large">
            <div class="modal-header">
                <h2 id="modalTitle"><i class="fas fa-plus-circle"></i> Add New Service</h2>
                <button class="modal-close" onclick="closeServiceModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body" id="serviceModalBody">
                <!-- Form will be loaded here -->
            </div>
        </div>
    </div>

    <!-- View Service Modal -->
    <div id="viewServiceModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-eye"></i> Service Details</h2>
                <button class="modal-close" onclick="closeViewServiceModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body" id="viewServiceContent">
                <!-- Details will be loaded here -->
            </div>
        </div>
    </div>

    <script src="../assets/js/dashboard.js"></script>
    <script src="../assets/js/admin-services.js"></script>
</body>
</html>