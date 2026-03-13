-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Jan 14, 2026 at 09:22 AM
-- Server version: 9.1.0
-- PHP Version: 8.3.14

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `apu_sustainable_transport`
--

-- --------------------------------------------------------

--
-- Table structure for table `administrators`
--

DROP TABLE IF EXISTS `administrators`;
CREATE TABLE IF NOT EXISTS `administrators` (
  `admin_id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `department` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`admin_id`),
  KEY `user_id` (`user_id`)
) ENGINE=MyISAM AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `administrators`
--

INSERT INTO `administrators` (`admin_id`, `user_id`, `department`) VALUES
(1, 4, 'Admin'),
(2, 5, 'Admin'),
(3, 11, 'Admin'),
(4, 4, 'Admin'),
(5, 14, 'Admin');

-- --------------------------------------------------------

--
-- Table structure for table `admin_analytics`
--

DROP TABLE IF EXISTS `admin_analytics`;
CREATE TABLE IF NOT EXISTS `admin_analytics` (
  `analytics_id` int NOT NULL AUTO_INCREMENT,
  `admin_id` int NOT NULL,
  `analysis_text` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`analytics_id`),
  KEY `admin_id` (`admin_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `challenges`
--

DROP TABLE IF EXISTS `challenges`;
CREATE TABLE IF NOT EXISTS `challenges` (
  `challenge_id` int NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`challenge_id`)
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `challenges`
--

INSERT INTO `challenges` (`challenge_id`, `title`, `description`, `start_date`, `end_date`, `created_at`) VALUES
(1, 'Go Green', 'hi hi hi', '2025-12-07', '2025-12-13', '2025-12-07 09:07:05'),
(2, 'Plastic free week', 'All the best', '2026-01-11', '2026-01-12', '2026-01-11 15:34:48'),
(3, 'Plastic free', 'All the best.', '2026-01-11', '2026-01-16', '2026-01-11 15:39:14');

-- --------------------------------------------------------

--
-- Table structure for table `community_partners`
--

DROP TABLE IF EXISTS `community_partners`;
CREATE TABLE IF NOT EXISTS `community_partners` (
  `partner_id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `is_verified` tinyint(1) DEFAULT '0',
  `vehicle_model` varchar(100) DEFAULT NULL,
  `license_plate` varchar(20) DEFAULT NULL,
  `capacity` int DEFAULT '4',
  PRIMARY KEY (`partner_id`),
  KEY `user_id` (`user_id`)
) ENGINE=MyISAM AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `community_partners`
--

INSERT INTO `community_partners` (`partner_id`, `user_id`, `full_name`, `is_verified`, `vehicle_model`, `license_plate`, `capacity`) VALUES
(1, 3, 'kovalan', 1, 'Myvi', 'JTK8328', 4),
(2, 6, 'leo das', 1, NULL, NULL, 4),
(3, 8, 'Elijah', 1, NULL, NULL, 4),
(4, 10, 'Denish', 1, NULL, NULL, 4),
(5, 13, 'Parthiban', 1, 'Myvi', 'WLG 1234', 4),
(6, 18, 'Denish', 1, NULL, NULL, 4),
(7, 19, 'Ganesan', 1, NULL, NULL, 4),
(8, 20, 'Shiva', 0, 'Saga', 'VNR1234', 4);

-- --------------------------------------------------------

--
-- Table structure for table `content_posts`
--

DROP TABLE IF EXISTS `content_posts`;
CREATE TABLE IF NOT EXISTS `content_posts` (
  `post_id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `title` varchar(255) NOT NULL,
  `body` text NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`post_id`)
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `content_posts`
--

INSERT INTO `content_posts` (`post_id`, `user_id`, `title`, `body`, `created_at`) VALUES
(1, 5, 'Go Green APU', 'GOOD GOOD GOOD GOOD GOOD GOOD GOOD GOOD GOOD GOOD', '2025-12-07 08:57:40'),
(2, 14, 'Go green', 'Go green', '2026-01-11 15:34:14'),
(3, 14, 'Go green Safe', 'All the best', '2026-01-11 15:38:38');

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

DROP TABLE IF EXISTS `customers`;
CREATE TABLE IF NOT EXISTS `customers` (
  `customer_id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `apu_id` varchar(30) DEFAULT NULL,
  `total_points` int DEFAULT '0',
  PRIMARY KEY (`customer_id`),
  KEY `user_id` (`user_id`)
) ENGINE=MyISAM AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `customers`
--

INSERT INTO `customers` (`customer_id`, `user_id`, `apu_id`, `total_points`) VALUES
(1, 1, NULL, 88),
(2, 2, NULL, 0),
(3, 7, NULL, 0),
(4, 9, NULL, 0),
(5, 12, NULL, 54),
(6, 15, NULL, 0),
(7, 16, NULL, 0),
(8, 17, NULL, 0),
(9, 21, NULL, 4);

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

DROP TABLE IF EXISTS `messages`;
CREATE TABLE IF NOT EXISTS `messages` (
  `message_id` int NOT NULL AUTO_INCREMENT,
  `sender_id` int NOT NULL,
  `receiver_id` int NOT NULL,
  `ride_id` int DEFAULT NULL,
  `message_text` text NOT NULL,
  `sent_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`message_id`),
  KEY `sender_id` (`sender_id`),
  KEY `receiver_id` (`receiver_id`),
  KEY `ride_id` (`ride_id`)
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `messages`
--

INSERT INTO `messages` (`message_id`, `sender_id`, `receiver_id`, `ride_id`, `message_text`, `sent_at`) VALUES
(1, 12, 1, NULL, 'Thak you', '2026-01-03 16:32:06'),
(2, 20, 1, NULL, 'Hello...', '2026-01-11 15:31:21'),
(3, 21, 1, NULL, 'Hi...', '2026-01-11 15:48:56');

-- --------------------------------------------------------

--
-- Table structure for table `points_history`
--

DROP TABLE IF EXISTS `points_history`;
CREATE TABLE IF NOT EXISTS `points_history` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `change_value` int NOT NULL,
  `reason` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=MyISAM AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `points_history`
--

INSERT INTO `points_history` (`id`, `user_id`, `change_value`, `reason`, `created_at`) VALUES
(1, 1, 6, 'Trip: Carpool 3 km', '2025-12-06 16:27:00'),
(2, 1, 20, 'Trip: Public Bus 10 km', '2025-12-06 16:28:42'),
(3, 1, 16, 'Trip: Carpool 8 km', '2025-12-06 17:05:41'),
(4, 1, 18, 'Trip: Carpool 9 km', '2025-12-06 17:32:51'),
(5, 1, 12, 'Trip: Walk 6 km', '2025-12-06 19:07:52'),
(6, 1, 16, 'Trip: Walk 8 km', '2025-12-07 06:07:47'),
(7, 1, 12, 'Trip: Walk 6 km', '2025-12-07 07:10:13'),
(8, 1, 8, 'Trip: Walk 4 km', '2025-12-07 10:08:31'),
(9, 12, 30, 'Trip: Carpool 15 km', '2026-01-03 16:31:47'),
(10, 12, 10, 'Trip: Walk 5 km', '2026-01-05 05:16:07'),
(11, 12, 4, 'Trip: Bicycle 2 km', '2026-01-07 04:03:04'),
(12, 13, 4, 'Trip: Bicycle 2 km', '2026-01-11 07:42:58'),
(13, 19, 8, 'Trip: Bicycle 4 km', '2026-01-11 15:26:54'),
(14, 20, 44, 'Trip: Public Bus 22 km', '2026-01-11 15:30:20'),
(15, 12, 50, 'Admin Award', '2026-01-11 15:40:34'),
(16, 21, 24, 'Trip: Bicycle 12 km', '2026-01-11 15:48:02');

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

DROP TABLE IF EXISTS `reviews`;
CREATE TABLE IF NOT EXISTS `reviews` (
  `review_id` int NOT NULL AUTO_INCREMENT,
  `ride_id` int NOT NULL,
  `reviewer_id` int NOT NULL,
  `driver_partner_id` int NOT NULL,
  `rating` int NOT NULL,
  `comment` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`review_id`)
) ENGINE=MyISAM AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `reviews`
--

