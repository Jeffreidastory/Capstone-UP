-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 17, 2025 at 08:25 PM
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
-- Database: `kfood_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `cart_items`
--

CREATE TABLE `cart_items` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `added_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cart_items`
--

INSERT INTO `cart_items` (`id`, `user_id`, `product_id`, `quantity`, `added_at`) VALUES
(37, 32, 15, 1, '2025-10-16 19:25:55'),
(38, 32, 14, 1, '2025-10-16 19:25:55'),
(39, 32, 13, 1, '2025-10-16 19:25:55');

-- --------------------------------------------------------

--
-- Table structure for table `delivery_addresses`
--

CREATE TABLE `delivery_addresses` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `street_address` varchar(255) NOT NULL,
  `barangay` varchar(100) NOT NULL,
  `city` varchar(100) NOT NULL,
  `province` varchar(100) NOT NULL,
  `zip_code` varchar(10) NOT NULL,
  `is_default` tinyint(1) DEFAULT 0,
  `label` varchar(50) DEFAULT 'Home',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `delivery_addresses`
--

INSERT INTO `delivery_addresses` (`id`, `user_id`, `street_address`, `barangay`, `city`, `province`, `zip_code`, `is_default`, `label`, `created_at`) VALUES
(3, 28, 'Unit 12, 12th Floor, AD Building', ' San Antonio Village', 'Makati City', 'Metro Manila', '1250', 0, 'Office', '2025-10-06 14:37:40'),
(4, 28, 'Sti Building', 'Fairmont', 'Quezon City', 'Metro Manila', '4245', 0, 'School', '2025-10-06 14:38:35'),
(5, 30, 'Sti Building', 'Fairmont', 'Quezon City', 'Metro Manila', '4245', 0, 'School', '2025-10-06 16:38:55'),
(6, 31, '241 Kaypian Hills', 'Kaypian', 'San Jose Del Monte', 'Bulacan', '3023', 0, 'Home', '2025-10-06 19:04:21'),
(7, 32, 'Sti Building', 'Fairmont', 'Quezon City', 'Metro Manila', '4245', 0, 'School', '2025-10-08 17:01:26'),
(8, 32, 'Unit 12, 12th Floor, AD Building', ' San Antonio Village', 'Makati City', 'Metro Manila', '1250', 0, 'Office', '2025-10-08 17:28:47'),
(9, 33, 'Unit 12, 12th Floor, AD Building', 'Fairmont', 'Makati City', 'Metro Manila', '3024', 0, 'Home', '2025-10-08 19:10:43'),
(10, 33, 'Astoria Building', 'Mabini', 'Ermita', 'Metro Manila', '1250', 0, 'Office', '2025-10-08 19:18:47');

-- --------------------------------------------------------

--
-- Table structure for table `landing_settings`
--

CREATE TABLE `landing_settings` (
  `id` int(11) NOT NULL,
  `restaurant_name` varchar(255) DEFAULT NULL,
  `tagline` text DEFAULT NULL,
  `logo_path` varchar(255) DEFAULT NULL,
  `favicon_path` varchar(255) DEFAULT NULL,
  `hero_title` varchar(255) DEFAULT NULL,
  `hero_subtitle` text DEFAULT NULL,
  `hero_image_path` varchar(255) DEFAULT NULL,
  `about_story` text DEFAULT NULL,
  `features` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`features`)),
  `address` text DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `operating_hours` text DEFAULT NULL,
  `social_facebook` varchar(255) DEFAULT NULL,
  `social_instagram` varchar(255) DEFAULT NULL,
  `social_tiktok` varchar(255) DEFAULT NULL,
  `primary_color` varchar(10) DEFAULT NULL,
  `secondary_color` varchar(10) DEFAULT NULL,
  `font_style` varchar(50) DEFAULT NULL,
  `layout_style` varchar(50) DEFAULT NULL,
  `newsletter_enabled` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `address` varchar(255) NOT NULL,
  `method` varchar(50) NOT NULL,
  `total_products` int(11) NOT NULL,
  `item_name` text DEFAULT NULL,
  `total_price` decimal(10,2) NOT NULL,
  `status` varchar(20) DEFAULT 'pending',
  `order_time` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `delivery_instructions` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `name`, `user_id`, `address`, `method`, `total_products`, `item_name`, `total_price`, `status`, `order_time`, `updated_at`, `delivery_instructions`) VALUES
(1, 'denisead Wonwoo', 8, 'Kaypian', 'cod', 1, 'Lasagna (1)', 900.00, 'completed', '2025-09-28 19:51:48', '2025-09-28 19:55:08', NULL),
(2, 'denisead Wonwoo', 8, 'Kaypian', 'cod', 1, 'Pastilan (1)', 126.00, 'completed', '2025-09-28 19:51:58', '2025-09-28 19:55:18', NULL),
(3, 'denisead Wonwoo', 8, 'Kaypian', 'cod', 1, 'Crinkles (1)', 100.00, 'completed', '2025-09-28 19:52:06', '2025-09-28 19:55:27', NULL),
(4, 'denisead Wonwoo', 8, 'Kaypian', 'cod', 1, 'fishda (1)', 200.00, 'completed', '2025-09-28 19:52:16', '2025-09-28 19:55:35', NULL),
(5, 'denisead Wonwoo', 8, 'Kaypian', 'gcash', 2, 'itlog  (2)', 40.00, 'pending_verification', '2025-09-28 19:52:28', '2025-10-16 23:53:48', NULL),
(6, 'Cora Feliciano', 16, 'HONGKONG', 'cod', 3, 'Pastilan (2)\nCrinkles (1)', 352.00, 'completed', '2025-09-29 14:38:25', '2025-09-29 20:58:09', NULL),
(7, 'Cora Feliciano', 16, 'HONGKONG', 'cod', 2, 'Pastilan (1)\nfishda (1)', 326.00, 'out for delivery', '2025-09-29 14:38:55', '2025-10-01 12:18:03', NULL),
(8, 'Cora Feliciano', 16, 'HONGKONG', 'cod', 2, 'Lasagna (1)\nitlog  (1)', 920.00, 'completed', '2025-09-29 14:39:06', '2025-09-29 18:37:14', NULL),
(9, 'Cora Feliciano', 16, 'HONGKONG', 'cod', 1, 'Lasagna (1)', 900.00, 'completed', '2025-09-29 14:39:15', '2025-09-29 14:42:55', NULL),
(10, 'denisead Wonwoo', 8, 'Kaypian', 'cod', 1, 'Crinkles (1)', 100.00, 'completed', '2025-09-29 17:34:57', '2025-09-29 17:36:29', NULL),
(11, 'Cora Feliciano', 16, 'HONGKONG', 'cod', 1, 'Crinkles (1)', 100.00, 'completed', '2025-09-29 17:42:35', '2025-09-29 17:43:19', NULL),
(12, 'raven pablo', 15, 'kaypian', 'cod', 2, 'Crinkles (2)', 200.00, 'completed', '2025-09-29 17:50:49', '2025-09-29 17:51:42', NULL),
(13, 'Cora Feliciano', 16, 'HONGKONG', 'gcash', 1, 'Crinkles (1)', 100.00, 'pending_verification', '2025-09-29 18:03:37', '2025-10-16 23:53:48', NULL),
(14, 'denisead Wonwoo', 8, 'Kaypian', 'gcash', 1, 'Crinkles (1)', 100.00, 'pending_verification', '2025-09-29 18:15:53', '2025-10-16 23:53:48', NULL),
(15, 'denisead Wonwoo', 8, 'Kaypian', 'cod', 1, 'Crinkles (1)', 100.00, 'completed', '2025-09-29 18:22:05', '2025-09-29 18:22:49', NULL),
(16, 'Cora Feliciano', 16, 'HONGKONG', 'cod', 1, 'Crinkles (1)', 100.00, 'completed', '2025-09-29 18:37:45', '2025-09-29 20:58:00', NULL),
(17, 'Cora Feliciano', 16, 'HONGKONG', 'cod', 1, 'Crinkles (1)', 100.00, 'completed', '2025-09-29 18:56:47', '2025-09-29 20:57:52', NULL),
(18, 'Cora Feliciano', 16, 'HONGKONG', 'gcash', 1, 'Crinkles (1)', 100.00, 'pending_verification', '2025-09-29 18:59:58', '2025-10-16 23:53:48', NULL),
(19, 'Cora Feliciano', 16, 'HONGKONG', 'cod', 25, 'itlog  (25)', 500.00, 'completed', '2025-09-29 20:14:55', '2025-09-29 20:15:33', NULL),
(20, 'Cora Feliciano', 16, 'HONGKONG', 'cod', 1, 'Crinkles (1)', 100.00, 'completed', '2025-09-29 20:53:40', '2025-09-29 20:54:18', NULL),
(21, 'denisead Wonwoo', 8, 'Kaypian', 'cod', 1, 'Pastilan (1)', 126.00, 'completed', '2025-10-01 12:16:27', '2025-10-01 12:18:48', NULL),
(22, 'jay galang', 17, 'Bagong Silang, QC', 'cod', 7, 'Pastilan (1)\nManga (2)\nCrinkles (3)\nitlog  (1)', 486.00, 'completed', '2025-10-01 14:41:26', '2025-10-01 15:37:04', NULL),
(23, 'jay galang', 17, 'Bagong Silang, QC', 'gcash', 1, 'Lasagna (1)', 900.00, 'pending_verification', '2025-10-01 14:47:36', '2025-10-16 23:53:48', NULL),
(24, 'jay galang', 17, 'Bagong Silang, QC', 'cod', 1, 'Atchara (1)', 30.00, 'completed', '2025-10-01 15:42:27', '2025-10-01 15:43:40', NULL),
(25, 'denisead Wonwoo', 8, 'Kaypian', 'cod', 3, 'Manga (1)\nPastilan (1)\nCrinkles (1)', 246.00, 'completed', '2025-10-01 15:47:37', '2025-10-01 15:50:18', NULL),
(26, 'denisead Wonwoo', 8, 'Kaypian', 'cod', 1, 'Atchara (1)', 30.00, 'out for delivery', '2025-10-01 17:26:52', '2025-10-01 17:27:48', NULL),
(27, 'raven pablo', 15, 'kaypian', 'cod', 8, 'Lasagna (1)\nPastilan (1)\nCrinkles (1)\nfishda (1)\nitlog  (1)\nAtchara (1)\nManga (1)\nSinigang (1)', 1496.00, 'out for delivery', '2025-10-01 17:33:22', '2025-10-01 17:37:50', NULL),
(28, 'Aira Feliciano', 18, 'Kaypian Hills, San Jose Bulacan', 'cod', 2, 'Crinkles (2)', 200.00, 'out for delivery', '2025-10-03 11:20:05', '2025-10-05 23:16:27', NULL),
(29, 'Cedric Feliciano', 21, 'Kaypian Huills', 'cod', 1, 'Crinkles (1)', 100.00, 'out for delivery', '2025-10-03 15:00:37', '2025-10-05 23:16:24', NULL),
(30, 'Mudeok Yeon', 26, 'Bulacan , Palmera', 'cod', 1, 'Crinkles (1)', 100.00, 'out for delivery', '2025-10-04 16:45:02', '2025-10-05 23:16:30', NULL),
(31, 'Mudeok Yeon', 26, 'Bulacan , Palmera', 'cod', 2, 'Crinkles (1)\nPastilan (1)', 226.00, 'out for delivery', '2025-10-04 16:57:37', '2025-10-05 23:16:30', NULL),
(32, 'Jay Mon', 28, 'Bulcan, Sanjose', 'cod', 2, 'Lasagna (1)\nPastilan (1)', 1026.00, 'completed', '2025-10-05 22:07:52', '2025-10-05 23:22:06', NULL),
(33, 'Jay Mon', 28, 'Bulcan, Sanjose', 'cod', 1, 'fishda (1)', 200.00, 'completed', '2025-10-05 22:11:05', '2025-10-05 23:21:57', NULL),
(34, 'Jay Mon', 28, 'Bulcan, Sanjose', 'cod', 1, 'Crinkles (1)', 100.00, 'completed', '2025-10-05 22:24:04', '2025-10-05 23:22:26', NULL),
(35, 'Jay Mon', 28, 'Bulcan, Sanjose', 'gcash', 1, 'Crinkles (1)', 100.00, 'pending_verification', '2025-10-05 22:24:43', '2025-10-16 23:53:48', NULL),
(36, 'Jay Mon', 28, 'Bulcan, Sanjose', 'cod', 1, 'Crinkles (1)', 100.00, 'completed', '2025-10-05 22:28:00', '2025-10-05 23:21:50', NULL),
(37, 'Jay Mon', 28, 'Bulcan, Sanjose', 'cod', 1, 'fishda (1)', 200.00, 'completed', '2025-10-05 22:32:09', '2025-10-05 23:21:32', NULL),
(38, 'Jay Mon', 28, 'Bulcan, Sanjose', 'gcash', 1, 'Crinkles (1)', 100.00, 'pending_verification', '2025-10-05 22:32:47', '2025-10-16 23:53:48', NULL),
(39, 'Jay Mon', 28, 'Bulcan, Sanjose', 'cod', 1, 'Crinkles (1)', 100.00, 'completed', '2025-10-05 22:35:52', '2025-10-05 23:21:14', NULL),
(40, 'Jay Mon', 28, 'Bulcan, Sanjose', 'gcash', 1, 'Pastilan (1)', 126.00, 'pending_verification', '2025-10-05 22:36:34', '2025-10-16 23:53:48', NULL),
(41, 'Jay Mon', 28, 'Bulcan, Sanjose', 'gcash', 1, 'Lasagna (1)', 900.00, 'pending_verification', '2025-10-05 22:39:11', '2025-10-16 23:53:48', NULL),
(42, 'Jay Mon', 28, 'Bulcan, Sanjose', 'gcash', 1, 'Crinkles (1)', 100.00, 'pending_verification', '2025-10-05 22:41:41', '2025-10-16 23:53:48', NULL),
(43, 'Jay Mon', 28, 'Bulcan, Sanjose', 'gcash', 1, 'Atchara (1)', 30.00, 'pending_verification', '2025-10-05 22:45:03', '2025-10-16 23:53:48', NULL),
(44, 'Jay Mon', 28, 'Bulcan, Sanjose', 'cod', 1, 'Crinkles (1)', 100.00, 'completed', '2025-10-05 22:45:19', '2025-10-05 23:19:47', NULL),
(45, 'Jay Mon', 28, 'Bulcan, Sanjose', 'gcash', 2, 'fishda (2)', 400.00, 'pending_verification', '2025-10-05 22:51:24', '2025-10-16 23:53:48', NULL),
(46, 'Jay Mon', 28, 'Bulcan, Sanjose', 'cod', 1, 'Pastilan (1)', 126.00, 'completed', '2025-10-05 22:53:58', '2025-10-05 23:20:39', NULL),
(47, 'Jay Mon', 28, 'Bulcan, Sanjose', 'cod', 1, 'Manga (1)', 20.00, 'completed', '2025-10-05 22:57:29', '2025-10-05 23:20:17', NULL),
(48, 'Jay Mon', 28, 'Bulcan, Sanjose', 'cod', 1, 'Lasagna (1)', 900.00, 'completed', '2025-10-05 23:01:11', '2025-10-05 23:20:24', NULL),
(49, 'Jay Mon', 28, 'Bulcan, Sanjose', 'cod', 1, 'Pastilan (1)', 126.00, 'completed', '2025-10-05 23:02:46', '2025-10-05 23:20:10', NULL),
(50, 'Jay Mon', 28, 'Bulcan, Sanjose', 'cod', 1, 'Crinkles (1)', 100.00, 'completed', '2025-10-05 23:09:41', '2025-10-05 23:19:57', NULL),
(51, 'Jay Mon', 28, 'Bulcan, Sanjose', 'cod', 1, 'Pastilan (1)', 126.00, 'completed', '2025-10-05 23:10:04', '2025-10-05 23:19:37', NULL),
(52, 'Jay Mon', 28, 'Bulcan, Sanjose', 'cod', 1, 'fishda (1)', 200.00, 'completed', '2025-10-05 23:12:01', '2025-10-05 23:19:28', NULL),
(53, 'Jay Mon', 28, 'Bulcan, Sanjose', 'cod', 1, 'Pastilan (1)', 126.00, 'completed', '2025-10-05 23:12:11', '2025-10-05 23:19:17', NULL),
(54, 'Jay Mon', 28, 'Bulcan, Sanjose', 'cod', 1, 'Crinkles (1)', 100.00, 'completed', '2025-10-05 23:14:35', '2025-10-05 23:19:08', NULL),
(55, 'Jay Mon', 28, 'Bulcan, Sanjose', 'gcash', 1, 'Pastilan (1)', 126.00, 'pending_verification', '2025-10-05 23:14:50', '2025-10-16 23:53:48', NULL),
(56, 'Jay Mon', 28, '241 Kaypian Hills, Kaypian, San Jose Del Monte, Bulacan, 3023, Philippines', 'cod', 1, 'Pastilan (1)', 126.00, 'preparing', '2025-10-06 14:47:50', '2025-10-16 22:51:19', NULL),
(57, 'Jay Mon', 28, '241 Kaypian Hills, Kaypian, San Jose Del Monte, Bulacan, 3023, Philippines', 'cod', 1, 'Crinkles (1)', 100.00, 'preparing', '2025-10-06 14:55:11', '2025-10-06 15:53:52', NULL),
(58, 'Jay Mon', 28, 'Sti Building, Fairmont, Quezon City, Metro Manila 4245', 'cod', 1, 'Atchara (1)', 30.00, 'preparing', '2025-10-06 15:02:12', '2025-10-06 15:53:46', ''),
(59, 'Jay Mon', 28, 'Sti Building, Fairmont, Quezon City, Metro Manila 4245', 'cod', 1, 'itlog  (1)', 20.00, 'preparing', '2025-10-06 15:03:26', '2025-10-06 15:53:57', 'Paiwan po sa guard'),
(60, 'Seolran Jin', 31, '241 Kaypian Hills, Kaypian, San Jose Del Monte, Bulacan 3023', 'cod', 1, 'Pastilan (1)', 126.00, 'preparing', '2025-10-06 19:04:54', '2025-10-16 22:51:25', ''),
(61, 'Aira Araneta', 32, 'Sti Building, Fairmont, Quezon City, Metro Manila 4245', 'cod', 1, 'fishda (1)', 200.00, 'preparing', '2025-10-08 19:00:24', '2025-10-16 18:45:11', ''),
(62, 'Aira Araneta', 32, 'Sti Building, Fairmont, Quezon City, Metro Manila 4245', 'gcash', 1, 'Lasagna (1)', 900.00, 'pending_verification', '2025-10-08 19:32:25', '2025-10-16 23:53:48', ''),
(63, 'Aira Araneta', 32, 'Sti Building, Fairmont, Quezon City, Metro Manila 4245', 'gcash', 1, 'Pastilan (1)', 126.00, 'pending_verification', '2025-10-08 19:32:52', '2025-10-16 23:53:48', ''),
(64, 'Aira Araneta', 32, 'Sti Building, Fairmont, Quezon City, Metro Manila 4245', 'cod', 2, 'fishda (1)\nCrinkles (1)', 300.00, 'out for delivery', '2025-10-08 20:36:00', '2025-10-16 22:51:45', ''),
(65, 'Aira Araneta', 32, 'Sti Building, Fairmont, Quezon City, Metro Manila 4245', 'cod', 2, 'Manga (2)', 40.00, 'pending', '2025-10-16 16:25:04', '2025-10-16 16:25:04', ''),
(66, 'Aira Araneta', 32, 'Sti Building, Fairmont, Quezon City, Metro Manila 4245', 'cod', 1, 'fishda (1)', 200.00, 'preparing', '2025-10-16 16:41:50', '2025-10-16 22:40:22', ''),
(69, 'Aira Araneta', 32, 'Sti Building, Fairmont, Quezon City, Metro Manila 4245', 'gcash', 1, 'Pastilan (1)', 126.00, 'pending_verification', '2025-10-16 17:06:26', '2025-10-16 23:53:48', ''),
(74, 'Aira Araneta', 32, 'Sti Building, Fairmont, Quezon City, Metro Manila 4245', 'gcash', 1, 'Crinkles (1)', 100.00, 'pending_verification', '2025-10-16 17:54:01', '2025-10-16 23:53:48', ''),
(75, 'Aira Araneta', 32, 'Sti Building, Fairmont, Quezon City, Metro Manila 4245', 'gcash', 1, 'Crinkles (1)', 100.00, 'pending_verification', '2025-10-16 18:25:03', '2025-10-16 23:53:48', ''),
(76, 'Aira Araneta', 32, 'Unit 12, 12th Floor, AD Building,  San Antonio Village, Makati City, Metro Manila 1250', 'gcash', 1, 'Pastilan (1)', 126.00, 'pending_verification', '2025-10-16 18:27:00', '2025-10-16 23:53:48', ''),
(77, 'Aira Araneta', 32, 'Sti Building, Fairmont, Quezon City, Metro Manila 4245', 'gcash', 1, 'Lasagna (1)', 900.00, 'pending_verification', '2025-10-16 18:47:54', '2025-10-16 23:53:48', ''),
(78, 'Aira Araneta', 32, 'Sti Building, Fairmont, Quezon City, Metro Manila 4245', 'gcash', 1, 'Atchara (1)', 30.00, 'pending', '2025-10-16 18:56:09', '2025-10-16 23:53:48', ''),
(79, 'Aira Araneta', 32, 'Sti Building, Fairmont, Quezon City, Metro Manila 4245', 'gcash', 3, 'Crinkles (1)\nPastilan (2)', 352.00, 'pending', '2025-10-16 20:05:10', '2025-10-16 23:53:48', ''),
(80, 'Aira Araneta', 32, 'Sti Building, Fairmont, Quezon City, Metro Manila 4245', 'gcash', 2, 'fishda (1)\nLasagna (1)', 1100.00, 'pending', '2025-10-16 20:35:29', '2025-10-16 23:53:48', ''),
(81, 'Aira Araneta', 32, 'Unit 12, 12th Floor, AD Building,  San Antonio Village, Makati City, Metro Manila 1250', 'gcash', 1, 'Pastilan (1)', 126.00, 'pending', '2025-10-16 20:46:13', '2025-10-16 23:53:48', ''),
(82, 'Aira Araneta', 32, 'Unit 12, 12th Floor, AD Building,  San Antonio Village, Makati City, Metro Manila 1250', 'gcash', 1, 'Pastilan (1)', 126.00, 'pending', '2025-10-16 20:50:11', '2025-10-16 23:53:48', ''),
(83, 'Aira Araneta', 32, 'Unit 12, 12th Floor, AD Building,  San Antonio Village, Makati City, Metro Manila 1250', 'gcash', 1, 'Lasagna (1)', 900.00, 'pending', '2025-10-16 20:51:41', '2025-10-16 23:53:48', ''),
(84, 'Aira Araneta', 32, 'Sti Building, Fairmont, Quezon City, Metro Manila 4245', 'gcash', 1, 'itlog  (1)', 20.00, 'pending', '2025-10-16 21:15:39', '2025-10-16 23:53:48', ''),
(85, 'Aira Araneta', 32, 'Unit 12, 12th Floor, AD Building,  San Antonio Village, Makati City, Metro Manila 1250', 'gcash', 1, 'Pastilan (1)', 126.00, 'pending', '2025-10-16 21:32:11', '2025-10-16 23:53:48', ''),
(86, 'Aira Araneta', 32, 'Sti Building, Fairmont, Quezon City, Metro Manila 4245', 'gcash', 1, 'Pastilan (1)', 126.00, 'pending', '2025-10-16 22:23:11', '2025-10-16 23:53:48', ''),
(87, 'Aira Araneta', 32, 'Unit 12, 12th Floor, AD Building,  San Antonio Village, Makati City, Metro Manila 1250', 'gcash', 1, 'Crinkles (1)', 100.00, '', '2025-10-16 22:31:48', '2025-10-16 23:53:48', ''),
(88, 'Aira Araneta', 32, 'Sti Building, Fairmont, Quezon City, Metro Manila 4245', 'gcash', 1, 'Pastilan (1)', 126.00, 'pending', '2025-10-16 22:35:02', '2025-10-16 23:53:48', ''),
(89, 'Aira Araneta', 32, 'Sti Building, Fairmont, Quezon City, Metro Manila 4245', 'gcash', 1, 'Crinkles (1)', 100.00, 'pending', '2025-10-16 22:36:51', '2025-10-16 23:53:48', ''),
(90, 'Aira Araneta', 32, 'Sti Building, Fairmont, Quezon City, Metro Manila 4245', 'gcash', 1, 'Pastilan (1)', 126.00, '', '2025-10-16 22:40:50', '2025-10-16 23:53:48', ''),
(91, 'Aira Araneta', 32, 'Sti Building, Fairmont, Quezon City, Metro Manila 4245', 'gcash', 1, 'Crinkles (1)', 100.00, '', '2025-10-16 22:45:13', '2025-10-16 23:53:48', ''),
(92, 'Aira Araneta', 32, 'Unit 12, 12th Floor, AD Building,  San Antonio Village, Makati City, Metro Manila 1250', 'gcash', 1, 'itlog  (1)', 20.00, 'cancelled', '2025-10-16 22:53:00', '2025-10-17 00:03:00', ''),
(93, 'Aira Araneta', 32, 'Sti Building, Fairmont, Quezon City, Metro Manila 4245', 'gcash', 1, 'Crinkles (1)', 100.00, '', '2025-10-16 22:53:42', '2025-10-16 23:53:48', ''),
(94, 'Aira Araneta', 32, 'Sti Building, Fairmont, Quezon City, Metro Manila 4245', 'gcash', 1, 'Pastilan (1)', 126.00, '', '2025-10-16 22:58:13', '2025-10-16 23:53:48', ''),
(95, 'Aira Araneta', 32, 'Unit 12, 12th Floor, AD Building,  San Antonio Village, Makati City, Metro Manila 1250', 'gcash', 1, 'itlog  (1)', 20.00, '', '2025-10-16 22:59:43', '2025-10-16 23:53:48', ''),
(96, 'Aira Araneta', 32, 'Sti Building, Fairmont, Quezon City, Metro Manila 4245', 'gcash', 1, 'Manga (1)', 20.00, '', '2025-10-16 23:16:14', '2025-10-16 23:53:48', ''),
(97, 'Aira Araneta', 32, 'Sti Building, Fairmont, Quezon City, Metro Manila 4245', 'gcash', 1, 'itlog  (1)', 20.00, '', '2025-10-16 23:25:50', '2025-10-16 23:53:48', ''),
(98, 'Aira Araneta', 32, 'Sti Building, Fairmont, Quezon City, Metro Manila 4245', 'gcash', 1, 'Sinigang (1)', 100.00, '', '2025-10-16 23:31:42', '2025-10-16 23:53:48', ''),
(99, 'Aira Araneta', 32, 'Sti Building, Fairmont, Quezon City, Metro Manila 4245', 'gcash', 1, 'Atchara (1)', 30.00, 'cancelled', '2025-10-16 23:36:11', '2025-10-16 23:56:58', ''),
(100, 'Aira Araneta', 32, 'Unit 12, 12th Floor, AD Building,  San Antonio Village, Makati City, Metro Manila 1250', 'gcash', 1, 'Pastilan (1)', 126.00, 'cancelled', '2025-10-16 23:43:43', '2025-10-16 23:56:58', ''),
(101, 'Aira Araneta', 32, 'Unit 12, 12th Floor, AD Building,  San Antonio Village, Makati City, Metro Manila 1250', 'gcash', 1, 'Manga (1)', 20.00, 'cancelled', '2025-10-16 23:46:22', '2025-10-17 00:03:04', ''),
(102, 'Aira Araneta', 32, 'Sti Building, Fairmont, Quezon City, Metro Manila 4245', 'gcash', 1, 'Crinkles (1)', 100.00, 'cancelled', '2025-10-16 23:48:04', '2025-10-17 00:02:56', ''),
(103, 'Aira Araneta', 32, 'Unit 12, 12th Floor, AD Building,  San Antonio Village, Makati City, Metro Manila 1250', 'gcash', 1, 'Manga (1)', 20.00, 'pending_verification', '2025-10-17 00:03:29', '2025-10-17 00:03:29', ''),
(104, 'Aira Araneta', 32, 'Sti Building, Fairmont, Quezon City, Metro Manila 4245', 'gcash', 1, 'itlog  (1)', 20.00, 'pending_verification', '2025-10-17 00:07:17', '2025-10-17 00:07:17', ''),
(105, 'Aira Araneta', 32, 'Sti Building, Fairmont, Quezon City, Metro Manila 4245', 'gcash', 1, 'itlog  (1)', 20.00, 'awaiting_payment_ver', '2025-10-17 00:09:23', '2025-10-17 00:09:23', ''),
(106, 'Aira Araneta', 32, 'Sti Building, Fairmont, Quezon City, Metro Manila 4245', 'gcash', 1, 'itlog  (1)', 20.00, 'awaiting_payment_ver', '2025-10-17 00:18:48', '2025-10-17 00:18:48', ''),
(107, 'Aira Araneta', 32, 'Sti Building, Fairmont, Quezon City, Metro Manila 4245', 'gcash', 1, 'Crinkles (1)', 100.00, 'awaiting_payment_ver', '2025-10-17 00:20:17', '2025-10-17 00:20:17', ''),
(108, 'Aira Araneta', 32, 'Unit 12, 12th Floor, AD Building,  San Antonio Village, Makati City, Metro Manila 1250', 'gcash', 1, 'fishda (1)', 200.00, 'awaiting_payment_ver', '2025-10-17 00:27:33', '2025-10-17 00:27:33', ''),
(110, 'Aira Araneta', 32, 'Sti Building, Fairmont, Quezon City, Metro Manila 4245', 'gcash', 2, 'Atchara (2)', 60.00, 'preparing', '2025-10-17 00:34:42', '2025-10-17 00:34:51', ''),
(111, 'Aira Araneta', 32, 'Sti Building, Fairmont, Quezon City, Metro Manila 4245', 'gcash', 1, 'Crinkles (1)', 100.00, 'cancelled', '2025-10-17 00:36:10', '2025-10-17 00:41:00', ''),
(112, 'Aira Araneta', 32, 'Unit 12, 12th Floor, AD Building,  San Antonio Village, Makati City, Metro Manila 1250', 'gcash', 1, 'Pastilan (1)', 126.00, 'pending', '2025-10-17 00:53:59', '2025-10-17 00:53:59', ''),
(113, 'Aira Araneta', 32, 'Unit 12, 12th Floor, AD Building,  San Antonio Village, Makati City, Metro Manila 1250', 'gcash', 1, 'fishda (1)', 200.00, 'pending', '2025-10-17 01:04:39', '2025-10-17 01:04:39', ''),
(114, 'Aira Araneta', 32, 'Sti Building, Fairmont, Quezon City, Metro Manila 4245', 'gcash', 1, 'Pastilan (1)', 126.00, 'preparing', '2025-10-17 15:23:30', '2025-10-17 15:24:39', '');

-- --------------------------------------------------------

--
-- Table structure for table `order_status_logs`
--

CREATE TABLE `order_status_logs` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `status` varchar(20) NOT NULL,
  `crew_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `otp_codes`
