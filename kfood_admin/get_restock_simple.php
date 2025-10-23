php
session_start();
require_once "../connect.php";

header('Content-Type: application/json');

try {
    // Simple query first to verify basic functionality
    $query = "SELECT 
        r.*,
        p.unit_measurement as uom,
        p.current_stock as total_product_stock
    FROM restocking r
    JOIN new_products p ON r.product_id = p.id
    ORDER BY r.restock_date DESC";
    
    $result = $conn->query($query);
    $records = [];
    
    while ($row = $result->fetch_assoc()) {
        $records[] = [
            'restock_date' => $row['restock_date'],
            'product_name' => $row['product_name'],
            'current_stock' => $row['current_stock'],
            'restock_quantity' => $row['restock_quantity'],
            'unit_measurement' => $row['uom'],
            'cost_per_unit' => $row['cost_per_unit'],
            'final_price' => $row['final_price'],
            'expiration_date' => $row['expiration_date']
        ];
    }
    
    echo json_encode(['success' => true, 'records' => $records]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conn->close();
?>