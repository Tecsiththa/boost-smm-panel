-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 04, 2026 at 01:49 AM
-- Server version: 10.4.28-MariaDB
-- PHP Version: 8.1.17

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `smm_panel`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_logs`
--

CREATE TABLE `activity_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `activity_logs`
--

INSERT INTO `activity_logs` (`id`, `user_id`, `action`, `description`, `ip_address`, `created_at`) VALUES
(1, 2, 'User Registration', 'New user registered: siththa (siththa@gmail.com)', '::1', '2026-01-19 11:29:14'),
(2, 3, 'User Registration', 'New user registered: sriyani (sriyani@gmail.com)', '127.0.0.1', '2026-01-19 11:32:53'),
(3, 2, 'User Logout', 'User logged out', '::1', '2026-01-21 17:36:30'),
(4, 2, 'User Logout', 'User logged out', '::1', '2026-01-22 08:33:58'),
(5, 2, 'User Logout', 'User logged out', '::1', '2026-01-23 04:43:21'),
(6, 2, 'User Logout', 'User logged out', '::1', '2026-01-23 14:47:44'),
(7, 3, 'User Logout', 'User logged out', '::1', '2026-01-23 14:59:15'),
(8, 4, 'User Registration', 'New user registered: yash (yash@gmail.com)', '::1', '2026-01-23 15:04:32'),
(9, 4, 'User Logout', 'User logged out', '::1', '2026-01-23 15:08:19'),
(10, 4, 'User Logout', 'User logged out', '::1', '2026-01-23 15:10:37'),
(11, 4, 'User Login', 'User logged in: yash', '::1', '2026-01-23 15:10:44'),
(12, 4, 'User Logout', 'User logged out', '::1', '2026-01-25 14:23:49'),
(13, 2, 'User Login', 'User logged in: siththa', '::1', '2026-01-25 14:36:04'),
(14, 2, 'User Logout', 'User logged out', '::1', '2026-01-25 14:37:01'),
(15, 2, 'User Login', 'User logged in: siththa', '::1', '2026-01-25 14:51:13'),
(16, 2, 'User Logout', 'User logged out', '::1', '2026-01-26 08:16:55'),
(17, 5, 'User Registration', 'New user registered: admin (admin@gmail.com)', '::1', '2026-01-26 08:17:57'),
(18, 5, 'User Login', 'User logged in: admin', '::1', '2026-01-26 08:18:22'),
(19, 5, 'User Logout', 'User logged out', '::1', '2026-01-26 08:18:30'),
(20, 5, 'User Login', 'User logged in: admin', '::1', '2026-01-26 08:19:24'),
(21, 5, 'User Login', 'User logged in: admin', '::1', '2026-02-07 04:31:19'),
(22, 5, 'User Login', 'User logged in: admin', '127.0.0.1', '2026-02-07 04:50:27'),
(23, 5, 'User Login', 'User logged in: admin', '::1', '2026-02-07 05:34:20'),
(24, 5, 'User Logout', 'User logged out', '::1', '2026-02-07 06:29:43'),
(25, 5, 'User Login', 'User logged in: admin', '::1', '2026-02-07 06:29:50'),
(26, 5, 'User Logout', 'User logged out', '127.0.0.1', '2026-02-07 23:40:18'),
(27, 3, 'User Login', 'User logged in: sriyani', '127.0.0.1', '2026-02-07 23:40:26'),
(28, 3, 'User Logout', 'User logged out', '127.0.0.1', '2026-02-07 23:40:41'),
(29, 5, 'User Login', 'User logged in: admin', '127.0.0.1', '2026-02-07 23:40:50'),
(30, 5, 'User Logout', 'User logged out', '::1', '2026-02-08 00:00:55'),
(31, 5, 'User Logout', 'User logged out', '127.0.0.1', '2026-02-08 00:01:07'),
(32, 3, 'User Login', 'User logged in: sriyani', '127.0.0.1', '2026-02-08 00:01:11'),
(33, 5, 'User Login', 'User logged in: admin', '::1', '2026-03-03 15:44:47'),
(34, 5, 'User Logout', 'User logged out', '::1', '2026-03-03 15:46:08'),
(35, 2, 'User Login', 'User logged in: siththa', '::1', '2026-03-03 15:47:12'),
(36, 2, 'User Logout', 'User logged out', '::1', '2026-03-03 15:58:10'),
(37, 5, 'User Login', 'User logged in: admin', '::1', '2026-03-03 15:58:16');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `type` enum('info','success','warning','error') DEFAULT 'info',
  `is_read` tinyint(1) DEFAULT 0,
  `link` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `service_id` int(11) NOT NULL,
  `order_number` varchar(50) NOT NULL,
  `link` varchar(500) NOT NULL,
  `quantity` int(11) NOT NULL,
  `start_count` int(11) DEFAULT 0,
  `remains` int(11) DEFAULT 0,
  `price` decimal(10,2) NOT NULL,
  `status` enum('pending','processing','completed','partial','cancelled','refunded') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `services`
