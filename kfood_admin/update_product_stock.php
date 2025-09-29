<?php
include "../connect.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = $_POST['product_id'];
    $stock_change = intval($_POST['stock_change']);
    
    try {
        // Start transaction
        $conn->begin_transaction();
        
        // Get current stock
        $stmt = $conn->prepare("SELECT stock FROM products WHERE id = ?");
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $current_stock = $result->fetch_assoc()['stock'];
        
        // Calculate new stock
        $new_stock = $current_stock + $stock_change;
        
        // Prevent negative stock
        if ($new_stock < 0) {
            throw new Exception("Stock cannot be negative");
        }
        
        // Update product stock
        $stmt = $conn->prepare("UPDATE products SET stock = ? WHERE id = ?");
        $stmt->bind_param("ii", $new_stock, $product_id);
        $stmt->execute();
        
        // Log stock change in history
        $type = $stock_change > 0 ? 'stock_in' : 'stock_out';
        $quantity = abs($stock_change);
        
        $stmt = $conn->prepare("INSERT INTO stock_history (product_id, type, quantity, previous_stock, new_stock) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("isiii", $product_id, $type, $quantity, $current_stock, $new_stock);
        $stmt->execute();
        
        // Commit transaction
        $conn->commit();
        
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['error' => $e->getMessage()]);
    }
} else {
    echo json_encode(['error' => 'Invalid request method']);
}
?>