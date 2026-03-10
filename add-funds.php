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

// Handle payment method submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['payment_method'])) {
    $amount = isset($_POST['amount']) ? (float)$_POST['amount'] : 0;
    $payment_method = sanitize_input($_POST['payment_method']);
    $custom_amount = isset($_POST['custom_amount']) ? (float)$_POST['custom_amount'] : 0;
    
    // Use custom amount if provided
    if ($custom_amount > 0) {
        $amount = $custom_amount;
    }
    
    // Validation
    if ($amount <= 0) {
        $errors[] = "Please select or enter a valid amount";
    } elseif ($amount < 5) {
        $errors[] = "Minimum deposit amount is $5.00";
    } elseif ($amount > 10000) {
        $errors[] = "Maximum deposit amount is $10,000.00";
    }
    
    if (empty($payment_method)) {
        $errors[] = "Please select a payment method";
    }
    
    if (empty($errors)) {
        // Generate transaction ID
        $transaction_id = 'TXN-' . strtoupper(substr(md5(time() . rand()), 0, 12));
        
        // For demo purposes, we'll mark as pending
        // In production, integrate with actual payment gateways
        
        // Create payment record
        $description = "Deposit via " . ucfirst($payment_method);
        $insert_transaction = "INSERT INTO transactions 
                              (user_id, transaction_id, type, amount, balance_before, balance_after, description, payment_method, status) 
                              VALUES 
                              ($user_id, '$transaction_id', 'deposit', $amount, {$user['balance']}, {$user['balance']}, '$description', '$payment_method', 'pending')";
        
        if (mysqli_query($conn, $insert_transaction)) {
            // Create notification
            $notif_title = "Payment Pending";
            $notif_message = "Your payment of $$amount via $payment_method is being processed.";
            mysqli_query($conn, "INSERT INTO notifications (user_id, title, message, type) 
                               VALUES ($user_id, '$notif_title', '$notif_message', 'info')");
            
            // Log activity
            $ip_address = $_SERVER['REMOTE_ADDR'];
            mysqli_query($conn, "INSERT INTO activity_logs (user_id, action, description, ip_address) 
                               VALUES ($user_id, 'Payment Initiated', 'Payment of $$amount via $payment_method', '$ip_address')");
            
            // For DEMO: Auto-approve the payment
            $new_balance = $user['balance'] + $amount;
            mysqli_query($conn, "UPDATE users SET balance = $new_balance WHERE id = $user_id");
            mysqli_query($conn, "UPDATE transactions SET status = 'completed', balance_after = $new_balance WHERE transaction_id = '$transaction_id'");
            
            $success = "Payment successful! $$amount has been added to your account.";
            
            // Update user data
            $user['balance'] = $new_balance;
            $_SESSION['balance'] = $new_balance;
        } else {
            $errors[] = "Payment failed. Please try again.";
        }
    }
}

// Get recent transactions
$recent_transactions = mysqli_query($conn, "
    SELECT * FROM transactions 
    WHERE user_id = $user_id 
    AND type = 'deposit'
    ORDER BY created_at DESC 
    LIMIT 5
");

// Get notifications count
$unread_notifications = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM notifications WHERE user_id = $user_id AND is_read = 0"));

// Get active tickets count
$active_tickets = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM support_tickets WHERE user_id = $user_id AND status != 'closed'"));

$page_title = "Add Funds";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - SMM Panel</title>
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <link rel="stylesheet" href="assets/css/add-funds.css">
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
            <a href="add-funds.php" class="nav-item active">
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
                <h1>Add Funds</h1>
                <p>Top up your account balance securely</p>
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

        <!-- Add Funds Content -->
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

            <div class="funds-container">
                <!-- Payment Form -->
                <div class="payment-form-card">
                    <div class="card-header">
                        <i class="fas fa-credit-card"></i>
                        <h2>Select Amount & Payment Method</h2>
                    </div>

                    <form method="POST" action="" id="paymentForm" class="payment-form">
                        
                        <!-- Current Balance Display -->
                        <div class="balance-info">
                            <div class="balance-label">Current Balance</div>
                            <div class="balance-amount">$<?php echo number_format($user['balance'], 2); ?></div>
                        </div>

                        <!-- Amount Selection -->
                        <div class="amount-section">
                            <h3><i class="fas fa-dollar-sign"></i> Select Amount</h3>
                            <div class="amount-grid">
                                <label class="amount-option">
                                    <input type="radio" name="amount" value="10" required>
                                    <div class="amount-card">
                                        <span class="amount-value">$10</span>
                                        <span class="amount-bonus">Get Started</span>
                                    </div>
                                </label>

                                <label class="amount-option">
                                    <input type="radio" name="amount" value="25">
                                    <div class="amount-card">
                                        <span class="amount-value">$25</span>
                                        <span class="amount-bonus">Popular</span>
                                    </div>
                                </label>

                                <label class="amount-option">
                                    <input type="radio" name="amount" value="50">
                                    <div class="amount-card">
                                        <span class="amount-value">$50</span>
                                        <span class="amount-bonus">+5% Bonus</span>
                                    </div>
                                </label>

                                <label class="amount-option">
                                    <input type="radio" name="amount" value="100">
                                    <div class="amount-card">
                                        <span class="amount-value">$100</span>
                                        <span class="amount-bonus">+10% Bonus</span>
                                    </div>
                                </label>

                                <label class="amount-option">
                                    <input type="radio" name="amount" value="250">
                                    <div class="amount-card">
                                        <span class="amount-value">$250</span>
                                        <span class="amount-bonus">+15% Bonus</span>
                                    </div>
                                </label>

                                <label class="amount-option">
                                    <input type="radio" name="amount" value="500">
                                    <div class="amount-card">
                                        <span class="amount-value">$500</span>
                                        <span class="amount-bonus">+20% Bonus</span>
                                    </div>
                                </label>
                            </div>

                            <!-- Custom Amount -->
                            <div class="custom-amount-group">
                                <label for="custom_amount">
                                    <i class="fas fa-edit"></i> Or Enter Custom Amount
                                </label>
                                <div class="custom-amount-input">
                                    <span class="currency-symbol">$</span>
                                    <input type="number" 
                                           id="custom_amount" 
                                           name="custom_amount" 
                                           placeholder="Enter amount (min $5)"
                                           min="5"
                                           max="10000"
                                           step="0.01">
                                </div>
                                <small>Minimum: $5.00 | Maximum: $10,000.00</small>
                            </div>
                        </div>

                        <!-- Payment Methods -->
                        <div class="payment-methods-section">
                            <h3><i class="fas fa-credit-card"></i> Select Payment Method</h3>
                            <div class="payment-methods-grid">
                                
                                <label class="payment-method-option">
                                    <input type="radio" name="payment_method" value="credit_card" required>
                                    <div class="payment-method-card">
                                        <i class="fas fa-credit-card"></i>
                                        <span>Credit/Debit Card</span>
                                    </div>
                                </label>

                                <label class="payment-method-option">
                                    <input type="radio" name="payment_method" value="paypal">
                                    <div class="payment-method-card">
                                        <i class="fab fa-paypal"></i>
                                        <span>PayPal</span>
                                    </div>
                                </label>

                                <label class="payment-method-option">
                                    <input type="radio" name="payment_method" value="bitcoin">
                                    <div class="payment-method-card">
                                        <i class="fab fa-bitcoin"></i>
                                        <span>Bitcoin</span>
                                    </div>
                                </label>

                                <label class="payment-method-option">
                                    <input type="radio" name="payment_method" value="ethereum">
                                    <div class="payment-method-card">
                                        <i class="fab fa-ethereum"></i>
                                        <span>Ethereum</span>
                                    </div>
                                </label>

                                <label class="payment-method-option">
                                    <input type="radio" name="payment_method" value="bank_transfer">
                                    <div class="payment-method-card">
                                        <i class="fas fa-university"></i>
                                        <span>Bank Transfer</span>
                                    </div>
                                </label>

                                <label class="payment-method-option">
                                    <input type="radio" name="payment_method" value="stripe">
                                    <div class="payment-method-card">
                                        <i class="fab fa-stripe"></i>
                                        <span>Stripe</span>
                                    </div>
                                </label>

                            </div>
                        </div>

                        <!-- Payment Summary -->
                        <div class="payment-summary">
                            <div class="summary-row">
                                <span>Amount:</span>
                                <strong id="summaryAmount">$0.00</strong>
                            </div>
                            <div class="summary-row">
                                <span>Payment Method:</span>
                                <strong id="summaryMethod">Not selected</strong>
                            </div>
                            <div class="summary-divider"></div>
                            <div class="summary-row total">
                                <span>Total to Pay:</span>
                                <strong id="summaryTotal">$0.00</strong>
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <button type="submit" class="btn-submit-payment">
                            <i class="fas fa-lock"></i> Proceed to Secure Payment
                        </button>

                        <div class="payment-note">
                            <i class="fas fa-shield-alt"></i>
                            <p>All payments are secured with SSL encryption. Your financial information is safe with us.</p>
                        </div>
                    </form>
                </div>

                <!-- Payment Info & Recent Transactions -->
                <div class="payment-sidebar">
                    
                    <!-- Payment Benefits -->
                    <div class="info-card">
                        <div class="info-card-header">
                            <i class="fas fa-gift"></i>
                            <h3>Deposit Bonuses</h3>
                        </div>
                        <div class="info-card-body">
                            <div class="bonus-item">
                                <i class="fas fa-star"></i>
                                <div>
                                    <strong>$50+</strong>
                                    <p>Get 5% bonus</p>
                                </div>
                            </div>
                            <div class="bonus-item">
                                <i class="fas fa-star"></i>
                                <div>
                                    <strong>$100+</strong>
                                    <p>Get 10% bonus</p>
                                </div>
                            </div>
                            <div class="bonus-item">
                                <i class="fas fa-star"></i>
                                <div>
                                    <strong>$250+</strong>
                                    <p>Get 15% bonus</p>
                                </div>
                            </div>
                            <div class="bonus-item">
                                <i class="fas fa-star"></i>
                                <div>
                                    <strong>$500+</strong>
                                    <p>Get 20% bonus</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Payment Security -->
                    <div class="info-card">
                        <div class="info-card-header">
                            <i class="fas fa-shield-alt"></i>
                            <h3>Secure Payments</h3>
                        </div>
                        <div class="info-card-body">
                            <ul class="security-list">
                                <li><i class="fas fa-check"></i> SSL Encrypted</li>
                                <li><i class="fas fa-check"></i> PCI Compliant</li>
                                <li><i class="fas fa-check"></i> Instant Processing</li>
                                <li><i class="fas fa-check"></i> 24/7 Support</li>
                            </ul>
                        </div>
                    </div>

                    <!-- Recent Transactions -->
                    <div class="info-card">
                        <div class="info-card-header">
                            <i class="fas fa-history"></i>
                            <h3>Recent Deposits</h3>
                        </div>
                        <div class="info-card-body">
                            <?php if (mysqli_num_rows($recent_transactions) > 0): ?>
                                <div class="recent-transactions">
                                    <?php while ($txn = mysqli_fetch_assoc($recent_transactions)): ?>
                                        <div class="transaction-item">
                                            <div class="txn-icon <?php echo $txn['status']; ?>">
                                                <i class="fas fa-<?php echo $txn['status'] === 'completed' ? 'check' : 'clock'; ?>"></i>
                                            </div>
                                            <div class="txn-details">
                                                <strong>$<?php echo number_format($txn['amount'], 2); ?></strong>
                                                <p><?php echo ucfirst($txn['payment_method'] ?? 'Payment'); ?></p>
                                                <small><?php echo date('M d, Y', strtotime($txn['created_at'])); ?></small>
                                            </div>
                                            <span class="txn-status <?php echo $txn['status']; ?>">
                                                <?php echo ucfirst($txn['status']); ?>
                                            </span>
                                        </div>
                                    <?php endwhile; ?>
                                </div>
                            <?php else: ?>
                                <p class="no-transactions">No deposit history yet</p>
                            <?php endif; ?>
                        </div>
                    </div>

                </div>
            </div>

        </div>
    </div>

    <script src="assets/js/dashboard.js"></script>
    <script src="assets/js/add-funds.js"></script>
</body>
</html>