DROP TABLE IF EXISTS stock_history;

CREATE TABLE stock_history (
    id int(11) NOT NULL AUTO_INCREMENT,
    product_id int(11) NOT NULL,
    type enum('stock_in','stock_out') NOT NULL,
    quantity decimal(10,2) NOT NULL,
    previous_stock decimal(10,2) DEFAULT NULL,
    new_stock decimal(10,2) DEFAULT NULL,
    date timestamp NOT NULL DEFAULT current_timestamp(),
    PRIMARY KEY (id),
    KEY product_id (product_id),
    CONSTRAINT stock_history_ibfk_1 FOREIGN KEY (product_id) REFERENCES new_products (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;