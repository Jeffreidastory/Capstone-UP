<?php
require_once "connect.php";
require_once "Session.php";

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'crew') {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
    exit();
}

$orderId = intval($_POST['order_id'] ?? 0);
$verificationStatus = $_POST['verification_status'] ?? '';
$notes = trim($_POST['notes'] ?? '');

if ($orderId <= 0 || !in_array($verificationStatus, ['verified', 'invalid'])) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid parameters']);
    exit();
}

try {
    $conn->begin_transaction();

    // Update payment record
    $stmt = $conn->prepare("UPDATE payment_records SET payment_status = ?, verification_notes = ? WHERE order_id = ?");
    $stmt->bind_param("ssi", $verificationStatus, $notes, $orderId);
    
    if (!$stmt->execute()) {
        throw new Exception('Failed to update payment status');
    }

    // Update order status
    $orderStatus = ($verificationStatus === 'verified') ? 'paid' : 'payment_failed';
    $stmt = $conn->prepare("UPDATE orders SET payment_status = ? WHERE id = ?");
    $stmt->bind_param("si", $orderStatus, $orderId);
    
    if (!$stmt->execute()) {
        throw new Exception('Failed to update order status');
    }

    $conn->commit();
    echo json_encode(['status' => 'success', 'message' => 'Payment verification updated successfully']);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>