<?php
require_once "connect.php";
require_once "Session.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: loginpage.php");
    exit();
}

$userId = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['form_submitted'])) {
    error_log("Processing profile update form submission");
    error_log("POST data: " . print_r($_POST, true));
    error_log("FILES data: " . print_r($_FILES, true));
    $updateFields = array();
    $updateParams = array();
    $updateTypes = "";

    // Get form data
    $firstName = $_POST['firstName'] ?? '';
    $lastName = $_POST['lastName'] ?? '';
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';
    
    // Get address fields if they are set
    $street_address = $_POST['street_address'] ?? '';
    $barangay = $_POST['barangay'] ?? '';
    $city = $_POST['city'] ?? '';
    $province = $_POST['province'] ?? '';
    $zip_code = $_POST['zip_code'] ?? '';

    // If address fields are provided, combine them
    if (!empty($street_address) && !empty($barangay) && !empty($city) && !empty($province) && !empty($zip_code)) {
        $fullAddress = implode(', ', [$street_address, $barangay, $city, $province, $zip_code]);
    }

    // Basic validation
    if (empty($firstName) || empty($lastName) || empty($email)) {
        $_SESSION['message'] = "Name and email fields are required!";
        $_SESSION['messageType'] = 'error';
        header("Location: profile.php");
        exit();
    }

    // Validate phone number if provided
    if (!empty($phone) && !preg_match('/^[0-9]{11}$/', $phone)) {
        $_SESSION['message'] = "Please enter a valid 11-digit phone number";
        $_SESSION['messageType'] = 'error';
        header("Location: profile.php");
        exit();
    }

    // Basic fields
    $updateFields[] = "firstName = ?";
    $updateFields[] = "lastName = ?";
    $updateFields[] = "email = ?";
    $updateFields[] = "phone = ?";
    $updateParams[] = $firstName;
    $updateParams[] = $lastName;
    $updateParams[] = $email;
    $updateParams[] = $phone;
    $updateTypes .= "ssss";

    // Add address if provided
    if (isset($fullAddress)) {
        $updateFields[] = "address = ?";
        $updateParams[] = $fullAddress;
        $updateTypes .= "s";
    }

    // Handle profile picture
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        $maxSize = 5 * 1024 * 1024; // 5MB
        
        if (!in_array($_FILES['profile_picture']['type'], $allowedTypes)) {
            $_SESSION['message'] = "Invalid file type. Please upload a JPG, PNG, or GIF file.";
            $_SESSION['messageType'] = 'error';
            header("Location: profile.php");
            exit();
        }
        
        if ($_FILES['profile_picture']['size'] > $maxSize) {
            $_SESSION['message'] = "File is too large. Maximum size is 5MB.";
            $_SESSION['messageType'] = 'error';
            header("Location: profile.php");
            exit();
        }
        
        $uploadDir = 'uploaded_img/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        // Generate a unique filename with original extension
        $extension = strtolower(pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION));
        $fileName = uniqid() . '_' . time() . '.' . $extension;
        
        if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $uploadDir . $fileName)) {
            $updateFields[] = "profile_picture = ?";
            $updateParams[] = $fileName;
            $updateTypes .= "s";
        } else {
            $_SESSION['message'] = "Failed to upload profile picture. Please try again.";
            $_SESSION['messageType'] = 'error';
            header("Location: profile.php");
            exit();
        }
    }

    // Add WHERE clause parameter
    $updateParams[] = $userId;
    $updateTypes .= "i";

    try {
        // Build and execute query
        $sql = "UPDATE users SET " . implode(", ", $updateFields) . " WHERE id = ?";
        error_log("SQL Query: " . $sql);
        error_log("Parameters: " . print_r($updateParams, true));
        error_log("Types: " . $updateTypes);
        
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Failed to prepare statement: " . $conn->error);
        }

        if (!$stmt->bind_param($updateTypes, ...$updateParams)) {
            throw new Exception("Failed to bind parameters: " . $stmt->error);
        }
        
        if ($stmt->execute()) {
            error_log("Profile update executed successfully");
            $_SESSION['message'] = "Profile updated successfully!";
            $_SESSION['messageType'] = 'success';
            $_SESSION['email'] = $email;
        } else {
            error_log("Failed to execute profile update: " . $stmt->error);
            throw new Exception("Failed to execute update: " . $stmt->error);
        }

    } catch (Exception $e) {
        $_SESSION['message'] = "Error: " . $e->getMessage();
        $_SESSION['messageType'] = 'error';
    }
}

header("Location: profile.php");
exit();
?>