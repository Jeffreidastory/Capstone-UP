<?php
require_once "connect.php";
require_once "Session.php";

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'User not logged in']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
    exit();
}

// Validate reference number
$referenceNumber = trim($_POST['reference_number'] ?? '');
if (empty($referenceNumber)) {
    echo json_encode(['status' => 'error', 'message' => 'Reference number is required']);
    exit();
}

// Validate order ID
$orderId = intval($_POST['order_id'] ?? 0);
if ($orderId <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid order ID']);
    exit();
}

// Handle file upload
if (!isset($_FILES['payment_proof']) || $_FILES['payment_proof']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['status' => 'error', 'message' => 'Payment proof is required']);
    exit();
}

$file = $_FILES['payment_proof'];
$allowedTypes = ['image/jpeg', 'image/png'];
$maxSize = 2 * 1024 * 1024; // 2MB

// Validate file type
if (!in_array($file['type'], $allowedTypes)) {
    echo json_encode(['status' => 'error', 'message' => 'Only JPG and PNG files are allowed']);
    exit();
}

// Validate file size
if ($file['size'] > $maxSize) {
    echo json_encode(['status' => 'error', 'message' => 'File size must be less than 2MB']);
    exit();
}

// Create upload directory if it doesn't exist
$uploadDir = 'uploaded_img/payment_proofs/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

// Generate unique filename
$extension = pathinfo($file['name'], PATHINFO_EXTENSION);
$filename = uniqid('payment_') . '_' . time() . '.' . $extension;
$targetPath = $uploadDir . $filename;

try {
    // Begin transaction
    $conn->begin_transaction();

    // Debug information
    error_log("Processing GCash payment - Order ID: " . $orderId);
    error_log("Reference Number: " . $referenceNumber);
    error_log("File Info: " . print_r($file, true));

    // Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        throw new Exception('Failed to upload file: ' . error_get_last()['message']);
    }

    error_log("File uploaded successfully to: " . $targetPath);

    // Insert payment record into payment_records table
    $stmt = $conn->prepare("INSERT INTO payment_records (order_id, e_wallet, reference_number, payment_proof, payment_status, created_at, updated_at) 
                           VALUES (?, 'GCash', ?, ?, 'pending_verification', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)");
    
    if (!$stmt) {
        throw new Exception('Prepare failed: ' . $conn->error);
    }
    
    $stmt->bind_param("iss", $orderId, $referenceNumber, $filename);
    
    if (!$stmt->execute()) {
        throw new Exception('Failed to save payment record: ' . $stmt->error);
    }
    
    error_log("Payment record inserted successfully");

    // Update order status to awaiting_payment_verification
    $stmt = $conn->prepare("UPDATE orders SET status = 'awaiting_payment_verification' WHERE id = ?");
    $stmt->bind_param("i", $orderId);
    
    if (!$stmt->execute()) {
        throw new Exception('Failed to update order status');
    }

    // Commit transaction
    $conn->commit();

    echo json_encode([
        'status' => 'success',
        'message' => 'Payment proof uploaded successfully. Please wait for verification.',
        'payment_proof_url' => $targetPath
    ]);

} catch (Exception $e) {
    // Rollback on error
    $conn->rollback();
    
    // Delete uploaded file if it exists
    if (file_exists($targetPath)) {
        unlink($targetPath);
    }

    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>