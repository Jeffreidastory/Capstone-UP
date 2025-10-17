<?php
session_start();
require_once "../connect.php";

header('Content-Type: application/json');

function logDebug($message, $data = null) {
    $logMessage = '[' . date('Y-m-d H:i:s') . '] ' . $message;
    if ($data !== null) {
        $logMessage .= '\nData: ' . print_r($data, true);
    }
    $logMessage .= "\n----------------------------------------\n";
    file_put_contents(__DIR__ . '/debug_log.txt', $logMessage, FILE_APPEND);
}

// Ensure the user is logged in and is a crew member
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 3) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if the request is JSON
    $contentType = isset($_SERVER["CONTENT_TYPE"]) ? trim($_SERVER["CONTENT_TYPE"]) : '';
    
    if (stripos($contentType, 'application/json') !== false) {
        // Handle JSON input
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);
        $order_id = $data['order_id'] ?? null;
        $new_status = $data['status'] ?? null;
    } else {
        // Handle form data
        $order_id = $_POST['orderId'] ?? null;
        $new_status = $_POST['status'] ?? null;
    }
    
    if (!$order_id || !$new_status) {
        echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
        exit;
    }
    
    // Start transaction
    $conn->begin_transaction();

    try {
        // Get current order details
        $get_order = $conn->prepare("SELECT status, item_name, total_products FROM orders WHERE id = ?");
        $get_order->bind_param("i", $order_id);
        if (!$get_order->execute()) {
            throw new Exception('Failed to get order details');
        }
        
        $result = $get_order->get_result();
        $order = $result->fetch_assoc();

        if (!$order) {
            throw new Exception('Order not found');
        }

        // Check if status transition is valid
        $valid_transitions = [
            'pending' => ['preparing'],
            'preparing' => ['out for delivery'],
            'out for delivery' => ['completed']
        ];

        if (!isset($valid_transitions[$order['status']]) || 
            !in_array($new_status, $valid_transitions[$order['status']])) {
            throw new Exception('Invalid status transition from ' . $order['status'] . ' to ' . $new_status);
        }

        // Handle stock deduction first if marking as out for delivery
        if ($new_status === 'out for delivery') {
            logDebug("Starting stock deduction for Order #$order_id", [
                'items' => $order['item_name'],
                'total_products' => $order['total_products']
            ]);
            // Split the item_name string by newlines to get individual items
            $items = array_filter(explode("\n", $order['item_name']));
            
            foreach ($items as $item_entry) {
                // Extract item name and quantity using regex
                logDebug("Processing item: $item_entry");
                if (preg_match('/^(.+?)\s*\((\d+)\)$/', trim($item_entry), $matches)) {
                    $item_name = trim($matches[1]);
                    $quantity = intval($matches[2]);
                    
                    // Get product details
                    $get_product = $conn->prepare("SELECT id, stock FROM products WHERE name = ?");
                    $get_product->bind_param("s", $item_name);
                    
                    if (!$get_product->execute()) {
                        throw new Exception("Failed to check stock for: $item_name");
                    }
                    
                    $product_result = $get_product->get_result();
                    $product = $product_result->fetch_assoc();
                    
                    if (!$product) {
                        throw new Exception("Product not found: $item_name");
                    }
                    
                    // Verify and update stock
                    $current_stock = $product['stock'];
                    $new_stock = $current_stock - $quantity;
                    
                    if ($new_stock < 0) {
                        throw new Exception("Insufficient stock for $item_name (needed: $quantity, available: $current_stock)");
                    }
                    
                    // Update stock
                    logDebug("Updating stock for $item_name", [
                        'product_id' => $product['id'],
                        'current_stock' => $current_stock,
                        'quantity_to_deduct' => $quantity,
                        'new_stock' => $new_stock
                    ]);
                    $update_stock = $conn->prepare("UPDATE products SET stock = ? WHERE id = ?");
                    $update_stock->bind_param("ii", $new_stock, $product['id']);
                    
                    if (!$update_stock->execute()) {
                        throw new Exception("Failed to update stock for: $item_name");
                    }
                    
                    // Log stock change
                    logDebug("Recording stock history", [
                        'type' => 'stock_out',
                        'product_id' => $product['id'],
                        'quantity' => $quantity,
                        'previous_stock' => $current_stock,
                        'new_stock' => $new_stock
                    ]);
                    $type = 'stock_out';
                    $stock_history = $conn->prepare("INSERT INTO stock_history (product_id, type, quantity, previous_stock, new_stock) VALUES (?, ?, ?, ?, ?)");
                    if (!$stock_history) {
                        throw new Exception("Failed to prepare stock history query: " . $conn->error);
                    }
                    $stock_history->bind_param("isiii", $product['id'], $type, $quantity, $current_stock, $new_stock);
                    
                    if (!$stock_history->execute()) {
                        throw new Exception("Failed to log stock change for: $item_name. Error: " . $conn->error);
                    }
                } else {
                    throw new Exception("Invalid item format: $item_entry");
                }
            }
        }

        // Update order status
        $update_status = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
        $update_status->bind_param("si", $new_status, $order_id);
        
        if (!$update_status->execute()) {
            throw new Exception('Failed to update order status');
        }

        // Get updated order details for response
        $get_updated = $conn->prepare("SELECT id, order_time, total_price, total_products, status, item_name, method FROM orders WHERE id = ?");
        $get_updated->bind_param("i", $order_id);
        
        if (!$get_updated->execute()) {
            throw new Exception('Failed to get updated order details');
        }

        $updated_result = $get_updated->get_result();
        $updated_order = $updated_result->fetch_assoc();

        // Commit the transaction
        $conn->commit();
        logDebug("Successfully completed stock deduction for Order #$order_id");

        // Format the response
        $order_data = [
            'id' => str_pad($updated_order['id'], 4, '0', STR_PAD_LEFT),
            'order_time' => date('M d, Y h:i A', strtotime($updated_order['order_time'])),
            'total_price' => number_format($updated_order['total_price'], 2),
            'total_products' => (int)$updated_order['total_products'],
            'status' => ucfirst($updated_order['status']),
            'items' => $updated_order['item_name'],
            'payment_method' => ucfirst($updated_order['method'])
        ];
        
        echo json_encode([
            'success' => true,
            'message' => 'Order status updated successfully',
            'order' => $order_data
        ]);

    } catch (Exception $e) {
        logDebug("ERROR: " . $e->getMessage(), [
            'order_id' => $order_id,
            'status' => $new_status,
            'trace' => $e->getTraceAsString()
        ]);
        $conn->rollback();
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}

$conn->close();