<?php
include "connect.php";

if (isset($_GET['id'])) {
    $orderId = $_GET['id'];

    // Make sure $orderId is sanitized
    $orderId = mysqli_real_escape_string($conn, $orderId); 

    // Check if the order is already completed
    $checkStatusQuery = "SELECT status FROM orders WHERE id='$orderId'";
    $result = mysqli_query($conn, $checkStatusQuery);
    if ($result) {
        $row = mysqli_fetch_assoc($result);
        if ($row['status'] === 'completed') {
            echo json_encode(['success' => false, 'error' => 'Order is already completed']);
            exit; // Stop further execution
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'Error checking order status']);
        exit;
    }

    // Update the order status to 'completed'
    $updateOrderQuery = "UPDATE orders SET status='completed' WHERE id='$orderId'";

    if (mysqli_query($conn, $updateOrderQuery)) {
        // Check how many rows were affected
        if (mysqli_affected_rows($conn) > 0) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false]);
        }
    } else {
        // Log the error
        echo json_encode(['success' => false, 'error' => 'Database error: ' . mysqli_error($conn)]);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid order ID']);
}