INSERT INTO `reviews` (`review_id`, `ride_id`, `reviewer_id`, `driver_partner_id`, `rating`, `comment`, `created_at`) VALUES
(1, 1, 1, 1, 5, 'good', '2025-12-07 09:55:13'),
(2, 1, 3, 1, 5, 'good', '2025-12-07 09:56:03'),
(3, 7, 9, 4, 5, 'good', '2025-12-09 07:15:18'),
(4, 8, 12, 5, 5, '', '2026-01-03 16:31:17'),
(5, 9, 12, 5, 5, '', '2026-01-06 07:45:38'),
(6, 10, 12, 5, 5, '', '2026-01-07 04:08:51'),
(7, 11, 12, 5, 5, '', '2026-01-11 15:16:32');

-- --------------------------------------------------------

--
-- Table structure for table `rewards`
--

DROP TABLE IF EXISTS `rewards`;
CREATE TABLE IF NOT EXISTS `rewards` (
  `reward_id` int NOT NULL AUTO_INCREMENT,
  `title` varchar(100) DEFAULT NULL,
  `points_cost` int DEFAULT NULL,
  `stock` int DEFAULT NULL,
  PRIMARY KEY (`reward_id`)
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `rewards`
--

INSERT INTO `rewards` (`reward_id`, `title`, `points_cost`, `stock`) VALUES
(1, 'free points', 20, 16),
(2, 'Free Voucher', 100, 1);

-- --------------------------------------------------------

--
-- Table structure for table `reward_claims`
--

DROP TABLE IF EXISTS `reward_claims`;
CREATE TABLE IF NOT EXISTS `reward_claims` (
  `claim_id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `reward_id` int NOT NULL,
  `claimed_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`claim_id`),
  KEY `user_id` (`user_id`),
  KEY `reward_id` (`reward_id`)
) ENGINE=MyISAM AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `reward_claims`
--