--

CREATE TABLE `services` (
  `id` int(11) NOT NULL,
  `category` varchar(50) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `min_quantity` int(11) DEFAULT 100,
  `max_quantity` int(11) DEFAULT 100000,
  `price_per_1000` decimal(10,2) NOT NULL,
  `reseller_price_per_1000` decimal(10,2) NOT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `delivery_time` varchar(50) DEFAULT 'Instant - 1 hour',
  `icon` varchar(50) DEFAULT 'fa-star',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `services`
--

INSERT INTO `services` (`id`, `category`, `name`, `description`, `min_quantity`, `max_quantity`, `price_per_1000`, `reseller_price_per_1000`, `status`, `delivery_time`, `icon`, `created_at`, `updated_at`) VALUES
(1, 'Instagram', 'Instagram Followers - High Quality', 'Real looking followers with profile pictures', 100, 50000, 10.00, 5.00, 'active', 'Instant - 2 hours', 'fa-instagram', '2026-01-23 14:45:50', '2026-01-23 14:45:50'),
(2, 'Instagram', 'Instagram Likes - Instant', 'Fast delivery of likes on your posts', 50, 10000, 2.00, 1.00, 'active', 'Instant - 30 minutes', 'fa-heart', '2026-01-23 14:45:50', '2026-01-23 14:45:50'),
(3, 'Instagram', 'Instagram Views - Real', 'High retention video views', 100, 100000, 0.50, 0.25, 'active', 'Instant - 1 hour', 'fa-eye', '2026-01-23 14:45:50', '2026-01-23 14:45:50'),
(4, 'Instagram', 'Instagram Comments - Custom', 'Custom comments on your posts', 10, 1000, 15.00, 8.00, 'active', '1-6 hours', 'fa-comment', '2026-01-23 14:45:50', '2026-01-23 14:45:50'),
(5, 'Facebook', 'Facebook Page Likes', 'Real page likes from active accounts', 100, 20000, 8.00, 4.00, 'active', 'Instant - 3 hours', 'fa-thumbs-up', '2026-01-23 14:45:50', '2026-01-23 14:45:50'),
(6, 'Facebook', 'Facebook Post Likes', 'Likes on your Facebook posts', 50, 5000, 3.00, 1.50, 'active', 'Instant - 1 hour', 'fa-heart', '2026-01-23 14:45:50', '2026-01-23 14:45:50'),
(7, 'YouTube', 'YouTube Subscribers', 'Real subscribers for your channel', 100, 10000, 25.00, 12.00, 'active', '1-12 hours', 'fa-user-plus', '2026-01-23 14:45:50', '2026-01-23 14:45:50'),
(8, 'YouTube', 'YouTube Views - HQ', 'High retention views from real users', 1000, 1000000, 5.00, 2.50, 'active', 'Instant - 24 hours', 'fa-play', '2026-01-23 14:45:50', '2026-01-23 14:45:50'),
(9, 'YouTube', 'YouTube Likes', 'Likes on your YouTube videos', 50, 5000, 10.00, 5.00, 'active', 'Instant - 2 hours', 'fa-thumbs-up', '2026-01-23 14:45:50', '2026-01-23 14:45:50'),
(10, 'Twitter', 'Twitter Followers', 'Active Twitter followers', 100, 25000, 12.00, 6.00, 'active', 'Instant - 6 hours', 'fa-user-plus', '2026-01-23 14:45:50', '2026-01-23 14:45:50'),
(11, 'Twitter', 'Twitter Likes', 'Likes on your tweets', 50, 10000, 4.00, 2.00, 'active', 'Instant - 1 hour', 'fa-heart', '2026-01-23 14:45:50', '2026-01-23 14:45:50'),
(12, 'TikTok', 'TikTok Followers', 'Real TikTok followers', 100, 50000, 8.00, 4.00, 'active', 'Instant - 3 hours', 'fa-user-plus', '2026-01-23 14:45:50', '2026-01-23 14:45:50'),
(13, 'TikTok', 'TikTok Likes', 'Likes on your TikTok videos', 100, 100000, 1.50, 0.75, 'active', 'Instant - 1 hour', 'fa-heart', '2026-01-23 14:45:50', '2026-01-23 14:45:50'),
(14, 'TikTok', 'TikTok Views', 'High quality video views', 1000, 500000, 0.30, 0.15, 'active', 'Instant - 2 hours', 'fa-eye', '2026-01-23 14:45:50', '2026-01-23 14:45:50');

-- --------------------------------------------------------

--
-- Table structure for table `support_tickets`
--

CREATE TABLE `support_tickets` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `ticket_number` varchar(50) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `category` enum('general','payment','order','technical','other') DEFAULT 'general',
  `priority` enum('low','medium','high') DEFAULT 'medium',
  `status` enum('open','replied','closed') DEFAULT 'open',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ticket_messages`
--

CREATE TABLE `ticket_messages` (
  `id` int(11) NOT NULL,
  `ticket_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `is_admin` tinyint(1) DEFAULT 0,
  `message` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ticket_replies`
--

CREATE TABLE `ticket_replies` (
  `id` int(11) NOT NULL,
  `ticket_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `message` longtext NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `transactions`
--

CREATE TABLE `transactions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `transaction_id` varchar(100) NOT NULL,
  `type` enum('deposit','order','refund','bonus') NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `balance_before` decimal(10,2) NOT NULL,
  `balance_after` decimal(10,2) NOT NULL,
  `description` text DEFAULT NULL,
  `payment_method` varchar(50) DEFAULT NULL,
  `status` enum('pending','completed','failed') DEFAULT 'completed',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `user_role` enum('admin','user','reseller') DEFAULT 'user',
  `balance` decimal(10,2) DEFAULT 0.00,
  `status` enum('active','suspended','inactive') DEFAULT 'active',
  `api_key` varchar(64) DEFAULT NULL,
  `profile_image` varchar(255) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `verification_token` varchar(64) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `country` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_login` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `full_name`, `email`, `username`, `password`, `user_role`, `balance`, `status`, `api_key`, `profile_image`, `phone`, `verification_token`, `ip_address`, `country`, `created_at`, `last_login`, `updated_at`) VALUES
