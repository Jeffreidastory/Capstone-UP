-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 30, 2025 at 01:20 AM
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
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `name`, `user_id`, `address`, `method`, `total_products`, `item_name`, `total_price`, `status`, `order_time`, `updated_at`) VALUES
(1, 'denisead Wonwoo', 8, 'Kaypian', 'cod', 1, 'Lasagna (1)', 900.00, 'completed', '2025-09-28 19:51:48', '2025-09-28 19:55:08'),
(2, 'denisead Wonwoo', 8, 'Kaypian', 'cod', 1, 'Pastilan (1)', 126.00, 'completed', '2025-09-28 19:51:58', '2025-09-28 19:55:18'),
(3, 'denisead Wonwoo', 8, 'Kaypian', 'cod', 1, 'Crinkles (1)', 100.00, 'completed', '2025-09-28 19:52:06', '2025-09-28 19:55:27'),
(4, 'denisead Wonwoo', 8, 'Kaypian', 'cod', 1, 'fishda (1)', 200.00, 'completed', '2025-09-28 19:52:16', '2025-09-28 19:55:35'),
(5, 'denisead Wonwoo', 8, 'Kaypian', 'gcash', 2, 'itlog  (2)', 40.00, 'completed', '2025-09-28 19:52:28', '2025-09-28 19:55:44'),
(6, 'Cora Feliciano', 16, 'HONGKONG', 'cod', 3, 'Pastilan (2)\nCrinkles (1)', 352.00, 'completed', '2025-09-29 14:38:25', '2025-09-29 20:58:09'),
(7, 'Cora Feliciano', 16, 'HONGKONG', 'cod', 2, 'Pastilan (1)\nfishda (1)', 326.00, 'preparing', '2025-09-29 14:38:55', '2025-09-29 20:57:26'),
(8, 'Cora Feliciano', 16, 'HONGKONG', 'cod', 2, 'Lasagna (1)\nitlog  (1)', 920.00, 'completed', '2025-09-29 14:39:06', '2025-09-29 18:37:14'),
(9, 'Cora Feliciano', 16, 'HONGKONG', 'cod', 1, 'Lasagna (1)', 900.00, 'completed', '2025-09-29 14:39:15', '2025-09-29 14:42:55'),
(10, 'denisead Wonwoo', 8, 'Kaypian', 'cod', 1, 'Crinkles (1)', 100.00, 'completed', '2025-09-29 17:34:57', '2025-09-29 17:36:29'),
(11, 'Cora Feliciano', 16, 'HONGKONG', 'cod', 1, 'Crinkles (1)', 100.00, 'completed', '2025-09-29 17:42:35', '2025-09-29 17:43:19'),
(12, 'raven pablo', 15, 'kaypian', 'cod', 2, 'Crinkles (2)', 200.00, 'completed', '2025-09-29 17:50:49', '2025-09-29 17:51:42'),
(13, 'Cora Feliciano', 16, 'HONGKONG', 'gcash', 1, 'Crinkles (1)', 100.00, 'completed', '2025-09-29 18:03:37', '2025-09-29 18:04:36'),
(14, 'denisead Wonwoo', 8, 'Kaypian', 'gcash', 1, 'Crinkles (1)', 100.00, 'completed', '2025-09-29 18:15:53', '2025-09-29 18:16:29'),
(15, 'denisead Wonwoo', 8, 'Kaypian', 'cod', 1, 'Crinkles (1)', 100.00, 'completed', '2025-09-29 18:22:05', '2025-09-29 18:22:49'),
(16, 'Cora Feliciano', 16, 'HONGKONG', 'cod', 1, 'Crinkles (1)', 100.00, 'completed', '2025-09-29 18:37:45', '2025-09-29 20:58:00'),
(17, 'Cora Feliciano', 16, 'HONGKONG', 'cod', 1, 'Crinkles (1)', 100.00, 'completed', '2025-09-29 18:56:47', '2025-09-29 20:57:52'),
(18, 'Cora Feliciano', 16, 'HONGKONG', 'gcash', 1, 'Crinkles (1)', 100.00, 'completed', '2025-09-29 18:59:58', '2025-09-29 19:29:12'),
(19, 'Cora Feliciano', 16, 'HONGKONG', 'cod', 25, 'itlog  (25)', 500.00, 'completed', '2025-09-29 20:14:55', '2025-09-29 20:15:33'),
(20, 'Cora Feliciano', 16, 'HONGKONG', 'cod', 1, 'Crinkles (1)', 100.00, 'completed', '2025-09-29 20:53:40', '2025-09-29 20:54:18');

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
(13, 'Lasagna', 'Main Dishes', 900, 50, 'web1.jpg', 'normal'),
(14, 'Pastilan', 'Main Dishes', 126, 48, 'web2.jpg', 'normal'),
(15, 'Crinkles', 'Desserts', 100, 0, 'web5.jpg', 'low'),
(18, 'fishda', 'Main Dishes', 200, 0, '550884818_1066900732318507_1897651687058388133_n.jpg', 'low'),
(19, 'itlog ', 'Side Dishes', 20, 2, 'newprofile.png', 'low'),
(20, 'Sinigang', 'Main Dishes', 100, 50, 'amberjack-hamachi.jpg', 'normal'),
(21, 'Manga', 'Desserts', 20, 50, 'manga.jpg', 'normal');

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
(21, 15, 'stock_out', 1, 1, 0, '2025-09-29 20:57:36');

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
  `address` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`Id`, `FirstName`, `LastName`, `Email`, `username`, `Password`, `role_id`, `profile_picture`, `phone`, `address`) VALUES
