<?php
include "../connect.php";

if(isset($_GET['product_id'])) {
    $product_id = $_GET['product_id'];
    
    $query = "SELECT 
                date,
                type,
                quantity,
                previous_stock,
                new_stock 
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
        $history[] = $row;
    }
    
    echo json_encode($history);
} else {
    echo json_encode(['error' => 'No product ID provided']);
}
?>