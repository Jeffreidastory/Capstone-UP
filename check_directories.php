<?php
// This file ensures required directories exist with proper permissions

function ensureDirectoryExists($path) {
    if (!file_exists($path)) {
        if (!mkdir($path, 0777, true)) {
            error_log("Failed to create directory: " . $path);
            return false;
        }
        chmod($path, 0777); // Ensure proper permissions
    }
    return true;
}

// Ensure payment proof upload directory exists
$uploadDir = 'uploaded_img/payment_proofs/';
if (!ensureDirectoryExists($uploadDir)) {
    die("Failed to create required directories. Please check server permissions.");
}
?>