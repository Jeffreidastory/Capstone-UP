<?php
require_once "connect.php";
require_once "Session.php";

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit();
}

$userId = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'add':
            $street_address = filter_input(INPUT_POST, 'street_address', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
            $barangay = filter_input(INPUT_POST, 'barangay', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
            $city = filter_input(INPUT_POST, 'city', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
            $province = filter_input(INPUT_POST, 'province', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
            $zip_code = filter_input(INPUT_POST, 'zip_code', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
            $label = filter_input(INPUT_POST, 'label', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? 'Home';
            $is_default = isset($_POST['is_default']) ? 1 : 0;

            if ($is_default) {
                // Remove default status from other addresses
                $stmt = $conn->prepare("UPDATE delivery_addresses SET is_default = 0 WHERE user_id = ?");
                $stmt->bind_param("i", $userId);
                $stmt->execute();
            }

            $stmt = $conn->prepare("INSERT INTO delivery_addresses (user_id, street_address, barangay, city, province, zip_code, label, is_default) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("issssssi", $userId, $street_address, $barangay, $city, $province, $zip_code, $label, $is_default);
            
            if ($stmt->execute()) {
                echo json_encode(['status' => 'success', 'message' => 'Delivery address added successfully']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Failed to add delivery address']);
            }
            break;

        case 'delete':
            $address_id = filter_input(INPUT_POST, 'address_id', FILTER_SANITIZE_NUMBER_INT);
            
            $stmt = $conn->prepare("DELETE FROM delivery_addresses WHERE id = ? AND user_id = ?");
            $stmt->bind_param("ii", $address_id, $userId);
            
            if ($stmt->execute()) {
                echo json_encode(['status' => 'success', 'message' => 'Delivery address deleted']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Failed to delete delivery address']);
            }
            break;

        case 'set_default':
            $address_id = filter_input(INPUT_POST, 'address_id', FILTER_SANITIZE_NUMBER_INT);
            
            // Remove default status from all addresses
            $stmt = $conn->prepare("UPDATE delivery_addresses SET is_default = 0 WHERE user_id = ?");
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            
            // Set new default address
            $stmt = $conn->prepare("UPDATE delivery_addresses SET is_default = 1 WHERE id = ? AND user_id = ?");
            $stmt->bind_param("ii", $address_id, $userId);
            
            if ($stmt->execute()) {
                echo json_encode(['status' => 'success', 'message' => 'Default delivery address updated']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Failed to update default delivery address']);
            }
            break;

        default:
            echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
            break;
    }
} else if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Fetch user's delivery addresses
    $stmt = $conn->prepare("SELECT * FROM delivery_addresses WHERE user_id = ? ORDER BY is_default DESC, created_at DESC");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $addresses = $result->fetch_all(MYSQLI_ASSOC);
    
    echo json_encode(['status' => 'success', 'addresses' => $addresses]);
}
?>