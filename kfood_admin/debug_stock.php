<?php
session_start();
require_once "../connect.php";

header('Content-Type: text/html');
echo "<pre>";

try {
    // Get Pastil product ID first
    $query = "SELECT id FROM new_products WHERE product_name = 'Pastil'";
    $result = $conn->query($query);
    $product = $result->fetch_assoc();
    $pastil_id = $product['id'];

    // Check stock_history entries for Pastil
    $query = "SELECT 
        sh.id,
        sh.date,
        sh.type,
        sh.quantity,
        sh.expiration_batch,
        sh.previous_stock,
        sh.new_stock
    FROM stock_history sh
    WHERE sh.product_id = ?
    ORDER BY sh.date DESC, sh.id DESC";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $pastil_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    echo "Stock History for Pastil (ID: $pastil_id):\n\n";
    echo str_pad("ID", 5) . " | " . 
         str_pad("Date", 25) . " | " . 
         str_pad("Type", 10) . " | " . 
         str_pad("Quantity", 10) . " | " . 
         str_pad("Expiration", 20) . " | " . 
         str_pad("Prev Stock", 12) . " | " . 
         "New Stock\n";
    echo str_repeat("-", 100) . "\n";
    
    while ($row = $result->fetch_assoc()) {
        echo str_pad($row['id'], 5) . " | " .
             str_pad($row['date'], 25) . " | " .
             str_pad($row['type'], 10) . " | " .
             str_pad($row['quantity'], 10) . " | " .
             str_pad($row['expiration_batch'], 12) . " | " .
             str_pad($row['previous_stock'], 12) . " | " .
             $row['new_stock'] . "\n";
    }

    // Now check the current calculations
    $query = "SELECT 
        sh.expiration_batch,
        SUM(CASE WHEN sh.type = 'stock_in' THEN sh.quantity 
                 WHEN sh.type = 'stock_out' THEN -sh.quantity 
            END) as current_quantity
    FROM stock_history sh
    WHERE sh.product_id = ?
    GROUP BY sh.expiration_batch
    HAVING current_quantity > 0";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $pastil_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    echo "\n\nCurrent Stock by Expiration Date:\n\n";
    echo str_pad("Expiration Date", 15) . " | Current Quantity\n";
    echo str_repeat("-", 35) . "\n";
    
    while ($row = $result->fetch_assoc()) {
        echo str_pad($row['expiration_batch'], 15) . " | " .
             $row['current_quantity'] . "\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}

echo "</pre>";
$conn->close();
?>