<?php
require_once "../connect.php";

function getExpiringStock($conn, $days_threshold = 30) {
    $query = "
        SELECT 
            np.id AS product_id,
            np.product_name,
            np.unit_measurement,
            sh.expiration_batch,
            SUM(CASE 
                WHEN sh.type = 'stock_in' THEN sh.quantity 
                WHEN sh.type = 'stock_out' THEN -sh.quantity 
                ELSE 0 
            END) as batch_quantity
        FROM stock_history sh
        JOIN new_products np ON sh.product_id = np.id
        WHERE sh.expiration_batch IS NOT NULL 
        AND sh.expiration_batch BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL ? DAY)
        GROUP BY np.id, np.product_name, np.unit_measurement, sh.expiration_batch
        HAVING batch_quantity > 0
        ORDER BY sh.expiration_batch ASC";

    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $days_threshold);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $expiring_items = array();
    while ($row = mysqli_fetch_assoc($result)) {
        $days_until_expiry = (strtotime($row['expiration_batch']) - time()) / (60 * 60 * 24);
        $row['days_until_expiry'] = round($days_until_expiry);
        $expiring_items[] = $row;
    }
    
    return $expiring_items;
}

// If this file is accessed directly via AJAX
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    header('Content-Type: application/json');
    $days = isset($_GET['days']) ? intval($_GET['days']) : 30;
    echo json_encode(getExpiringStock($conn, $days));
}
?>