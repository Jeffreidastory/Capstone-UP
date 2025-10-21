<?php
require_once "../connect.php";

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $product_id = $_GET['product_id'] ?? null;
    
    if (!$product_id) {
        echo json_encode(['success' => false, 'message' => 'Product ID is required']);
        exit;
    }

    $stmt = $conn->prepare("SELECT current_stock, unit_of_measurement FROM new_products WHERE id = ?");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        echo json_encode([
            'success' => true,
            'current_stock' => (int)$row['current_stock'],
            'uom' => $row['unit_of_measurement']
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Product not found']);
    }
    
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}

$conn->close();
?>