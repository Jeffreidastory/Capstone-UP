<?php
// Query to get products with total stock
$inventory_query = "SELECT 
    p.*,
    COALESCE(SUM(r.restock_quantity), 0) as total_stock
FROM new_products p
LEFT JOIN restocking r ON p.id = r.product_id
GROUP BY p.id
ORDER BY p.product_name ASC";

$inventory_result = mysqli_query($conn, $inventory_query);

if ($inventory_result && mysqli_num_rows($inventory_result) > 0) {
    while($product = mysqli_fetch_assoc($inventory_result)) {
        $total_stock = $product['total_stock'];
        
        // Determine stock status
        if($total_stock <= 0) {
            $status_class = 'status-out';
            $stock_status = 'Out of Stock';
        } elseif($total_stock <= 10) {
            $status_class = 'status-critical';
            $stock_status = 'Critical';
        } else {
            $status_class = 'status-sufficient';
            $stock_status = 'Sufficient';
        }
        
        echo "<tr>";
        echo "<td><img src='../uploaded_img/" . htmlspecialchars($product['image']) . "' alt='" . htmlspecialchars($product['product_name']) . "'></td>";
        echo "<td>" . htmlspecialchars($product['product_name']) . "</td>";
        echo "<td>" . htmlspecialchars($product['category_name']) . "</td>";
        echo "<td>" . htmlspecialchars($total_stock) . "</td>";
        echo "<td>" . htmlspecialchars($product['unit_measurement']) . "</td>";
        echo "<td>₱" . number_format($product['markup_value'], 2) . "</td>";
        echo "<td><span class='status-badge " . $status_class . "'>" . $stock_status . "</span></td>";
        echo "</tr>";
    }
} else {
    echo "<tr><td colspan='7' class='no-records'>No products found</td></tr>";
}
?>