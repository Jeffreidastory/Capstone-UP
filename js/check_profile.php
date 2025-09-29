<?php
session_start();
require_once('../config/db.php');

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['isComplete' => false, 'message' => 'Not logged in']);
    exit;
}

$user_id = $_SESSION['user_id'];
$query = "SELECT full_name, contact_no, address FROM users WHERE user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Check if all required fields are filled
$isComplete = !empty($user['full_name']) && !empty($user['contact_no']) && !empty($user['address']);

echo json_encode([
    'isComplete' => $isComplete,
    'message' => $isComplete ? 'Profile complete' : 'Profile incomplete'
]);