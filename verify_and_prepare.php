<?php
session_start();
require_once "connect.php";

// Check if user is logged in and is a crew member
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 3) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['order_id']) || !isset($data['status'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Missing required data']);
    exit();
}

$order_id = intval($data['order_id']);
$status = $data['status'];

try {
    // Start transaction
    $conn->begin_transaction();

    if ($status === 'verified') {
        // Update payment records to mark as verified
        $stmt = $conn->prepare("UPDATE payment_records SET payment_status = 'verified', verified_at = NOW(), verified_by = ? WHERE order_id = ?");
        $stmt->bind_param("ii", $_SESSION['user_id'], $order_id);
        $stmt->execute();

        // Update order status to preparing
        $stmt = $conn->prepare("UPDATE orders SET status = 'preparing', updated_at = NOW() WHERE id = ?");
        $stmt->bind_param("i", $order_id);
        $stmt->execute();

    } else if ($status === 'rejected') {
        // Update payment records to mark as rejected
        $stmt = $conn->prepare("UPDATE payment_records SET payment_status = 'rejected', verified_at = NOW(), verified_by = ? WHERE order_id = ?");
        $stmt->bind_param("ii", $_SESSION['user_id'], $order_id);
        $stmt->execute();

        // Update order status to rejected
        $stmt = $conn->prepare("UPDATE orders SET status = 'cancelled', updated_at = NOW() WHERE id = ?");
        $stmt->bind_param("i", $order_id);
        $stmt->execute();
    } else {
        throw new Exception('Invalid status');
    }

    // Commit transaction
    $conn->commit();

    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'message' => $status === 'verified' ? 'Payment verified and order status updated to preparing' : 'Payment rejected and order cancelled',
        'redirect_url' => $status === 'verified' ? null : 'dashboard.php'
    ]);

} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Error processing request: ' . $e->getMessage()
    ]);
}

$conn->close();
?>