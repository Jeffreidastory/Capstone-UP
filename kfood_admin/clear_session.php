<?php
session_start();

// Clear all notification-related session variables
unset($_SESSION['notifications_initialized']);
unset($_SESSION['last_notification_time']);
unset($_SESSION['welcome_shown']);
unset($_SESSION['message']);
unset($_SESSION['message_type']);
unset($_SESSION['temp_message']);
unset($_SESSION['login_timestamp']);

// Return success
echo json_encode(['status' => 'success']);
?>