<?php
session_start();
include "../connect.php";

// Check if user is logged in and is a crew member
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 3) {
    header("Location: ../loginpage.php");
    exit();
}

// Simple query to fetch all pending, preparing, and out for delivery orders
$query = "SELECT * FROM orders WHERE status IN ('pending', 'preparing', 'out for delivery') ORDER BY order_time DESC";
$result = $conn->query($query);
$orders = [];
while ($row = $result->fetch_assoc()) {
    $orders[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crew Dashboard - K-Food Delight</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #2563eb;
            --success-color: #22c55e;
            --warning-color: #eab308;
            --danger-color: #ef4444;
            --gray-100: #f3f4f6;
            --gray-200: #e5e7eb;
            --gray-300: #d1d5db;
            --gray-700: #374151;
            --gray-800: #1f2937;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background-color: var(--gray-100);
        }

        .dashboard {
            display: grid;
            grid-template-columns: 250px 1fr;
            min-height: 100vh;
        }

        .sidebar {
            background-color: white;
            padding: 1.5rem;
            border-right: 1px solid var(--gray-200);
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 2rem;
        }

        .logo img {
            width: 40px;
            height: 40px;
        }

        .nav-menu {
            list-style: none;
        }

        .nav-item {
            margin-bottom: 0.5rem;
        }

        .nav-link {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem 1rem;
            color: var(--gray-700);
            text-decoration: none;
            border-radius: 0.5rem;
            transition: all 0.3s ease;
        }

        .nav-link:hover, .nav-link.active {
            background-color: var(--gray-100);
            color: var(--primary-color);
        }

        .main-content {
            padding: 2rem;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        .orders-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 0.75rem;
            max-width: 100%;
        }

        .order-card {
            background: white;
            border-radius: 0.75rem;
            padding: 0.75rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            font-size: 0.85rem;
        }

        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid var(--gray-200);
        }

        .order-id {
            font-weight: 600;
            color: #2196F3;
            font-family: monospace;
            font-size: 1.2rem;
        }

        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: 9999px;
            font-size: 0.875rem;
            font-weight: 500;
        }

        .status-pending {
            background-color: var(--warning-color);
            color: white;
        }

        .status-preparing {
            background-color: var(--primary-color);
            color: white;
        }

        .status-out {
            background-color: var(--success-color);
            color: white;
        }

        .customer-info {
            margin-bottom: 0.5rem;
            padding: 0.5rem;
            background-color: var(--gray-100);
            border-radius: 0.5rem;
        }

        .order-items {
            margin-bottom: 0.5rem;
            padding: 0.5rem;
            background-color: var(--gray-100);
            border-radius: 0.5rem;
        }

        .order-items h4 {
            margin-bottom: 0.25rem;
        }

        .order-address {
            margin: 0.25rem 0;
            padding: 0.375rem;
            background-color: white;
            border-radius: 0.375rem;
        }

        .payment-method {
            margin: 0.25rem 0;
            padding: 0.375rem;
            background-color: white;
            border-radius: 0.375rem;
        }

        .order-total {
            font-size: 1.1rem;
            font-weight: 600;
            text-align: right;
            margin-top: 1rem;
        }

        .action-buttons {
            display: flex;
            gap: 0.5rem;
            margin-top: 1rem;
        }

        .btn {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 0.375rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            flex: 1;
        }

        .btn-primary {
            background-color: var(--primary-color);
            color: white;
        }

        .btn-success {
            background-color: var(--success-color);
            color: white;
        }

        .btn:hover {
            opacity: 0.9;
        }

        /* Notifications */
        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 1rem 2rem;
            border-radius: 8px;
            background: white;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            display: flex;
            align-items: center;
            gap: 0.75rem;
            transform: translateX(120%);
            transition: transform 0.3s ease;
            z-index: 1000;
        }

        .notification.show {
            transform: translateX(0);
        }

        .notification-success {
            border-left: 4px solid var(--success-color);
        }

        .notification-error {
            border-left: 4px solid var(--danger-color);
        }

        .notification i {
            font-size: 1.25rem;
        }

        .notification-success i {
            color: var(--success-color);
        }

        .notification-error i {
            color: var(--danger-color);
        }

        @media (max-width: 768px) {
            .dashboard {
                grid-template-columns: 1fr;
            }

            .sidebar {
                display: none;
            }
        }
    </style>
    <script src="js/order-status.js" defer></script>
</head>
<body>
    <div class="dashboard">
        <aside class="sidebar">
            <div class="logo">
                <img src="../images/logo.png" alt="K-Food Delight">
                <h2>Crew Panel</h2>
            </div>
            <nav>
                <ul class="nav-menu">
                    <li class="nav-item">
                        <a href="#" class="nav-link active">
                            <i class="fas fa-clipboard-list"></i>
                            <span>Orders</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="../logout.php" class="nav-link">
                            <i class="fas fa-sign-out-alt"></i>
                            <span>Logout</span>
                        </a>
                    </li>
                </ul>
            </nav>
        </aside>

        <main class="main-content">
            <div class="header">
                <h1>Order Processing</h1>
            </div>

            <div class="orders-grid">
                <?php foreach ($orders as $order): ?>
                    <div class="order-card">
                        <div class="order-header">
                            <span class="order-id">#<?php echo str_pad($order['id'], 5, '0', STR_PAD_LEFT); ?></span>
                            <span class="status-badge status-<?php echo strtolower($order['status']); ?>">
                                <?php echo ucfirst($order['status']); ?>
                            </span>
                        </div>

                        <div class="customer-info">
                            <h4><i class="fas fa-user"></i> Customer Details</h4>
                            <div class="customer-name">
                                <?php echo htmlspecialchars($order['name']); ?>
                            </div>
                        </div>

                        <div class="order-items">
                            <h4><i class="fas fa-shopping-bag"></i> Order Items</h4>
                            <?php if (!empty($order['item_name'])): ?>
                                <p><?php echo nl2br(htmlspecialchars($order['item_name'])); ?></p>
                                <p>Quantity: <?php echo htmlspecialchars($order['total_products']); ?></p>
                            <?php else: ?>
                                <p>No items found</p>
                            <?php endif; ?>
                        </div>

                        <div class="customer-info">
                            <div class="order-address">
                                <i class="fas fa-map-marker-alt"></i>
                                <strong>Delivery Address:</strong><br>
                                <?php echo htmlspecialchars($order['address']); ?>
                            </div>
                            <div class="payment-method">
                                <i class="fas fa-money-bill-wave"></i>
                                <strong>Payment Method:</strong>
                                <?php echo ucfirst(htmlspecialchars($order['method'])); ?>
                            </div>
                        </div>

                        <div class="order-total">
                            Total: ₱<?php echo number_format($order['total_price'], 2); ?>
                        </div>

                        <div class="action-buttons">
                            <?php if ($order['status'] == 'pending'): ?>
                                <button class="btn btn-primary" onclick="updateStatus(<?php echo $order['id']; ?>, 'preparing')">
                                    <i class="fas fa-utensils"></i> Start Preparing
                                </button>
                            <?php elseif ($order['status'] == 'preparing'): ?>
                                <button class="btn btn-success" onclick="updateStatus(<?php echo $order['id']; ?>, 'out for delivery')">
                                    <i class="fas fa-motorcycle"></i> Mark Out for Delivery
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </main>
    </div>

    <!-- JavaScript loaded from order-status.js -->
</body>
</html>