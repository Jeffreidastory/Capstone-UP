<?php
include "../connect.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = $_POST['productId'];
    $name = $_POST['productName'];
    $category = $_POST['productCategory'];
    $price = $_POST['productPrice'];
    $new_stock = $_POST['productStock'];
    
    try {
        // Start transaction
        $conn->begin_transaction();
        
        // Get current stock
        $stmt = $conn->prepare("SELECT stock FROM products WHERE id = ?");
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $current_stock = $result->fetch_assoc()['stock'];
        
        // If stock has changed, log it in stock_history
        if ($current_stock != $new_stock) {
            $type = $new_stock > $current_stock ? 'stock_in' : 'stock_out';
            $quantity = abs($new_stock - $current_stock);
            
            $stmt = $conn->prepare("INSERT INTO stock_history (product_id, type, quantity, previous_stock, new_stock) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("isiii", $product_id, $type, $quantity, $current_stock, $new_stock);
            $stmt->execute();
        }
        
        // Update product details
        if (!empty($_FILES['productImage']['name'])) {
            $image = $_FILES['productImage']['name'];
            $image_tmp_name = $_FILES['productImage']['tmp_name'];
            $image_folder = '../uploaded_img/'.$image;
            
            move_uploaded_file($image_tmp_name, $image_folder);
            
            $stmt = $conn->prepare("UPDATE products SET name = ?, category = ?, price = ?, stock = ?, image = ? WHERE id = ?");
            $stmt->bind_param("ssdisi", $name, $category, $price, $new_stock, $image, $product_id);
        } else {
            $stmt = $conn->prepare("UPDATE products SET name = ?, category = ?, price = ?, stock = ? WHERE id = ?");
            $stmt->bind_param("ssdii", $name, $category, $price, $new_stock, $product_id);
        }
        
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