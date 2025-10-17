<?php
session_start();
require_once "../connect.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 3) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$orderId = $_GET['order_id'] ?? null;

if (!$orderId) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Order ID is required']);
    exit;
}

try {
    $stmt = $conn->prepare("
        SELECT p.reference_number, p.payment_proof, p.payment_status, 
               o.total_price, o.status as order_status,
               CONCAT(u.FirstName, ' ', u.LastName) as customer_name
        FROM payment_records p
        JOIN orders o ON p.order_id = o.id
        JOIN users u ON o.user_id = u.Id
        WHERE p.order_id = ? AND o.method = 'gcash'
    ");
    
    $stmt->bind_param("i", $orderId);
    $stmt->execute();
    $result = $stmt->get_result();
    $payment = $result->fetch_assoc();

    if ($payment) {
        $response = [
            'success' => true,
            'reference_number' => $payment['reference_number'],
            'screenshot_url' => '../uploaded_img/payment_proofs/' . $payment['payment_proof'],
            'payment_status' => $payment['payment_status'],
            'total_amount' => $payment['total_price'],
            'customer_name' => $payment['customer_name'],
            'order_status' => $payment['order_status']
        ];
    } else {
        $response = ['success' => false, 'message' => 'Payment record not found'];
    }

    header('Content-Type: application/json');
    echo json_encode($response);

} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>