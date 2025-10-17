<?php
session_start();
require_once "connect.php";

header('Content-Type: application/json');

function getUploadErrorMessage($code) {
    switch ($code) {
        case UPLOAD_ERR_INI_SIZE:
            return 'The uploaded file exceeds the upload_max_filesize directive in php.ini';
        case UPLOAD_ERR_FORM_SIZE:
            return 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form';
        case UPLOAD_ERR_PARTIAL:
            return 'The uploaded file was only partially uploaded';
        case UPLOAD_ERR_NO_FILE:
            return 'No file was uploaded';
        case UPLOAD_ERR_NO_TMP_DIR:
            return 'Missing a temporary folder';
        case UPLOAD_ERR_CANT_WRITE:
            return 'Failed to write file to disk';
        case UPLOAD_ERR_EXTENSION:
            return 'A PHP extension stopped the file upload';
        default:
            return 'Unknown upload error';
    }
}

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

    // Always set initial status to 'pending'
    $status = 'pending';

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
        // Debug information
        error_log("Processing GCash payment for order ID: " . $orderId);
        error_log("POST data: " . print_r($_POST, true));
        error_log("FILES data: " . print_r($_FILES, true));

        // Create uploads directory if it doesn't exist
        $uploadDir = 'uploaded_img/payment_proofs/';
        if (!is_dir($uploadDir)) {
            if (!mkdir($uploadDir, 0777, true)) {
                throw new Exception("Failed to create upload directory");
            }
            error_log("Created upload directory: " . $uploadDir);
        }

        // Handle file upload
        if (!isset($_FILES['payment_proof'])) {
            error_log("Payment proof not found in FILES array");
            throw new Exception("Payment proof is required for GCash payments");
        }

        $paymentProof = $_FILES['payment_proof'];
        
        // Validate file upload
        if ($paymentProof['error'] !== UPLOAD_ERR_OK) {
            error_log("File upload error: " . $paymentProof['error']);
            throw new Exception("Error uploading file: " . $this->getUploadErrorMessage($paymentProof['error']));
        }

        $fileExtension = pathinfo($paymentProof['name'], PATHINFO_EXTENSION);
        $fileName = 'payment_' . $orderId . '_' . time() . '.' . $fileExtension;
        $targetPath = $uploadDir . $fileName;
        
        error_log("Attempting to move uploaded file to: " . $targetPath);

        if (!move_uploaded_file($paymentProof['tmp_name'], $targetPath)) {
            $moveError = error_get_last();
            error_log("Failed to move uploaded file. Error: " . print_r($moveError, true));
            throw new Exception("Error uploading payment proof: " . ($moveError['message'] ?? 'Unknown error'));
        }
        error_log("Successfully moved uploaded file to: " . $targetPath);

        // Get reference number
        if (!isset($_POST['reference_number']) || empty($_POST['reference_number'])) {
            error_log("Reference number is missing from POST data");
            throw new Exception("GCash reference number is required");
        }
        $refNumber = $_POST['reference_number'];
        error_log("Reference number received: " . $refNumber);

        // Insert payment record
        $stmtPayment = $conn->prepare("INSERT INTO payment_records (
            order_id, 
            reference_number, 
            payment_proof, 
            payment_status
        ) VALUES (
            ?, 
            ?, 
            ?, 
            'pending_verification'
        )");
        
        if (!$stmtPayment) {
            error_log("Failed to prepare payment record statement: " . $conn->error);
            throw new Exception("Failed to prepare payment record statement");
        }
        
        error_log("Binding parameters - Order ID: $orderId, Reference: $refNumber, File: $fileName");
        if (!$stmtPayment->bind_param("iss", $orderId, $refNumber, $fileName)) {
            error_log("Failed to bind parameters: " . $stmtPayment->error);
            throw new Exception("Failed to bind payment record parameters");
        }

        if (!$stmtPayment->execute()) {
            error_log("Failed to execute payment record insert: " . $stmtPayment->error);
            // Delete uploaded file if database insert fails
            if (file_exists($targetPath)) {
                unlink($targetPath);
                error_log("Deleted uploaded file due to database insert failure");
            }
            throw new Exception("Error creating payment record: " . $stmtPayment->error);
        }
        error_log("Successfully inserted payment record");

        // Keep order status as 'pending' for crew verification
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
    
    // Log detailed error information
    error_log("Order Error Details:");
    error_log("Error Message: " . $e->getMessage());
    error_log("Order Data: " . print_r($data, true));
    error_log("POST Data: " . print_r($_POST, true));
    error_log("FILES Data: " . print_r($_FILES, true));
    error_log("Stack Trace: " . $e->getTraceAsString());
    
    // Return more specific error message to client
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'debug' => [
            'post' => $_POST,
            'files' => array_map(function($file) {
                return [
                    'name' => $file['name'],
                    'type' => $file['type'],
                    'size' => $file['size'],
                    'error' => $file['error']
                ];
            }, $_FILES)
        ]
    ]);
} finally {
    if (isset($stmt)) $stmt->close();
    if (isset($stmtItems)) $stmtItems->close();
    $conn->close();
}
?>