--

CREATE TABLE `otp_codes` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `otp_code` varchar(6) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `expires_at` timestamp NULL DEFAULT NULL,
  `verified` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `otp_codes`
--

INSERT INTO `otp_codes` (`id`, `email`, `otp_code`, `created_at`, `expires_at`, `verified`) VALUES
(1, 'naksujin024@gmail.com', '980397', '2025-10-04 17:19:26', '2025-10-04 17:29:26', 0),
(2, 'naksujin024@gmail.com', '790952', '2025-10-04 17:21:37', '2025-10-04 17:31:37', 0),
(3, 'naksujin024@gmail.com', '103722', '2025-10-04 17:24:48', '2025-10-04 17:34:48', 0),
(4, 'naksujin024@gmail.com', '170006', '2025-10-04 17:28:54', '2025-10-04 17:38:54', 0),
(5, 'naksujin024@gmail.com', '152139', '2025-10-04 17:28:56', '2025-10-04 17:38:56', 0),
(6, 'naksujin024@gmail.com', '156103', '2025-10-04 17:29:03', '2025-10-04 17:39:03', 0),
(7, 'naksujin024@gmail.com', '103308', '2025-10-04 17:29:05', '2025-10-04 17:39:05', 0),
(8, 'naksujin024@gmail.com', '613488', '2025-10-04 17:29:17', '2025-10-04 17:39:17', 0),
(9, 'naksujin024@gmail.com', '277980', '2025-10-04 17:29:30', '2025-10-04 17:39:30', 0),
(10, 'naksujin024@gmail.com', '442529', '2025-10-04 17:31:16', '2025-10-04 17:41:16', 0),
(11, 'naksujin024@gmail.com', '328482', '2025-10-04 17:31:41', '2025-10-04 17:41:41', 0),
(12, 'naksujin024@gmail.com', '155205', '2025-10-04 17:31:43', '2025-10-04 17:41:43', 0),
(13, 'naksujin024@gmail.com', '591829', '2025-10-04 17:31:45', '2025-10-04 17:41:45', 0),
(14, 'naksujin024@gmail.com', '429072', '2025-10-04 17:31:47', '2025-10-04 17:41:47', 0),
(15, 'naksujin024@gmail.com', '157532', '2025-10-04 17:31:49', '2025-10-04 17:41:49', 0),
(16, 'naksujin024@gmail.com', '904900', '2025-10-04 17:31:51', '2025-10-04 17:41:51', 0),
(17, 'naksujin024@gmail.com', '679605', '2025-10-04 17:31:52', '2025-10-04 17:41:52', 0),
(18, 'naksujin024@gmail.com', '757414', '2025-10-04 17:31:54', '2025-10-04 17:41:54', 0),
(19, 'naksujin024@gmail.com', '443483', '2025-10-04 17:54:28', '2025-10-04 18:04:28', 0),
(20, 'naksujin024@gmail.com', '303053', '2025-10-04 17:54:51', '2025-10-04 18:04:51', 1),
(21, 'soyeaji24@gmail.com', '528575', '2025-10-04 18:00:36', '2025-10-04 18:10:36', 1),
(22, 'Koricsvagabond@gmail.com', '471632', '2025-10-04 18:08:25', '2025-10-04 18:18:25', 0),
(23, 'Koricsvagabond@gmail.com', '978754', '2025-10-04 18:08:28', '2025-10-04 18:18:28', 0),
(24, 'Koricsvagabond@gmail.com', '840490', '2025-10-04 18:11:34', '2025-10-04 18:21:34', 0),
(25, 'Koricsvagabond@gmail.com', '224680', '2025-10-04 18:14:07', '2025-10-04 18:24:07', 1),
(26, 'jffrsnfeliciano0000@gmail.com', '647276', '2025-10-06 16:36:50', '2025-10-06 16:46:50', 1),
(27, 'corafeliciano09@gmail.com', '487571', '2025-10-06 18:03:08', '2025-10-06 18:13:08', 1),
(28, 'aywaaraneta@gmail.com', '069854', '2025-10-08 16:58:10', '2025-10-08 17:08:10', 1),
(29, 'Vicenzocassano@gmail.com', '177859', '2025-10-08 19:01:17', '2025-10-08 19:11:17', 0),
(30, 'Vicenzocassano24@gmail.com', '576371', '2025-10-08 19:02:37', '2025-10-08 19:12:37', 0),
(31, 'Vicenzocasaano24@gmail.com', '348568', '2025-10-08 19:03:07', '2025-10-08 19:13:07', 0),
(32, 'vincenzocasaano24@gmail.com', '421454', '2025-10-08 19:03:42', '2025-10-08 19:13:42', 1),
(33, 'graceanneee25@gmail.com', '041702', '2025-10-17 14:45:18', '2025-10-17 14:55:18', 1),
(34, 'daxal44183@fixwap.com', '511505', '2025-10-17 17:43:42', '2025-10-17 17:53:42', 1);

