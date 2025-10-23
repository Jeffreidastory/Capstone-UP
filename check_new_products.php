<?php
require_once "connect.php";

$sql = "DESCRIBE new_products";
$result = $conn->query($sql);

if ($result) {
    echo "<pre>\nNew Products Table Structure:\n\n";
    while ($row = $result->fetch_assoc()) {
        echo $row['Field'] . " - " . $row['Type'] . "\n";
    }
    echo "</pre>";
} else {
    echo "Error: " . $conn->error;
}

$conn->close();
?>