-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 02, 2025 at 03:58 PM
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
-- Database: `zerohunger`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_logs`
--

CREATE TABLE `activity_logs` (
  `log_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `type` enum('donation_created','donation_approved','donation_completed','message_sent','verification','system') NOT NULL,
  `description` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `activity_logs`
--

INSERT INTO `activity_logs` (`log_id`, `user_id`, `type`, `description`, `created_at`) VALUES
(1, 10, 'message_sent', 'User 10 sent message to 1', '2025-11-25 01:50:48');

-- --------------------------------------------------------

--
-- Table structure for table `admin_reports`
--

CREATE TABLE `admin_reports` (
  `id` int(11) NOT NULL,
  `message` text NOT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin_reports`
--

INSERT INTO `admin_reports` (`id`, `message`, `created_at`) VALUES
(1, 'Report created for: don@gmail.com', '2025-11-23 09:07:47'),
(2, 'Report created for: 25-0303c@sgen.edu.ph', '2025-11-25 09:21:18'),
(3, 'Report created for: 25-0303c@sgen.edu.ph', '2025-11-25 09:21:21'),
(4, 'Report created for: 25-0303c@sgen.edu.ph', '2025-11-25 09:21:33');

-- --------------------------------------------------------

--
-- Table structure for table `charitable_institutions`
--

CREATE TABLE `charitable_institutions` (
  `institution_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `institution_name` varchar(200) NOT NULL,
  `number_of_children` int(11) DEFAULT NULL,
  `profile_description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `charitable_institutions`
--

INSERT INTO `charitable_institutions` (`institution_id`, `user_id`, `institution_name`, `number_of_children`, `profile_description`) VALUES
(1, 1, 'Sisters Of Mary', 10, 'School'),
(2, 9, '', 0, ''),
(3, 13, 'Kamay ni Hesus', 200, 'School'),
(4, 15, 'Kamay ni Hesus', 2000, 'School');

-- --------------------------------------------------------

--
-- Table structure for table `donations`
--

CREATE TABLE `donations` (
  `donation_id` int(11) NOT NULL,
  `donor_id` int(11) NOT NULL,
  `item_name` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `quantity` int(11) NOT NULL,
  `expiry_date` date DEFAULT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `donation_status` enum('Pending','Approved','Declined','Delivered') DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `donation_items`
--

CREATE TABLE `donation_items` (
  `item_id` int(11) NOT NULL,
  `donation_id` int(11) NOT NULL,
  `item_name` varchar(255) NOT NULL,
  `quantity` varchar(50) DEFAULT NULL,
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

CREATE TABLE `messages` (
  `message_id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `receiver_id` int(11) NOT NULL,
  `is_charity` tinyint(1) DEFAULT 0,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `messages`
--

INSERT INTO `messages` (`message_id`, `sender_id`, `receiver_id`, `is_charity`, `message`, `is_read`, `created_at`) VALUES
(1, 10, 1, 0, 'lets go', 0, '2025-11-25 01:50:48');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `notification_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `link` varchar(255) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pending_accounts`
--

CREATE TABLE `pending_accounts` (
  `pending_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `submitted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `admin_action` enum('Pending','Approved','Declined') DEFAULT 'Pending',
  `admin_remarks` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pending_accounts`
--

INSERT INTO `pending_accounts` (`pending_id`, `user_id`, `submitted_at`, `admin_action`, `admin_remarks`) VALUES
(1, 1, '2025-11-23 05:14:23', 'Approved', NULL),
(2, 5, '2025-11-23 06:11:50', 'Pending', NULL),
(3, 7, '2025-11-23 08:42:31', 'Pending', NULL),
(4, 8, '2025-11-23 08:49:04', 'Pending', NULL),
(5, 9, '2025-11-23 12:58:26', 'Pending', NULL),
(6, 10, '2025-11-25 01:13:22', 'Approved', NULL),
(7, 11, '2025-11-25 01:26:15', 'Pending', NULL),
(8, 12, '2025-11-25 01:26:15', 'Pending', NULL),
(9, 13, '2025-11-25 02:04:56', 'Approved', NULL),
(10, 15, '2025-11-25 03:05:41', 'Approved', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `transactions`
--

CREATE TABLE `transactions` (
  `transaction_id` int(11) NOT NULL,
  `donation_id` int(11) NOT NULL,
  `institution_id` int(11) NOT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `transaction_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `remarks` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `username` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `phone_number` varchar(20) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `user_type` enum('Donor','CharitableInstitution','Admin') NOT NULL DEFAULT 'Donor',
  `profile_photo` varchar(255) DEFAULT NULL,
  `uploaded_id` varchar(255) DEFAULT NULL,
  `account_status` enum('Active','Pending','Declined') DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `notes` text DEFAULT NULL,
  `selfie` longtext DEFAULT NULL,
  `id_image` longtext DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `name`, `username`, `email`, `phone_number`, `password`, `user_type`, `profile_photo`, `uploaded_id`, `account_status`, `created_at`, `notes`, `selfie`, `id_image`) VALUES
(1, 'Pier', 'chat', 'paulotudla3@gmail.com', '09916752540', '$2y$10$AIesE3TdlxzEATTvEVUKsOIf2/ix4iYy8nLvl.NBzijF.G24JCpaC', 'CharitableInstitution', 'uploads/1763874863_pf_Screenshot_20251022_133507.jpg', 'uploads/1763874863_id_Screenshot_20251022_133507.jpg', 'Active', '2025-11-23 05:14:23', NULL, NULL, NULL),
(5, 'Pier', 'don', 'don@gmail.com', '09916752540', '$2y$10$EbuRrU1rwDLSPo7qtPjgIu1uNFseIW/DgfU9loVOzt7Ou2oujr2p.', 'Donor', 'uploads/1763878309_pf_1000006457.jpg', 'uploads/1763878309_id_Screenshot_20251022_133507.jpg', 'Active', '2025-11-23 06:11:50', NULL, NULL, NULL),
(6, 'Admin', 'admin', 'admin@example.com', '0000', '$2y$10$Wq8fUuF3I8YiZCqQjmVwCO3X3kDn8Hp4uUy0CbZqUpBZHeZ9crr6y', 'Admin', NULL, NULL, 'Active', '2025-11-23 06:43:42', NULL, NULL, NULL),
(7, 'Pier', 'eme', 'donor@supermart.com', '09916752540', '$2y$10$oylzT0Lo7hiIxEYDGB.jgO/gUszE8BUGz.3p4Uv69diya7OglVDc2', 'Donor', 'uploads/1763887351_pf_Screenshot_20251022_133507.jpg', 'uploads/1763887351_id_Screenshot_20251022_133507.jpg', 'Active', '2025-11-23 08:42:31', NULL, NULL, NULL),
(8, 'Pau', 'Pau', '23-065s@sgen.edu.ph', '09916752540', '$2y$10$cZjncPeELXrJiJxuD1caGOIJMH6UB.7HsIwVmBYOoa.2CSx9tyjzy', 'Donor', 'uploads/1763887744_pf_1000012016.jpg', 'uploads/1763887744_id_Messenger_creation_1360289848890989.jpeg', 'Active', '2025-11-23 08:49:04', NULL, NULL, NULL),
(9, 'leo', 'leo', 'leo@gmail.com', '09916752540', '$2y$10$rlsXYQ4Kv/l3.tVWyf7M2OvBPT1VG2.8ecLgKXrUIp76TESx6Fhjm', 'CharitableInstitution', 'uploads/1763902706_pf_Screenshot_20251022_133507.jpg', 'uploads/1763902706_id_Screenshot_20251022_133514.jpg', 'Active', '2025-11-23 12:58:26', NULL, NULL, NULL),
(10, 'Pier Paulo Tudla', 'MrRaccoon', '25-0303c@sgen.edu.ph', '09916752540', '$2y$10$qbFZxOlRjAYcoT6/VbsqL.1lwB9PjzmC2v41V4oEY09pgpSCailRq', 'Donor', 'uploads/1764033202_selfie_692502b2ca9fb.jpeg', 'uploads/1764033202_id_hand1_e_bot_seg_1_cropped.jpeg', 'Active', '2025-11-25 01:13:22', '', 'uploads/1764033202_selfie_692502b2ca9fb.jpeg', NULL),
(11, 'Demo Donor', 'demo_donor', 'demo_donor@local', '09990001111', '$2y$10$uc1YMPRAswzsArsms6uiA.WE2dvaOE1jZUBeo5uAbAAphiu.mZzfK', 'Donor', NULL, NULL, 'Pending', '2025-11-24 18:26:14', NULL, NULL, NULL),
(12, 'Demo Charity', 'demo_charity', 'demo_charity@local', '09990002222', '$2y$10$oAXXGseKGXhQEHpFzxygxuibZfYDLJHsfBUfghVo1tPz5csKJui7q', 'CharitableInstitution', NULL, NULL, 'Pending', '2025-11-24 18:26:14', NULL, NULL, NULL),
(13, 'Riziel Mae Wanillo', 'maemae', 'maemae@gmail.com', '09916752540', '$2y$10$HITmd2m3yDmVA6xlEpeVnOxuUlnYnw5O14aMhcz9Kd8ung.lO2KNC', 'CharitableInstitution', 'uploads/1764036296_selfie_69250ec8283d5.jpeg', 'uploads/1764036296_id_hand1_e_bot_seg_5_cropped.jpeg', 'Active', '2025-11-25 02:04:56', NULL, 'uploads/1764036296_selfie_69250ec8283d5.jpeg', NULL),
(15, 'SOM', 'SOM', 'som@gmail.com', '09916752540', '$2y$10$3dk4CBScXdbX/6Ch8fLnNegd5oblqighmUqLECslETAsJ.DXVHQ4y', 'CharitableInstitution', 'uploads/1764039941_selfie_69251d0527efe.jpeg', 'uploads/1764039941_id_hand1_e_dif_seg_5_cropped.jpeg', 'Active', '2025-11-25 03:05:41', NULL, 'uploads/1764039941_selfie_69251d0527efe.jpeg', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `user_details`
--

CREATE TABLE `user_details` (
  `detail_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `address` varchar(255) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `province` varchar(100) DEFAULT NULL,
  `postal_code` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_type`
--

CREATE TABLE `user_type` (
  `id` int(11) NOT NULL,
  `user_type` enum('Donor','CharitableInstitution','Admin') NOT NULL DEFAULT 'Donor'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `admin_reports`
--
ALTER TABLE `admin_reports`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `charitable_institutions`
--
ALTER TABLE `charitable_institutions`
  ADD PRIMARY KEY (`institution_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `donations`
--
ALTER TABLE `donations`
  ADD PRIMARY KEY (`donation_id`),
  ADD KEY `donor_id` (`donor_id`);

--
-- Indexes for table `donation_items`
--
ALTER TABLE `donation_items`
  ADD PRIMARY KEY (`item_id`),
  ADD KEY `donation_id` (`donation_id`);

--
-- Indexes for table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`message_id`),
  ADD KEY `sender_id` (`sender_id`),
  ADD KEY `receiver_id` (`receiver_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`notification_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `pending_accounts`
--
ALTER TABLE `pending_accounts`
  ADD PRIMARY KEY (`pending_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `transactions`
--
ALTER TABLE `transactions`
  ADD PRIMARY KEY (`transaction_id`),
  ADD KEY `donation_id` (`donation_id`),
  ADD KEY `institution_id` (`institution_id`),
  ADD KEY `approved_by` (`approved_by`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `user_details`
--
ALTER TABLE `user_details`
  ADD PRIMARY KEY (`detail_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `user_type`
--
ALTER TABLE `user_type`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `admin_reports`
--
ALTER TABLE `admin_reports`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `charitable_institutions`
--
ALTER TABLE `charitable_institutions`
  MODIFY `institution_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `donations`
--
ALTER TABLE `donations`
  MODIFY `donation_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `donation_items`
--
ALTER TABLE `donation_items`
  MODIFY `item_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `messages`
--
ALTER TABLE `messages`
  MODIFY `message_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `notification_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pending_accounts`
--
ALTER TABLE `pending_accounts`
  MODIFY `pending_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `transactions`
--
ALTER TABLE `transactions`
  MODIFY `transaction_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `user_details`
--
ALTER TABLE `user_details`
  MODIFY `detail_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_type`
--
ALTER TABLE `user_type`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD CONSTRAINT `activity_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `charitable_institutions`
--
ALTER TABLE `charitable_institutions`
  ADD CONSTRAINT `charitable_institutions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `donations`
--
ALTER TABLE `donations`
  ADD CONSTRAINT `donations_ibfk_1` FOREIGN KEY (`donor_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `donation_items`
--
ALTER TABLE `donation_items`
  ADD CONSTRAINT `donation_items_ibfk_1` FOREIGN KEY (`donation_id`) REFERENCES `donations` (`donation_id`) ON DELETE CASCADE;

--
-- Constraints for table `messages`
--
ALTER TABLE `messages`
  ADD CONSTRAINT `messages_ibfk_1` FOREIGN KEY (`sender_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `messages_ibfk_2` FOREIGN KEY (`receiver_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `pending_accounts`
--
ALTER TABLE `pending_accounts`
  ADD CONSTRAINT `pending_accounts_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `transactions`
--
ALTER TABLE `transactions`
  ADD CONSTRAINT `transactions_ibfk_1` FOREIGN KEY (`donation_id`) REFERENCES `donations` (`donation_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `transactions_ibfk_2` FOREIGN KEY (`institution_id`) REFERENCES `charitable_institutions` (`institution_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `transactions_ibfk_3` FOREIGN KEY (`approved_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `user_details`
--
ALTER TABLE `user_details`
  ADD CONSTRAINT `user_details_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
