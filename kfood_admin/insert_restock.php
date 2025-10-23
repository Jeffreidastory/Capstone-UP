<?php
// Log the values being inserted
error_log("Attempting to insert restock record with values: " . json_encode([
    'product_id' => $product_id,
    'product_name' => $product['product_name'],
    'current_stock' => $current_stock,
    'restock_quantity' => $restock_quantity,
    'cost_per_unit' => $cost_per_unit,
    'final_price' => $final_price,
    'expiration_date' => $expiration_date
]));

// First, try to insert without any triggers
$conn->query("SET @TRIGGER_CHECKS=0;");

// Insert restock record
$stmt = $conn->prepare("INSERT INTO restocking 
    (product_id, product_name, current_stock, restock_quantity, cost_per_unit, final_price, expiration_date) 
    VALUES (?, ?, ?, ?, ?, ?, ?)");

if (!$stmt) {
    error_log("Failed to prepare statement: " . $conn->error);
    throw new Exception("Database error while preparing statement");
}

if (!$stmt->bind_param("isiddds", 
    $product_id, 
    $product['product_name'],
    $current_stock,
    $restock_quantity,
    $cost_per_unit,
    $final_price,
    $expiration_date
)) {
    error_log("Failed to bind parameters: " . $stmt->error);
    throw new Exception("Database error while binding parameters");
}

if (!$stmt->execute()) {
    error_log("Failed to execute statement: " . $stmt->error);
    throw new Exception("Failed to insert restock record: " . $stmt->error);
}

// Re-enable triggers
$conn->query("SET @TRIGGER_CHECKS=1;");

// Log successful insertion
error_log("Successfully inserted restock record");
?>