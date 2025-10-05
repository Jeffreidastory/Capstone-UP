<?php
header('Content-Type: application/json');
session_start();
include "../connect.php";

// Check if user is logged in and has admin privileges
if (!isset($_SESSION['role_id']) || ($_SESSION['role_id'] != 1 && $_SESSION['role_id'] != 2)) {
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$status = isset($_GET['status']) ? $_GET['status'] : 'all';
$search = isset($_GET['search']) ? $_GET['search'] : '';

$query = "SELECT 
    o.*,
    u.profile_picture,
    u.FirstName,
    u.LastName,
    u.Id as user_id
FROM orders o 
LEFT JOIN users u ON o.name = CONCAT(u.FirstName, ' ', u.LastName)
WHERE 1=1";

if ($status !== 'all') {
    $query .= " AND o.order_status = ?";
}

if (!empty($search)) {
    $query .= " AND (o.id LIKE ? OR CONCAT(u.FirstName, ' ', u.LastName) LIKE ? OR o.delivery_address LIKE ?)";
}

$query .= " ORDER BY o.order_time DESC";

$types = "";
$params = array();

if ($status !== 'all') {
    $types .= "s";
    $params[] = ucfirst($status);
}

if (!empty($search)) {
    $searchTerm = "%$search%";
    $types .= "sss";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

$stmt = $conn->prepare($query);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();
$orders = array();

while ($row = $result->fetch_assoc()) {
    // Format the order data
    $order = array(
        'id' => $row['id'],
        'FirstName' => htmlspecialchars($row['FirstName']),
        'LastName' => htmlspecialchars($row['LastName']),
        'profile_picture' => $row['profile_picture'] ? htmlspecialchars($row['profile_picture']) : null,
        'delivery_address' => htmlspecialchars($row['delivery_address']),
        'payment_mode' => htmlspecialchars($row['payment_mode']),
        'item_name' => htmlspecialchars($row['item_name']),
        'total_products' => $row['total_products'],
        'total_price' => $row['total_price'],
        'order_status' => $row['order_status'],
        'order_time' => $row['order_time']
    );
    $orders[] = $order;
}

echo json_encode($orders);