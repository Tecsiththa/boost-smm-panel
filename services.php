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

// Category filter
$category_filter = isset($_GET['category']) ? sanitize_input($_GET['category']) : '';
$category_where = $category_filter ? "AND category = '$category_filter'" : '';

// Search
$search = isset($_GET['search']) ? sanitize_input($_GET['search']) : '';
$search_where = $search ? "AND (name LIKE '%$search%' OR description LIKE '%$search%')" : '';

// Get all categories
$categories_query = mysqli_query($conn, "SELECT DISTINCT category FROM services WHERE status = 'active' ORDER BY category");
$categories = [];
while ($cat = mysqli_fetch_assoc($categories_query)) {
    $categories[] = $cat['category'];
}

// Get services
$services_query = mysqli_query($conn, "
    SELECT * FROM services 
    WHERE status = 'active' 
    $category_where 
    $search_where
    ORDER BY category, name ASC
");

// Group services by category
$services_by_category = [];
while ($service = mysqli_fetch_assoc($services_query)) {
    $category = $service['category'];
    if (!isset($services_by_category[$category])) {
        $services_by_category[$category] = [];
    }
    $services_by_category[$category][] = $service;
}

// Count services
$total_services = 0;
foreach ($services_by_category as $services) {
    $total_services += count($services);
}

// Get notifications count
$unread_notifications = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM notifications WHERE user_id = $user_id AND is_read = 0"));

// Get active tickets count
$active_tickets = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM support_tickets WHERE user_id = $user_id AND status != 'closed'"));

$page_title = "Services";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - SMM Panel</title>
    <link rel="stylesheet" href="assets/css/styles.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="assets/css/dashboard.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="assets/css/services.css?v=<?php echo time(); ?>">
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
            <a href="services.php" class="nav-item active">
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
                <h1>Our Services</h1>
                <p>Browse all available social media marketing services</p>
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

        <!-- Services Content -->
        <div class="dashboard-content">
            
            <!-- Services Header -->
            <div class="services-header">
                <div class="services-stats">
                    <div class="stat-item">
                        <i class="fas fa-layer-group"></i>
                        <div>
                            <h3><?php echo count($categories); ?></h3>
                            <p>Categories</p>
                        </div>
                    </div>
                    <div class="stat-item">
                        <i class="fas fa-star"></i>
                        <div>
                            <h3><?php echo $total_services; ?></h3>
                            <p>Services Available</p>
                        </div>
                    </div>
                    <div class="stat-item">
                        <i class="fas fa-bolt"></i>
                        <div>
                            <h3>Instant</h3>
                            <p>Delivery Start</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Category Tabs & Search -->
            <div class="services-toolbar">
                <div class="category-tabs">
                    <a href="services.php" class="tab-item <?php echo !$category_filter ? 'active' : ''; ?>">
                        <i class="fas fa-th"></i> All Services
                    </a>
                    <?php foreach ($categories as $cat): ?>
                        <a href="services.php?category=<?php echo urlencode($cat); ?>" 
                           class="tab-item <?php echo $category_filter === $cat ? 'active' : ''; ?>">
                            <?php
                            $icons = [
                                'Instagram' => 'fa-instagram',
                                'Facebook' => 'fa-facebook-f',
                                'YouTube' => 'fa-youtube',
                                'Twitter' => 'fa-twitter',
                                'TikTok' => 'fa-tiktok',
                                'LinkedIn' => 'fa-linkedin-in',
                                'Snapchat' => 'fa-snapchat-ghost',
                                'Pinterest' => 'fa-pinterest-p',
                                'Reddit' => 'fa-reddit-alien',
                                'Discord' => 'fa-discord',
                                'Telegram' => 'fa-telegram-plane',
                                'WhatsApp' => 'fa-whatsapp',
                                'Twitch' => 'fa-twitch'
                            ];
                            $icon = $icons[$cat] ?? 'fa-star';
                            ?>
                            <i class="fab <?php echo $icon; ?>"></i> <?php echo htmlspecialchars($cat); ?>
                        </a>
                    <?php endforeach; ?>
                </div>

                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" 
                           id="searchInput" 
                           placeholder="Search services..." 
                           value="<?php echo htmlspecialchars($search); ?>">
                </div>
            </div>

            <!-- Services Grid -->
            <?php if (!empty($services_by_category)): ?>
                <?php foreach ($services_by_category as $category => $services): ?>
                    <div class="category-section">
                        <div class="category-header">
                            <?php
                            $category_icons = [
                                'Instagram' => 'fa-instagram',
                                'Facebook' => 'fa-facebook-f',
                                'YouTube' => 'fa-youtube',
                                'Twitter' => 'fa-twitter',
                                'TikTok' => 'fa-tiktok',
                                'LinkedIn' => 'fa-linkedin-in',
                                'Snapchat' => 'fa-snapchat-ghost',
                                'Pinterest' => 'fa-pinterest-p',
                                'Reddit' => 'fa-reddit-alien',
                                'Discord' => 'fa-discord',
                                'Telegram' => 'fa-telegram-plane',
                                'WhatsApp' => 'fa-whatsapp',
                                'Twitch' => 'fa-twitch'
                            ];
                            $cat_icon = $category_icons[$category] ?? 'fa-star';
                            ?>
                            <i class="fab <?php echo $cat_icon; ?>"></i>
                            <h2><?php echo htmlspecialchars($category); ?> Services</h2>
                            <span class="count">(<?php echo count($services); ?> services)</span>
                        </div>

                        <div class="services-grid">
                            <?php foreach ($services as $service): ?>
                                <?php
                                // Get price based on user role
                                $price = ($user['user_role'] === 'reseller') 
                                    ? $service['reseller_price_per_1000'] 
                                    : $service['price_per_1000'];
                                ?>
                                <div class="service-card" data-service-id="<?php echo $service['id']; ?>">
                                    <div class="service-card-header">
                                        <div class="service-icon-large">
                                            <i class="fas <?php echo htmlspecialchars($service['icon']); ?>"></i>
                                        </div>
                                        <span class="service-category-badge">
                                            <?php echo htmlspecialchars($category); ?>
                                        </span>
                                    </div>

                                    <div class="service-card-body">
                                        <h3 class="service-name"><?php echo htmlspecialchars($service['name']); ?></h3>
                                        <p class="service-description">
                                            <?php echo htmlspecialchars($service['description']); ?>
                                        </p>

                                        <div class="service-details">
                                            <div class="detail-item">
                                                <i class="fas fa-clock"></i>
                                                <span><?php echo htmlspecialchars($service['delivery_time']); ?></span>
                                            </div>
                                            <div class="detail-item">
                                                <i class="fas fa-arrow-down"></i>
                                                <span>Min: <?php echo number_format($service['min_quantity']); ?></span>
                                            </div>
                                            <div class="detail-item">
                                                <i class="fas fa-arrow-up"></i>
                                                <span>Max: <?php echo number_format($service['max_quantity']); ?></span>
                                            </div>
                                        </div>

                                        <div class="service-pricing">
                                            <div class="price-tag">
                                                <span class="price-label">Price per 1000</span>
                                                <span class="price-value">${<?php echo number_format($price, 2); ?></span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="service-card-footer">
                                        <a href="new-order.php?service=<?php echo $service['id']; ?>" class="btn-order">
                                            <i class="fas fa-shopping-cart"></i> Order Now
                                        </a>
                                        <button class="btn-details" onclick="viewServiceDetails(<?php echo $service['id']; ?>)">
                                            <i class="fas fa-info-circle"></i>
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-services">
                    <i class="fas fa-search"></i>
                    <h3>No Services Found</h3>
                    <p>Try adjusting your search or filter</p>
                    <a href="services.php" class="btn-primary">
                        <i class="fas fa-redo"></i> View All Services
                    </a>
                </div>
            <?php endif; ?>

        </div>
    </div>

    <!-- Service Details Modal -->
    <div id="serviceModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-star"></i> Service Details</h2>
                <button class="modal-close" onclick="closeServiceModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body" id="serviceModalBody">
                <div class="modal-loading">
                    <i class="fas fa-spinner fa-spin"></i>
                    <p>Loading service details...</p>
                </div>
            </div>
        </div>
    </div>

    <script src="assets/js/dashboard.js"></script>
    <script src="assets/js/services.js"></script>
</body>
</html>