<?php
require_once "connect.php";

// Set timezone
date_default_timezone_set('Asia/Manila');

// Function to log messages
function logMessage($message, $type = 'INFO') {
    $logDir = __DIR__ . '/logs';
    $logFile = $logDir . '/auto_complete.log';
    
    // Create logs directory if it doesn't exist
    if (!file_exists($logDir)) {
        mkdir($logDir, 0777, true);
    }
    
    $timestamp = date('Y-m-d H:i:s');
    $formattedMessage = "[$timestamp] [$type] $message\n";
    file_put_contents($logFile, $formattedMessage, FILE_APPEND);
    
    // Also output to console
    echo $formattedMessage;
}

try {
    // Get orders that need to be completed
    $selectQuery = "SELECT id, order_id FROM orders 
                   WHERE status = 'out for delivery' 
                   AND delivery_time IS NOT NULL 
                   AND delivery_time < DATE_SUB(NOW(), INTERVAL 24 HOUR)";
    
    $result = $conn->query($selectQuery);
    
    if (!$result) {
        throw new Exception("Failed to query orders: " . $conn->error);
    }
    
    $ordersToComplete = $result->num_rows;
    
    if ($ordersToComplete > 0) {
        // Update orders
        $query = "UPDATE orders 
                  SET status = 'completed', 
                      completion_time = CURRENT_TIMESTAMP,
                      last_updated = CURRENT_TIMESTAMP 
                  WHERE status = 'out for delivery' 
                  AND delivery_time IS NOT NULL 
                  AND delivery_time < DATE_SUB(NOW(), INTERVAL 24 HOUR)";
        
        if ($conn->query($query)) {
            $affected = $conn->affected_rows;
            logMessage("Successfully auto-completed $affected orders");
            
            // Log individual order IDs
            while ($row = $result->fetch_assoc()) {
                logMessage("Completed order ID: " . $row['order_id'], 'DETAIL');
            }
        } else {
            throw new Exception("Failed to update orders: " . $conn->error);
        }
    } else {
        logMessage("No orders require auto-completion at this time");
    }
} catch (Exception $e) {
    logMessage($e->getMessage(), 'ERROR');
    exit(1);
} finally {
    if (isset($result)) {
        $result->close();
    }
    $conn->close();
}

exit(0);