-- --------------------------------------------------------

--
-- Table structure for table `payment_records`
--

CREATE TABLE `payment_records` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `e-wallet` varchar(50) NOT NULL,
  `reference_number` varchar(100) DEFAULT NULL,
  `payment_proof` varchar(255) DEFAULT NULL,
  `payment_status` enum('pending_verification','verified','invalid') DEFAULT 'pending_verification',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payment_records`
--

INSERT INTO `payment_records` (`id`, `order_id`, `e-wallet`, `reference_number`, `payment_proof`, `payment_status`, `created_at`, `updated_at`) VALUES
(1, 74, '', '4234 4235 656', 'payment_74_1760637241.jpg', 'pending_verification', '2025-10-16 17:54:01', '2025-10-16 17:54:01'),
(2, 75, '', '423 545 543', 'payment_75_1760639103.jpg', 'pending_verification', '2025-10-16 18:25:03', '2025-10-16 18:25:03'),
(3, 76, '', '4443 665 334', 'payment_76_1760639220.jpg', 'pending_verification', '2025-10-16 18:27:00', '2025-10-16 18:27:00'),
(4, 77, '', '23424 423424 43566', 'payment_77_1760640474.jpg', 'pending_verification', '2025-10-16 18:47:54', '2025-10-16 18:47:54'),
(5, 78, '', '4443 665 334', 'payment_78_1760640969.jpg', 'verified', '2025-10-16 18:56:09', '2025-10-16 19:00:40'),
(6, 79, '', '634 5344 5654', 'payment_79_1760645110.jpg', 'verified', '2025-10-16 20:05:10', '2025-10-16 20:44:27'),
(7, 80, '', '3243 4234324 42342', 'payment_80_1760646930.jpg', 'verified', '2025-10-16 20:35:30', '2025-10-16 20:44:39'),
(8, 81, '', '324 656 9809', 'payment_81_1760647573.jpg', 'verified', '2025-10-16 20:46:13', '2025-10-16 20:46:35'),
(9, 82, '', '4 765 657', 'payment_82_1760647811.jpg', 'verified', '2025-10-16 20:50:11', '2025-10-16 20:50:16'),
(10, 83, '', '5565677', 'payment_83_1760647901.jpg', 'verified', '2025-10-16 20:51:41', '2025-10-16 20:51:50'),
(11, 84, '', '5646', 'payment_84_1760649339.jpg', 'verified', '2025-10-16 21:15:39', '2025-10-16 21:15:57'),
(12, 85, '', '967459456', 'payment_85_1760650331.jpg', 'verified', '2025-10-16 21:32:11', '2025-10-16 21:59:11'),
(13, 86, '', '96345 5654654', 'payment_86_1760653391.jpg', 'verified', '2025-10-16 22:23:11', '2025-10-16 22:23:19'),
(14, 87, '', '9856745', 'payment_87_1760653908.jpg', '', '2025-10-16 22:31:48', '2025-10-16 22:32:32'),
(15, 88, '', '63466867', 'payment_88_1760654102.jpg', 'verified', '2025-10-16 22:35:02', '2025-10-16 22:40:24'),
(16, 89, '', '9856745', 'payment_89_1760654211.jpg', 'verified', '2025-10-16 22:36:51', '2025-10-16 22:40:21'),
(17, 90, '', '28947234', 'payment_90_1760654450.jpg', '', '2025-10-16 22:40:50', '2025-10-16 22:41:13'),
(18, 91, '', '09954657', 'payment_91_1760654713.jpg', '', '2025-10-16 22:45:13', '2025-10-16 22:45:26'),
(19, 92, '', '534534', 'payment_92_1760655180.jpg', '', '2025-10-16 22:53:00', '2025-10-17 00:03:00'),
(20, 93, '', '953545', 'payment_93_1760655222.jpg', '', '2025-10-16 22:53:42', '2025-10-16 22:59:04'),
(21, 94, '', '8978342565', 'payment_94_1760655493.jpg', '', '2025-10-16 22:58:13', '2025-10-16 22:58:34'),
(22, 95, '', '85656 34536', 'payment_95_1760655583.jpg', '', '2025-10-16 22:59:43', '2025-10-16 23:29:18'),
(23, 96, '', '09756', 'payment_96_1760656574.jpg', '', '2025-10-16 23:16:14', '2025-10-16 23:29:08'),
(24, 97, '', '9576764', 'payment_97_1760657150.jpg', '', '2025-10-16 23:25:50', '2025-10-16 23:29:03'),
(25, 98, '', '765890', 'payment_98_1760657502.jpg', '', '2025-10-16 23:31:42', '2025-10-16 23:32:08'),
(26, 99, '', '435435', 'payment_99_1760657771.jpg', '', '2025-10-16 23:36:11', '2025-10-16 23:36:23'),
(27, 100, '', '345345', 'payment_100_1760658223.jpg', '', '2025-10-16 23:43:43', '2025-10-16 23:43:59'),
(28, 101, '', '5435435', 'payment_101_1760658382.jpg', '', '2025-10-16 23:46:22', '2025-10-17 00:03:04'),
(29, 102, '', '75663345', 'payment_102_1760658484.jpg', '', '2025-10-16 23:48:04', '2025-10-17 00:02:56'),
(30, 103, '', '54399869', 'payment_103_1760659409.jpg', 'pending_verification', '2025-10-17 00:03:29', '2025-10-17 00:03:29'),
(31, 104, '', '878', 'payment_104_1760659637.jpg', 'pending_verification', '2025-10-17 00:07:17', '2025-10-17 00:07:17'),
(32, 105, '', '556456', 'payment_105_1760659763.jpg', 'pending_verification', '2025-10-17 00:09:23', '2025-10-17 00:09:23'),
(33, 106, '', '978645 64564', 'payment_106_1760660328.jpg', 'pending_verification', '2025-10-17 00:18:48', '2025-10-17 00:18:48'),
(34, 107, '', '6546 6546', 'payment_107_1760660417.jpg', 'pending_verification', '2025-10-17 00:20:17', '2025-10-17 00:20:17'),
(35, 108, '', '654 6547', 'payment_108_1760660853.jpg', 'pending_verification', '2025-10-17 00:27:33', '2025-10-17 00:27:33'),
(36, 110, '', '9856745', 'payment_110_1760661282.jpg', 'verified', '2025-10-17 00:34:42', '2025-10-17 00:34:51'),
(37, 111, '', '28947234', 'payment_111_1760661370.jpg', '', '2025-10-17 00:36:10', '2025-10-17 00:41:00'),
(38, 112, '', '63466867', 'payment_112_1760662439.jpg', 'pending_verification', '2025-10-17 00:53:59', '2025-10-17 00:53:59'),
(39, 113, '', '28947234', 'payment_113_1760663079.jpg', 'pending_verification', '2025-10-17 01:04:39', '2025-10-17 01:04:39'),
(40, 114, '', '63466867', 'payment_114_1760714610.jpg', 'verified', '2025-10-17 15:23:30', '2025-10-17 15:24:39');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(200) NOT NULL,
  `name` varchar(200) NOT NULL,
  `category` enum('Main Dishes','Side Dishes','Desserts') NOT NULL,
  `price` int(200) NOT NULL,
  `stock` int(11) DEFAULT 0,
  `image` varchar(200) DEFAULT NULL,
  `stock_status` varchar(20) DEFAULT 'normal'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `name`, `category`, `price`, `stock`, `image`, `stock_status`) VALUES
(13, 'Lasagna', 'Main Dishes', 900, 45, 'web1.jpg', 'normal'),
(14, 'Pastilan', 'Main Dishes', 126, 31, 'web2.jpg', 'normal'),
(15, 'Crinkles', 'Desserts', 100, 15, 'web5.jpg', 'normal'),
(18, 'fishda', 'Main Dishes', 200, 37, '550884818_1066900732318507_1897651687058388133_n.jpg', 'normal'),
(19, 'itlog ', 'Side Dishes', 20, 20, 'newprofile.png', 'normal'),
(20, 'Sinigang', 'Main Dishes', 100, 49, 'amberjack-hamachi.jpg', 'normal'),
(21, 'Manga', 'Desserts', 20, 44, 'manga.jpg', 'normal'),
(22, 'Atchara', 'Side Dishes', 30, 31, 'atchara.jpg', 'normal');

--
-- Triggers `products`
--
DELIMITER $$
CREATE TRIGGER `check_stock_before_update` BEFORE UPDATE ON `products` FOR EACH ROW BEGIN
    IF NEW.stock < 0 THEN
        SET NEW.stock = 0;
    END IF;
    IF NEW.stock <= 10 THEN
        SET NEW.stock_status = 'low';
    ELSE
        SET NEW.stock_status = 'normal';
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `stock_history`
--

CREATE TABLE `stock_history` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `type` enum('stock_in','stock_out') NOT NULL,
  `quantity` int(11) NOT NULL,
  `previous_stock` int(11) NOT NULL,
  `new_stock` int(11) NOT NULL,
  `date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `stock_history`
