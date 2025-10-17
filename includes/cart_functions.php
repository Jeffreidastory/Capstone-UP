<?php
function saveCartToDatabase($userId, $cartItems) {
    global $conn;
    
    try {
        // Start transaction
        $conn->begin_transaction();

        // First, delete existing cart items for this user
        $deleteStmt = $conn->prepare("DELETE FROM cart_items WHERE user_id = ?");
        $deleteStmt->bind_param("i", $userId);
        $deleteStmt->execute();

        // Then insert new cart items
        $insertStmt = $conn->prepare("INSERT INTO cart_items (user_id, product_id, quantity, added_at) VALUES (?, ?, ?, NOW())");
        
        foreach ($cartItems as $item) {
            $insertStmt->bind_param("iii", $userId, $item['id'], $item['quantity']);
            $insertStmt->execute();
        }

        // Commit transaction
        $conn->commit();
        return true;
    } catch (Exception $e) {
        // Rollback on error
        $conn->rollback();
        error_log("Error saving cart: " . $e->getMessage());
        return false;
    }
}

function getCartFromDatabase($userId) {
    global $conn;
    
    try {
        $stmt = $conn->prepare("
            SELECT ci.product_id as id, p.name, p.price, p.image, ci.quantity
            FROM cart_items ci
            JOIN products p ON ci.product_id = p.id
            WHERE ci.user_id = ?
        ");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $cartItems = [];
        while ($row = $result->fetch_assoc()) {
            $cartItems[] = $row;
        }
        
        return $cartItems;
    } catch (Exception $e) {
        error_log("Error getting cart: " . $e->getMessage());
        return [];
    }
}
?>