<?php
session_start();
require_once "connect.php";

header('Content-Type: application/json');

// Check if user is logged in and is a crew member
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 3) {
    echo json_encode(['success' => false, 'message' => 'Not authorized']);
    exit();
}

// Get the request body
$data = json_decode(file_get_contents('php://input'), true);
if (!$data || !isset($data['orderId']) || !isset($data['status'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request data']);
    exit();
}

$orderId = (int)$data['orderId'];
$status = $data['status'];

// Validate status
if (!in_array($status, ['verified', 'invalid'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid status']);
    exit();
}

// Start transaction
$conn->begin_transaction();

try {
    // Update payment status in payment_records table
    $stmt = $conn->prepare("UPDATE payment_records SET payment_status = ? WHERE order_id = ?");
    $stmt->bind_param("si", $status, $orderId);
    
    if (!$stmt->execute()) {
        throw new Exception("Failed to update payment status");
    }

    // If payment is invalid, update order status to indicate payment issue
    if ($status === 'invalid') {
        $orderStmt = $conn->prepare("UPDATE orders SET status = 'payment_failed' WHERE id = ?");
        $orderStmt->bind_param("i", $orderId);
        
        if (!$orderStmt->execute()) {
            throw new Exception("Failed to update order status");
        }
    }

    // Commit transaction
    $conn->commit();
    
    echo json_encode([
        'success' => true,
        'message' => $status === 'verified' ? 'Payment verified successfully' : 'Payment marked as invalid'
    ]);
    
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} finally {
    $conn->close();
}