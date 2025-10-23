<?php
session_start();
require_once "../connect.php";

header('Content-Type: application/json');

// Check if user is logged in and has appropriate permissions
if (!isset($_SESSION['role_id']) || ($_SESSION['role_id'] != 1 && $_SESSION['role_id'] != 2)) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

try {
    // Query to get restock records with product details
    $query = "SELECT r.*, p.unit_measurement AS uom 
              FROM restocking r 
              INNER JOIN new_products p ON r.product_id = p.id 
              ORDER BY r.restock_date DESC 
              LIMIT 50"; // Limit to last 50 records for performance
    
    $result = $conn->query($query);
    $records = [];
    
    while ($row = $result->fetch_assoc()) {
        $records[] = [
            'restock_date' => $row['restock_date'],
            'product_name' => $row['product_name'],
            'restock_quantity' => $row['restock_quantity'],
            'unit_measurement' => $row['uom'],
            'cost_per_unit' => $row['cost_per_unit'],
            'final_price' => $row['final_price'],
            'expiration_date' => $row['expiration_date'],
            'status' => $row['status']
        ];
    }
    
    echo json_encode($records);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}

$conn->close();
?>