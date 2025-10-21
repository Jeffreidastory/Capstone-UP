<?php
$host = "localhost";
$user = "root";
$pass = "";
$db = "kfood_db";
$conn = new mysqli($host, $user, $pass, $db);
if($conn->connect_error){
    error_log("Failed to connect DB: " . $conn->connect_error);
    die(json_encode(['success' => false, 'message' => 'Database connection failed']));
}
?>