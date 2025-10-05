<?php
session_start();
header('Content-Type: application/json');

try {
    include "../connect.php";
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

// Enable error reporting for logs but not display
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Check if user is logged in and has admin privileges
if (!isset($_SESSION['role_id']) || ($_SESSION['role_id'] != 1 && $_SESSION['role_id'] != 2)) {
    error_log("Unauthorized verification attempt: " . print_r($_SESSION, true));
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Get and log POST data
$raw_data = file_get_contents('php://input');
error_log("Raw verification request data: " . $raw_data);
$data = json_decode($raw_data, true);

// Log decoded data
error_log("Decoded verification data: " . print_r($data, true));

if (!isset($data['user_id']) || !isset($data['status'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit();
}

$user_id = $data['user_id'];
$status = $data['status'];
$date = date('Y-m-d H:i:s');

// Update user verification status
$stmt = $conn->prepare("UPDATE users SET verification_status = ?, verification_date = ? WHERE id = ?");
$stmt->bind_param("ssi", $status, $date, $user_id);

try {
    if ($stmt->execute()) {
        // Get user details including name and document
        $user_details_stmt = $conn->prepare("SELECT FirstName, LastName, id_document, verification_status FROM users WHERE id = ?");
        $user_details_stmt->bind_param("i", $user_id);
        $user_details_stmt->execute();
        $user_details = $user_details_stmt->get_result()->fetch_assoc();
        
        // Generate full name and get document
        $user_full_name = trim($user_details['FirstName'] . ' ' . $user_details['LastName']);
        $id_doc = $user_details['id_document'];

        // For debugging
        error_log("User details - Name: " . $user_full_name . ", ID: " . $user_id . ", Document: " . $id_doc);
        
        $admin_name = $_SESSION['firstName'] . ' ' . $_SESSION['lastName'];
        $user_full_name = $user_details['full_name'];
        $id_document = $user_details['id_document'];

        // First check if there's an existing record for this user
        $check_stmt = $conn->prepare("SELECT id FROM verification_history WHERE user_id = ? ORDER BY created_at DESC LIMIT 1");
        $check_stmt->bind_param("i", $user_id);
        $check_stmt->execute();
        $existing_record = $check_stmt->get_result()->fetch_assoc();

        // Get customer details (the one being verified)
        $customer_stmt = $conn->prepare("SELECT CONCAT(FirstName, ' ', LastName, ' (', username, ')') as full_name, id_document FROM users WHERE id = ?");
        $customer_stmt->bind_param("i", $user_id);
        $customer_stmt->execute();
        $customer_details = $customer_stmt->get_result()->fetch_assoc();
        
        $customer_full_name = $customer_details['full_name'];
        $id_doc = $customer_details['id_document'];
        error_log("Customer details - Name: " . $customer_full_name . ", Document: " . $id_doc);

        // Check for existing record
        $check_stmt = $conn->prepare("SELECT id FROM verification_history WHERE user_id = ? ORDER BY created_at DESC LIMIT 1");
        $check_stmt->bind_param("i", $user_id);
        $check_stmt->execute();
        $existing_record = $check_stmt->get_result()->fetch_assoc();

        if ($existing_record) {
            // Update existing record with all fields
            $log_stmt = $conn->prepare("UPDATE verification_history SET status = ?, admin_name = ?, created_at = ?, id_document = ?, user_name = ? WHERE user_id = ?");
            $log_stmt->bind_param("sssssi", $status, $admin_name, $date, $id_doc, $customer_full_name, $user_id);
            error_log("Updating record - User: $customer_full_name, Status: $status");
        } else {
            // Insert new record with all fields
            $log_stmt = $conn->prepare("INSERT INTO verification_history (user_id, status, admin_name, created_at, user_name, id_document) VALUES (?, ?, ?, ?, ?, ?)");
            $log_stmt->bind_param("isssss", $user_id, $status, $admin_name, $date, $customer_full_name, $id_doc);
            error_log("Inserting new record - User: $customer_full_name, Status: $status");
        }
        $log_stmt->execute();

        // Get user details for response
        $user_stmt = $conn->prepare("SELECT FirstName, LastName FROM users WHERE id = ?");
        $user_stmt->bind_param("i", $user_id);
        $user_stmt->execute();
        $user_result = $user_stmt->get_result();
        $user_data = $user_result->fetch_assoc();

        echo json_encode([
            'success' => true,
            'message' => "Successfully {$status} verification for {$user_data['FirstName']} {$user_data['LastName']}",
            'user' => $user_data
        ]);
    } else {
        throw new Exception('Failed to update verification status');    
    }
} catch (Exception $e) {
    error_log("Verification Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}