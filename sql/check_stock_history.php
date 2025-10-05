<?php
require_once "../connect.php";

// Show all triggers on stock_history table
$sql = "SHOW TRIGGERS WHERE `Table` = 'stock_history'";
$result = $conn->query($sql);

echo "Triggers on stock_history table:\n";
if ($result) {
    while ($row = $result->fetch_assoc()) {
        print_r($row);
    }
} else {
    echo "Error: " . $conn->error;
}

// Show the actual INSERT that's causing the error
$sql2 = "SELECT * FROM stock_history LIMIT 1";
$result2 = $conn->query($sql2);

echo "\n\nStock History Table Data Sample:\n";
if ($result2) {
    $columns = [];
    while ($field = $result2->fetch_field()) {
        $columns[] = $field->name;
    }
    echo "Columns: " . implode(", ", $columns) . "\n";
} else {
    echo "Error: " . $conn->error;
}

$conn->close();
?>