<?php
session_start();
require_once "connect.php";

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'error' => 'User not logged in'
    ]);
    exit;
}

$user_id = $_SESSION['user_id'];
error_log("Fetching orders for user_id: " . $user_id);

// Fetch all orders for the user
$query = "SELECT 
            o.id,
            o.order_time,
            o.total_price,
            o.total_products,
            o.status,
            o.item_name,
            o.method
          FROM orders o 
          WHERE o.user_id = ? 
          AND o.status != 'completed' 
          ORDER BY o.order_time DESC";

try {
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }

    $stmt->bind_param("i", $user_id);
    
    if (!$stmt->execute()) {
        throw new Exception("Execute failed: " . $stmt->error);
    }

    $result = $stmt->get_result();
    
    error_log("Number of orders found: " . $result->num_rows);

    $orders = [];
    while ($row = $result->fetch_assoc()) {
        $orders[] = [
            'id' => str_pad($row['id'], 5, '0', STR_PAD_LEFT),
            'order_time' => date('M d, Y h:i A', strtotime($row['order_time'])),
            'total_price' => number_format($row['total_price'], 2),
            'total_products' => (int)$row['total_products'],
            'status' => ucfirst($row['status']),
            'items' => $row['item_name'],
            'payment_method' => ucfirst($row['method'])
        ];
    }

    echo json_encode([
        'success' => true,
        'orders' => $orders,
        'hasOrders' => count($orders) > 0,
        'user_id' => $user_id
    ]);

} catch (Exception $e) {
    error_log("Error in get_recent_orders.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Failed to fetch orders: ' . $e->getMessage(),
        'user_id' => $user_id
    ]);
} finally {
    if (isset($stmt)) {
        $stmt->close();
    }
    if (isset($conn)) {
        $conn->close();
    }
}