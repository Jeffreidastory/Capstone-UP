<?php
require_once "connect.php";
require_once "Session.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: loginpage.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = $_SESSION['user_id'];
    $firstName = $_POST['firstName'] ?? '';
    $lastName = $_POST['lastName'] ?? '';
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';
    
    // Handle profile picture
    $profilePicture = null;
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = 'uploaded_img/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        $fileName = uniqid() . '_' . basename($_FILES['profile_picture']['name']);
        if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $uploadDir . $fileName)) {
            $profilePicture = $fileName;
        }
    }
    
    // Prepare the update query
    $sql = "UPDATE users SET firstName = ?, lastName = ?, email = ?, phone = ?";
    $params = [$firstName, $lastName, $email, $phone];
    $types = "ssss";
    
    if ($profilePicture) {
        $sql .= ", profile_picture = ?";
        $params[] = $profilePicture;
        $types .= "s";
    }
    
    $sql .= " WHERE id = ?";
    $params[] = $userId;
    $types .= "i";
    
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param($types, ...$params);
        if ($stmt->execute()) {
            $_SESSION['message'] = "Profile updated successfully!";
            $_SESSION['messageType'] = 'success';
            $_SESSION['email'] = $email;
        } else {
            $_SESSION['message'] = "Error updating profile";
            $_SESSION['messageType'] = 'error';
        }
    } else {
        $_SESSION['message'] = "Error preparing statement";
        $_SESSION['messageType'] = 'error';
    }
}

header("Location: profile.php");
exit();
?>