INSERT INTO `reward_claims` (`claim_id`, `user_id`, `reward_id`, `claimed_at`) VALUES
(1, 1, 1, '2025-12-07 12:18:27'),
(2, 12, 1, '2026-01-03 16:31:52'),
(3, 12, 1, '2026-01-05 05:16:25'),
(4, 21, 1, '2026-01-11 15:48:30');

-- --------------------------------------------------------

--
-- Table structure for table `rides`
--

DROP TABLE IF EXISTS `rides`;
CREATE TABLE IF NOT EXISTS `rides` (
  `ride_id` int NOT NULL AUTO_INCREMENT,
  `driver_partner_id` int NOT NULL,
  `vehicle_id` int DEFAULT NULL,
  `origin` varchar(100) DEFAULT NULL,
  `destination` varchar(100) DEFAULT NULL,
  `departure_time` datetime DEFAULT NULL,
  `is_recurring` tinyint(1) DEFAULT '0',
  `status` enum('Scheduled','Completed','Cancelled') DEFAULT 'Scheduled',
  `price` decimal(10,2) DEFAULT '0.00',
  PRIMARY KEY (`ride_id`),
  KEY `driver_partner_id` (`driver_partner_id`),
  KEY `vehicle_id` (`vehicle_id`)
) ENGINE=MyISAM AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `rides`
--

