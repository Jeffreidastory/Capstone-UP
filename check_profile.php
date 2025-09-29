<?php
session_start();
require_once "../connect.php";

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['isComplete' => false, 'message' => 'Please log in to continue']);
    exit;
}

// Get user data
$userId = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT phone, address FROM users WHERE Id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Check if profile is complete
$isComplete = !empty($user['phone']) && !empty($user['address']);

echo json_encode([
    'isComplete' => $isComplete,
    'message' => $isComplete ? 'Profile complete' : 'Please complete your profile information (address and contact number) before proceeding to checkout.'
]);