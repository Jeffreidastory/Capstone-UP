<?php
session_start();
require_once "connect.php";

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please login to place an order']);
    exit();
}

// Get the raw POST data
$data = json_decode(file_get_contents('php://input'), true);

if (!$data || empty($data['items'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid order data']);
    exit();
}

// Get user information
$userId = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT FirstName, LastName, phone, address, verification_status FROM users WHERE Id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Check if user is verified
if ($user['verification_status'] !== 'approved') {
    echo json_encode(['success' => false, 'message' => 'Your account must be verified before placing orders. Please complete verification in your profile.']);
    exit();
}

if (!$user) {
    echo json_encode(['success' => false, 'message' => 'User information not found']);
    exit();
}

if (empty($user['address'])) {
    echo json_encode(['success' => false, 'message' => 'Please complete your delivery address in profile']);
    exit();
}

// Calculate totals
$totalProducts = 0;
$totalPrice = 0;
foreach ($data['items'] as $item) {
    $totalProducts += (int)$item['quantity'];
    $totalPrice += (float)$item['price'] * (int)$item['quantity'];
}

// Validate totals
if ($totalProducts <= 0 || $totalPrice <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid order quantities or prices']);
    exit();
}

// Start transaction
$conn->begin_transaction();

try {
    // Prepare item names for display
    $itemNames = array();
    foreach ($data['items'] as $item) {
        $itemNames[] = $item['name'] . ' (' . $item['quantity'] . ')';
    }
    $itemNameString = implode("\n", $itemNames);

    // Insert into orders table with user_id, address, payment method, and item names
    $stmt = $conn->prepare("INSERT INTO orders (user_id, name, address, method, total_products, total_price, status, order_time, item_name) VALUES (?, ?, ?, ?, ?, ?, 'pending', CURRENT_TIMESTAMP, ?)");
    $fullName = $user['FirstName'] . ' ' . $user['LastName'];
    $stmt->bind_param("isssids", $userId, $fullName, $user['address'], $data['paymentMethod'], $totalProducts, $totalPrice, $itemNameString);
    
    if (!$stmt->execute()) {
        throw new Exception("Failed to create order: " . $stmt->error);
    }
    
    $orderId = $stmt->insert_id;

    // We don't need to insert individual items since we store totals
    
    // Commit transaction
    $conn->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Order placed successfully!',
        'orderId' => $orderId
    ]);
    
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    error_log("Order Error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while processing your order. Please try again.'
    ]);
} finally {
    if (isset($stmt)) $stmt->close();
    if (isset($stmtItems)) $stmtItems->close();
    $conn->close();
}
?>
