<?php
session_start();
require_once "../connect.php";

if (!isset($_SESSION['crew_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $orderId = $_POST['order_id'];
    $status = $_POST['status']; // 'valid' or 'invalid'
    $notes = $_POST['notes'] ?? '';

    try {
        $conn->begin_transaction();

        // Update payment record
        $stmtPayment = $conn->prepare("UPDATE payment_records SET 
            payment_status = ?, 
            verification_notes = ?,
            verified_by = ?,
            verified_at = CURRENT_TIMESTAMP
            WHERE order_id = ?");
        
        $paymentStatus = $status === 'valid' ? 'verified' : 'invalid';
        $stmtPayment->bind_param("ssii", $paymentStatus, $notes, $_SESSION['crew_id'], $orderId);
        
        if (!$stmtPayment->execute()) {
            throw new Exception("Error updating payment record");
        }

        // Update order status
        $orderStatus = $status === 'valid' ? 'pending' : 'payment_invalid';
        $stmtOrder = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
        $stmtOrder->bind_param("si", $orderStatus, $orderId);
        
        if (!$stmtOrder->execute()) {
            throw new Exception("Error updating order status");
        }

        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Payment verification updated']);

    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}
?>