INSERT INTO `rides` (`ride_id`, `driver_partner_id`, `vehicle_id`, `origin`, `destination`, `departure_time`, `is_recurring`, `status`, `price`) VALUES
(1, 1, NULL, 'kl', 'jb', '2025-12-07 06:35:00', 0, 'Completed', 0.00),
(2, 1, NULL, 'kl', 'jb', '2025-12-07 14:10:00', 0, 'Completed', 0.00),
(3, 1, NULL, 'kl', 'jb', '2025-12-15 15:10:00', 0, 'Completed', 0.00),
(4, 2, NULL, 'apu', 'pd', '2025-12-07 15:13:00', 0, 'Scheduled', 0.00),
(5, 1, NULL, 'apu', 'medan', '2025-12-07 20:19:00', 0, 'Scheduled', 50.00),
(6, 1, NULL, 'apu', 'taman roja', '2025-12-07 06:35:00', 0, 'Completed', 50.00),
(7, 4, NULL, 'apu', 'GG', '2025-12-11 15:11:00', 0, 'Completed', 90.00),
(8, 5, NULL, 'APU', 'putra height', '2026-01-01 00:58:00', 0, 'Completed', 55.00),
(9, 5, NULL, '16 sierra', 'Apu', '2026-01-05 13:19:00', 0, 'Completed', 30.00),
(10, 5, NULL, 'APU', 'Subang', '2026-01-07 12:06:00', 0, 'Completed', 25.00),
(11, 5, NULL, 'APU', 'Sunway', '2026-01-09 23:32:00', 0, 'Completed', 25.00),
(12, 8, NULL, 'APU', 'Sunway', '2026-01-11 23:31:00', 0, 'Scheduled', 30.00);

-- --------------------------------------------------------

--
-- Table structure for table `ride_bookings`
--

DROP TABLE IF EXISTS `ride_bookings`;
CREATE TABLE IF NOT EXISTS `ride_bookings` (
  `booking_id` int NOT NULL AUTO_INCREMENT,
  `ride_id` int NOT NULL,
  `passenger_user_id` int NOT NULL,
  `status` enum('Pending','Confirmed','Cancelled') DEFAULT 'Pending',
  `rating` int DEFAULT NULL,
  `review_text` text,
  PRIMARY KEY (`booking_id`),
  KEY `ride_id` (`ride_id`),
  KEY `passenger_user_id` (`passenger_user_id`)
) ENGINE=MyISAM AUTO_INCREMENT=28 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `ride_bookings`
--

