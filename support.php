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

// Handle ticket submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_ticket'])) {
    $subject = sanitize_input($_POST['subject']);
    $category = sanitize_input($_POST['category']);
    $priority = sanitize_input($_POST['priority']);
    $message = sanitize_input($_POST['message']);
    
    // Validation
    if (empty($subject)) {
        $errors[] = "Subject is required";
    }
    
    if (empty($category)) {
        $errors[] = "Please select a category";
    }
    
    if (empty($priority)) {
        $errors[] = "Please select a priority";
    }
    
    if (empty($message)) {
        $errors[] = "Message is required";
    } elseif (strlen($message) < 20) {
        $errors[] = "Message must be at least 20 characters";
    }
    
    if (empty($errors)) {
        // Generate ticket number
        $ticket_number = 'TKT-' . strtoupper(substr(md5(time() . rand()), 0, 10));
        
        // Insert ticket
        $insert_ticket = "INSERT INTO support_tickets (user_id, ticket_number, subject, category, priority, status) 
                         VALUES ($user_id, '$ticket_number', '$subject', '$category', '$priority', 'open')";
        
        if (mysqli_query($conn, $insert_ticket)) {
            $ticket_id = mysqli_insert_id($conn);
            
            // Insert first message
            $insert_message = "INSERT INTO ticket_messages (ticket_id, user_id, message, is_admin) 
                              VALUES ($ticket_id, $user_id, '$message', 0)";
            mysqli_query($conn, $insert_message);
            
            // Update ticket with last reply
            mysqli_query($conn, "UPDATE support_tickets SET updated_at = NOW() WHERE id = $ticket_id");
            
            // Create notification
            $notif_title = "Support Ticket Created";
            $notif_message = "Your ticket #$ticket_number has been created. We'll respond soon.";
            mysqli_query($conn, "INSERT INTO notifications (user_id, title, message, type, link) 
                               VALUES ($user_id, '$notif_title', '$notif_message', 'info', 'support.php?ticket=$ticket_id')");
            
            // Log activity
            $ip_address = $_SERVER['REMOTE_ADDR'];
            mysqli_query($conn, "INSERT INTO activity_logs (user_id, action, description, ip_address) 
                               VALUES ($user_id, 'Ticket Created', 'Support ticket #$ticket_number created', '$ip_address')");
            
            $success = "Support ticket created successfully! Ticket #: $ticket_number";
            
            // Redirect to ticket view
            header("refresh:2;url=support.php?ticket=$ticket_id");
        } else {
            $errors[] = "Failed to create ticket. Please try again.";
        }
    }
}

// Handle ticket reply
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reply_ticket'])) {
    $ticket_id = (int)$_POST['ticket_id'];
    $message = sanitize_input($_POST['reply_message']);
    
    if (empty($message)) {
        $errors[] = "Reply message is required";
    }
    
    // Verify ticket belongs to user
    $ticket_check = mysqli_query($conn, "SELECT * FROM support_tickets WHERE id = $ticket_id AND user_id = $user_id");
    
    if (mysqli_num_rows($ticket_check) === 0) {
        $errors[] = "Invalid ticket";
    }
    
    if (empty($errors)) {
        // Insert reply
        $insert_reply = "INSERT INTO ticket_messages (ticket_id, user_id, message, is_admin) 
                        VALUES ($ticket_id, $user_id, '$message', 0)";
        
        if (mysqli_query($conn, $insert_reply)) {
            // Update ticket status and timestamp
            mysqli_query($conn, "UPDATE support_tickets SET status = 'open', updated_at = NOW() WHERE id = $ticket_id");
            
            $success = "Reply sent successfully!";
        } else {
            $errors[] = "Failed to send reply. Please try again.";
        }
    }
}

// Pagination for tickets
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

// Status filter
$status_filter = isset($_GET['status']) ? sanitize_input($_GET['status']) : '';
$status_where = $status_filter ? "AND status = '$status_filter'" : '';

// Get tickets count
$count_query = mysqli_query($conn, "SELECT COUNT(*) as total FROM support_tickets WHERE user_id = $user_id $status_where");
$total_tickets = mysqli_fetch_assoc($count_query)['total'];
$total_pages = ceil($total_tickets / $per_page);

