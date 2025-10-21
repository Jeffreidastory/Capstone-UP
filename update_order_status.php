<?php
session_start();
require_once "connect.php";

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit();
}

$order_id = $_POST['order_id'] ?? null;
$new_status = $_POST['status'] ?? null;

if (!$order_id || !$new_status) {
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit();
}

// Validate status transition
$valid_statuses = ['pending', 'preparing', 'out for delivery', 'completed'];
if (!in_array($new_status, $valid_statuses)) {
    echo json_encode(['success' => false, 'message' => 'Invalid status']);
    exit();
}

try {
    // First check if the order belongs to the user
    $check_query = "SELECT status FROM orders WHERE id = ? AND user_id = ?";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->bind_param("ii", $order_id, $_SESSION['user_id']);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    $order = $result->fetch_assoc();

    if (!$order) {
        throw new Exception("Order not found or unauthorized");
    }

    // Only allow completing orders that are "out for delivery"
    if ($new_status === 'completed' && $order['status'] !== 'out for delivery') {
        throw new Exception("Order must be out for delivery before marking as complete");
    }

    // Update the order status and track delivery time
    if ($new_status === 'out for delivery') {
        $update_query = "UPDATE orders SET status = ?, delivery_time = CURRENT_TIMESTAMP WHERE id = ? AND user_id = ?";
    } elseif ($new_status === 'completed') {
        $update_query = "UPDATE orders SET status = ?, completion_time = CURRENT_TIMESTAMP WHERE id = ? AND user_id = ?";
    } else {
        $update_query = "UPDATE orders SET status = ? WHERE id = ? AND user_id = ?";
    }
    $update_stmt = $conn->prepare($update_query);
    $update_stmt->bind_param("sii", $new_status, $order_id, $_SESSION['user_id']);
    
    if (!$update_stmt->execute()) {
        throw new Exception("Failed to update order status");
    }

    echo json_encode([
        'success' => true,
        'message' => 'Order status updated successfully',
        'new_status' => $new_status
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

$conn->close();