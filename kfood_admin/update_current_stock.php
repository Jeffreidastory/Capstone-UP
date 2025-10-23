<?php
require_once "../connect.php";

// Function to update current_stock in restocking table
function updateCurrentStock($conn) {
    try {
        $conn->begin_transaction();

        // Get all restocking records
        $query = "SELECT id, product_id, restock_quantity, restock_date, expiration_date 
                 FROM restocking 
                 ORDER BY restock_date ASC";
        $result = $conn->query($query);

        while ($row = $result->fetch_assoc()) {
            // Count deductions for this batch
            $deductions_query = "
                SELECT COUNT(*) as deduction_count
                FROM stock_history sh
                WHERE sh.product_id = ?
                AND sh.type = 'stock_out'
                AND sh.date >= ?
                AND sh.date < COALESCE(
                    (SELECT MIN(r2.restock_date)
                    FROM restocking r2
                    WHERE r2.product_id = ?
                    AND r2.restock_date > ?),
                    '2099-12-31'
                )";
            
            $stmt = $conn->prepare($deductions_query);
            $stmt->bind_param("isis", 
                $row['product_id'], 
                $row['restock_date'],
                $row['product_id'],
                $row['restock_date']
            );
            $stmt->execute();
            $deduction_result = $stmt->get_result();
            $deductions = $deduction_result->fetch_assoc();
            
            // Calculate new current stock
            $new_current_stock = $row['restock_quantity'] - $deductions['deduction_count'];
            
            // Update restocking table
            $update_query = "UPDATE restocking 
                           SET current_stock = ? 
                           WHERE id = ?";
            $stmt = $conn->prepare($update_query);
            $stmt->bind_param("di", $new_current_stock, $row['id']);
            $stmt->execute();
        }

        $conn->commit();
        return true;
    } catch (Exception $e) {
        $conn->rollback();
        error_log("Error updating current stock: " . $e->getMessage());
        return false;
    }
}

// Execute the update
$success = updateCurrentStock($conn);
echo json_encode(['success' => $success]);
?>