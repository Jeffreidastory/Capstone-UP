<?php
session_start();
require_once "connect.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: loginpage.php");
    exit();
}

$userId = $_SESSION['user_id'];
$response = ['success' => false, 'message' => ''];

if (isset($_POST['submit_verification']) && isset($_FILES['id_document'])) {
    $file = $_FILES['id_document'];
    
    // Validate file
    $allowedTypes = ['image/jpeg', 'image/png', 'application/pdf'];
    $maxSize = 5 * 1024 * 1024; // 5MB
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $response['message'] = "Error uploading file. Please try again.";
    } elseif (!in_array($file['type'], $allowedTypes)) {
        $response['message'] = "Invalid file type. Please upload JPG, PNG, or PDF.";
    } elseif ($file['size'] > $maxSize) {
        $response['message'] = "File is too large. Maximum size is 5MB.";
    } else {
        // Create upload directory if it doesn't exist
        $uploadDir = 'uploaded_img';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        // Generate unique filename
        $fileName = uniqid() . '_' . basename($file['name']);
        $uploadPath = $uploadDir . '/' . $fileName;

        if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
            // Update database
            $stmt = $conn->prepare("UPDATE users SET id_document = ?, verification_status = 'pending', verification_date = NOW() WHERE id = ?");
            $stmt->bind_param("si", $fileName, $userId);

            if ($stmt->execute()) {
                // Insert into verification history
                $historyStmt = $conn->prepare("INSERT INTO verification_history (user_id, status) VALUES (?, 'pending')");
                $historyStmt->bind_param("i", $userId);
                $historyStmt->execute();

                $response['success'] = true;
                $response['message'] = "Document uploaded successfully! Your verification is pending review.";
            } else {
                $response['message'] = "Error updating database. Please try again.";
                // Clean up uploaded file if database update fails
                unlink($uploadPath);
            }
        } else {
            $response['message'] = "Error saving file. Please try again.";
        }
    }
}

// Return JSON response if it's an AJAX request
if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}

// Otherwise, redirect back to profile with message
$_SESSION['verification_message'] = $response['message'];
$_SESSION['verification_status'] = $response['success'] ? 'success' : 'error';
header("Location: profile.php#verification");
exit();
?>