<?php
session_start();

// Set notifications as read in session
$_SESSION['notifications_read'] = true;

// Return success response
header('Content-Type: application/json');
echo json_encode(['status' => 'success']);
?>