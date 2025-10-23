DROP TABLE IF EXISTS restocking;
CREATE TABLE restocking (
    id INT(11) NOT NULL AUTO_INCREMENT,
    product_id INT(11) NOT NULL,
    product_name VARCHAR(255) NOT NULL,
    current_stock DECIMAL(10,2) DEFAULT NULL,
    restock_quantity DECIMAL(10,2) NOT NULL,
    cost_per_unit DECIMAL(10,2) NOT NULL,
    final_price DECIMAL(10,2) NOT NULL,
    expiration_date DATE NOT NULL,
    restock_date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    status ENUM('pending', 'completed', 'cancelled') NOT NULL DEFAULT 'pending',
    PRIMARY KEY (id),
    KEY product_id (product_id),
    CONSTRAINT restocking_ibfk_1 FOREIGN KEY (product_id) REFERENCES new_products (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;