<?php
require_once "../connect.php";

// Drop any existing triggers on stock_history
$sql1 = "DROP TRIGGER IF EXISTS before_stock_history_insert";
$sql2 = "DROP TRIGGER IF EXISTS after_stock_history_insert";
$sql3 = "DROP TRIGGER IF EXISTS before_stock_update";
$sql4 = "DROP TRIGGER IF EXISTS after_stock_update";

if ($conn->query($sql1) && $conn->query($sql2) && $conn->query($sql3) && $conn->query($sql4)) {
    echo "Old triggers removed successfully\n";
} else {
    echo "Error removing triggers: " . $conn->error . "\n";
}

// Create new stock_history table with correct structure
$sql5 = "DROP TABLE IF EXISTS stock_history";
$sql6 = "CREATE TABLE stock_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    type ENUM('stock_in', 'stock_out') NOT NULL,
    quantity INT NOT NULL,
    previous_stock INT NOT NULL,
    new_stock INT NOT NULL,
    date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id)
)";

if ($conn->query($sql5) && $conn->query($sql6)) {
    echo "Stock history table recreated successfully\n";
} else {
    echo "Error recreating table: " . $conn->error . "\n";
}

$conn->close();
?>