<?php
session_start();
include "../connect.php";

// Check if user is logged in and is a crew member
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 3) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $orderId = isset($_POST['orderId']) ? (int)$_POST['orderId'] : 0;
    $status = isset($_POST['status']) ? $_POST['status'] : '';

    if ($orderId && in_array($status, ['pending', 'preparing', 'out for delivery', 'delivered'])) {
        // Update order status
        $stmt = $conn->prepare("UPDATE orders SET status = ?, updated_at = NOW() WHERE id = ?");
        $stmt->bind_param("si", $status, $orderId);

        if ($stmt->execute()) {
            // Log the status change
            $crew_id = $_SESSION['user_id'];
            $log_stmt = $conn->prepare("INSERT INTO order_status_logs (order_id, status, crew_id) VALUES (?, ?, ?)");
            $log_stmt->bind_param("isi", $orderId, $status, $crew_id);
            $log_stmt->execute();

            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Database error']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>