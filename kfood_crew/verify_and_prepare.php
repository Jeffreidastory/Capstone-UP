<?php
session_start();
include "../connect.php";

// Check if user is logged in and is a crew member
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 3) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Get the POST data
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['order_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Order ID is required']);
    exit();
}

$orderId = intval($data['order_id']);
$action = isset($data['status']) ? $data['status'] : 'verified';

// Start transaction
$conn->begin_transaction();

try {
    if ($action === 'verified') {
        // Update payment status in payment_records
        $updatePayment = $conn->prepare("UPDATE payment_records SET payment_status = 'verified' WHERE order_id = ?");
        $updatePayment->bind_param("i", $orderId);
        $updatePayment->execute();

        // Update order status to preparing
        $updateOrder = $conn->prepare("UPDATE orders SET status = 'preparing' WHERE id = ? AND method = 'gcash' AND status = 'pending'");
        $updateOrder->bind_param("i", $orderId);
        $updateOrder->execute();

        if ($updateOrder->affected_rows > 0 && $updatePayment->affected_rows > 0) {
            // Get customer ID and order details
            $stmt = $conn->prepare("SELECT o.user_id, o.total_price, u.FirstName 
                                  FROM orders o 
                                  JOIN users u ON o.user_id = u.Id 
                                  WHERE o.id = ?");
            $stmt->bind_param("i", $orderId);
            $stmt->execute();
            $result = $stmt->get_result();
            $orderDetails = $result->fetch_assoc();

            // Commit the transaction immediately
            $conn->commit();
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true, 
                'message' => 'Payment verified and order status updated',
                'redirect_url' => '../order_confirmation.php?order_id=' . $orderId
            ]);
        } else {
            throw new Exception('Failed to update payment or order status');
        }
    } else if ($action === 'rejected') {
        // Update payment status to rejected
        $updatePayment = $conn->prepare("UPDATE payment_records SET payment_status = 'rejected' WHERE order_id = ?");
        $updatePayment->bind_param("i", $orderId);
        $updatePayment->execute();

        // Update order status to cancelled
        $updateOrder = $conn->prepare("UPDATE orders SET status = 'cancelled' WHERE id = ?");
        $updateOrder->bind_param("i", $orderId);
        $updateOrder->execute();

        // Get customer details for notification
        $stmt = $conn->prepare("SELECT o.user_id, u.FirstName 
                              FROM orders o 
                              JOIN users u ON o.user_id = u.Id 
                              WHERE o.id = ?");
        $stmt->bind_param("i", $orderId);
        $stmt->execute();
        $result = $stmt->get_result();
        $orderDetails = $result->fetch_assoc();

        // Commit the transaction immediately
        $conn->commit();
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true, 
            'message' => 'Payment rejected and order cancelled'
        ]);
    }
} catch (Exception $e) {
    $conn->rollback();
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conn->close();
?>