(1, 'jeff', 'feliciano', 'jeje@gmail.com', NULL, '202cb962ac59075b964b07152d234b70', 4, NULL, NULL, NULL),
(2, 'jeff', 'feliciano', 'admins@gmail.com', NULL, '9ed6a5571323d50fd224af605b4ae077', 4, NULL, NULL, NULL),
(3, 'Jeff', 'Feliciano', 'anne@gmail.com', NULL, '7440da479f6533e79ab58fc153307c3b', 4, NULL, NULL, NULL),
(4, 'jeffer', 'ciano', 'jeffreidastory@gmail.com', 'jeff1', '$2y$10$XzrsMlnXr7urxqnDSoWpcO75IIDUfi240Dt21/YR/ZU', 4, NULL, NULL, NULL),
(5, 'nat', 'tnatna', 'ann@gmail.com', 'nath', '$2y$10$LLVlAHJr2N.lYkPhRZLmMudDYitHCWEQGd8.fj7MV3BYG.mZ/vED6', 4, NULL, NULL, NULL),
(6, 'eto', 'sample', 'janidscefchua19@gmail.com', 'sample', '$2y$10$oEo40L1TFIEsMWF6QW/Q1OHcgWx4l44mHs8hKBG0b2c7qfp.03WIm', 4, NULL, NULL, NULL),
(8, 'denisead', 'Wonwoo', 'den@gmail.com', 'Den', '$2y$10$1DRsavAY1t7yyllGWvQhVux4MUkrVQ2rkssAaH.FdWBAnm5cCZgsu', 4, '68d8135dc5de2_newprofile.png', '09455423535', 'Kaypian'),
(11, 'Grace', 'anta', 'admin01@gmail.com', 'admin01', '$2y$10$XB5FM5jBa46ka3hkDMOJIOwZllyfyxI8RD29TQ0OxwIhHn3oL6zyK', 2, '68db1344384ba_profile.png', '0935444234', 'Mexico'),
(12, 'jays', 'yajs', 'crew01@gmail.com', 'crew01', '$2y$10$YxcOsHCx6v29SmAb.HldyusuGj3xxQYMU6.XniZOfWQBEbcmHP4Mq', 3, NULL, NULL, NULL),
(13, 'adsyui', 'qweert', 'qwer@gmail.com', 'qwer', '$2y$10$bEKHHy1.XQICUjkin60dCOzhtZTJ.tgG6BnYHu9SNLDUgFCd9QaBa', 2, NULL, NULL, NULL),
(14, 'jaysss', 'yajss', 'jeffreidastoryasd@gmail.com', 'Jaysss', '$2y$10$yEZ.bnsQJDYnLksEYohkaew6dlrzFHfbfyuO/EvFnuYMae1y30JQe', 2, NULL, NULL, NULL),
(15, 'raven', 'pablo', 'rjven@gmail.com', 'rjven', '$2y$10$CQhYy33l3MhE/qJLQY5XZOpBNfa64sfy4NDIaE9HZSuD02pO.nHly', 4, '68d82d95ace6e_profile.jpg', '09455423535', 'kaypian'),
(16, 'Cora', 'Feliciano', 'Cora@gmail.com', 'Cora', '$2y$10$T7QAY1ErPnG2hdnR0uXUUuo.e.XX0K8mrtdJo6U0pCxStf7TAdyHm', 4, '68dab83d6c452_profile.jpg', '09654634523', 'HONGKONG');

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

--
-- Indexes for dumped tables
--

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
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `order_status_logs`
--
ALTER TABLE `order_status_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(200) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `stock_history`
--
ALTER TABLE `stock_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `super_admin_users`
--
ALTER TABLE `super_admin_users`
  MODIFY `super_admin_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `Id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `user_roles`
--
ALTER TABLE `user_roles`
  MODIFY `role_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

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
-- Constraints for table `stock_history`
--
ALTER TABLE `stock_history`
  ADD CONSTRAINT `stock_history_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `fk_user_role` FOREIGN KEY (`role_id`) REFERENCES `user_roles` (`role_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
