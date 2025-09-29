<?php
include "connect.php"; // Database connection

// Decode the incoming JSON data
$data = json_decode(file_get_contents("php://input"), true);

if (!empty($data)) {
    $name = mysqli_real_escape_string($conn, $data['name']);
    $address = mysqli_real_escape_string($conn, $data['address']);
    $method = mysqli_real_escape_string($conn, $data['method']);
    $totalProducts = intval($data['totalProducts']);
    $totalPrice = floatval($data['totalPrice']);
    $cartItems = $data['cartItems']; // Ensure this is an array

    // Insert order into the `orders` table
    $stmt = $conn->prepare("INSERT INTO orders (name, address, method, total_products, total_price) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssdi", $name, $address, $method, $totalProducts, $totalPrice);

    if ($stmt->execute()) {
        $orderId = $stmt->insert_id; // Get the inserted order ID

        // Insert each cart item into the `order_items` table
        $stmtItems = $conn->prepare("INSERT INTO order_items (order_id, product_name, quantity, price) VALUES (?, ?, ?, ?)");

        foreach ($cartItems as $item) {
            $productName = mysqli_real_escape_string($conn, $item['name']);
            $quantity = intval($item['quantity']);
            $price = floatval($item['price']);
            $stmtItems->bind_param("isid", $orderId, $productName, $quantity, $price);
            $stmtItems->execute();
        }
        $stmtItems->close();

        echo json_encode(["success" => true]);
    } else {
        echo json_encode(["success" => false, "error" => $stmt->error]);
    }

    $stmt->close();
    $conn->close();
} else {
    echo json_encode(["success" => false, "error" => "Invalid input"]);
}
?>