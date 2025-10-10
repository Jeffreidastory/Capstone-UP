<?php
session_start();
require_once "connect.php";

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please login to place an order']);
    exit();
}

// Get the order data
$data = isset($_POST['orderData']) ? json_decode($_POST['orderData'], true) : null;

if (!$data || empty($data['items'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid order data']);
    exit();
}

// Get user information
$userId = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT FirstName, LastName, phone, verification_status FROM users WHERE Id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Get selected delivery address
if (!isset($data['deliveryAddressId'])) {
    echo json_encode(['success' => false, 'message' => 'Please select a delivery address']);
    exit();
}

$stmtAddr = $conn->prepare("SELECT CONCAT(street_address, ', ', barangay, ', ', city, ', ', province, ' ', zip_code) as full_address FROM delivery_addresses WHERE id = ? AND user_id = ?");
$stmtAddr->bind_param("ii", $data['deliveryAddressId'], $userId);
$stmtAddr->execute();
$addrResult = $stmtAddr->get_result();
$deliveryAddress = $addrResult->fetch_assoc();

if (!$deliveryAddress) {
    echo json_encode(['success' => false, 'message' => 'Invalid delivery address']);
    exit();
}

// Check if user is verified
if ($user['verification_status'] !== 'approved') {
    echo json_encode(['success' => false, 'message' => 'Your account must be verified before placing orders. Please complete verification in your profile.']);
    exit();
}

if (!$user) {
    echo json_encode(['success' => false, 'message' => 'User information not found']);
    exit();
}

if (!$deliveryAddress) {
    echo json_encode(['success' => false, 'message' => 'Please select a valid delivery address']);
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

    // Set initial status based on payment method
    $status = $data['paymentMethod'] === 'gcash' ? 'awaiting_payment_verification' : 'pending';

    // Insert into orders table with user_id, address, payment method, and item names
    $stmt = $conn->prepare("INSERT INTO orders (user_id, name, address, method, total_products, total_price, status, order_time, item_name, delivery_instructions) VALUES (?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP, ?, ?)");
    $fullName = $user['FirstName'] . ' ' . $user['LastName'];
    $deliveryInstructions = isset($data['deliveryInstructions']) ? $data['deliveryInstructions'] : '';
    $stmt->bind_param("isssidsss", $userId, $fullName, $deliveryAddress['full_address'], $data['paymentMethod'], $totalProducts, $totalPrice, $status, $itemNameString, $deliveryInstructions);
    
    if (!$stmt->execute()) {
        throw new Exception("Failed to create order: " . $stmt->error);
    }
    
    $orderId = $stmt->insert_id;

    // Handle GCash payment if selected
    if ($data['paymentMethod'] === 'gcash') {
        // Create uploads directory if it doesn't exist
        $uploadDir = 'uploaded_payments/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        // Handle file upload
        if (!isset($_FILES['payment_proof'])) {
            throw new Exception("Payment proof is required for GCash payments");
        }

        $paymentProof = $_FILES['payment_proof'];
        $fileExtension = pathinfo($paymentProof['name'], PATHINFO_EXTENSION);
        $fileName = 'payment_' . $orderId . '_' . time() . '.' . $fileExtension;
        $targetPath = $uploadDir . $fileName;

        if (!move_uploaded_file($paymentProof['tmp_name'], $targetPath)) {
            throw new Exception("Error uploading payment proof");
        }

        // Insert payment record
        $stmtPayment = $conn->prepare("INSERT INTO payment_records (order_id, payment_method, reference_number, payment_proof, payment_status, verification_notes) 
                                      VALUES (?, 'gcash', ?, ?, 'pending', '')");
        
        $refNumber = $_POST['reference_number'];
        $stmtPayment->bind_param("iss", $orderId, $refNumber, $fileName);

        if (!$stmtPayment->execute()) {
            throw new Exception("Error creating payment record: " . $stmtPayment->error);
        }
    }

    // Commit transaction
    $conn->commit();
    
    // Add ordered items and redirect URL to response
    echo json_encode([
        'success' => true,
        'message' => 'Order placed successfully!',
        'orderId' => $orderId,
        'orderedItems' => $data['items'],
        'redirectUrl' => 'index.php?order_complete=true'
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
