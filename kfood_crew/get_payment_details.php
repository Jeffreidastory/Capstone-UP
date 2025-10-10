<?php
session_start();
require_once "../connect.php";

if (!isset($_SESSION['crew_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$orderId = $_GET['order_id'] ?? null;

if (!$orderId) {
    echo json_encode(['success' => false, 'message' => 'Order ID is required']);
    exit;
}

try {
    $stmt = $conn->prepare("
        SELECT p.*, o.total_price, o.status as order_status, 
               CONCAT(u.FirstName, ' ', u.LastName) as customer_name
        FROM payment_records p
        JOIN orders o ON p.order_id = o.id
        JOIN users u ON o.user_id = u.Id
        WHERE p.order_id = ?
    ");
    
    $stmt->bind_param("i", $orderId);
    $stmt->execute();
    $result = $stmt->get_result();
    $payment = $result->fetch_assoc();

    if ($payment) {
        echo json_encode(['success' => true, 'payment' => $payment]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Payment record not found']);
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>