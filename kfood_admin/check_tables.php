<?php
session_start();
require_once "../connect.php";

header('Content-Type: text/html');
echo "<pre>";

try {
    // 1. Check new_products table
    echo "=== NEW_PRODUCTS TABLE ===\n";
    $query = "SELECT * FROM new_products WHERE product_name = 'Pastil'";
    $result = $conn->query($query);
    $product = $result->fetch_assoc();
    print_r($product);

    // 2. Check restocking table
    echo "\n=== RESTOCKING TABLE ===\n";
    $query = "SELECT * FROM restocking WHERE product_id = ? ORDER BY restock_date DESC";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $product['id']);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        print_r($row);
    }

    // 3. Check stock_history table structure
    echo "\n=== STOCK_HISTORY TABLE STRUCTURE ===\n";
    $query = "SHOW CREATE TABLE stock_history";
    $result = $conn->query($query);
    $table = $result->fetch_assoc();
    print_r($table);

    // 4. Check stock_history records for Pastil with all columns
    echo "\n=== STOCK_HISTORY RECORDS FOR PASTIL ===\n";
    $query = "SELECT * FROM stock_history WHERE product_id = ? ORDER BY date DESC";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $product['id']);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        print_r($row);
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}

echo "</pre>";
$conn->close();
?>