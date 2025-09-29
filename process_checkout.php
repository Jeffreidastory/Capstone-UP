<?php
session_start();
require_once "connect.php";

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please log in to checkout']);
    exit;
}

// Get cart data from POST request
$cartData = json_decode(file_get_contents('php://input'), true);

if (!$cartData || !isset($cartData['items']) || empty($cartData['items'])) {
    echo json_encode(['success' => false, 'message' => 'Cart is empty']);
    exit;
}

try {
    // Start transaction
    $conn->begin_transaction();

    // Create order
    $userId = $_SESSION['user_id'];
    $total = $cartData['total'];
    $status = 'pending';
    
    $stmt = $conn->prepare("INSERT INTO orders (user_id, total_amount, status, created_at) VALUES (?, ?, ?, NOW())");
    $stmt->bind_param("ids", $userId, $total, $status);
    $stmt->execute();
    
    $orderId = $conn->insert_id;

    // Insert order items
    $stmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
    
    foreach ($cartData['items'] as $item) {
        $stmt->bind_param("iiid", $orderId, $item['id'], $item['quantity'], $item['price']);
        $stmt->execute();
    }

    // Commit transaction
    $conn->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Order placed successfully',
        'orderId' => $orderId
    ]);

} catch (Exception $e) {
    // Rollback on error
    $conn->rollback();
    error_log("Checkout error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while processing your order'
    ]);
}

$conn->close();