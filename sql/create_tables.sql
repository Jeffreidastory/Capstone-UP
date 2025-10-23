-- Create database if not exists
CREATE DATABASE IF NOT EXISTS kfood_db;
USE kfood_db;

-- Create restocking table
CREATE TABLE IF NOT EXISTS `restocking` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `product_name` varchar(255) NOT NULL,
  `current_stock` decimal(10,2) NOT NULL,
  `restock_quantity` decimal(10,2) NOT NULL,
  `cost_per_unit` decimal(10,2) NOT NULL,
  `final_price` decimal(10,2) NOT NULL,
  `expiration_date` date NOT NULL,
  `restock_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `status` varchar(50) NOT NULL DEFAULT 'completed',
  PRIMARY KEY (`id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `restocking_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `new_products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create stock history table
CREATE TABLE IF NOT EXISTS `stock_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `quantity` decimal(10,2) NOT NULL,
  `type` varchar(50) NOT NULL,
  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `reason` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `stock_history_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `new_products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