INSERT INTO `ride_bookings` (`booking_id`, `ride_id`, `passenger_user_id`, `status`, `rating`, `review_text`) VALUES
(1, 1, 3, '', NULL, NULL),
(2, 1, 1, '', NULL, NULL),
(3, 1, 1, '', NULL, NULL),
(4, 4, 6, 'Pending', NULL, NULL),
(5, 1, 3, '', NULL, NULL),
(6, 1, 1, '', NULL, NULL),
(7, 4, 1, 'Pending', NULL, NULL),
(8, 5, 7, 'Pending', NULL, NULL),
(9, 4, 7, 'Pending', NULL, NULL),
(10, 5, 7, 'Pending', NULL, NULL),
(11, 5, 7, 'Pending', NULL, NULL),
(12, 5, 9, 'Pending', NULL, NULL),
(13, 7, 9, '', NULL, NULL),
(14, 5, 12, 'Pending', NULL, NULL),
(15, 8, 12, '', NULL, NULL),
(16, 5, 12, 'Pending', NULL, NULL),
(17, 9, 12, '', NULL, NULL),
(18, 5, 12, 'Pending', NULL, NULL),
(19, 10, 12, '', NULL, NULL),
(20, 5, 12, 'Pending', NULL, NULL),
(21, 5, 12, 'Pending', NULL, NULL),
(22, 11, 12, '', NULL, NULL),
(23, 11, 16, '', NULL, NULL),
(24, 11, 17, '', NULL, NULL),
(25, 11, 17, '', NULL, NULL),
(26, 4, 13, 'Pending', NULL, NULL),
(27, 12, 21, 'Pending', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `transactions`
--

DROP TABLE IF EXISTS `transactions`;
CREATE TABLE IF NOT EXISTS `transactions` (
  `transaction_id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `type` enum('Credit','Debit') NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`transaction_id`)
) ENGINE=MyISAM AUTO_INCREMENT=29 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `transactions`
--

INSERT INTO `transactions` (`transaction_id`, `user_id`, `type`, `amount`, `description`, `created_at`) VALUES
(1, 7, 'Credit', 222.00, 'Wallet Top Up', '2025-12-07 14:41:38'),
(2, 9, 'Credit', 200.00, 'Wallet Top Up', '2025-12-09 07:10:16'),
(3, 9, 'Debit', 90.00, 'Ride to GG', '2025-12-09 07:14:17'),
(4, 10, 'Credit', 90.00, 'Earnings: Ride to GG', '2025-12-09 07:14:17'),
(5, 12, 'Credit', 100000.00, 'Wallet Top Up', '2025-12-31 16:56:02'),
(6, 12, 'Debit', 55.00, 'Ride to putra height', '2025-12-31 17:00:20'),
(7, 13, 'Credit', 55.00, 'Earnings: Ride to putra height', '2025-12-31 17:00:20'),
(8, 12, 'Credit', 12.00, 'Wallet Top Up', '2026-01-05 05:16:55'),
(9, 12, 'Credit', 2.12, 'Wallet Top Up', '2026-01-05 05:17:25'),
(10, 12, 'Debit', 30.00, 'Ride to Apu', '2026-01-05 05:21:06'),
(11, 13, 'Credit', 30.00, 'Earnings: Ride to Apu', '2026-01-05 05:21:06'),
(12, 12, 'Credit', 12.00, 'Wallet Top Up', '2026-01-06 07:28:57'),
(13, 12, 'Credit', 9.00, 'Wallet Top Up', '2026-01-06 07:52:24'),
(14, 12, 'Credit', 99999999.99, 'Wallet Top Up', '2026-01-06 14:07:29'),
(15, 12, 'Credit', 2.00, 'Wallet Top Up', '2026-01-06 14:07:42'),
(16, 12, 'Credit', 2.00, 'Wallet Top Up via Card ****2606', '2026-01-06 15:01:47'),
(17, 12, 'Debit', 25.00, 'Ride to Subang', '2026-01-07 04:08:26'),
(18, 13, 'Credit', 25.00, 'Earnings: Ride to Subang', '2026-01-07 04:08:26'),
(19, 16, 'Credit', 50.00, 'Wallet Top Up via Card ****4444', '2026-01-09 15:37:32'),
(20, 17, 'Credit', 29.00, 'Wallet Top Up via Card ****4444', '2026-01-09 15:39:11'),
(21, 12, 'Debit', 25.00, 'Ride to Sunway', '2026-01-09 15:48:56'),
(22, 16, 'Debit', 25.00, 'Ride to Sunway', '2026-01-09 15:48:56'),
(23, 17, 'Debit', 25.00, 'Ride to Sunway', '2026-01-09 15:48:56'),
(24, 17, 'Debit', 25.00, 'Ride to Sunway', '2026-01-09 15:48:56'),
(25, 13, 'Credit', 100.00, 'Earnings: Ride to Sunway', '2026-01-09 15:48:56'),
(26, 19, 'Credit', 20.00, 'Wallet Top Up via Card ****1234', '2026-01-11 15:27:30'),
(27, 20, 'Credit', 20.00, 'Wallet Top Up via Card ****1234', '2026-01-11 15:30:54'),
(28, 21, 'Credit', 50.00, 'Wallet Top Up via Card ****1234', '2026-01-11 15:47:31');

-- --------------------------------------------------------

--
-- Table structure for table `trip_logs`
--

DROP TABLE IF EXISTS `trip_logs`;
CREATE TABLE IF NOT EXISTS `trip_logs` (
  `log_id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `transport_type` enum('Bicycle','Walk','Public Bus','Carpool') NOT NULL,
  `distance_km` decimal(6,2) DEFAULT NULL,
  `co2_saved_kg` decimal(6,2) DEFAULT NULL,
  `cost_saved_rm` decimal(6,2) DEFAULT NULL,
  `log_date` date DEFAULT NULL,
  PRIMARY KEY (`log_id`),
  KEY `user_id` (`user_id`)
) ENGINE=MyISAM AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `trip_logs`
--

INSERT INTO `trip_logs` (`log_id`, `user_id`, `transport_type`, `distance_km`, `co2_saved_kg`, `cost_saved_rm`, `log_date`) VALUES
(1, 1, 'Carpool', 3.00, 0.60, 0.60, '2025-12-06'),
(2, 1, 'Public Bus', 10.00, 2.00, 2.00, '2025-12-06'),
(3, 1, 'Carpool', 8.00, 1.60, 1.60, '2025-12-06'),
(4, 1, 'Carpool', 9.00, 1.80, 1.80, '2025-12-06'),
(5, 1, 'Walk', 6.00, 1.20, 0.00, '2025-12-06'),
(6, 1, 'Walk', 8.00, 1.60, 0.00, '2025-12-07'),
(7, 1, 'Walk', 6.00, 1.20, 0.00, '2025-12-07'),
(8, 1, 'Walk', 4.00, 0.80, 0.00, '2025-12-07'),
(9, 12, 'Carpool', 15.00, 3.00, 3.00, '2026-01-03'),
(10, 12, 'Walk', 5.00, 1.00, 0.00, '2026-01-05'),
(11, 12, 'Bicycle', 2.00, 0.40, 0.00, '2026-01-07'),
(12, 13, 'Bicycle', 2.00, 0.40, 0.00, '2026-01-11'),
(13, 19, 'Bicycle', 4.00, 0.80, 0.00, '2026-01-11'),
(14, 20, 'Public Bus', 22.00, 4.40, 4.40, '2026-01-11'),
(15, 21, 'Bicycle', 12.00, 2.40, 0.00, '2026-01-11');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `user_id` int NOT NULL AUTO_INCREMENT,
  `full_name` varchar(100) DEFAULT NULL,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('Student','Driver','Admin') NOT NULL,
  `wallet_balance` decimal(10,2) DEFAULT '0.00',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `phone_number` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=MyISAM AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `full_name`, `email`, `password_hash`, `role`, `wallet_balance`, `created_at`, `phone_number`) VALUES
