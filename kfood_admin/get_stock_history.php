<?php
include "../connect.php";

if(isset($_GET['product_id'])) {
    $product_id = $_GET['product_id'];
    
    $query = "SELECT 
                date,
                type,
                expiration_batch,
                quantity,
                previous_stock,
                new_stock,
                cost_per_unit 
              FROM stock_history 
              WHERE product_id = ? 
              ORDER BY date DESC";
              
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $product_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $history = [];
    while($row = mysqli_fetch_assoc($result)) {
        $row['date'] = date('M d, Y g:i A', strtotime($row['date']));
        if ($row['expiration_batch']) {
            $row['expiration_batch'] = date('M d, Y', strtotime($row['expiration_batch']));
        }
        $history[] = $row;
    }
    
    echo json_encode($history);
} else {
    echo json_encode(['error' => 'No product ID provided']);
}
?>