--

INSERT INTO `stock_history` (`id`, `product_id`, `type`, `quantity`, `previous_stock`, `new_stock`, `date`) VALUES
(1, 15, 'stock_out', 1, 50, 49, '2025-09-29 19:28:59'),
(2, 18, 'stock_in', 50, 0, 50, '2025-09-29 19:30:21'),
(3, 19, 'stock_in', 50, 0, 50, '2025-09-29 19:30:26'),
(4, 13, 'stock_in', 50, 0, 50, '2025-09-29 19:30:31'),
(5, 21, 'stock_in', 50, 0, 50, '2025-09-29 19:30:34'),
(6, 14, 'stock_in', 50, 0, 50, '2025-09-29 19:30:36'),
(7, 20, 'stock_in', 50, 0, 50, '2025-09-29 19:30:39'),
(8, 15, 'stock_out', 20, 49, 29, '2025-09-29 20:12:42'),
(9, 15, 'stock_out', 20, 29, 9, '2025-09-29 20:12:52'),
(10, 15, 'stock_out', 3, 9, 6, '2025-09-29 20:13:11'),
(11, 15, 'stock_out', 5, 6, 1, '2025-09-29 20:13:18'),
(12, 18, 'stock_out', 50, 50, 0, '2025-09-29 20:13:33'),
(13, 19, 'stock_out', 25, 50, 25, '2025-09-29 20:15:25'),
(14, 19, 'stock_out', 23, 25, 2, '2025-09-29 20:17:38'),
(15, 15, 'stock_out', 1, 1, 0, '2025-09-29 20:54:08'),
(16, 15, 'stock_in', 3, 0, 3, '2025-09-29 20:56:57'),
(17, 15, 'stock_out', 1, 3, 2, '2025-09-29 20:57:24'),
(18, 15, 'stock_out', 1, 2, 1, '2025-09-29 20:57:25'),
(20, 14, 'stock_out', 2, 50, 48, '2025-09-29 20:57:36'),
(21, 15, 'stock_out', 1, 1, 0, '2025-09-29 20:57:36'),
(22, 15, 'stock_in', 50, 0, 50, '2025-09-30 20:05:32'),
(23, 15, 'stock_out', 50, 50, 0, '2025-10-01 08:01:28'),
(24, 19, 'stock_out', 2, 2, 0, '2025-10-01 08:16:49'),
(25, 19, 'stock_in', 23, 0, 23, '2025-10-01 08:20:46'),
(26, 18, 'stock_in', 45, 0, 45, '2025-10-01 08:20:54'),
(27, 15, 'stock_in', 20, 0, 20, '2025-10-01 08:30:47'),
(28, 15, 'stock_out', 19, 20, 1, '2025-10-01 08:30:59'),
(29, 15, 'stock_out', 1, 1, 0, '2025-10-01 08:49:57'),
(30, 15, 'stock_in', 12, 0, 12, '2025-10-01 12:17:33'),
(31, 14, 'stock_out', 1, 48, 47, '2025-10-01 12:18:03'),
(32, 18, 'stock_out', 1, 45, 44, '2025-10-01 12:18:03'),
(33, 14, 'stock_out', 1, 47, 46, '2025-10-01 12:18:09'),
(34, 15, 'stock_out', 12, 12, 0, '2025-10-01 12:58:58'),
(35, 15, 'stock_in', 12, 0, 12, '2025-10-01 13:02:55'),
(36, 13, 'stock_out', 1, 50, 49, '2025-10-01 14:55:50'),
(37, 14, 'stock_out', 1, 46, 45, '2025-10-01 15:07:28'),
(38, 21, 'stock_out', 2, 50, 48, '2025-10-01 15:07:28'),
(39, 15, 'stock_out', 3, 12, 9, '2025-10-01 15:07:28'),
(40, 19, 'stock_out', 1, 23, 22, '2025-10-01 15:07:28'),
(41, 22, 'stock_in', 50, 0, 50, '2025-10-01 15:35:34'),
(42, 22, 'stock_out', 1, 50, 49, '2025-10-01 15:42:57'),
(43, 21, 'stock_out', 1, 48, 47, '2025-10-01 15:48:28'),
(44, 14, 'stock_out', 1, 45, 44, '2025-10-01 15:48:28'),
(45, 15, 'stock_out', 1, 9, 8, '2025-10-01 15:48:28'),
(46, 22, 'stock_out', 49, 49, 0, '2025-10-01 17:06:14'),
(47, 22, 'stock_in', 34, 0, 34, '2025-10-01 17:06:54'),
(48, 22, 'stock_out', 1, 34, 33, '2025-10-01 17:27:48'),
(49, 13, 'stock_out', 1, 49, 48, '2025-10-01 17:37:50'),
(50, 14, 'stock_out', 1, 44, 43, '2025-10-01 17:37:50'),
(51, 15, 'stock_out', 1, 8, 7, '2025-10-01 17:37:50'),
(52, 18, 'stock_out', 1, 44, 43, '2025-10-01 17:37:50'),
(53, 19, 'stock_out', 1, 22, 21, '2025-10-01 17:37:50'),
(54, 22, 'stock_out', 1, 33, 32, '2025-10-01 17:37:50'),
(55, 21, 'stock_out', 1, 47, 46, '2025-10-01 17:37:50'),
(56, 20, 'stock_out', 1, 50, 49, '2025-10-01 17:37:50'),
(57, 15, 'stock_out', 1, 7, 6, '2025-10-05 23:16:24'),
(58, 15, 'stock_out', 2, 6, 4, '2025-10-05 23:16:27'),
(59, 18, 'stock_out', 1, 43, 42, '2025-10-05 23:16:27'),
(60, 15, 'stock_out', 1, 4, 3, '2025-10-05 23:16:28'),
(61, 13, 'stock_out', 1, 48, 47, '2025-10-05 23:16:29'),
(62, 14, 'stock_out', 1, 43, 42, '2025-10-05 23:16:29'),
(63, 15, 'stock_out', 1, 3, 2, '2025-10-05 23:16:30'),
(64, 15, 'stock_out', 1, 2, 1, '2025-10-05 23:16:30'),
(65, 14, 'stock_out', 1, 42, 41, '2025-10-05 23:16:30'),
(66, 13, 'stock_out', 1, 47, 46, '2025-10-05 23:16:32'),
(67, 14, 'stock_out', 1, 41, 40, '2025-10-05 23:16:32'),
(68, 15, 'stock_out', 1, 1, 0, '2025-10-05 23:16:32'),
(69, 18, 'stock_out', 1, 42, 41, '2025-10-05 23:16:33'),
(70, 22, 'stock_out', 1, 32, 31, '2025-10-05 23:16:49'),
(71, 18, 'stock_out', 2, 41, 39, '2025-10-05 23:16:51'),
(72, 13, 'stock_out', 1, 46, 45, '2025-10-05 23:16:57'),
(73, 21, 'stock_out', 1, 46, 45, '2025-10-05 23:16:57'),
(74, 14, 'stock_out', 1, 40, 39, '2025-10-05 23:16:58'),
(75, 15, 'stock_in', 24, 0, 24, '2025-10-05 23:18:14'),
(76, 15, 'stock_out', 1, 24, 23, '2025-10-05 23:18:37'),
(77, 15, 'stock_out', 1, 23, 22, '2025-10-05 23:18:38'),
(78, 15, 'stock_out', 1, 22, 21, '2025-10-05 23:18:38'),
(79, 15, 'stock_out', 1, 21, 20, '2025-10-05 23:18:39'),
(80, 15, 'stock_out', 1, 20, 19, '2025-10-05 23:18:40'),
(81, 15, 'stock_out', 1, 19, 18, '2025-10-05 23:18:42'),
(82, 14, 'stock_out', 1, 39, 38, '2025-10-05 23:18:42'),
(83, 14, 'stock_out', 1, 38, 37, '2025-10-05 23:18:43'),
(84, 14, 'stock_out', 1, 37, 36, '2025-10-05 23:18:46'),
(85, 14, 'stock_out', 1, 36, 35, '2025-10-05 23:18:47'),
(86, 15, 'stock_out', 1, 18, 17, '2025-10-05 23:18:47'),
(87, 18, 'stock_out', 1, 39, 38, '2025-10-05 23:18:47'),
(88, 15, 'stock_out', 1, 17, 16, '2025-10-16 22:49:51'),
(89, 14, 'stock_out', 1, 35, 34, '2025-10-16 22:49:55'),
(90, 14, 'stock_out', 1, 34, 33, '2025-10-16 22:49:58'),
(91, 14, 'stock_out', 1, 33, 32, '2025-10-16 22:50:01'),
(92, 14, 'stock_out', 1, 32, 31, '2025-10-16 22:51:21'),
(93, 18, 'stock_out', 1, 38, 37, '2025-10-16 22:51:45'),
(94, 15, 'stock_out', 1, 16, 15, '2025-10-16 22:51:45'),
(95, 19, 'stock_out', 1, 21, 20, '2025-10-16 22:51:57'),
(96, 21, 'stock_out', 1, 45, 44, '2025-10-16 23:47:20');