(2, 'sithum vindana', 'siththa@gmail.com', 'siththa', 'sithum1234', 'user', 0.00, 'active', NULL, NULL, '0771397583', 'ec418408b4ac3a7d0725eaaee5602cec', '::1', NULL, '2026-01-19 11:29:14', '2026-03-03 15:47:12', '2026-03-03 15:47:12'),
(3, 'sriyani wijethunga', 'sriyani@gmail.com', 'sriyani', 'sriyani1234', 'user', 0.00, 'active', NULL, NULL, '0771297538', '0349033caf375e87cb8fde4a769b21f3', '127.0.0.1', NULL, '2026-01-19 11:32:53', '2026-02-08 00:01:11', '2026-02-08 00:01:11'),
(4, 'yasiru fonseka', 'yash@gmail.com', 'yash', 'yash12345', 'user', 0.00, 'active', NULL, NULL, '0123456779', '27c457fa38e73df4f3d7fe41df688792', '::1', NULL, '2026-01-23 15:04:32', '2026-01-23 15:10:44', '2026-01-23 15:10:44'),
(5, 'admin user', 'admin@gmail.com', 'admin', 'admin1234', 'admin', 0.00, 'active', NULL, NULL, '07712985632', '6c6ade160b2c193210260e9048023057', '::1', NULL, '2026-01-26 08:17:57', '2026-03-03 15:58:16', '2026-03-03 15:58:16');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_is_read` (`is_read`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `order_number` (`order_number`),
  ADD KEY `service_id` (`service_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_order_number` (`order_number`);

--
-- Indexes for table `services`
--
ALTER TABLE `services`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_category` (`category`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `support_tickets`
--
ALTER TABLE `support_tickets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ticket_number` (`ticket_number`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `ticket_messages`
--
ALTER TABLE `ticket_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_ticket_id` (`ticket_id`);

--
-- Indexes for table `ticket_replies`
--
ALTER TABLE `ticket_replies`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_ticket_id` (`ticket_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `transactions`
--
ALTER TABLE `transactions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `transaction_id` (`transaction_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_type` (`type`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_username` (`username`),
  ADD KEY `idx_user_role` (`user_role`),
  ADD KEY `idx_status` (`status`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `services`
--
ALTER TABLE `services`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `support_tickets`
--
ALTER TABLE `support_tickets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ticket_messages`
--
ALTER TABLE `ticket_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ticket_replies`
--
ALTER TABLE `ticket_replies`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `transactions`
--
ALTER TABLE `transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD CONSTRAINT `activity_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `orders_ibfk_2` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `support_tickets`
--
ALTER TABLE `support_tickets`
  ADD CONSTRAINT `support_tickets_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `ticket_messages`
--
ALTER TABLE `ticket_messages`
  ADD CONSTRAINT `ticket_messages_ibfk_1` FOREIGN KEY (`ticket_id`) REFERENCES `support_tickets` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `ticket_messages_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `ticket_replies`
--
ALTER TABLE `ticket_replies`
  ADD CONSTRAINT `ticket_replies_ibfk_1` FOREIGN KEY (`ticket_id`) REFERENCES `support_tickets` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `ticket_replies_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `transactions`
--
ALTER TABLE `transactions`
  ADD CONSTRAINT `transactions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
