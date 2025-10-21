<?php
session_start();
require_once "connect.php";

if (!isset($_SESSION['user_id']) || !isset($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$orderId = $_GET['id'];
$userId = $_SESSION['user_id'];

// Get order details
$stmt = $conn->prepare("
    SELECT o.*, u.FirstName, u.LastName, u.Email, u.phone, u.address 
    FROM orders o 
    JOIN users u ON o.user_id = u.Id 
    WHERE o.id = ? AND o.user_id = ?
");
$stmt->bind_param("ii", $orderId, $userId);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order) {
    header("Location: index.php");
    exit();
}

// Get order items
$stmt = $conn->prepare("
    SELECT oi.*, p.name, p.image 
    FROM order_items oi 
    JOIN products p ON oi.product_id = p.id 
    WHERE oi.order_id = ?
");
$stmt->bind_param("i", $orderId);
$stmt->execute();
$items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation - K-Food Delight</title>
    <link rel="stylesheet" href="css/modern-style.css">
    <link rel="stylesheet" href="css/navbar-modern.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .confirmation-container {
            max-width: 800px;
            margin: 40px auto;
            padding: 20px;
        }

        .success-message {
            text-align: center;
            margin-bottom: 40px;
        }

        .success-icon {
            color: #4CAF50;
            font-size: 64px;
            margin-bottom: 20px;
        }

        .order-details {
            background: white;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .section-title {
            font-size: 1.3rem;
            color: #333;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #eee;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            padding: 8px 0;
        }

        .detail-label {
            color: #666;
            font-weight: 500;
        }

        .detail-value {
            color: #333;
            font-weight: 600;
        }

        .items-list {
            margin-top: 20px;
        }

        .item {
            display: flex;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }

        .item img {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 8px;
            margin-right: 15px;
        }

        .item-details {
            flex: 1;
        }

        .item-name {
            font-weight: 500;
            margin-bottom: 5px;
        }

        .item-price {
            color: #666;
        }

        .payment-info {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 2px solid #eee;
        }

        .total-amount {
            font-size: 1.2rem;
            font-weight: 600;
            color: #4CAF50;
        }

        .actions {
            text-align: center;
            margin-top: 30px;
        }

        .action-btn {
            display: inline-block;
            padding: 12px 24px;
            background: #4CAF50;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            transition: background 0.3s;
        }

        .action-btn:hover {
            background: #45a049;
        }

        @media (max-width: 768px) {
            .confirmation-container {
                padding: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="confirmation-container">
        <div class="success-message">
            <i class="fas fa-check-circle success-icon"></i>
            <h1>Order Confirmed!</h1>
            <p>Thank you for your order. We'll start preparing it right away!</p>
            <p class="order-note" style="margin-top: 15px; color: #666; font-size: 0.9em;">
                <i class="fas fa-info-circle"></i> 
                Please remember to click "Confirm Order Received" after receiving your delivery. Orders will be automatically marked as completed after 24 hours from delivery.
            </p>
        </div>

        <div class="order-details">
            <h2 class="section-title">Order Details</h2>
            <div class="detail-row">
                <span class="detail-label">Order ID:</span>
                <span class="detail-value">#<?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Order Date:</span>
                <span class="detail-value"><?php echo date('F j, Y g:i A', strtotime($order['created_at'])); ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Status:</span>
                <span class="detail-value"><?php echo ucfirst($order['status']); ?></span>
            </div>
        </div>

        <div class="order-details">
            <h2 class="section-title">Delivery Information</h2>
            <div class="detail-row">
                <span class="detail-label">Name:</span>
                <span class="detail-value"><?php echo htmlspecialchars($order['FirstName'] . ' ' . $order['LastName']); ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Phone:</span>
                <span class="detail-value"><?php echo htmlspecialchars($order['phone']); ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Address:</span>
                <span class="detail-value"><?php echo htmlspecialchars($order['address']); ?></span>
            </div>
            <?php if (!empty($order['delivery_instructions'])): ?>
            <div class="detail-row">
                <span class="detail-label">Instructions:</span>
                <span class="detail-value"><?php echo htmlspecialchars($order['delivery_instructions']); ?></span>
            </div>
            <?php endif; ?>
        </div>

        <div class="order-details">
            <h2 class="section-title">Order Summary</h2>
            <div class="items-list">
                <?php foreach ($items as $item): ?>
                <div class="item">
                    <img src="uploaded_img/<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
                    <div class="item-details">
                        <div class="item-name"><?php echo htmlspecialchars($item['name']); ?></div>
                        <div class="item-price">
                            <?php echo $item['quantity']; ?> × ₱<?php echo number_format($item['price'], 2); ?>
                        </div>
                    </div>
                    <div class="item-total">
                        ₱<?php echo number_format($item['quantity'] * $item['price'], 2); ?>
                    </div>
                </div>
                <?php endforeach; ?>

                <div class="payment-info">
                    <div class="detail-row">
                        <span class="detail-label">Payment Method:</span>
                        <span class="detail-value"><?php echo $order['payment_method'] === 'cod' ? 'Cash on Delivery' : 'GCash'; ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Total Amount:</span>
                        <span class="detail-value total-amount">₱<?php echo number_format($order['total_amount'], 2); ?></span>
                    </div>
                </div>
            </div>
        </div>

        <div class="actions">
            <a href="index.php" class="action-btn">Back to Menu</a>
        </div>
    </div>
</body>
</html>