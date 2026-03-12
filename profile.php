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

// Get user data
$user_query = mysqli_query($conn, "SELECT * FROM users WHERE id = $user_id");
$user = mysqli_fetch_assoc($user_query);

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $full_name = sanitize_input($_POST['full_name']);
    $email = sanitize_input($_POST['email']);
    $phone = sanitize_input($_POST['phone']);
    
    // Validation
    if (empty($full_name)) {
        $errors[] = "Full name is required";
    }
    
    if (empty($email)) {
        $errors[] = "Email is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    }
    
    // Check if email is already taken by another user
    if (empty($errors)) {
        $email_check = mysqli_query($conn, "SELECT id FROM users WHERE email = '$email' AND id != $user_id");
        if (mysqli_num_rows($email_check) > 0) {
            $errors[] = "Email already in use by another account";
        }
    }
    
    if (empty($errors)) {
        $update_query = "UPDATE users SET 
                        full_name = '$full_name',
                        email = '$email',
                        phone = '$phone',
                        updated_at = NOW()
                        WHERE id = $user_id";
        
        if (mysqli_query($conn, $update_query)) {
            // Update session
            $_SESSION['email'] = $email;
            $_SESSION['full_name'] = $full_name;
            
            // Log activity
            $ip_address = $_SERVER['REMOTE_ADDR'];
            mysqli_query($conn, "INSERT INTO activity_logs (user_id, action, description, ip_address) 
                               VALUES ($user_id, 'Profile Updated', 'Profile information updated', '$ip_address')");
            
            $success = "Profile updated successfully!";
            
            // Refresh user data
            $user_query = mysqli_query($conn, "SELECT * FROM users WHERE id = $user_id");
            $user = mysqli_fetch_assoc($user_query);
        } else {
            $errors[] = "Failed to update profile. Please try again.";
        }
    }
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validation
    if (empty($current_password)) {
        $errors[] = "Current password is required";
    } elseif ($current_password !== $user['password']) {
        $errors[] = "Current password is incorrect";
    }
    
    if (empty($new_password)) {
        $errors[] = "New password is required";
    } elseif (strlen($new_password) < 6) {
        $errors[] = "New password must be at least 6 characters";
    }
    
    if ($new_password !== $confirm_password) {
        $errors[] = "New passwords do not match";
    }
    
    if (empty($errors)) {
        $update_password = "UPDATE users SET password = '$new_password', updated_at = NOW() WHERE id = $user_id";
        
        if (mysqli_query($conn, $update_password)) {
            // Log activity
            $ip_address = $_SERVER['REMOTE_ADDR'];
            mysqli_query($conn, "INSERT INTO activity_logs (user_id, action, description, ip_address) 
                               VALUES ($user_id, 'Password Changed', 'User password changed', '$ip_address')");
            
            // Create notification
            mysqli_query($conn, "INSERT INTO notifications (user_id, title, message, type) 
                               VALUES ($user_id, 'Password Changed', 'Your password has been changed successfully.', 'info')");
            
            $success = "Password changed successfully!";
            
            // Refresh user data
            $user_query = mysqli_query($conn, "SELECT * FROM users WHERE id = $user_id");
            $user = mysqli_fetch_assoc($user_query);
        } else {
            $errors[] = "Failed to change password. Please try again.";
        }
    }
}

// Get account statistics
$orders_count = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM orders WHERE user_id = $user_id"));
$total_spent_query = mysqli_query($conn, "SELECT SUM(amount) as total FROM transactions WHERE user_id = $user_id AND type = 'order'");
$total_spent = mysqli_fetch_assoc($total_spent_query)['total'] ?? 0;

// Get recent activity
$recent_activity = mysqli_query($conn, "
    SELECT * FROM activity_logs 
    WHERE user_id = $user_id 
    ORDER BY created_at DESC 
    LIMIT 10
");

// Get notifications count
$unread_notifications = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM notifications WHERE user_id = $user_id AND is_read = 0"));

// Get active tickets count
$active_tickets = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM support_tickets WHERE user_id = $user_id AND status != 'closed'"));

$page_title = "Profile Settings";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - SMM Panel</title>
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <link rel="stylesheet" href="assets/css/profile.css">
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
            <a href="profile.php" class="nav-item active">
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
                <h1>Profile Settings</h1>
                <p>Manage your account information and preferences</p>
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

        <!-- Profile Content -->
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

            <div class="profile-container">
                
                <!-- Main Profile Section -->
                <div class="profile-main">
                    
                    <!-- Profile Overview Card -->
                    <div class="profile-overview-card">
                        <div class="profile-header-section">
                            <div class="profile-avatar-large">
                                <i class="fas fa-user"></i>
                            </div>
                            <div class="profile-header-info">
                                <h2><?php echo htmlspecialchars($user['full_name']); ?></h2>
                                <p><?php echo htmlspecialchars($user['email']); ?></p>
                                <div class="profile-badges">
                                     <span class="user-badge"><?php echo ucfirst($user['user_role']); ?></span>
                                    <span class="profile-badge status"><?php echo ucfirst($user['status']); ?></span>
                                    <span class="profile-badge member">Member since <?php echo date('M Y', strtotime($user['created_at'])); ?></span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="profile-stats-row">
                            <div class="profile-stat">
                                <i class="fas fa-shopping-cart"></i>
                                <div>
                                    <strong><?php echo number_format($orders_count); ?></strong>
                                    <span>Total Orders</span>
                                </div>
                            </div>
                            <div class="profile-stat">
                                <i class="fas fa-dollar-sign"></i>
                                <div>
                                    <strong>$<?php echo number_format($total_spent, 2); ?></strong>
                                    <span>Total Spent</span>
                                </div>
                            </div>
                            <div class="profile-stat">
                                <i class="fas fa-wallet"></i>
                                <div>
                                    <strong>$<?php echo number_format($user['balance'], 2); ?></strong>
                                    <span>Current Balance</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Edit Profile Form -->
                    <div class="profile-form-card">
                        <div class="card-header">
                            <i class="fas fa-user-edit"></i>
                            <h3>Edit Profile Information</h3>
                        </div>
                        <form method="POST" action="" class="profile-form">
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="username">
                                        <i class="fas fa-user"></i> Username
                                    </label>
                                    <input type="text" 
                                           id="username" 
                                           value="<?php echo htmlspecialchars($user['username']); ?>" 
                                           disabled
                                           class="form-control">
                                    <small>Username cannot be changed</small>
                                </div>

                                <div class="form-group">
                                    <label for="full_name">
                                        <i class="fas fa-id-card"></i> Full Name
                                    </label>
                                    <input type="text" 
                                           id="full_name" 
                                           name="full_name" 
                                           value="<?php echo htmlspecialchars($user['full_name']); ?>"
                                           class="form-control"
                                           required>
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label for="email">
                                        <i class="fas fa-envelope"></i> Email Address
                                    </label>
                                    <input type="email" 
                                           id="email" 
                                           name="email" 
                                           value="<?php echo htmlspecialchars($user['email']); ?>"
                                           class="form-control"
                                           required>
                                </div>

                                <div class="form-group">
                                    <label for="phone">
                                        <i class="fas fa-phone"></i> Phone Number
                                    </label>
                                    <input type="tel" 
                                           id="phone" 
                                           name="phone" 
                                           value="<?php echo htmlspecialchars($user['phone']); ?>"
                                           class="form-control"
                                           placeholder="+1234567890">
                                </div>
                            </div>

                            <button type="submit" name="update_profile" class="btn-submit">
                                <i class="fas fa-save"></i> Update Profile
                            </button>
                        </form>
                    </div>

                    <!-- Change Password Form -->
                    <div class="profile-form-card">
                        <div class="card-header">
                            <i class="fas fa-lock"></i>
                            <h3>Change Password</h3>
                        </div>
                        <form method="POST" action="" class="profile-form">
                            <div class="form-group">
                                <label for="current_password">
                                    <i class="fas fa-key"></i> Current Password
                                </label>
                                <div class="password-input">
                                    <input type="password" 
                                           id="current_password" 
                                           name="current_password" 
                                           class="form-control"
                                           required>
                                    <i class="fas fa-eye toggle-password" data-target="current_password"></i>
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label for="new_password">
                                        <i class="fas fa-lock"></i> New Password
                                    </label>
                                    <div class="password-input">
                                        <input type="password" 
                                               id="new_password" 
                                               name="new_password" 
                                               class="form-control"
                                               required>
                                        <i class="fas fa-eye toggle-password" data-target="new_password"></i>
                                    </div>
                                    <small>Minimum 6 characters</small>
                                </div>

                                <div class="form-group">
                                    <label for="confirm_password">
                                        <i class="fas fa-lock"></i> Confirm New Password
                                    </label>
                                    <div class="password-input">
                                        <input type="password" 
                                               id="confirm_password" 
                                               name="confirm_password" 
                                               class="form-control"
                                               required>
                                        <i class="fas fa-eye toggle-password" data-target="confirm_password"></i>
                                    </div>
                                </div>
                            </div>

                            <button type="submit" name="change_password" class="btn-submit btn-secondary">
                                <i class="fas fa-lock"></i> Change Password
                            </button>
                        </form>
                    </div>

                </div>

                <!-- Sidebar Info -->
                <div class="profile-sidebar">
                    
                    <!-- Account Info -->
                    <div class="info-card">
                        <div class="info-card-header">
                            <i class="fas fa-info-circle"></i>
                            <h3>Account Information</h3>
                        </div>
                        <div class="info-card-body">
                            <div class="info-item">
                                <span class="info-label">User ID:</span>
                                <span class="info-value">#<?php echo $user['id']; ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Account Type:</span>
                                 <span class="user-badge"><?php echo ucfirst($user['user_role']); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Status:</span>
                                <span class="info-value status-<?php echo $user['status']; ?>"><?php echo ucfirst($user['status']); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Member Since:</span>
                                <span class="info-value"><?php echo date('M d, Y', strtotime($user['created_at'])); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Last Updated:</span>
                                <span class="info-value"><?php echo date('M d, Y', strtotime($user['updated_at'])); ?></span>
                            </div>
                            <?php if ($user['last_login']): ?>
                            <div class="info-item">
                                <span class="info-label">Last Login:</span>
                                <span class="info-value"><?php echo date('M d, Y H:i', strtotime($user['last_login'])); ?></span>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Recent Activity -->
                    <div class="info-card">
                        <div class="info-card-header">
                            <i class="fas fa-history"></i>
                            <h3>Recent Activity</h3>
                        </div>
                        <div class="info-card-body">
                            <?php if (mysqli_num_rows($recent_activity) > 0): ?>
                                <div class="activity-list">
                                    <?php while ($activity = mysqli_fetch_assoc($recent_activity)): ?>
                                        <div class="activity-item">
                                            <div class="activity-icon">
                                                <i class="fas fa-circle"></i>
                                            </div>
                                            <div class="activity-content">
                                                <strong><?php echo htmlspecialchars($activity['action']); ?></strong>
                                                <p><?php echo htmlspecialchars($activity['description']); ?></p>
                                                <small><?php echo date('M d, Y H:i', strtotime($activity['created_at'])); ?></small>
                                            </div>
                                        </div>
                                    <?php endwhile; ?>
                                </div>
                            <?php else: ?>
                                <p class="no-activity">No recent activity</p>
                            <?php endif; ?>
                        </div>
                    </div>

                </div>

            </div>

        </div>
    </div>

    <script src="assets/js/dashboard.js"></script>
    <script src="assets/js/profile.js"></script>
</body>
</html>