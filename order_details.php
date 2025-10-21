<?php
require_once "connect.php";
require_once "Session.php";

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: loginpage.php");
    exit();
}

// Get order ID from URL
$order_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Fetch order details
$stmt = $conn->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $order_id, $_SESSION['user_id']);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order) {
    header("Location: index.php");
    exit();
}

// Parse items from item_name field
$items = array();
$lines = explode("\n", $order['item_name']);
foreach ($lines as $line) {
    if (preg_match('/^(.+?)\s*\((\d+)\)$/', trim($line), $matches)) {
        $items[] = array(
            'name' => $matches[1],
            'quantity' => $matches[2],
            'price' => $order['total_price'] / count($lines) // Approximate price per item
        );
    }
}

// Handle order completion
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['order_id']) && isset($_POST['status'])) {
        $new_status = $_POST['status'];
        if ($order['status'] === 'out for delivery' && $new_status === 'completed') {
            $stmt = $conn->prepare("UPDATE orders SET status = 'completed' WHERE id = ? AND user_id = ?");
            $stmt->bind_param("ii", $order_id, $_SESSION['user_id']);
            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Order completed successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error updating order status']);
            }
            exit();
        }
    }
}

$statusOrder = ['pending', 'preparing', 'out for delivery', 'completed'];
$currentStatusIndex = array_search(strtolower($order['status']), $statusOrder);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Details - K-Food Delight</title>
    <link rel="stylesheet" href="css/modern-style.css">
    <link rel="stylesheet" href="css/order-details.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <script src="js/order-completion.js" defer></script>
</head>
<body>
    <a href="index.php" class="back-btn">
        <i class="fas fa-arrow-left"></i> Back to Home
    </a>
    
    <div class="order-details">
        <div class="order-card">
            <div class="order-header">
                <div class="order-title">
                    <h1>Order #<?php echo str_pad($order['id'], 5, '0', STR_PAD_LEFT); ?></h1>
                    <span class="order-badge <?php echo strtolower($order['status']); ?>">
                        <?php echo ucfirst($order['status']); ?>
                    </span>
                </div>
                <div class="order-date">
                    <i class="fas fa-calendar-alt"></i>
                    Placed on: <?php echo date('M d, Y h:i A', strtotime($order['order_time'])); ?>
                </div>
            </div>

        <div class="status-tracker">
            <div class="status-line">
                <div class="status-line-progress" style="width: <?php echo ($currentStatusIndex / 3) * 100; ?>%"></div>
            </div>
            <?php foreach ($statusOrder as $index => $status): ?>
                <div class="status-step">
                    <div class="status-point <?php echo $index <= $currentStatusIndex ? 'active' : ''; ?>">
                        <i class="fas <?php 
                            if ($status == 'pending') echo 'fa-clock';
                            elseif ($status == 'preparing') echo 'fa-utensils';
                            elseif ($status == 'out for delivery') echo 'fa-motorcycle';
                            else echo 'fa-check';
                        ?>"></i>
                    </div>
                    <div class="status-label <?php echo $index <= $currentStatusIndex ? 'active' : ''; ?>">
                        <span class="label-text">
                            <?php 
                                if ($status == 'out for delivery') {
                                    echo 'Out for Delivery';
                                } elseif ($status == 'completed') {
                                    echo 'Completed';
                                } else {
                                    echo ucfirst($status);
                                }
                            ?>
                        </span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if ($order['status'] == 'out for delivery'): ?>
        <div class="complete-order-section">
            <button class="complete-order-btn" onclick="confirmOrderCompletion(<?php echo $order['id']; ?>)">
                <i class="fas fa-check-circle"></i> Confirm Order Received
            </button>
            <p class="confirmation-note">Please click "Confirm Order Received" once you have received your order. The order will be automatically marked as completed after 24 hours.</p>
        </div>
        <?php endif; ?>

        <div class="order-items">
            <h2>Order Items</h2>
            <?php foreach ($items as $item): ?>
            <div class="order-item">
                <span><?php echo htmlspecialchars($item['name']); ?> x <?php echo $item['quantity']; ?></span>
                <span>₱<?php echo number_format($item['price'], 2); ?></span>
            </div>
            <?php endforeach; ?>

            <div class="order-item total-row">
                <strong>Total:</strong>
                <strong>₱<?php echo number_format($order['total_price'], 2); ?></strong>
            </div>
        </div>

        <div class="delivery-info">
            <h2>Delivery Information</h2>
            <div class="info-grid">
                <div class="info-item">
                    <label><i class="fas fa-map-marker-alt"></i> Delivery Address:</label>
                    <p><?php echo htmlspecialchars($order['address']); ?></p>
                </div>
                <div class="info-item">
                    <label><i class="fas fa-wallet"></i> Payment Method:</label>
                    <div class="method-badge">
                        <i class="fas <?php echo $order['method'] == 'cod' ? 'fa-money-bill-wave' : 'fa-credit-card'; ?>"></i>
                        <?php echo strtoupper($order['method']); ?>
                    </div>
                </div>
            </div>
        </div>

    

        <?php if ($order['status'] === 'Out for Delivery'): ?>
        <form method="POST" style="text-align: right;">
            <button type="submit" name="complete_order" class="complete-order-btn">
                Mark as Received
            </button>
        </form>
        <?php endif; ?>
    </div>

    <script>
    // Optional: Add animations for status changes
    document.addEventListener('DOMContentLoaded', function() {
        const statusPoints = document.querySelectorAll('.status-point');
        statusPoints.forEach((point, index) => {
            setTimeout(() => {
                if (point.classList.contains('active')) {
                    point.style.transform = 'scale(1.1)';
                    setTimeout(() => {
                        point.style.transform = 'scale(1)';
                    }, 200);
                }
            }, index * 300);
        });
    });
    </script>
</body>
</html>
