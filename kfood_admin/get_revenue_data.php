<?php
include "../connect.php";

header('Content-Type: application/json');

$period = isset($_GET['period']) ? $_GET['period'] : 'monthly';
$data = array(
    'labels' => array(),
    'revenue' => array()
);

switch ($period) {
    case 'daily':
        // Get last 30 days
        for ($i = 29; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-$i days"));
            $query = "SELECT COALESCE(SUM(total_price), 0) as revenue 
                     FROM orders 
                     WHERE status = 'completed' 
                     AND DATE(order_time) = '$date'";
            
            $result = mysqli_query($conn, $query);
            $row = mysqli_fetch_assoc($result);
            
            $data['revenue'][] = floatval($row['revenue']);
            $data['labels'][] = date('M d', strtotime($date));
        }
        break;

    case 'weekly':
        // Get last 12 weeks
        for ($i = 11; $i >= 0; $i--) {
            $start_date = date('Y-m-d', strtotime("-$i weeks"));
            $end_date = date('Y-m-d', strtotime("-$i weeks +6 days"));
            
            $query = "SELECT COALESCE(SUM(total_price), 0) as revenue 
                     FROM orders 
                     WHERE status = 'completed' 
                     AND DATE(order_time) BETWEEN '$start_date' AND '$end_date'";
            
            $result = mysqli_query($conn, $query);
            $row = mysqli_fetch_assoc($result);
            
            $data['revenue'][] = floatval($row['revenue']);
            $data['labels'][] = 'Week ' . (12 - $i);
        }
        break;

    case 'monthly':
    default:
        // Get last 12 months
        for ($i = 11; $i >= 0; $i--) {
            $start_date = date('Y-m-01', strtotime("-$i months"));
            $end_date = date('Y-m-t', strtotime("-$i months"));
            
            $query = "SELECT COALESCE(SUM(total_price), 0) as revenue 
                     FROM orders 
                     WHERE status = 'completed' 
                     AND order_time BETWEEN '$start_date' AND '$end_date'";
            
            $result = mysqli_query($conn, $query);
            $row = mysqli_fetch_assoc($result);
            
            $data['revenue'][] = floatval($row['revenue']);
            $data['labels'][] = date('M', strtotime($start_date));
        }
        break;
}

echo json_encode($data);
?>