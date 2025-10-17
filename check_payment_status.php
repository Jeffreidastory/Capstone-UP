<?php
session_start();
require_once "connect.php";

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authorized']);
    exit();
}

// Get order ID from request
$orderId = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;

if ($orderId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid order ID']);
    exit();
}

// Get payment status from database
$stmt = $conn->prepare("
    SELECT 
        pr.payment_status, 
        pr.verification_notes,
        pr.reference_number,
        pr.payment_proof,
        pr.created_at,
        pr.updated_at,
        o.total_price
    FROM payment_records pr 
    JOIN orders o ON pr.order_id = o.id 
    WHERE pr.order_id = ? AND o.user_id = ?
");

$stmt->bind_param("ii", $orderId, $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$payment = $result->fetch_assoc();

if (!$payment) {
    echo json_encode(['success' => false, 'message' => 'Payment record not found']);
    exit();
}

echo json_encode([
    'success' => true,
    'status' => $payment['payment_status'],
    'notes' => $payment['verification_notes'],
    'reference_number' => $payment['reference_number'],
    'proof_image' => $payment['payment_proof'],
    'amount' => $payment['total_price'],
    'created_at' => $payment['created_at'],
    'updated_at' => $payment['updated_at']
]);