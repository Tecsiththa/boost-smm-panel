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
$errors = [];
$success = '';

if (isset($_SESSION['flash_errors'])) {
    $errors = $_SESSION['flash_errors'];
    unset($_SESSION['flash_errors']);
}

if (isset($_SESSION['flash_success'])) {
    $success = $_SESSION['flash_success'];
    unset($_SESSION['flash_success']);
}

// Get user data
$user_query = mysqli_query($conn, "SELECT * FROM users WHERE id = $user_id");
$user = mysqli_fetch_assoc($user_query);

// Get all active services grouped by category
$categories_query = mysqli_query($conn, "SELECT DISTINCT category FROM services WHERE status = 'active' ORDER BY category");
$categories = [];
while ($cat = mysqli_fetch_assoc($categories_query)) {
    $categories[] = $cat['category'];
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $service_id = sanitize_input($_POST['service_id']);
    $link = sanitize_input($_POST['link']);
    $quantity = (int)$_POST['quantity'];
    
    // Validation
    if (empty($service_id)) {
        $errors[] = "Please select a service";
    }
    
    if (empty($link)) {
        $errors[] = "Link is required";
    } elseif (!filter_var($link, FILTER_VALIDATE_URL)) {
        $errors[] = "Please enter a valid URL";
    }
    
    if (empty($quantity) || $quantity <= 0) {
        $errors[] = "Please enter a valid quantity";
    }
    
    if (empty($errors)) {
        // Get service details
        $service_query = mysqli_query($conn, "SELECT * FROM services WHERE id = $service_id AND status = 'active'");
        
        if (mysqli_num_rows($service_query) > 0) {
            $service = mysqli_fetch_assoc($service_query);
            
            // Check quantity limits
            if ($quantity < $service['min_quantity']) {
                $errors[] = "Minimum quantity for this service is " . number_format($service['min_quantity']);
            } elseif ($quantity > $service['max_quantity']) {
                $errors[] = "Maximum quantity for this service is " . number_format($service['max_quantity']);
            } else {
                // Calculate price based on user role
                $price_per_1000 = ($user['user_role'] === 'reseller') ? $service['reseller_price_per_1000'] : $service['price_per_1000'];
                $total_price = ($quantity / 1000) * $price_per_1000;
                
                // Check balance
                if ($user['balance'] < $total_price) {
                    $errors[] = "Insufficient balance. You need $" . number_format($total_price, 2) . " but have $" . number_format($user['balance'], 2);
                } else {
                    // Generate order number
                    $order_number = 'ORD-' . strtoupper(substr(md5(time() . rand()), 0, 10));
                    
                    // Create order
                    $insert_order = "INSERT INTO orders (user_id, service_id, order_number, link, quantity, start_count, remains, price, status) 
                                    VALUES ($user_id, $service_id, '$order_number', '$link', $quantity, 0, $quantity, $total_price, 'pending')";
                    
                    if (mysqli_query($conn, $insert_order)) {
                        $order_id = mysqli_insert_id($conn);
                        
                        // Deduct balance
                        $new_balance = $user['balance'] - $total_price;
                        mysqli_query($conn, "UPDATE users SET balance = $new_balance WHERE id = $user_id");
                        
                        // Create transaction record
                        $transaction_id = 'TXN-' . strtoupper(substr(md5(time() . rand()), 0, 12));
                        $description = "Order placed: " . $service['name'] . " (Qty: " . number_format($quantity) . ")";
                        mysqli_query($conn, "INSERT INTO transactions (user_id, transaction_id, type, amount, balance_before, balance_after, description) 
                                           VALUES ($user_id, '$transaction_id', 'order', $total_price, {$user['balance']}, $new_balance, '$description')");
                        
                        // Create notification
                        $notif_title = "Order Placed Successfully";
                        $notif_message = "Your order #$order_number has been placed and is being processed.";
                        mysqli_query($conn, "INSERT INTO notifications (user_id, title, message, type, link) 
                                           VALUES ($user_id, '$notif_title', '$notif_message', 'success', 'orders.php?id=$order_id')");
                        
                        // Log activity
                        $ip_address = $_SERVER['REMOTE_ADDR'];
                        mysqli_query($conn, "INSERT INTO activity_logs (user_id, action, description, ip_address) 
                                           VALUES ($user_id, 'Order Placed', 'Order #$order_number placed', '$ip_address')");
                        
                        // Update session balance
                        $_SESSION['balance'] = $new_balance;
                        
                        $success = "Order placed successfully! Order ID: $order_number";
                        
                        // Redirect after 2 seconds
                        header("refresh:2;url=orders.php?id=$order_id");
                    } else {
                        $errors[] = "Failed to place order. Please try again.";
                    }
                }
            }
        } else {
            $errors[] = "Invalid service selected";
        }
    }
    
    // PRG Pattern: Store flash messages and redirect
    if (!empty($errors)) {
        $_SESSION['flash_errors'] = $errors;
    }
    if (!empty($success)) {
        $_SESSION['flash_success'] = $success;
    }
    header('Location: new-order.php');
    exit();
}

// Get notifications count
$unread_notifications = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM notifications WHERE user_id = $user_id AND is_read = 0"));

// Get active tickets count
$active_tickets = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM support_tickets WHERE user_id = $user_id AND status != 'closed'"));

$page_title = "New Order";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - SMM Panel</title>
    <link rel="stylesheet" href="assets/css/styles.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="assets/css/dashboard.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="assets/css/new-order.css?v=<?php echo time(); ?>">
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
            <a href="new-order.php" class="nav-item active">
                <i class="fas fa-plus-circle"></i>
                <span>New Order</span>
            </a>
            <a href="orders.php" class="nav-item">
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
                <h1>New Order</h1>
                <p>Place a new order for your social media growth</p>
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

        <!-- Order Content -->
        <div class="dashboard-content">
            
            <!-- Flash Messages using showToast -->
            <?php if (!empty($errors)): ?>
                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        setTimeout(() => {
                            <?php foreach ($errors as $error): ?>
                                showToast("<?php echo addslashes($error); ?>", "error");
                            <?php endforeach; ?>
                        }, 500);
                    });
                </script>
            <?php endif; ?>

            <?php if ($success): ?>
                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        setTimeout(() => {
                            showToast("<?php echo addslashes($success); ?>", "success");
                        }, 500);
                    });
                </script>
            <?php endif; ?>

            <div class="order-container">
                <!-- Order Form -->
                <div class="order-form-card">
                    <div class="card-header">
                        <i class="fas fa-plus-circle"></i>
                        <h2>Place Your Order</h2>
                    </div>

                    <form method="POST" action="" id="orderForm" class="order-form">
                        
                        <!-- Category Selection -->
                        <div class="form-group">
                            <label for="category">
                                <i class="fas fa-folder"></i> Select Category
                            </label>
                            <select id="category" name="category" class="form-control" required>
                                <option value="">Choose a category...</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo htmlspecialchars($category); ?>">
                                        <?php echo htmlspecialchars($category); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Service Selection -->
                        <div class="form-group">
                            <label for="service_id">
                                <i class="fas fa-star"></i> Select Service
                            </label>
                            <select id="service_id" name="service_id" class="form-control" required disabled>
                                <option value="">Select a category first...</option>
                            </select>
                            <div id="serviceInfo" class="service-info" style="display: none;">
                                <div class="info-row">
                                    <span><i class="fas fa-clock"></i> Delivery Time:</span>
                                    <strong id="deliveryTime">-</strong>
                                </div>
                                <div class="info-row">
                                    <span><i class="fas fa-arrow-down"></i> Min Quantity:</span>
                                    <strong id="minQty">-</strong>
                                </div>
                                <div class="info-row">
                                    <span><i class="fas fa-arrow-up"></i> Max Quantity:</span>
                                    <strong id="maxQty">-</strong>
                                </div>
                                <div class="info-row">
                                    <span><i class="fas fa-dollar-sign"></i> Price per 1000:</span>
                                    <strong id="pricePerK">-</strong>
                                </div>
                            </div>
                        </div>

                        <!-- Link Input -->
                        <div class="form-group">
                            <label for="link">
                                <i class="fas fa-link"></i> Link
                            </label>
                            <input type="url" 
                                   id="link" 
                                   name="link" 
                                   class="form-control"
                                   placeholder="https://instagram.com/yourprofile"
                                   required>
                            <small>Enter the URL where you want the service delivered</small>
                        </div>

                        <!-- Quantity Input -->
                        <div class="form-group">
                            <label for="quantity">
                                <i class="fas fa-hashtag"></i> Quantity
                            </label>
                            <input type="number" 
                                   id="quantity" 
                                   name="quantity" 
                                   class="form-control"
                                   placeholder="Enter quantity"
                                   min="1"
                                   required>
                            <small id="quantityHelp">Enter the amount you want to order</small>
                        </div>

                        <!-- Price Calculator -->
                        <div class="price-calculator">
                            <div class="calculator-row">
                                <span>Price per 1000:</span>
                                <strong id="displayPricePerK">$0.00</strong>
                            </div>
                            <div class="calculator-row">
                                <span>Quantity:</span>
                                <strong id="displayQuantity">0</strong>
                            </div>
                            <div class="calculator-divider"></div>
                            <div class="calculator-row total">
                                <span>Total Price:</span>
                                <strong id="totalPrice">$0.00</strong>
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <button type="submit" class="btn-submit" id="submitBtn">
                            <i class="fas fa-shopping-cart"></i> Place Order
                        </button>

                        <div class="form-note">
                            <i class="fas fa-info-circle"></i>
                            <p>Your order will be processed immediately after submission. Make sure all details are correct before placing the order.</p>
                        </div>
                    </form>
                </div>

                <!-- Order Instructions -->
                <div class="instructions-card">
                    <div class="card-header">
                        <i class="fas fa-info-circle"></i>
                        <h2>How to Order</h2>
                    </div>

                    <div class="instruction-steps">
                        <div class="step">
                            <div class="step-number">1</div>
                            <div class="step-content">
                                <h3>Select Category & Service</h3>
                                <p>Choose the social media platform and type of service you need</p>
                            </div>
                        </div>

                        <div class="step">
                            <div class="step-number">2</div>
                            <div class="step-content">
                                <h3>Enter Your Link</h3>
                                <p>Provide the URL where you want the service delivered (profile, post, video, etc.)</p>
                            </div>
                        </div>

                        <div class="step">
                            <div class="step-number">3</div>
                            <div class="step-content">
                                <h3>Set Quantity</h3>
                                <p>Enter the amount you want (followers, likes, views, etc.)</p>
                            </div>
                        </div>

                        <div class="step">
                            <div class="step-number">4</div>
                            <div class="step-content">
                                <h3>Review & Submit</h3>
                                <p>Check the total price and submit your order</p>
                            </div>
                        </div>
                    </div>

                    <div class="tips-section">
                        <h3><i class="fas fa-lightbulb"></i> Tips</h3>
                        <ul>
                            <li>Make sure your profile/post is public</li>
                            <li>Double-check the URL before submitting</li>
                            <li>Orders start processing within minutes</li>
                            <li>You can track orders in "My Orders" section</li>
                        </ul>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <script src="assets/js/dashboard.js"></script>
    <script src="assets/js/new-order.js"></script>
</body>
</html>