// Get tickets
$tickets_query = mysqli_query($conn, "
    SELECT * FROM support_tickets 
    WHERE user_id = $user_id 
    $status_where
    ORDER BY updated_at DESC 
    LIMIT $per_page OFFSET $offset
");

// Get ticket statistics
$stats_query = mysqli_query($conn, "
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'open' THEN 1 ELSE 0 END) as open,
        SUM(CASE WHEN status = 'answered' THEN 1 ELSE 0 END) as answered,
        SUM(CASE WHEN status = 'closed' THEN 1 ELSE 0 END) as closed
    FROM support_tickets 
    WHERE user_id = $user_id
");
$stats = mysqli_fetch_assoc($stats_query);

// Get notifications count
$unread_notifications = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM notifications WHERE user_id = $user_id AND is_read = 0"));

// Get active tickets count
$active_tickets = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM support_tickets WHERE user_id = $user_id AND status != 'closed'"));

// Check if viewing a specific ticket
$viewing_ticket = null;
if (isset($_GET['ticket'])) {
    $ticket_id = (int)$_GET['ticket'];
    $ticket_query = mysqli_query($conn, "SELECT * FROM support_tickets WHERE id = $ticket_id AND user_id = $user_id");
    if (mysqli_num_rows($ticket_query) > 0) {
        $viewing_ticket = mysqli_fetch_assoc($ticket_query);
        
        // Get ticket messages
        $messages_query = mysqli_query($conn, "
            SELECT tm.*, u.full_name, u.role 
            FROM ticket_messages tm
            LEFT JOIN users u ON tm.user_id = u.id
            WHERE tm.ticket_id = $ticket_id
            ORDER BY tm.created_at ASC
        ");
    }
}

$page_title = "Support";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - SMM Panel</title>
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <link rel="stylesheet" href="assets/css/support.css">
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
            <a href="support.php" class="nav-item active">
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
                <h1>Support Center</h1>
                <p>Get help from our support team</p>
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

        <!-- Support Content -->
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

            <?php if ($viewing_ticket): ?>
                <!-- Ticket View -->
                <div class="ticket-view-container">
                    <div class="ticket-header">
                        <a href="support.php" class="back-button">
                            <i class="fas fa-arrow-left"></i> Back to Tickets
                        </a>
                        
                        <div class="ticket-info">
                            <h2><?php echo htmlspecialchars($viewing_ticket['subject']); ?></h2>
                            <div class="ticket-meta">
                                <span class="ticket-number">
                                    <i class="fas fa-ticket-alt"></i>
                                    <?php echo htmlspecialchars($viewing_ticket['ticket_number']); ?>
                                </span>
                                <span class="ticket-category">
                                    <i class="fas fa-folder"></i>
                                    <?php echo ucfirst($viewing_ticket['category']); ?>
                                </span>
                                <span class="ticket-priority priority-<?php echo $viewing_ticket['priority']; ?>">
                                    <i class="fas fa-flag"></i>
                                    <?php echo ucfirst($viewing_ticket['priority']); ?>
                                </span>
                                <?php
                                $status_classes = [
                                    'open' => 'warning',
                                    'answered' => 'info',
                                    'closed' => 'secondary'
                                ];
                                $s_class = $status_classes[$viewing_ticket['status']] ?? 'secondary';
                                ?>
                                <span class="ticket-status status-<?php echo $s_class; ?>">
                                    <i class="fas fa-circle"></i>
                                    <?php echo ucfirst($viewing_ticket['status']); ?>
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="ticket-messages">
                        <?php while ($msg = mysqli_fetch_assoc($messages_query)): ?>
                            <div class="message-item <?php echo $msg['is_admin'] ? 'admin-message' : 'user-message'; ?>">
                                <div class="message-avatar">
                                    <?php if ($msg['is_admin']): ?>
                                        <i class="fas fa-user-shield"></i>
                                    <?php else: ?>
                                        <i class="fas fa-user"></i>
                                    <?php endif; ?>
                                </div>
                                <div class="message-content">
                                    <div class="message-header">
                                        <strong><?php echo htmlspecialchars($msg['full_name']); ?></strong>
                                        <?php if ($msg['is_admin']): ?>
                                            <span class="admin-badge">Support Team</span>
                                        <?php endif; ?>
                                        <span class="message-time">
                                            <?php echo date('M d, Y h:i A', strtotime($msg['created_at'])); ?>
                                        </span>
                                    </div>
                                    <div class="message-body">
                                        <?php echo nl2br(htmlspecialchars($msg['message'])); ?>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>

                    <?php if ($viewing_ticket['status'] !== 'closed'): ?>
                        <div class="ticket-reply-form">
                            <h3><i class="fas fa-reply"></i> Reply to Ticket</h3>
                            <form method="POST" action="">
                                <input type="hidden" name="ticket_id" value="<?php echo $viewing_ticket['id']; ?>">
                                <div class="form-group">
                                    <textarea name="reply_message" 
                                              class="form-control" 
                                              rows="5" 
                                              placeholder="Type your reply here..."
                                              required></textarea>
                                </div>
                                <button type="submit" name="reply_ticket" class="btn-submit">
                                    <i class="fas fa-paper-plane"></i> Send Reply
                                </button>
                            </form>
                        </div>
                    <?php else: ?>
                        <div class="ticket-closed-notice">
                            <i class="fas fa-lock"></i>
                            <p>This ticket is closed. Please create a new ticket if you need further assistance.</p>
                        </div>
                    <?php endif; ?>
                </div>

            <?php else: ?>
                <!-- Support Overview -->
                
                <!-- Ticket Statistics -->
                <div class="support-stats-grid">
                    <div class="stat-card total">
                        <div class="stat-icon">
                            <i class="fas fa-ticket-alt"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo number_format($stats['total']); ?></h3>
                            <p>Total Tickets</p>
                        </div>
                    </div>

                    <div class="stat-card open">
                        <div class="stat-icon">
                            <i class="fas fa-folder-open"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo number_format($stats['open']); ?></h3>
                            <p>Open Tickets</p>
                        </div>
                    </div>

                    <div class="stat-card answered">
                        <div class="stat-icon">
                            <i class="fas fa-reply"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo number_format($stats['answered']); ?></h3>
                            <p>Answered</p>
                        </div>
                    </div>

                    <div class="stat-card closed">
                        <div class="stat-icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo number_format($stats['closed']); ?></h3>
                            <p>Closed</p>
                        </div>
                    </div>
                </div>

                <div class="support-container">
                    <!-- Create Ticket Form -->
                    <div class="create-ticket-card">
                        <div class="card-header">
                            <i class="fas fa-plus-circle"></i>
                            <h2>Create New Ticket</h2>
                        </div>

                        <form method="POST" action="" class="ticket-form">
                            <div class="form-group">
                                <label for="subject">
                                    <i class="fas fa-heading"></i> Subject
                                </label>
                                <input type="text" 
                                       id="subject" 
                                       name="subject" 
                                       class="form-control"
                                       placeholder="Brief description of your issue"
                                       required>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label for="category">
                                        <i class="fas fa-folder"></i> Category
                                    </label>
                                    <select id="category" name="category" class="form-control" required>
                                        <option value="">Select category...</option>
                                        <option value="order">Order Issue</option>
                                        <option value="payment">Payment Problem</option>
                                        <option value="account">Account Issue</option>
                                        <option value="technical">Technical Support</option>
                                        <option value="general">General Inquiry</option>
                                        <option value="other">Other</option>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label for="priority">
                                        <i class="fas fa-flag"></i> Priority
                                    </label>
                                    <select id="priority" name="priority" class="form-control" required>
                                        <option value="">Select priority...</option>
                                        <option value="low">Low</option>
                                        <option value="medium">Medium</option>
                                        <option value="high">High</option>
                                        <option value="urgent">Urgent</option>
                                    </select>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="message">
                                    <i class="fas fa-comment-dots"></i> Message
                                </label>
                                <textarea id="message" 
                                          name="message" 
                                          class="form-control"
                                          rows="6"
                                          placeholder="Describe your issue in detail..."
                                          required></textarea>
                                <small>Minimum 20 characters</small>
                            </div>

                            <button type="submit" name="create_ticket" class="btn-submit">
                                <i class="fas fa-paper-plane"></i> Submit Ticket
                            </button>
                        </form>
                    </div>

                    <!-- Quick Help -->
                    <div class="quick-help-card">
                        <div class="card-header">
                            <i class="fas fa-question-circle"></i>
                            <h3>Quick Help</h3>
                        </div>
                        <div class="help-sections">
                            <div class="help-section">
                                <h4><i class="fas fa-book"></i> FAQ</h4>
                                <div class="faq-item">
                                    <strong>How long does delivery take?</strong>
                                    <p>Most orders start within minutes and complete in 24-48 hours.</p>
                                </div>
                                <div class="faq-item">
                                    <strong>Can I cancel an order?</strong>
                                    <p>Only pending orders can be cancelled for a full refund.</p>
                                </div>
                                <div class="faq-item">
                                    <strong>What payment methods do you accept?</strong>
                                    <p>We accept credit cards, PayPal, and cryptocurrency.</p>
                                </div>
                            </div>

                            <div class="help-section">
                                <h4><i class="fas fa-envelope"></i> Contact Info</h4>
                                <div class="contact-item">
                                    <i class="fas fa-envelope"></i>
                                    <div>
                                        <strong>Email</strong>
                                        <p>support@smmpanel.com</p>
                                    </div>
                                </div>
                                <div class="contact-item">
                                    <i class="fas fa-clock"></i>
                                    <div>
                                        <strong>Response Time</strong>
                                        <p>Usually within 2-4 hours</p>
                                    </div>
                                </div>
                                <div class="contact-item">
                                    <i class="fas fa-headset"></i>
                                    <div>
                                        <strong>Availability</strong>
                                        <p>24/7 Support</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- My Tickets -->
                <div class="my-tickets-section">
                    <div class="section-header">
                        <h2>My Tickets</h2>
                        <div class="filter-buttons">
                            <a href="support.php" class="filter-btn <?php echo !$status_filter ? 'active' : ''; ?>">
                                All
                            </a>
                            <a href="support.php?status=open" class="filter-btn <?php echo $status_filter === 'open' ? 'active' : ''; ?>">
                                Open
                            </a>
                            <a href="support.php?status=answered" class="filter-btn <?php echo $status_filter === 'answered' ? 'active' : ''; ?>">
                                Answered
                            </a>
                            <a href="support.php?status=closed" class="filter-btn <?php echo $status_filter === 'closed' ? 'active' : ''; ?>">
                                Closed
                            </a>
                        </div>
                    </div>

                    <?php if ($total_tickets > 0): ?>
                        <div class="tickets-list">
                            <?php while ($ticket = mysqli_fetch_assoc($tickets_query)): ?>
                                <div class="ticket-card" onclick="location.href='support.php?ticket=<?php echo $ticket['id']; ?>'">
                                    <div class="ticket-card-header">
                                        <div class="ticket-card-info">
                                            <h3><?php echo htmlspecialchars($ticket['subject']); ?></h3>
                                            <div class="ticket-card-meta">
                                                <span class="ticket-number">
                                                    <i class="fas fa-ticket-alt"></i>
                                                    <?php echo htmlspecialchars($ticket['ticket_number']); ?>
                                                </span>
                                                <span class="ticket-category">
                                                    <i class="fas fa-folder"></i>
                                                    <?php echo ucfirst($ticket['category']); ?>
                                                </span>
                                            </div>
                                        </div>
                                        <div class="ticket-card-badges">
                                            <span class="priority-badge priority-<?php echo $ticket['priority']; ?>">
                                                <i class="fas fa-flag"></i>
                                                <?php echo ucfirst($ticket['priority']); ?>
                                            </span>
                                            <?php
                                            $status_classes = [
                                                'open' => 'warning',
                                                'answered' => 'info',
                                                'closed' => 'secondary'
                                            ];
                                            $s_class = $status_classes[$ticket['status']] ?? 'secondary';
                                            ?>
                                            <span class="status-badge <?php echo $s_class; ?>">
                                                <i class="fas fa-circle"></i>
                                                <?php echo ucfirst($ticket['status']); ?>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="ticket-card-footer">
                                        <span class="ticket-date">
                                            <i class="fas fa-clock"></i>
                                            Created: <?php echo date('M d, Y h:i A', strtotime($ticket['created_at'])); ?>
                                        </span>
                                        <span class="ticket-updated">
                                            <i class="fas fa-sync-alt"></i>
                                            Updated: <?php echo date('M d, Y h:i A', strtotime($ticket['updated_at'])); ?>
                                        </span>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>

                        <!-- Pagination -->
                        <?php if ($total_pages > 1): ?>
                            <div class="pagination">
                                <?php if ($page > 1): ?>
                                    <a href="?page=<?php echo $page - 1; ?><?php echo $status_filter ? '&status=' . $status_filter : ''; ?>" 
                                       class="page-link">
                                        <i class="fas fa-chevron-left"></i> Previous
                                    </a>
                                <?php endif; ?>

                                <div class="page-numbers">
                                    <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                        <a href="?page=<?php echo $i; ?><?php echo $status_filter ? '&status=' . $status_filter : ''; ?>" 
                                           class="page-link <?php echo $i === $page ? 'active' : ''; ?>">
                                            <?php echo $i; ?>
                                        </a>
                                    <?php endfor; ?>
                                </div>

                                <?php if ($page < $total_pages): ?>
                                    <a href="?page=<?php echo $page + 1; ?><?php echo $status_filter ? '&status=' . $status_filter : ''; ?>" 
                                       class="page-link">
                                        Next <i class="fas fa-chevron-right"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

                    <?php else: ?>
                        <div class="empty-tickets">
                            <i class="fas fa-inbox"></i>
                            <h3>No Tickets Found</h3>
                            <p>You haven't created any support tickets yet.</p>
                        </div>
                    <?php endif; ?>
                </div>

            <?php endif; ?>

        </div>
    </div>

    <script src="assets/js/dashboard.js"></script>
    <script src="assets/js/support.js"></script>
</body>
</html>