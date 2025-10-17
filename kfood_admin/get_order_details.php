<?php
require_once "../connect.php";

if (!isset($_GET['id'])) {
    echo json_encode(['error' => 'Order ID is required']);
    exit;
}

$orderId = (int)$_GET['id'];

$stmt = $conn->prepare("
    SELECT 
        o.*,
        u.profile_picture,
        o.delivery_instructions
    FROM orders o 
    LEFT JOIN users u ON u.FirstName || ' ' || u.LastName = o.name 
    WHERE o.id = ?
");

$stmt->bind_param("i", $orderId);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    echo json_encode($row);
} else {
    echo json_encode(['error' => 'Order not found']);
}

$stmt->close();
$conn->close();
?>