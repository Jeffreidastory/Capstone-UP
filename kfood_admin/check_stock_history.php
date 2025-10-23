<?php
require_once "../connect.php";

$sql = "DESCRIBE stock_history";
$result = $conn->query($sql);

if ($result) {
    echo "<pre>\nStock History Table Structure:\n\n";
    while ($row = $result->fetch_assoc()) {
        echo $row['Field'] . " - " . $row['Type'] . "\n";
    }
    echo "</pre>";
} else {
    echo "Error: " . $conn->error;
}

$conn->close();
?>