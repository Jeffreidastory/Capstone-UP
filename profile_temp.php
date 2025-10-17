<?php
require_once "connect.php";
require_once "Session.php";

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: loginpage.php");
    exit();
}

$userId = $_SESSION['user_id'];

// Initialize message variables
$message = '';
$messageType = '';

// Check for flash messages
if (isset($_SESSION['message']) && isset($_SESSION['messageType'])) {
    $message = $_SESSION['message'];
    $messageType = $_SESSION['messageType'];
    unset($_SESSION['message']);
    unset($_SESSION['messageType']);
}

// Fetch current user data
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Initialize default values if not found
if (!$user) {
    $user = [
        'firstName' => '',
        'lastName' => '',
        'email' => '',
        'phone' => '',
        'address' => '',
        'profile_picture' => '',
        'verification_status' => 'none',
        'id_document' => '',
        'verification_date' => null
    ];
}

// Pass form handling to process_profile_update.php
?>