<?php
require_once 'config.php';

// Create support_tickets table if it doesn't exist
$create_tickets_table = "CREATE TABLE IF NOT EXISTS `support_tickets` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `description` LONGTEXT NOT NULL,
  `category` VARCHAR(100),
  `status` ENUM('open', 'pending', 'replied', 'resolved', 'closed') DEFAULT 'open',
  `priority` ENUM('low', 'medium', 'high') DEFAULT 'medium',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  INDEX `idx_user_id` (`user_id`),
  INDEX `idx_status` (`status`),
  INDEX `idx_priority` (`priority`),
  INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

// Create ticket_replies table if it doesn't exist
$create_replies_table = "CREATE TABLE IF NOT EXISTS `ticket_replies` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `ticket_id` INT NOT NULL,
  `user_id` INT NOT NULL,
  `message` LONGTEXT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`ticket_id`) REFERENCES `support_tickets`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  INDEX `idx_ticket_id` (`ticket_id`),
  INDEX `idx_user_id` (`user_id`),
  INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

// Execute table creation
if (mysqli_query($conn, $create_tickets_table)) {
    $message = "✓ support_tickets table created/verified successfully";
} else {
    $message = "✗ Error creating support_tickets table: " . mysqli_error($conn);
}

if (mysqli_query($conn, $create_replies_table)) {
    $message .= "<br>✓ ticket_replies table created/verified successfully";
} else {
    $message .= "<br>✗ Error creating ticket_replies table: " . mysqli_error($conn);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Setup - SMM Panel</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }
        
        .container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
            padding: 2rem;
            max-width: 500px;
            width: 100%;
        }
        
        h1 {
            color: #2d3748;
            margin-bottom: 1.5rem;
            text-align: center;
        }
        
        .message {
            background: #f0fdf4;
            border: 2px solid #22c55e;
            color: #166534;
            padding: 1.5rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            line-height: 1.6;
        }
        
        .message br {
            display: block;
            margin: 0.5rem 0;
        }
        
        .button-group {
            display: flex;
            gap: 1rem;
        }
        
        a, button {
            flex: 1;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.95rem;
            cursor: pointer;
            text-decoration: none;
            text-align: center;
            transition: all 0.3s ease;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }
        
        .btn-secondary {
            background: #e2e8f0;
            color: #4a5568;
        }
        
        .btn-secondary:hover {
            background: #cbd5e0;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Database Setup</h1>
        <div class="message"><?php echo $message; ?></div>
        <div class="button-group">
            <a href="admin/tickets.php" class="btn-primary">Go to Tickets</a>
            <a href="admin/dashboard.php" class="btn-secondary">Go to Dashboard</a>
        </div>
    </div>
</body>
</html>
