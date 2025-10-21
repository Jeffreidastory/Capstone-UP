<?php
require_once "connect.php";

// Set timezone
date_default_timezone_set('Asia/Manila');

// Function to check and update order status
function checkAndUpdateOrders() {
    global $conn;
    
    try {
        // Find orders that are "out for delivery" for more than 24 hours
        $query = "UPDATE orders 
                SET status = 'completed', 
                    completion_time = CURRENT_TIMESTAMP,
                    last_updated = CURRENT_TIMESTAMP
                WHERE status = 'out for delivery' 
                AND delivery_time IS NOT NULL 
                AND delivery_time < DATE_SUB(NOW(), INTERVAL 24 HOUR)";
        
        if ($conn->query($query)) {
            $affected = $conn->affected_rows;
            logAction("Auto-completed $affected orders");
            return $affected;
        } else {
            throw new Exception("Failed to update orders: " . $conn->error);
        }
    } catch (Exception $e) {
        logAction("Error: " . $e->getMessage());
        return false;
    }
}

// Function to log actions
function logAction($message) {
    $logFile = __DIR__ . '/logs/order_updates.log';
    $logDir = dirname($logFile);
    
    // Create logs directory if it doesn't exist
    if (!file_exists($logDir)) {
        mkdir($logDir, 0777, true);
    }
    
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] $message\n";
    file_put_contents($logFile, $logMessage, FILE_APPEND);
}

// Add last_updated column if it doesn't exist
$result = $conn->query("SHOW COLUMNS FROM orders LIKE 'last_updated'");
if ($result->num_rows == 0) {
    $sql = "ALTER TABLE orders 
            ADD COLUMN last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP 
            ON UPDATE CURRENT_TIMESTAMP";
    if ($conn->query($sql)) {
        logAction("Added last_updated column to orders table");
    } else {
        logAction("Error adding last_updated column: " . $conn->error);
    }
}

// Run the check
$updatedOrders = checkAndUpdateOrders();
if ($updatedOrders !== false) {
    echo "Successfully checked orders. Updated $updatedOrders orders.\n";
} else {
    echo "Error checking orders. Check the log file for details.\n";
}

$conn->close();
?>