(1, 'Mugi', 'konnde@gmail.com', '$2y$10$CEGWjrNLRzEoPP2uWlapEewVhvNU9n9XNvqHJAX4uE4lZFKBEHwCu', 'Student', 0.00, '2025-12-06 16:14:04', '01133201565'),
(2, 'paal', 'paal@gmail.com', '$2y$10$uZHKCOpL6prtfp5.rQygDuldV5DIvYmcaxrXM7d8NmMRerUYuXHpO', 'Student', 0.00, '2025-12-06 22:33:34', NULL),
(3, 'kovalan', 'kovalan@gmail.com', '$2y$10$IrJAEYlF51Xx72DOUcog4.TGMB.wOkacRcodGqqfK32s4n1CRStjm', 'Driver', 0.00, '2025-12-06 22:34:55', NULL),
(4, 'ravi', 'ravi@gmail.com', '$2y$10$ax3bOwRdSv52D4jcoWN5kOd1YnHGzVZff/zyR0AwxrvRgtZmV0uzG', 'Admin', 0.00, '2025-12-06 22:37:27', NULL),
(5, 'mani', 'mani@gmail.com', '$2y$10$u9Y7H7ohKwPZlaKuZ/qtOe.yMwQ/r1dooZuzD8ZNKUYjVEPhREdCa', 'Admin', 0.00, '2025-12-07 06:11:25', NULL),
(6, 'leo das', 'leo@gmail.com', '$2y$10$MnJTHHkU8SG855Abo5dWv.JaVKgt0woTjXkEPjtJagLkzOaPbcMO6', 'Driver', 0.00, '2025-12-07 07:12:17', NULL),
(7, 'Denish', 'DENISH@gmail.com', '$2y$10$3ndA7DUzOW/AQs1tne0.CuzXJCixD8a3ZvsFyAZ2PJss9EPyWnCei', 'Student', 222.00, '2025-12-07 13:31:32', '+60182135493'),
(8, 'Elijah', 'Elijah@gmail.com', '$2y$10$ANf/MosD2O1T6zrpPDFc5.RNnUO7yNt7jMxzsHZQmy/sHTqADt2Uq', 'Driver', 0.00, '2025-12-07 13:32:35', NULL),
(9, 'BARSHAN', 'B@gmail.com', '$2y$10$gsiECEVIYYLsRrZEFJ6UlOSZpdtAAI4xJLJZrvRpxWpMaQEalv.yS', 'Student', 110.00, '2025-12-09 07:09:14', NULL),
(10, 'Denish', 'D@gmail.com', '$2y$10$qZzNNC2ruPxkjXrCiHFNhejNwg7697KBfH3lAVdeTq8bEfxAIMj/G', 'Driver', 90.00, '2025-12-09 07:10:59', NULL),
(11, 'KKK', 'K@gmail.com', '$2y$10$BLsGd0UQmgiPjp0u4o.ImuctOOUZCFd9CHcYsbonkKwoHu2ltwg0y', 'Admin', 0.00, '2025-12-09 07:17:26', NULL),
(12, 'Taarini', 'taarini@gmail.com', '$2y$10$r0KrkinxenoAuEPDl43kTu/fbT8TPuDW2d4bu0Nn0PJi7zkQEj/Um', 'Student', 99999949.99, '2025-12-31 16:55:25', '0182135493'),
(13, 'Parthiban', 'parthiban@gmail.com', '$2y$10$NRubAiE8SPz2Ru.gBnFAVu2edJW4e4Z0bxpCEG/nUu5nwkF0V2GVO', 'Driver', 210.00, '2025-12-31 16:57:19', ''),
(14, 'Harold Das', 'harolddas@gmail.com', '$2y$10$XYV/mVmhg.psfOp3uEn3JeiiJC.6./CCv3.zwsGnEe3Lso3k7c9iu', 'Admin', 0.00, '2026-01-03 17:17:00', '0182135493'),
(16, 'Pehroshini', 'pehro@gmail.com', '$2y$10$rtUnw.oPY8f0.wxN/AzmDOKsLiKBM6QzG8yfcMHCeXOHdPpSyKeC.', 'Student', 25.00, '2026-01-09 15:36:02', NULL),
(17, 'Shahrrimelan', 'shahrri@gmail.com', '$2y$10$YjYoJfElMCua2Qp9xCPRHugQUKaDuDt4h.cZQwl72mJZMfZW/2YJO', 'Student', -21.00, '2026-01-09 15:38:16', NULL),
(18, 'Denish', 'denishdjr02@gmail.com', '$2y$10$8dhygbz8LdiqEbISx0.b6OPQsz9Mqc80rUiUovlTPv6Tw3bIqHBlC', 'Driver', 0.00, '2026-01-11 09:19:36', NULL),
(19, 'Ganesan', 'ganesan@gmail.com', '$2y$10$Iizd3FiGpNYezwu/kUVlYuuztym8gqETVUi7z7xXoBkmRBj6xo1dy', 'Driver', 20.00, '2026-01-11 15:26:00', NULL),
(20, 'Shiva', 'shiva@gmail.com', '$2y$10$FVxf980BAjBn5HVKqmA6peehtZdUosP9mqp11ZDYOIgNFPRR.vo/W', 'Driver', 20.00, '2026-01-11 15:29:34', '0182135493'),
(21, 'shasveent', 'shas@gmail.com', '$2y$10$TBmwyTBkyZxVUKt1UeqCQOwS81f63jPrv0lgWOP668oHAK8epn3py', 'Student', 50.00, '2026-01-11 15:45:40', '0182135493');

-- --------------------------------------------------------

--
-- Table structure for table `vehicles`
--

DROP TABLE IF EXISTS `vehicles`;
CREATE TABLE IF NOT EXISTS `vehicles` (
  `vehicle_id` int NOT NULL AUTO_INCREMENT,
  `driver_partner_id` int NOT NULL,
  `plate_number` varchar(15) NOT NULL,
  `model` varchar(50) DEFAULT NULL,
  `capacity` int DEFAULT '4',
  PRIMARY KEY (`vehicle_id`),
  KEY `driver_partner_id` (`driver_partner_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
