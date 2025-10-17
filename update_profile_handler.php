<?php
require_once "connect.php";
require_once "Session.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: loginpage.php");
    exit();
}

$userId = $_SESSION['user_id'];
$response = ['status' => 'error', 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $firstName = $_POST['firstName'] ?? '';
    $lastName = $_POST['lastName'] ?? '';
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';
    
    // Validate phone number
    if (!preg_match('/^[0-9]{11}$/', $phone)) {
        $_SESSION['message'] = "Invalid phone number format. Please enter 11 digits.";
        $_SESSION['messageType'] = 'error';
        header("Location: profile.php");
        exit();
    }
    
    // Handle file upload
    $profilePicture = null;
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = 'uploaded_img/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        $fileName = uniqid() . '_' . basename($_FILES['profile_picture']['name']);
        $uploadPath = $uploadDir . $fileName;
        
        if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $uploadPath)) {
            $profilePicture = $fileName;
        }
    }
    
    // Prepare SQL query
    $sql = "UPDATE users SET firstName=?, lastName=?, email=?, phone=?";
    $params = [$firstName, $lastName, $email, $phone];
    $types = "ssss";
    
    if ($profilePicture) {
        $sql .= ", profile_picture=?";
        $params[] = $profilePicture;
        $types .= "s";
    }
    
    $sql .= " WHERE id=?";
    $params[] = $userId;
    $types .= "i";
    
    // Execute update
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param($types, ...$params);
        if ($stmt->execute()) {
            $_SESSION['message'] = "Profile updated successfully!";
            $_SESSION['messageType'] = 'success';
        } else {
            $_SESSION['message'] = "Error updating profile: " . $stmt->error;
            $_SESSION['messageType'] = 'error';
        }
    } else {
        $_SESSION['message'] = "Error preparing statement: " . $conn->error;
        $_SESSION['messageType'] = 'error';
    }
}

header("Location: profile.php");
exit();
?>