-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 15, 2026 at 07:38 AM
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
-- Database: `foodbridge`
--

-- --------------------------------------------------------

--
-- Table structure for table `contact_messages`
--

CREATE TABLE `contact_messages` (
  `id` int(11) NOT NULL,
  `name` varchar(120) NOT NULL,
  `email` varchar(160) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `contact_messages`
--

INSERT INTO `contact_messages` (`id`, `name`, `email`, `message`, `is_read`, `created_at`) VALUES
(1, 'pugal', 'raampugal07@gmail.com', '1st message for testing', 1, '2026-07-07 19:18:10');

-- --------------------------------------------------------

--
-- Table structure for table `food_posts`
--

CREATE TABLE `food_posts` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `food_type` enum('cooked','groceries','vegetables','fruits','other') NOT NULL DEFAULT 'other',
  `food_name` varchar(160) NOT NULL,
  `quantity` varchar(60) NOT NULL,
  `unit` varchar(40) DEFAULT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `location` varchar(180) DEFAULT NULL,
  `latitude` decimal(10,7) DEFAULT NULL,
  `longitude` decimal(10,7) DEFAULT NULL,
  `pickup_date` date DEFAULT NULL,
  `pickup_time` time DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `status` enum('available','claimed','completed','expired') NOT NULL DEFAULT 'available',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `food_posts`
--

INSERT INTO `food_posts` (`id`, `user_id`, `food_type`, `food_name`, `quantity`, `unit`, `photo`, `location`, `latitude`, `longitude`, `pickup_date`, `pickup_time`, `notes`, `status`, `created_at`) VALUES
(1, 1, 'fruits', 'apples', '5', '2', NULL, 'hailela,badulla', 6.9271000, 79.8612000, '2026-06-30', '08:00:00', 'peure and good food', 'claimed', '2026-06-29 19:25:01'),
(2, 1, 'cooked', 'Rice & carry', '3', '2', NULL, 'badulla rithipana', 6.9350000, 79.8500000, '2026-07-04', '10:16:00', 'clean and freshi food', 'claimed', '2026-07-04 04:47:24'),
(3, 1, 'vegetables', 'boile vagitabale', '2', '2', NULL, 'hailela,badulla', 6.9147000, 79.8730000, '2026-07-05', '10:00:00', '', 'claimed', '2026-07-05 06:46:10'),
(4, 1, 'fruits', 'Orange', '5', '2', NULL, 'hailela,badulla', NULL, NULL, NULL, NULL, 'good food', 'claimed', '2026-07-06 17:27:47'),
(5, 1, 'cooked', 'Rice & carry', '2', '2', NULL, 'hailela,badulla', NULL, NULL, '2026-07-07', '07:00:00', 'healthy food', 'claimed', '2026-07-06 18:08:58'),
(20, 1, 'cooked', 'boile vagitabale', '5', 'people', NULL, 'hailela,badulla', 6.9934000, 81.0550000, '2026-07-15', '16:00:00', 'get food', 'claimed', '2026-07-14 20:09:37'),
(21, 1, 'other', 'rotty', '5', 'people', NULL, 'Bandarawela', 6.8304820, 80.9888200, '2026-07-15', '18:00:00', '', 'claimed', '2026-07-15 03:09:30'),
(22, 1, 'cooked', 'Rice & carry', '2', 'people', NULL, 'ketavela', 6.8956400, 80.9172780, '2026-07-15', '20:00:00', 'healthy food', 'available', '2026-07-15 03:12:44'),
(23, 1, 'vegetables', 'apples', '5', 'people', NULL, 'udapusalava,nuwareliya', 6.9995380, 80.9039580, '2026-07-15', '21:00:00', 'hot food', 'available', '2026-07-15 03:19:02'),
(24, 1, 'cooked', 'Rice & chicken', '2', 'people', NULL, 'hailela,badulla', 6.9551690, 81.0322530, '2026-07-15', '20:00:00', 'pic food', 'available', '2026-07-15 05:29:39');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(160) NOT NULL,
  `body` varchar(255) DEFAULT NULL,
  `icon` varchar(40) DEFAULT 'bell',
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `title`, `body`, `icon`, `is_read`, `created_at`) VALUES
(1, 1, 'Donation posted: apples', 'Your food donation is now visible to people in need.', 'gift', 1, '2026-06-29 19:25:01'),
(2, 3, 'Food request sent', 'Your request is now pending. We will notify you when it is approved.', 'hand-helping', 1, '2026-06-30 08:21:16'),
(3, 1, 'Food request sent', 'Your request is now pending. We will notify you when it is approved.', 'hand-helping', 1, '2026-06-30 17:53:44'),
(4, 1, 'A volunteer accepted your request', 'Duli is coming to help you soon.', 'bike', 1, '2026-07-03 16:03:20'),
(5, 1, 'Your food has been delivered 🍱', 'Delivered by Duli. Enjoy your meal!', 'package-check', 1, '2026-07-03 17:03:30'),
(65, 1, 'Donation posted: boile vagitabale', 'Your food donation is now visible to people in need.', 'gift', 1, '2026-07-14 20:09:37'),
(66, 1, 'Your food was claimed by SMS 📟', 'Puhatha Lojan (SMS user) claimed \"boile vagitabale\". Call them: 0775250037', 'message-circle', 1, '2026-07-14 20:10:55'),
(67, 1, 'Donation posted: rotty', 'Your food donation is now visible to people in need.', 'gift', 0, '2026-07-15 03:09:30'),
(68, 1, 'Donation posted: Rice & carry', 'Your food donation is now visible to people in need.', 'gift', 0, '2026-07-15 03:12:44'),
(69, 3, 'Food request sent', 'Your request is now pending. We will notify you when it is approved.', 'hand-helping', 0, '2026-07-15 03:14:31'),
(70, 1, 'Your donation was claimed 🎉', 'karthik claimed \"rotty\". Call them: 0774356609', 'hand', 0, '2026-07-15 03:15:37'),
(71, 1, 'Donation posted: apples', 'Your food donation is now visible to people in need.', 'gift', 0, '2026-07-15 03:19:02'),
(72, 1, 'Donation posted: Rice & chicken', 'Your food donation is now visible to people in need.', 'gift', 0, '2026-07-15 05:29:39');

