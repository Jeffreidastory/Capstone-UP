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
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;