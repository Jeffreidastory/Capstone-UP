<?php
session_start();
require_once "connect.php";

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: loginpage.php");
    exit();
}

$userId = $_SESSION['user_id'];

// Fetch user's orders
$stmt = $conn->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY order_time DESC");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order History - K-Food Delight</title>
    <link rel="stylesheet" href="css/modern-style.css">
    <link rel="stylesheet" href="css/navbar-modern.css">
    <link rel="stylesheet" href="css/order-history.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .order-history-container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
        }

        .order-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            transition: transform 0.2s;
        }

        .order-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }

        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }

        .order-date {
            color: #666;
            font-size: 0.9rem;
        }

        .order-id {
            font-weight: 600;
            color: #333;
        }

        .order-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 15px;
        }

        .detail-group {
            display: flex;
            flex-direction: column;
        }

        .detail-label {
            font-size: 0.85rem;
            color: #666;
            margin-bottom: 4px;
        }

        .detail-value {
            font-weight: 500;
            color: #333;
        }

        .order-items {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-top: 15px;
        }

        .item-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
            border-bottom: 1px solid #eee;
        }

        .item-row:last-child {
            border-bottom: none;
        }

        .item-name {
            flex: 1;
        }

        .item-quantity {
            color: #666;
            margin: 0 15px;
        }

        .item-price {
            font-weight: 500;
            color: #333;
        }

        .order-total {
            text-align: right;
            margin-top: 15px;
            font-weight: 600;
            color: #ff6b6b;
        }

        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
            text-transform: capitalize;
        }

        .status-pending {
            background: #fff3cd;
            color: #856404;
        }

        .status-processing {
            background: #cce5ff;
            color: #004085;
        }

        .status-completed {
            background: #d4edda;
            color: #155724;
        }

        .status-cancelled {
            background: #f8d7da;
            color: #721c24;
        }

        .status-outfordelivery {
            background: #e0f3ff;
            color: #0066cc;
        }

        .page-header {
            background: white;
            padding: 20px;
            margin-bottom: 30px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            position: relative;
        }

        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: white;
            text-decoration: none;
            font-size: 0.95rem;
            font-weight: 500;
            padding: 8px 16px;
            border-radius: 20px;
            background-color: #ff6b6b;
            transition: all 0.3s ease;
            border: none;
            position: absolute;
            left: 20px;
            top: 50%;
            transform: translateY(-50%);
        }

        .back-btn:hover {
            background-color: #ff5252;
            transform: translateY(-50%) translateX(-2px);
        }

        .back-btn i {
            font-size: 0.9em;
            transition: transform 0.3s ease;
        }

        .back-btn:hover i {
            transform: translateX(-2px);
        }

        .page-title {
            color: #333;
            font-size: 1.8rem;
            margin: 0;
            text-align: center;
        }

        @media (max-width: 768px) {
            .order-history-container {
                padding: 10px;
            }

            .order-details {
                grid-template-columns: 1fr;
            }

            .page-title {
                font-size: 1.5rem;
            }
        }

        .empty-history {
            text-align: center;
            padding: 40px 20px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .empty-history i {
            font-size: 3rem;
            color: #ddd;
            margin-bottom: 20px;
        }

        .empty-history p {
            color: #666;
            margin: 10px 0;
        }

        .shop-now-btn {
            display: inline-block;
            padding: 10px 20px;
            background: #ff6b6b;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            margin-top: 20px;
            transition: all 0.3s;
        }

        .shop-now-btn:hover {
            background: #ff5252;
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <div class="page-header">
        <a href="index.php" class="back-btn">
            <i class="fas fa-arrow-left"></i>
            <span>Back to Menu</span>
        </a>
        <h1 class="page-title">Order History</h1>
    </div>

    <div class="order-history-container">
        <?php if ($result->num_rows > 0): ?>
            <?php while ($order = $result->fetch_assoc()): ?>
                <div class="order-card">
                    <div class="order-header">
                        <div>
                            <span class="order-id">Order #<?php echo $order['id']; ?></span>
                            <span class="order-date"><?php echo date('F j, Y g:i A', strtotime($order['order_time'])); ?></span>
                        </div>
                        <span class="status-badge status-<?php echo strtolower($order['status']); ?>">
                            <?php echo $order['status']; ?>
                        </span>
                    </div>
                    <div class="order-details">
                        <div class="detail-group">
                            <span class="detail-label">Delivery Address</span>
                            <span class="detail-value"><?php echo htmlspecialchars($order['address']); ?></span>
                        </div>
                        <div class="detail-group">
                            <span class="detail-label">Payment Method</span>
                            <span class="detail-value"><?php echo ucfirst($order['method']); ?></span>
                        </div>
                        <div class="detail-group">
                            <span class="detail-label">Items</span>
                            <span class="detail-value"><?php echo $order['total_products']; ?></span>
                        </div>
                    </div>
                    <div class="order-items">
                        <?php
                        $items = explode(', ', $order['item_name']);
                        foreach ($items as $item):
                            // Extract quantity and item name from the format "quantity x item"
                            if (preg_match('/(\d+)\s*x\s*(.+)/', $item, $matches)):
                                $quantity = $matches[1];
                                $itemName = $matches[2];
                        ?>
                            <div class="item-row">
                                <span class="item-name"><?php echo htmlspecialchars($itemName); ?></span>
                                <span class="item-quantity">×<?php echo $quantity; ?></span>
                            </div>
                        <?php 
                            endif;
                        endforeach; 
                        ?>
                    </div>
                    <div class="order-total">
                        Total: ₱<?php echo number_format($order['total_price'], 2); ?>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="empty-history">
                <i class="fas fa-shopping-bag"></i>
                <h2>No Orders Yet</h2>
                <p>Looks like you haven't placed any orders yet.</p>
                <p>Start shopping to see your order history here!</p>
                <a href="index.php" class="shop-now-btn">
                    <i class="fas fa-shopping-cart"></i>
                    Shop Now
                </a>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // Add any interactive features here if needed
    </script>
</body>
</html>