-- --------------------------------------------------------

--
-- Table structure for table `ratings`
--

CREATE TABLE `ratings` (
  `id` int(11) NOT NULL,
  `from_user` int(11) NOT NULL,
  `to_user` int(11) NOT NULL,
  `request_id` int(11) DEFAULT NULL,
  `stars` tinyint(4) NOT NULL,
  `comment` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `ratings`
--

INSERT INTO `ratings` (`id`, `from_user`, `to_user`, `request_id`, `stars`, `comment`, `created_at`) VALUES
(1, 3, 4, 3, 5, 'very kind person and good food', '2026-07-05 06:18:23');

-- --------------------------------------------------------

--
-- Table structure for table `requests`
--

CREATE TABLE `requests` (
  `id` int(11) NOT NULL,
  `receiver_id` int(11) NOT NULL,
  `food_post_id` int(11) DEFAULT NULL,
  `volunteer_id` int(11) DEFAULT NULL,
  `food_type` enum('cooked','groceries','vegetables','fruits','other') NOT NULL DEFAULT 'other',
  `description` varchar(255) NOT NULL,
  `people_count` int(11) DEFAULT 1,
  `location` varchar(180) DEFAULT NULL,
  `latitude` decimal(10,7) DEFAULT NULL,
  `longitude` decimal(10,7) DEFAULT NULL,
  `urgency` enum('urgent','today','anytime') NOT NULL DEFAULT 'today',
  `status` enum('pending','approved','assigned','delivered','rejected') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `requests`
--

INSERT INTO `requests` (`id`, `receiver_id`, `food_post_id`, `volunteer_id`, `food_type`, `description`, `people_count`, `location`, `latitude`, `longitude`, `urgency`, `status`, `created_at`) VALUES
(1, 3, NULL, 3, 'fruits', 'food', 3, 'badhulla', 6.9200000, 79.8600000, 'anytime', 'assigned', '2026-06-30 08:21:16'),
(2, 1, NULL, 4, 'cooked', 'food', 5, 'hailela,badulla', 6.9280000, 79.8650000, 'today', 'delivered', '2026-06-30 17:53:44'),
(3, 3, NULL, 4, 'cooked', 'coke food', 1, 'Badhulla', NULL, NULL, 'today', 'delivered', '2026-07-05 06:11:32'),
(4, 3, 1, 4, 'fruits', 'apples', 1, 'hailela,badulla', NULL, NULL, 'today', 'assigned', '2026-07-05 06:49:04'),
(5, 3, 2, NULL, 'cooked', 'Rice & carry', 1, 'badulla rithipana', NULL, NULL, 'today', 'approved', '2026-07-06 14:30:57'),
(6, 3, NULL, NULL, 'cooked', 'Rice & chicken', 1, 'hailela,badulla', NULL, NULL, 'today', 'approved', '2026-07-11 05:43:57'),
(22, 3, NULL, NULL, 'cooked', 'need good food for eat', 5, 'Badhulla,hali ela', 6.9551690, 81.0322530, 'today', 'pending', '2026-07-15 03:14:31'),
(23, 3, 21, NULL, 'other', 'rotty', 1, 'Bandarawela', NULL, NULL, 'today', 'approved', '2026-07-15 03:15:37');

-- --------------------------------------------------------

--
-- Table structure for table `sms_logs`
--

CREATE TABLE `sms_logs` (
  `id` int(11) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `recipient_name` varchar(120) DEFAULT NULL,
  `message` varchar(255) NOT NULL,
  `food_post_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sms_logs`
--

INSERT INTO `sms_logs` (`id`, `phone`, `recipient_name`, `message`, `food_post_id`, `created_at`) VALUES
(1, '0775250037', 'Puhatha Lojan', 'FoodBridge: Free food \"mangoes\" (5 3) at Bandarawela. Call 0788231532. Reply YES to claim.', 15, '2026-07-11 22:55:37'),
(2, '0775250037', 'Puhatha Lojan', 'You claimed \"mangoes\". Call the donor at 0788231532 and collect at Bandarawela.', 15, '2026-07-11 22:56:54'),
(3, '0775250037', 'Puhatha Lojan', 'FoodBridge: Free food \"itly\" (5 people) at diyatalawa. Call 0788231532. Reply YES to claim.', 16, '2026-07-13 14:41:17'),
(4, '0775250037', 'Puhatha Lojan', 'FoodBridge: Free food \"apples\" (2 people) at hailela,badulla. Call 0788231532. Reply YES to claim.', 17, '2026-07-13 20:30:27'),
(5, '0775250037', 'Puhatha Lojan', 'FoodBridge: Free food \"Rice & chicken\" (2 people) at Bandarawela. Call 0788231532. Reply YES to claim.', 18, '2026-07-13 20:55:08'),
(6, '0775250037', 'Puhatha Lojan', 'You claimed \"Rice & chicken\". Call the donor at 0788231532 and collect at Bandarawela.', 18, '2026-07-13 20:56:35'),
(7, '0775250037', 'Puhatha Lojan', 'FoodBridge: Free food \"Rice & chicken\" (2 people) at hailela,badulla. Call 0788231532. Reply YES to claim.', 19, '2026-07-14 10:45:17'),
(8, '0775250037', 'Puhatha Lojan', 'FoodBridge: Free food \"boile vagitabale\" (5 people) at hailela,badulla. Call 0788231532. Reply YES to claim.', 20, '2026-07-14 20:09:37'),
(9, '0775250037', 'Puhatha Lojan', 'You claimed \"boile vagitabale\". Call the donor at 0788231532 and collect at hailela,badulla.', 20, '2026-07-14 20:10:55'),
(10, '0775250037', 'Puhatha Lojan', 'FoodBridge: Free food \"rotty\" (5 people) at Bandarawela. Call 0788231532. Reply YES to claim.', 21, '2026-07-15 03:09:30'),
(11, '0775250037', 'Puhatha Lojan', 'FoodBridge: Free food \"Rice & carry\" (2 people) at ketavela. Call 0788231532. Reply YES to claim.', 22, '2026-07-15 03:12:44'),
(12, '0775250037', 'Puhatha Lojan', 'FoodBridge: Free food \"apples\" (5 people) at udapusalava,nuwareliya. Call 0788231532. Reply YES to claim.', 23, '2026-07-15 03:19:02'),
(13, '0775250037', 'Puhatha Lojan', 'FoodBridge: Free food \"Rice & chicken\" (2 people) at hailela,badulla. Call 0788231532. Reply YES to claim.', 24, '2026-07-15 05:29:39');

-- --------------------------------------------------------

--
-- Table structure for table `sms_subscribers`
--

CREATE TABLE `sms_subscribers` (
  `id` int(11) NOT NULL,
  `name` varchar(120) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `location` varchar(180) DEFAULT NULL,
  `added_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sms_subscribers`
--

INSERT INTO `sms_subscribers` (`id`, `name`, `phone`, `location`, `added_by`, `created_at`) VALUES
(1, 'Puhatha Lojan', '0775250037', 'haliela,badhulla', 2, '2026-07-11 22:30:18');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(120) NOT NULL,
  `email` varchar(160) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('admin','giver','receiver','volunteer') NOT NULL DEFAULT 'giver',
  `location` varchar(180) DEFAULT NULL,
  `latitude` decimal(10,7) DEFAULT NULL,
  `longitude` decimal(10,7) DEFAULT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `language` enum('en','ta','si') NOT NULL DEFAULT 'en',
  `status` enum('active','blocked','pending') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `phone`, `password_hash`, `role`, `location`, `latitude`, `longitude`, `avatar`, `language`, `status`, `created_at`) VALUES
(1, 'puvanalojan', 'lojan@gmail.com', '0788231532', '$2y$10$4qQirNpWvWB7PtKUYYhoo.SsvirC7q6G5aymC/HTPke6kRVnsAm6a', 'giver', 'hailela,badulla', NULL, NULL, NULL, 'en', 'active', '2026-06-29 03:10:05'),
(2, 'pugalMarxis', 'pugal@foodbridge.lk', '0770000000', '$2y$10$p8KJvRiHt2ivCn0yAadzo.K70hpOEGJfi5N356CM4LfdF8LBKZzpm', 'admin', 'Badulla', NULL, NULL, NULL, 'en', 'active', '2026-06-29 19:59:22'),
(3, 'karthik', 'karthik@gmail.com', '0774356609', '$2y$10$PhLCt8wStrhlM4ywQJasU.kLH.y7UtygJPiHkgT4IKnwCV0CpIdBy', 'receiver', 'Badhulla', NULL, NULL, NULL, 'en', 'active', '2026-06-30 08:13:34'),
(4, 'Duli', 'duli@gmail.com', '0775478890', '$2y$10$Mj7iZmi0qeSK34K.FNP13ulyznAiHxeahR9cEHazEQpSa.Z9BMAky', 'volunteer', 'badulla', NULL, NULL, NULL, 'en', 'active', '2026-07-03 14:53:57');

-- --------------------------------------------------------

--
-- Table structure for table `volunteers`
--

CREATE TABLE `volunteers` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `vehicle_type` enum('walking','bicycle','motorbike','car') NOT NULL DEFAULT 'walking',
  `points` int(11) NOT NULL DEFAULT 0,
  `rating` decimal(2,1) NOT NULL DEFAULT 0.0,
  `deliveries` int(11) NOT NULL DEFAULT 0,
  `is_available` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `volunteers`
--

INSERT INTO `volunteers` (`id`, `user_id`, `vehicle_type`, `points`, `rating`, `deliveries`, `is_available`) VALUES
(1, 4, 'walking', 30, 5.0, 3, 1);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `contact_messages`
--
ALTER TABLE `contact_messages`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `food_posts`
--
ALTER TABLE `food_posts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_food_user` (`user_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_notif_user` (`user_id`);

--
-- Indexes for table `ratings`
--
ALTER TABLE `ratings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_rate_from` (`from_user`),
  ADD KEY `fk_rate_to` (`to_user`);

--
-- Indexes for table `requests`
--
ALTER TABLE `requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_req_receiver` (`receiver_id`),
  ADD KEY `fk_req_food` (`food_post_id`),
  ADD KEY `fk_req_volunteer` (`volunteer_id`);

--
-- Indexes for table `sms_logs`
--
ALTER TABLE `sms_logs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `sms_subscribers`
--
ALTER TABLE `sms_subscribers`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `volunteers`
--
ALTER TABLE `volunteers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_vol_user` (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `contact_messages`
--
ALTER TABLE `contact_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `food_posts`
--
ALTER TABLE `food_posts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=73;

--
-- AUTO_INCREMENT for table `ratings`
--
ALTER TABLE `ratings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `requests`
--
ALTER TABLE `requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `sms_logs`
--
ALTER TABLE `sms_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `sms_subscribers`
--
ALTER TABLE `sms_subscribers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `volunteers`
--
ALTER TABLE `volunteers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `food_posts`
--
ALTER TABLE `food_posts`
  ADD CONSTRAINT `fk_food_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `fk_notif_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `ratings`
--
ALTER TABLE `ratings`
  ADD CONSTRAINT `fk_rate_from` FOREIGN KEY (`from_user`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_rate_to` FOREIGN KEY (`to_user`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `requests`
--
ALTER TABLE `requests`
  ADD CONSTRAINT `fk_req_food` FOREIGN KEY (`food_post_id`) REFERENCES `food_posts` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_req_receiver` FOREIGN KEY (`receiver_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_req_volunteer` FOREIGN KEY (`volunteer_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `volunteers`
--
ALTER TABLE `volunteers`
  ADD CONSTRAINT `fk_vol_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
