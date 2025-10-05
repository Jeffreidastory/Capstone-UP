<?php
session_start();
require_once 'connect.php';

require_once __DIR__ . '/phpmailer/src/Exception.php';
require_once __DIR__ . '/phpmailer/src/PHPMailer.php';
require_once __DIR__ . '/phpmailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['verify_otp'])) {
        $email = $_SESSION['temp_email'] ?? '';
        $otp = $_POST['otp'];

        // Verify OTP
        $stmt = $conn->prepare("SELECT * FROM otp_codes WHERE email = ? AND otp_code = ? AND verified = 0 AND expires_at > NOW() ORDER BY created_at DESC LIMIT 1");
        $stmt->bind_param("ss", $email, $otp);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            // Mark OTP as verified
            $updateStmt = $conn->prepare("UPDATE otp_codes SET verified = 1 WHERE email = ? AND otp_code = ?");
            $updateStmt->bind_param("ss", $email, $otp);
            $updateStmt->execute();

            // Get user data from session
            $userData = $_SESSION['temp_user_data'];
            
            // Insert new user with default role_id for customers (4)
            $stmt = $conn->prepare("INSERT INTO users (firstName, lastName, username, Email, Password, role_id) VALUES (?, ?, ?, ?, ?, 4)");
            $stmt->bind_param("sssss", 
                $userData['firstName'], 
                $userData['lastName'], 
                $userData['username'], 
                $userData['email'], 
                $userData['password']
            );
            
            if ($stmt->execute()) {
                // Clear temporary session data
                unset($_SESSION['temp_user_data']);
                unset($_SESSION['temp_email']);
                
                echo json_encode(['success' => true, 'message' => 'Registration successful!']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Registration failed. Please try again.']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid or expired OTP code.']);
        }
    } elseif (isset($_POST['resend_otp'])) {
        $email = $_SESSION['temp_email'] ?? '';
        
        if (!empty($email)) {
            // Generate new OTP
            $otp = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
            
            // Insert new OTP
            $stmt = $conn->prepare("INSERT INTO otp_codes (email, otp_code, expires_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 10 MINUTE))");
            $stmt->bind_param("ss", $email, $otp);
            
            if ($stmt->execute()) {
                // Send email with new OTP
                $mail = new PHPMailer\PHPMailer\PHPMailer(true);
                
                try {
                    $mail->isSMTP();
                    $mail->Host = 'smtp.gmail.com';
                    $mail->SMTPAuth = true;
                    $mail->Username = 'your-email@gmail.com'; // Your Gmail
                    $mail->Password = 'your-app-password'; // Your Gmail App Password
                    $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port = 587;

                    $mail->setFrom('your-email@gmail.com', 'K-Food Delight');
                    $mail->addAddress($email);

                    $mail->isHTML(true);
                    $mail->Subject = 'New Verification Code - K-Food Delight';
                    $mail->Body = "Your new verification code is: <b>{$otp}</b><br>This code will expire in 10 minutes.";

                    $mail->send();
                    echo json_encode(['success' => true, 'message' => 'New OTP code sent successfully!']);
                } catch (Exception $e) {
                    echo json_encode(['success' => false, 'message' => 'Failed to send new OTP code.']);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to generate new OTP code.']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid email address.']);
        }
    }
}
?>