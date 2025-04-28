-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 28, 2025 at 06:06 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `echo_ride`
--

-- --------------------------------------------------------

--
-- Table structure for table `contact_messages`
--

CREATE TABLE `contact_messages` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `message` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('new','read','replied') DEFAULT 'new'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `contact_messages`
--

INSERT INTO `contact_messages` (`id`, `name`, `email`, `message`, `created_at`, `status`) VALUES
(1, 'test', 'test@gmail.com', 'this is a test message', '2025-04-27 23:31:03', 'new');

-- --------------------------------------------------------

--
-- Table structure for table `maintenance`
--

CREATE TABLE `maintenance` (
  `id` int(11) NOT NULL,
  `vehicle_id` int(11) NOT NULL,
  `type` enum('routine','repair','battery','tire','software') NOT NULL,
  `description` text NOT NULL,
  `priority` enum('high','medium','low') NOT NULL,
  `status` enum('pending','in_progress','completed','overdue') NOT NULL DEFAULT 'pending',
  `due_date` datetime NOT NULL,
  `assigned_to` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `maintenance`
--

INSERT INTO `maintenance` (`id`, `vehicle_id`, `type`, `description`, `priority`, `status`, `due_date`, `assigned_to`, `notes`, `created_at`, `updated_at`) VALUES
(1, 1, 'routine', 'Regular monthly checkup', 'medium', 'pending', '2025-05-05 03:06:39', NULL, 'Standard inspection and maintenance', '2025-04-28 01:06:39', '2025-04-28 01:06:39'),
(3, 1, 'software', 'System update required', 'low', 'pending', '2025-05-12 03:06:39', NULL, 'New firmware version available', '2025-04-28 01:06:40', '2025-04-28 01:06:40'),
(4, 2, 'routine', 'Perfoming a routine check to our bike', 'medium', 'pending', '2025-04-30 04:20:00', 6, 'Standard bike routine check', '2025-04-28 01:26:39', '2025-04-28 01:26:39');

-- --------------------------------------------------------

--
-- Table structure for table `maintenance_history`
--

CREATE TABLE `maintenance_history` (
  `id` int(11) NOT NULL,
  `maintenance_id` int(11) NOT NULL,
  `status` enum('pending','in_progress','completed','overdue') NOT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `maintenance_history`
--

INSERT INTO `maintenance_history` (`id`, `maintenance_id`, `status`, `notes`, `created_at`) VALUES
(1, 1, 'pending', 'Task created', '2025-04-28 01:06:39'),
(3, 3, 'pending', 'Task created', '2025-04-28 01:06:40'),
(4, 4, 'pending', 'Task created: Standard bike routine check', '2025-04-28 01:26:39');

-- --------------------------------------------------------

--
-- Table structure for table `maintenance_logs`
--

CREATE TABLE `maintenance_logs` (
  `id` int(11) NOT NULL,
  `vehicle_id` int(11) NOT NULL,
  `maintenance_type` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `cost` decimal(10,2) DEFAULT NULL,
  `service_date` datetime NOT NULL,
  `next_service_date` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

CREATE TABLE `messages` (
  `id` int(11) NOT NULL,
  `from_id` int(11) NOT NULL,
  `to_id` int(11) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `message_type` enum('support','feedback','complaint','inquiry','notification') NOT NULL,
  `status` enum('read','unread') NOT NULL DEFAULT 'unread',
  `is_flagged` tinyint(1) NOT NULL DEFAULT 0,
  `reply_to_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `messages`
--

INSERT INTO `messages` (`id`, `from_id`, `to_id`, `subject`, `content`, `message_type`, `status`, `is_flagged`, `reply_to_id`, `created_at`, `updated_at`) VALUES
(1, 7, 6, 'Question about ride sharing', 'Hello,\n\nI have a question about the ride sharing feature. How do I set up recurring rides?\n\nThanks,\nUser', 'inquiry', 'unread', 0, NULL, '2025-04-28 02:24:24', '2025-04-28 02:24:24'),
(2, 7, 6, 'App not working properly', 'Hi,\n\nI\'m having trouble with the app. It keeps crashing when I try to book a ride.\n\nCan you help?\nUser', 'support', 'read', 1, NULL, '2025-04-28 02:24:24', '2025-04-28 02:24:24'),
(3, 6, 7, 'RE: App not working properly', 'Hello,\n\nI\'m sorry to hear you\'re having trouble. Could you please provide more details about your device and the app version?\n\nBest regards,\nAdmin', 'support', 'unread', 0, 2, '2025-04-28 02:24:24', '2025-04-28 02:24:24'),
(4, 7, 6, 'Question about ride sharing', 'Hello,\n\nI have a question about the ride sharing feature. How do I set up recurring rides?\n\nThanks,\nUser', 'inquiry', 'unread', 1, NULL, '2025-04-28 02:26:22', '2025-04-28 02:32:39'),
(5, 7, 6, 'App not working properly', 'Hi,\n\nI\'m having trouble with the app. It keeps crashing when I try to book a ride.\n\nCan you help?\nUser', 'support', 'read', 0, NULL, '2025-04-28 02:26:22', '2025-04-28 02:32:36'),
(6, 6, 7, 'RE: App not working properly', 'Hello,\n\nI\'m sorry to hear you\'re having trouble. Could you please provide more details about your device and the app version?\n\nBest regards,\nAdmin', 'support', 'read', 0, 2, '2025-04-28 02:26:22', '2025-04-28 02:32:43'),
(7, 8, 6, 'Feedback on new features', 'Hello Admin,\n\nI really like the new features in the latest update. The carbon footprint tracker is awesome!\n\nKeep up the good work,\nUser', 'feedback', 'unread', 0, NULL, '2025-04-28 02:26:22', '2025-04-28 02:26:22'),
(8, 8, 6, 'Driver was late', 'Hi,\n\nI had a ride scheduled for 9am but the driver was 20 minutes late. This caused me to be late for an important meeting.\n\nI would like a refund or credit for this ride.\n\nRegards,\nUser', 'complaint', 'unread', 1, NULL, '2025-04-28 02:26:22', '2025-04-28 02:26:22'),
(9, 6, 8, 'Welcome to Echoride', 'Hello,\n\nWelcome to Echoride! We\'re excited to have you on board.\n\nHere are some tips to get started:\n- Complete your profile\n- Add your payment method\n- Book your first ride\n\nIf you have any questions, feel free to reach out.\n\nBest regards,\nEchoride Team', 'notification', 'read', 0, NULL, '2025-04-28 02:26:22', '2025-04-28 02:26:22'),
(10, 6, 6, 'RE: RE: App not working properly', 'hello', 'notification', 'unread', 0, 6, '2025-04-28 02:32:59', '2025-04-28 02:32:59');

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` int(11) NOT NULL,
  `ride_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_method` varchar(50) NOT NULL,
  `payment_status` enum('pending','completed','failed') NOT NULL,
  `transaction_id` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`id`, `ride_id`, `user_id`, `amount`, `payment_method`, `payment_status`, `transaction_id`, `created_at`) VALUES
(2, 3, 6, 1500.00, 'credit_card', 'completed', 'PAY-1820972', '2025-04-28 02:02:20'),
(3, 4, 6, 250.00, 'mpesa', 'completed', 'PAY-6812452', '2025-04-28 02:02:21'),
(4, 5, 7, 2000.00, 'bank_transfer', 'pending', 'PAY-2620382', '2025-04-28 02:02:21'),
(5, 6, 7, 500.00, 'paypal', 'failed', 'PAY-8614239', '2025-04-28 02:02:21'),
(6, 7, 6, 1000.00, 'credit_card', 'completed', 'PAY-6639744', '2025-04-28 02:02:21');

-- --------------------------------------------------------

--
-- Table structure for table `payment_history`
--

CREATE TABLE `payment_history` (
  `id` int(11) NOT NULL,
  `payment_id` int(11) NOT NULL,
  `status` enum('completed','pending','failed') NOT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payment_history`
--

INSERT INTO `payment_history` (`id`, `payment_id`, `status`, `notes`, `created_at`) VALUES
(1, 2, 'completed', 'Payment created with status: completed', '2025-04-28 02:02:21'),
(2, 3, 'completed', 'Payment created with status: completed', '2025-04-28 02:02:21'),
(3, 4, 'pending', 'Payment created with status: pending', '2025-04-28 02:02:21'),
(4, 5, 'failed', 'Payment created with status: failed', '2025-04-28 02:02:21'),
(5, 6, 'completed', 'Payment created with status: completed', '2025-04-28 02:02:21');

-- --------------------------------------------------------

--
-- Table structure for table `rides`
--

CREATE TABLE `rides` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `pickup` varchar(255) NOT NULL,
  `dropoff` varchar(255) NOT NULL,
  `ride_time` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `end_time` datetime DEFAULT NULL,
  `status` varchar(20) DEFAULT 'ongoing',
  `vehicle_id` int(11) NOT NULL,
  `distance` float DEFAULT 0,
  `carbon_saved` float DEFAULT 0,
  `battery_usage` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `rides`
--

INSERT INTO `rides` (`id`, `user_id`, `pickup`, `dropoff`, `ride_time`, `created_at`, `end_time`, `status`, `vehicle_id`, `distance`, `carbon_saved`, `battery_usage`) VALUES
(3, 7, 'Nairobi CBD', 'Westlands', '2025-04-23 04:02:20', '2025-04-28 02:02:20', '2025-04-23 04:28:20', 'completed', 1, 11, 2.2, 13),
(4, 7, 'Kileleshwa', 'Kilimani', '2025-04-16 04:02:20', '2025-04-28 02:02:20', '2025-04-16 04:57:20', 'completed', 1, 15, 3, 7),
(5, 7, 'Lavington', 'Karen', '2025-04-05 04:02:20', '2025-04-28 02:02:20', '2025-04-05 04:34:20', 'completed', 1, 12, 2.4, 6),
(6, 6, 'Embakasi', 'Jomo Kenyatta Airport', '2025-04-24 04:02:20', '2025-04-28 02:02:20', '2025-04-24 04:22:20', 'completed', 1, 20, 4, 13),
(7, 6, 'Ngong Road', 'Upper Hill', '2025-04-16 04:02:20', '2025-04-28 02:02:20', '2025-04-16 04:34:20', 'completed', 1, 16, 3.2, 14),
(8, 7, 'Nairobi', 'Nairobi', '2025-04-30 09:15:00', '2025-04-28 03:12:13', '2025-04-28 05:33:17', 'completed', 2, 13, 2.6, 19),
(9, 6, 'Kiamiko', 'Nairobi', '2025-04-29 08:23:00', '2025-04-28 03:22:10', '2025-04-28 05:29:34', 'completed', 1, 20, 4, 30);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(20) NOT NULL,
  `full_name` varchar(20) NOT NULL,
  `email` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `total_rides` int(11) DEFAULT 0,
  `total_carbon_saved` float DEFAULT 0,
  `wallet_balance` decimal(10,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_admin` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `full_name`, `email`, `password`, `phone`, `address`, `total_rides`, `total_carbon_saved`, `wallet_balance`, `created_at`, `is_admin`) VALUES
(6, 'Admin User', 'admin@echoride.com', '$2y$10$LIZbvZ26sMzVxlOtqnAHbecJkT/ONZPTWSOGWerBqcY55IjEzVtl6', NULL, NULL, 1, 4, 0.00, '2025-04-27 23:58:41', 1),
(7, 'Ian Were', 'ianwere@gmail.com', '$2y$10$cBeJ9P2gIn1LNFbqXeg5L.3zbHdnR2QWqjEL5hy1LnTa.rPhCMIGi', '123456789', '', 1, 2.6, 0.00, '2025-04-28 00:20:01', 0),
(8, 'Test User', 'testuser@example.com', 'password', NULL, NULL, 0, 0, 0.00, '2025-04-28 02:26:22', 0);

-- --------------------------------------------------------

--
-- Table structure for table `vehicles`
--

CREATE TABLE `vehicles` (
  `id` int(11) NOT NULL,
  `type` enum('scooter','bike') NOT NULL,
  `model` varchar(100) NOT NULL,
  `status` enum('available','in_use','maintenance','charging') NOT NULL,
  `battery_level` int(11) NOT NULL,
  `location` varchar(255) NOT NULL,
  `last_maintenance_date` datetime DEFAULT NULL,
  `total_distance` float DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `vehicles`
--

INSERT INTO `vehicles` (`id`, `type`, `model`, `status`, `battery_level`, `location`, `last_maintenance_date`, `total_distance`, `created_at`) VALUES
(1, 'scooter', 'Yamaha', 'available', 100, 'Nairobi', NULL, 0, '2025-04-28 00:10:10'),
(2, 'bike', 'Honda', 'available', 100, 'Nakuru', NULL, 0, '2025-04-28 00:10:29'),
(3, 'scooter', 'Eco', 'in_use', 50, 'Nairobi', NULL, 0, '2025-04-28 00:11:02'),
(4, 'bike', 'Boxer', 'available', 100, 'Nairobi', NULL, 0, '2025-04-28 03:28:36');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `contact_messages`
--
ALTER TABLE `contact_messages`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `maintenance`
--
ALTER TABLE `maintenance`
  ADD PRIMARY KEY (`id`),
  ADD KEY `vehicle_id` (`vehicle_id`),
  ADD KEY `assigned_to` (`assigned_to`);

--
-- Indexes for table `maintenance_history`
--
ALTER TABLE `maintenance_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `maintenance_id` (`maintenance_id`);

--
-- Indexes for table `maintenance_logs`
--
ALTER TABLE `maintenance_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `vehicle_id` (`vehicle_id`);

--
-- Indexes for table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `from_id` (`from_id`),
  ADD KEY `to_id` (`to_id`),
  ADD KEY `reply_to_id` (`reply_to_id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ride_id` (`ride_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `payment_history`
--
ALTER TABLE `payment_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `payment_id` (`payment_id`);

--
-- Indexes for table `rides`
--
ALTER TABLE `rides`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `vehicle_id` (`vehicle_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `vehicles`
--
ALTER TABLE `vehicles`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `contact_messages`
--
ALTER TABLE `contact_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `maintenance`
--
ALTER TABLE `maintenance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `maintenance_history`
--
ALTER TABLE `maintenance_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `maintenance_logs`
--
ALTER TABLE `maintenance_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `messages`
--
ALTER TABLE `messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `payment_history`
--
ALTER TABLE `payment_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `rides`
--
ALTER TABLE `rides`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `vehicles`
--
ALTER TABLE `vehicles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `maintenance`
--
ALTER TABLE `maintenance`
  ADD CONSTRAINT `maintenance_ibfk_1` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `maintenance_ibfk_2` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `maintenance_history`
--
ALTER TABLE `maintenance_history`
  ADD CONSTRAINT `maintenance_history_ibfk_1` FOREIGN KEY (`maintenance_id`) REFERENCES `maintenance` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `maintenance_logs`
--
ALTER TABLE `maintenance_logs`
  ADD CONSTRAINT `maintenance_logs_ibfk_1` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`id`);

--
-- Constraints for table `messages`
--
ALTER TABLE `messages`
  ADD CONSTRAINT `messages_ibfk_1` FOREIGN KEY (`from_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `messages_ibfk_2` FOREIGN KEY (`to_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `messages_ibfk_3` FOREIGN KEY (`reply_to_id`) REFERENCES `messages` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`ride_id`) REFERENCES `rides` (`id`),
  ADD CONSTRAINT `payments_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `payment_history`
--
ALTER TABLE `payment_history`
  ADD CONSTRAINT `payment_history_ibfk_1` FOREIGN KEY (`payment_id`) REFERENCES `payments` (`id`);

--
-- Constraints for table `rides`
--
ALTER TABLE `rides`
  ADD CONSTRAINT `rides_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `rides_ibfk_2` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
