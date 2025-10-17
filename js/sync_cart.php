<?php
session_start();
require_once "../connect.php";
require_once "../includes/cart_functions.php";

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit();
}

$userId = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$data || !isset($data['items'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid data']);
        exit();
    }
    
    $success = saveCartToDatabase($userId, $data['items']);
    echo json_encode(['success' => $success]);
} else {
    $cartItems = getCartFromDatabase($userId);
    echo json_encode(['success' => true, 'items' => $cartItems]);
}
?>