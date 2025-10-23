<?php
include "../connect.php";

// When a product is restocked, update or insert into products table
if(isset($_POST['action']) && $_POST['action'] === 'restock_product') {
    try {
        $conn->begin_transaction();

        $product_id = $_POST['product_id'];
        $restock_quantity = $_POST['restock_quantity'];

        // Get product details from new_products
        $stmt = $conn->prepare("SELECT product_name, category_name, category_id, markup_value, image, unit_measurement 
                              FROM new_products WHERE id = ?");
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $product_result = $stmt->get_result();
        $product_data = $product_result->fetch_assoc();

        // Check if product exists in products table
        $check_stmt = $conn->prepare("SELECT stock FROM products WHERE name = ?");
        $check_stmt->bind_param("s", $product_data['product_name']);
        $check_stmt->execute();
        $existing_result = $check_stmt->get_result();

        if($existing_result->num_rows > 0) {
            // Update existing product
            $existing_product = $existing_result->fetch_assoc();
            $new_stock = $existing_product['stock'] + $restock_quantity;
            
            $update_stmt = $conn->prepare("UPDATE products 
                                         SET stock = ?, 
                                             price = ?,
                                             category = ?,
                                             category_id = ?,
                                             image = ?,
                                             uom = ?
                                         WHERE name = ?");
            $update_stmt->bind_param("ddsisss", 
                $new_stock,
                $product_data['markup_value'],
                $product_data['category_name'],
                $product_data['category_id'],
                $product_data['image'],
                $product_data['unit_measurement'],
                $product_data['product_name']
            );
            $update_stmt->execute();
        } else {
            // Insert new product
            $insert_stmt = $conn->prepare("INSERT INTO products 
                (name, category, category_id, price, stock, image, uom) 
                VALUES (?, ?, ?, ?, ?, ?, ?)");
            $insert_stmt->bind_param("ssiidss",
                $product_data['product_name'],
                $product_data['category_name'],
                $product_data['category_id'],
                $product_data['markup_value'],
                $restock_quantity,
                $product_data['image'],
                $product_data['unit_measurement']
            );
            $insert_stmt->execute();
        }

        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Inventory updated successfully']);

    } catch (Exception $e) {
        $conn->rollback();
        error_log("Error updating inventory: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Error updating inventory']);
    }
}
?>