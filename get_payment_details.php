<?php
session_start();
require_once "connect.php";

// Check if user is logged in and is a crew member
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 3) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Get order ID from query parameters
if (!isset($_GET['order_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Missing order ID']);
    exit();
}

$order_id = intval($_GET['order_id']);

// Get payment details including the screenshot URL
$stmt = $conn->prepare(
    "SELECT pr.reference_number, pr.screenshot_url, pr.payment_status, o.total_price 
     FROM payment_records pr
     JOIN orders o ON pr.order_id = o.id
     WHERE pr.order_id = ?"
);
$stmt->bind_param("i", $order_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Payment record not found']);
    exit();
}

$payment = $result->fetch_assoc();

// Return payment details
header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'reference_number' => $payment['reference_number'],
    'screenshot_url' => $payment['screenshot_url'],
    'payment_status' => $payment['payment_status'],
    'total_amount' => $payment['total_price']
]);

$conn->close();
?>