-- --------------------------------------------------------

--
-- Table structure for table `super_admin_users`
--

CREATE TABLE `super_admin_users` (
  `super_admin_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_login` timestamp NULL DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `role_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `super_admin_users`
--

INSERT INTO `super_admin_users` (`super_admin_id`, `username`, `password`, `email`, `full_name`, `created_at`, `last_login`, `is_active`, `role_id`) VALUES
(1, 'admin', '$2y$10$gBR26WV9yIsLqnw1b/Q10OqcmxfCkZFAvT5dzAqaO4WbQL5Mi.uVa', 'admin01@gmail.com', 'Jeff Feliciano', '2025-09-26 19:27:17', NULL, 1, 1);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `Id` int(10) NOT NULL,
  `FirstName` varchar(50) NOT NULL,
  `LastName` varchar(50) NOT NULL,
  `Email` varchar(50) NOT NULL,
  `username` varchar(50) DEFAULT NULL,
  `Password` varchar(255) DEFAULT NULL,
  `role_id` int(11) NOT NULL DEFAULT 4,
  `profile_picture` varchar(255) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `id_document` varchar(255) DEFAULT NULL,
  `verification_status` enum('pending','approved','rejected') DEFAULT NULL,
  `verification_date` datetime DEFAULT NULL,
  `verification_notes` text DEFAULT NULL,
  `discount_type` enum('none','pwd','senior') DEFAULT 'none',
  `discount_rate` decimal(5,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`Id`, `FirstName`, `LastName`, `Email`, `username`, `Password`, `role_id`, `profile_picture`, `phone`, `address`, `id_document`, `verification_status`, `verification_date`, `verification_notes`, `discount_type`, `discount_rate`) VALUES
(1, 'jeff', 'feliciano', 'jeje@gmail.com', NULL, '202cb962ac59075b964b07152d234b70', 4, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'none', 0.00),
(2, 'jeff', 'feliciano', 'admins@gmail.com', NULL, '9ed6a5571323d50fd224af605b4ae077', 4, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'none', 0.00),
(3, 'Jeff', 'Feliciano', 'anne@gmail.com', NULL, '7440da479f6533e79ab58fc153307c3b', 4, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'none', 0.00),
(4, 'jeffer', 'ciano', 'jeffreidastory@gmail.com', 'jeff1', '$2y$10$XzrsMlnXr7urxqnDSoWpcO75IIDUfi240Dt21/YR/ZU', 4, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'none', 0.00),
(5, 'nat', 'tnatna', 'ann@gmail.com', 'nath', '$2y$10$LLVlAHJr2N.lYkPhRZLmMudDYitHCWEQGd8.fj7MV3BYG.mZ/vED6', 4, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'none', 0.00),
(6, 'eto', 'sample', 'janidscefchua19@gmail.com', 'sample', '$2y$10$oEo40L1TFIEsMWF6QW/Q1OHcgWx4l44mHs8hKBG0b2c7qfp.03WIm', 4, '68dfc70b943b8_pfp.png', '09787856555', 'Kaypian, bulacan Sanjose', '68dfcac6b238c_sampol.jpg', 'approved', '2025-10-03 15:08:42', '', 'none', 0.00),
(8, 'denisead', 'Wonwoo', 'den@gmail.com', 'Den', '$2y$10$1DRsavAY1t7yyllGWvQhVux4MUkrVQ2rkssAaH.FdWBAnm5cCZgsu', 4, '68d8135dc5de2_newprofile.png', '09455423535', 'Kaypian', '68e1444bee310_sampol.jpg', 'approved', '2025-10-04 17:59:30', '', 'none', 0.00),
(11, 'Grace', 'anta', 'admin01@gmail.com', 'admin01', '$2y$10$XB5FM5jBa46ka3hkDMOJIOwZllyfyxI8RD29TQ0OxwIhHn3oL6zyK', 2, '68dccfb5aabbb_profile.png', '0935444234', 'Mexico', NULL, NULL, NULL, NULL, 'none', 0.00),
(12, 'jays', 'yajs', 'crew01@gmail.com', 'crew01', '$2y$10$YxcOsHCx6v29SmAb.HldyusuGj3xxQYMU6.XniZOfWQBEbcmHP4Mq', 3, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'none', 0.00),
(13, 'adsyui', 'qweert', 'qwer@gmail.com', 'qwer', '$2y$10$bEKHHy1.XQICUjkin60dCOzhtZTJ.tgG6BnYHu9SNLDUgFCd9QaBa', 2, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'none', 0.00),
(14, 'jaysss', 'yajss', 'jeffreidastoryasd@gmail.com', 'Jaysss', '$2y$10$yEZ.bnsQJDYnLksEYohkaew6dlrzFHfbfyuO/EvFnuYMae1y30JQe', 2, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'none', 0.00),
(15, 'raven', 'pablo', 'rjven@gmail.com', 'rjven', '$2y$10$CQhYy33l3MhE/qJLQY5XZOpBNfa64sfy4NDIaE9HZSuD02pO.nHly', 4, '68d82d95ace6e_profile.jpg', '09455423535', 'kaypian', '68dfbca300f47_sample.jpg', 'approved', '2025-10-03 14:08:22', '', 'none', 0.00),
(16, 'Cora', 'Feliciano', 'Cora@gmail.com', 'Cora', '$2y$10$T7QAY1ErPnG2hdnR0uXUUuo.e.XX0K8mrtdJo6U0pCxStf7TAdyHm', 4, '68dab83d6c452_profile.jpg', '09654634523', 'HONGKONG', '68dfb935f1659_sampol.jpg', 'approved', '2025-10-03 13:58:19', '', 'none', 0.00),
(17, 'jay', 'galang', 'jay@gmail.com', 'jay123', '$2y$10$pTGPaEep0nQ1ogGfHCFGRuF4IBhcMWkB2QDNgTKr5vpCtqTYbiqHS', 4, '68dd3d51e75e0_pfp.png', '09275604562', 'Bagong Silang, QC', '68dfbdee1287f_sampol.jpg', 'approved', '2025-10-03 14:23:32', '', 'none', 0.00),
(18, 'Aira', 'Feliciano', 'aira@gmail.com', 'Aira', '$2y$10$Y6h.kLaoh0NKOrLyn2wugOt5yZftM1dzCCOTdiY8V3fV4zDwYJ6d6', 4, '68df889e8f405_seo yeaji.jpg', '09944767382', 'Kaypian Hills, San Jose Bulacan', '68dfb224185e3_sample.jpg', 'approved', '2025-10-03 13:51:04', '', 'none', 0.00),
(19, 'Jeff', 'Feliciano', 'Jeff@gmail.com', 'Jeff123', '$2y$10$3qAmFTLgYdgy.F9f2L/Gpe1FljNLcB76fdI1OuKQxRMLVGKj2DLLm', 4, '68dfc6ac79765_pfp.png', '09944767542', 'Kaypian, bulacan', '68dfc693d51bc_sampol.jpg', 'approved', '2025-10-03 14:51:10', '', 'none', 0.00),
(20, 'Grace', 'Arcella', 'Grace@gmail.com', 'Grace', '$2y$10$p5NYtambNiyJUNsw0rd.ou2dh9jp2qRpKd8D2WtmtQLv9SNTmWywG', 4, NULL, NULL, NULL, '68dfdc92ddb3b_sample.jpg', 'approved', '2025-10-03 16:24:32', '', 'none', 0.00),
(21, 'Cedric', 'Feliciano', 'Ced@gmail.com', 'Ced', '$2y$10$eYfulcFwBCZOg/gAZGVNludjXCD38d.tX93Pww8mRR8z0PaAkcnEy', 4, NULL, '09324232544', 'Kaypian Huills', '68dfee0e599f7_sampol.jpg', 'approved', '2025-10-03 17:39:01', '', 'none', 0.00),
(22, 'Yeaji', 'Seo', 'seoyeji@gmail.com', 'Seo', '$2y$10$/aHrMQAE7UV0fy8AimTjau5NNP75zAOHdq4IlMjOp6sPFNGcSY52K', 4, NULL, NULL, NULL, '68dff0eebb750_sampol.jpg', 'approved', '2025-10-03 17:51:21', '', 'none', 0.00),
(23, 'Naksu', 'Go', 'Nakse@gmail.com', 'Nakse', '$2y$10$sW4nBxi83ltVsi.7ptoVh.bdV0DwPjup6tv3uUy8UOR40u2c4ZOeG', 4, NULL, NULL, NULL, '68dff1fed55e0_sampol.jpg', 'approved', '2025-10-03 17:55:47', '', 'none', 0.00),
(24, 'Jang', 'Uk', 'jang@gmail.com', 'Jang', '$2y$10$Xtq5647sOt0V0YqvSQE/KO5e5h6cTMP05C7uvRFIdFW3kq5ALghzC', 4, NULL, NULL, NULL, '68dff432c0c29_sample.jpg', 'approved', '2025-10-03 18:05:19', '', 'none', 0.00),
(25, 'Seo', 'Yul', 'Yul@gmail.com', 'Yul', '$2y$10$6fBfu.I.qdrdCcgO96a1M.dnPD6yw6ZOdprkTv9JbG47tNLm/SAei', 4, NULL, NULL, NULL, '68e045a654488_sampol.jpg', 'approved', '2025-10-04 17:41:13', '', 'none', 0.00),
(26, 'Mudeok', 'Yeon', 'Muds@gmail.com', 'Muds', '$2y$10$tHBJ5mndp97C2NMSBt8r1OkuCzm2etxNNtBr71BDNRctExae/XpDi', 4, '68e14f0402cbf_seo yeaji.jpg', '09786785535', 'Bulacan , Palmera', '68e151dad1eb4_sample.jpg', 'approved', '2025-10-04 18:57:19', NULL, 'none', 0.00),
(27, 'Vince', 'Nagal', 'naksujin024@gmail.com', 'Nathy', '$2y$10$3eiTSnEcqdz06gmveGpF.uZ1NYMlLbxvbnICJjyJhimYf.PG808tO', 4, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'none', 0.00),
(28, 'Jay', 'Mon', 'soyeaji24@gmail.com', 'Mon', '$2y$10$IkElPbyzUHJuAZaMYsYWhefzipYQr99.IDkvIyoShLR5OLLWlDq/a', 4, '68e2ea019d8d2_image-removebg-preview.png', '09455423535', '241 Kaypian Hills, Kaypian, San Jose Del Monte, Bulacan, 3023, Philippines', '68e2eb0d1b2fe_sampol.jpg', 'approved', '2025-10-06 00:07:24', NULL, 'none', 0.00),
(29, 'Graces', 'Wonwoo', 'Koricsvagabond@gmail.com', 'Gr0123', '$2y$10$DnNGAl0sLPShf579C0Poaeo1Yt8DRR9VSvnSq.a.snhZvYC2ZXzT2', 4, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'none', 0.00),
(30, 'Vincenzo', 'Cassano', 'jffrsnfeliciano0000@gmail.com', 'Mafia', '$2y$10$YW2JKNrqxTzpq6FPwz3/X.YB22aAPzc7dLzglAnx3SYzzDsisAKb2', 4, NULL, '09944767382', '241 Kaypian Hills, Kaypian, San Jose Del Monte, Bulacan, 3023, Philippines', '68e3f653480a6_sample_pwd.png', 'approved', '2025-10-06 19:03:31', NULL, NULL, NULL),
(31, 'Seolran', 'Jin', 'corafeliciano09@gmail.com', 'Jin', '$2y$10$oGmVNT4o0hECPJ1jPNuxgOGN/vKg7ApmvTnl.ZlpYXk4m8pQs9XqG', 4, NULL, '09944767382', 'Unit 12, 12th Floor, AD Building,  San Antonio Village, Makati City, Metro Manila, 4245, Philippines', '68e4048da9967_sample_pwd.png', 'approved', '2025-10-06 21:02:46', NULL, 'none', 0.00),
(32, 'Aira', 'Araneta', 'aywaaraneta@gmail.com', 'Aira24', '$2y$10$Wd/JyNZm3iq3Rgm3OVisu.YH/yEzOxoGW/9BDcpkKLXY31qRJlXAe', 4, '68f274d48c127_1760720084.jpg', '09445663366', '256 Kaypian Hills, Carrisa, San Jose Del Monte, Bulacan, 3023', '68e69883ce225_sample_pwd.png', 'approved', '2025-10-08 20:46:50', NULL, 'none', 0.00),
(33, 'Romeo', 'Feliciano', 'vincenzocasaano24@gmail.com', 'Roms', '$2y$10$4244l6Z93yvhVjSCT8jUCOCT5YIE3n.5IGWqHdnlTwmqPh1rfWS6W', 4, NULL, '09944767333', '223 Kaypian Hills, Phase 5, San Jose Del Monte, Bulacan, 3024', NULL, NULL, NULL, NULL, 'none', 0.00),
(34, 'Ced', 'Felic', 'graceanneee25@gmail.com', 'Cedric', '$2y$10$4/.GT5qYu6.TPigbuFWZRuRZY5aPP/VVqtGlULcyEy3cdeZdjTBAi', 4, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'none', 0.00),
(35, 'Lubian', 'Gener', 'daxal44183@fixwap.com', 'Galang', '$2y$10$4RzbgrIxTkDV9B7Thkrl/.TT4xS.Aok7WC18mAUUSMQ/bfTWz9r3a', 4, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'none', 0.00);

-- --------------------------------------------------------

--
-- Table structure for table `user_roles`
--

CREATE TABLE `user_roles` (
  `role_id` int(11) NOT NULL,
  `role_name` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_roles`
--

INSERT INTO `user_roles` (`role_id`, `role_name`, `description`, `created_at`, `updated_at`) VALUES
(1, 'Super Admin', 'Full system access with all permissions', '2025-09-26 21:32:16', '2025-09-26 21:32:16'),
(2, 'Admin', 'Administrative access with elevated permissions', '2025-09-26 21:32:16', '2025-09-26 21:32:16'),
(3, 'Crew', 'Staff/crew access with limited permissions', '2025-09-26 21:32:16', '2025-09-26 21:32:16'),
(4, 'Customer', 'Access for customers with basic functionality', '2025-09-26 21:32:16', '2025-09-26 21:32:16');

-- --------------------------------------------------------

--
-- Table structure for table `verification_history`
--

CREATE TABLE `verification_history` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `status` enum('pending','approved','rejected') NOT NULL,
  `created_at` datetime NOT NULL,
  `user_name` varchar(255) DEFAULT NULL,
  `id_document` varchar(255) DEFAULT NULL,
  `admin_name` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `verification_history`
--

INSERT INTO `verification_history` (`id`, `user_id`, `status`, `created_at`, `user_name`, `id_document`, `admin_name`) VALUES
(2, 21, 'approved', '2025-10-03 17:32:52', NULL, NULL, 'Grace anta'),
(4, 21, 'approved', '2025-10-03 17:39:01', 'Cedric Feliciano', '68dfee0e599f7_sampol.jpg', 'Grace anta'),
(5, 22, 'approved', '2025-10-03 17:51:21', NULL, '68dff0eebb750_sampol.jpg', 'Grace anta'),
(7, 23, 'approved', '2025-10-03 17:55:47', NULL, '68dff1fed55e0_sampol.jpg', 'Grace anta'),
(8, 24, 'approved', '2025-10-03 18:05:19', 'Jang Uk (Jang)', '68dff432c0c29_sample.jpg', 'Grace anta'),
(9, 24, 'approved', '2025-10-03 18:05:19', 'Jang Uk (Jang)', '68dff432c0c29_sample.jpg', 'Grace anta'),
(10, 24, 'approved', '2025-10-03 18:05:19', 'Jang Uk (Jang)', '68dff432c0c29_sample.jpg', 'Grace anta'),
(11, 25, 'approved', '2025-10-04 17:41:13', 'Seo Yul (Yul)', '68e045a654488_sampol.jpg', 'Grace anta'),
(12, 25, 'approved', '2025-10-04 17:41:13', 'Seo Yul (Yul)', '68e045a654488_sampol.jpg', 'Grace anta'),
(13, 25, 'approved', '2025-10-04 17:41:13', 'Seo Yul (Yul)', '68e045a654488_sampol.jpg', 'Grace anta'),
(14, 25, 'approved', '2025-10-04 17:41:13', 'Seo Yul (Yul)', '68e045a654488_sampol.jpg', 'Grace anta'),
(15, 25, 'approved', '2025-10-04 17:41:13', 'Seo Yul (Yul)', '68e045a654488_sampol.jpg', 'Grace anta'),
(16, 25, 'approved', '2025-10-04 17:41:13', 'Seo Yul (Yul)', '68e045a654488_sampol.jpg', 'Grace anta'),
(18, 25, 'approved', '2025-10-04 17:41:13', 'Seo Yul (Yul)', '68e045a654488_sampol.jpg', 'Grace anta'),
(19, 25, 'approved', '2025-10-04 17:41:13', 'Seo Yul (Yul)', '68e045a654488_sampol.jpg', 'Grace anta'),
(20, 25, 'approved', '2025-10-04 17:41:13', 'Seo Yul (Yul)', '68e045a654488_sampol.jpg', 'Grace anta'),
(21, 25, 'approved', '2025-10-04 17:41:13', 'Seo Yul (Yul)', '68e045a654488_sampol.jpg', 'Grace anta'),
(22, 25, 'approved', '2025-10-04 17:41:13', 'Seo Yul (Yul)', '68e045a654488_sampol.jpg', 'Grace anta'),
(23, 25, 'approved', '2025-10-04 17:41:13', 'Seo Yul (Yul)', '68e045a654488_sampol.jpg', 'Grace anta'),
(24, 25, 'approved', '2025-10-04 17:41:13', 'Seo Yul (Yul)', '68e045a654488_sampol.jpg', 'Grace anta'),
(25, 25, 'approved', '2025-10-04 17:41:13', 'Seo Yul (Yul)', '68e045a654488_sampol.jpg', 'Grace anta'),
(26, 25, 'approved', '2025-10-04 17:41:13', 'Seo Yul (Yul)', '68e045a654488_sampol.jpg', 'Grace anta'),
(27, 25, 'approved', '2025-10-04 17:41:13', 'Seo Yul (Yul)', '68e045a654488_sampol.jpg', 'Grace anta'),
(28, 8, 'approved', '2025-10-04 17:59:30', 'denisead Wonwoo (Den)', '68e1444bee310_sampol.jpg', 'Grace anta'),
(29, 26, 'approved', '2025-10-04 18:57:19', 'Mudeok Yeon (Muds)', '68e151dad1eb4_sample.jpg', 'Grace anta'),
(30, 26, 'approved', '2025-10-04 18:57:19', 'Mudeok Yeon (Muds)', '68e151dad1eb4_sample.jpg', 'Grace anta'),
(31, 28, 'approved', '2025-10-06 00:07:24', 'Jay Mon (Mon)', '68e2eb0d1b2fe_sampol.jpg', 'Grace anta'),
(32, 30, 'pending', '0000-00-00 00:00:00', NULL, NULL, ''),
(33, 31, 'approved', '2025-10-06 21:02:46', 'Seolran Jin (Jin)', '68e4048da9967_sample_pwd.png', 'Grace anta'),
(34, 32, 'approved', '2025-10-08 20:46:50', 'Aira Araneta (Aira24)', '68e69883ce225_sample_pwd.png', 'Grace anta');

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_verification_history`
-- (See below for the actual view)
--
CREATE TABLE `v_verification_history` (
);

-- --------------------------------------------------------

--
-- Structure for view `v_verification_history`
--
DROP TABLE IF EXISTS `v_verification_history`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_verification_history`  AS SELECT `vh`.`id` AS `id`, `vh`.`user_id` AS `user_id`, `vh`.`admin_name` AS `verified_by`, `vh`.`status` AS `status`, `vh`.`notes` AS `notes`, `vh`.`created_at` AS `created_at`, `vh`.`user_name` AS `user_verified`, `vh`.`id_document` AS `id_document`, `vh`.`action_type` AS `action_type`, `u`.`FirstName` AS `user_firstname`, `u`.`LastName` AS `user_lastname`, `u`.`Email` AS `user_email` FROM (`verification_history` `vh` left join `users` `u` on(`vh`.`user_id` = `u`.`Id`)) ORDER BY `vh`.`created_at` DESC ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `cart_items`
--
ALTER TABLE `cart_items`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_product` (`user_id`,`product_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `delivery_addresses`
--
ALTER TABLE `delivery_addresses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `landing_settings`
--
ALTER TABLE `landing_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_single_record` (`id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `order_status_logs`
--
ALTER TABLE `order_status_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `crew_id` (`crew_id`);

--
-- Indexes for table `otp_codes`
--
ALTER TABLE `otp_codes`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `payment_records`
--
ALTER TABLE `payment_records`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `stock_history`
--
ALTER TABLE `stock_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `super_admin_users`
--
ALTER TABLE `super_admin_users`
  ADD PRIMARY KEY (`super_admin_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`Id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `fk_user_role` (`role_id`);

--
-- Indexes for table `user_roles`
--
ALTER TABLE `user_roles`
  ADD PRIMARY KEY (`role_id`);

--
-- Indexes for table `verification_history`
--
ALTER TABLE `verification_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_admin_name` (`admin_name`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `cart_items`
--
ALTER TABLE `cart_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=40;

--
-- AUTO_INCREMENT for table `delivery_addresses`
--
ALTER TABLE `delivery_addresses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `landing_settings`
--
ALTER TABLE `landing_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=115;

--
-- AUTO_INCREMENT for table `order_status_logs`
--
ALTER TABLE `order_status_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `otp_codes`
--
ALTER TABLE `otp_codes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- AUTO_INCREMENT for table `payment_records`
--
ALTER TABLE `payment_records`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(200) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `stock_history`
--
ALTER TABLE `stock_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=97;

--
-- AUTO_INCREMENT for table `super_admin_users`
--
ALTER TABLE `super_admin_users`
  MODIFY `super_admin_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `Id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT for table `user_roles`
--
ALTER TABLE `user_roles`
  MODIFY `role_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `verification_history`
--
ALTER TABLE `verification_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `cart_items`
--
ALTER TABLE `cart_items`
  ADD CONSTRAINT `cart_items_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`Id`),
  ADD CONSTRAINT `cart_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);

--
-- Constraints for table `delivery_addresses`
--
ALTER TABLE `delivery_addresses`
  ADD CONSTRAINT `delivery_addresses_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`Id`) ON DELETE CASCADE;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`Id`);

--
-- Constraints for table `order_status_logs`
--
ALTER TABLE `order_status_logs`
  ADD CONSTRAINT `order_status_logs_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_status_logs_ibfk_2` FOREIGN KEY (`crew_id`) REFERENCES `users` (`Id`) ON DELETE SET NULL;

--
-- Constraints for table `payment_records`
--
ALTER TABLE `payment_records`
  ADD CONSTRAINT `payment_records_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`);

--
-- Constraints for table `stock_history`
--
ALTER TABLE `stock_history`
  ADD CONSTRAINT `stock_history_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `fk_user_role` FOREIGN KEY (`role_id`) REFERENCES `user_roles` (`role_id`);

--
-- Constraints for table `verification_history`
--
ALTER TABLE `verification_history`
  ADD CONSTRAINT `verification_history_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`Id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
