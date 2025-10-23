<?php
function deductStockWithExpiration($conn, $product_id, $quantity_to_deduct) {
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Get current total stock
        $stmt = $conn->prepare("SELECT stock FROM new_products WHERE id = ? FOR UPDATE");
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $product = $result->fetch_assoc();
        
        if (!$product || $product['stock'] < $quantity_to_deduct) {
            throw new Exception("Insufficient stock");
        }

        // Get stock batches ordered by expiration date (FIFO)
        $stmt = $conn->prepare("
            SELECT id, quantity, expiration_batch, cost_per_unit 
            FROM stock_history 
            WHERE product_id = ? 
            AND type = 'stock_in' 
            AND quantity > 0 
            ORDER BY expiration_batch ASC, date ASC
        ");
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $remaining_to_deduct = $quantity_to_deduct;
        $total_deducted = 0;
        
        // Process each batch
        while ($batch = $result->fetch_assoc()) {
            if ($remaining_to_deduct <= 0) break;
            
            $deduct_from_batch = min($remaining_to_deduct, $batch['quantity']);
            $remaining_to_deduct -= $deduct_from_batch;
            $total_deducted += $deduct_from_batch;
            
            // Record stock out from this batch
            $stmt = $conn->prepare("
                INSERT INTO stock_history 
                    (product_id, type, expiration_batch, cost_per_unit, quantity, previous_stock, new_stock) 
                VALUES 
                    (?, 'stock_out', ?, ?, ?, ?, ?)
            ");
            $new_stock = $product['stock'] - $deduct_from_batch;
            $stmt->bind_param("isdddd", 
                $product_id, 
                $batch['expiration_batch'],
                $batch['cost_per_unit'],
                $deduct_from_batch,
                $product['stock'],
                $new_stock
            );
            $stmt->execute();
            
            // Update current stock
            $product['stock'] = $new_stock;
            
            // Update remaining batch quantity
            $stmt = $conn->prepare("
                UPDATE stock_history 
                SET quantity = quantity - ? 
                WHERE id = ?
            ");
            $stmt->bind_param("di", $deduct_from_batch, $batch['id']);
            $stmt->execute();
        }
        
        // Verify we deducted all requested quantity
        if ($remaining_to_deduct > 0) {
            throw new Exception("Insufficient stock across batches");
        }
        
        // Update product total stock
        $stmt = $conn->prepare("
            UPDATE new_products 
            SET stock = stock - ? 
            WHERE id = ?
        ");
        $stmt->bind_param("di", $total_deducted, $product_id);
        $stmt->execute();
        
        // Commit transaction
        $conn->commit();
        
        return true;
        
    } catch (Exception $e) {
        $conn->rollback();
        throw $e;
    }
}

function addStockWithExpiration($conn, $product_id, $quantity, $expiration_date, $cost_per_unit) {
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Get current stock
        $stmt = $conn->prepare("SELECT stock FROM new_products WHERE id = ? FOR UPDATE");
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $product = $result->fetch_assoc();
        
        if (!$product) {
            throw new Exception("Product not found");
        }
        
        $current_stock = $product['stock'];
        $new_stock = $current_stock + $quantity;
        
        // Record stock addition with expiration
        $stmt = $conn->prepare("
            INSERT INTO stock_history 
                (product_id, type, expiration_batch, cost_per_unit, quantity, previous_stock, new_stock) 
            VALUES 
                (?, 'stock_in', ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("isdddd", 
            $product_id,
            $expiration_date, 
            $cost_per_unit,
            $quantity,
            $current_stock,
            $new_stock
        );
        $stmt->execute();
        
        // Update product total stock
        $stmt = $conn->prepare("
            UPDATE new_products 
            SET stock = ? 
            WHERE id = ?
        ");
        $stmt->bind_param("di", $new_stock, $product_id);
        $stmt->execute();
        
        // Commit transaction
        $conn->commit();
        
        return true;
        
    } catch (Exception $e) {
        $conn->rollback();
        throw $e;
    }
}

// Helper function to get current stock with expiration details
function getCurrentStockWithExpiration($conn, $product_id) {
    $stmt = $conn->prepare("
        SELECT 
            SUM(CASE WHEN type = 'stock_in' THEN quantity 
                     WHEN type = 'stock_out' THEN -quantity 
                END) as batch_quantity,
            expiration_batch,
            cost_per_unit
        FROM stock_history 
        WHERE product_id = ?
        GROUP BY expiration_batch, cost_per_unit
        HAVING batch_quantity > 0
        ORDER BY expiration_batch ASC
    ");
    
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    return $stmt->get_result();
}
?>